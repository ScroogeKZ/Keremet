<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();

// Получаем выбранный месяц и год
$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear = $_GET['year'] ?? date('Y');

// Получаем заказы на выбранный месяц
try {
    $pdo = \Database::getInstance()->getConnection();
    
    $firstDay = "$selectedYear-$selectedMonth-01";
    $lastDay = date('Y-m-t', strtotime($firstDay));
    
    $stmt = $pdo->prepare("
        SELECT id, status, order_type, pickup_address, destination_city, 
               cargo_type, contact_name, contact_phone, created_at,
               COALESCE(desired_arrival_date, DATE(created_at)) as delivery_date
        FROM shipment_orders 
        WHERE DATE(COALESCE(desired_arrival_date, created_at)) BETWEEN ? AND ?
        ORDER BY delivery_date ASC
    ");
    
    $stmt->execute([$firstDay, $lastDay]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Группируем заказы по дням
    $ordersByDate = [];
    foreach ($orders as $order) {
        $date = date('Y-m-d', strtotime($order['delivery_date']));
        if (!isset($ordersByDate[$date])) {
            $ordersByDate[$date] = [];
        }
        $ordersByDate[$date][] = $order;
    }
    
} catch (Exception $e) {
    $orders = [];
    $ordersByDate = [];
}

$currentUser = Auth::getCurrentUser();

// Функция для получения названия дня недели
function getDayName($dayNumber) {
    $days = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    return $days[$dayNumber];
}

// Функция для получения названия месяца
function getMonthName($monthNumber) {
    $months = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
    ];
    return $months[(int)$monthNumber];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь доставок - Хром-KZ Логистика</title>
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
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Навигация по месяцам -->
        <div class="bg-white border border-gray-200 p-4 mb-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <?php echo getMonthName((int)$selectedMonth) . ' ' . $selectedYear; ?>
                    </h2>
                    <div class="text-sm text-gray-500">
                        Всего заказов: <?php echo count($orders); ?>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Переключение месяцев -->
                    <a href="?month=<?php echo date('m', strtotime("$selectedYear-$selectedMonth-01 -1 month")); ?>&year=<?php echo date('Y', strtotime("$selectedYear-$selectedMonth-01 -1 month")); ?>" 
                       class="text-gray-600 hover:text-gray-900 px-3 py-1 border border-gray-300 text-sm">← Предыдущий</a>
                    
                    <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" 
                       class="bg-gray-900 text-white px-3 py-1 text-sm hover:bg-gray-800">Текущий месяц</a>
                    
                    <a href="?month=<?php echo date('m', strtotime("$selectedYear-$selectedMonth-01 +1 month")); ?>&year=<?php echo date('Y', strtotime("$selectedYear-$selectedMonth-01 +1 month")); ?>" 
                       class="text-gray-600 hover:text-gray-900 px-3 py-1 border border-gray-300 text-sm">Следующий →</a>
                </div>
            </div>
        </div>

        <!-- Календарь -->
        <div class="bg-white border border-gray-200">
            <!-- Заголовки дней недели -->
            <div class="grid grid-cols-7 border-b border-gray-200">
                <?php 
                $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                foreach ($dayNames as $dayName): ?>
                    <div class="p-3 text-center text-sm font-medium text-gray-500 border-r border-gray-200 last:border-r-0">
                        <?php echo $dayName; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Дни месяца -->
            <div class="grid grid-cols-7">
                <?php
                $firstDayOfMonth = mktime(0, 0, 0, $selectedMonth, 1, $selectedYear);
                $daysInMonth = date('t', $firstDayOfMonth);
                $firstDayWeekday = date('N', $firstDayOfMonth); // 1 = понедельник
                
                // Пустые ячейки для дней предыдущего месяца
                for ($i = 1; $i < $firstDayWeekday; $i++): ?>
                    <div class="h-32 border-r border-b border-gray-200 bg-gray-50"></div>
                <?php endfor;
                
                // Дни текущего месяца
                for ($day = 1; $day <= $daysInMonth; $day++):
                    $currentDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                    $isToday = $currentDate === date('Y-m-d');
                    $dayOrders = $ordersByDate[$currentDate] ?? [];
                ?>
                    <div class="h-32 border-r border-b border-gray-200 last:border-r-0 relative overflow-hidden <?php echo $isToday ? 'bg-blue-50' : 'bg-white'; ?>">
                        <div class="p-2">
                            <div class="text-sm font-medium text-gray-900 <?php echo $isToday ? 'text-blue-600' : ''; ?>">
                                <?php echo $day; ?>
                                <?php if ($isToday): ?>
                                    <span class="text-xs text-blue-500">сегодня</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Заказы на этот день -->
                            <div class="mt-1 space-y-1">
                                <?php foreach ($dayOrders as $index => $order): 
                                    if ($index >= 3) break; // Показываем только первые 3 заказа
                                    $statusColor = match($order['status']) {
                                        'new' => 'bg-red-100 text-red-800',
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>
                                    <div class="text-xs p-1 rounded <?php echo $statusColor; ?> cursor-pointer hover:opacity-80"
                                         onclick="showOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                        №<?php echo $order['id']; ?> - <?php echo htmlspecialchars(substr($order['contact_name'], 0, 10)); ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($dayOrders) > 3): ?>
                                    <div class="text-xs text-gray-500">+<?php echo count($dayOrders) - 3; ?> еще</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Легенда -->
        <div class="mt-6 flex items-center space-x-6 text-sm">
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-red-100 border border-red-200"></div>
                <span>Новые заказы</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-yellow-100 border border-yellow-200"></div>
                <span>В обработке</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-green-100 border border-green-200"></div>
                <span>Завершенные</span>
            </div>
        </div>
    </div>

    <!-- Модальное окно для деталей заказа -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white max-w-2xl w-full p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Детали заказа</h3>
                    <button onclick="closeOrderModal()" class="text-gray-500 hover:text-gray-700">✕</button>
                </div>
                <div id="orderDetails" class="space-y-3 text-sm">
                    <!-- Детали заказа будут загружены через JavaScript -->
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeOrderModal()" class="text-gray-600 border border-gray-300 px-4 py-2 text-sm hover:border-gray-400">
                        Закрыть
                    </button>
                    <a id="editOrderLink" href="#" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                        Редактировать
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showOrderDetails(order) {
            const modal = document.getElementById('orderModal');
            const details = document.getElementById('orderDetails');
            const editLink = document.getElementById('editOrderLink');
            
            const statusText = {
                'new': 'Новый',
                'processing': 'В обработке',
                'completed': 'Завершен'
            }[order.status] || order.status;
            
            const orderTypeText = order.order_type === 'astana' ? 'Астана' : 'Межгород';
            
            details.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div><strong>Номер заказа:</strong> №${order.id}</div>
                    <div><strong>Тип:</strong> ${orderTypeText}</div>
                    <div><strong>Статус:</strong> ${statusText}</div>
                    <div><strong>Дата создания:</strong> ${new Date(order.created_at).toLocaleDateString('ru-RU')}</div>
                    <div><strong>Адрес забора:</strong> ${order.pickup_address || '-'}</div>
                    <div><strong>Направление:</strong> ${order.destination_city || 'Астана'}</div>
                    <div><strong>Тип груза:</strong> ${order.cargo_type || '-'}</div>
                    <div><strong>Контакт:</strong> ${order.contact_name || '-'}</div>
                    <div><strong>Телефон:</strong> ${order.contact_phone || '-'}</div>
                </div>
            `;
            
            editLink.href = `/admin/panel.php?order_id=${order.id}`;
            modal.classList.remove('hidden');
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
        
        // Закрытие модального окна по клику вне его
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>