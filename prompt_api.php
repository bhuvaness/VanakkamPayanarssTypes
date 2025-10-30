<?php
require_once 'PayanarssTypeModel.php';  // include your class files

header('Content-Type: application/json');

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

$parentId = $data['parent_id'] ?? null;

$app = new PayanarssApplication();
$app->load_all_types();

$payanarssType = $app->get_type($parentId);

if (isset($parentId)) {
    $payanarssType = $app->get_type($parentId);
    if (isset($payanarssType)) {
        $payanarssTypes = $payanarssType->Children;
    }
} else {
    error_log("\n❌ ParentId is invalid or not loaded properly.", 3, __DIR__ . "/payanarss_debug.log");
}

if ($payanarssType !== null) {
    $payanarssType->Attributes = []; // safe to assign
    //error_log("\n" . json_encode($payanarssType, JSON_PRETTY_PRINT), 3, __DIR__ . "/payanarss_debug.log");
} else {
    error_log("\n❌ PayanarssType is NULL. ParentId may be invalid or not loaded properly.", 3, __DIR__ . "/payanarss_debug.log");
}

$payanarssType->Attributes[] = ["Id" => "100000000000000000000000000000023", "Value" => "True"];

$application = new PayanarssApplication();
$application->addType($payanarssType);

$request = new PromptRequestMessage("system", $application);

$jsonBody = json_encode($request, JSON_PRETTY_PRINT);
error_log("\n" . $jsonBody, 3, __DIR__ . "/payanarss_debug.log");

$apiUrl = "https://localhost:7000/api/v1/EmployeeDataAgent/Prompt";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
} else {
    echo $response;
}
curl_close($ch);
