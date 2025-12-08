# PayanarssType Prompt Parsing - Technical Implementation Guide

## Quick Start: Prompt to PayanarssType Conversion

### Your Design Pattern

```
Natural Language Input
         ↓
   Claude AI Analysis
         ↓
Component Extraction
- Control Flow (if/else/foreach/etc)
- Field Names (amount, salary, status)
- Operators (>, <, ==, etc)
- Values (10000, "Active", etc)
- Actions (send_to, approve, notify)
         ↓
Build Nested PayanarssType Tree
         ↓
Execute via Interpreter
```

---

## Step 1: Claude Prompt Template

```python
PROMPT_PARSING_TEMPLATE = """
Analyze this business rule and extract components:

"{user_prompt}"

Extract and return as JSON:
{
  "control_flow": "if/else/foreach/while/try/etc",
  "conditions": [
    {
      "field": "field_name",
      "operator": ">/</==/!=/between/in/like",
      "value": "value_or_array"
    }
  ],
  "actions": [
    {
      "type": "send_to/approve/notify/create/update/delete/etc",
      "target": "target_value",
      "parameters": {}
    }
  ],
  "else_conditions": [optional],
  "else_actions": [optional]
}

Return ONLY valid JSON, no other text.
"""
```

**Example:**
```
Input: "if amount is greater than 10000 send for approval to HR Manager"

Claude Output:
{
  "control_flow": "if",
  "conditions": [
    {
      "field": "amount",
      "operator": ">",
      "value": 10000
    }
  ],
  "actions": [
    {
      "type": "send_to",
      "target": "HR Manager",
      "parameters": {"action_type": "approval"}
    }
  ]
}
```

---

## Step 2: Operator ID Mapping

```python
OPERATOR_MAPPING = {
    ">": "100000000000000000000000000000026",        # greater_than
    "<": "100000000000000000000000000000027",        # less_than
    "==": "100000000000000000000000000000028",       # equals
    "!=": "100000000000000000000000000000029",       # not_equals
    ">=": "100000000000000000000000000000030",       # greater_equal
    "<=": "100000000000000000000000000000031",       # less_equal
    "between": "100000000000000000000000000000040",  # between
    "in": "100000000000000000000000000000041",       # in
    "like": "100000000000000000000000000000042",     # pattern match
}

CONTROL_FLOW_MAPPING = {
    "if": "100000000000000000010000000000001",
    "else": "100000000000000000010000000000002",
    "else if": "100000000000000000010000000000003",
    "foreach": "100000000000000000010000000000011",
    "while": "100000000000000000010000000000013",
    "do-while": "100000000000000000010000000000014",
    "try": "100000000000000000010000000000019",
    "catch": "100000000000000000010000000000020",
    "finally": "100000000000000000010000000000021",
}

ACTION_MAPPING = {
    "send_to": "100000000000000000000000000000032",
    "approve": "100000000000000000000000000000033",
    "notify": "100000000000000000000000000000034",
    "create": "100000000000000000000000000000035",
    "update": "100000000000000000000000000000036",
    "delete": "100000000000000000000000000000037",
    "reject": "100000000000000000000000000000038",
    "escalate": "100000000000000000000000000000039",
}

SQL_MAPPING = {
    "SELECT": "100000000000000000020000000000001",
    "FROM": "100000000000000000020000000000002",
    "WHERE": "100000000000000000020000000000003",
    "JOIN": "100000000000000000020000000000004",
    "GROUP_BY": "100000000000000000020000000000007",
    "ORDER_BY": "100000000000000000020000000000009",
}

FIELD_PREFIX = "FIELD-"
```

---

## Step 3: Build PayanarssType Tree

