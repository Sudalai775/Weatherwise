<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>WeatherWise - Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <div class="logo">WeatherWise</div>
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="weather.php">Weather</a></li>
            </ul>
            <button id="theme-toggle" aria-label="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
        </nav>
    </header>

    <main>
        <section class="hero fade-in">
            <h1>Welcome to WeatherWise</h1>
            <p>Get accurate weather forecasts and chat with our intelligent AI assistant.</p>
            <a href="weather.php" class="cta-button">Check Weather Now →</a>
        </section>

        <!-- RESPONSIVE YOUTUBE VIDEO - WORKS ON ALL DEVICES -->
        <div class="card">
            <h2 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                <i class="fab fa-youtube"></i> Weather Live News
            </h2>
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px;">
                <iframe 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                    src="https://www.youtube-nocookie.com/embed/wt6SIE7BXS8?si=XFjDlmXsI7OcLrB4" 
                    title="YouTube video player" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    referrerpolicy="strict-origin-when-cross-origin" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>

        
    </main>

    <footer>
        <p>&copy; 2026 WeatherWise | Powered by OpenWeatherMap</p>
    </footer>

    <script>
    (function() {
        const chatInput = document.getElementById('chat-input');
        const sendButton = document.getElementById('send-message');
        const chatMessages = document.getElementById('chat-messages');

        function addMessage(content, isUser, isLoading = false) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('chat-message', isUser ? 'user' : 'bot');
            if (isLoading) {
                messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking...';
            } else {
                messageDiv.innerHTML = content;
            }
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return messageDiv;
        }

        function generateSmartResponse(msg) {
            const lowerMsg = msg.toLowerCase();
            if (/(hi|hello|hey)/i.test(lowerMsg)) {
                return "Hello! 👋 Go to the Weather page and search for a city to get weather information!";
            }
            if (/(weather|temperature|rain|forecast)/i.test(lowerMsg)) {
                return "Please visit the Weather page to check current weather and forecasts! 🌤️";
            }
            return "I'm your weather assistant! Go to the Weather page to search for any city and get real-time weather data! 🚀";
        }

        function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;
            addMessage(`<i class="fas fa-user"></i> ${escapeHtml(message)}`, true);
            chatInput.value = '';
            const typingMsg = addMessage('', false, true);
            setTimeout(() => {
                typingMsg.remove();
                const response = generateSmartResponse(message);
                addMessage(`<i class="fas fa-robot"></i> ${response}`, false);
            }, 400);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        if (sendButton) sendButton.addEventListener('click', sendMessage);
        if (chatInput) chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    })();

    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const icon = document.body.classList.contains('dark-mode') ? 'fa-sun' : 'fa-moon';
            themeToggle.innerHTML = `<i class="fas ${icon}"></i>`;
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });
    }

    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
    </script>
</body>
</html>