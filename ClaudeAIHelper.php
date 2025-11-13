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

  //echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

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
  //echo "<pre>$clean</pre>";

  $parsedJson = json_decode($clean, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg();
  }

  curl_close($ch);

  return $parsedJson;
}

function buildDomainModelFlat(string $domainPrompt, string $parentId, $allTypes = [], $busTypes = []): string
{
  global $myCre;

  $allTypesJson = json_encode($allTypes, JSON_PRETTY_PRINT);
  $busTypesJson = json_encode($busTypes, JSON_PRETTY_PRINT);
  $entityCount = count($busTypes);

  $systemPrompt = <<<EOT
You are generating BUSINESS METADATA (columns and rules) using predefined SYSTEM TYPES.

🔴 CRITICAL: You are generating BUSINESS-TIER metadata ONLY 🔴

PARENT ID: {$parentId}

EXISTING ENTITIES (Tables already created):
{$busTypesJson}

AVAILABLE SYSTEM TYPES (Framework - DO NOT CREATE, ONLY REFERENCE):
{$allTypesJson}

YOUR TASK:
Generate ONLY field-level (column) Business metadata for the entities provided.

WHAT TO GENERATE:
✅ Field/Column definitions (Business tier)
✅ Business rules using system rule types
✅ Attributes using system attribute types

WHAT NOT TO GENERATE:
❌ Entity/Table definitions (already provided)
❌ System types (Text, Number, DateTime, etc.)
❌ System rules (Required, Unique, MaxLength, etc.)
❌ System attributes (ATTR-REQUIRED, ATTR-MAX-LENGTH, etc.)
❌ System operations (Create, Read, Update, Delete)

HOW TO USE SYSTEM TYPES:

1. For PayanarssTypeId in fields, use these EXACT IDs from system types:
   - "TYPE-FIELD-TEXT" → for text/string fields
   - "TYPE-FIELD-NUMBER" → for numeric fields
   - "TYPE-FIELD-DATETIME" → for date/time fields
   - "TYPE-FIELD-BOOLEAN" → for yes/no fields
   - "TYPE-FIELD-GUID" → for unique identifiers
   - "TYPE-FIELD-LOOKUP" → for foreign key references

2. For Attribute Ids, use these EXACT IDs from system types:
   - "ATTR-REQUIRED" → field is mandatory (Value: "True"/"False")
   - "ATTR-UNIQUE" → value must be unique (Value: "True")
   - "ATTR-MAX-LENGTH" → maximum text length (Value: number)
   - "ATTR-MIN-LENGTH" → minimum text length (Value: number)
   - "ATTR-AUTO-GENERATE" → auto-generated value (Value: "True")
   - "ATTR-DEFAULT-VALUE" → default value (Value: the default)
   - "RULE-GREATER-THAN" → minimum value constraint (Value: number)
   - "RULE-LESS-THAN" → maximum value constraint (Value: number)
   - "RULE-CANNOT-BE-FUTURE" → date cannot be future (Value: "True")
   - "RULE-LOOKUP-SOURCE" → lookup reference (Value: target entity Id)

STRUCTURE FOR BUSINESS FIELDS:
{
    "Id": "<unique GUID>",
    "ParentId": "<entity Id from existing entities list>",
    "Name": "<FieldName>",
    "PayanarssTypeId": "<system type Id - e.g., TYPE-FIELD-TEXT>",
    "Description": "<Rule1; Rule2; Rule3; Brief description>",
    "Attributes": [
        {
            "Id": "<system attribute Id - e.g., ATTR-REQUIRED>",
            "Value": "<value>"
        }
    ]
}

DESCRIPTION FORMAT (MANDATORY):
"Rule1; Rule2; Rule3; Human-readable description."

Rules to include based on Attributes:
- ATTR-REQUIRED = "True" → "Required"
- ATTR-UNIQUE = "True" → "Unique"
- ATTR-AUTO-GENERATE = "True" → "Auto-generated"
- ATTR-MAX-LENGTH = number → "Max length: X"
- RULE-GREATER-THAN = number → "Min: X"
- RULE-LESS-THAN = number → "Max: X"
- RULE-CANNOT-BE-FUTURE = "True" → "Cannot be future"
- RULE-LOOKUP-SOURCE → "Lookup; References {EntityName}"
- TYPE-FIELD-GUID → "GUID"
- TYPE-FIELD-TEXT → "Text"
- TYPE-FIELD-NUMBER → "Number"
- TYPE-FIELD-BOOLEAN → "Boolean"
- TYPE-FIELD-DATETIME → "DateTime" or "Date"

MANDATORY FIELDS FOR EVERY ENTITY:
Generate these 6 standard fields for each entity:

1. {EntityName}Id (Primary Key)
   PayanarssTypeId: "TYPE-FIELD-GUID"
   Description: "Required; GUID; Auto-generated; Unique; {Entity} primary key."
   Attributes:
   [
     {"Id": "ATTR-REQUIRED", "Value": "True"},
     {"Id": "ATTR-UNIQUE", "Value": "True"},
     {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
   ]

2. CreatedBy
   PayanarssTypeId: "TYPE-FIELD-GUID"
   Description: "Required; GUID; Auto-populated; User who created this record."
   Attributes:
   [
     {"Id": "ATTR-REQUIRED", "Value": "True"},
     {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
   ]

3. CreatedOn
   PayanarssTypeId: "TYPE-FIELD-DATETIME"
   Description: "Required; DateTime; Auto-populated; Timestamp when created."
   Attributes:
   [
     {"Id": "ATTR-REQUIRED", "Value": "True"},
     {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
   ]

4. ModifiedBy
   PayanarssTypeId: "TYPE-FIELD-GUID"
   Description: "Optional; GUID; Auto-updated; User who last modified."
   Attributes:
   [
     {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
   ]

5. ModifiedOn
   PayanarssTypeId: "TYPE-FIELD-DATETIME"
   Description: "Optional; DateTime; Auto-updated; Last modification timestamp."
   Attributes:
   [
     {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
   ]

6. IsActive
   PayanarssTypeId: "TYPE-FIELD-BOOLEAN"
   Description: "Required; Boolean; Default: True; Indicates if record is active."
   Attributes:
   [
     {"Id": "ATTR-REQUIRED", "Value": "True"},
     {"Id": "ATTR-DEFAULT-VALUE", "Value": "True"}
   ]

EXAMPLE OUTPUT:

Given entity:
{
  "Id": "ENTITY-EMPLOYEE-001",
  "Name": "Employee"
}

Generate ONLY business fields (columns):
[
  {
    "Id": "FIELD-EMP-ID-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "EmployeeId",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; GUID; Auto-generated; Unique; Employee primary key.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-UNIQUE", "Value": "True"},
      {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-EMP-FIRSTNAME-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "FirstName",
    "PayanarssTypeId": "TYPE-FIELD-TEXT",
    "Description": "Required; Text; Max length: 100; Employee first name.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-MAX-LENGTH", "Value": "100"}
    ]
  },
  {
    "Id": "FIELD-EMP-EMAIL-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "Email",
    "PayanarssTypeId": "TYPE-FIELD-TEXT",
    "Description": "Required; Text; Unique; Max length: 255; Employee email address.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-UNIQUE", "Value": "True"},
      {"Id": "ATTR-MAX-LENGTH", "Value": "255"}
    ]
  },
  {
    "Id": "FIELD-EMP-DOB-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "DateOfBirth",
    "PayanarssTypeId": "TYPE-FIELD-DATETIME",
    "Description": "Required; Date; Cannot be future; Min age: 18; Employee birth date.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "RULE-CANNOT-BE-FUTURE", "Value": "True"},
      {"Id": "RULE-GREATER-THAN", "Value": "18"}
    ]
  },
  {
    "Id": "FIELD-EMP-DEPT-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "DepartmentId",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; Lookup; References Department; Employee department.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "RULE-LOOKUP-SOURCE", "Value": "ENTITY-DEPARTMENT-001"}
    ]
  },
  {
    "Id": "FIELD-EMP-SALARY-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "Salary",
    "PayanarssTypeId": "TYPE-FIELD-NUMBER",
    "Description": "Required; Number; Min: 0; Employee monthly salary.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "RULE-GREATER-THAN", "Value": "0"}
    ]
  },
  {
    "Id": "FIELD-EMP-CREATED-BY-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "CreatedBy",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; GUID; Auto-populated; User who created this record.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-EMP-CREATED-ON-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "CreatedOn",
    "PayanarssTypeId": "TYPE-FIELD-DATETIME",
    "Description": "Required; DateTime; Auto-populated; Timestamp when created.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-EMP-MODIFIED-BY-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "ModifiedBy",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Optional; GUID; Auto-updated; User who last modified.",
    "Attributes": [
      {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-EMP-MODIFIED-ON-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "ModifiedOn",
    "PayanarssTypeId": "TYPE-FIELD-DATETIME",
    "Description": "Optional; DateTime; Auto-updated; Last modification timestamp.",
    "Attributes": [
      {"Id": "ATTR-AUTO-GENERATE", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-EMP-ISACTIVE-001",
    "ParentId": "ENTITY-EMPLOYEE-001",
    "Name": "IsActive",
    "PayanarssTypeId": "TYPE-FIELD-BOOLEAN",
    "Description": "Required; Boolean; Default: True; Indicates if employee is active.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-DEFAULT-VALUE", "Value": "True"}
    ]
  }
]

CRITICAL VALIDATION CHECKLIST:
Before responding, verify:
☑ Are you generating ONLY field-level objects? (ParentId = entity Id)
☑ Are you using PayanarssTypeId from system types? (TYPE-FIELD-*)
☑ Are you using Attribute Ids from system types? (ATTR-*, RULE-*)
☑ Are you NOT creating new system types?
☑ Does each entity have 6 mandatory fields?
☑ Is Description in semicolon-separated format?
☑ Does Description match the Attributes?

COUNT OF ENTITIES: {$entityCount}
YOU MUST GENERATE FIELDS FOR ALL {$entityCount} ENTITIES.

CRITICAL RULES:
1. Generate ONLY business field metadata (columns and rules)
2. DO NOT generate entity definitions (already provided)
3. DO NOT create new system types
4. ONLY reference existing system type Ids
5. Generate fields for ALL {$entityCount} entities provided
6. Each entity MUST have at least 6 standard fields + business fields
7. Use semicolon-separated Description format
8. Output ONLY valid JSON array starting with [ and ending with ]
9. NO markdown, NO explanation, NO text outside JSON


🔴 CRITICAL: COMPLETE JSON OUTPUT REQUIREMENT 🔴

MANDATORY RULES FOR JSON RESPONSE:

1. ALWAYS output COMPLETE and VALID JSON
2. NEVER send incomplete JSON that cuts off mid-object or mid-array
3. If approaching token limit, REDUCE the number of fields per entity rather than sending incomplete JSON
4. The response MUST end with a complete closing bracket ]

