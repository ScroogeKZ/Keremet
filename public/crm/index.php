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
$filters = [
    'status' => $_GET['status'] ?? '',
    'order_type' => $_GET['order_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$orders = $orderModel->getAll($filters);

// Get statistics
$stats = $orderModel->getStatistics();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Система - Хром-KZ Логистика</title>
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
                <a href="/crm" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm group"
                   :title="!sidebarOpen ? 'Дашборд' : ''">
                    <i class="fas fa-tachometer-alt w-5 text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="font-semibold transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Дашборд</span>
                </a>
                <a href="/crm/orders.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Заказы' : ''">
                    <i class="fas fa-box w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
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
                    <h2 class="text-2xl font-bold text-gray-900">Панель управления</h2>
                    <p class="text-sm text-gray-600">Добро пожаловать в систему управления заказами</p>
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
                <!-- Statistics Cards with Modern UI -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Всего заказов</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></p>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>+12% от прошлого месяца
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-box text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">В обработке</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['processing_orders'] ?? 0 ?></p>
                            <p class="text-xs text-yellow-600 mt-1">
                                <i class="fas fa-clock mr-1"></i>Требует внимания
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Завершенные</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['completed_orders'] ?? 0 ?></p>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-check mr-1"></i>Успешно доставлено
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Общие расходы</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_revenue'] ?? 0, 0, '.', ' ') ?> ₸</p>
                            <p class="text-xs text-purple-600 mt-1">
                                <i class="fas fa-trending-up mr-1"></i>За текущий период
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Последние заказы</h3>
                <a href="/crm/orders.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">Посмотреть все</a>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                <li class="px-4 py-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $order['status'] === 'new' ? 'bg-blue-100 text-blue-800' : 
                                        ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) ?>">
                                    <?= $order['status'] === 'new' ? 'Новый' : 
                                        ($order['status'] === 'processing' ? 'В работе' : 
                                        ($order['status'] === 'completed' ? 'Завершен' : 'Отменен')) ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    Заказ #<?= $order['id'] ?> - <?= htmlspecialchars($order['cargo_type']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($order['contact_name']) ?> • <?= date('d.m.Y', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-900 font-medium">
                            <?= number_format($order['shipping_cost'] ?? 0, 0, '.', ' ') ?> ₸
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

                <!-- Quick Actions -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="/astana.php" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-plus-circle text-blue-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Новый заказ (Астана)</h3>
                                <p class="text-sm text-gray-500">Создать заказ по городу</p>
                            </div>
                        </div>
                    </a>

                    <a href="/regional.php" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-truck text-green-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Региональный заказ</h3>
                                <p class="text-sm text-gray-500">Межгородские перевозки</p>
                            </div>
                        </div>
                    </a>

                    <a href="/crm/analytics.php" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-purple-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Аналитика</h3>
                                <p class="text-sm text-gray-500">Отчеты и статистика</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>