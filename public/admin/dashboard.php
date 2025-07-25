<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Models\User;
use App\Auth;
use App\TelegramService;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$userModel = new User();
$telegramService = new TelegramService();

// Основная статистика
$stats = [
    'total_orders' => $orderModel->getCount(),
    'astana_orders' => $orderModel->getCount(['order_type' => 'astana']),
    'regional_orders' => $orderModel->getCount(['order_type' => 'regional']),
    'new_orders' => $orderModel->getCount(['status' => 'new']),
    'processing_orders' => $orderModel->getCount(['status' => 'processing']),
    'completed_orders' => $orderModel->getCount(['status' => 'completed'])
];

// Получаем данные для графиков цен
try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Статистика по ценам
    $priceStats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN shipping_cost IS NOT NULL THEN 1 END) as orders_with_cost,
            COUNT(*) as total_orders,
            AVG(shipping_cost) as avg_cost,
            MIN(shipping_cost) as min_cost,
            MAX(shipping_cost) as max_cost,
            SUM(shipping_cost) as total_costs
        FROM shipment_orders
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Распределение цен по диапазонам
    $priceRanges = $pdo->query("
        SELECT 
            CASE 
                WHEN shipping_cost IS NULL THEN 'Не указана'
                WHEN shipping_cost < 5000 THEN 'До 5000 ₸'
                WHEN shipping_cost < 10000 THEN '5000-10000 ₸'
                WHEN shipping_cost < 20000 THEN '10000-20000 ₸'
                WHEN shipping_cost < 50000 THEN '20000-50000 ₸'
                ELSE 'Свыше 50000 ₸'
            END as price_range,
            COUNT(*) as count
        FROM shipment_orders
        GROUP BY price_range
        ORDER BY 
            CASE 
                WHEN shipping_cost IS NULL THEN 0
                WHEN shipping_cost < 5000 THEN 1
                WHEN shipping_cost < 10000 THEN 2
                WHEN shipping_cost < 20000 THEN 3
                WHEN shipping_cost < 50000 THEN 4
                ELSE 5
            END
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Средние цены по типам заказов
    $avgPricesByType = $pdo->query("
        SELECT 
            order_type,
            AVG(shipping_cost) as avg_cost,
            COUNT(CASE WHEN shipping_cost IS NOT NULL THEN 1 END) as count
        FROM shipment_orders
        WHERE shipping_cost IS NOT NULL
        GROUP BY order_type
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Затраты по дням (последние 7 дней)
    $dailyCosts = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            SUM(shipping_cost) as costs,
            COUNT(*) as orders_count
        FROM shipment_orders
        WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $priceStats = ['orders_with_cost' => 0, 'total_orders' => 0, 'avg_cost' => 0, 'min_cost' => 0, 'max_cost' => 0, 'total_costs' => 0];
    $priceRanges = [];
    $avgPricesByType = [];
    $dailyCosts = [];
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд - Хром-KZ Логистика</title>
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
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Дашборд</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-sm text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/cost_calculator.php" class="text-sm text-gray-600 hover:text-gray-900">Калькулятор</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <a href="/admin/search.php" class="text-sm text-gray-600 hover:text-gray-900">Поиск</a>
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <h1 class="text-xl font-medium text-gray-900 mb-6">Обзор</h1>

        <!-- Основные метрики -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Всего заказов</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_orders']; ?></div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Новые</div>
                <div class="text-2xl font-semibold text-yellow-600"><?php echo $stats['new_orders']; ?></div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">В работе</div>
                <div class="text-2xl font-semibold text-blue-600"><?php echo $stats['processing_orders']; ?></div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Завершенные</div>
                <div class="text-2xl font-semibold text-green-600"><?php echo $stats['completed_orders']; ?></div>
            </div>
        </div>

        <!-- Статистика по ценам -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Общие затраты</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($priceStats['total_costs'], 0, ',', ' '); ?> ₸</div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Средние затраты</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($priceStats['avg_cost'], 0, ',', ' '); ?> ₸</div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Мин. затраты</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($priceStats['min_cost'], 0, ',', ' '); ?> ₸</div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Макс. затраты</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($priceStats['max_cost'], 0, ',', ' '); ?> ₸</div>
            </div>
        </div>

        <!-- Графики -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Распределение затрат -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Распределение по затратам</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="priceDistributionChart"></canvas>
                </div>
            </div>

            <!-- Затраты по дням -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Затраты за 7 дней</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="dailyCostsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Дополнительные графики -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Средние затраты по типам -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Средние затраты по типам</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="avgPriceByTypeChart"></canvas>
                </div>
            </div>

            <!-- Статистика по типам заказов -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Типы заказов</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Астана</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo $stats['astana_orders']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Межгород</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo $stats['regional_orders']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">С указанными затратами</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo $priceStats['orders_with_cost']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Конфигурация Chart.js
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6B7280';

    // Распределение цен
    const priceDistributionCtx = document.getElementById('priceDistributionChart').getContext('2d');
    new Chart(priceDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($priceRanges, 'price_range')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($priceRanges, 'count')); ?>,
                backgroundColor: [
                    '#E5E7EB',
                    '#FEF3C7', 
                    '#DBEAFE',
                    '#D1FAE5',
                    '#FEE2E2',
                    '#EDE9FE'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // Затраты по дням
    const dailyCostsCtx = document.getElementById('dailyCostsChart').getContext('2d');
    new Chart(dailyCostsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($dailyCosts, 'date')); ?>,
            datasets: [{
                label: 'Затраты',
                data: <?php echo json_encode(array_column($dailyCosts, 'costs')); ?>,
                borderColor: '#374151',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.1,
                pointRadius: 4,
                pointBackgroundColor: '#374151'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F3F4F6'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' ₸';
                        }
                    }
                },
                x: {
                    grid: {
                        color: '#F3F4F6'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Средние затраты по типам
    const avgPriceByTypeCtx = document.getElementById('avgPriceByTypeChart').getContext('2d');
    new Chart(avgPriceByTypeCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($item) { 
                return $item['order_type'] === 'astana' ? 'Астана' : 'Межгород'; 
            }, $avgPricesByType)); ?>,
            datasets: [{
                label: 'Средние затраты',
                data: <?php echo json_encode(array_column($avgPricesByType, 'avg_cost')); ?>,
                backgroundColor: '#9CA3AF',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F3F4F6'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' ₸';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>