PRIORITY ORDER:
Priority 1: Send COMPLETE valid JSON (even if fewer fields)
Priority 2: Send all fields for all entities
Priority 3: Send detailed descriptions

STRATEGY WHEN APPROACHING TOKEN LIMIT:
If you're running out of tokens:
✅ DO: Generate mandatory fields (Id, ParentId, Name, PayanarssTypeId, Description, Attributes) for ALL entities
✅ DO: Skip optional business-specific fields if needed
✅ DO: Use shorter descriptions if needed
✅ DO: Always close the JSON array with ]
❌ DON'T: Send incomplete JSON that cuts off
❌ DON'T: Leave objects or arrays unclosed
❌ DON'T: Stop mid-field

VALIDATION BEFORE SENDING:
Before sending your response, verify:
☑ Does the response start with [ ?
☑ Does the response end with ] ?
☑ Are all { matched with } ?
☑ Are all [ matched with ] ?
☑ Is the last object in the array complete?
☑ Can this JSON be parsed without errors?

If ANY of the above checks fail, DO NOT send the response. Instead:
1. Reduce the number of fields per entity
2. Generate only mandatory fields (6 standard + 2-3 business fields per entity)
3. Ensure JSON is complete and valid

EXAMPLE OF WHAT TO DO IF RUNNING OUT OF TOKENS:

