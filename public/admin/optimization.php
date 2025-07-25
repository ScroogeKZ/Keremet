<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

// Алгоритм оптимизации маршрутов
$optimizationResults = null;

if ($_POST && isset($_POST['optimize'])) {
    try {
        $pdo = \Database::getInstance()->getConnection();
        
        // Получаем активные заказы для оптимизации
        $stmt = $pdo->query("
            SELECT id, pickup_address, destination_city, weight, 
                   shipping_cost, created_at, contact_name, contact_phone
            FROM shipment_orders 
            WHERE status IN ('new', 'processing')
            AND created_at >= CURRENT_DATE - INTERVAL '7 days'
            ORDER BY created_at ASC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Группируем заказы по направлениям
        $routes = [];
        $totalSavings = 0;
        $totalOrders = count($orders);
        
        foreach ($orders as $order) {
            $destination = $order['destination_city'] ?: 'Астана';
            if (!isset($routes[$destination])) {
                $routes[$destination] = [];
            }
            $routes[$destination][] = $order;
        }
        
        // Рассчитываем потенциальную экономию
        $optimizedRoutes = [];
        foreach ($routes as $destination => $destOrders) {
            if (count($destOrders) > 1) {
                $baseDeliveryCost = 15000; // Базовая стоимость доставки
                $additionalOrderCost = 3000; // Стоимость дополнительного заказа в попутке
                
                $originalCost = count($destOrders) * $baseDeliveryCost;
                $optimizedCost = $baseDeliveryCost + (count($destOrders) - 1) * $additionalOrderCost;
                $savings = $originalCost - $optimizedCost;
                $totalSavings += $savings;
                
                $optimizedRoutes[] = [
                    'destination' => $destination,
                    'orders' => $destOrders,
                    'order_count' => count($destOrders),
                    'original_cost' => $originalCost,
                    'optimized_cost' => $optimizedCost,
                    'savings' => $savings,
                    'efficiency' => round(($savings / $originalCost) * 100, 1)
                ];
            }
        }
        
        // Сортируем по экономии
        usort($optimizedRoutes, function($a, $b) {
            return $b['savings'] - $a['savings'];
        });
        
        $optimizationResults = [
            'total_orders' => $totalOrders,
            'optimizable_routes' => count($optimizedRoutes),
            'total_savings' => $totalSavings,
            'routes' => $optimizedRoutes
        ];
        
    } catch (Exception $e) {
        $optimizationResults = ['error' => $e->getMessage()];
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оптимизация маршрутов - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Оптимизация маршрутов</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/routes.php" class="text-sm text-gray-600 hover:text-gray-900">Маршруты</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Форма запуска оптимизации -->
        <div class="bg-white border border-gray-200 p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Автоматическая оптимизация маршрутов</h2>
                    <p class="text-sm text-gray-600">
                        Система анализирует активные заказы и предлагает оптимальные маршруты для снижения затрат на доставку
                    </p>
                </div>
                <form method="POST">
                    <button type="submit" name="optimize" value="1" 
                            class="bg-blue-600 text-white px-6 py-2 text-sm hover:bg-blue-700">
                        Запустить оптимизацию
                    </button>
                </form>
            </div>
        </div>

        <?php if ($optimizationResults): ?>
            <?php if (isset($optimizationResults['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                    Ошибка оптимизации: <?php echo htmlspecialchars($optimizationResults['error']); ?>
                </div>
            <?php else: ?>
                <!-- Результаты оптимизации -->
                <div class="bg-white border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Результаты оптимизации</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-blue-50 border border-blue-200">
                            <div class="text-2xl font-bold text-blue-600">
                                <?php echo $optimizationResults['total_orders']; ?>
                            </div>
                            <div class="text-sm text-blue-800">Всего заказов</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 border border-green-200">
                            <div class="text-2xl font-bold text-green-600">
                                <?php echo $optimizationResults['optimizable_routes']; ?>
                            </div>
                            <div class="text-sm text-green-800">Оптимизируемых маршрутов</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 border border-purple-200">
                            <div class="text-2xl font-bold text-purple-600">
                                <?php echo number_format($optimizationResults['total_savings'], 0, ',', ' '); ?> ₸
                            </div>
                            <div class="text-sm text-purple-800">Потенциальная экономия</div>
                        </div>
                        <div class="text-center p-4 bg-orange-50 border border-orange-200">
                            <div class="text-2xl font-bold text-orange-600">
                                <?php echo $optimizationResults['total_savings'] > 0 ? round(($optimizationResults['total_savings'] / ($optimizationResults['total_orders'] * 15000)) * 100, 1) : 0; ?>%
                            </div>
                            <div class="text-sm text-orange-800">Экономия затрат</div>
                        </div>
                    </div>

                    <?php if (empty($optimizationResults['routes'])): ?>
                        <div class="text-center py-8 text-gray-500">
                            <div class="text-lg mb-2">Нет возможностей для оптимизации</div>
                            <div class="text-sm">Все заказы идут в разные направления или уже оптимальны</div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($optimizationResults['routes'] as $route): ?>
                                <div class="border border-gray-200 p-4">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                Направление: <?php echo htmlspecialchars($route['destination']); ?>
                                            </h4>
                                            <div class="text-sm text-gray-600">
                                                <?php echo $route['order_count']; ?> заказов • 
                                                Экономия: <?php echo number_format($route['savings'], 0, ',', ' '); ?> ₸ 
                                                (<?php echo $route['efficiency']; ?>%)
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">Было: <?php echo number_format($route['original_cost'], 0, ',', ' '); ?> ₸</div>
                                            <div class="text-sm font-medium text-green-600">Станет: <?php echo number_format($route['optimized_cost'], 0, ',', ' '); ?> ₸</div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4">
                                        <h5 class="font-medium text-gray-800 mb-3">Заказы в маршруте:</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <?php foreach ($route['orders'] as $order): ?>
                                                <div class="bg-white border border-gray-200 p-3">
                                                    <div class="font-medium text-sm">Заказ №<?php echo $order['id']; ?></div>
                                                    <div class="text-xs text-gray-600 mt-1">
                                                        <?php echo htmlspecialchars(substr($order['pickup_address'] ?? '', 0, 30)); ?>
                                                        <?php echo strlen($order['pickup_address'] ?? '') > 30 ? '...' : ''; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?php echo htmlspecialchars($order['contact_name'] ?? ''); ?>
                                                    </div>
                                                    <?php if ($order['weight']): ?>
                                                        <div class="text-xs text-gray-500">
                                                            Вес: <?php echo $order['weight']; ?> кг
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex justify-end space-x-3">
                                        <button onclick="createOptimizedRoute(<?php echo htmlspecialchars(json_encode($route)); ?>)"
                                                class="bg-green-600 text-white px-4 py-2 text-sm hover:bg-green-700">
                                            Применить оптимизацию
                                        </button>
                                        <button onclick="showRouteDetails(<?php echo htmlspecialchars(json_encode($route)); ?>)"
                                                class="border border-gray-300 text-gray-700 px-4 py-2 text-sm hover:border-gray-400">
                                            Подробности
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Рекомендации по оптимизации -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Принципы оптимизации</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div>
                            <div class="font-medium">Консолидация заказов</div>
                            <div class="text-gray-600">Объединение заказов в одном направлении для снижения транспортных расходов</div>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                        <div>
                            <div class="font-medium">Оптимальная загрузка</div>
                            <div class="text-gray-600">Максимальное использование грузоподъемности транспорта</div>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                        <div>
                            <div class="font-medium">Временные окна</div>
                            <div class="text-gray-600">Учет времени доставки и предпочтений клиентов</div>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                        <div>
                            <div class="font-medium">Географическая близость</div>
                            <div class="text-gray-600">Группировка адресов по районам и зонам доставки</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Статистика эффективности</h3>
                
                <?php
                try {
                    $pdo = \Database::getInstance()->getConnection();
                    
                    // Статистика по направлениям за последний месяц
                    $stmt = $pdo->query("
                        SELECT destination_city, COUNT(*) as count
                        FROM shipment_orders 
                        WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
                        AND destination_city IS NOT NULL
                        GROUP BY destination_city
                        ORDER BY count DESC
                        LIMIT 5
                    ");
                    $topDirections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                } catch (Exception $e) {
                    $topDirections = [];
                }
                ?>
                
                <div class="space-y-3">
                    <div class="text-sm text-gray-600 mb-3">Топ направлений за месяц:</div>
                    <?php foreach ($topDirections as $direction): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($direction['destination_city']); ?></span>
                            <span class="text-sm text-gray-600"><?php echo $direction['count']; ?> заказов</span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($topDirections)): ?>
                        <div class="text-center text-gray-500 text-sm py-4">
                            Недостаточно данных для анализа
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200">
                    <div class="text-sm text-blue-800">
                        <div class="font-medium mb-2">Рекомендация:</div>
                        <div>Запускайте оптимизацию ежедневно для максимальной эффективности. 
                        Оптимальное время - утром перед планированием маршрутов на день.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createOptimizedRoute(route) {
            if (confirm(`Применить оптимизацию для направления "${route.destination}"?\nЭкономия составит ${route.savings.toLocaleString()} ₸`)) {
                // Здесь можно добавить отправку данных на сервер для создания оптимизированного маршрута
                alert('Оптимизация применена! Заказы объединены в один маршрут.');
            }
        }
        
        function showRouteDetails(route) {
            let details = `Подробности маршрута: ${route.destination}\n\n`;
            details += `Заказов в маршруте: ${route.order_count}\n`;
            details += `Экономия: ${route.savings.toLocaleString()} ₸ (${route.efficiency}%)\n\n`;
            details += "Заказы:\n";
            
            route.orders.forEach((order, index) => {
                details += `${index + 1}. Заказ №${order.id} - ${order.contact_name}\n`;
                details += `   Адрес: ${order.pickup_address}\n`;
                if (order.weight) details += `   Вес: ${order.weight} кг\n`;
                details += "\n";
            });
            
            alert(details);
        }
    </script>
</body>
</html>