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
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-2xl font-bold text-blue-600">Хром-KZ CRM</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/crm" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>Дашборд
                        </a>
                        <a href="/crm/orders.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-box mr-2"></i>Заказы
                        </a>
                        <a href="/crm/notifications.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-bell mr-2"></i>Уведомления
                        </a>
                        <a href="/crm/calendar.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-calendar mr-2"></i>Календарь
                        </a>
                        <a href="/crm/bulk_operations.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tasks mr-2"></i>Массовые операции
                        </a>
                        <a href="/crm/reports.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-file-alt mr-2"></i>Отчеты
                        </a>
                        <a href="/crm/analytics.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>Аналитика
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative" x-data="{ open: false }">
                        <button @click="open = !open" class="bg-white flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-user-circle text-2xl text-gray-400"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <a href="/crm/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Настройки</a>
                                <a href="/crm/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Выход</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Дашборд CRM</h2>
            <p class="mt-2 text-gray-600">Обзор всех операций логистики</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-box text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Всего заказов</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">В обработке</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= $stats['processing_orders'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Завершенные</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= $stats['completed_orders'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-purple-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Доход</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['total_revenue'] ?? 0, 0, '.', ' ') ?> ₸</dd>
                            </dl>
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
</body>
</html>