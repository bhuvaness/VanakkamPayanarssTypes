# PayanarssType Prompt Parsing & Conversion Architecture

## Design Overview

**Concept**: Convert natural language user prompts into hierarchical nested PayanarssType structures that represent the complete business logic.

**Flow**:
```
Natural Language Prompt
         ↓
    Claude AI
         ↓
PayanarssType Tree Structure
         ↓
Execute Logic
```

---

## Example: Prompt to PayanarssType Conversion

### User Prompt
```
"if amount is greater than 10000 send for approval to HR Manager"
```

### Converted PayanarssType Structure
```json
{
  "Id": "100000000000000000010000000000001",
  "Name": "if",
  "Attributes": [
    {
      "Id": "FIELD-AR-AMOUNT-001",
      "Name": "condition",
      "Attributes": [
        {
          "Id": "100000000000000000000000000000026",
          "Name": "greater_than",
          "Value": 10000,
          "Attributes": [
            {
              "Id": "100000000000000000000000000000032",
              "Name": "send_to",
              "Attributes": [
                {
                  "Id": "100000000000000000020000000000001",
                  "Name": "HR_Manager",
                  "Value": "approval"
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

### Structure Breakdown

```
Level 1: CONTROL FLOW
├─ Id: 100000000000000000010000000000001
├─ Name: "if"
└─ Attributes: [Condition evaluation]

  Level 2: CONDITION
  ├─ Id: FIELD-AR-AMOUNT-001
  ├─ Name: "condition" (field to evaluate)
  └─ Attributes: [Comparison operator]

    Level 3: OPERATOR
    ├─ Id: 100000000000000000000000000000026
    ├─ Name: "greater_than" (>) 
    ├─ Value: 10000 (comparison value)
    └─ Attributes: [Action on true]

      Level 4: ACTION
      ├─ Id: 100000000000000000000000000000032
      ├─ Name: "send_to" (action)
      └─ Attributes: [Target/Recipient]

        Level 5: TARGET
        ├─ Id: 100000000000000000020000000000001
        ├─ Name: "HR_Manager"
        └─ Value: "approval" (action type)
