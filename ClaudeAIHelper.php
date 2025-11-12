<?php

$config = require 'config.php';
$myCre = $config['anthropic']['my_cre'];

function callClaude(string $parentId, string $prompt, $types = []): string
{
  global $myCre;

  $allTypesJson = json_encode($types, JSON_PRETTY_PRINT);

  $systemPrompt = <<<EOT
You are a PayanarssType metadata generator.

ParentId: $parentId

$allTypesJson

Your ONLY job is to convert natural-language business rules or field definitions into structured JSON objects following the exact PayanarssType schema.
Respond ONLY with a valid JSON object. Do NOT include any explanation, PHP object notation, or extra fields. Do NOT wrap the response in any class or array. Output must begin with `{` and end with `}`.
If you cannot follow this format, respond with: {"error": "Invalid format"}

FORMAT STRICTLY:
{
    "Id": "<GUID or unique identifier>",
    "ParentId": "<ParentId GUID>",
    "Name": "<FieldName or RuleName>",
    "PayanarssTypeId": "<PayanarssType GUID>",
    "Attributes": [ { "Id": "<AttributeTypeId>", "Value": "<AttributeValue>" } ],
    "Description": "<Description or null>"
}

No text, markdown, or commentary outside JSON.

Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named 'Id', always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
- Whenever you generate PayanarssType metadata, always return the result as an array of PayanarssType objects, even if there is only one. Each array element must be a complete PayanarssType object.

ATTRIBUTE ID RULE:
- The "<AttributeTypeId>" field must always be one of the known "PayanarssTypeId" values.
- Use the correct "PayanarssTypeId" from the provided list as the "<AttributeTypeId>" value for each object.
- Each metadata object represents a PayanarssType instance, so its "<AttributeTypeId>" must match its "PayanarssTypeId".

ATTRIBUTE VALUE RULE:
- Each item in "Attributes" must be an object with "Id" and "Value".
- The "Value" must be derived from the meaning or implication of the "Description" field.
- For example:
  - If the description says "This field is mandatory", then Value = "true"
  - If the description says "Maximum allowed is 10", then Value = "10"
  - If the description implies uniqueness, then Value = "true"
- Do NOT return generic strings like "Mandatory" or "Unique" as values.
- Always infer a meaningful, context-based value from the description.
EOT;

  $data = [
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4096,
    'system' => $systemPrompt,
    'messages' => [
      ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.3,
  ];

  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $myCre,
    'Content-Type: application/json',
    'anthropic-version: 2023-06-01'
  ]);

  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  ini_set('max_execution_time', '120');
  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
    curl_close($ch);
    return '{}';
  }

  curl_close($ch);
  $decoded = json_decode($result, true);
  return $decoded['content'][0]['text'] ?? '{}';
}

