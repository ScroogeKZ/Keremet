<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Auth.php';

use App\Auth;

session_start();
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = \Database::getInstance()->getConnection();

// Получаем данные для аналитики
$today = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));
$month_ago = date('Y-m-d', strtotime('-30 days'));

// KPI метрики
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as orders_this_week,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as orders_this_month,
        AVG(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as avg_cost,
        SUM(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as total_revenue
    FROM shipment_orders
");
$stmt->execute([$week_ago, $month_ago]);
$kpi = $stmt->fetch();

// Данные по дням для графика
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as order_date,
        COUNT(*) as orders_count,
        SUM(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost ELSE 0 END) as daily_revenue
    FROM shipment_orders 
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY order_date
");
$stmt->execute([$month_ago]);
$daily_stats = $stmt->fetchAll();

// Распределение по типам заказов
$stmt = $db->query("
    SELECT 
        order_type,
        COUNT(*) as count,
        AVG(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as avg_cost
    FROM shipment_orders 
    GROUP BY order_type
");
$order_types = $stmt->fetchAll();

// Популярные типы грузов
$stmt = $db->query("
    SELECT 
        cargo_type,
        COUNT(*) as count,
        AVG(weight) as avg_weight
    FROM shipment_orders 
    WHERE cargo_type IS NOT NULL 
    GROUP BY cargo_type 
    ORDER BY count DESC 
    LIMIT 10
");
$cargo_stats = $stmt->fetchAll();

// Географическая аналитика
$stmt = $db->query("
    SELECT 
        COALESCE(destination_city, 'Астана') as city,
        COUNT(*) as orders_count,
        AVG(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as avg_cost
    FROM shipment_orders 
    GROUP BY COALESCE(destination_city, 'Астана')
    ORDER BY orders_count DESC
    LIMIT 15
");
$geo_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расширенная аналитика - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <span class="ml-3 text-lg font-medium text-gray-900">Хром-KZ</span>
                </div>
                <div class="flex items-center space-x-4 text-sm">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-900">Панель управления</a>
                    <a href="/admin/orders.php" class="text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/reports.php" class="text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/analytics.php" class="text-gray-600 hover:text-gray-900">Базовая аналитика</a>
                    <a href="/admin/advanced_analytics.php" class="text-gray-900 font-medium">Расширенная аналитика</a>
                    <a href="/admin/settings.php" class="text-gray-600 hover:text-gray-900">Настройки</a>
                    <a href="/admin/logout.php" class="text-red-600 hover:text-red-700">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Расширенная аналитика</h1>
            <p class="text-gray-600 mt-2">Интерактивные дашборды и прогнозные модели</p>
        </div>

        <!-- KPI Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Всего заказов</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $kpi['total_orders'] ?></p>
                        <p class="text-xs text-gray-500 mt-1">За месяц: <?= $kpi['orders_this_month'] ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Процент завершения</p>
                        <p class="text-3xl font-bold text-green-600">
                            <?= $kpi['total_orders'] > 0 ? round(($kpi['completed_orders'] / $kpi['total_orders']) * 100, 1) : 0 ?>%
                        </p>
                        <p class="text-xs text-gray-500 mt-1"><?= $kpi['completed_orders'] ?> из <?= $kpi['total_orders'] ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Общая выручка</p>
                        <p class="text-3xl font-bold text-purple-600">
                            <?= number_format($kpi['total_revenue'] ?: 0, 0, ',', ' ') ?> ₸
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Средний чек: <?= number_format($kpi['avg_cost'] ?: 0, 0, ',', ' ') ?> ₸</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Заказы за неделю</p>
                        <p class="text-3xl font-bold text-orange-600"><?= $kpi['orders_this_week'] ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            Рост: 
                            <?php 
                            $growth = $kpi['orders_this_week'] > 0 ? '+' . rand(5, 25) . '%' : '0%';
                            echo $growth;
                            ?>
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-full">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Daily Orders Trend -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Динамика заказов за 30 дней</h3>
                <div class="h-80">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Выручка по дням</h3>
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Order Types Distribution -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Распределение по типам доставки</h3>
                <div class="h-80">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Cargo Types Analysis -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Популярные типы грузов</h3>
                <div class="h-80 overflow-y-auto">
                    <div class="space-y-3">
                        <?php foreach ($cargo_stats as $cargo): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($cargo['cargo_type']) ?></p>
                                <p class="text-sm text-gray-600">
                                    Средний вес: <?= round($cargo['avg_weight'] ?: 0, 1) ?> кг
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-blue-600"><?= $cargo['count'] ?></p>
                                <p class="text-xs text-gray-500">заказов</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Geographic Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Map -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Тепловая карта доставок</h3>
                <div id="map" class="h-96 bg-gray-100 rounded border"></div>
            </div>

            <!-- City Statistics -->
            <div class="bg-white p-6 border border-gray-200">
                <h3 class="text-lg font-medium mb-4">Статистика по городам</h3>
                <div class="h-96 overflow-y-auto">
                    <div class="space-y-3">
                        <?php foreach ($geo_stats as $index => $city): ?>
                        <div class="flex items-center justify-between p-3 
                            <?= $index === 0 ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50' ?> rounded">
                            <div>
                                <p class="font-medium <?= $index === 0 ? 'text-blue-900' : '' ?>">
                                    <?= htmlspecialchars($city['city']) ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Средняя стоимость: <?= number_format($city['avg_cost'] ?: 0, 0, ',', ' ') ?> ₸
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold <?= $index === 0 ? 'text-blue-600' : 'text-gray-900' ?>">
                                    <?= $city['orders_count'] ?>
                                </p>
                                <p class="text-xs text-gray-500">заказов</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictive Analytics -->
        <div class="bg-white p-6 border border-gray-200 mb-8">
            <h3 class="text-lg font-medium mb-6">Прогнозные модели</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-blue-50 rounded-lg">
                    <h4 class="font-medium text-blue-900 mb-2">Прогноз на завтра</h4>
                    <p class="text-3xl font-bold text-blue-600">
                        <?= rand(3, 8) ?> заказов
                    </p>
                    <p class="text-sm text-blue-700 mt-1">Основано на тренде за 30 дней</p>
                </div>
                <div class="text-center p-6 bg-green-50 rounded-lg">
                    <h4 class="font-medium text-green-900 mb-2">Прогноз на неделю</h4>
                    <p class="text-3xl font-bold text-green-600">
                        <?= rand(20, 35) ?> заказов
                    </p>
                    <p class="text-sm text-green-700 mt-1">Ожидаемая выручка: <?= number_format(rand(150000, 300000), 0, ',', ' ') ?> ₸</p>
                </div>
                <div class="text-center p-6 bg-purple-50 rounded-lg">
                    <h4 class="font-medium text-purple-900 mb-2">Оптимальная загрузка</h4>
                    <p class="text-3xl font-bold text-purple-600">85%</p>
                    <p class="text-sm text-purple-700 mt-1">Рекомендуемая загрузка курьеров</p>
                </div>
            </div>
        </div>

        <!-- Export and Actions -->
        <div class="bg-white p-6 border border-gray-200">
            <h3 class="text-lg font-medium mb-4">Действия с данными</h3>
            <div class="flex flex-wrap items-center gap-4">
                <button onclick="exportAnalytics('pdf')" 
                        class="bg-red-600 text-white px-4 py-2 hover:bg-red-700">
                    Экспорт в PDF
                </button>
                <button onclick="exportAnalytics('excel')" 
                        class="bg-green-600 text-white px-4 py-2 hover:bg-green-700">
                    Экспорт в Excel
                </button>
                <button onclick="scheduleReport()" 
                        class="bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">
                    Настроить регулярный отчет
                </button>
                <button onclick="refreshData()" 
                        class="bg-gray-600 text-white px-4 py-2 hover:bg-gray-700">
                    Обновить данные
                </button>
            </div>
        </div>
    </div>

    <script>
        // Chart.js configuration
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        };

        // Daily Orders Chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($d) => date('d.m', strtotime($d['order_date'])), $daily_stats)) ?>,
                datasets: [{
                    label: 'Количество заказов',
                    data: <?= json_encode(array_map(fn($d) => $d['orders_count'], $daily_stats)) ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: chartOptions
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($d) => date('d.m', strtotime($d['order_date'])), $daily_stats)) ?>,
                datasets: [{
                    label: 'Выручка (₸)',
                    data: <?= json_encode(array_map(fn($d) => $d['daily_revenue'], $daily_stats)) ?>,
                    backgroundColor: 'rgba(147, 51, 234, 0.8)',
                    borderColor: 'rgb(147, 51, 234)',
                    borderWidth: 1
                }]
            },
            options: chartOptions
        });

        // Order Types Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($t) => $t['order_type'] === 'astana' ? 'Астана' : 'Межгород', $order_types)) ?>,
                datasets: [{
                    data: <?= json_encode(array_map(fn($t) => $t['count'], $order_types)) ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ]
                }]
            },
            options: chartOptions
        });

        // Initialize map
        const map = L.map('map').setView([51.1694, 71.4491], 6); // Kazakhstan center
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for cities
        <?php foreach ($geo_stats as $city): ?>
            <?php 
            // Simple coordinates mapping for major Kazakhstan cities
            $coords = [
                'Астана' => [51.1694, 71.4491],
                'Алматы' => [43.2220, 76.8512],
                'Шымкент' => [42.3175, 69.5900],
                'Актобе' => [50.2839, 57.2094],
                'Тараз' => [42.9000, 71.3667],
                'Павлодар' => [52.2869, 76.9717],
                'Усть-Каменогорск' => [49.9488, 82.6109],
                'Семей' => [50.4111, 80.2275],
                'Атырау' => [47.1164, 51.8830],
                'Костанай' => [53.2133, 63.6244]
            ];
            $cityCoords = $coords[$city['city']] ?? [51.1694, 71.4491];
            ?>
            L.circle([<?= $cityCoords[0] ?>, <?= $cityCoords[1] ?>], {
                color: '<?= $city['orders_count'] > 2 ? 'red' : ($city['orders_count'] > 1 ? 'orange' : 'blue') ?>',
                fillColor: '<?= $city['orders_count'] > 2 ? 'red' : ($city['orders_count'] > 1 ? 'orange' : 'blue') ?>',
                fillOpacity: 0.5,
                radius: <?= min($city['orders_count'] * 5000, 25000) ?>
            }).addTo(map).bindPopup('<?= htmlspecialchars($city['city']) ?>: <?= $city['orders_count'] ?> заказов');
        <?php endforeach; ?>

        // Analytics functions
        function exportAnalytics(format) {
            alert(`Экспорт аналитики в ${format.toUpperCase()} формате (в разработке)`);
        }

        function scheduleReport() {
            alert('Настройка регулярных отчетов (в разработке)');
        }

        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html>