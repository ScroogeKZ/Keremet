<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$message = '';
$error = '';

// Получаем ID заказа
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: /admin/orders.php');
    exit;
}

// Получаем данные заказа
$order = $orderModel->getById($orderId);
if (!$order) {
    $_SESSION['error'] = 'Заказ не найден';
    header('Location: /admin/orders.php');
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверяем, это быстрое обновление статуса или полное редактирование
        if (isset($_POST['status']) && count($_POST) === 1) {
            // Быстрое обновление только статуса
            $updateData = ['status' => $_POST['status']];
        } else {
            // Полное обновление всех полей
            $updateData = [
                'order_type' => $_POST['order_type'] ?? $order['order_type'],
                'pickup_address' => $_POST['pickup_address'] ?? $order['pickup_address'],
                'ready_time' => $_POST['ready_time'] ?? $order['ready_time'],
                'cargo_type' => $_POST['cargo_type'] ?? $order['cargo_type'],
                'weight' => $_POST['weight'] ?? $order['weight'],
                'dimensions' => $_POST['dimensions'] ?? $order['dimensions'],
                'contact_name' => $_POST['contact_name'] ?? $order['contact_name'],
                'contact_phone' => $_POST['contact_phone'] ?? $order['contact_phone'],
                'notes' => $_POST['notes'] ?? $order['notes'],
                'status' => $_POST['status'] ?? $order['status']
            ];

            // Поля для региональных заказов
            if (($_POST['order_type'] ?? $order['order_type']) === 'regional') {
                $updateData['pickup_city'] = $_POST['pickup_city'] ?? $order['pickup_city'];
                $updateData['destination_city'] = $_POST['destination_city'] ?? $order['destination_city'];
                $updateData['delivery_address'] = $_POST['delivery_address'] ?? $order['delivery_address'];
                $updateData['delivery_method'] = $_POST['delivery_method'] ?? $order['delivery_method'];
                $updateData['desired_arrival_date'] = $_POST['desired_arrival_date'] ?? $order['desired_arrival_date'];
            } else {
                // Очищаем региональные поля для астанинских заказов
                $updateData['pickup_city'] = null;
                $updateData['destination_city'] = null;
                $updateData['delivery_address'] = null;
                $updateData['delivery_method'] = null;
                $updateData['desired_arrival_date'] = null;
            }
        }

        $orderModel->update($orderId, $updateData);
        $message = 'Заказ успешно обновлен';
        
        // Обновляем данные заказа
        $order = $orderModel->getById($orderId);
        
    } catch (Exception $e) {
        $error = 'Ошибка обновления заказа: ' . $e->getMessage();
    }
}

$currentUser = Auth::getCurrentUser();