```

---

## PayanarssType ID Mapping Reference

### Control Flow Types
| Name | Id | Purpose |
|------|-----|---------|
| if | 100000000000000000010000000000001 | Conditional statement |
| else | 100000000000000000010000000000002 | Alternative path |
| else if | 100000000000000000010000000000003 | Additional condition |
| foreach | 100000000000000000010000000000011 | Loop through collection |
| while | 100000000000000000010000000000013 | Conditional loop |
| try | 100000000000000000010000000000019 | Exception handling |
| catch | 100000000000000000010000000000020 | Exception handler |
| finally | 100000000000000000010000000000021 | Cleanup block |

### Comparison Operators
| Name | Id | Example |
|------|-----|---------|
| greater_than | 100000000000000000000000000000026 | amount > 10000 |
| less_than | 100000000000000000000000000000027 | amount < 5000 |
| equals | 100000000000000000000000000000028 | status = 'Active' |
| not_equals | 100000000000000000000000000000029 | status != 'Inactive' |
| greater_equal | 100000000000000000000000000000030 | amount >= 10000 |
| less_equal | 100000000000000000000000000000031 | amount <= 5000 |

### Action Types
| Name | Id | Purpose |
|------|-----|---------|
| send_to | 100000000000000000000000000000032 | Send for approval |
| approve | 100000000000000000000000000000033 | Auto-approve |
| notify | 100000000000000000000000000000034 | Send notification |
| create | 100000000000000000000000000000035 | Create record |
| update | 100000000000000000000000000000036 | Update record |
| delete | 100000000000000000000000000000037 | Delete record |

### Data Sources (SQL)
| Name | Id | Purpose |
|------|-----|---------|
| SELECT | 100000000000000000020000000000001 | Retrieve data |
| FROM | 100000000000000000020000000000002 | Table source |
| WHERE | 100000000000000000020000000000003 | Filter condition |
| JOIN | 100000000000000000020000000000004 | Combine tables |
| GROUP_BY | 100000000000000000020000000000007 | Aggregate |
| ORDER_BY | 100000000000000000020000000000009 | Sort |

---

## Complete Example: Complex Workflow

### Prompt
```
"For each active employee, if salary is greater than 100000, 
send for director approval, else if greater than 50000, send for 
manager approval, else auto-approve"
```

### PayanarssType Structure

```json
{
  "Id": "100000000000000000010000000000011",
  "Name": "foreach",
  "Attributes": [
    {
      "Id": "FIELD-EMPLOYEE-001",
      "Name": "employee_collection",
      "Attributes": [
        {
          "Id": "100000000000000000020000000000003",
          "Name": "where",
          "Attributes": [
            {
              "Id": "FIELD-STATUS-001",
              "Name": "status",
              "Value": "Active",
              "Attributes": []
            }
          ]
        }
      ]
    },
    {
      "Id": "100000000000000000010000000000001",
      "Name": "if",
      "Attributes": [
        {
          "Id": "FIELD-SALARY-001",
          "Name": "salary",
          "Attributes": [
            {
              "Id": "100000000000000000000000000000026",
              "Name": "greater_than",
              "Value": 100000,
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000032",
                  "Name": "send_to",
                  "Value": "Director",
                  "Attributes": [
                    {
                      "Id": "100000000000000000000000000000005",
                      "Name": "approval_type",
                      "Value": "salary_review"
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "Id": "100000000000000000010000000000003",
          "Name": "else_if",
          "Attributes": [
            {
              "Id": "FIELD-SALARY-001",
              "Name": "salary",
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000026",
                  "Name": "greater_than",
                  "Value": 50000,
                  "Attributes": [
                    {
                      "Id": "100000000000000000000000000000032",
                      "Name": "send_to",
                      "Value": "Manager",
                      "Attributes": [
                        {
                          "Id": "100000000000000000000000000000005",
                          "Name": "approval_type",
                          "Value": "salary_review"
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "Id": "100000000000000000010000000000002",
          "Name": "else",
          "Attributes": [
            {
              "Id": "100000000000000000000000000000033",
              "Name": "approve",
              "Value": "auto",
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000034",
                  "Name": "notify",
                  "Value": "Employee"
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Processing Workflow

### Step 1: Prompt Analysis
```
Input: "if amount is greater than 10000 send for approval to HR Manager"

Claude AI extracts:
├─ Control: "if" → 100000000000000000010000000000001
├─ Field: "amount" → FIELD-AR-AMOUNT-001
├─ Operator: "greater than" → 100000000000000000000000000000026
├─ Value: 10000
└─ Action: "send for approval" → 100000000000000000000000000000032
```

### Step 2: Tree Construction
```
Build nested PayanarssType tree:
└─ if (100000000000000000010000000000001)
   └─ condition: amount
      └─ greater_than (100000000000000000000000000000026)
         └─ value: 10000
            └─ send_to (100000000000000000000000000000032)
               └─ HR_Manager
```

### Step 3: Execution Engine
```
Execute tree:
├─ Evaluate: amount > 10000
├─ If TRUE: 
│  └─ send_to(HR_Manager, "approval")
├─ If FALSE:
│  └─ Do nothing (no else defined)
└─ Return: Execution result
```

---

## Mapping Common Business Keywords to PayanarssTypes

### Approval Keywords
| Keyword | Maps To | PayanarssType Id |
|---------|---------|-----------------|
| "send for approval" | send_to (Action) | 100000000000000000000000000000032 |
| "auto-approve" | approve (Action) | 100000000000000000000000000000033 |
| "reject" | reject (Action) | 100000000000000000000000000000038 |
| "route to" | send_to (Action) | 100000000000000000000000000000032 |
| "escalate to" | escalate (Action) | 100000000000000000000000000000039 |

### Data Keywords
| Keyword | Maps To | PayanarssType Id |
|---------|---------|-----------------|
| "greater than" | greater_than (Operator) | 100000000000000000000000000000026 |
| "less than" | less_than (Operator) | 100000000000000000000000000000027 |
| "equals" | equals (Operator) | 100000000000000000000000000000028 |
| "between" | between (Operator) | 100000000000000000000000000000040 |
| "in" | in (Operator) | 100000000000000000000000000000041 |

### Loop Keywords
| Keyword | Maps To | PayanarssType Id |
|---------|---------|-----------------|
| "for each" | foreach (Loop) | 100000000000000000010000000000011 |
| "for all" | foreach (Loop) | 100000000000000000010000000000011 |
| "all" | foreach (Loop) | 100000000000000000010000000000011 |
| "each employee in" | foreach (Loop) | 100000000000000000010000000000011 |

---

## Real-World MAA ERP Examples

### Example 1: Purchase Order Approval Workflow

**Prompt:**
```
"If purchase order amount is greater than 100000, send to Director for approval, 
else if greater than 50000, send to Manager, else auto approve"
```

**PayanarssType Structure:**
```json
{
  "Id": "100000000000000000010000000000001",
  "Name": "if",
  "Attributes": [
    {
      "Id": "FIELD-PO-AMOUNT-001",
      "Name": "po_amount",
      "Attributes": [
        {
          "Id": "100000000000000000000000000000026",
          "Name": "greater_than",
          "Value": 100000,
          "Attributes": [
            {
              "Id": "100000000000000000000000000000032",
              "Name": "send_to",
              "Value": "Director",
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000005",
                  "Name": "action",
                  "Value": "approval"
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

**Execution:**
```
1. Evaluate: po_amount > 100000
2. If TRUE: Route to Director for approval
3. If FALSE: Check next condition (else if)
4. Evaluate: po_amount > 50000
5. If TRUE: Route to Manager for approval
6. If FALSE: Auto-approve
```

---

### Example 2: Payroll Processing Workflow

**Prompt:**
```
"For each active employee, get salary from database, 
calculate tax, deduct PF, generate salary slip"
```

**PayanarssType Structure:**
```json
{
  "Id": "100000000000000000010000000000011",
  "Name": "foreach",
  "Attributes": [
    {
      "Id": "FIELD-EMPLOYEES-001",
      "Name": "employees",
      "Attributes": [
        {
          "Id": "100000000000000000020000000000001",
          "Name": "SELECT",
          "Attributes": [
            {
              "Id": "FIELD-SALARY-001",
              "Name": "salary"
            },
            {
              "Id": "FIELD-DEPT-001",
              "Name": "department"
            }
          ]
        },
        {
          "Id": "100000000000000000020000000000003",
          "Name": "WHERE",
          "Attributes": [
            {
              "Id": "FIELD-STATUS-001",
              "Name": "status",
              "Value": "Active"
            }
          ]
        }
      ]
    },
    {
      "Id": "100000000000000000000000000000036",
      "Name": "calculate_tax",
      "Attributes": [
        {
          "Id": "FIELD-SALARY-001",
          "Name": "salary",
          "Attributes": []
        }
      ]
    },
    {
      "Id": "100000000000000000000000000000037",
      "Name": "deduct_pf",
      "Attributes": [
        {
          "Id": "FIELD-SALARY-001",
          "Name": "salary",
          "Value": "12%"
        }
      ]
    },
    {
      "Id": "100000000000000000000000000000035",
      "Name": "generate_salary_slip",
      "Attributes": []
    }
  ]
}
```

---

### Example 3: Leave Approval Workflow

**Prompt:**
```
"If leave type is casual and days is less than 3, auto approve, 
else if it's sick leave, auto approve, 
else send to manager for approval"
```

**PayanarssType Structure:**
```json
{
  "Id": "100000000000000000010000000000001",
  "Name": "if",
  "Attributes": [
    {
      "Id": "FIELD-LEAVE-TYPE-001",
      "Name": "leave_type",
      "Attributes": [
        {
          "Id": "100000000000000000000000000000028",
          "Name": "equals",
          "Value": "Casual",
          "Attributes": [
            {
              "Id": "FIELD-DAYS-001",
              "Name": "days",
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000027",
                  "Name": "less_than",
                  "Value": 3,
                  "Attributes": [
                    {
                      "Id": "100000000000000000000000000000033",
                      "Name": "approve",
                      "Value": "auto"
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    },
    {
      "Id": "100000000000000000010000000000003",
      "Name": "else_if",
      "Attributes": [
        {
          "Id": "FIELD-LEAVE-TYPE-001",
          "Name": "leave_type",
          "Attributes": [
            {
              "Id": "100000000000000000000000000000028",
              "Name": "equals",
              "Value": "Sick",
              "Attributes": [
                {
                  "Id": "100000000000000000000000000000033",
                  "Name": "approve",
                  "Value": "auto"
                }
              ]
            }
          ]
        }
      ]
    },
    {
      "Id": "100000000000000000010000000000002",
      "Name": "else",
      "Attributes": [
        {
          "Id": "100000000000000000000000000000032",
          "Name": "send_to",
          "Value": "Manager",
          "Attributes": [
            {
              "Id": "100000000000000000000000000000005",
              "Name": "action",
              "Value": "approval"
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Implementation Steps for Prompt Parsing

### Step 1: Create Prompt Parser Service
```python
@app.post("/parse-prompt")
async def parse_prompt(request: PromptRequest):
    """
    Parse natural language prompt into PayanarssType structure
    """
    prompt = request.prompt
    
    # 1. Send to Claude to extract intent
    claude_response = call_claude(prompt)
    
    # 2. Parse Claude response into PayanarssType components
    components = extract_components(claude_response)
    
    # 3. Build nested PayanarssType tree
    payanarss_tree = build_payanarss_tree(components)
    
    # 4. Return structure
    return payanarss_tree
```

### Step 2: Map Keywords to IDs
```python
keyword_mapping = {
    "if": "100000000000000000010000000000001",
    "else": "100000000000000000010000000000002",
    "else if": "100000000000000000010000000000003",
    "foreach": "100000000000000000010000000000011",
    "greater than": "100000000000000000000000000000026",
    "less than": "100000000000000000000000000000027",
    "equals": "100000000000000000000000000000028",
    "send to": "100000000000000000000000000000032",
    "approve": "100000000000000000000000000000033",
    "select": "100000000000000000020000000000001",
    "from": "100000000000000000020000000000002",
    "where": "100000000000000000020000000000003",
    # ... more mappings
}
```

### Step 3: Build Tree Structure
```python
def build_payanarss_tree(components):
    """
    Build nested PayanarssType tree from components
    """
    root = {
        "Id": keyword_mapping[components['control']],
        "Name": components['control'],
        "Attributes": []
    }
    
    # Add condition
    condition = {
        "Id": components['field_id'],
        "Name": components['field_name'],
        "Attributes": []
    }
    
    # Add operator
    operator = {
        "Id": keyword_mapping[components['operator']],
        "Name": components['operator'],
        "Value": components['value'],
        "Attributes": []
    }
    
    # Add action
    action = {
        "Id": keyword_mapping[components['action']],
        "Name": components['action'],
        "Attributes": []
    }
    
    # Build tree
    operator['Attributes'].append(action)
    condition['Attributes'].append(operator)
    root['Attributes'].append(condition)
    
    return root
```

### Step 4: Execute PayanarssType Tree
```python
@app.post("/execute-payanarss")
async def execute_payanarss(request: PayanarssExecuteRequest):
    """
    Execute PayanarssType tree structure
    """
    payanarss_tree = request.payanarss_tree
    data_context = request.data_context
    
    # 1. Parse tree recursively
    result = execute_tree(payanarss_tree, data_context)
    
    # 2. Return execution result
    return {
        "status": "success",
        "result": result,
        "affected_records": result['count']
    }
```

---

## Benefits of This Architecture

### For Users
✅ **Natural Language**: Describe workflows in plain English
✅ **No Coding**: No technical knowledge required
✅ **Flexible**: Any business logic can be expressed
✅ **Reusable**: Save and reuse workflows
✅ **Transparent**: See the PayanarssType structure

### For System
✅ **Structured**: Parsed into strict PayanarssType format
✅ **Semantic**: Understands business logic
✅ **Executable**: Can be executed immediately
✅ **Trainable**: Vector DB can learn patterns
✅ **Auditable**: Every decision is tracked

### For Enterprise
✅ **Automation**: Reduce manual processes
✅ **Consistency**: Same logic everywhere
✅ **Compliance**: Audit trail of decisions
✅ **Scalability**: Handle 1000s of workflows
✅ **Governance**: Control business logic

---

## Next Steps

1. **Define Complete ID Mapping**: Map all business keywords to PayanarssType IDs
2. **Create Prompt Parser**: Build Claude-powered prompt parser
3. **Implement Tree Builder**: Create PayanarssType tree construction
4. **Build Executor**: Implement execution engine
5. **Add UI**: Create workflow designer UI
6. **Test & Iterate**: POC with real workflows

---

## Summary

Your design converts **natural language prompts into executable PayanarssType trees**.

**Example Flow:**
```
Prompt: "if amount > 10000 send for approval"
       ↓
Claude Analysis:
├─ Control: "if" (100000000000000000010000000000001)
├─ Field: "amount" (FIELD-AR-AMOUNT-001)
├─ Operator: ">" (100000000000000000000000000000026)
├─ Value: 10000
└─ Action: "send for approval" (100000000000000000000000000000032)
       ↓
PayanarssType Tree:
{
  "Id": "if",
  "Attributes": [
    {
      "Id": "amount",
      "Attributes": [
        {
          "Id": ">",
          "Value": 10000,
          "Attributes": [
            {"Id": "send_to"}
          ]
        }
      ]
    }
  ]
}
       ↓
Execution:
Evaluate → Route → Execute
```

This makes MAA ERP truly **zero-configuration and AI-driven**! 🚀
