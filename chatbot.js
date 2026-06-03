document.addEventListener('DOMContentLoaded', () => {
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('send-message');
    const chatMessages = document.getElementById('chat-messages');

    function getCurrentTime() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function addMessage(content, isUser, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message', isUser ? 'user' : 'bot');
        if (isLoading) {
            messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking...';
        } else {
            messageDiv.innerHTML = content;
            messageDiv.setAttribute('data-time', getCurrentTime());
        }
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return messageDiv;
    }

    function getWeatherSummary() {
        const weatherBox = document.querySelector('.weather-box');
        if (!weatherBox) return null;

        const cityElem = weatherBox.querySelector('h2');
        const tempElem = document.querySelector('.temperature');
        const descElem = document.querySelector('.description');
        const humidityElem = document.querySelector('.weather-details div:first-child');
        const windElem = document.querySelector('.weather-details div:last-child');

        return {
            city: cityElem ? cityElem.textContent.replace('📍 Current Weather in ', '').trim() : 'unknown',
            temp: tempElem ? tempElem.textContent : 'N/A',
            desc: descElem ? descElem.textContent : 'N/A',
            humidity: humidityElem ? humidityElem.textContent : 'N/A',
            wind: windElem ? windElem.textContent : 'N/A'
        };
    }

    function getForecastSummary() {
        const forecastItems = document.querySelectorAll('.forecast-item');
        if (forecastItems.length === 0) return null;

        const forecasts = [];
        forecastItems.forEach(item => {
            forecasts.push({
                day: item.querySelector('.forecast-date')?.textContent || 'N/A',
                temp: item.querySelector('.forecast-temp')?.textContent || 'N/A',
                desc: item.querySelector('.forecast-desc')?.textContent || 'N/A'
            });
        });
        return forecasts;
    }

    function generateSmartResponse(userMessage) {
        const msg = userMessage.toLowerCase().trim();
        const weather = getWeatherSummary();
        const forecast = getForecastSummary();

        if (/(hi|hello|hey|namaste|good morning|good afternoon|good evening)/.test(msg)) {
            const hour = new Date().getHours();
            let greeting = 'Hello';
            if (hour < 12) greeting = 'Good morning';
            else if (hour < 18) greeting = 'Good afternoon';
            else greeting = 'Good evening';
            return `${greeting}! 👋 I'm your weather assistant. Ask me about current weather, forecasts, or if you need an umbrella today!`;
        }

        if (/(how are you|how r u|how do you do)/.test(msg)) {
            return "I'm doing great, thanks for asking! 🌟 The weather is my specialty, so I'm always excited to help you plan your day.";
        }

        if (/(weather|temperature|temp|current|outside|how is it)/.test(msg) && !msg.includes('forecast')) {
            if (weather) {
                return `📍 Currently in ${weather.city}: ${weather.temp}. ${weather.desc}. ${weather.humidity}. ${weather.wind}.`;
            }
            return 'Please search for a city first using the search box above, then I can tell you all about the weather there! 🔍';
        }

        if (/(forecast|next days|upcoming|week|tomorrow|future)/.test(msg)) {
            if (forecast && forecast.length > 0) {
                let response = '📅 Here is your 5-day forecast:\n';
                forecast.forEach(f => {
                    response += `• ${f.day}: ${f.temp}, ${f.desc}\n`;
                });
                return response;
            }
            return 'Please search for a city first to see the 5-day forecast!';
        }

        if (/(umbrella|rain|wet|shower|precipitation|drizzle)/.test(msg)) {
            if (weather && weather.desc.toLowerCase().includes('rain')) {
                return 'Yes, it looks like rain is expected! 🌧️ Definitely take an umbrella with you today. Stay dry! ☔';
            } else if (forecast && forecast.some(f => f.desc.toLowerCase().includes('rain'))) {
                return 'Rain might be coming in the next few days. Keep an umbrella handy just in case! 🌂';
            } else if (weather) {
                return 'No rain in the current forecast. You can leave the umbrella at home today! ☀️';
            }
            return 'I need to know your city first. Search for a city above to check if you need an umbrella!';
        }

        if (/(wind|breeze|storm|cyclone|gale)/.test(msg)) {
            if (weather && weather.wind) {
                const windSpeed = weather.wind.match(/\d+\.?\d*/);
                if (windSpeed && parseFloat(windSpeed[0]) > 15) {
                    return `⚠️ Windy conditions detected! ${weather.wind}. Please be careful if you're going outside.`;
                }
                return `Current wind conditions: ${weather.wind}. It should be fine for most outdoor activities.`;
            }
            return 'Search for a city first to check wind conditions!';
        }

        if (/(humidity|humid|sticky|dry)/.test(msg)) {
            if (weather && weather.humidity) {
                const humidValue = weather.humidity.match(/\d+/);
                if (humidValue && parseInt(humidValue[0]) > 70) {
                    return `${weather.humidity} - That's quite humid! It might feel stickier than usual today. Drink plenty of water! 💧`;
                }
                return `Current humidity: ${weather.humidity}. Comfortable levels for most people.`;
            }
            return 'Search for a city first to check humidity levels!';
        }

        if (/(hot|cold|freezing|chilly|warm|cool)/.test(msg)) {
            if (weather && weather.temp) {
                const tempValue = parseFloat(weather.temp.replace('°', ''));
                if (tempValue > 30) return `🔥 Yes, it's HOT! ${weather.temp}. Stay cool, drink water, and avoid peak sun hours.`;
                if (tempValue < 10) return `❄️ Brrr, it's cold! ${weather.temp}. Bundle up with warm layers!`;
                if (tempValue > 25) return `🌞 It's warm at ${weather.temp}. Perfect weather for outdoor plans!`;
                return `The temperature is ${weather.temp}. Pretty comfortable weather!`;
            }
            return 'Search for a city first to check the temperature!';
        }

        if (/(compare|difference|better|worse)/.test(msg)) {
            return 'I specialize in current weather and forecasts for your searched city. Try asking about temperature, rain, or wind specifically! 🌈';
        }

        if (/(help|what can you do|features|capabilities)/.test(msg)) {
            return '🤖 I can help you with:\n• Current weather conditions\n• 5-day forecasts\n• Rain & umbrella checks\n• Wind conditions\n• Humidity levels\n• Temperature advice\n\nJust search for a city first, then ask away!';
        }

        if (/(thank|thanks|good|helpful)/.test(msg)) {
            return 'You\'re very welcome! 😊 Always happy to help you stay weather-ready. Anything else you\'d like to know?';
        }

        return 'I\'m your weather specialist! 🌤️ Ask me about current weather, forecasts, rain, wind, humidity, or temperature. For the best results, search for a city first using the box above!';
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
        }, 500 + Math.random() * 500);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    sendButton.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});

