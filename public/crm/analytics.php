<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

// Get analytics data
try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Revenue and orders statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
            SUM(COALESCE(shipping_cost, 0)) as total_revenue,
            AVG(COALESCE(shipping_cost, 0)) as avg_order_value
        FROM shipment_orders
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Orders by month (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            TO_CHAR(created_at, 'YYYY-MM') as month,
            COUNT(*) as orders_count,
            SUM(COALESCE(shipping_cost, 0)) as revenue
        FROM shipment_orders
        WHERE created_at >= CURRENT_DATE - INTERVAL '6 months'
        GROUP BY TO_CHAR(created_at, 'YYYY-MM')
        ORDER BY month
    ");
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top destinations
    $stmt = $pdo->query("
        SELECT 
            COALESCE(destination_city, 'Астана') as city,
            COUNT(*) as orders_count,
            SUM(COALESCE(shipping_cost, 0)) as revenue
        FROM shipment_orders
        GROUP BY COALESCE(destination_city, 'Астана')
        ORDER BY orders_count DESC
        LIMIT 10
    ");
    $topDestinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargo types
    $stmt = $pdo->query("
        SELECT 
            cargo_type,
            COUNT(*) as orders_count,
            AVG(COALESCE(shipping_cost, 0)) as avg_price
        FROM shipment_orders
        WHERE cargo_type IS NOT NULL
        GROUP BY cargo_type
        ORDER BY orders_count DESC
        LIMIT 10
    ");
    $cargoTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Ошибка загрузки данных: ' . $e->getMessage();
    $stats = ['total_orders' => 0, 'completed_orders' => 0, 'processing_orders' => 0, 'new_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];
    $monthlyData = [];
    $topDestinations = [];
    $cargoTypes = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика - CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen bg-gray-50" x-data="{ sidebarOpen: true }">
        <!-- Left Sidebar Navigation -->
        <div class="bg-white shadow-xl border-r border-gray-200 transition-all duration-300 ease-in-out" 
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
            <nav class="px-4 py-6 space-y-1">
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
                <a href="/crm/reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-file-alt mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Отчеты</span>
                </a>
                <a href="/crm/analytics.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm">
                    <i class="fas fa-chart-bar mr-3 w-5 text-blue-600"></i>
                    <span class="font-semibold">Аналитика</span>
                </a>
            </nav>
            
            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center">
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
                    <h2 class="text-2xl font-bold text-gray-900">Аналитика и метрики</h2>
                    <p class="text-sm text-gray-600">Детальные показатели эффективности логистики</p>
                </div>
                <div class="flex items-center space-x-4">
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
                        <a href="/crm/analytics.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>Аналитика
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <a href="/crm/logout.php" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>Выход
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Аналитика и отчеты</h2>
            <p class="mt-2 text-gray-600">Подробная аналитика работы логистической компании</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-box text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Всего заказов</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= $stats['total_orders'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Завершено</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= $stats['completed_orders'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-purple-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Общий доход</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['total_revenue'], 0, '.', ' ') ?> ₸</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Средний чек</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['avg_order_value'], 0, '.', ' ') ?> ₸</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Monthly Revenue Chart -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Доходы по месяцам</h3>
                <canvas id="monthlyRevenueChart" width="400" height="200"></canvas>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Распределение заказов по статусам</h3>
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Destinations -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Популярные направления</h3>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <?php foreach ($topDestinations as $destination): ?>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars($destination['city']) ?></dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <div class="flex justify-between">
                                    <span><?= $destination['orders_count'] ?> заказов</span>
                                    <span class="font-medium"><?= number_format($destination['revenue'], 0, '.', ' ') ?> ₸</span>
                                </div>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>

            <!-- Top Cargo Types -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Популярные типы грузов</h3>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <?php foreach ($cargoTypes as $cargo): ?>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars($cargo['cargo_type']) ?></dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <div class="flex justify-between">
                                    <span><?= $cargo['orders_count'] ?> заказов</span>
                                    <span class="font-medium">~<?= number_format($cargo['avg_price'], 0, '.', ' ') ?> ₸</span>
                                </div>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
                datasets: [{
                    label: 'Доход',
                    data: <?= json_encode(array_column($monthlyData, 'revenue')) ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Новые', 'В работе', 'Завершенные'],
                datasets: [{
                    data: [<?= $stats['new_orders'] ?>, <?= $stats['processing_orders'] ?>, <?= $stats['completed_orders'] ?>],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(34, 197, 94)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>