```python
def build_payanarss_tree(claude_output):
    """
    Convert Claude extracted components into nested PayanarssType structure
    """
    
    root = {
        "Id": CONTROL_FLOW_MAPPING.get(claude_output['control_flow']),
        "Name": claude_output['control_flow'],
        "Attributes": []
    }
    
    # Build condition tree
    condition_tree = build_condition_tree(claude_output['conditions'])
    
    # Build action tree
    action_tree = build_action_tree(claude_output['actions'])
    
    # Attach to root
    condition_tree['Attributes'].append(action_tree)
    root['Attributes'].append(condition_tree)
    
    # Handle else conditions if present
    if 'else_conditions' in claude_output and claude_output['else_conditions']:
        else_tree = build_else_tree(
            claude_output['else_conditions'],
            claude_output.get('else_actions', [])
        )
        root['Attributes'].append(else_tree)
    
    return root


def build_condition_tree(conditions):
    """
    Build nested condition attribute tree
    """
    if not conditions:
        return {}
    
    # First condition
    cond = conditions[0]
    field_id = f"{FIELD_PREFIX}{cond['field'].upper()}-001"
    
    operator_id = OPERATOR_MAPPING.get(cond['operator'])
    
    condition_node = {
        "Id": field_id,
        "Name": cond['field'],
        "Attributes": [
            {
                "Id": operator_id,
                "Name": cond['operator'],
                "Value": cond['value'],
                "Attributes": []
            }
        ]
    }
    
    return condition_node


def build_action_tree(actions):
    """
    Build nested action attribute tree
    """
    if not actions:
        return {}
    
    # First action
    action = actions[0]
    action_id = ACTION_MAPPING.get(action['type'])
    
    action_node = {
        "Id": action_id,
        "Name": action['type'],
        "Value": action.get('target', ''),
        "Attributes": []
    }
    
    # Add parameters as child attributes
    for key, value in action.get('parameters', {}).items():
        action_node['Attributes'].append({
            "Id": f"PARAM-{key.upper()}-001",
            "Name": key,
            "Value": value
        })
    
    return action_node


def build_else_tree(else_conditions, else_actions):
    """
    Build else/else if branch
    """
    else_node = {
        "Id": CONTROL_FLOW_MAPPING.get('else if' if else_conditions else 'else'),
        "Name": "else if" if else_conditions else "else",
        "Attributes": []
    }
    
    if else_conditions:
        cond_tree = build_condition_tree(else_conditions)
        action_tree = build_action_tree(else_actions)
        cond_tree['Attributes'].append(action_tree)
        else_node['Attributes'].append(cond_tree)
    else:
        action_tree = build_action_tree(else_actions)
        else_node['Attributes'].append(action_tree)
    
    return else_node
```

---

## Step 4: Complete API Endpoint

```python
from fastapi import FastAPI
from pydantic import BaseModel
import json

app = FastAPI()

class PromptParsingRequest(BaseModel):
    prompt: str
    context: dict = {}

class PayanarssTreeResponse(BaseModel):
    payanarss_tree: dict
    components: dict
    execution_ready: bool

@app.post("/parse-prompt-to-payanarss")
async def parse_prompt_to_payanarss(request: PromptParsingRequest):
    """
    Convert natural language prompt to PayanarssType tree
    
    Example:
    {
        "prompt": "if amount is greater than 10000 send for approval to HR Manager"
    }
    
    Returns:
    {
        "payanarss_tree": {nested structure},
        "components": {extracted components},
        "execution_ready": true
    }
    """
    try:
        # Step 1: Send to Claude for analysis
        claude_output = await call_claude_for_parsing(request.prompt)
        print(f"Claude output: {claude_output}")
        
        # Step 2: Build PayanarssType tree
        payanarss_tree = build_payanarss_tree(claude_output)
        print(f"PayanarssType tree: {json.dumps(payanarss_tree, indent=2)}")
        
        # Step 3: Validate tree structure
        is_valid = validate_payanarss_tree(payanarss_tree)
        
        return {
            "payanarss_tree": payanarss_tree,
            "components": claude_output,
            "execution_ready": is_valid
        }
        
    except Exception as e:
        return {
            "error": str(e),
            "execution_ready": False
        }


async def call_claude_for_parsing(prompt: str):
    """
    Call Claude to extract components from prompt
    """
    from anthropic import Anthropic
    
    client = Anthropic()
    
    system_prompt = PROMPT_PARSING_TEMPLATE
    
    response = client.messages.create(
        model="claude-3-5-sonnet-20241022",
        max_tokens=1000,
        system=system_prompt,
        messages=[
            {
                "role": "user",
                "content": prompt
            }
        ]
    )
    
    # Extract and parse JSON response
    response_text = response.content[0].text
    
    # Try to parse JSON
    try:
        components = json.loads(response_text)
        return components
    except json.JSONDecodeError:
        # Clean and retry
        cleaned = response_text.strip()
        if cleaned.startswith("```json"):
            cleaned = cleaned[7:]
        if cleaned.endswith("```"):
            cleaned = cleaned[:-3]
        components = json.loads(cleaned)
        return components


