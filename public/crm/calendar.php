<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

$orders = new ShipmentOrder();

// Get current week dates
$current_date = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$start_of_week = clone $current_date;
$start_of_week->modify('monday this week');

$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $day = clone $start_of_week;
    $day->modify("+$i days");
    $week_days[] = $day;
}

// Get orders for this week
$week_start = $start_of_week->format('Y-m-d');
$week_end = $start_of_week->modify('+6 days')->format('Y-m-d');
$week_orders = $orders->getFiltered([
    'date_from' => $week_start,
    'date_to' => $week_end
]);

// Group orders by date
$orders_by_date = [];
foreach ($week_orders as $order) {
    $order_date = date('Y-m-d', strtotime($order['created_at']));
    if (!isset($orders_by_date[$order_date])) {
        $orders_by_date[$order_date] = [];
    }
    $orders_by_date[$order_date][] = $order;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь доставок - CRM Система</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen bg-gray-50">
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
                <a href="/crm/notifications.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-bell mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Уведомления</span>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">3</span>
                </a>
                <a href="/crm/calendar.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm">
                    <i class="fas fa-calendar mr-3 w-5 text-blue-600"></i>
                    <span class="font-semibold">Календарь</span>
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
                    <h2 class="text-2xl font-bold text-gray-900">Календарь доставок</h2>
                    <p class="text-sm text-gray-600">Планирование и контроль доставок по дням</p>
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
            <main class="flex-1 overflow-auto p-4 md:p-6 lg:p-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 space-y-4 sm:space-y-0">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Календарь доставок</h1>
                        <p class="mt-2 text-sm md:text-base text-gray-600">Планирование и контроль доставок на неделю</p>
                    </div>
                    
                    <!-- Week Navigation -->
                    <div class="flex items-center justify-center sm:justify-end space-x-2 md:space-x-4">
                        <?php
                        $prev_week = clone $current_date;
                        $prev_week->modify('-1 week');
                        $next_week = clone $current_date;
                        $next_week->modify('+1 week');
                        ?>
                        <a href="?date=<?= $prev_week->format('Y-m-d') ?>" 
                           class="bg-white border border-gray-300 rounded-md px-2 md:px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="text-sm md:text-lg font-medium text-gray-900 px-2">
                            <?= $week_days[0]->format('d.m') ?> - <?= $week_days[6]->format('d.m.Y') ?>
                        </span>
                        <a href="?date=<?= $next_week->format('Y-m-d') ?>" 
                           class="bg-white border border-gray-300 rounded-md px-2 md:px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- Desktop Calendar (hidden on mobile) -->
                    <div class="hidden lg:block">
                        <div class="grid grid-cols-7 gap-px bg-gray-200">
                            <!-- Week header -->
                            <?php 
                            $day_names = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                            foreach ($day_names as $day_name): 
                            ?>
                            <div class="bg-gray-50 p-3 text-center">
                                <div class="text-sm font-medium text-gray-900"><?= $day_name ?></div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Week days -->
                            <?php foreach ($week_days as $day): ?>
                            <div class="bg-white p-3 min-h-48">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-900"><?= $day->format('d.m') ?></span>
                                    <?php
                                    $day_key = $day->format('Y-m-d');
                                    $day_orders = $orders_by_date[$day_key] ?? [];
                                    $order_count = count($day_orders);
                                    ?>
                                    <?php if ($order_count > 0): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= $order_count ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Orders for this day -->
                                <div class="space-y-1">
                                    <?php foreach (array_slice($day_orders, 0, 4) as $order): ?>
                                    <div class="bg-gray-50 border border-gray-200 rounded p-2 text-xs cursor-pointer hover:bg-gray-100 transition-colors"
                                         onclick="showOrderDetails(<?= $order['id'] ?>)">
                                        <div class="font-medium text-gray-900 truncate">
                                            #<?= $order['id'] ?> - <?= htmlspecialchars($order['cargo_type']) ?>
                                        </div>
                                        <div class="text-gray-500 truncate mt-1">
                                            <?= htmlspecialchars($order['pickup_address']) ?>
                                        </div>
                                        <div class="flex justify-between items-center mt-1">
                                            <span class="px-1 py-0.5 rounded text-xs font-medium 
                                                <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($order['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                            <span class="text-gray-400">
                                                <?= $order['order_type'] === 'astana' ? 'АСТ' : 'РЕГ' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($order_count > 4): ?>
                                    <div class="text-center mt-2">
                                        <button class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            +<?= $order_count - 4 ?> еще
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mobile Calendar (shown on mobile and tablet) -->
            <div class="block lg:hidden">
                <div class="space-y-4">
                    <?php foreach ($week_days as $day): ?>
                    <?php
                    $day_key = $day->format('Y-m-d');
                    $day_orders = $orders_by_date[$day_key] ?? [];
                    $order_count = count($day_orders);
                    $day_names_short = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                    $day_names_full = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                    $day_index = (int)$day->format('N') - 1;
                    ?>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <span class="sm:hidden"><?= $day_names_short[$day_index] ?></span>
                                        <span class="hidden sm:inline"><?= $day_names_full[$day_index] ?></span>
                                        <span class="ml-2 text-gray-500"><?= $day->format('d.m.Y') ?></span>
                                    </div>
                                </div>
                                <?php if ($order_count > 0): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= $order_count ?> заказов
                                </span>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">Нет заказов</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($order_count > 0): ?>
                        <div class="p-4 space-y-3">
                            <?php foreach ($day_orders as $order): ?>
                            <div class="border border-gray-200 rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                                 onclick="showOrderDetails(<?= $order['id'] ?>)">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="text-sm font-medium text-gray-900">#<?= $order['id'] ?></span>
                                            <span class="px-2 py-1 rounded text-xs font-medium 
                                                <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($order['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональная' ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-900 mb-1">
                                            <strong>Груз:</strong> <?= htmlspecialchars($order['cargo_type']) ?>
                                        </div>
                                        <div class="text-sm text-gray-600 truncate">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?= htmlspecialchars($order['pickup_address']) ?>
                                        </div>
                                        <?php if (!empty($order['shipping_cost'])): ?>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-ruble-sign mr-1"></i>
                                            <?= number_format($order['shipping_cost'], 0, ',', ' ') ?> ₸
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-shrink-0 ml-2">
                                        <i class="fas fa-chevron-right text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-calendar-times text-2xl mb-2"></i>
                            <p class="text-sm">На этот день заказов нет</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar-check text-green-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs md:text-sm font-medium text-gray-500">Заказов на неделе</p>
                                <p class="text-lg md:text-xl font-semibold text-gray-900"><?= count($week_orders) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-truck text-blue-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs md:text-sm font-medium text-gray-500">В работе</p>
                                <p class="text-lg md:text-xl font-semibold text-gray-900">
                                    <?= count(array_filter($week_orders, fn($o) => $o['status'] === 'in_progress')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs md:text-sm font-medium text-gray-500">Завершено</p>
                                <p class="text-lg md:text-xl font-semibold text-gray-900">
                                    <?= count(array_filter($week_orders, fn($o) => $o['status'] === 'completed')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tenge text-purple-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs md:text-sm font-medium text-gray-500">Общая стоимость</p>
                                <p class="text-sm md:text-lg font-semibold text-gray-900">
                                    <?= number_format(array_sum(array_column($week_orders, 'shipping_cost')), 0, ',', ' ') ?> ₸
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="order-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Детали заказа</h3>
                    <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600 p-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="order-details">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showOrderDetails(orderId) {
            // In a real implementation, this would fetch order details via AJAX
            document.getElementById('order-details').innerHTML = 
                '<p class="text-gray-600">Загрузка деталей заказа #' + orderId + '...</p>';
            document.getElementById('order-modal').classList.remove('hidden');
        }

        function closeOrderModal() {
            document.getElementById('order-modal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('order-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>