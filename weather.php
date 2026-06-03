<?php
session_start();
require_once 'config.php';

$units = $_GET['units'] ?? ($_SESSION['units'] ?? 'metric');
$_SESSION['units'] = $units;
$unitLabel = $units == 'metric' ? 'C' : 'F';
$windUnit = $units == 'metric' ? 'm/s' : 'mph';

function fetchUrl($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$data, $code];
    }

    $data = @file_get_contents($url);
    $code = 0;
    if (!empty($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $hdr) {
            if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#i', $hdr, $matches)) {
                $code = (int)$matches[1];
                break;
            }
        }
    }
    return [$data, $code];
}

$error = '';
$cityName = $temperature = $description = $humidity = $windSpeed = $icon = null;
$forecastList = [];
$alert = '';

if (!empty($_GET['city'])) {
    $city = urlencode(trim(preg_replace('/[^a-zA-Z\s]/', '', $_GET['city'])));
    $apiKey = OPENWEATHER_API_KEY;

    list($data, $code) = fetchUrl("https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=$units");
    if ($data && $code == 200) {
        $weather = json_decode($data, true);
        if (isset($weather['cod']) && $weather['cod'] == 200) {
            $cityName = $weather['name'];
            $temperature = $weather['main']['temp'];
            $description = $weather['weather'][0]['description'];
            $humidity = $weather['main']['humidity'];
            $windSpeed = $weather['wind']['speed'] ?? 0;
            $icon = $weather['weather'][0]['icon'];

            $windThreshold = $units == 'metric' ? 15 : 33.5;
            if ($windSpeed > $windThreshold) {
                $alert = "⚠️ Warning: High winds detected!";
            } elseif (stripos($description, 'storm') !== false) {
                $alert = "🌧️ Storm conditions possible. Stay safe!";
            }
        } else {
            $error = "City not found. Please check spelling.";
        }
    } else {
        $error = "Failed to fetch weather data. Please try again.";
    }

    if (!$error) {
        list($fdata) = fetchUrl("https://api.openweathermap.org/data/2.5/forecast?q=$city&appid=$apiKey&units=$units");
        if ($fdata) {
            $forecast = json_decode($fdata, true);
            if (isset($forecast['list'])) {
                $forecastList = [];
                $prevDate = '';
                foreach ($forecast['list'] as $item) {
                    $date = date('Y-m-d', $item['dt']);
                    if ($date !== $prevDate && date('H', $item['dt']) >= 12 && count($forecastList) < 5) {
                        $forecastList[] = $item;
                        $prevDate = $date;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>WeatherWise - Weather</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
     <link rel="icon" href="scattered-thunderstorms.png">
</head>
<body>
    <header>
        <nav>
            <div class="logo">WeatherWise</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="weather.php" class="active">Weather</a></li>
            </ul>
            <button id="theme-toggle" aria-label="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
        </nav>
    </header>

    <main>
        <div class="card">
            <form method="GET" id="weather-form">
                <div class="search-group">
                    <input type="text" id="city-input" name="city" placeholder="Enter city name (e.g., Chennai, London, Tokyo)" required>
                    <select name="units">
                        <option value="metric" <?= $units=='metric'?'selected':'' ?>>Celsius (°C)</option>
                        <option value="imperial" <?= $units=='imperial'?'selected':'' ?>>Fahrenheit (°F)</option>
                    </select>
                    <button type="submit" id="search-btn"><i class="fas fa-search"></i> Get Weather</button>
                </div>
                <button type="button" id="geolocation-btn" class="geo-btn"><i class="fas fa-location-dot"></i> Use My Location</button>
            </form>
        </div>

        <div id="loading-spinner" class="loading" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i> Fetching weather data...
        </div>

        <?php if ($cityName): ?>
            <div class="card">
                <div class="weather-main">
                    <img src="https://openweathermap.org/img/wn/<?= $icon ?>@4x.png" alt="<?= $description ?>">
                    <div>
                        <div class="temperature"><?= round($temperature) ?>°<?= $unitLabel ?></div>
                        <div class="description"><?= ucfirst($description) ?></div>
                    </div>
                </div>
                <div class="weather-details">
                    <div><i class="fas fa-tint"></i> Humidity: <?= $humidity ?>%</div>
                    <div><i class="fas fa-wind"></i> Wind: <?= $windSpeed ?> <?= $windUnit ?></div>
                </div>
                <?php if ($alert): ?>
                    <p class="alert"><i class="fas fa-exclamation-triangle"></i> <?= $alert ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($forecastList)): ?>
                <div class="card">
                    <h2 style="margin-bottom: 1rem;"><i class="fas fa-calendar-week"></i> 5-Day Forecast</h2>
                    <div class="forecast-container">
                        <?php foreach($forecastList as $item): ?>
                            <div class="forecast-item">
                                <div class="forecast-date"><?= date('D, M d', $item['dt']) ?></div>
                                <img src="https://openweathermap.org/img/wn/<?= $item['weather'][0]['icon'] ?>@2x.png" alt="">
                                <div class="forecast-temp"><?= round($item['main']['temp']) ?>°</div>
                                <div class="forecast-desc"><?= ucfirst($item['weather'][0]['description']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif($error): ?>
            <div class="card error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- CHATBOT SECTION - FULLY SELF-CONTAINED, NO EXTERNAL FILE! -->
        <div class="card">
            <h2 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-robot"></i> AI Weather Assistant
            </h2>
            <div id="chat-container">
                <div id="chat-messages">
                    <div class="chat-message bot">
                        👋 Hi! I'm your weather assistant. Ask me about temperature, rain, wind, or anything weather-related!
                    </div>
                </div>
                <div class="chat-input">
                    <input type="text" id="chat-input" placeholder="Ask me something... (e.g., 'Should I take an umbrella?')">
                    <button id="send-message"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 WeatherWise | Powered by OpenWeatherMap</p>
    </footer>

    <!-- FULL CHATBOT JAVASCRIPT - EMBEDDED DIRECTLY, NO EXTERNAL FILE! -->
    <script>
    // ============================================
    // COMPLETE CHATBOT - EMBEDDED IN WEATHER.PHP
    // ============================================
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

        function getWeatherSummary() {
            const tempElem = document.querySelector('.temperature');
            const descElem = document.querySelector('.description');
            const cityElem = document.querySelector('.weather-main + div');
            const humidityElem = document.querySelector('.weather-details div:first-child');
            const windElem = document.querySelector('.weather-details div:last-child');
            
            return {
                temp: tempElem ? tempElem.textContent : 'N/A',
                desc: descElem ? descElem.textContent : 'N/A',
                humidity: humidityElem ? humidityElem.textContent : 'N/A',
                wind: windElem ? windElem.textContent : 'N/A'
            };
        }

        function generateSmartResponse(msg) {
            const lowerMsg = msg.toLowerCase();
            const weather = getWeatherSummary();
            
            if (/(hi|hello|hey)/i.test(lowerMsg)) {
                return "Hello! 👋 I'm your weather assistant. Ask me about the weather!";
            }
            if (/(weather|temperature|temp)/i.test(lowerMsg)) {
                return `📍 Current weather: ${weather.temp}, ${weather.desc}. ${weather.humidity}. ${weather.wind}.`;
            }
            if (/(umbrella|rain)/i.test(lowerMsg)) {
                if (weather.desc && weather.desc.toLowerCase().includes('rain')) {
                    return "Yes, take an umbrella! ☔";
                }
                return "No rain expected. No umbrella needed! ☀️";
            }
            if (/(forecast|next days)/i.test(lowerMsg)) {
                const items = document.querySelectorAll('.forecast-item');
                if (items.length > 0) {
                    let response = "📅 5-day forecast:\n";
                    items.forEach(item => {
                        const day = item.querySelector('.forecast-date')?.textContent;
                        const temp = item.querySelector('.forecast-temp')?.textContent;
                        response += `• ${day}: ${temp}\n`;
                    });
                    return response;
                }
                return "Please search for a city first to see the forecast!";
            }
            return "Ask me about weather, temperature, rain, or forecast! 🌤️";
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
                addMessage(`<i class="fas fa-robot"></i> ${response.replace(/\n/g, '<br>')}`, false);
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
    </script>

    <script>
        // Weather form and theme functionality
        const lastCity = localStorage.getItem('lastCity');
        if (lastCity && document.getElementById('city-input')) {
            document.getElementById('city-input').value = lastCity;
        }

        const form = document.getElementById('weather-form');
        if (form) {
            form.addEventListener('submit', function() {
                const city = document.getElementById('city-input').value.trim();
                if (city) {
                    localStorage.setItem('lastCity', city);
                }
                const spinner = document.getElementById('loading-spinner');
                if (spinner) spinner.style.display = 'flex';
            });
        }

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

        const geoBtn = document.getElementById('geolocation-btn');
        if (geoBtn) {
            geoBtn.addEventListener('click', () => {
                if (navigator.geolocation) {
                    const spinner = document.getElementById('loading-spinner');
                    if (spinner) spinner.style.display = 'flex';
                    navigator.geolocation.getCurrentPosition(async (position) => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const apiKey = '<?= OPENWEATHER_API_KEY ?>';
                        const units = document.querySelector('select[name="units"]').value;
                        try {
                            const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=${units}`);
                            const data = await response.json();
                            if (data.name) {
                                document.getElementById('city-input').value = data.name;
                                form.submit();
                            }
                        } catch (err) {
                            if (spinner) spinner.style.display = 'none';
                            alert('Could not get location weather');
                        }
                    }, () => {
                        if (spinner) spinner.style.display = 'none';
                        alert('Please enable location access to use this feature');
                    });
                } else {
                    alert('Geolocation is not supported by your browser');
                }
            });
        }
    </script>
</body>
</html>