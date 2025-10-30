<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$message = $data["message"] ?? "";

// Simple logic (replace with Agent call later)
if (stripos($message, "hello") !== false) {
    $reply = "👋 Hello! How can I assist you with MAA ERP today?";
} elseif (stripos($message, "employee") !== false) {
    $reply = "Would you like to create a new employee record or view existing ones?";
} elseif (stripos($message, "attendance") !== false) {
    $reply = "Attendance Agent ready. Shall I mark attendance for an employee?";
} else {
    $reply = "Received: “$message”. I’ll process that soon.";
}

echo json_encode(["reply" => $reply]);
?>
