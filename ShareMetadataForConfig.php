<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '\ClaudeAIHelper.php';  // ✅ Only include what’s needed

try {
    // Accept either ?agent= or ?agentId=
    if (isset($_GET['prompt'])) {
        $prompt = $_GET['prompt'];
    } else {
        $prompt   =  null;
    }

    if (empty($prompt)) {
        echo json_encode(["error" => "Prompt is required"]);
        exit;
    }

    $arrayTypes = extractAgentsFromPrompt($prompt);

    //error_log("Decoded types count: " . count($childrenType ?? []), 3, __DIR__ . "/payanarss_debug.log");
    //error_log("\n✅ Fetched agentId: $agentId", 3, __DIR__ . "/payanarss_debug.log");
    //error_log("\nChildren: " . json_encode($arrayTypes, JSON_PRETTY_PRINT), 3, __DIR__ . "/payanarss_debug.log");

    if (!empty($arrayTypes)) {
        echo json_encode($arrayTypes, JSON_PRETTY_PRINT);
    } else {
        echo json_encode(["error" => "Type not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
