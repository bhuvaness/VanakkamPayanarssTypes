document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("messageInput").addEventListener("keypress", (e) => {
  if (e.key === "Enter") sendMessage();
});

async function sendMessage() {
  const input = document.getElementById("messageInput");
  const text = input.value.trim();
  if (!text) return;

  appendMessage(text, "user");
  input.value = "";

  // Call backend API
  const response = await fetch("send_message.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ message: text })
  });

  const result = await response.json();
  appendMessage(result.reply, "bot");
}

function appendMessage(text, type) {
  const chatBox = document.getElementById("chatBox");
  const msgDiv = document.createElement("div");

  if (type === "user") {
    msgDiv.className = "bg-blue-600 text-white self-end p-3 rounded-lg max-w-[80%] ml-auto";
  } else {
    msgDiv.className = "bg-gray-200 text-gray-800 p-3 rounded-lg max-w-[80%]";
  }

  msgDiv.textContent = text;
  chatBox.appendChild(msgDiv);
  chatBox.scrollTop = chatBox.scrollHeight;
}