function callClaudeV1(PayanarssType $payanarssType, $types = [])
{
  global $myCre;

  $allRules = "";
  $parentId = $payanarssType->Id;
  $allTypesJson = json_encode($types, JSON_PRETTY_PRINT);

  foreach ($payanarssType->Children as $child) {
    $allRules .= "Parent Id: " . $child->Id . ", Rules: " . "Child Description : " . $child->Description . "\n\n";
  }

  $systemPrompt = <<<EOT
You are a metadata assistant working with the PayanarssType structure:

$allTypesJson

Task:
- Convert the following field rule into PayanarssType-compliant JSON objects.
- Use "ParentId": $parentId for all generated objects.
- Use valid GUIDs for "Id".
- Return only valid JSON, not text or explanation.

Rules-to-ID mapping:
- "Required | Mandatory" → "100000000000000000000000000000012"
- "Maximum | To | Date To" → "100000000000000000000000000000017"
- "Minimum | From | Date From" → "100000000000000000000000000000016"
- "GUID" → "100000000000000000000000000000018"
- "Text" → "100000000000000000000000000000006"
- "Number" → "100000000000000000000000000000007"
- "Boolean" → "100000000000000000000000000000009"
- "DateTime" → "100000000000000000000000000000008"
- "Unique" → "100000000000000000000000000000013"
- "Auto Generate or Auto-updated" → "100000000000000000000000000000024"
- "Validate Before Create" → "100000000000000000000000000000025"
- "Default Value" → "100000000000000000000000000000029"
- "GreaterThan" → "100000000000000000000000000000026"
- "LessThan" → "100000000000000000000000000000027"

Output format:
[
  {
    "Parent Id": "$parentId",
    "attributes": [
      { "<RuleTypeId>": "<Value>" }
    ]
  }
]

Ex: 1
Rules: "Required; GUID; Auto-generated; Auto-updated unique Employee identifier."
[
  {
    "ParentId": "$parentId",
    "attributes": [
      {"100000000000000000000000000000016": "True"},     // IsRequired
      {"100000000000000000000000000000022": "True"},     // GUID
      {"100000000000000000000000000000013": "True"}      // EmployeeId type
    ]
  }
]
Ex: 2
Rules: "Required; Text; Pay element name; Max length 100."
[
  {
    "ParentId": "$parentId",
    "Attributes": [
      {"100000000000000000000000000000016": "True"},     // IsRequired
      {"100000000000000000000000000000018": "100"}       // Maximum
    ]
  }
]
Ex: 3
Field: GenderId
Description: Required; Lookup; Must reference Gender master.
Attributes:
  - Id: "100000000000000000000000000000003" (Lookup Source)
    Value: "689b2769ac2f3" (Refers to Gender master table)
  - Id: "100000000000000000000000000000012" (Required)
    Value: "True"

Example 4:
Rule: "Age must be > 18"
Output:
{
  "ParentId": "$parentId",
  "Attributes": [
    {"Id": "100000000000000000000000000000028", "Value": "True"},
    {"Id": "100000000000000000000000000000026", "Value": "18"}
  ]
}

Example 5:
Rule: "Cannot be future. Age must be > 18"
Output:
{
  "ParentId": "$parentId",
  "Attributes": [
    {"Id": "100000000000000000000000000000012", "Value": "True"},
    {"Id": "100000000000000000000000000000008", "Value": "True"},
    {"Id": "100000000000000000000000000000028", "Value": "True"},
    {"Id": "100000000000000000000000000000026", "Value": "18"}
  ]
}

IMPORTANT:
- Do NOT return PayanarssType objects.
- Do NOT include fields named "Id", "Name", "PayanarssTypeId", or "Description" at the top level.
- Do NOT wrap the output in code fences.
- Output MUST be valid JSON, no comments, no trailing commas.

Conversion logic:
- "must be > X" → Add Attribute Id=100000000000000000000000000000026, Value=X
- "cannot be future" or "must not be a future date" → Add Attribute Id=100000000000000000000000000000008, Value="True"
- "age" or "birth date" → Add Attribute Id=100000000000000000000000000000028, Value="True"
- "creation timestamp", "record creation", or "created on" → Add Attribute Id=100000000000000000000000000000029, Value="DateTime.UtcNow"
- "modification date", or "modification timestamp" → Add Attribute Id=100000000000000000000000000000029, Value="DateTime.UtcNow"

Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named 'Id', always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
- Whenever you generate PayanarssType metadata, always return the result as an array of PayanarssType objects, even if there is only one. Each array element must be a complete PayanarssType object.
- If a field has an attribute with ID 100000000000000000000000000000003, treat it as a lookup field. Its value points to another PayanarssType that defines the lookup source.
EOT;

  $data = [
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4096,
    'system' => $systemPrompt,
    'messages' => [
      ['role' => 'user', 'content' => $allRules]
    ],
    'temperature' => 0.0,
  ];

  echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $myCre,
    'Content-Type: application/json',
    'anthropic-version: 2023-06-01'
  ]);

  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  ini_set('max_execution_time', '120');
  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
    curl_close($ch);
    return null;
  }

  $responseData = json_decode($result, true);
  $content = $responseData['content'][0]['text'] ?? '';

  $clean = preg_replace('/```(?:json)?/', '', $content);
  $clean = preg_replace('/\/\/.*$/m', '', $clean);
  $clean = trim($clean);
  echo "<pre>$clean</pre>";

  $parsedJson = json_decode($clean, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg();
  }

  curl_close($ch);

  return $parsedJson;
}

