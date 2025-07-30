<?php

function callOpenAI(string $prompt, $types = []): string
{
    $apiKey = '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';

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
        'Authorization: Bearer ' . $apiKey,
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
