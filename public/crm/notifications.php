<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Notification;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

// Initialize notification model
$notificationModel = new Notification();

// Get current user ID from session
$currentUserId = $_SESSION['user_id'] ?? 1; // Default to admin user

// Get all notifications for current user
$allNotifications = $notificationModel->getForUser($currentUserId);

// Format notifications for display
$notifications = array_map(function($notification) use ($notificationModel) {
    return [
        'id' => $notification['id'],
        'type' => $notification['type'],
        'title' => $notification['title'],
        'message' => $notification['message'],
        'time' => $notificationModel->formatTime($notification['created_at']),
        'icon' => $notification['icon'],
        'color' => $notification['color'],
        'read' => $notification['is_read'],
        'related_id' => $notification['related_id'],
        'related_type' => $notification['related_type']
    ];
}, $allNotifications);

// Get unread count
$unreadCount = $notificationModel->getUnreadCount($currentUserId);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];
            $success = $notificationModel->markAsRead($notification_id, $currentUserId);
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Уведомление отмечено как прочитанное' : 'Ошибка при обновлении'
                ]);
                exit;
            }
        } elseif ($_POST['action'] === 'mark_all_read') {
            $success = $notificationModel->markAllAsRead($currentUserId);
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Все уведомления отмечены как прочитанные' : 'Ошибка при обновлении'
                ]);
                exit;
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];
            $success = $notificationModel->delete($notification_id, $currentUserId);
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Уведомление удалено' : 'Ошибка при удалении'
                ]);
                exit;
            }
        }
    }
    
    // Redirect back to notifications page for non-AJAX requests
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
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50" x-data="{ sidebarOpen: true }">
        <!-- Left Sidebar Navigation -->
        <div class="bg-white shadow-xl border-r border-gray-200 transition-all duration-300 ease-in-out flex flex-col overflow-hidden" 
             :class="sidebarOpen ? 'w-72' : 'w-16'">
            <!-- Logo/Brand -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-shipping-fast text-white text-sm"></i>
                    </div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent transition-opacity duration-300"
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0'"
                        x-show="sidebarOpen">Хром-KZ CRM</h1>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" 
                        class="ml-auto p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-bars text-gray-600" :class="sidebarOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto overflow-x-hidden">
                <a href="/crm" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-tachometer-alt mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Дашборд</span>
                </a>
                <a href="/crm/orders.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-box mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Заказы</span>
                </a>
                <a href="/crm/notifications.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm">
                    <i class="fas fa-bell mr-3 w-5 text-blue-600"></i>
                    <span class="font-semibold">Уведомления</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/crm/calendar.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-calendar mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Календарь</span>
                </a>
                <a href="/crm/bulk_operations.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-tasks mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Массовые операции</span>
                </a>
                <a href="/crm/reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-file-alt mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Отчеты</span>
                </a>
                <a href="/crm/analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-chart-bar mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Аналитика</span>
                </a>
            </nav>
            
            <!-- User Profile -->
            <div class="mt-auto p-4 border-t border-gray-200 bg-white flex-shrink-0">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Admin</p>
                        <p class="text-xs text-gray-500">Администратор</p>
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="origin-bottom-left absolute bottom-full left-0 mb-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="/crm/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Настройки</a>
                                <a href="/crm/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Выход</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Центр уведомлений</h2>
                    <p class="text-sm text-gray-600">Важные обновления и системные сообщения</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                        <i class="fas fa-globe mr-2"></i>На сайт
                    </a>
                    <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-auto bg-gray-50 p-8">

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Уведомления</h1>
                    <p class="mt-2 text-gray-600">Последние события и обновления системы</p>
                </div>
                <button onclick="markAllAsRead()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors duration-200 flex items-center">
                    <i class="fas fa-check-double mr-2"></i>Отметить все как прочитанные
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
                    <div class="p-6 hover:bg-gray-50 <?= !$notification['read'] ? 'bg-blue-50' : '' ?>" data-notification-id="<?= $notification['id'] ?>">
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
                                        <button onclick="markAsRead(<?= $notification['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm transition-colors duration-200">
                                            Отметить как прочитанное
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="deleteNotification(<?= $notification['id'] ?>)" 
                                                class="text-gray-400 hover:text-red-600 transition-colors duration-200">
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
        // Function to show toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Slide in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        // Mark all notifications as read
        function markAllAsRead() {
            if (!confirm('Отметить все уведомления как прочитанные?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            formData.append('ajax', '1');

            fetch('/crm/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Reload page to update UI
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Ошибка при обновлении уведомлений', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при обновлении уведомлений', 'error');
            });
        }

        // Mark single notification as read
        function markAsRead(notificationId) {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            formData.append('ajax', '1');

            fetch('/crm/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Find and update the notification in UI
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.classList.remove('bg-blue-50');
                        const newBadge = notificationElement.querySelector('.bg-blue-100');
                        const markReadBtn = notificationElement.querySelector('[onclick*="markAsRead"]');
                        if (newBadge) newBadge.remove();
                        if (markReadBtn) markReadBtn.remove();
                    }
                } else {
                    showToast('Ошибка при обновлении уведомления', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при обновлении уведомления', 'error');
            });
        }

        // Delete notification
        function deleteNotification(notificationId) {
            if (!confirm('Удалить это уведомление?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('notification_id', notificationId);
            formData.append('ajax', '1');

            fetch('/crm/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Find and remove the notification from UI
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.style.transition = 'all 0.3s ease';
                        notificationElement.style.opacity = '0';
                        notificationElement.style.transform = 'translateX(100%)';
                        setTimeout(() => {
                            notificationElement.remove();
                            // Update notification count
                            location.reload();
                        }, 300);
                    }
                } else {
                    showToast('Ошибка при удалении уведомления', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при удалении уведомления', 'error');
            });
        }
    </script>
</body>
</html>