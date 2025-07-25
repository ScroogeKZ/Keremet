<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';
require_once '../../src/Auth.php';

use App\Auth;

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);

if (!$auth->isLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Получаем параметры фильтрации
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Начало месяца
$dateTo = $_GET['date_to'] ?? date('Y-m-t'); // Конец месяца
$orderType = $_GET['order_type'] ?? '';

// Базовый запрос для заказов
$whereClause = "WHERE created_at BETWEEN :date_from AND :date_to";
$params = [
    'date_from' => $dateFrom . ' 00:00:00',
    'date_to' => $dateTo . ' 23:59:59'
];

if ($orderType) {
    $whereClause .= " AND order_type = :order_type";
    $params['order_type'] = $orderType;
}

// Основная статистика
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'new' THEN 1 END) as new_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN order_type = 'astana' THEN 1 END) as astana_orders,
        COUNT(CASE WHEN order_type = 'regional' THEN 1 END) as regional_orders,
        AVG(CAST(shipping_cost as DECIMAL)) as avg_cost,
        SUM(CAST(shipping_cost as DECIMAL)) as total_costs,
        MIN(CAST(shipping_cost as DECIMAL)) as min_cost,
        MAX(CAST(shipping_cost as DECIMAL)) as max_cost
    FROM shipment_orders $whereClause
";

$stmt = $pdo->prepare($statsQuery);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Статистика по дням
$dailyStatsQuery = "
    SELECT 
        DATE(created_at) as order_date,
        COUNT(*) as orders_count,
        SUM(CAST(shipping_cost as DECIMAL)) as daily_costs
    FROM shipment_orders $whereClause
    GROUP BY DATE(created_at)
    ORDER BY order_date
";

$stmt = $pdo->prepare($dailyStatsQuery);
$stmt->execute($params);
$dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Топ типы грузов
$cargoStatsQuery = "
    SELECT 
        cargo_type,
        COUNT(*) as count,
        AVG(CAST(shipping_cost as DECIMAL)) as avg_cost
    FROM shipment_orders $whereClause AND cargo_type IS NOT NULL AND cargo_type != ''
    GROUP BY cargo_type
    ORDER BY count DESC
    LIMIT 10
";

$stmt = $pdo->prepare($cargoStatsQuery);
$stmt->execute($params);
$cargoStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Производительность по статусам
$statusEfficiencyQuery = "
    SELECT 
        status,
        COUNT(*) as count,
        AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/3600) as avg_processing_hours
    FROM shipment_orders $whereClause
    GROUP BY status
";

$stmt = $pdo->prepare($statusEfficiencyQuery);
$stmt->execute($params);
$statusEfficiency = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Региональная статистика
$regionalStatsQuery = "
    SELECT 
        destination_city,
        COUNT(*) as orders_count,
        AVG(CAST(shipping_cost as DECIMAL)) as avg_cost,
        SUM(CAST(shipping_cost as DECIMAL)) as total_costs
    FROM shipment_orders 
    WHERE order_type = 'regional' AND created_at BETWEEN :date_from AND :date_to
    AND destination_city IS NOT NULL AND destination_city != ''
    GROUP BY destination_city
    ORDER BY orders_count DESC
    LIMIT 10
";

