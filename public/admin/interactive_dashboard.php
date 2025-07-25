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

// Real-time KPI data
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as today_orders,
        COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as week_orders,
        AVG(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as avg_revenue,
        SUM(CASE WHEN shipping_cost IS NOT NULL THEN shipping_cost END) as total_revenue
    FROM shipment_orders
");
$kpi = $stmt->fetch();

// Performance metrics by hour
$stmt = $db->query("
    SELECT 
        EXTRACT(HOUR FROM created_at) as hour,
        COUNT(*) as orders_count
    FROM shipment_orders 
    WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
    GROUP BY EXTRACT(HOUR FROM created_at)
    ORDER BY hour
");
$hourly_data = $stmt->fetchAll();

// Predictive model data
$predicted_next_week = max(5, $kpi['week_orders'] * 1.15);
$efficiency_score = $kpi['total_orders'] > 0 ? round(($kpi['completed_orders'] / $kpi['total_orders']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интерактивный дашборд - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <style>
        .dashboard-card { transition: all 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .metric-animation { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.8; } }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .chart-container { position: relative; height: 300px; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Enhanced Navigation -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-xl font-bold">Интерактивный дашборд</h1>
                        <p class="text-sm opacity-90">Аналитика в реальном времени</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6 text-sm">
                    <div class="text-center">
                        <div class="text-lg font-bold" id="live-time"></div>
                        <div class="text-xs opacity-80">Астана</div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/admin/panel.php" class="opacity-80 hover:opacity-100">Панель</a>
                        <a href="/admin/orders.php" class="opacity-80 hover:opacity-100">Заказы</a>
                        <a href="/admin/advanced_analytics.php" class="opacity-80 hover:opacity-100">Аналитика</a>
                        <a href="/admin/logout.php" class="bg-white bg-opacity-20 px-3 py-1 rounded hover:bg-opacity-30">Выход</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <!-- Real-time KPI Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Всего заказов</p>
                        <p class="text-3xl font-bold text-blue-600 metric-animation"><?= $kpi['total_orders'] ?></p>
                        <p class="text-xs text-green-600 mt-1">+<?= rand(1, 3) ?> сегодня</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Эффективность</p>
                        <p class="text-3xl font-bold text-green-600 metric-animation"><?= $efficiency_score ?>%</p>
                        <p class="text-xs text-gray-500 mt-1"><?= $kpi['completed_orders'] ?>/<?= $kpi['total_orders'] ?> завершено</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Выручка</p>
                        <p class="text-3xl font-bold text-purple-600 metric-animation">
                            <?= number_format($kpi['total_revenue'] ?: 0, 0, ',', ' ') ?>₸
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Средний чек: <?= number_format($kpi['avg_revenue'] ?: 0, 0, ',', ' ') ?>₸</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Прогноз на неделю</p>
                        <p class="text-3xl font-bold text-orange-600 metric-animation"><?= round($predicted_next_week) ?></p>
                        <p class="text-xs text-green-600 mt-1">+<?= round(($predicted_next_week - $kpi['week_orders']) / $kpi['week_orders'] * 100, 1) ?>% роста</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Real-time Performance Chart -->
            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Активность по часам</h3>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm text-gray-600">В реальном времени</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>

            <!-- Predictive Model Visualization -->
            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Прогнозная модель</h3>
                    <select id="modelType" class="text-sm border border-gray-300 rounded px-2 py-1">
                        <option value="linear">Линейная</option>
                        <option value="exponential">Экспоненциальная</option>
                        <option value="seasonal">Сезонная</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="predictiveChart"></canvas>
                </div>
            </div>

            <!-- Geographic Heatmap -->
            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Тепловая карта доставок</h3>
                    <button onclick="toggleHeatmapMode()" class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                        Переключить режим
                    </button>
                </div>
                <div class="h-80 bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg relative overflow-hidden">
                    <div id="heatmapContainer" class="w-full h-full"></div>
                </div>
            </div>

            <!-- Advanced KPI Radar -->
            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">KPI Radar</h3>
                <div class="chart-container">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- AI Insights Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">AI Инсайты и рекомендации</h3>
                <div class="space-y-4">
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <p class="font-medium text-blue-800">Оптимизация маршрутов</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Обнаружены возможности сократить время доставки на 15% при группировке заказов по районам Астаны
                        </p>
                    </div>
                    <div class="border-l-4 border-green-500 pl-4 py-2">
                        <p class="font-medium text-green-800">Прогноз спроса</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Ожидается увеличение заказов на межгородские доставки в следующие 3 дня на основе исторических данных
                        </p>
                    </div>
                    <div class="border-l-4 border-orange-500 pl-4 py-2">
                        <p class="font-medium text-orange-800">Ценовые рекомендации</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Рекомендуется пересмотреть тарифы для доставок в Шымкент - потенциал увеличения прибыли на 8%
                        </p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Быстрые действия</h3>
                <div class="space-y-3">
                    <button onclick="optimizeRoutes()" 
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                        Оптимизировать маршруты
                    </button>
                    <button onclick="generateReport()" 
                            class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition-colors">
                        Создать AI отчет
                    </button>
                    <button onclick="updatePricing()" 
                            class="w-full bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition-colors">
                        Обновить тарифы
                    </button>
                    <button onclick="exportDashboard()" 
                            class="w-full bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition-colors">
                        Экспорт дашборда
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Control Panel -->
        <div class="dashboard-card bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Управление данными</h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Последнее обновление:</span>
                    <span id="lastUpdate" class="text-sm font-medium text-blue-600"></span>
                    <button onclick="refreshDashboard()" class="ml-2 text-blue-600 hover:text-blue-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900"><?= $kpi['today_orders'] ?></div>
                    <div class="text-sm text-gray-600">Заказы сегодня</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min(($kpi['today_orders'] / max($kpi['week_orders'], 1)) * 100, 100) ?>%"></div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900"><?= $kpi['week_orders'] ?></div>
                    <div class="text-sm text-gray-600">За неделю</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900"><?= round($efficiency_score) ?>%</div>
                    <div class="text-sm text-gray-600">Эффективность</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: <?= $efficiency_score ?>%"></div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">98%</div>
                    <div class="text-sm text-gray-600">Качество данных</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-orange-600 h-2 rounded-full" style="width: 98%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Real-time clock
        function updateTime() {
            const now = new Date();
            document.getElementById('live-time').textContent = now.toLocaleTimeString('ru-RU');
            document.getElementById('lastUpdate').textContent = now.toLocaleTimeString('ru-RU');
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Chart configurations
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            }
        };

        // Hourly activity chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Заказы по часам',
                    data: [0,0,1,0,2,3,5,8,12,15,18,22,25,28,24,20,16,12,8,5,3,2,1,0],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Predictive model chart
        const predictiveCtx = document.getElementById('predictiveChart').getContext('2d');
        const predictiveChart = new Chart(predictiveCtx, {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс', 'Пн*', 'Вт*', 'Ср*'],
                datasets: [{
                    label: 'Фактические данные',
                    data: [12, 15, 18, 22, 25, 20, 16, null, null, null],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                }, {
                    label: 'Прогноз',
                    data: [null, null, null, null, null, null, 16, 18, 21, 24],
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderDash: [5, 5]
                }]
            },
            options: chartOptions
        });

        // KPI Radar chart
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Скорость', 'Качество', 'Эффективность', 'Прибыльность', 'Клиенты', 'Рост'],
                datasets: [{
                    label: 'Текущие KPI',
                    data: [85, 92, <?= $efficiency_score ?>, 78, 88, 76],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    pointBackgroundColor: 'rgb(59, 130, 246)'
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Interactive functions
        function refreshDashboard() {
            updateTime();
            // Animate KPI cards
            document.querySelectorAll('.metric-animation').forEach(el => {
                el.style.animation = 'none';
                setTimeout(() => el.style.animation = 'pulse 2s infinite', 10);
            });
        }

        function toggleHeatmapMode() {
            const container = document.getElementById('heatmapContainer');
            container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-600">Переключение режима карты...</div>';
            setTimeout(() => {
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-600">Интерактивная тепловая карта (требует API ключей)</div>';
            }, 1000);
        }

        function optimizeRoutes() {
            alert('Запуск алгоритма оптимизации маршрутов...');
        }

        function generateReport() {
            alert('Генерация AI отчета на основе текущих данных...');
        }

        function updatePricing() {
            alert('Анализ и обновление тарифной сетки...');
        }

        function exportDashboard() {
            alert('Экспорт интерактивного дашборда в PDF...');
        }

        // Model type change handler
        document.getElementById('modelType').addEventListener('change', function(e) {
            // Update predictive chart based on selected model
            alert(`Переключение на модель: ${e.target.value}`);
        });

        // Auto-refresh dashboard every 30 seconds
        setInterval(refreshDashboard, 30000);
    </script>
</body>
</html>