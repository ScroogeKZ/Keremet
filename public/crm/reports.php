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
                        <a href="/crm/reports.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
            <div class="border-4 border-dashed border-gray-200 rounded-lg p-6">
                
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Отчеты</h1>
                    <p class="mt-2 text-gray-600">Детальная отчетность по заказам и операциям</p>
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

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-box text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Всего заказов</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= $total_orders ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Завершено</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= $completed_orders ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">В работе</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= $in_progress_orders ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-ruble-sign text-purple-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Общая стоимость</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= number_format($total_cost, 0, ',', ' ') ?> ₸</p>
                            </div>
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