function buildDomainModelFlat(string $domainPrompt, string $parentId, $allTypes = []): string
{
  global $myCre;

  $allTypesJson = json_encode($allTypes, JSON_PRETTY_PRINT);

  $systemPrompt = <<<EOT
You are an expert ERP system architect and database designer working with the PayanarssType metadata structure.

AVAILABLE PAYANARSS TYPES:
$allTypesJson

PARENT ID FOR THIS REQUEST: $parentId

YOUR TASK:
When given a business domain request (e.g., "Build Leave Management", "Create Employee Management"), you must:

1. Analyze the domain and identify ALL required entities (tables)
2. For each entity, define ALL necessary fields (columns)
3. For each field, specify ALL business rules and constraints
4. Define relationships between entities (lookup fields, foreign keys)
5. Return everything as a FLAT array of PayanarssType objects (no nesting, no Children arrays)

PAYANARSS TYPE STRUCTURE (FLAT):
{
    "Id": "<unique GUID>",
    "ParentId": "<parent GUID - NEVER null>",
    "Name": "<EntityName or FieldName>",
    "PayanarssTypeId": "<type GUID from available types>",
    "Attributes": [
        {
            "Id": "<AttributeTypeId from available types>",
            "Value": "<rule value>"
        }
    ],
    "Description": "<clear description>"
}

KNOWN ATTRIBUTE TYPE IDs (use these in Attributes array):
- "100000000000000000000000000000012" = Required/Mandatory (Value: "True"/"False")
- "100000000000000000000000000000017" = Maximum/DateTo (Value: number or date)
- "100000000000000000000000000000016" = Minimum/DateFrom (Value: number or date)
- "100000000000000000000000000000018" = GUID type (Value: "True")
- "100000000000000000000000000000006" = Text type (Value: max length)
- "100000000000000000000000000000007" = Number type (Value: "True")
- "100000000000000000000000000000009" = Boolean type (Value: "True")
- "100000000000000000000000000000008" = DateTime type (Value: "True")
- "100000000000000000000000000000013" = Unique constraint (Value: "True")
- "100000000000000000000000000000024" = Auto Generate/Auto-updated (Value: "True")
- "100000000000000000000000000000025" = Validate Before Create (Value: "True")
- "100000000000000000000000000000029" = Default Value (Value: the default value)
- "100000000000000000000000000000026" = GreaterThan (Value: number)
- "100000000000000000000000000000027" = LessThan (Value: number)
- "100000000000000000000000000000003" = Lookup/Foreign Key (Value: target entity GUID)

FLAT ARRAY OUTPUT EXAMPLE for "Leave Management":
[
    {
        "Id": "A1B2C3D4-E5F6-4789-0123-456789ABCDEF",
        "ParentId": "$parentId",
        "Name": "LeaveType",
        "PayanarssTypeId": "100000000000000000000000000000001",
        "Description": "Master table for leave types",
        "Attributes": []
    },
    {
        "Id": "B2C3D4E5-F6A7-4890-1234-56789ABCDEF0",
        "ParentId": "A1B2C3D4-E5F6-4789-0123-456789ABCDEF",
        "Name": "LeaveTypeId",
        "PayanarssTypeId": "100000000000000000000000000000018",
        "Description": "Primary key for leave type",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000018", "Value": "True"},
            {"Id": "100000000000000000000000000000013", "Value": "True"},
            {"Id": "100000000000000000000000000000024", "Value": "True"}
        ]
    },
    {
        "Id": "C3D4E5F6-A7B8-4901-2345-6789ABCDEF01",
        "ParentId": "A1B2C3D4-E5F6-4789-0123-456789ABCDEF",
        "Name": "LeaveTypeName",
        "PayanarssTypeId": "100000000000000000000000000000006",
        "Description": "Name of leave type",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000006", "Value": "100"},
            {"Id": "100000000000000000000000000000013", "Value": "True"}
        ]
    },
    {
        "Id": "D4E5F6A7-B8C9-4012-3456-789ABCDEF012",
        "ParentId": "A1B2C3D4-E5F6-4789-0123-456789ABCDEF",
        "Name": "MaxDaysPerYear",
        "PayanarssTypeId": "100000000000000000000000000000007",
        "Description": "Maximum days allowed per year",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000007", "Value": "True"},
            {"Id": "100000000000000000000000000000026", "Value": "0"}
        ]
    },
    {
        "Id": "E5F6A7B8-C9D0-4123-4567-89ABCDEF0123",
        "ParentId": "$parentId",
        "Name": "LeaveApplication",
        "PayanarssTypeId": "100000000000000000000000000000001",
        "Description": "Leave application requests",
        "Attributes": []
    },
    {
        "Id": "F6A7B8C9-D0E1-4234-5678-9ABCDEF01234",
        "ParentId": "E5F6A7B8-C9D0-4123-4567-89ABCDEF0123",
        "Name": "ApplicationId",
        "PayanarssTypeId": "100000000000000000000000000000018",
        "Description": "Primary key for leave application",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000018", "Value": "True"},
            {"Id": "100000000000000000000000000000013", "Value": "True"},
            {"Id": "100000000000000000000000000000024", "Value": "True"}
        ]
    },
    {
        "Id": "A7B8C9D0-E1F2-4345-6789-ABCDEF012345",
        "ParentId": "E5F6A7B8-C9D0-4123-4567-89ABCDEF0123",
        "Name": "LeaveTypeId",
        "PayanarssTypeId": "100000000000000000000000000000018",
        "Description": "Foreign key to LeaveType",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000003", "Value": "A1B2C3D4-E5F6-4789-0123-456789ABCDEF"}
        ]
    },
    {
        "Id": "B8C9D0E1-F2A3-4456-789A-BCDEF0123456",
        "ParentId": "E5F6A7B8-C9D0-4123-4567-89ABCDEF0123",
        "Name": "StartDate",
        "PayanarssTypeId": "100000000000000000000000000000008",
        "Description": "Leave start date",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000008", "Value": "True"}
        ]
    },
    {
        "Id": "C9D0E1F2-A3B4-4567-89AB-CDEF01234567",
        "ParentId": "E5F6A7B8-C9D0-4123-4567-89ABCDEF0123",
        "Name": "EndDate",
        "PayanarssTypeId": "100000000000000000000000000000008",
        "Description": "Leave end date",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000008", "Value": "True"}
        ]
    },
    {
        "Id": "D0E1F2A3-B4C5-4678-9ABC-DEF012345678",
        "ParentId": "$parentId",
        "Name": "LeaveBalance",
        "PayanarssTypeId": "100000000000000000000000000000001",
        "Description": "Track employee leave balances",
        "Attributes": []
    },
    {
        "Id": "E1F2A3B4-C5D6-4789-ABCD-EF0123456789",
        "ParentId": "D0E1F2A3-B4C5-4678-9ABC-DEF012345678",
        "Name": "BalanceId",
        "PayanarssTypeId": "100000000000000000000000000000018",
        "Description": "Primary key for leave balance",
        "Attributes": [
            {"Id": "100000000000000000000000000000012", "Value": "True"},
            {"Id": "100000000000000000000000000000018", "Value": "True"},
            {"Id": "100000000000000000000000000000013", "Value": "True"},
            {"Id": "100000000000000000000000000000024", "Value": "True"}
        ]
    }
]