def validate_payanarss_tree(tree):
    """
    Validate PayanarssType tree structure
    """
    required_fields = ['Id', 'Name', 'Attributes']
    
    def validate_node(node):
        if isinstance(node, dict):
            # Check required fields
            if 'Id' not in node:
                return False
            # Recursively check attributes
            if 'Attributes' in node:
                if isinstance(node['Attributes'], list):
                    for attr in node['Attributes']:
                        if not validate_node(attr):
                            return False
            return True
        return False
    
    return validate_node(tree)
```

---

## Step 5: Execution Engine

```python
async def execute_payanarss_tree(tree: dict, data_context: dict):
    """
    Execute PayanarssType tree against data
    """
    
    def execute_node(node, context):
        """
        Recursively execute PayanarssType node
        """
        control_type = node['Name']
        attributes = node.get('Attributes', [])
        
        if control_type == 'if':
            # Evaluate condition
            condition_result = evaluate_condition(attributes[0], context)
            
            if condition_result:
                # Execute true action
                action_node = attributes[0]['Attributes'][0]  # Condition → Operator → Action
                return execute_action(action_node, context)
            else:
                # Check for else/else if
                if len(attributes) > 1:
                    return execute_node(attributes[1], context)
                return {"status": "not_executed"}
        
        elif control_type == 'foreach':
            # Get collection
            collection = get_collection_from_context(attributes[0], context)
            results = []
            
            for item in collection:
                # Add item to context
                item_context = {**context, 'current_item': item}
                
                # Execute logic for each item
                if len(attributes) > 1:
                    result = execute_node(attributes[1], item_context)
                    results.append(result)
            
            return {
                "status": "success",
                "processed": len(results),
                "results": results
            }
        
        elif control_type in ['else', 'else if']:
            condition_result = evaluate_condition(attributes[0], context)
            
            if condition_result or control_type == 'else':
                action_node = attributes[0]['Attributes'][0]
                return execute_action(action_node, context)
            
            return {"status": "not_executed"}
        
        else:
            return execute_action(node, context)
    
    def evaluate_condition(condition_node, context):
        """
        Evaluate condition node
        """
        field_name = condition_node['Name']
        field_value = context.get(field_name)
        
        if 'Attributes' in condition_node and condition_node['Attributes']:
            operator_node = condition_node['Attributes'][0]
            operator = operator_node['Name']
            compare_value = operator_node.get('Value')
            
            if operator == '>':
                return field_value > compare_value
            elif operator == '<':
                return field_value < compare_value
            elif operator == '==':
                return field_value == compare_value
            elif operator == '!=':
                return field_value != compare_value
            elif operator == '>=':
                return field_value >= compare_value
            elif operator == '<=':
                return field_value <= compare_value
        
        return False
    
    def execute_action(action_node, context):
        """
        Execute action node
        """
        action_type = action_node['Name']
        target = action_node.get('Value', '')
        
        if action_type == 'send_to':
            return {
                "status": "success",
                "action": "send_to",
                "target": target,
                "message": f"Routed to {target} for approval"
            }
        
        elif action_type == 'approve':
            return {
                "status": "success",
                "action": "approve",
                "message": "Auto-approved"
            }
        
        elif action_type == 'notify':
            return {
                "status": "success",
                "action": "notify",
                "target": target,
                "message": f"Notified {target}"
            }
        
        else:
            return {
                "status": "success",
                "action": action_type,
                "target": target
            }
    
    def get_collection_from_context(collection_node, context):
        """
        Get collection from context
        """
        collection_name = collection_node['Name']
        return context.get(collection_name, [])
    
    # Execute tree
    return execute_node(tree, data_context)
