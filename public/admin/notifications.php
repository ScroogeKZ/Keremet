<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../config/database.php';

use App\Auth;

session_start();
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$db = \Database::getInstance()->getConnection();

// Создаем таблицу уведомлений если не существует
$db->exec("
    CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        order_id INTEGER,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'mark_all_read') {
        $db->exec("UPDATE notifications SET is_read = TRUE");
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    header('Location: /admin/notifications.php');
    exit;
}

// Получаем все уведомления
$stmt = $db->query("
    SELECT n.*, s.pickup_address, s.delivery_address 
    FROM notifications n
    LEFT JOIN shipment_orders s ON n.order_id = s.id
    ORDER BY n.created_at DESC
");
$notifications = $stmt->fetchAll();

// Статистика уведомлений
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_read = FALSE THEN 1 END) as unread,
        COUNT(CASE WHEN type = 'new_order' THEN 1 END) as new_orders,
        COUNT(CASE WHEN type = 'status_change' THEN 1 END) as status_changes
    FROM notifications
")->fetch();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Центр уведомлений - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .notification-card {
            transition: all 0.3s ease;
        }
        .notification-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .notification-unread {
            border-left: 4px solid #3b82f6;
            background: #f8fafc;
        }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Центр уведомлений</h1>
                        <p class="text-sm text-gray-500">Управление уведомлениями системы</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Панель</a>
                    <a href="/admin/orders.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/mobile.php" class="text-sm text-gray-600 hover:text-gray-900">Мобильная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto py-6 px-4">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM7 7h.01M7 3h5l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?></p>
                        <p class="text-sm text-gray-600">Всего уведомлений</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center relative">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php if ($stats['unread'] > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full pulse-dot"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-orange-600"><?= $stats['unread'] ?></p>
                        <p class="text-sm text-gray-600">Непрочитанных</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-green-600"><?= $stats['new_orders'] ?></p>
                        <p class="text-sm text-gray-600">Новые заказы</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-purple-600"><?= $stats['status_changes'] ?></p>
                        <p class="text-sm text-gray-600">Изменения статуса</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Список уведомлений</h2>
            <div class="flex space-x-3">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700">
                        Отметить все как прочитанные
                    </button>
                </form>
                <button onclick="refreshNotifications()" class="bg-gray-600 text-white px-4 py-2 text-sm rounded hover:bg-gray-700">
                    Обновить
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-4">
            <?php if (empty($notifications)): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM7 7h.01M7 3h5l5 5v11a2 2 0 01-2-2H7a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Нет уведомлений</h3>
                <p class="text-gray-600">Новые уведомления будут появляться здесь</p>
            </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-card bg-white rounded-lg border border-gray-200 p-6 <?= !$notification['is_read'] ? 'notification-unread' : '' ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="flex-shrink-0">
                                    <?php
                                    $iconClass = match($notification['type']) {
                                        'new_order' => 'text-green-600 bg-green-100',
                                        'status_change' => 'text-blue-600 bg-blue-100',
                                        'urgent' => 'text-red-600 bg-red-100',
                                        default => 'text-gray-600 bg-gray-100'
                                    };
                                    ?>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $iconClass ?>">
                                        <?php if ($notification['type'] === 'new_order'): ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        <?php elseif ($notification['type'] === 'status_change'): ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <?php else: ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900"><?= htmlspecialchars($notification['title']) ?></h3>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    
                                    <?php if ($notification['order_id'] && $notification['pickup_address']): ?>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <span class="font-medium">Заказ #<?= $notification['order_id'] ?>:</span>
                                        <?= htmlspecialchars($notification['pickup_address']) ?> → <?= htmlspecialchars($notification['delivery_address']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-4">
                                <span class="text-xs text-gray-500">
                                    <?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?>
                                </span>
                                <div class="flex space-x-2">
                                    <?php if (!$notification['is_read']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="id" value="<?= $notification['id'] ?>">
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                                            Отметить как прочитанное
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($notification['order_id']): ?>
                                    <a href="/admin/orders.php?view=<?= $notification['order_id'] ?>" 
                                       class="text-xs text-green-600 hover:text-green-800">
                                        Просмотреть заказ
                                    </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $notification['id'] ?>">
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800"
                                                onclick="return confirm('Удалить уведомление?')">
                                            Удалить
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!$notification['is_read']): ?>
                        <div class="ml-4">
                            <span class="w-2 h-2 bg-blue-600 rounded-full pulse-dot"></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function refreshNotifications() {
            window.location.reload();
        }

        // Автоматическое обновление каждые 30 секунд
        setInterval(refreshNotifications, 30000);

        // Показать уведомление в браузере если есть непрочитанные
        <?php if ($stats['unread'] > 0): ?>
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Хром-KZ Логистика', {
                body: 'У вас <?= $stats['unread'] ?> непрочитанных уведомлений',
                icon: '/assets/logo.png'
            });
        } else if ('Notification' in window && Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('Хром-KZ Логистика', {
                        body: 'У вас <?= $stats['unread'] ?> непрочитанных уведомлений',
                        icon: '/assets/logo.png'
                    });
                }
            });
        }
        <?php endif; ?>

        // Обновление заголовка страницы с количеством непрочитанных
        <?php if ($stats['unread'] > 0): ?>
        document.title = '(<?= $stats['unread'] ?>) Центр уведомлений - Хром-KZ';
        <?php endif; ?>
    </script>
</body>
</html>