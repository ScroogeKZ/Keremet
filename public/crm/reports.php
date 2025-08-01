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

// Handle date filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get filtered orders
$filtered_orders = $orders->getFiltered([
    'date_from' => $start_date,
    'date_to' => $end_date
]);

// Generate report data
$total_orders = count($filtered_orders);
$completed_orders = count(array_filter($filtered_orders, fn($o) => $o['status'] === 'completed'));
$pending_orders = count(array_filter($filtered_orders, fn($o) => $o['status'] === 'pending'));
$in_progress_orders = count(array_filter($filtered_orders, fn($o) => $o['status'] === 'in_progress'));

// Calculate total shipping cost
$total_cost = array_sum(array_column($filtered_orders, 'shipping_cost'));

// Group by order type
$astana_orders = count(array_filter($filtered_orders, fn($o) => $o['order_type'] === 'astana'));
$regional_orders = count(array_filter($filtered_orders, fn($o) => $o['order_type'] === 'regional'));

// Calculate previous period for comparison
$prev_start = date('Y-m-d', strtotime($start_date . ' -1 month'));
$prev_end = date('Y-m-d', strtotime($end_date . ' -1 month'));

$prev_orders = $orders->getFiltered([
    'date_from' => $prev_start,
    'date_to' => $prev_end
]);

$prev_total_orders = count($prev_orders);
$prev_total_cost = array_sum(array_column($prev_orders, 'shipping_cost'));
$prev_avg_cost = $prev_total_orders > 0 ? $prev_total_cost / $prev_total_orders : 0;

// Calculate trends
$order_trend = $prev_total_orders > 0 ? round((($total_orders - $prev_total_orders) / $prev_total_orders) * 100, 1) : 0;
$cost_trend = $prev_total_cost > 0 ? round((($total_cost - $prev_total_cost) / $prev_total_cost) * 100, 1) : 0;
$avg_cost = $total_orders > 0 ? $total_cost / $total_orders : 0;
$avg_trend = $prev_avg_cost > 0 ? round((($avg_cost - $prev_avg_cost) / $prev_avg_cost) * 100, 1) : 0;

// Group by cargo types with costs
$cargo_stats = [];
foreach ($filtered_orders as $order) {
    $cargo = $order['cargo_type'] ?: 'Не указано';
    if (!isset($cargo_stats[$cargo])) {
        $cargo_stats[$cargo] = ['count' => 0, 'total_cost' => 0];
    }
    $cargo_stats[$cargo]['count']++;
    $cargo_stats[$cargo]['total_cost'] += floatval($order['shipping_cost']);
}

// Sort by total cost descending
arsort($cargo_stats);

// Group by destinations (cities) with costs
$destination_stats = [];
foreach ($filtered_orders as $order) {
    // Extract city from delivery address or use pickup address
    $address = $order['delivery_address'] ?: $order['pickup_address'];
    $city = 'Не указано';
    
    // Simple city extraction logic
    if (stripos($address, 'Алматы') !== false) $city = 'Алматы';
    elseif (stripos($address, 'Астана') !== false || stripos($address, 'Нур-Султан') !== false) $city = 'Астана';
    elseif (stripos($address, 'Шымкент') !== false) $city = 'Шымкент';
    elseif (stripos($address, 'Караганда') !== false) $city = 'Караганда';
    elseif (stripos($address, 'Актобе') !== false) $city = 'Актобе';
    elseif (preg_match('/([А-Яа-я]+)/u', $address, $matches)) {
        $city = $matches[1];
    }
    
    if (!isset($destination_stats[$city])) {
        $destination_stats[$city] = ['count' => 0, 'total_cost' => 0];
    }
    $destination_stats[$city]['count']++;
    $destination_stats[$city]['total_cost'] += floatval($order['shipping_cost']);
}

