from fastapi import FastAPI, UploadFile, File, Form, types
from fastapi.middleware.cors import CORSMiddleware
import json, os
import chromadb
from sentence_transformers import SentenceTransformer
from openai import OpenAI
from pydantic import BaseModel
import ssl
# Disable SSL verification for local development
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Create SSL context that doesn't verify
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# --- Initialize app ---
app = FastAPI(title="MAA ERP Metadata RAG Service")

print("Python app started successfully")

# CORS for PHP/React frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# --- Initialize components ---
model = SentenceTransformer("all-MiniLM-L6-v2")
chroma_client = chromadb.EphemeralClient()
##chroma_client = chromadb.PersistentClient(path="./chroma_data")
##chroma_client = chromadb.HttpClient(host="localhost", port=8000)
collection_name = "payanarsstype_metadata"
metadata={"hnsw:space": "cosine"}

# Delete old collection (with empty embeddings)
try:
    chroma_client.delete_collection(name="payanarss_types")
    print("✅ Deleted old collection")
except Exception as e:
    print(f"Collection delete error: {e}")

# Create fresh collection
collection = chroma_client.create_collection(
    name="payanarss_types",
    metadata={"hnsw:space": "cosine"}
)

@app.get("/hello")
def hello():
    return {"message": "Hi!"}

# --- Endpoint 1: Build / Rebuild Index ---
@app.post("/metadata/index")
async def build_index():
    """Index with ChromaDB's built-in embeddings"""
    try:
        file_path = r"E:\Bhu\CS\Phps\Practices\VanakkamPayanarssTypes\meta_data\VanakkamPayanarssTypes.json"
        
        with open(file_path, "r", encoding='utf-8') as file:
            data = json.load(file)
        
        if isinstance(data, dict):
            types = data.get('types') or data.get('Attributes') or list(data.values())
        else:
            types = data
        
        if not isinstance(types, list):
            return {"error": f"Expected list, got {type(types)}"}
        
        indexed_count = 0
        batch_ids = []
        batch_documents = []
        batch_metadatas = []
        batch_size = 50
        
        for i, type_item in enumerate(types):
            if not isinstance(type_item, dict):
                continue
            
            name = type_item.get('Name', '')
            description = type_item.get('Description', '')
            type_id = type_item.get('PayanarssTypeId', '')
            item_id = type_item.get('Id', f'item_{i}')
            
            if not name:
                continue
            
            # Create searchable text
            searchable_text = f"{name} {description} {type_id}".strip()
            
            try:
                batch_ids.append(item_id)
                batch_documents.append(searchable_text)  # ✅ Only documents (Chroma creates embeddings)
                batch_metadatas.append({
                    'name': str(name),
                    'type_id': str(type_id),
                    'description': str(description)[:500],
                    'full_data': json.dumps(type_item)
                })
                
                indexed_count += 1
                
                # Batch insert every 50 items
                if indexed_count % batch_size == 0:
                    collection.add(
                        ids=batch_ids,
                        documents=batch_documents,  # ✅ ChromaDB creates embeddings automatically
                        metadatas=batch_metadatas
                    )
                    print(f"✅ Indexed {indexed_count}/{len(types)} types...")
                    
                    batch_ids = []
                    batch_documents = []
                    batch_metadatas = []
            
            except Exception as e:
                print(f"Error processing {name}: {str(e)}")
                continue
        
        # Insert remaining items
        if batch_ids:
            collection.add(
                ids=batch_ids,
                documents=batch_documents,
                metadatas=batch_metadatas
            )
        
        print(f"✅ Re-indexing complete! Indexed {indexed_count} types")
        return {
            "status": "success",
            "message": "Re-indexed with ChromaDB embeddings",
            "indexed": indexed_count,
            "total": len(types)
        }
    
    except Exception as e:
        import traceback
        print(traceback.format_exc())
        return {"error": str(e)}


class SearchQuery(BaseModel):
    prompt: str

# --- Endpoint 2: Query Metadata + OpenAI ---
@app.post("/metadata/search")
async def search_agents(query: SearchQuery):
    try:
        prompt = query.prompt
        
        if not prompt:
            return {"error": "Prompt required"}
        
        print(f"Searching for: {prompt}")
        
        # Generate embedding for search query
        query_embedding = model.encode(prompt).tolist()
        print(f"Embedding generated: {len(query_embedding)} dimensions")
        
        # Search in ChromaDB with lower threshold
        results = collection.query(
            query_embeddings=[query_embedding],
            n_results=20,  # Increased from 10
            include=['embeddings', 'documents', 'metadatas', 'distances']
        )
        
        print(f"Results found: {len(results['ids'][0]) if results['ids'] else 0}")
        
        # Format results - remove similarity threshold for now
        agents = []
        if results['ids'] and len(results['ids']) > 0:
            for i, agent_id in enumerate(results['ids'][0]):
                distance = results['distances'][0][i]
                similarity = max(0, 1 - distance)
                
                print(f"Item {i}: {agent_id} - Similarity: {similarity}")
                
                # Lowered threshold to 0.3 to see results
                if similarity > 0.3:
                    metadata = results['metadatas'][0][i]
                    agents.append({
                        'id': agent_id,
                        'name': metadata.get('name', 'Unknown'),
                        'similarity': round(similarity * 100, 1),
                        'distance': round(distance, 3),
                        'data': json.loads(metadata.get('full_data', '{}'))
                    })
        
        return {
            'query': prompt,
            'agents': agents,
            'count': len(agents),
            'total_results_checked': len(results['ids'][0]) if results['ids'] else 0
        }
    
    except Exception as e:
        import traceback
        print(f"Error: {traceback.format_exc()}")
        return {"error": str(e)}

@app.get("/debug/training-info")
async def training_info():
    """Check embedding model and collection metadata"""
    try:
        collection_data = collection.get()
        
        # Get first embedding to check dimension
        first_embedding = collection_data['embeddings'][0] if collection_data['embeddings'] else []
        
        return {
            "total_items": len(collection_data['ids']),
            "embedding_dimension": len(first_embedding) if first_embedding else 0,
            "sample_item": {
                "id": collection_data['ids'][0] if collection_data['ids'] else None,
                "name": collection_data['metadatas'][0]['name'] if collection_data['metadatas'] else None
            },
            "current_model": "all-MiniLM-L6-v2",
            "current_model_dimension": 384
        }
    except Exception as e:
        return {"error": str(e)}