CRITICAL PARENT-CHILD RULES:
1. ALL objects MUST have a ParentId - NEVER use null
2. Entity-level objects (tables) have ParentId = "$parentId" (the input ParentId provided)
3. Field-level objects (columns) have ParentId = their entity's Id (the GUID of the table they belong to)
4. Output must be a FLAT array - NO nested "Children" property
5. ALWAYS generate unique GUIDs for every Id field

STRUCTURE PATTERN (2-LEVEL HIERARCHY):
Level 1: Entities (ParentId = "$parentId")
Level 2: Fields (ParentId = their entity's Id)

Example Pattern:
- Object 1: Entity "LeaveType" with ParentId = "$parentId"
- Object 2: Field "LeaveTypeId" with ParentId = "LeaveType's Id"
- Object 3: Field "LeaveTypeName" with ParentId = "LeaveType's Id"
- Object 4: Field "MaxDaysPerYear" with ParentId = "LeaveType's Id"
- Object 5: Entity "LeaveApplication" with ParentId = "$parentId"
- Object 6: Field "ApplicationId" with ParentId = "LeaveApplication's Id"
- Object 7: Field "LeaveTypeId" with ParentId = "LeaveApplication's Id"
- Continue pattern...

MANDATORY RULES:
1. Return ONLY valid JSON - no markdown, no explanation, no code fences, no text outside the JSON array
2. Do NOT include any "Children" property in any object
3. The entire output must be a single JSON array starting with [ and ending with ]
4. ALWAYS include standard fields for every entity:
   - Id (GUID, Required, Unique, Auto-generated)
   - CreatedBy (GUID, Required, Lookup to User)
   - CreatedOn (DateTime, Required, Auto-generated, Default: DateTime.UtcNow)
   - ModifiedBy (GUID, Lookup to User)
   - ModifiedOn (DateTime, Auto-updated)
   - IsActive (Boolean, Required, Default: True)

DOMAIN-SPECIFIC CONSIDERATIONS:
For Leave Management, include entities like:
- LeaveType (master data)
- LeaveApplication (transaction)
- LeaveBalance (balance tracking)
- LeaveApproval (approval workflow)
- LeaveEntitlement (policy rules)
- LeavePolicy (configuration)

For other domains, think comprehensively about:
- Master data entities
- Transaction entities
- Lookup/reference entities
- Configuration entities
- Audit/tracking needs
- Business rules and validations

Remember: Every entity uses ParentId = "$parentId", and every field uses ParentId = its entity's Id.
EOT;

  $data = [
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 16000,
    'system' => $systemPrompt,
    'messages' => [
      ['role' => 'user', 'content' => $domainPrompt]
    ],
    'temperature' => 0.1,
  ];

  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $myCre,
    'Content-Type: application/json',
    'anthropic-version: 2023-06-01'
  ]);

  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  ini_set('max_execution_time', '180');

  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
    curl_close($ch);
    return '[]';
  }

  curl_close($ch);

  $decoded = json_decode($result, true);
  $content = $decoded['content'][0]['text'] ?? '[]';

  // Clean up any markdown code fences
  $clean = preg_replace('/```(?:json)?/', '', $content);
  $clean = trim($clean);

  return $clean;
}