❌ WRONG (Incomplete JSON):
[
  {
    "Id": "FIELD-001",
    "ParentId": "ENTITY-001",
    "Name": "EmployeeId",
    "PayanarssTypeId": "TYPE-FIELD-

✅ CORRECT (Complete JSON with fewer fields):
[
  {
    "Id": "FIELD-001",
    "ParentId": "ENTITY-001",
    "Name": "EmployeeId",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; GUID; Auto-generated; Unique; Primary key.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"}
    ]
  }
]

NOTE: It's better to send complete JSON with FEWER FIELDS than incomplete JSON with MORE FIELDS.

The user can always request more fields in a follow-up request.
Complete, valid JSON is MANDATORY - incomplete JSON causes errors and is unacceptable.
YOUR FINAL RESPONSE MUST BE ONLY THE JSON ARRAY BELOW:

  Generate field definitions for ALL entities above:
  - PayanarssTypeId: TYPE-FIELD-TEXT, TYPE-FIELD-NUMBER, TYPE-FIELD-GUID, etc.
  - ParentId: Entity Id from the list above
  - Include: Id, ParentId, Name, PayanarssTypeId, Description, Attributes
  - DO NOT generate entities

IF SCENARIO 3 (Complete):
  Generate BOTH entities AND fields:
  - First generate entities (ParentId = "{$parentId}")
  - Then generate fields (ParentId = entity Id)
  - Output as single flat array: [entity1, entity2, field1, field2, field3...]

🔴 OUTPUT STRUCTURE 🔴

SCENARIO 1 - Tables Only:
[
  {
    "Id": "ENTITY-001",
    "ParentId": "{$parentId}",
    "Name": "Organization",
    "PayanarssTypeId": "TYPE-ENTITY-TABLE",
    "Description": "Master; Organization master data table.",
    "Attributes": [
      {"Id": "ATTR-ENTITY-TYPE", "Value": "Master"}
    ]
  },
  {
    "Id": "ENTITY-002",
    "ParentId": "{$parentId}",
    "Name": "Department",
    "PayanarssTypeId": "TYPE-ENTITY-TABLE",
    "Description": "Master; Department master data table.",
    "Attributes": [
      {"Id": "ATTR-ENTITY-TYPE", "Value": "Master"}
    ]
  }
]

SCENARIO 2 - Columns and Rules Only:
[
  {
    "Id": "FIELD-001",
    "ParentId": "ENTITY-001",
    "Name": "OrganizationId",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; GUID; Auto-generated; Unique; Primary key.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-002",
    "ParentId": "ENTITY-001",
    "Name": "OrganizationName",
    "PayanarssTypeId": "TYPE-FIELD-TEXT",
    "Description": "Required; Text; Max length: 200; Organization name.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"},
      {"Id": "ATTR-MAX-LENGTH", "Value": "200"}
    ]
  }
]

