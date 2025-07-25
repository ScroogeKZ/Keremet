<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

$message = '';
$messageType = '';

// Обработка настроек
if ($_POST) {
    try {
        // Здесь можно добавить сохранение настроек в базу данных
        $message = 'Настройки сохранены успешно';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Ошибка сохранения: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Настройки системы</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <?php if ($message): ?>
            <div class="mb-6 p-4 border <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Боковое меню -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 p-4">
                    <nav class="space-y-2">
                        <a href="#general" class="block px-3 py-2 text-sm bg-blue-50 text-blue-700 border-l-2 border-blue-500">
                            Общие настройки
                        </a>
                        <a href="#notifications" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Уведомления
                        </a>
                        <a href="#backup" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Резервное копирование
                        </a>
                        <a href="#security" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Безопасность
                        </a>
                        <a href="#api" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            API настройки
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Основной контент -->
            <div class="lg:col-span-2">
                <form method="POST" class="space-y-6">
                    <!-- Общие настройки -->
                    <div id="general" class="bg-white border border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Общие настройки</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Название компании
                                </label>
                                <input type="text" value="Хром-KZ Логистика" 
                                       class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Часовой пояс
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                    <option value="Asia/Almaty" selected>Алматы (UTC+6)</option>
                                    <option value="Asia/Nur-Sultan">Нур-Султан (UTC+6)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Язык по умолчанию
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                    <option value="ru" selected>Русский</option>
                                    <option value="kz">Қазақша</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Валюта
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                    <option value="KZT" selected>Тенге (₸)</option>
                                    <option value="USD">Доллар ($)</option>
                                    <option value="EUR">Евро (€)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки уведомлений -->
                    <div id="notifications" class="bg-white border border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Уведомления</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">Email уведомления</div>
                                    <div class="text-sm text-gray-500">Отправка писем при создании заказов</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" checked class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">Telegram уведомления</div>
                                    <div class="text-sm text-gray-500">Отправка сообщений в Telegram</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">SMS уведомления</div>
                                    <div class="text-sm text-gray-500">Отправка SMS клиентам</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки безопасности -->
                    <div id="security" class="bg-white border border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Безопасность</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Время сессии (минуты)
                                </label>
                                <input type="number" value="480" min="30" max="1440"
                                       class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                <p class="text-xs text-gray-500 mt-1">Через сколько минут неактивности выходить из системы</p>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">Двухфакторная аутентификация</div>
                                    <div class="text-sm text-gray-500">Дополнительная защита входа</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">Логирование действий</div>
                                    <div class="text-sm text-gray-500">Записывать все действия пользователей</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" checked class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 border border-gray-300 text-sm text-gray-700 hover:border-gray-400">
                            Отмена
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm hover:bg-blue-700">
                            Сохранить настройки
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>