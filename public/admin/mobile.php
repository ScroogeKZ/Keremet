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

// Получаем статистику для мобильного интерфейса
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as today_orders
    FROM shipment_orders
");
$stats = $stmt->fetch();

// Последние заказы
$stmt = $db->query("
    SELECT id, pickup_address, delivery_address, status, created_at, shipping_cost
    FROM shipment_orders 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мобильная админка - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mobile-card { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-bottom: 16px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .quick-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50 pb-20">
    <!-- Mobile Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">Мобильная админка</h1>
                        <p class="text-xs text-gray-500">Управление на ходу</p>
                    </div>
                </div>
                <button onclick="toggleMenu()" class="p-2 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="px-4 py-4">
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="mobile-card p-4">
                <div class="text-2xl font-bold text-blue-600"><?= $stats['total_orders'] ?></div>
                <div class="text-sm text-gray-600">Всего заказов</div>
            </div>
            <div class="mobile-card p-4">
                <div class="text-2xl font-bold text-orange-600"><?= $stats['pending_orders'] ?></div>
                <div class="text-sm text-gray-600">Ожидают</div>
            </div>
            <div class="mobile-card p-4">
                <div class="text-2xl font-bold text-green-600"><?= $stats['active_orders'] ?></div>
                <div class="text-sm text-gray-600">В работе</div>
            </div>
            <div class="mobile-card p-4">
                <div class="text-2xl font-bold text-purple-600"><?= $stats['today_orders'] ?></div>
                <div class="text-sm text-gray-600">Сегодня</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Быстрые действия</h3>
            <a href="/astana.php" class="quick-action">+ Новый заказ по Астане</a>
            <a href="/regional.php" class="quick-action">+ Межгородской заказ</a>
            <a href="/admin/cost_calculator.php" class="quick-action">🧮 Калькулятор стоимости</a>
        </div>

        <!-- Recent Orders -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Последние заказы</h3>
            <?php foreach ($recent_orders as $order): ?>
            <div class="mobile-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-gray-900">#<?= $order['id'] ?></span>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="text-sm text-gray-600 mb-2">
                    <div class="truncate">От: <?= htmlspecialchars($order['pickup_address']) ?></div>
                    <div class="truncate">До: <?= htmlspecialchars($order['delivery_address']) ?></div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
                    <?php if ($order['shipping_cost']): ?>
                    <span class="font-medium text-green-600"><?= number_format($order['shipping_cost'], 0, ',', ' ') ?>₸</span>
                    <?php endif; ?>
                </div>
                <div class="mt-3 flex space-x-2">
                    <button onclick="viewOrder(<?= $order['id'] ?>)" class="flex-1 bg-blue-100 text-blue-700 py-2 px-3 rounded text-sm font-medium">
                        Просмотр
                    </button>
                    <button onclick="editOrder(<?= $order['id'] ?>)" class="flex-1 bg-gray-100 text-gray-700 py-2 px-3 rounded text-sm font-medium">
                        Изменить
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobileMenu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="fixed right-0 top-0 h-full w-80 bg-white shadow-lg">
            <div class="p-4 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Навигация</h2>
                    <button onclick="toggleMenu()" class="p-2 text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-4 space-y-3">
                <a href="/admin/panel.php" class="block py-3 px-4 rounded bg-gray-50 text-gray-900 font-medium">Панель управления</a>
                <a href="/admin/orders.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Все заказы</a>
                <a href="/admin/dashboard.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Дашборд</a>
                <a href="/admin/reports.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Отчеты</a>
                <a href="/admin/interactive_dashboard.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Интерактивная аналитика</a>
                <a href="/admin/advanced_analytics.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Расширенная аналитика</a>
                <a href="/admin/cost_calculator.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Калькулятор</a>
                <a href="/admin/logistics_calendar.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Календарь</a>
                <a href="/admin/quick_actions.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Быстрые действия</a>
                <a href="/admin/users.php" class="block py-3 px-4 rounded hover:bg-gray-50 text-gray-700">Пользователи</a>
                <hr class="my-4">
                <a href="/admin/logout.php" class="block py-3 px-4 rounded hover:bg-red-50 text-red-600 font-medium">Выйти</a>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <div class="grid grid-cols-4 h-16">
            <a href="/admin/mobile.php" class="flex flex-col items-center justify-center text-blue-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span class="text-xs mt-1">Главная</span>
            </a>
            <a href="/admin/orders.php" class="flex flex-col items-center justify-center text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-xs mt-1">Заказы</span>
            </a>
            <a href="/admin/dashboard.php" class="flex flex-col items-center justify-center text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="text-xs mt-1">Аналитика</span>
            </a>
            <a href="/admin/cost_calculator.php" class="flex flex-col items-center justify-center text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span class="text-xs mt-1">Калькулятор</span>
            </a>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        function viewOrder(id) {
            // Открываем детали заказа в модальном окне или новой странице
            window.location.href = `/admin/orders.php?view=${id}`;
        }

        function editOrder(id) {
            // Переходим к редактированию заказа
            window.location.href = `/admin/orders.php?edit=${id}`;
        }

        // Закрытие меню при клике на overlay
        document.getElementById('mobileMenu').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMenu();
            }
        });

        // PWA функциональность
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(console.error);
        }

        // Уведомления о новых заказах (имитация)
        function checkNewOrders() {
            // В реальном приложении здесь был бы AJAX запрос
            const badges = document.querySelectorAll('.status-pending');
            if (badges.length > 0) {
                document.title = `(${badges.length}) Мобильная админка - Хром-KZ`;
            }
        }

        // Проверяем новые заказы каждые 30 секунд
        setInterval(checkNewOrders, 30000);
        checkNewOrders();
    </script>
</body>
</html>