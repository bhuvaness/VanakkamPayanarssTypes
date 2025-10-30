<?php


function callOpenAI(string $prompt, $types = []): string
{
    global $myCre;

    $allTypesJson = json_encode($types, JSON_PRETTY_PRINT);

    $systemPrompt = <<<EOT
You are a metadata assistant working with the PayanarssType structure:

$allTypesJson

Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named ‘Id’, always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
- Whenever you generate PayanarssType metadata, always return the result as an array of PayanarssType objects, even if there is only one. Each array element must be a complete PayanarssType object.
EOT;

    $data = [
        'model' => 'gpt-4',
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
- "Auto Generate" → "100000000000000000000000000000024"
- "Validate Before Create" → "100000000000000000000000000000025"

Output format:
[
  {
    "Parent Id": "<$parentId from input>",
    "attributes": [
      { "<RuleTypeId>": "<Value>" }
    ]
  }
]

Ex: 1
Rules: "Required; GUID; Auto-generated unique Employee identifier."
[
  {
    "Parent Id": "$parentId",
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
    "Parent Id": "$parentId",
    "attributes": [
      {"100000000000000000000000000000016": "True"},     // IsRequired
      {"100000000000000000000000000000018": "100"}       // Maximum
    ]
  }
]

Guidelines:
- Whenever you create or modify definitions, always use this structure.
- Whenever you generate a value for any field named ‘Id’, always use a valid GUID (example: 5F2A48CB-F50E-4CDE-8A3E-B6F9D6761B2E). Ensure all generated GUIDs are unique.
- Whenever you generate PayanarssType metadata, always return the result as an array of PayanarssType objects, even if there is only one. Each array element must be a complete PayanarssType object.
EOT;

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $allRules]
        ],
        'temperature' => 0.0,
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

    $responseData = json_decode($result, true);
    $content = $responseData['choices'][0]['message']['content'] ?? '';

    $clean = preg_replace('/```(?:json)?/', '', $content);
    $clean = preg_replace('/\/\/.*$/m', '', $clean);
    $clean = trim($clean);

    $parsedJson = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg();
    } /*else {
        echo "<pre>" . json_encode($parsedJson, JSON_PRETTY_PRINT) . "</pre>";
    }*/
    
    curl_close($ch);

    return $parsedJson;
}