```

---

## Step 6: Complete Workflow Example

```python
# Example 1: Simple if-then workflow

@app.post("/workflow/purchase-order-approval")
async def po_approval_workflow(po_data: dict):
    """
    Workflow: if po_amount > 100000, send to Director, else if > 50000, send to Manager
    """
    
    # Step 1: Parse prompt (one-time during setup)
    prompt = "if amount is greater than 100000 send for approval to Director"
    parse_response = await parse_prompt_to_payanarss(
        PromptParsingRequest(prompt=prompt)
    )
    payanarss_tree = parse_response['payanarss_tree']
    
    # Step 2: Execute workflow with data
    context = {
        "amount": po_data['amount'],
        "po_id": po_data['po_id'],
        "requester": po_data['requester']
    }
    
    result = await execute_payanarss_tree(payanarss_tree, context)
    
    return result


# Example 2: Complex foreach workflow

@app.post("/workflow/payroll-processing")
async def payroll_workflow(batch_data: dict):
    """
    Workflow: for each employee, calculate salary, deduct tax, generate slip
    """
    
    # Step 1: Parse prompt
    prompt = """
    for each active employee, 
    select salary from database,
    calculate tax at 20%,
    deduct pf at 12%,
    generate salary slip
    """
    
    parse_response = await parse_prompt_to_payanarss(
        PromptParsingRequest(prompt=prompt)
    )
    payanarss_tree = parse_response['payanarss_tree']
    
    # Step 2: Prepare context
    employees = fetch_active_employees()  # Get from DB
    
    context = {
        "employees": employees,
        "tax_rate": 0.20,
        "pf_rate": 0.12
    }
    
    # Step 3: Execute
    result = await execute_payanarss_tree(payanarss_tree, context)
    
    return result
```

---

## Testing Your Implementation

```python
# Test 1: Simple if
test_prompt_1 = "if amount is greater than 10000 send for approval to HR Manager"

response_1 = await parse_prompt_to_payanarss(
    PromptParsingRequest(prompt=test_prompt_1)
)

print(json.dumps(response_1['payanarss_tree'], indent=2))


# Test 2: If-else if-else
test_prompt_2 = """
if salary greater than 100000, send to Director,
else if greater than 50000, send to Manager,
else auto approve
"""

response_2 = await parse_prompt_to_payanarss(
    PromptParsingRequest(prompt=test_prompt_2)
)

print(json.dumps(response_2['payanarss_tree'], indent=2))


# Test 3: Foreach
test_prompt_3 = "for each employee, calculate salary, generate slip, save"

response_3 = await parse_prompt_to_payanarss(
    PromptParsingRequest(prompt=test_prompt_3)
)

print(json.dumps(response_3['payanarss_tree'], indent=2))


# Test execution
tree = response_1['payanarss_tree']
context = {"amount": 15000}  # > 10000

execution_result = await execute_payanarss_tree(tree, context)
print(execution_result)
# Expected: {"status": "success", "action": "send_to", "target": "HR Manager", ...}
```

---

## Key Insights for Your POC

1. **Hierarchical Structure**: PayanarssType nests to represent logic hierarchy
2. **Field Naming**: FIELD-{FIELD_NAME}-{INDEX} for field references
3. **ID Mapping**: Every keyword has a unique ID
4. **Attributes Array**: Represents nested conditions/actions
5. **Value Storage**: Actual values stored in "Value" field
6. **Execution**: Traverse tree recursively, evaluate conditions, execute actions

This architecture makes your MAA ERP truly intelligent and flexible! 🚀
