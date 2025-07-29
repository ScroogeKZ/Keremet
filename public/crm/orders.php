<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

$orderModel = new ShipmentOrder();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {
        $orderModel->updateStatus($_POST['order_id'], $_POST['status']);
        header('Location: /crm/orders.php?updated=1');
        exit;
    }
}

// Get orders with filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'order_type' => $_GET['order_type'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$orders = $orderModel->getAll($filters);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen bg-gray-50" x-data="{ sidebarOpen: true }">
        <!-- Left Sidebar Navigation -->
        <div class="bg-white shadow-xl border-r border-gray-200 transition-all duration-300 ease-in-out" 
             :class="sidebarOpen ? 'w-72' : 'w-16'">
            <!-- Logo/Brand -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-shipping-fast text-white text-sm"></i>
                    </div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent transition-opacity duration-300"
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0'"
                        x-show="sidebarOpen">Хром-KZ CRM</h1>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" 
                        class="ml-auto p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-bars text-gray-600" :class="sidebarOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="px-4 py-6 space-y-1">
                <a href="/crm" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Дашборд' : ''">
                    <i class="fas fa-tachometer-alt w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Дашборд</span>
                </a>
                <a href="/crm/orders.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm group"
                   :title="!sidebarOpen ? 'Заказы' : ''">
                    <i class="fas fa-box w-5 text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="font-semibold transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Заказы</span>
                </a>
                <a href="/crm/clients.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Клиенты' : ''">
                    <i class="fas fa-users w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Клиенты</span>
                </a>
                <a href="/crm/notifications.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Уведомления' : ''">
                    <i class="fas fa-bell w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Уведомления</span>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">3</span>
                </a>
                <a href="/crm/calendar.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Календарь' : ''">
                    <i class="fas fa-calendar w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Календарь</span>
                </a>
                <a href="/crm/bulk_operations.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Массовые операции' : ''">
                    <i class="fas fa-tasks w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Массовые операции</span>
                </a>
                <a href="/crm/reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Отчеты' : ''">
                    <i class="fas fa-file-alt w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Отчеты</span>
                </a>
                <a href="/crm/analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Аналитика' : ''">
                    <i class="fas fa-chart-bar w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Аналитика</span>
                </a>
            </nav>
            
            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center" :class="sidebarOpen ? 'space-x-3' : 'justify-center'">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="flex-1 transition-opacity duration-300" 
                         :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                         x-show="sidebarOpen">
                        <p class="text-sm font-medium text-gray-900">Admin</p>
                        <p class="text-xs text-gray-500">Администратор</p>
                    </div>
                    <div class="relative transition-opacity duration-300" 
                         :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                         x-show="sidebarOpen" 
                         x-data="{ open: false }">
                        <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="origin-bottom-left absolute bottom-full left-0 mb-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="/crm/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Настройки</a>
                                <a href="/crm/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Выход</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Управление заказами</h2>
                    <p class="text-sm text-gray-600">Просмотр и управление всеми заказами</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                        <i class="fas fa-globe mr-2"></i>На сайт
                    </a>
                    <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-auto bg-gray-50 p-8">
                <!-- Page header -->
                <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Управление заказами</h2>
                <p class="mt-2 text-gray-600">Просмотр и управление всеми заказами</p>
            </div>
            <div class="flex space-x-3">
                <a href="/astana.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Новый заказ
                </a>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>Статус заказа успешно обновлен
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                           placeholder="Поиск по имени, телефону..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все статусы</option>
                        <option value="new" <?= $filters['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                        <option value="processing" <?= $filters['status'] === 'processing' ? 'selected' : '' ?>>В работе</option>
                        <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Завершен</option>
                        <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип заказа</label>
                    <select name="order_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Все типы</option>
                        <option value="astana" <?= $filters['order_type'] === 'astana' ? 'selected' : '' ?>>Астана</option>
                        <option value="regional" <?= $filters['order_type'] === 'regional' ? 'selected' : '' ?>>Региональный</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-search mr-2"></i>Фильтр
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Заказы (<?= count($orders) ?>)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Груз</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Стоимость</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?= $order['id'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                    <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональный' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['contact_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($order['contact_phone']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></div>
                                <div class="text-sm text-gray-500"><?= $order['weight'] ?>кг</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= number_format($order['shipping_cost'] ?? 0, 0, '.', ' ') ?> ₸
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $order['status'] === 'new' ? 'bg-blue-100 text-blue-800' : 
                                        ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) ?>">
                                    <?= $order['status'] === 'new' ? 'Новый' : 
                                        ($order['status'] === 'processing' ? 'В работе' : 
                                        ($order['status'] === 'completed' ? 'Завершен' : 'Отменен')) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                        <div class="py-1">
                                            <form method="POST" class="px-4 py-2">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <select name="status" onchange="this.form.submit()" class="w-full text-sm border-0 focus:ring-0">
                                                    <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В работе</option>
                                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Завершен</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                                </select>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>