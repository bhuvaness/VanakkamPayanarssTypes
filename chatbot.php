<?php
session_start();

// Initialize chat history if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent Creator - Chatbot</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-blue-700 text-white px-6 py-4 shadow-md">
        <h1 class="text-xl font-semibold">🤖 AI Agent Creator - Chatbot</h1>
    </header>

    <!-- Main Container with Side Nav and Content -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Side Navigation -->
        <aside class="w-64 bg-white border-r border-gray-300 overflow-y-auto">
            <nav class="p-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Navigation</h2>
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700">
                            📁 Type Designer
                        </a>
                    </li>
                    <li>
                        <a href="chatbot.php" class="block px-3 py-2 rounded bg-blue-100 text-sm text-blue-700 font-medium">
                            🤖 AI Chatbot
                        </a>
                    </li>
                </ul>

                <hr class="my-4 border-gray-200">

                <h2 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h2>
                <ul class="space-y-2">
                    <li>
                        <button onclick="clearChat()" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700">
                            🗑️ Clear Chat
                        </button>
                    </li>
                </ul>

                <hr class="my-4 border-gray-200">

                <div class="text-xs text-gray-500 mt-4 p-3 bg-gray-50 rounded">
                    <p class="font-semibold mb-1">💡 Examples:</p>
                    <ul class="space-y-1">
                        <li>• Create Employee Agent</li>
                        <li>• Generate Customer Table</li>
                        <li>• Build Invoice System</li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col bg-gray-50">
            <!-- Chat Messages Area -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Welcome Message -->
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        AI
                    </div>
                    <div class="flex-1 bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                        <p class="text-gray-700">
                            👋 Hello! I'm your AI Agent Creator assistant. I can help you generate agents, tables, and systems using AI.
                        </p>
                        <p class="text-gray-700 mt-2">
                            You can type prompts like:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 mt-1 ml-2">
                            <li>I would like to create Employee Agent</li>
                            <li>Generate a Customer management system</li>
                            <li>Create an Invoice table with fields</li>
                        </ul>
                        <p class="text-gray-700 mt-2">
                            I'll use AI APIs (OpenAI, Copilot, or Gemini) to help generate the required files.
                        </p>
                    </div>
                </div>

                <?php
                // Display chat history
                if (!empty($_SESSION['chat_history'])) {
                    foreach ($_SESSION['chat_history'] as $chat) {
                        if ($chat['type'] === 'user') {
                            echo '<div class="flex items-start space-x-3 justify-end">';
                            echo '<div class="flex-1 bg-blue-600 text-white rounded-lg shadow-sm p-4 max-w-2xl ml-auto">';
                            echo '<p>' . htmlspecialchars($chat['message']) . '</p>';
                            echo '</div>';
                            echo '<div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white font-bold flex-shrink-0">';
                            echo 'U';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="flex items-start space-x-3">';
                            echo '<div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold flex-shrink-0">';
                            echo 'AI';
                            echo '</div>';
                            echo '<div class="flex-1 bg-white rounded-lg shadow-sm p-4 border border-gray-200 max-w-2xl">';
                            echo '<p class="text-gray-700">' . nl2br(htmlspecialchars($chat['message'])) . '</p>';
                            if (!empty($chat['api_used'])) {
                                echo '<p class="text-xs text-gray-500 mt-2">API Used: ' . htmlspecialchars($chat['api_used']) . '</p>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>

            <!-- Footer - Prompt Input Area -->
            <footer class="bg-white border-t border-gray-300 p-4 shadow-md">
                <form id="chatForm" class="flex items-center gap-3">
                    <div class="flex-1 flex items-center gap-2">
                        <input
                            type="text"
                            id="promptInput"
                            name="prompt"
                            placeholder="💬 Type your prompt here (e.g., 'I would like to create Employee Agent')..."
                            class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            autocomplete="off"
                            required>
                        
                        <select id="apiSelector" name="api" class="border border-gray-300 rounded-lg px-3 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="openai">OpenAI</option>
                            <option value="copilot">Copilot</option>
                            <option value="gemini">Gemini</option>
                        </select>
                    </div>
                    
                    <button
                        type="submit"
                        id="sendButton"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                        <span>Send</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            </footer>
        </main>
    </div>

    <script>
        const chatForm = document.getElementById('chatForm');
        const promptInput = document.getElementById('promptInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        const apiSelector = document.getElementById('apiSelector');

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const prompt = promptInput.value.trim();
            const selectedApi = apiSelector.value;
            
            if (!prompt) return;

            // Disable input while processing
            promptInput.disabled = true;
            sendButton.disabled = true;
            sendButton.innerHTML = '<span>Processing...</span>';

            // Add user message to chat
            addMessage(prompt, 'user');
            promptInput.value = '';

            try {
                // Send to backend
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        api: selectedApi
                    })
                });

                const data = await response.json();

                if (data.success) {
                    addMessage(data.message, 'bot', selectedApi);
                } else {
                    addMessage('Error: ' + (data.error || 'Failed to process your request'), 'bot', selectedApi);
                }
            } catch (error) {
                addMessage('Error: Failed to connect to the server. Please try again.', 'bot', selectedApi);
                console.error('Error:', error);
            } finally {
                // Re-enable input
                promptInput.disabled = false;
                sendButton.disabled = false;
                sendButton.innerHTML = '<span>Send</span><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>';
                promptInput.focus();
            }
        });

        function addMessage(text, type, apiUsed = '') {
            const messageDiv = document.createElement('div');
            
            if (type === 'user') {
                messageDiv.className = 'flex items-start space-x-3 justify-end';
                messageDiv.innerHTML = `
                    <div class="flex-1 bg-blue-600 text-white rounded-lg shadow-sm p-4 max-w-2xl ml-auto">
                        <p>${escapeHtml(text)}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        U
                    </div>
                `;
            } else {
                messageDiv.className = 'flex items-start space-x-3';
                messageDiv.innerHTML = `
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        AI
                    </div>
                    <div class="flex-1 bg-white rounded-lg shadow-sm p-4 border border-gray-200 max-w-2xl">
                        <p class="text-gray-700" style="white-space: pre-wrap;">${escapeHtml(text)}</p>
                        ${apiUsed ? `<p class="text-xs text-gray-500 mt-2">API Used: ${escapeHtml(apiUsed)}</p>` : ''}
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function clearChat() {
            if (confirm('Are you sure you want to clear the chat history?')) {
                fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'clear_chat'
                    })
                }).then(() => {
                    window.location.reload();
                });
            }
        }

        // Auto-scroll to bottom on page load
        window.addEventListener('load', () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    </script>
</body>

</html>
