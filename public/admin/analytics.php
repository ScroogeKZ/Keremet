<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Детальная аналитика по логистике
    $analytics = [];
    
    // 1. Топ направлений по прибыльности
    $stmt = $pdo->query("
        SELECT destination_city, 
               COUNT(*) as order_count,
               SUM(COALESCE(shipping_cost, 0)) as total_revenue,
               AVG(COALESCE(shipping_cost, 0)) as avg_revenue,
               MIN(COALESCE(shipping_cost, 0)) as min_cost,
               MAX(COALESCE(shipping_cost, 0)) as max_cost
        FROM shipment_orders 
        WHERE destination_city IS NOT NULL 
        GROUP BY destination_city 
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $analytics['top_destinations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Анализ по типам грузов
    $stmt = $pdo->query("
        SELECT cargo_type,
               COUNT(*) as order_count,
               SUM(COALESCE(shipping_cost, 0)) as total_revenue,
               AVG(COALESCE(shipping_cost, 0)) as avg_revenue
        FROM shipment_orders 
        WHERE cargo_type IS NOT NULL 
        GROUP BY cargo_type 
        ORDER BY order_count DESC
        LIMIT 15
    ");
    $analytics['cargo_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Временной анализ (по дням недели)
    $stmt = $pdo->query("
        SELECT EXTRACT(DOW FROM created_at) as day_of_week,
               COUNT(*) as order_count,
               SUM(COALESCE(shipping_cost, 0)) as total_revenue
        FROM shipment_orders 
        GROUP BY EXTRACT(DOW FROM created_at)
        ORDER BY day_of_week
    ");
    $weekdays = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    $weekly_data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $weekly_data[] = [
            'day' => $weekdays[$row['day_of_week']],
            'order_count' => $row['order_count'],
            'total_revenue' => $row['total_revenue']
        ];
    }
    $analytics['weekly_pattern'] = $weekly_data;
    
    // 4. Конверсия по статусам
    $stmt = $pdo->query("
        SELECT status,
               COUNT(*) as count,
               ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
        FROM shipment_orders 
        GROUP BY status
        ORDER BY count DESC
    ");
    $analytics['status_conversion'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Средняя стоимость по месяцам
    $stmt = $pdo->query("
        SELECT DATE_TRUNC('month', created_at) as month,
               COUNT(*) as order_count,
               SUM(COALESCE(shipping_cost, 0)) as total_revenue,
               AVG(COALESCE(shipping_cost, 0)) as avg_cost
        FROM shipment_orders 
        WHERE created_at >= CURRENT_DATE - INTERVAL '12 months'
        GROUP BY DATE_TRUNC('month', created_at)
        ORDER BY month DESC
    ");
    $analytics['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. KPI метрики
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as orders_last_30_days,
            SUM(COALESCE(shipping_cost, 0)) as total_revenue,
            AVG(COALESCE(shipping_cost, 0)) as avg_order_value,
            COUNT(DISTINCT destination_city) as unique_destinations
        FROM shipment_orders
    ");
    $analytics['kpi'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $analytics = ['error' => $e->getMessage()];
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика логистики - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Аналитика логистики</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <?php if (isset($analytics['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                Ошибка загрузки данных: <?php echo htmlspecialchars($analytics['error']); ?>
            </div>
        <?php else: ?>
            <!-- KPI Метрики -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-blue-600">
                        <?php echo number_format($analytics['kpi']['total_orders'] ?? 0); ?>
                    </div>
                    <div class="text-sm text-gray-500">Всего заказов</div>
                    <div class="text-xs text-gray-400 mt-1">
                        За 30 дней: <?php echo $analytics['kpi']['orders_last_30_days'] ?? 0; ?>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-green-600">
                        <?php echo number_format($analytics['kpi']['total_revenue'] ?? 0, 0, ',', ' '); ?> ₸
                    </div>
                    <div class="text-sm text-gray-500">Общая выручка</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Средний чек: <?php echo number_format($analytics['kpi']['avg_order_value'] ?? 0, 0, ',', ' '); ?> ₸
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-purple-600">
                        <?php 
                        $completion_rate = 0;
                        if ($analytics['kpi']['total_orders'] > 0) {
                            $completion_rate = round(($analytics['kpi']['completed_orders'] / $analytics['kpi']['total_orders']) * 100, 1);
                        }
                        echo $completion_rate;
                        ?>%
                    </div>
                    <div class="text-sm text-gray-500">Процент завершения</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Завершено: <?php echo $analytics['kpi']['completed_orders'] ?? 0; ?>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-orange-600">
                        <?php echo $analytics['kpi']['unique_destinations'] ?? 0; ?>
                    </div>
                    <div class="text-sm text-gray-500">Направлений</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Уникальных городов
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Топ направлений -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Топ направлений по выручке</h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice($analytics['top_destinations'], 0, 8) as $index => $dest): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-xs font-medium">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($dest['destination_city'] ?: 'Не указан'); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $dest['order_count']; ?> заказов
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium text-gray-900">
                                        <?php echo number_format($dest['total_revenue'], 0, ',', ' '); ?> ₸
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        средний: <?php echo number_format($dest['avg_revenue'], 0, ',', ' '); ?> ₸
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- График по дням недели -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Активность по дням недели</h3>
                    <canvas id="weeklyChart" width="400" height="250"></canvas>
                </div>

                <!-- Типы грузов -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Популярные типы грузов</h3>
                    <div class="space-y-2">
                        <?php foreach (array_slice($analytics['cargo_types'], 0, 10) as $cargo): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($cargo['cargo_type'] ?: 'Не указан'); ?>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <?php 
                                        $max_orders = $analytics['cargo_types'][0]['order_count'] ?? 1;
                                        $percentage = ($cargo['order_count'] / $max_orders) * 100;
                                        ?>
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                                <div class="ml-4 text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo $cargo['order_count']; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo number_format($cargo['avg_revenue'], 0, ',', ' '); ?> ₸
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Конверсия по статусам -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Распределение по статусам</h3>
                    <canvas id="statusChart" width="400" height="250"></canvas>
                    
                    <div class="mt-4 space-y-2">
                        <?php foreach ($analytics['status_conversion'] as $status): 
                            $statusText = match($status['status']) {
                                'new' => 'Новые',
                                'processing' => 'В обработке',
                                'completed' => 'Завершенные',
                                default => $status['status']
                            };
                            $color = match($status['status']) {
                                'new' => 'text-red-600',
                                'processing' => 'text-yellow-600',
                                'completed' => 'text-green-600',
                                default => 'text-gray-600'
                            };
                        ?>
                            <div class="flex justify-between items-center">
                                <span class="<?php echo $color; ?> font-medium"><?php echo $statusText; ?></span>
                                <span class="text-gray-900"><?php echo $status['count']; ?> (<?php echo $status['percentage']; ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Месячные тренды -->
            <div class="mt-6 bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Тренды по месяцам</h3>
                <canvas id="monthlyChart" width="800" height="300"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // График по дням недели
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($analytics['weekly_pattern'], 'day')); ?>,
                datasets: [{
                    label: 'Количество заказов',
                    data: <?php echo json_encode(array_column($analytics['weekly_pattern'], 'order_count')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
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

        // График статусов
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($s) {
                    return match($s['status']) {
                        'new' => 'Новые',
                        'processing' => 'В обработке',
                        'completed' => 'Завершенные',
                        default => $s['status']
                    };
                }, $analytics['status_conversion'])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['status_conversion'], 'count')); ?>,
                    backgroundColor: [
                        'rgba(220, 38, 38, 0.8)',
                        'rgba(217, 119, 6, 0.8)',
                        'rgba(5, 150, 105, 0.8)',
                        'rgba(107, 114, 128, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // График месячных трендов
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($m) {
                    return date('M Y', strtotime($m['month']));
                }, array_reverse($analytics['monthly_trends']))); ?>,
                datasets: [
                    {
                        label: 'Количество заказов',
                        data: <?php echo json_encode(array_column(array_reverse($analytics['monthly_trends']), 'order_count')); ?>,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Выручка (тыс. ₸)',
                        data: <?php echo json_encode(array_map(function($m) {
                            return round($m['total_revenue'] / 1000);
                        }, array_reverse($analytics['monthly_trends']))); ?>,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Количество заказов'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Выручка (тыс. ₸)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>