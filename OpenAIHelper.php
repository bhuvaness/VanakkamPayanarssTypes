<?php


function callOpenAI(string $prompt, $types = []): string
{
    global $myCre;

    $allTypesJson = json_encode($types, JSON_PRETTY_PRINT);

    $systemPrompt = <<<EOT
You are a PayanarssType metadata generator.

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
SYS;

Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named ‘Id’, always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
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
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $myCre,
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    ini_set('max_execution_time', '120');
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);
    $decoded = json_decode($result, true);
    return $decoded['choices'][0]['message']['content'] ?? '{}';
}

function callOpenAIV1(PayanarssType $payanarssType, $types = [])
{
    global $myCre;

    $allRules = "";
    $parentId = $payanarssType->Id;
    $allTypesJson = json_encode($types, JSON_PRETTY_PRINT);

    //echo $allTypesJson;

    foreach ($payanarssType->Children as $child) {
        $allRules .= "Parent Id: " . $child->Id . ", Rules: " . "Child Description : " . $child->Description . "\n\n";
    }

    //echo $allRules;

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
{
  "role": "system",
  "content": "You are a metadata interpreter for a dynamic ERP system. The following field definitions use attribute IDs that define rules and relationships."
},
{
  "role": "user",
  "content": "Field: GenderId
Description: Required; Lookup; Must reference Gender master.
Attributes:
  - Id: "100000000000000000000000000000003" (Lookup Source)
    Value: "689b2769ac2f3" (Refers to Gender master table)
  - Id: "100000000000000000000000000000012" (Required)
    Value: "True"
Task: Explain how this field should behave in the data entry UI."
}

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
- “must be > X” → Add Attribute Id=100000000000000000000000000000026, Value=X
- “cannot be future” or “must not be a future date” → Add Attribute Id=100000000000000000000000000000008, Value="True"
- “age” or “birth date” → Add Attribute Id=100000000000000000000000000000028, Value="True"
- "creation timestamp", "record creation", or "created on" → Add Attribute Id=100000000000000000000000000000029, Value="DateTime.UtcNow"
- "modification date", or "modification timestamp" → Add Attribute Id=100000000000000000000000000000029, Value="DateTime.UtcNow"
Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named ‘Id’, always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
- Whenever you generate PayanarssType metadata, always return the result as an array of PayanarssType objects, even if there is only one. Each array element must be a complete PayanarssType object.
- If a field has an attribute with ID 100000000000000000000000000000003, treat it as a lookup field. Its value points to another PayanarssType that defines the lookup source. Refer example 3.
EOT;

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $allRules]
        ],
        'temperature' => 0.0,
    ];

    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $myCre,
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    ini_set('max_execution_time', '120');
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    //echo $result;

    $responseData = json_decode($result, true);
    $content = $responseData['choices'][0]['message']['content'] ?? '';

    $clean = preg_replace('/```(?:json)?/', '', $content);
    $clean = preg_replace('/\/\/.*$/m', '', $clean);
    $clean = trim($clean);
    echo "<pre>$clean</pre>";

    $parsedJson = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg();
    } /*else {
        echo "<pre>" . json_encode($parsedJson, JSON_PRETTY_PRINT) . "</pre>";
    }*/
    
    curl_close($ch);

    return $parsedJson;
}
