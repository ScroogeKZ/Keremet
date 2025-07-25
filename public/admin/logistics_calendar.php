<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();

// Получаем заказы на ближайшую неделю
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+7 days'));

try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Заказы с желаемыми датами доставки
    $deliveryOrders = $pdo->prepare("
        SELECT id, order_type, status, pickup_city, destination_city, 
               cargo_type, contact_name, contact_phone, desired_arrival_date, shipping_cost
        FROM shipment_orders 
        WHERE desired_arrival_date BETWEEN ? AND ?
        ORDER BY desired_arrival_date ASC, created_at ASC
    ");
    $deliveryOrders->execute([$startDate, $endDate]);
    $scheduledOrders = $deliveryOrders->fetchAll(PDO::FETCH_ASSOC);
    
    // Группируем по датам
    $ordersByDate = [];
    foreach ($scheduledOrders as $order) {
        $date = $order['desired_arrival_date'];
        if (!isset($ordersByDate[$date])) {
            $ordersByDate[$date] = [];
        }
        $ordersByDate[$date][] = $order;
    }
    
} catch (Exception $e) {
    $ordersByDate = [];
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь логиста - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Календарь доставок</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
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

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-medium text-gray-900">Календарь доставок</h1>
                <div class="flex space-x-3">
                    <a href="/admin/quick_actions.php" class="bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">
                        Быстрые действия
                    </a>
                    <a href="/admin/panel.php" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                        Назад в панель
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-7 gap-4">
                <?php for ($i = 0; $i < 7; $i++): 
                    $currentDate = date('Y-m-d', strtotime("+$i days"));
                    $dayName = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'][date('N', strtotime($currentDate)) - 1];
                    $isToday = $currentDate === date('Y-m-d');
                    $orders = $ordersByDate[$currentDate] ?? [];
                ?>
                <div class="bg-white border border-gray-200 min-h-80">
                    <div class="p-3 border-b border-gray-200 <?php echo $isToday ? 'bg-blue-50' : 'bg-gray-50'; ?>">
                        <div class="text-center">
                            <div class="text-xs font-medium text-gray-900"><?php echo $dayName; ?></div>
                            <div class="text-lg font-bold <?php echo $isToday ? 'text-blue-600' : 'text-gray-600'; ?>">
                                <?php echo date('j', strtotime($currentDate)); ?>
                            </div>
                            <div class="text-xs text-gray-500"><?php echo date('M', strtotime($currentDate)); ?></div>
                        </div>
                    </div>
                    
                    <div class="p-2 space-y-2">
                        <?php if (empty($orders)): ?>
                            <div class="text-center text-gray-400 text-xs py-6">
                                Нет запланированных доставок
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <div class="bg-gray-50 border border-gray-200 p-2 text-xs">
                                <div class="font-medium text-gray-900 mb-1">
                                    #<?php echo $order['id']; ?>
                                    <span class="ml-1 px-1 py-0.5 text-xs 
                                        <?php 
                                        echo match($order['status']) {
                                            'new' => 'bg-blue-100 text-blue-800',
                                            'processing' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>">
                                        <?php 
                                        echo match($order['status']) {
                                            'new' => 'Новый',
                                            'processing' => 'В работе',
                                            'completed' => 'Завершен',
                                            default => $order['status']
                                        };
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="text-gray-600 mb-1">
                                    <?php if ($order['order_type'] === 'regional'): ?>
                                        <div class="font-medium">
                                            <?php echo htmlspecialchars($order['pickup_city'] ?? 'Не указан'); ?> → 
                                            <?php echo htmlspecialchars($order['destination_city'] ?? 'Не указан'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="font-medium">Астана</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-gray-500 mb-1">
                                    <?php echo htmlspecialchars(substr($order['cargo_type'], 0, 20)); ?>
                                    <?php if (strlen($order['cargo_type']) > 20): ?>...<?php endif; ?>
                                </div>
                                
                                <div class="text-gray-500 mb-1">
                                    <?php echo htmlspecialchars($order['contact_name'] ?? 'Не указан'); ?>
                                </div>
                                
                                <?php if ($order['shipping_cost']): ?>
                                <div class="font-medium text-gray-700">
                                    <?php echo number_format($order['shipping_cost'], 0, ',', ' '); ?> ₸
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-center mt-2">
                                    <a href="/admin/edit_order.php?id=<?php echo $order['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800">Редактировать</a>
                                    
                                    <?php if ($order['status'] === 'new'): ?>
                                    <button onclick="quickUpdate(<?php echo $order['id']; ?>, 'processing')" 
                                            class="text-yellow-600 hover:text-yellow-800">В работу</button>
                                    <?php elseif ($order['status'] === 'processing'): ?>
                                    <button onclick="quickUpdate(<?php echo $order['id']; ?>, 'completed')" 
                                            class="text-green-600 hover:text-green-800">Завершить</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Статистика на неделю -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 border border-gray-200">
                    <div class="text-2xl font-bold text-gray-900"><?php echo count($scheduledOrders); ?></div>
                    <div class="text-sm text-gray-500">Запланировано на неделю</div>
                </div>
                
                <div class="bg-white p-4 border border-gray-200">
                    <div class="text-2xl font-bold text-blue-600">
                        <?php echo count(array_filter($scheduledOrders, fn($o) => $o['status'] === 'new')); ?>
                    </div>
                    <div class="text-sm text-gray-500">Новых заказов</div>
                </div>
                
                <div class="bg-white p-4 border border-gray-200">
                    <div class="text-2xl font-bold text-yellow-600">
                        <?php echo count(array_filter($scheduledOrders, fn($o) => $o['status'] === 'processing')); ?>
                    </div>
                    <div class="text-sm text-gray-500">В обработке</div>
                </div>
                
                <div class="bg-white p-4 border border-gray-200">
                    <div class="text-2xl font-bold text-green-600">
                        <?php 
                        $totalCost = array_sum(array_column($scheduledOrders, 'shipping_cost'));
                        echo number_format($totalCost, 0, ',', ' '); 
                        ?>
                    </div>
                    <div class="text-sm text-gray-500">Общая стоимость ₸</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function quickUpdate(orderId, newStatus) {
        const statusNames = {
            'processing': 'В обработку',
            'completed': 'Завершен'
        };
        
        if (confirm(`Изменить статус заказа #${orderId} на "${statusNames[newStatus]}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/quick_actions.php';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = newStatus === 'processing' ? 'mark_processing' : 'mark_completed';
            form.appendChild(actionInput);
            
            const orderInput = document.createElement('input');
            orderInput.name = 'order_ids[]';
            orderInput.value = orderId;
            form.appendChild(orderInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>