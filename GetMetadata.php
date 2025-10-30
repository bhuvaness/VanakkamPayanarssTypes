<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '\PayanarssTypeModel.php';  // ✅ Only include what’s needed

$filePath = __DIR__ . "\PayanarssTypes.json";

if (!file_exists($filePath)) {
    error_log("❌ Type file missing: $filePath");
    return;
}

try {
    // Accept either ?agent= or ?agentId=
    if (isset($_GET['agentId'])) {
        $agentId = $_GET['agentId'];
    } else {
        $agentId   =  null;
    }

    if (empty($agentId)) {
        echo json_encode(["error" => "Agent ID is required"]);
        exit;
    }

    $app = new PayanarssApplication();
    $app->load_all_types();

    $childrenType = $app->getChildren($agentId);
    $bobj = new PayanarssTypeBusinessLogics();
    $arrayTypes = $bobj->convertToArray($childrenType);

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
