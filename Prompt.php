<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MAA ERP Chat Assistant</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-lg flex flex-col h-[600px]">
    <!-- Header -->
    <div class="bg-blue-600 text-white p-4 rounded-t-2xl flex justify-between items-center">
      <h2 class="text-lg font-semibold">🧠 MAA ERP Chat Assistant</h2>
      <span class="text-sm opacity-80">Online</span>
    </div>

    <!-- Chat box -->
    <div id="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
      <!-- Messages appear here -->
    </div>

    <!-- Input -->
    <div class="p-3 border-t border-gray-200 flex gap-2 bg-white">
      <input 
        id="messageInput" 
        type="text" 
        placeholder="Type your message..." 
        class="flex-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
      >
      <button 
        id="sendBtn"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        Send
      </button>
    </div>
  </div>

  <script src="Prompt.js"></script>
</body>
</html>