// ============================================
// DYNAMIC CHAT HEIGHT - Screen Space Optimizer
// ============================================

function adjustChatHeight() {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    const width = window.innerWidth;
    const height = window.innerHeight;
    
    let chatHeight = 450; // Default for desktop
    
    // Adjust based on screen width
    if (width <= 480) {
        chatHeight = 320;  // Small mobile
    } else if (width <= 768) {
        chatHeight = 380;  // Mobile/Tablet
    } else if (width <= 1024) {
        chatHeight = 420;  // Tablet
    }
    
    // Adjust based on screen height (override width settings)
    if (height <= 600) {
        chatHeight = 250;  // Very short screens
    } else if (height <= 700) {
        chatHeight = 320;  // Short screens (landscape mobile)
    } else if (height >= 1000 && width > 768) {
        chatHeight = 550;  // Tall desktop screens
    } else if (height >= 1200) {
        chatHeight = 600;  // Very tall screens
    }
    
    // Apply the height
    chatMessages.style.height = `${chatHeight}px`;
    chatMessages.style.minHeight = `${chatHeight}px`;
    
    console.log(`📱 Chat height optimized: ${chatHeight}px (${width}x${height})`);
}

// Initialize dynamic chat height
(function initDynamicLayout() {
    // Wait for DOM to be ready
    const runAdjustment = () => {
        if (document.getElementById('chat-messages')) {
            adjustChatHeight();
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAdjustment);
    } else {
        runAdjustment();
    }
    
    // Adjust on window resize (with debounce)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            adjustChatHeight();
        }, 200);
    });
    
    // Adjust on device orientation change (mobile)
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            adjustChatHeight();
        }, 100);
    });
    
    console.log('✅ Dynamic layout system initialized');
})();