// Список городов Казахстана
$kazakhstanCities = [
    'Алматы', 'Нур-Султан', 'Шымкент', 'Актобе', 'Тараз', 'Павлодар', 'Усть-Каменогорск', 
    'Семей', 'Атырау', 'Костанай', 'Петропавловск', 'Орал', 'Темиртау', 'Актау', 'Кокшетау',
    'Талдыкорган', 'Экибастуз', 'Рудный', 'Жанаозен', 'Балхаш', 'Житикара', 'Каскелен',
    'Кентау', 'Лисаковск', 'Сарань', 'Степногорск', 'Шахтинск', 'Капшагай', 'Аксу',
    'Зыряновск', 'Кандыагаш', 'Жезказган', 'Форт-Шевченко', 'Приозерск', 'Макинск'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заказа #<?php echo $order['id']; ?> - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Редактирование заказа #<?php echo $order['id']; ?></h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-sm">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-900">Заказы</a>
                    <span class="text-gray-600"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-gray-600 hover:text-gray-900">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-xl font-medium text-gray-900 mb-1">Редактирование заказа #<?php echo $order['id']; ?></h1>
            <p class="text-sm text-gray-500">Изменение данных заказа</p>
        </div>

        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 text-sm mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Форма редактирования -->
        <div class="bg-white border border-gray-200 p-6">
            <form method="POST" class="space-y-6">
                <!-- Основная информация -->
                <div>
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Основная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Тип заказа</label>
                            <select name="order_type" id="order_type" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400" onchange="toggleRegionalFields()">
                                <option value="astana" <?php echo $order['order_type'] === 'astana' ? 'selected' : ''; ?>>Астана</option>
                                <option value="regional" <?php echo $order['order_type'] === 'regional' ? 'selected' : ''; ?>>Регионы</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Статус заказа</label>
                            <select name="status" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                                <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>Новый</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В работе</option>
                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Контактная информация -->
                <div>
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Контактная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Имя контактного лица *</label>
                            <input type="text" name="contact_name" value="<?php echo htmlspecialchars($order['contact_name'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Телефон *</label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($order['contact_phone'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                    </div>
                </div>

                <!-- Информация о грузе -->
                <div>
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Информация о грузе</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Тип груза *</label>
                            <input type="text" name="cargo_type" value="<?php echo htmlspecialchars($order['cargo_type'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Вес *</label>
                            <input type="text" name="weight" value="<?php echo htmlspecialchars($order['weight'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Габариты *</label>
                            <input type="text" name="dimensions" value="<?php echo htmlspecialchars($order['dimensions'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Время готовности к отправке *</label>
                            <input type="time" name="ready_time" value="<?php echo htmlspecialchars($order['ready_time'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                        </div>
                    </div>
                </div>

                <!-- Адреса -->
                <div>
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Адреса</h2>
                    
                    <!-- Поля для региональных заказов -->
                    <div id="regional_fields" class="space-y-4 <?php echo $order['order_type'] !== 'regional' ? 'hidden' : ''; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Город отправления</label>
                                <select name="pickup_city" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                                    <option value="">Выберите город</option>
                                    <?php foreach ($kazakhstanCities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($order['pickup_city'] ?? '') === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Город назначения</label>
                                <select name="destination_city" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                                    <option value="">Выберите город</option>
                                    <?php foreach ($kazakhstanCities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($order['destination_city'] ?? '') === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Способ доставки</label>
                                <select name="delivery_method" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                                    <option value="">Выберите способ</option>
                                    <option value="door_to_door" <?php echo ($order['delivery_method'] ?? '') === 'door_to_door' ? 'selected' : ''; ?>>От двери до двери</option>
                                    <option value="terminal_to_terminal" <?php echo ($order['delivery_method'] ?? '') === 'terminal_to_terminal' ? 'selected' : ''; ?>>Терминал-терминал</option>
                                    <option value="door_to_terminal" <?php echo ($order['delivery_method'] ?? '') === 'door_to_terminal' ? 'selected' : ''; ?>>От двери до терминала</option>
                                    <option value="terminal_to_door" <?php echo ($order['delivery_method'] ?? '') === 'terminal_to_door' ? 'selected' : ''; ?>>От терминала до двери</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Желаемая дата прибытия</label>
                                <input type="date" name="desired_arrival_date" value="<?php echo htmlspecialchars($order['desired_arrival_date'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Обязательные поля адресов -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Адрес забора груза *</label>
                            <textarea name="pickup_address" required rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400"><?php echo htmlspecialchars($order['pickup_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div id="delivery_address_field" class="<?php echo $order['order_type'] !== 'regional' ? 'hidden' : ''; ?>">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Адрес доставки</label>
                            <textarea name="delivery_address" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Комментарии -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Дополнительные комментарии</label>
                    <textarea name="notes" rows="4" class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-400" placeholder="Дополнительная информация о заказе..."><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Кнопки действий -->
                <div class="flex justify-between pt-4">
                    <a href="/admin/panel.php" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 hover:border-gray-400">
                        Отменить
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm bg-gray-900 text-white hover:bg-gray-800">
                        Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleRegionalFields() {
            const orderType = document.getElementById('order_type').value;
            const regionalFields = document.getElementById('regional_fields');
            const deliveryAddressField = document.getElementById('delivery_address_field');
            
            if (orderType === 'regional') {
                regionalFields.classList.remove('hidden');
                deliveryAddressField.classList.remove('hidden');
            } else {
                regionalFields.classList.add('hidden');
                deliveryAddressField.classList.add('hidden');
            }
        }

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            toggleRegionalFields();
        });
    </script>
</body>
</html>