$stmt = $pdo->prepare($regionalStatsQuery);
$stmt->execute($params);
$regionalStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - Хром-KZ Логистика</title>
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
                        <h1 class="text-lg font-medium text-gray-900">Отчеты</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
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
        <!-- Фильтры периода -->
        <div class="bg-white border border-gray-200 mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-medium text-gray-900">Период отчета</h2>
            </div>
            <div class="p-4">
                <form method="GET" class="flex items-end space-x-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">От даты</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                               class="text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">До даты</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                               class="text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Тип заказа</label>
                        <select name="order_type" class="text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                            <option value="">Все типы</option>
                            <option value="astana" <?php echo $orderType === 'astana' ? 'selected' : ''; ?>>Астана</option>
                            <option value="regional" <?php echo $orderType === 'regional' ? 'selected' : ''; ?>>Межгород</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-900 text-white text-sm px-4 py-1.5 hover:bg-gray-800">
                        Применить
                    </button>
                    <button type="button" onclick="exportReport()" class="text-gray-700 border border-gray-300 text-sm px-4 py-1.5 hover:border-gray-400">
                        Экспорт Excel
                    </button>
                    <button type="button" onclick="exportPDF()" class="text-gray-700 border border-gray-300 text-sm px-4 py-1.5 hover:border-gray-400">
                        Экспорт PDF
                    </button>
                </form>
            </div>
        </div>

        <!-- KPI Метрики -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Общие затраты на логистику</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_costs'] ?? 0, 0, ',', ' '); ?> ₸</div>
                <div class="text-xs text-gray-500">За выбранный период</div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Средние затраты на доставку</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['avg_cost'] ?? 0, 0, ',', ' '); ?> ₸</div>
                <div class="text-xs text-gray-500">На один заказ</div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Всего заказов</div>
                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_orders']; ?></div>
                <div class="text-xs text-gray-500">Завершено: <?php echo $stats['completed_orders']; ?></div>
            </div>
            
            <div class="bg-white border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 mb-1">Коэффициент завершения</div>
                <div class="text-2xl font-semibold text-gray-900">
                    <?php echo $stats['total_orders'] > 0 ? round(($stats['completed_orders'] / $stats['total_orders']) * 100, 1) : 0; ?>%
                </div>
                <div class="text-xs text-gray-500">Процент завершенных заказов</div>
            </div>
        </div>

        <!-- Детальная аналитика -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- График затрат по дням -->
            <div class="bg-white border border-gray-200 p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Затраты на логистику по дням</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="costsChart"></canvas>
                </div>
            </div>

            <!-- Распределение заказов по статусам -->
            <div class="bg-white border border-gray-200 p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Распределение по статусам</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Таблицы с детальной информацией -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Топ типы грузов -->
            <div class="bg-white border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Популярные типы грузов</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Тип груза</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Количество</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Средняя стоимость</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($cargoStats as $cargo): ?>
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($cargo['cargo_type']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500"><?php echo $cargo['count']; ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500"><?php echo number_format($cargo['avg_cost'] ?? 0, 0, ',', ' '); ?> ₸</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Региональная статистика -->
            <div class="bg-white border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Популярные направления</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Город</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Заказов</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Затраты</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($regionalStats as $region): ?>
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($region['destination_city']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500"><?php echo $region['orders_count']; ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500"><?php echo number_format($region['total_costs'] ?? 0, 0, ',', ' '); ?> ₸</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Сводка затрат отдела логистики -->
        <div class="bg-white border border-gray-200 p-4">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Сводка затрат отдела логистики</h3>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['total_costs'] ?? 0, 0, ',', ' '); ?> ₸</div>
                    <div class="text-xs text-gray-500">Общие затраты</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['avg_cost'] ?? 0, 0, ',', ' '); ?> ₸</div>
                    <div class="text-xs text-gray-500">Средняя доставка</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['min_cost'] ?? 0, 0, ',', ' '); ?> ₸</div>
                    <div class="text-xs text-gray-500">Минимум</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['max_cost'] ?? 0, 0, ',', ' '); ?> ₸</div>
                    <div class="text-xs text-gray-500">Максимум</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo $stats['astana_orders']; ?></div>
                    <div class="text-xs text-gray-500">Заказы Астана</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900"><?php echo $stats['regional_orders']; ?></div>
                    <div class="text-xs text-gray-500">Межгород</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Конфигурация Chart.js
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#6B7280';

        // График затрат по дням
        const costsData = <?php echo json_encode($dailyStats); ?>;
        const costsLabels = costsData.map(item => new Date(item.order_date).toLocaleDateString('ru-RU'));
        const costsValues = costsData.map(item => parseFloat(item.daily_costs) || 0);

        new Chart(document.getElementById('costsChart'), {
            type: 'line',
            data: {
                labels: costsLabels,
                datasets: [{
                    label: 'Затраты на логистику (₸)',
                    data: costsValues,
                    borderColor: '#374151',
                    backgroundColor: 'rgba(55, 65, 81, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        }
                    },
                    x: {
                        grid: {
                            color: '#F3F4F6'
                        }
                    }
                }
            }
        });

        // График распределения по статусам
        const statusData = [
            <?php echo $stats['new_orders']; ?>,
            <?php echo $stats['processing_orders']; ?>,
            <?php echo $stats['completed_orders']; ?>
        ];

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Новые', 'В обработке', 'Завершенные'],
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#EF4444', '#F59E0B', '#10B981'],
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
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Функция экспорта отчета
        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            params.set('action', 'export_report');
            window.open('/admin/export.php?' + params.toString(), '_blank');
        }
        
        // Функция экспорта в PDF
        function exportPDF() {
            const params = new URLSearchParams(window.location.search);
            window.open('/admin/export_pdf.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>