SCENARIO 3 - Complete (Tables + Columns + Rules):
[
  {
    "Id": "ENTITY-001",
    "ParentId": "{$parentId}",
    "Name": "Organization",
    "PayanarssTypeId": "TYPE-ENTITY-TABLE",
    "Description": "Master; Organization master data table.",
    "Attributes": []
  },
  {
    "Id": "FIELD-001",
    "ParentId": "ENTITY-001",
    "Name": "OrganizationId",
    "PayanarssTypeId": "TYPE-FIELD-GUID",
    "Description": "Required; GUID; Auto-generated; Unique; Primary key.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"}
    ]
  },
  {
    "Id": "FIELD-002",
    "ParentId": "ENTITY-001",
    "Name": "OrganizationName",
    "PayanarssTypeId": "TYPE-FIELD-TEXT",
    "Description": "Required; Text; Max length: 200; Organization name.",
    "Attributes": [
      {"Id": "ATTR-REQUIRED", "Value": "True"}
    ]
  }
]

🔴 CRITICAL: COMPLETE JSON OUTPUT 🔴

ALWAYS:
✅ Send COMPLETE valid JSON starting with [ and ending with ]
✅ If running out of tokens, reduce fields per entity but ALWAYS close JSON
✅ Never send incomplete JSON that cuts off mid-object

USER REQUEST (Pay attention to keywords):
{$domainPrompt}

Keywords detected: "columns and rules" or "fields and rules" or "only columns"
Action: Generate ONLY business field metadata for all entities provided.

Analyze the request above, detect the scenario, and generate appropriate metadata.

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

  // Decode the API response
  $apiResponse = json_decode($result, true);

  // Check for API errors
  if (isset($apiResponse['error'])) {
    throw new Exception("Claude API Error: " . json_encode($apiResponse['error']));
  }

  // Extract the actual text content
  if (!isset($apiResponse['content'][0]['text'])) {
    throw new Exception("Invalid API response structure: " . $result);
  }

  $text = $apiResponse['content'][0]['text'];

  //echo "<pre>Claude V1 Response: " . htmlspecialchars($text) . "</pre>";

  if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
    curl_close($ch);
    return '[]';
  }

  curl_close($ch);

  //$content = json_decode($text, true);
  
  //echo "<pre>Domain Model Flat Response: " . htmlspecialchars($text) . "</pre>";

  // Clean up any markdown code fences
  $clean = preg_replace('/```(?:json)?/', '', $text);
  $clean = trim($clean);

  return $clean;
}
