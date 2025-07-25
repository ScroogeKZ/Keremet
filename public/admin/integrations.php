<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Auth.php';

use App\Auth;

session_start();
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интеграции - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <span class="ml-3 text-lg font-medium text-gray-900">Хром-KZ</span>
                </div>
                <div class="flex items-center space-x-4 text-sm">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-900">Панель управления</a>
                    <a href="/admin/orders.php" class="text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/reports.php" class="text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/analytics.php" class="text-gray-600 hover:text-gray-900">Аналитика</a>
                    <a href="/admin/quick_actions.php" class="text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/logistics_calendar.php" class="text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/cost_calculator.php" class="text-gray-600 hover:text-gray-900">Калькулятор</a>
                    <a href="/admin/integrations.php" class="text-gray-900 font-medium">Интеграции</a>
                    <a href="/admin/settings.php" class="text-gray-600 hover:text-gray-900">Настройки</a>
                    <a href="/admin/logout.php" class="text-red-600 hover:text-red-700">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-medium text-gray-900">Интеграции и автоматизация</h1>
            <p class="text-gray-600 mt-1">Настройка внешних сервисов и автоматизации</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Telegram Integration -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">Telegram Bot</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bot Token</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="123456789:ABCDEF..." id="telegram_bot_token">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="-1001234567890" id="telegram_chat_id">
                    </div>
                    <button onclick="testTelegram()" class="bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">
                        Тестировать соединение
                    </button>
                    <button onclick="saveTelegram()" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                        Сохранить настройки
                    </button>
                </div>
            </div>

            <!-- WhatsApp Business API -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">WhatsApp Business API</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="1234567890123456">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="EAAxxxxxxxxxxxx">
                    </div>
                    <button class="bg-green-600 text-white px-4 py-2 text-sm hover:bg-green-700">
                        Тестировать WhatsApp
                    </button>
                </div>
            </div>

            <!-- Email SMTP -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">Email SMTP</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Сервер</label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Порт</label>
                            <input type="number" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                                   placeholder="587">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="noreply@chrome-kz.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Пароль приложения</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm">
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">
                        Тестировать Email
                    </button>
                </div>
            </div>

            <!-- 1C Integration -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">Интеграция с 1С</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL 1С Web-сервиса</label>
                        <input type="url" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="http://server:port/base/ws/logistics">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                            <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm">
                        </div>
                    </div>
                    <button class="bg-orange-600 text-white px-4 py-2 text-sm hover:bg-orange-700">
                        Тестировать 1С
                    </button>
                </div>
            </div>

            <!-- Maps Integration -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">Карты и геолокация</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps API Key</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="AIzaSyxxxxxxxxxxxxxxxxx">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Yandex Maps API Key</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx">
                    </div>
                    <div class="flex space-x-2">
                        <button class="bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">
                            Тест Google Maps
                        </button>
                        <button class="bg-red-600 text-white px-4 py-2 text-sm hover:bg-red-700">
                            Тест Yandex Maps
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Systems -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium mb-4">Платежные системы</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kaspi API Key</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CloudPayments Public ID</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 text-sm" 
                               placeholder="pk_xxxxxxxxxxxxxxx">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CloudPayments API Secret</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 text-sm">
                    </div>
                    <button class="bg-green-600 text-white px-4 py-2 text-sm hover:bg-green-700">
                        Тестировать платежи
                    </button>
                </div>
            </div>
        </div>

        <!-- Automation Rules -->
        <div class="mt-8">
            <h2 class="text-xl font-medium mb-4">Правила автоматизации</h2>
            <div class="bg-white border border-gray-200 p-6">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium mb-3">Автоматические действия</h3>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Автоматически отправлять SMS при смене статуса заказа</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Создавать задачи в календаре для новых заказов</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Автоматически рассчитывать стоимость доставки</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Отправлять напоминания о доставке за 1 час</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium mb-3">Уведомления</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Telegram</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">WhatsApp</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">Email</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" class="text-blue-600">
                                <span class="text-sm">SMS</span>
                            </label>
                        </div>
                    </div>

                    <button class="bg-gray-900 text-white px-6 py-2 text-sm hover:bg-gray-800">
                        Сохранить правила автоматизации
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testTelegram() {
            const token = document.getElementById('telegram_bot_token').value;
            const chatId = document.getElementById('telegram_chat_id').value;
            
            if (!token || !chatId) {
                alert('Заполните все поля для Telegram');
                return;
            }
            
            // Здесь будет AJAX запрос для тестирования
            alert('Тестирование Telegram Bot...');
        }
        
        function saveTelegram() {
            const token = document.getElementById('telegram_bot_token').value;
            const chatId = document.getElementById('telegram_chat_id').value;
            
            // Здесь будет AJAX запрос для сохранения настроек
            alert('Настройки Telegram сохранены');
        }
    </script>
</body>
</html>