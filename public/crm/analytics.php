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
    
    // Logistics costs and orders statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
            SUM(COALESCE(shipping_cost, 0)) as total_costs,
            AVG(COALESCE(shipping_cost, 0)) as avg_cost_per_order
        FROM shipment_orders
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Orders by month (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            TO_CHAR(created_at, 'YYYY-MM') as month,
            COUNT(*) as orders_count,
            SUM(COALESCE(shipping_cost, 0)) as costs
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
            SUM(COALESCE(shipping_cost, 0)) as costs
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
    $stats = ['total_orders' => 0, 'completed_orders' => 0, 'processing_orders' => 0, 'new_orders' => 0, 'total_costs' => 0, 'avg_cost_per_order' => 0];
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

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
                <a href="/crm/settings.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-cog mr-3 w-5 text-gray-500 group-hover:text-blue-600"></i>
                    <span>Настройки</span>
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
                    <h2 class="text-2xl font-bold text-gray-900">Аналитика логистических расходов</h2>
                    <p class="text-sm text-gray-600">Контроль и анализ затрат отдела логистики</p>
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
            <div class="flex-1 overflow-auto bg-gray-50 p-4 md:p-8">
                <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Статистические карточки -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-box text-blue-600 text-xl md:text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Всего заказов</p>
                                <p class="text-xl md:text-2xl font-semibold text-gray-900"><?= $stats['total_orders'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 text-xl md:text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Завершено</p>
                                <p class="text-xl md:text-2xl font-semibold text-gray-900"><?= $stats['completed_orders'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tenge text-purple-600 text-xl md:text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Расходы на логистику</p>
                                <p class="text-lg md:text-2xl font-semibold text-gray-900"><?= number_format($stats['total_costs'], 0, '.', ' ') ?> ₸</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-6 rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line text-yellow-600 text-xl md:text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Средний расход за заказ</p>
                                <p class="text-lg md:text-2xl font-semibold text-gray-900"><?= number_format($stats['avg_cost_per_order'], 0, '.', ' ') ?> ₸</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Дополнительная аналитика -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
                    <!-- Эффективность доставки -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-shipping-fast text-blue-600 mr-2"></i>
                            Контроль заказов
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Процент завершения</span>
                                <span class="text-sm font-semibold text-green-600">
                                    <?= $stats['total_orders'] > 0 ? round(($stats['completed_orders'] / $stats['total_orders']) * 100, 1) : 0 ?>%
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">В работе</span>
                                <span class="text-sm font-semibold text-yellow-600"><?= $stats['processing_orders'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Новые заказы</span>
                                <span class="text-sm font-semibold text-blue-600"><?= $stats['new_orders'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Популярные направления -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                            Основные направления доставки
                        </h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($topDestinations, 0, 5) as $index => $destination): ?>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full mr-2"><?= $index + 1 ?></span>
                                    <span class="text-sm text-gray-900"><?= htmlspecialchars($destination['city']) ?></span>
                                </div>
                                <span class="text-sm font-semibold text-gray-700"><?= $destination['orders_count'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Финансовая сводка -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 rounded-lg shadow text-white mb-6 md:mb-8">
                    <h3 class="text-lg font-medium mb-4 flex items-center">
                        <i class="fas fa-calculator text-white mr-2"></i>
                        Сводка расходов на логистику
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= number_format($stats['total_costs'], 0, '.', ' ') ?> ₸</div>
                            <div class="text-sm opacity-90">Всего потрачено</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= number_format($stats['avg_cost_per_order'], 0, '.', ' ') ?> ₸</div>
                            <div class="text-sm opacity-90">Средний расход на заказ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold"><?= $stats['total_orders'] ?></div>
                            <div class="text-sm opacity-90">Всего заказов</div>
                        </div>
                    </div>
                </div>

                <!-- Активность по типам грузов -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-boxes text-green-600 mr-2"></i>
                        Типы грузов
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach (array_slice($cargoTypes, 0, 6) as $cargo): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cargo['cargo_type']) ?></span>
                                <div class="text-xs text-gray-500"><?= $cargo['orders_count'] ?> заказов</div>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">~<?= number_format($cargo['avg_price'], 0, '.', ' ') ?> ₸</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>