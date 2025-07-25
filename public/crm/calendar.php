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
                        <a href="/crm/calendar.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Календарь доставок</h1>
                    <p class="mt-2 text-gray-600">Планирование и контроль доставок на неделю</p>
                </div>
                
                <!-- Week Navigation -->
                <div class="flex items-center space-x-4">
                    <?php
                    $prev_week = clone $current_date;
                    $prev_week->modify('-1 week');
                    $next_week = clone $current_date;
                    $next_week->modify('+1 week');
                    ?>
                    <a href="?date=<?= $prev_week->format('Y-m-d') ?>" 
                       class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="text-lg font-medium text-gray-900">
                        <?= $week_days[0]->format('d.m') ?> - <?= $week_days[6]->format('d.m.Y') ?>
                    </span>
                    <a href="?date=<?= $next_week->format('Y-m-d') ?>" 
                       class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
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
                    <div class="bg-white p-3 min-h-32">
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
                            <?php foreach (array_slice($day_orders, 0, 3) as $order): ?>
                            <div class="bg-gray-50 border border-gray-200 rounded p-2 text-xs cursor-pointer hover:bg-gray-100"
                                 onclick="showOrderDetails(<?= $order['id'] ?>)">
                                <div class="font-medium text-gray-900 truncate">
                                    #<?= $order['id'] ?> - <?= htmlspecialchars($order['cargo_type']) ?>
                                </div>
                                <div class="text-gray-500 truncate">
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
                            
                            <?php if ($order_count > 3): ?>
                            <div class="text-center">
                                <button class="text-blue-600 hover:text-blue-800 text-xs">
                                    +<?= $order_count - 3 ?> еще
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Заказов на неделе</p>
                            <p class="text-lg font-semibold text-gray-900"><?= count($week_orders) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-truck text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">В работе</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= count(array_filter($week_orders, fn($o) => $o['status'] === 'in_progress')) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Завершено</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= count(array_filter($week_orders, fn($o) => $o['status'] === 'completed')) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ruble-sign text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">Общая стоимость</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= number_format(array_sum(array_column($week_orders, 'shipping_cost')), 0, ',', ' ') ?> ₸
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="order-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Детали заказа</h3>
                    <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600">
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