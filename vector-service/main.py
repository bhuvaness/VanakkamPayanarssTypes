from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
import json, os
import chromadb
from sentence_transformers import SentenceTransformer
from openai import OpenAI

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
chroma_client = chromadb.Client()
collection_name = "payanarsstype_metadata"

# --- Ensure collection exists ---
if not chroma_client.list_collections():
    chroma_client.create_collection(collection_name)
collection = chroma_client.get_collection(collection_name)

# --- Endpoint 1: Build / Rebuild Index ---
@app.post("/metadata/index")
async def build_index(file: UploadFile = File(...)):
    try:
        data = json.load(file.file)
        docs = []
        for item in data:
            name = item.get("Name", "")
            desc = item.get("Description", "")
            rule_data = item.get("Attributes", [])
            text = f"Name: {name}\nDescription: {desc}\nRules: {rule_data}"
            docs.append(text)

        embeddings = model.encode(docs, show_progress_bar=True)
        chroma_client.delete_collection(collection_name)
        chroma_client.create_collection(collection_name)
        collection = chroma_client.get_collection(collection_name)

        for i, text in enumerate(docs):
            collection.add(documents=[text], embeddings=[embeddings[i]], ids=[str(i)])

        return {"message": f"Indexed {len(docs)} metadata records successfully."}
    except Exception as e:
        return {"error": str(e)}

# --- Endpoint 2: Query Metadata + OpenAI ---
@app.post("/metadata/query")
async def query_metadata(prompt: str = Form(...)):
    try:
        query_embedding = model.encode([prompt]).tolist()
        results = collection.query(query_embeddings=query_embedding, n_results=5)
        context = "\n\n".join(results["documents"][0])

        messages = [
            {
                "role": "system",
                "content": f"You are a metadata-aware ERP assistant. Use this context:\n{context}",
            },
            {"role": "user", "content": prompt},
        ]

        completion = client.chat.completions.create(model="gpt-4o-mini", messages=messages)
        answer = completion.choices[0].message.content
        return {"response": answer, "context_used": results["documents"][0]}
    except Exception as e:
        return {"error": str(e)}
