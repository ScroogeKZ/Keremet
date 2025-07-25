<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\ShipmentOrder;
use App\Models\Client;

// Check if client is logged in
if (!isset($_SESSION['client_logged_in']) || !$_SESSION['client_logged_in']) {
    header('Location: /client/login.php');
    exit;
}

$clientId = $_SESSION['client_id'];
$clientName = $_SESSION['client_name'];

// Get client's orders
try {
    $shipmentOrder = new ShipmentOrder();
    $orders = $shipmentOrder->getByClientPhone($_SESSION['client_phone']);
} catch (Exception $e) {
    $orders = [];
    $error = "Ошибка загрузки заказов: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-new { @apply bg-blue-100 text-blue-800; }
        .status-processing { @apply bg-yellow-100 text-yellow-800; }
        .status-completed { @apply bg-green-100 text-green-800; }
        .status-cancelled { @apply bg-red-100 text-red-800; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Личный кабинет</h1>
                        <p class="text-sm text-gray-600">Добро пожаловать, <?= htmlspecialchars($clientName) ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-900">На главную</a>
                    <a href="/client/logout.php" class="bg-gray-600 text-white px-4 py-2 text-sm hover:bg-gray-700">Выйти</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="/astana.php" class="bg-white p-6 border border-gray-200 hover:border-blue-300 transition-colors">
                <h3 class="font-medium text-gray-900 mb-2">Новый заказ по Астане</h3>
                <p class="text-sm text-gray-600">Оформить доставку в пределах города</p>
            </a>
            <a href="/regional.php" class="bg-white p-6 border border-gray-200 hover:border-blue-300 transition-colors">
                <h3 class="font-medium text-gray-900 mb-2">Межгородская доставка</h3>
                <p class="text-sm text-gray-600">Отправить груз в другой город</p>
            </a>
            <a href="/tracking.php" class="bg-white p-6 border border-gray-200 hover:border-blue-300 transition-colors">
                <h3 class="font-medium text-gray-900 mb-2">Отследить заказ</h3>
                <p class="text-sm text-gray-600">Проверить статус доставки</p>
            </a>
        </div>

        <!-- Orders Section -->
        <div class="bg-white border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Мои заказы</h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="px-6 py-4 bg-red-50 border-b border-red-200 text-red-800">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="px-6 py-12 text-center">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Пока нет заказов</h3>
                    <p class="text-gray-600 mb-6">Создайте первый заказ, используя ссылки выше</p>
                    <a href="/astana.php" class="bg-blue-600 text-white px-6 py-3 hover:bg-blue-700">Создать заказ</a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <h3 class="font-medium text-gray-900">Заказ №<?= $order['id'] ?></h3>
                                    <span class="px-2 py-1 text-xs font-medium rounded status-<?= $order['status'] ?>">
                                        <?php
                                        $statusText = match($order['status']) {
                                            'new' => 'Новый',
                                            'processing' => 'В обработке',
                                            'completed' => 'Завершен',
                                            'cancelled' => 'Отменен',
                                            default => $order['status']
                                        };
                                        echo $statusText;
                                        ?>
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?= date('d.m.Y', strtotime($order['created_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Откуда: <span class="text-gray-900"><?= htmlspecialchars($order['pickup_address']) ?></span></p>
                                    <?php if ($order['delivery_address']): ?>
                                        <p class="text-gray-600">Куда: <span class="text-gray-900"><?= htmlspecialchars($order['delivery_address']) ?></span></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-gray-600">Груз: <span class="text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></span></p>
                                    <?php if ($order['weight']): ?>
                                        <p class="text-gray-600">Вес: <span class="text-gray-900"><?= $order['weight'] ?> кг</span></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 flex space-x-3">
                                <a href="/tracking.php?order=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Отследить
                                </a>
                                <?php if ($order['status'] === 'new'): ?>
                                    <span class="text-gray-300">|</span>
                                    <button onclick="cancelOrder(<?= $order['id'] ?>)" class="text-red-600 hover:text-red-800 text-sm">
                                        Отменить
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact Info -->
        <div class="mt-8 bg-blue-50 border border-blue-200 p-6">
            <h3 class="font-medium text-blue-900 mb-2">Нужна помощь?</h3>
            <p class="text-blue-800 text-sm mb-3">Свяжитесь с нашей службой поддержки</p>
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 text-sm">
                <span class="text-blue-800">📞 +7 (7172) 123-456</span>
                <span class="text-blue-800">✉️ support@chrome-kz.com</span>
                <span class="text-blue-800">🕐 Пн-Пт: 9:00-18:00</span>
            </div>
        </div>
    </div>

    <script>
        function cancelOrder(orderId) {
            if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
                fetch('/api/orders.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: orderId,
                        status: 'cancelled'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при отмене заказа');
                    }
                })
                .catch(error => {
                    alert('Ошибка при отмене заказа');
                });
            }
        }
    </script>
</body>
</html>