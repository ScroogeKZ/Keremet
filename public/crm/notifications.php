<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

// Sample notifications data (in a real system, this would come from database)
$notifications = [
    [
        'id' => 1,
        'type' => 'new_order',
        'title' => 'Новый заказ #1024',
        'message' => 'Получен новый заказ на доставку по Астане',
        'time' => '2 минуты назад',
        'read' => false,
        'icon' => 'fas fa-box',
        'color' => 'blue'
    ],
    [
        'id' => 2,
        'type' => 'status_change',
        'title' => 'Заказ #1023 завершен',
        'message' => 'Доставка металлических плинтусов завершена успешно',
        'time' => '15 минут назад',
        'read' => false,
        'icon' => 'fas fa-check-circle',
        'color' => 'green'
    ],
    [
        'id' => 3,
        'type' => 'urgent',
        'title' => 'Срочная доставка',
        'message' => 'Клиент запросил срочную доставку на завтра',
        'time' => '1 час назад',
        'read' => true,
        'icon' => 'fas fa-exclamation-triangle',
        'color' => 'red'
    ],
    [
        'id' => 4,
        'type' => 'system',
        'title' => 'Обновление системы',
        'message' => 'Система была обновлена до версии 2.1',
        'time' => '3 часа назад',
        'read' => true,
        'icon' => 'fas fa-cogs',
        'color' => 'gray'
    ]
];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    // In real system, update database here
    header('Location: /crm/notifications.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления - CRM Система</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Хром-KZ CRM</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/crm" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>Дашборд
                        </a>
                        <a href="/crm/orders.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-box mr-2"></i>Заказы
                        </a>
                        <a href="/crm/notifications.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-bell mr-2"></i>Уведомления
                        </a>
                        <a href="/crm/calendar.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-calendar mr-2"></i>Календарь
                        </a>
                        <a href="/crm/bulk_operations.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tasks mr-2"></i>Массовые операции
                        </a>
                        <a href="/crm/reports.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-file-alt mr-2"></i>Отчеты
                        </a>
                        <a href="/crm/analytics.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>Аналитика
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="/crm/logout.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Выход
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Уведомления</h1>
                    <p class="mt-2 text-gray-600">Последние события и обновления системы</p>
                </div>
                <button onclick="markAllAsRead()" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                    <i class="fas fa-check-double mr-1"></i>Отметить все как прочитанные
                </button>
            </div>

            <!-- Notification Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bell text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Всего уведомлений</p>
                            <p class="text-lg font-semibold text-gray-900"><?= count($notifications) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-envelope text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Непрочитанные</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= count(array_filter($notifications, fn($n) => !$n['read'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Срочные</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= count(array_filter($notifications, fn($n) => $n['type'] === 'urgent')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Последние уведомления</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="p-6 hover:bg-gray-50 <?= !$notification['read'] ? 'bg-blue-50' : '' ?>">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-<?= $notification['color'] ?>-100 flex items-center justify-center">
                                    <i class="<?= $notification['icon'] ?> text-<?= $notification['color'] ?>-600"></i>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($notification['title']) ?>
                                            <?php if (!$notification['read']): ?>
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Новое
                                            </span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-2">
                                            <?= htmlspecialchars($notification['time']) ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if (!$notification['read']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <button type="submit" name="mark_read" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                Отметить как прочитанное
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="mt-6 bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Настройки уведомлений</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Новые заказы</p>
                                <p class="text-sm text-gray-500">Уведомления о новых заказах</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Изменения статуса</p>
                                <p class="text-sm text-gray-500">Уведомления об изменении статуса заказов</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Срочные доставки</p>
                                <p class="text-sm text-gray-500">Уведомления о срочных заказах</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Системные уведомления</p>
                                <p class="text-sm text-gray-500">Уведомления об обновлениях системы</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                            Сохранить настройки
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function markAllAsRead() {
            if (confirm('Отметить все уведомления как прочитанные?')) {
                // In real system, make AJAX call to mark all as read
                location.reload();
            }
        }
    </script>
</body>
</html>