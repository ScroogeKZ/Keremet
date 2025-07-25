<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\Client;

if (!isset($_SESSION['client_id'])) {
    header('Location: /client/login.php');
    exit;
}

$db = \Database::getInstance()->getConnection();
$clientModel = new Client();

// Получаем информацию о клиенте
$client = $clientModel->findById($_SESSION['client_id']);
if (!$client) {
    session_destroy();
    header('Location: /client/login.php');
    exit;
}

// Получаем заказы клиента
$stmt = $db->prepare("
    SELECT so.*, 
           CASE 
               WHEN so.status = 'pending' THEN 'Ожидает обработки'
               WHEN so.status = 'in_progress' THEN 'В пути'
               WHEN so.status = 'completed' THEN 'Доставлено'
               WHEN so.status = 'cancelled' THEN 'Отменен'
               ELSE so.status
           END as status_text,
           CASE 
               WHEN so.status = 'pending' THEN 'bg-yellow-100 text-yellow-800'
               WHEN so.status = 'in_progress' THEN 'bg-blue-100 text-blue-800'
               WHEN so.status = 'completed' THEN 'bg-green-100 text-green-800'
               WHEN so.status = 'cancelled' THEN 'bg-red-100 text-red-800'
               ELSE 'bg-gray-100 text-gray-800'
           END as status_class
    FROM shipment_orders so 
    WHERE so.contact_phone = ? 
    ORDER BY so.created_at DESC
    LIMIT 20
");
$stmt->execute([$client['phone']]);
$orders = $stmt->fetchAll();

// Статистика клиента
$stats = [
    'total' => count($orders),
    'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
    'in_progress' => count(array_filter($orders, fn($o) => $o['status'] === 'in_progress')),
    'completed' => count(array_filter($orders, fn($o) => $o['status'] === 'completed'))
];

// Получаем последние 5 заказов для быстрого просмотра
$recentOrders = array_slice($orders, 0, 5);

// Подсчитываем общую стоимость доставок
$totalCost = array_sum(array_column($orders, 'shipping_cost'));

// Активная вкладка
$activeTab = $_GET['tab'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .tab-active { border-bottom: 2px solid #3b82f6; color: #3b82f6; }
        .card-shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Хром-KZ</h1>
                        <p class="text-xs text-gray-500">Личный кабинет</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="hidden md:flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($client['phone']) ?></p>
                            <p class="text-xs text-gray-500">Клиент</p>
                        </div>
                    </div>
                    <a href="/client/logout.php" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Выход
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Navigation Tabs -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex space-x-8">
                <a href="?tab=overview" class="py-4 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Обзор
                </a>
                <a href="?tab=orders" class="py-4 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'orders' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Мои заказы
                </a>
                <a href="?tab=tracking" class="py-4 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'tracking' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Отследить заказ
                </a>
                <a href="?tab=profile" class="py-4 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Профиль
                </a>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($activeTab === 'overview'): ?>
        <!-- Overview Tab -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Добро пожаловать в личный кабинет</h2>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Всего заказов</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= $stats['total'] ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">В ожидании</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= $stats['pending'] ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">В пути</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= $stats['in_progress'] ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Доставлено</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= $stats['completed'] ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Быстрые действия</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="/astana.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Создать заказ в Астане
                    </a>
                    <a href="/regional.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Региональная доставка
                    </a>
                    <a href="?tab=tracking" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Отследить заказ
                    </a>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Последние заказы</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if (empty($recentOrders)): ?>
                        <div class="px-6 py-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"></path>
                            </svg>
                            <p class="mt-4 text-gray-500">У вас пока нет заказов</p>
                            <div class="mt-6">
                                <a href="/astana.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Создать первый заказ
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                Заказ KZ<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                            </p>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $order['status_class'] ?>">
                                                <?= htmlspecialchars($order['status_text']) ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?= htmlspecialchars($order['cargo_type']) ?> • <?= htmlspecialchars($order['weight']) ?> кг
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($order['shipping_cost']): ?>
                                            <span class="text-sm font-medium text-gray-900"><?= number_format($order['shipping_cost']) ?> ₸</span>
                                        <?php endif; ?>
                                        <a href="/tracking.php?id=KZ<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Отследить
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="px-6 py-4 bg-gray-50">
                            <a href="?tab=orders" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Показать все заказы →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($activeTab === 'orders'): ?>
        <!-- Orders Tab -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Мои заказы</h2>
                <div class="flex space-x-3">
                    <a href="/astana.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Новый заказ
                    </a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="bg-white shadow rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">У вас пока нет заказов</h3>
                    <p class="mt-2 text-gray-500">Создайте свой первый заказ для доставки груза.</p>
                    <div class="mt-6">
                        <a href="/astana.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Создать заказ
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Заказ
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Груз
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Маршрут
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Статус
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Стоимость
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Дата
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $order['order_type'] === 'astana' ? 'Астана' : 'Межгород' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($order['cargo_type']) ?>
                                <?php if ($order['weight']): ?>
                                    <br><span class="text-xs text-gray-500"><?= $order['weight'] ?> кг</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($order['pickup_address']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    <?php if ($order['status'] === 'new'): ?>bg-yellow-100 text-yellow-800
                                    <?php elseif ($order['status'] === 'processing'): ?>bg-blue-100 text-blue-800
                                    <?php elseif ($order['status'] === 'completed'): ?>bg-green-100 text-green-800
                                    <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                                    <?= $order['status_text'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="showOrderDetails(<?= $order['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-700">
                                    Подробнее
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                У вас пока нет заказов
                                <div class="mt-2">
                                    <a href="/astana.php" class="text-blue-600 hover:text-blue-700">Создать первый заказ</a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium">Детали заказа</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showOrderDetails(orderId) {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('orderDetails').innerHTML = '<div class="text-center py-4">Загрузка...</div>';
            
            // Here you would make an AJAX request to get order details
            // For now, we'll show a placeholder
            setTimeout(() => {
                document.getElementById('orderDetails').innerHTML = `
                    <div class="space-y-4">
                        <div><strong>Заказ:</strong> #${orderId}</div>
                        <div><strong>Статус:</strong> В обработке</div>
                        <div><strong>Трекинг:</strong> Заказ принят в обработку</div>
                        <div class="mt-6">
                            <div class="text-sm font-medium text-gray-700 mb-2">История статусов:</div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Заказ создан</span>
                                    <span class="text-gray-500">${new Date().toLocaleDateString('ru-RU')}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Принят в обработку</span>
                                    <span class="text-gray-500">${new Date().toLocaleDateString('ru-RU')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        }
        
        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
        
        function calculateCost() {
            alert('Калькулятор стоимости доставки (в разработке)');
        }
    </script>
</body>
</html>