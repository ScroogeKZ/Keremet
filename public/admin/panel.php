<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$telegramService = new App\TelegramService();
$filters = [];

// Handle filters
if (isset($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
if (isset($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
if (isset($_GET['order_type'])) $filters['order_type'] = $_GET['order_type'];
if (isset($_GET['status'])) $filters['status'] = $_GET['status'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    try {
        $orderModel->updateStatus($orderId, $newStatus);
        $success = 'Статус заказа обновлен';
    } catch (Exception $e) {
        $error = 'Ошибка обновления: ' . $e->getMessage();
    }
}

$orders = $orderModel->getAll($filters);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Хром-KZ Логистика</title>
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
                        <h1 class="text-base md:text-lg font-medium text-gray-900">Админ панель</h1>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-600 hover:text-gray-900 p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <!-- Desktop menu -->
                <div class="hidden md:flex space-x-4">
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
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
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 pt-3 pb-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Дашборд</a>
                    <a href="/admin/reports.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Быстрые действия</a>
                    <a href="/admin/cost_calculator.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Калькулятор</a>
                    <a href="/admin/users.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Пользователи</a>
                    <a href="/admin/search.php" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Поиск</a>
                    <a href="/" class="text-gray-600 hover:text-gray-900 px-2 py-2 text-center">Главная</a>
                    <a href="/admin/logout.php" class="text-gray-900 hover:text-red-600 px-2 py-2 text-center font-medium col-span-2">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-4 md:py-6">
        <h1 class="text-lg md:text-xl font-medium text-gray-900 mb-4 md:mb-6">Заказы</h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-50 text-green-800 px-3 py-2 text-sm mb-4 border border-green-200">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 text-red-800 px-3 py-2 text-sm mb-4 border border-red-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Status Bar -->
        <div class="bg-white border border-gray-200 px-4 py-3 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div>
                        <span class="text-xs text-gray-600">Telegram:</span>
                        <?php if ($telegramService->isConfigured()): ?>
                            <span class="text-xs text-green-600">Активен</span>
                        <?php else: ?>
                            <span class="text-xs text-red-600">Неактивен</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="text-xs text-gray-600">Всего заказов: <?php echo count($orders); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white border border-gray-200 mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-sm font-medium text-gray-900">Фильтры и поиск</h2>
                    <div class="flex space-x-2">
                        <button onclick="toggleBulkActions()" class="text-xs px-3 py-1.5 text-gray-700 border border-gray-300 hover:border-gray-400">
                            Массовые действия
                        </button>
                        <button onclick="exportOrders()" class="text-xs px-3 py-1.5 bg-gray-900 text-white hover:bg-gray-800">
                            Экспорт
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-4">
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 md:gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Поиск</label>
                    <input type="text" name="search" placeholder="Имя, телефон, адрес..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           class="w-full text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">От даты</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>"
                           class="w-full text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">До даты</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>"
                           class="w-full text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Тип</label>
                    <select name="order_type" class="w-full text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                        <option value="">Все</option>
                        <option value="astana" <?php echo ($_GET['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>Астана</option>
                        <option value="regional" <?php echo ($_GET['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>Межгород</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Статус</label>
                    <select name="status" class="w-full text-sm px-3 py-1.5 border border-gray-300 focus:outline-none focus:border-gray-400">
                        <option value="">Все</option>
                        <option value="new" <?php echo ($_GET['status'] ?? '') === 'new' ? 'selected' : ''; ?>>Новый</option>
                        <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                    </select>
                </div>
                
                <div class="md:col-span-5 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                    <button type="submit" class="bg-gray-900 text-white text-sm px-4 py-1.5 hover:bg-gray-800 text-center">
                        Применить
                    </button>
                    <a href="/admin/panel.php" class="bg-gray-300 text-gray-700 text-sm px-4 py-1.5 hover:bg-gray-400 text-center">
                        Сбросить
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Bar (Hidden by default) -->
        <div id="bulkActionsBar" class="bg-gray-100 border border-gray-200 p-3 mb-6 hidden">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-medium">Выбрано: <span id="selectedCount">0</span></span>
                    <select id="bulkAction" class="px-2 py-1 border border-gray-300 text-xs">
                        <option value="">Действие</option>
                        <option value="processing">В обработку</option>
                        <option value="completed">Завершить</option>
                        <option value="delete">Удалить</option>
                    </select>
                    <button onclick="executeBulkAction()" class="bg-gray-900 text-white px-3 py-1 text-xs hover:bg-gray-800">
                        Выполнить
                    </button>
                </div>
                <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
                    ✕ Отменить
                </button>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="bg-white border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-sm font-medium text-gray-900">Заказы (<?php echo count($orders); ?>)</h2>
                    <div class="flex space-x-2">
                        <label class="flex items-center space-x-2 text-xs">
                            <input type="checkbox" id="selectAllOrders" onchange="toggleAllOrders()" class="border-gray-300">
                            <span>Выбрать все</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full" id="ordersTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                <input type="checkbox" id="headerCheckbox" onchange="toggleAllOrders()" class="border-gray-300">
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                ID
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                Тип
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Маршрут</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Груз</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                Контакт
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                Статус
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                Дата
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 order-row" data-order-id="<?php echo $order['id']; ?>">
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <input type="checkbox" class="order-checkbox border-gray-300" value="<?php echo $order['id']; ?>" onchange="updateSelectedCount()">
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium mr-2">
                                            #<?php echo $order['id']; ?>
                                        </span>
                                        <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" class="text-gray-600 hover:text-gray-900 text-xs">
                                            Детали
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700">
                                        <?php echo $order['order_type'] === 'astana' ? 'Астана' : 'Межгород'; ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500 max-w-xs">
                                    <?php if ($order['order_type'] === 'regional'): ?>
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($order['pickup_city'] ?? 'Не указан'); ?> → <?php echo htmlspecialchars($order['destination_city'] ?? 'Не указан'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-900">Астана</div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-400 truncate">
                                        <?php echo htmlspecialchars(substr($order['pickup_address'], 0, 40)); ?>...
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($order['cargo_type']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php echo htmlspecialchars($order['weight'] ?? 'Не указан'); ?> кг
                                    </div>
                                    <?php if (isset($order['shipping_cost']) && $order['shipping_cost']): ?>
                                        <div class="text-xs font-medium text-gray-700">
                                            <?php echo number_format($order['shipping_cost'], 0, ',', ' '); ?> ₸
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-400">
                                            Не указана
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($order['contact_name'] ?? 'Не указан'); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <a href="tel:<?php echo htmlspecialchars($order['contact_phone'] ?? ''); ?>" class="hover:text-gray-600">
                                            <?php echo htmlspecialchars($order['contact_phone'] ?? 'Не указан'); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs 
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
                                            'processing' => 'В обработке',
                                            'completed' => 'Завершен',
                                            default => $order['status']
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <div class="text-sm">
                                        <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm">
                                    <div class="flex items-center space-x-2">
                                        <select onchange="updateOrderStatus(this, <?php echo $order['id']; ?>)" 
                                                class="text-xs border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                            <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>Новый</option>
                                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                        </select>
                                        <button onclick="deleteOrder(<?php echo $order['id']; ?>)" class="text-red-600 hover:text-red-800 text-xs">
                                            Удалить
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    Заказы не найдены
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for order details -->
    <div id="orderDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-30 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-0 border border-gray-200 w-11/12 md:w-3/4 lg:w-1/2 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900">Детали заказа</h3>
                    <button onclick="closeOrderDetails()" class="text-gray-400 hover:text-gray-600 text-sm">
                        ✕
                    </button>
                </div>
            </div>
            <div id="orderDetailsContent" class="p-4">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            // Show modal
            document.getElementById('orderDetailsModal').classList.remove('hidden');
            
            // Load order details via AJAX
            fetch(`/admin/api.php?action=get_order&id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = 
                            '<p class="text-red-600">Ошибка загрузки данных: ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<p class="text-red-600">Ошибка загрузки данных</p>';
                });
        }

        function displayOrderDetails(order) {
            const content = document.getElementById('orderDetailsContent');
            const orderTypeText = order.order_type === 'astana' ? 'Доставка по Астане' : 'Межгородская доставка';
            
            content.innerHTML = `
                <form id="editOrderForm" class="space-y-6" onsubmit="saveOrderChanges(event)">
                    <input type="hidden" name="order_id" value="${order.id}">
                    
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-medium text-gray-900">Заказ #${order.id} - ${orderTypeText}</h4>
                        <div class="flex gap-2">
                            <button type="button" onclick="toggleEditMode()" id="editButton" class="bg-gray-900 text-white px-3 py-1.5 text-xs hover:bg-gray-800">
                                Редактировать
                            </button>
                            <button type="submit" id="saveButton" class="hidden bg-gray-900 text-white px-3 py-1.5 text-xs hover:bg-gray-800">
                                Сохранить
                            </button>
                            <button type="button" onclick="cancelEdit()" id="cancelButton" class="hidden text-gray-600 hover:text-gray-900 px-3 py-1.5 text-xs border border-gray-300">
                                Отмена
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border border-gray-200 p-3">
                            <h4 class="text-xs font-medium text-gray-900 mb-3">Основная информация</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Статус:</label>
                                    <select name="status" class="edit-field hidden w-full mt-1 text-sm border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                        <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В обработке</option>
                                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Завершен</option>
                                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменен</option>
                                    </select>
                                    <span class="view-field text-sm">${{
                                        'new': 'Новый',
                                        'processing': 'В обработке',
                                        'completed': 'Завершен',
                                        'cancelled': 'Отменен'
                                    }[order.status] || order.status}</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Тип заказа:</label>
                                    <span class="view-field text-sm">${orderTypeText}</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Дата создания:</label>
                                    <span class="view-field text-sm">${new Date(order.created_at).toLocaleString('ru-RU')}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white border border-gray-200 p-3">
                            <h4 class="text-xs font-medium text-gray-900 mb-3">Груз</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Тип груза:</label>
                                    <select name="cargo_type" class="edit-field hidden w-full mt-1 text-sm border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                        <option value="">Выберите тип груза</option>
                                        <option value="лифтовые порталы" ${order.cargo_type === 'лифтовые порталы' ? 'selected' : ''}>Лифтовые порталы</option>
                                        <option value="т-образные профили" ${order.cargo_type === 'т-образные профили' ? 'selected' : ''}>Т-образные профили</option>
                                        <option value="металлические плинтуса" ${order.cargo_type === 'металлические плинтуса' ? 'selected' : ''}>Металлические плинтуса</option>
                                        <option value="корзины для кондиционеров" ${order.cargo_type === 'корзины для кондиционеров' ? 'selected' : ''}>Корзины для кондиционеров</option>
                                        <option value="декоративные решетки" ${order.cargo_type === 'декоративные решетки' ? 'selected' : ''}>Декоративные решетки</option>
                                        <option value="перфорированные фасадные кассеты" ${order.cargo_type === 'перфорированные фасадные кассеты' ? 'selected' : ''}>Перфорированные фасадные кассеты</option>
                                        <option value="стеклянные душевые кабины" ${order.cargo_type === 'стеклянные душевые кабины' ? 'selected' : ''}>Стеклянные душевые кабины</option>
                                        <option value="зеркальные панно" ${order.cargo_type === 'зеркальные панно' ? 'selected' : ''}>Зеркальные панно</option>
                                        <option value="рамы и багеты" ${order.cargo_type === 'рамы и багеты' ? 'selected' : ''}>Рамы и багеты</option>
                                        <option value="козырьки" ${order.cargo_type === 'козырьки' ? 'selected' : ''}>Козырьки</option>
                                        <option value="документы" ${order.cargo_type === 'документы' ? 'selected' : ''}>Документы</option>
                                        <option value="образцы" ${order.cargo_type === 'образцы' ? 'selected' : ''}>Образцы</option>
                                        <option value="другое" ${order.cargo_type === 'другое' ? 'selected' : ''}>Другое</option>
                                    </select>
                                    <span class="view-field text-sm">${order.cargo_type || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Вес (кг):</label>
                                    <input type="number" step="0.1" name="weight" value="${order.weight || ''}" class="edit-field hidden w-full mt-1 text-sm border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                    <span class="view-field text-sm">${order.weight || 'Не указан'} кг</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Габариты:</label>
                                    <input type="text" name="dimensions" value="${order.dimensions || ''}" class="edit-field hidden w-full mt-1 text-sm border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                    <span class="view-field text-sm">${order.dimensions || 'Не указаны'}</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Готов к отправке:</label>
                                    <input type="time" name="ready_time" value="${order.ready_time || ''}" class="edit-field hidden w-full mt-1 text-sm border border-gray-300 px-2 py-1 focus:outline-none focus:border-gray-400">
                                    <span class="view-field text-sm">${order.ready_time || 'Не указано'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">💰 Стоимость отгрузки (тенге):</label>
                                    <input type="number" step="0.01" min="0" name="shipping_cost" value="${order.shipping_cost || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                                    <span class="view-field">${order.shipping_cost ? order.shipping_cost + ' ₸' : 'Не указана'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Адреса</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${order.order_type === 'regional' ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Город отправления:</label>
                                    <input type="text" name="pickup_city" value="${order.pickup_city || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.pickup_city || 'Не указан'}</span>
                                </div>
                            ` : ''}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Адрес забора:</label>
                                <textarea name="pickup_address" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500 rows-3">${order.pickup_address || ''}</textarea>
                                <span class="view-field">${order.pickup_address || 'Не указан'}</span>
                            </div>
                            ${order.order_type === 'regional' ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Город назначения:</label>
                                    <input type="text" name="destination_city" value="${order.destination_city || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.destination_city || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Адрес доставки:</label>
                                    <textarea name="delivery_address" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500 rows-3">${order.delivery_address || ''}</textarea>
                                    <span class="view-field">${order.delivery_address || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Способ доставки:</label>
                                    <select name="delivery_method" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                        <option value="">Выберите способ</option>
                                        <option value="Курьер" ${order.delivery_method === 'Курьер' ? 'selected' : ''}>Курьер</option>
                                        <option value="Самовывоз" ${order.delivery_method === 'Самовывоз' ? 'selected' : ''}>Самовывоз</option>
                                        <option value="Терминал" ${order.delivery_method === 'Терминал' ? 'selected' : ''}>Терминал</option>
                                    </select>
                                    <span class="view-field">${order.delivery_method || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Желаемая дата прибытия:</label>
                                    <input type="date" name="desired_arrival_date" value="${order.desired_arrival_date || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.desired_arrival_date || 'Не указана'}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-purple-900 mb-3">Контакты отправителя</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Имя:</label>
                                    <input type="text" name="contact_name" value="${order.contact_name || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.contact_name || 'Не указано'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Телефон:</label>
                                    <input type="tel" name="contact_phone" value="${order.contact_phone || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.contact_phone || 'Не указан'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-orange-900 mb-3">Контакты получателя</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Имя:</label>
                                    <input type="text" name="recipient_contact" value="${order.recipient_contact || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.recipient_contact || 'Не указано'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Телефон:</label>
                                    <input type="tel" name="recipient_phone" value="${order.recipient_phone || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.recipient_phone || 'Не указан'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 mb-3">Комментарий</h4>
                        <textarea name="comment" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500" rows="3">${order.comment || ''}</textarea>
                        <span class="view-field">${order.comment || 'Не указан'}</span>
                    </div>
                </form>
            `;
        }

        function closeOrderDetails() {
            document.getElementById('orderDetailsModal').classList.add('hidden');
        }

        function updateOrderStatus(selectElement, orderId) {
            const form = selectElement.closest('form');
            form.submit();
        }

        function deleteOrder(orderId) {
            if (confirm('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.')) {
                // Send delete request to API
                fetch(`/admin/api.php?action=delete_order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: parseInt(orderId)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and reload page
                        alert('Заказ успешно удален!');
                        window.location.reload();
                    } else {
                        alert('Ошибка при удалении заказа: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при удалении заказа. Проверьте подключение к интернету.');
                });
            }
        }

        function toggleEditMode() {
            const editFields = document.querySelectorAll('.edit-field');
            const viewFields = document.querySelectorAll('.view-field');
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            
            editFields.forEach(field => field.classList.toggle('hidden'));
            viewFields.forEach(field => field.classList.toggle('hidden'));
            
            editButton.classList.toggle('hidden');
            saveButton.classList.toggle('hidden');
            cancelButton.classList.toggle('hidden');
        }

        function cancelEdit() {
            // Reset form and toggle back to view mode
            const currentOrderId = document.querySelector('input[name="order_id"]').value;
            
            // Reload order details
            fetch(`/admin/api.php?action=get_order&id=${currentOrderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    }
                });
        }

        function saveOrderChanges(event) {
            event.preventDefault();
            
            const form = document.getElementById('editOrderForm');
            const formData = new FormData(form);
            const orderData = {};
            
            for (let [key, value] of formData.entries()) {
                if (key !== 'order_id') {
                    orderData[key] = value;
                }
            }
            
            const orderId = formData.get('order_id');
            
            // Show loading state
            const saveButton = document.getElementById('saveButton');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '⏳ Сохранение...';
            saveButton.disabled = true;
            
            // Send update request
            fetch(`/admin/api.php?action=update_order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: parseInt(orderId),
                    data: orderData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Заказ успешно обновлен!');
                    
                    // Reload order details in view mode
                    displayOrderDetails(data.order);
                    
                    // Reload the main orders table
                    window.location.reload();
                } else {
                    alert('Ошибка при сохранении: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при сохранении данных');
            })
            .finally(() => {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        }

        // Close modal when clicking outside
        document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetails();
            }
        });

        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });

                // Close mobile menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                        mobileMenu.classList.add('hidden');
                    }
                });
            }
        });

        // Missing functions for bulk actions and export
        function toggleBulkActions() {
            const bulkActionsPanel = document.getElementById('bulkActionsPanel');
            if (bulkActionsPanel) {
                bulkActionsPanel.classList.toggle('hidden');
            }
        }

        function exportOrders() {
            // Get current filters from URL parameters or form inputs
            const urlParams = new URLSearchParams(window.location.search);
            const params = new URLSearchParams();
            
            // Copy existing filters
            urlParams.forEach((value, key) => {
                if (value && key !== 'page') {
                    params.append(key, value);
                }
            });
            
            // Add export parameter
            params.append('action', 'export');
            params.append('format', 'excel');
            
            // Create and trigger download
            const downloadUrl = `/admin/export.php?${params.toString()}`;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `orders_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show user feedback
            alert('Экспорт Excel файла начат. Файл будет загружен автоматически.');
        }

        function executeBulkAction() {
            const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
            const action = document.getElementById('bulkAction').value;
            
            if (!action) {
                alert('Выберите действие');
                return;
            }
            
            if (selectedOrders.length === 0) {
                alert('Выберите заказы');
                return;
            }
            
            if (confirm(`Применить действие "${action}" к ${selectedOrders.length} заказам?`)) {
                fetch('/admin/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'bulk_action',
                        bulk_action: action,
                        order_ids: selectedOrders
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Действие выполнено успешно');
                        window.location.reload();
                    } else {
                        alert('Ошибка: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка выполнения действия');
                });
            }
        }

        function clearSelection() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
            updateSelectedCount();
            toggleBulkActions();
        }

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.order-checkbox:checked').length;
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = selectedCount;
            }
            
            // Show/hide bulk actions panel
            const bulkActionsPanel = document.getElementById('bulkActionsPanel');
            if (bulkActionsPanel) {
                if (selectedCount > 0) {
                    bulkActionsPanel.classList.remove('hidden');
                } else {
                    bulkActionsPanel.classList.add('hidden');
                }
            }
        }

        function toggleAllOrders() {
            const headerCheckbox = document.getElementById('headerCheckbox') || document.getElementById('selectAllOrders');
            const orderCheckboxes = document.querySelectorAll('.order-checkbox');
            
            orderCheckboxes.forEach(cb => {
                cb.checked = headerCheckbox.checked;
            });
            
            updateSelectedCount();
        }

        function sortTable(column) {
            // Basic table sorting functionality
            console.log('Sorting by:', column);
            // Implementation would require server-side sorting or client-side table manipulation
        }

        function changeTableView(view) {
            const table = document.getElementById('ordersTable');
            if (view === 'compact') {
                table.classList.add('compact-view');
            } else {
                table.classList.remove('compact-view');
            }
        }
    </script>
</body>
</html>