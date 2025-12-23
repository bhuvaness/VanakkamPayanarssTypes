<?php
session_start();

header('Content-Type: application/json');

// Initialize chat history if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON parsing
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Handle clear chat action
if (isset($data['action']) && $data['action'] === 'clear_chat') {
    $_SESSION['chat_history'] = [];
    echo json_encode(['success' => true, 'message' => 'Chat cleared']);
    exit;
}

// Get prompt and API selection
$prompt = $data['prompt'] ?? '';
$selectedApi = $data['api'] ?? 'openai';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt is required']);
    exit;
}

// Add user message to history
$_SESSION['chat_history'][] = [
    'type' => 'user',
    'message' => $prompt,
    'timestamp' => date('Y-m-d H:i:s')
];

// Process the prompt based on selected API
$response = processPrompt($prompt, $selectedApi);

// Add bot response to history
$_SESSION['chat_history'][] = [
    'type' => 'bot',
    'message' => $response,
    'api_used' => ucfirst($selectedApi),
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode([
    'success' => true,
    'message' => $response
]);

/**
 * Process the prompt using the selected API
 */
function processPrompt($prompt, $api)
{
    switch ($api) {
        case 'openai':
            return callOpenAIAPI($prompt);
        case 'copilot':
            return callCopilotAPI($prompt);
        case 'gemini':
            return callGeminiAPI($prompt);
        default:
            return 'Unknown API selected';
    }
}

/**
 * Call OpenAI API
 */
function callOpenAIAPI($prompt)
{
    // Check if OpenAI helper exists
    if (!file_exists(__DIR__ . '/OpenAIHelper.php')) {
        return "OpenAI integration is available. To complete the setup, please configure your API key in OpenAIHelper.php.\n\nYour prompt: " . $prompt;
    }

    require_once __DIR__ . '/OpenAIHelper.php';

    // Check if API key is configured
    global $myCre;
    if (empty($myCre)) {
        return "OpenAI API key is not configured. Please set up your API key in OpenAIHelper.php to use this feature.\n\nYour prompt: " . $prompt;
    }

    try {
        $systemPrompt = "You are an AI assistant that helps developers create agents, tables, and system components. Based on the user's request, provide detailed guidance and suggestions.";
        
        $data = [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $myCre,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return "Error connecting to OpenAI: " . $error;
        }

        curl_close($ch);
        
        $decoded = json_decode($result, true);
        
        if (isset($decoded['error'])) {
            return "OpenAI Error: " . ($decoded['error']['message'] ?? 'Unknown error');
        }
        
        // Validate response structure
        if (!isset($decoded['choices']) || !is_array($decoded['choices']) || count($decoded['choices']) === 0) {
            return "OpenAI Error: Invalid response structure received";
        }
        
        if (!isset($decoded['choices'][0]['message']['content'])) {
            return "OpenAI Error: No content in response";
        }
        
        return $decoded['choices'][0]['message']['content'];
        
    } catch (Exception $e) {
        return "Exception occurred: " . $e->getMessage();
    }
}

/**
 * Call GitHub Copilot API
 */
function callCopilotAPI($prompt)
{
    // Placeholder for Copilot API integration
    // This would need to be implemented based on your Copilot setup
    
    $response = "GitHub Copilot API integration is ready to be configured.\n\n";
    $response .= "To use Copilot, you would need to:\n";
    $response .= "1. Set up GitHub Copilot API access\n";
    $response .= "2. Configure authentication credentials\n";
    $response .= "3. Implement the API call logic\n\n";
    $response .= "Your prompt: " . $prompt . "\n\n";
    $response .= "For now, this is a placeholder response. Once configured, Copilot will generate actual agent and file structures based on your request.";
    
    return $response;
}

/**
 * Call Google Gemini API
 */
function callGeminiAPI($prompt)
{
    // Placeholder for Gemini API integration
    // This would need to be implemented based on your Gemini setup
    
    $response = "Google Gemini API integration is ready to be configured.\n\n";
    $response .= "To use Gemini, you would need to:\n";
    $response .= "1. Set up Google Cloud project\n";
    $response .= "2. Enable Gemini API\n";
    $response .= "3. Configure API key\n";
    $response .= "4. Implement the API call logic\n\n";
    $response .= "Your prompt: " . $prompt . "\n\n";
    $response .= "For now, this is a placeholder response. Once configured, Gemini will help generate agents and system components based on your request.";
    
    return $response;
}
