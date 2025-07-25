<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();

// Обработка быстрых действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderIds = $_POST['order_ids'] ?? [];
    
    if (!empty($orderIds) && is_array($orderIds)) {
        try {
            $pdo = \Database::getInstance()->getConnection();
            
            switch ($action) {
                case 'mark_processing':
                    $stmt = $pdo->prepare("UPDATE shipment_orders SET status = 'processing', updated_at = CURRENT_TIMESTAMP WHERE id = ANY(?)");
                    $stmt->execute(['{' . implode(',', $orderIds) . '}']);
                    $message = 'Заказы переведены в статус "В обработке"';
                    break;
                    
                case 'mark_completed':
                    $stmt = $pdo->prepare("UPDATE shipment_orders SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ANY(?)");
                    $stmt->execute(['{' . implode(',', $orderIds) . '}']);
                    $message = 'Заказы переведены в статус "Завершен"';
                    break;
                    
                case 'set_urgent':
                    $stmt = $pdo->prepare("UPDATE shipment_orders SET notes = CASE 
                        WHEN notes IS NULL OR notes = '' THEN 'СРОЧНО!'
                        WHEN notes NOT LIKE '%СРОЧНО!%' THEN CONCAT('СРОЧНО! ', notes)
                        ELSE notes
                    END WHERE id = ANY(?)");
                    $stmt->execute(['{' . implode(',', $orderIds) . '}']);
                    $message = 'Заказы помечены как срочные';
                    break;
                    
                case 'calculate_route':
                    // Простой расчет маршрута для региональных заказов
                    $stmt = $pdo->query("
                        SELECT id, pickup_city, destination_city, pickup_address, delivery_address 
                        FROM shipment_orders 
                        WHERE id IN (" . implode(',', array_map('intval', $orderIds)) . ") 
                        AND order_type = 'regional'
                    ");
                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $routeInfo = [];
                    
                    foreach ($orders as $order) {
                        $distance = rand(300, 1500); // Случайное расстояние для демо
                        $routeInfo[] = [
                            'id' => $order['id'],
                            'route' => $order['pickup_city'] . ' → ' . $order['destination_city'],
                            'distance' => $distance . ' км',
                            'time' => round($distance / 60, 1) . ' ч'
                        ];
                    }
                    
                    header('Content-Type: application/json');
                    echo json_encode(['routes' => $routeInfo]);
                    exit;
            }
            
            if (isset($message)) {
                header('Location: /admin/panel.php?success=' . urlencode($message));
                exit;
            }
            
        } catch (Exception $e) {
            header('Location: /admin/panel.php?error=' . urlencode('Ошибка: ' . $e->getMessage()));
            exit;
        }
    }
}

// Получаем сегодняшние заказы для быстрого доступа
$todayOrders = $orderModel->getAll(['date' => date('Y-m-d')]);
$urgentOrders = $orderModel->getAll(['status' => 'new'], 'created_at ASC', 10);

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Быстрые действия - Хром-KZ Логистика</title>
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
                        <h1 class="text-lg font-medium text-gray-900">Быстрые действия</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
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
                <h1 class="text-xl font-medium text-gray-900">Быстрые действия</h1>
                <a href="/admin/panel.php" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                    Назад в панель
                </a>
            </div>

            <!-- Панель быстрых действий -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Массовые операции</h3>
                    <form id="bulkActionForm" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Номера заказов (через запятую)</label>
                            <input type="text" name="order_ids_input" placeholder="1, 2, 3..." 
                                   class="mt-1 block w-full border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div class="space-y-2">
                            <button type="button" onclick="bulkAction('mark_processing')" 
                                    class="w-full bg-yellow-600 text-white px-3 py-1.5 text-xs hover:bg-yellow-700">
                                В обработку
                            </button>
                            <button type="button" onclick="bulkAction('mark_completed')" 
                                    class="w-full bg-green-600 text-white px-3 py-1.5 text-xs hover:bg-green-700">
                                Завершить
                            </button>
                            <button type="button" onclick="bulkAction('set_urgent')" 
                                    class="w-full bg-red-600 text-white px-3 py-1.5 text-xs hover:bg-red-700">
                                Пометить срочным
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Планирование маршрутов</h3>
                    <form id="routeForm" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Заказы для маршрута</label>
                            <input type="text" name="route_orders" placeholder="Номера заказов..." 
                                   class="mt-1 block w-full border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <button type="button" onclick="calculateRoute()" 
                                class="w-full bg-blue-600 text-white px-3 py-1.5 text-xs hover:bg-blue-700">
                            Рассчитать маршрут
                        </button>
                    </form>
                    <div id="routeResults" class="mt-4 hidden">
                        <h4 class="font-medium text-gray-900 mb-2">Результат:</h4>
                        <div id="routeContent" class="text-sm text-gray-600"></div>
                    </div>
                </div>

                <div class="bg-white p-4 border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Быстрые ссылки</h3>
                    <div class="space-y-2">
                        <a href="/admin/orders.php?status=new" 
                           class="block w-full bg-gray-100 text-center px-3 py-1.5 text-xs hover:bg-gray-200">
                            Новые заказы (<?php echo count($urgentOrders); ?>)
                        </a>
                        <a href="/admin/export.php" 
                           class="block w-full bg-gray-100 text-center px-3 py-1.5 text-xs hover:bg-gray-200">
                            Экспорт данных
                        </a>
                        <a href="/admin/reports.php" 
                           class="block w-full bg-gray-100 text-center px-3 py-1.5 text-xs hover:bg-gray-200">
                            Отчеты
                        </a>
                        <a href="/admin/search.php" 
                           class="block w-full bg-gray-100 text-center px-3 py-1.5 text-xs hover:bg-gray-200">
                            Поиск заказов
                        </a>
                    </div>
                </div>
            </div>

            <!-- Срочные заказы -->
            <?php if (!empty($urgentOrders)): ?>
            <div class="bg-white border border-gray-200 mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Требуют внимания (новые заказы)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">№</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Тип</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Маршрут</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Груз</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Контакт</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($urgentOrders as $order): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-gray-900">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700">
                                        <?php echo $order['order_type'] === 'astana' ? 'Астана' : 'Межгород'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-900">
                                    <?php if ($order['order_type'] === 'regional'): ?>
                                        <?php echo htmlspecialchars($order['pickup_city'] ?? 'Не указан'); ?> → 
                                        <?php echo htmlspecialchars($order['destination_city'] ?? 'Не указан'); ?>
                                    <?php else: ?>
                                        Астана
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    <?php echo htmlspecialchars($order['cargo_type']); ?>
                                    <div class="text-xs text-gray-400">
                                        <?php echo htmlspecialchars($order['weight'] ?? 'Не указан'); ?> кг
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    <?php echo htmlspecialchars($order['contact_name'] ?? 'Не указан'); ?>
                                    <div class="text-xs text-gray-400">
                                        <?php echo htmlspecialchars($order['contact_phone'] ?? 'Не указан'); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs">
                                    <button onclick="quickProcess(<?php echo $order['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Взять в работу</button>
                                    <a href="/admin/edit_order.php?id=<?php echo $order['id']; ?>" 
                                       class="text-gray-600 hover:text-gray-900">Редактировать</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function bulkAction(action) {
        const input = document.querySelector('input[name="order_ids_input"]').value;
        if (!input.trim()) {
            alert('Введите номера заказов');
            return;
        }
        
        const orderIds = input.split(',').map(id => id.trim()).filter(id => id);
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        orderIds.forEach(id => {
            const input = document.createElement('input');
            input.name = 'order_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
    
    function calculateRoute() {
        const input = document.querySelector('input[name="route_orders"]').value;
        if (!input.trim()) {
            alert('Введите номера заказов для маршрута');
            return;
        }
        
        const orderIds = input.split(',').map(id => id.trim()).filter(id => id);
        
        fetch('/admin/quick_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=calculate_route&' + orderIds.map(id => 'order_ids[]=' + id).join('&')
        })
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('routeResults');
            const contentDiv = document.getElementById('routeContent');
            
            if (data.routes && data.routes.length > 0) {
                let html = '';
                data.routes.forEach(route => {
                    html += `<div class="mb-2">
                        <strong>Заказ #${route.id}:</strong> ${route.route}<br>
                        <span class="text-gray-500">Расстояние: ${route.distance}, Время: ${route.time}</span>
                    </div>`;
                });
                contentDiv.innerHTML = html;
                resultsDiv.classList.remove('hidden');
            } else {
                alert('Не удалось рассчитать маршрут');
            }
        })
        .catch(error => {
            alert('Ошибка при расчете маршрута');
        });
    }
    
    function quickProcess(orderId) {
        if (confirm('Взять заказ #' + orderId + ' в работу?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'mark_processing';
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