// Sort destinations by total cost descending
uasort($destination_stats, function($a, $b) {
    return $b['total_cost'] <=> $a['total_cost'];
});

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Тип заказа', 'Статус', 'Груз', 'Адрес получения', 'Телефон', 'Стоимость', 'Дата создания']);
    
    foreach ($filtered_orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['order_type'] === 'astana' ? 'Астана' : 'Региональный',
            $order['status'],
            $order['cargo_type'],
            $order['pickup_address'],
            $order['contact_phone'],
            $order['shipping_cost'],
            date('d.m.Y H:i', strtotime($order['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - CRM Система</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50" x-data="{ sidebarOpen: true }">
        <!-- Left Sidebar Navigation -->
        <div class="bg-white shadow-xl border-r border-gray-200 transition-all duration-300 ease-in-out w-72 flex flex-col overflow-hidden">
            <!-- Logo/Brand -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-shipping-fast text-white text-sm"></i>
                    </div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">Хром-KZ CRM</h1>
                </div>
                <button class="ml-auto p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-bars text-gray-600"></i>
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
                <a href="/crm/calendar.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-calendar mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Календарь</span>
                </a>
                <a href="/crm/bulk_operations.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-tasks mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Массовые операции</span>
                </a>
                <a href="/crm/reports.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm">
                    <i class="fas fa-file-alt mr-3 w-5 text-blue-600"></i>
                    <span class="font-semibold">Отчеты</span>
                </a>
                <a href="/crm/analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-chart-bar mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Аналитика</span>
                </a>
            </nav>
            
            <!-- User Profile -->
            <div class="mt-auto p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Admin</p>
                        <p class="text-xs text-gray-500">Администратор</p>
                    </div>
                    <a href="/crm/logout.php" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Отчеты по расходам на логистику</h2>
                    <p class="text-sm text-gray-600">Анализ затрат отдела логистики с трендами</p>
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
                
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Отчеты по расходам на логистику</h1>
                    <p class="mt-2 text-gray-600">Анализ затрат отдела логистики с трендами и сравнениями</p>
                </div>

                <!-- Date Filter -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">От даты</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" 
                                   class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">До даты</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" 
                                   class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                            <i class="fas fa-search mr-1"></i>Применить
                        </button>
                        <a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" 
                           class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700">
                            <i class="fas fa-download mr-1"></i>Экспорт CSV
                        </a>
                    </form>
                </div>

                <!-- Summary Cards with Trends -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-box text-blue-600 text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Всего заказов</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?= $total_orders ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs <?= $order_trend >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <i class="fas fa-<?= $order_trend >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= abs($order_trend) ?>%
                                </span>
                                <p class="text-xs text-gray-500">vs пред. период</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-tenge text-purple-600 text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Общие расходы</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?= number_format($total_cost, 0, ',', ' ') ?> ₸</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs <?= $cost_trend >= 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <i class="fas fa-<?= $cost_trend >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= abs($cost_trend) ?>%
                                </span>
                                <p class="text-xs text-gray-500">vs пред. период</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calculator text-green-600 text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Средний расход</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?= number_format($avg_cost, 0, ',', ' ') ?> ₸</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs <?= $avg_trend >= 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <i class="fas fa-<?= $avg_trend >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= abs($avg_trend) ?>%
                                </span>
                                <p class="text-xs text-gray-500">vs пред. период</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-percentage text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Эффективность</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    <?= $total_orders > 0 ? round(($completed_orders / $total_orders) * 100, 1) : 0 ?>%
                                </p>
                                <p class="text-xs text-gray-500">завершенных заказов</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Cargo Types Analysis -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-boxes text-blue-600 mr-2"></i>
                            Расходы по типам грузов
                        </h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($cargo_stats, 0, 6, true) as $cargo => $stats): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cargo) ?></div>
                                    <div class="text-xs text-gray-500"><?= $stats['count'] ?> заказов</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?= number_format($stats['total_cost'], 0, ',', ' ') ?> ₸
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ~<?= number_format($stats['total_cost'] / $stats['count'], 0, ',', ' ') ?> ₸/заказ
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Most Expensive Destinations -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                            Самые дорогие направления
                        </h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($destination_stats, 0, 6, true) as $city => $stats): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($city) ?></div>
                                    <div class="text-xs text-gray-500"><?= $stats['count'] ?> заказов</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?= number_format($stats['total_cost'], 0, ',', ' ') ?> ₸
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ~<?= number_format($stats['total_cost'] / $stats['count'], 0, ',', ' ') ?> ₸/заказ
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Распределение по статусам</h3>
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Типы заказов</h3>
                        <canvas id="typeChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Comparison with Previous Period -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 rounded-lg shadow text-white mb-6">
                    <h3 class="text-lg font-medium mb-4 flex items-center">
                        <i class="fas fa-chart-line text-white mr-2"></i>
                        Сравнение с предыдущим периодом
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= $prev_total_orders ?></div>
                            <div class="text-sm opacity-90">Заказов в пред. периоде</div>
                            <div class="text-xs mt-1">
                                (<?= date('d.m', strtotime($prev_start)) ?> - <?= date('d.m', strtotime($prev_end)) ?>)
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= number_format($prev_total_cost, 0, ',', ' ') ?> ₸</div>
                            <div class="text-sm opacity-90">Расходы в пред. периоде</div>
                            <div class="text-xs mt-1">
                                Тренд: <?= $cost_trend >= 0 ? '+' : '' ?><?= $cost_trend ?>%
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= number_format($prev_avg_cost, 0, ',', ' ') ?> ₸</div>
                            <div class="text-sm opacity-90">Средний расход ранее</div>
                            <div class="text-xs mt-1">
                                Тренд: <?= $avg_trend >= 0 ? '+' : '' ?><?= $avg_trend ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Детальный отчет по заказам</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Груз</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Стоимость</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($filtered_orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $order['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональный' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($order['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($order['cargo_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        <?= htmlspecialchars($order['pickup_address']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= number_format($order['shipping_cost'], 0, ',', ' ') ?> ₸
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Завершено', 'В работе', 'Ожидает'],
                datasets: [{
                    data: [<?= $completed_orders ?>, <?= $in_progress_orders ?>, <?= $pending_orders ?>],
                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: ['Астана', 'Региональные'],
                datasets: [{
                    data: [<?= $astana_orders ?>, <?= $regional_orders ?>],
                    backgroundColor: ['#8B5CF6', '#06B6D4']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>