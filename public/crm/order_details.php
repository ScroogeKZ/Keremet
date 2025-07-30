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
$orderId = $_GET['id'] ?? null;
$success = false;
$error = '';

if (!$orderId) {
    header('Location: /crm/orders.php');
    exit;
}

// Get order details first
try {
    $order = $orderModel->getById($orderId);
    if (!$order) {
        header('Location: /crm/orders.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: /crm/orders.php');
    exit;
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    try {
        $updateData = [
            'pickup_address' => $_POST['pickup_address'] ?? '',
            'ready_time' => $_POST['ready_time'] ?? '',
            'contact_name' => $_POST['contact_name'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'cargo_type' => $_POST['cargo_type'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'dimensions' => $_POST['dimensions'] ?? '',
            'delivery_address' => $_POST['delivery_address'] ?? '',
            'recipient_contact' => $_POST['recipient_contact'] ?? '',
            'recipient_phone' => $_POST['recipient_phone'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'comment' => $_POST['comment'] ?? '',
            'status' => $_POST['status'] ?? 'new',
            'destination_city' => $_POST['destination_city'] ?? '',
            'delivery_method' => $_POST['delivery_method'] ?? '',
            'desired_arrival_date' => $_POST['desired_arrival_date'] ?? '',
            'shipping_cost' => $_POST['shipping_cost'] ?? null,
            'uploaded_files' => $order['uploaded_files'] ?? '' // Preserve existing uploaded files
        ];
        
        $result = $orderModel->updateOrder($orderId, $updateData);
        if ($result) {
            $success = true;
            // Refresh order data after update
            $order = $orderModel->getById($orderId);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$cargoTypes = [
    'Лифтовые порталы',
    'Т-образные профили', 
    'Металлические плинтуса',
    'Корзины для кондиционеров',
    'Декоративные решетки',
    'Перфорированные фасадные кассеты',
    'Стеклянные душевые кабины',
    'Зеркальные панно',
    'Рамы и багеты',
    'Козырьки',
    'Документы',
    'Образцы',
    'Другое'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #<?= $order['id'] ?> - CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen bg-gray-50" x-data="{ editMode: false }">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/crm/orders.php" class="text-blue-600 hover:text-blue-800 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i>Назад к заказам
                        </a>
                        <h1 class="text-xl font-semibold text-gray-900">Заказ #<?= $order['id'] ?></h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button @click="editMode = !editMode" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-edit mr-2"></i>
                            <span x-text="editMode ? 'Отменить' : 'Редактировать'"></span>
                        </button>
                        <a href="/crm/" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-tachometer-alt mr-2"></i>CRM
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    <i class="fas fa-check-circle mr-2"></i>Заказ успешно обновлен
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_order">
                
                <!-- Order Header Info -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Информация о заказе</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Создан: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                <?= $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональный' ?>
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                                <div x-show="!editMode" class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $order['status'] === 'new' ? 'bg-blue-100 text-blue-800' : 
                                            ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) ?>">
                                        <?= $order['status'] === 'new' ? 'Новый' : 
                                            ($order['status'] === 'processing' ? 'В работе' : 
                                            ($order['status'] === 'completed' ? 'Завершен' : 'Отменен')) ?>
                                    </span>
                                </div>
                                <select x-show="editMode" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В работе</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Завершен</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                </select>
                            </div>

                            <!-- Shipping Cost -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Стоимость доставки</label>
                                <div x-show="!editMode" class="text-sm text-gray-900">
                                    <?= number_format($order['shipping_cost'] ?? 0, 0, '.', ' ') ?> ₸
                                </div>
                                <input x-show="editMode" type="number" name="shipping_cost" 
                                       value="<?= htmlspecialchars($order['shipping_cost'] ?? '') ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Контактная информация</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Contact Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Имя отправителя</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['contact_name']) ?></div>
                                <input x-show="editMode" type="text" name="contact_name" 
                                       value="<?= htmlspecialchars($order['contact_name']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон отправителя</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['contact_phone']) ?></div>
                                <input x-show="editMode" type="tel" name="contact_phone" 
                                       value="<?= htmlspecialchars($order['contact_phone']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Recipient Contact -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Имя получателя</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['recipient_contact']) ?></div>
                                <input x-show="editMode" type="text" name="recipient_contact" 
                                       value="<?= htmlspecialchars($order['recipient_contact']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Recipient Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон получателя</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['recipient_phone']) ?></div>
                                <input x-show="editMode" type="tel" name="recipient_phone" 
                                       value="<?= htmlspecialchars($order['recipient_phone']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickup & Delivery Details -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Детали доставки</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Pickup Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Адрес получения</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['pickup_address']) ?></div>
                                <textarea x-show="editMode" name="pickup_address" rows="3"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($order['pickup_address']) ?></textarea>
                            </div>

                            <!-- Delivery Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Адрес доставки</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['delivery_address']) ?></div>
                                <textarea x-show="editMode" name="delivery_address" rows="3"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($order['delivery_address']) ?></textarea>
                            </div>

                            <!-- Ready Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Время готовности</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['ready_time']) ?></div>
                                <input x-show="editMode" type="time" name="ready_time" 
                                       value="<?= htmlspecialchars($order['ready_time']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <?php if ($order['order_type'] === 'regional'): ?>
                            <!-- Destination City -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Город назначения</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['destination_city']) ?></div>
                                <input x-show="editMode" type="text" name="destination_city" 
                                       value="<?= htmlspecialchars($order['destination_city']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Delivery Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Способ доставки</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['delivery_method']) ?></div>
                                <select x-show="editMode" name="delivery_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Выберите способ</option>
                                    <option value="pickup" <?= $order['delivery_method'] === 'pickup' ? 'selected' : '' ?>>Самовывоз</option>
                                    <option value="delivery" <?= $order['delivery_method'] === 'delivery' ? 'selected' : '' ?>>Доставка до адреса</option>
                                </select>
                            </div>

                            <!-- Desired Arrival Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Желаемая дата прибытия</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['desired_arrival_date']) ?></div>
                                <input x-show="editMode" type="date" name="desired_arrival_date" 
                                       value="<?= htmlspecialchars($order['desired_arrival_date']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cargo Information -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Информация о грузе</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Cargo Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Тип груза</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></div>
                                <select x-show="editMode" name="cargo_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <?php foreach ($cargoTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>" <?= $order['cargo_type'] === $type ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Weight -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Вес (кг)</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['weight']) ?> кг</div>
                                <input x-show="editMode" type="number" step="0.1" name="weight" 
                                       value="<?= htmlspecialchars($order['weight']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Dimensions -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Габариты</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['dimensions']) ?></div>
                                <input x-show="editMode" type="text" name="dimensions" 
                                       value="<?= htmlspecialchars($order['dimensions']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Photos -->
                <?php if (!empty($order['uploaded_files'])): ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            <i class="fas fa-images mr-2 text-blue-600"></i>Прикрепленные фотографии
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Фотографии, загруженные при создании заказа
                        </p>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php 
                            $photoFiles = explode(',', $order['uploaded_files']);
                            foreach ($photoFiles as $photoFile): 
                                if (!empty(trim($photoFile))):
                            ?>
                                <div class="relative group">
                                    <div class="aspect-w-3 aspect-h-2 rounded-lg overflow-hidden bg-gray-100">
                                        <img src="<?= htmlspecialchars(trim($photoFile)) ?>" 
                                             alt="Фотография заказа" 
                                             class="w-full h-48 object-cover object-center group-hover:opacity-75 transition-opacity duration-200 cursor-pointer"
                                             onclick="openPhotoModal('<?= htmlspecialchars(trim($photoFile)) ?>')">
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <p class="text-sm text-gray-600 truncate">
                                            <?= basename(trim($photoFile)) ?>
                                        </p>
                                        <a href="<?= htmlspecialchars(trim($photoFile)) ?>" 
                                           download
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            <i class="fas fa-images mr-2 text-gray-400"></i>Прикрепленные фотографии
                        </h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="text-center py-8">
                            <i class="fas fa-camera text-gray-300 text-4xl mb-4"></i>
                            <p class="text-gray-500">К этому заказу не прикреплены фотографии</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Information -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Дополнительная информация</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Примечания</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['notes']) ?></div>
                                <textarea x-show="editMode" name="notes" rows="3"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($order['notes']) ?></textarea>
                            </div>

                            <!-- Comment -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                                <div x-show="!editMode" class="text-sm text-gray-900"><?= htmlspecialchars($order['comment']) ?></div>
                                <textarea x-show="editMode" name="comment" rows="3"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($order['comment']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div x-show="editMode" class="flex justify-end space-x-3">
                    <button type="button" @click="editMode = false" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md">
                        Отменить
                    </button>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md">
                        <i class="fas fa-save mr-2"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg max-w-4xl max-h-full overflow-hidden">
            <div class="absolute top-4 right-4 z-10">
                <button onclick="closePhotoModal()" 
                        class="bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <img id="modalPhoto" src="" alt="Увеличенное фото" class="max-w-full max-h-screen object-contain">
        </div>
    </div>

    <script>
        function openPhotoModal(imageSrc) {
            document.getElementById('modalPhoto').src = imageSrc;
            document.getElementById('photoModal').classList.remove('hidden');
        }

        function closePhotoModal() {
            document.getElementById('photoModal').classList.add('hidden');
        }

        // Close modal when clicking outside the image
        document.getElementById('photoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePhotoModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
            }
        });
    </script>
</body>
</html>