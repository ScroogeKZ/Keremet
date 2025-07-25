<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            $orderId = (int)$_POST['order_id'];
            $newStatus = $_POST['status'];
            try {
                $orderModel->updateStatus($orderId, $newStatus);
                $message = 'Статус заказа #' . $orderId . ' успешно обновлен';
            } catch (Exception $e) {
                $error = 'Ошибка обновления статуса: ' . $e->getMessage();
            }
            break;
            
        case 'delete_order':
            $orderId = (int)$_POST['order_id'];
            try {
                $orderModel->delete($orderId);
                $message = 'Заказ #' . $orderId . ' успешно удален';
            } catch (Exception $e) {
                $error = 'Ошибка удаления заказа: ' . $e->getMessage();
            }
            break;
    }
}

// Фильтры
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['order_type'])) $filters['order_type'] = $_GET['order_type'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];

// Пагинация
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$filters['limit'] = $limit;
$filters['offset'] = $offset;

$orders = $orderModel->getAll($filters);
$totalOrders = $orderModel->getCount(array_diff_key($filters, ['limit' => '', 'offset' => '']));
$totalPages = ceil($totalOrders / $limit);

// Просмотр конкретного заказа
$viewOrder = null;
if (!empty($_GET['view'])) {
    $viewOrderId = (int)$_GET['view'];
    $viewOrder = $orderModel->getById($viewOrderId);
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1e40af',
                        'primary-dark': '#1e3a8a',
                        'secondary': '#f59e0b',
                        'accent': '#10b981'
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #3730a3 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="gradient-bg p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-transparent">Хром-KZ Админ</h1>
                        <p class="text-sm text-gray-600 font-medium">Управление заказами</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/" class="text-gray-600 hover:text-primary transition-colors">Дашборд</a>
                    <a href="/admin/settings.php" class="text-gray-600 hover:text-primary transition-colors">Настройки</a>
                    <span class="text-gray-600">Добро пожаловать, <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></span>
                    <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Заголовок -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Управление заказами</h1>
            <p class="text-gray-600">Просмотр, редактирование и управление всеми заказами</p>
            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    💡 <strong>Совет:</strong> Нажмите на любой заказ в таблице, чтобы открыть страницу редактирования
                </p>
            </div>
        </div>

        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Фильтры -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Фильтры</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Все статусы</option>
                        <option value="new" <?php echo ($_GET['status'] ?? '') === 'new' ? 'selected' : ''; ?>>Новый</option>
                        <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>В работе</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                        <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип заказа</label>
                    <select name="order_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Все типы</option>
                        <option value="astana" <?php echo ($_GET['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>Астана</option>
                        <option value="regional" <?php echo ($_GET['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>Регионы</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                    <input type="text" name="search" placeholder="Имя, телефон, адрес..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата от</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Применить фильтры
                    </button>
                </div>
            </form>
        </div>

        <!-- Статистика по текущим фильтрам -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Результаты: <?php echo $totalOrders; ?> заказов</h2>
            <div class="flex flex-wrap gap-4">
                <a href="?" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Сбросить фильтры</a>
                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg">Страница <?php echo $page; ?> из <?php echo $totalPages; ?></span>
            </div>
        </div>

        <!-- Таблица заказов -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">ID</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Тип</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Клиент</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Телефон</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Груз</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Статус</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Дата</th>
                            <th class="text-center py-4 px-6 font-semibold text-gray-700">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 7h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zM10 4h4v3h-4V4zm10 17H4V9h16v12z"/>
                                    </svg>
                                    <p class="text-lg font-medium">Заказы не найдены</p>
                                    <p class="text-sm">Попробуйте изменить параметры фильтрации</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-blue-50 transition-colors cursor-pointer" onclick="window.location.href='/admin/edit_order.php?id=<?php echo $order['id']; ?>'">
                                    <td class="py-4 px-6 font-medium text-blue-600 hover:text-blue-800">
                                        <a href="/admin/edit_order.php?id=<?php echo $order['id']; ?>" class="font-semibold">#<?php echo $order['id']; ?></a>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo $order['order_type'] === 'astana' ? 'Астана' : 'Регионы'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($order['contact_name'] ?? 'Не указан'); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($order['contact_phone'] ?? 'Не указан'); ?></td>
                                    <td class="py-4 px-6">
                                        <div class="text-sm">
                                            <div class="font-medium"><?php echo htmlspecialchars(substr($order['cargo_type'] ?? '', 0, 30)); ?><?php echo strlen($order['cargo_type'] ?? '') > 30 ? '...' : ''; ?></div>
                                            <div class="text-gray-500"><?php echo htmlspecialchars($order['weight'] ?? ''); ?></div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6" onclick="event.stopPropagation()">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded-full border-0 cursor-pointer <?php
                                                $statusColors = [
                                                    'new' => 'bg-yellow-100 text-yellow-800',
                                                    'processing' => 'bg-blue-100 text-blue-800',
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800'
                                                ];
                                                echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>">
                                                <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>Новый</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В работе</option>
                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-600"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="py-4 px-6 text-center" onclick="event.stopPropagation()">
                                        <div class="flex justify-center space-x-2">
                                            <a href="/admin/edit_order.php?id=<?php echo $order['id']; ?>" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition-colors" title="Редактировать заказ">
                                                <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                </svg>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Вы уверены, что хотите удалить заказ #<?php echo $order['id']; ?>?')">
                                                <input type="hidden" name="action" value="delete_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition-colors" title="Удалить заказ">
                                                    <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9zM4 5a2 2 0 012-2h8a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 112 0v2a1 1 0 11-2 0V9zm4 0a1 1 0 112 0v2a1 1 0 11-2 0V9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>" class="px-3 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">Предыдущая</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>" class="px-3 py-2 <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> rounded-lg transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>" class="px-3 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">Следующая</a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно просмотра заказа -->
    <?php if ($viewOrder): ?>
        <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Заказ #<?php echo $viewOrder['id']; ?></h2>
                        <a href="/admin/orders.php" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</a>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Основная информация -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Основная информация</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-600">Тип заказа:</span>
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full <?php echo $viewOrder['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                        <?php echo $viewOrder['order_type'] === 'astana' ? 'Астана' : 'Регионы'; ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Статус:</span>
                                    <?php
                                    $statusColors = [
                                        'new' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusTexts = [
                                        'new' => 'Новый',
                                        'processing' => 'В работе',
                                        'completed' => 'Завершен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    ?>
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full <?php echo $statusColors[$viewOrder['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $statusTexts[$viewOrder['status']] ?? $viewOrder['status']; ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Дата создания:</span>
                                    <span class="ml-2 font-medium"><?php echo date('d.m.Y H:i', strtotime($viewOrder['created_at'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Последнее обновление:</span>
                                    <span class="ml-2 font-medium"><?php echo date('d.m.Y H:i', strtotime($viewOrder['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Контактная информация -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Контактная информация</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-600">Имя:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['contact_name'] ?? 'Не указано'); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Телефон:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['contact_phone'] ?? 'Не указан'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Информация о грузе -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Информация о грузе</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-600">Тип груза:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['cargo_type'] ?? 'Не указан'); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Вес:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['weight'] ?? 'Не указан'); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Габариты:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['dimensions'] ?? 'Не указаны'); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Время готовности:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['ready_time'] ?? 'Не указано'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Адреса -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Адреса</h3>
                            <div class="space-y-3">
                                <?php if (!empty($viewOrder['pickup_city'])): ?>
                                    <div>
                                        <span class="text-sm text-gray-600">Город отправления:</span>
                                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['pickup_city']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <span class="text-sm text-gray-600">Адрес забора:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['pickup_address'] ?? 'Не указан'); ?></span>
                                </div>
                                <?php if (!empty($viewOrder['destination_city'])): ?>
                                    <div>
                                        <span class="text-sm text-gray-600">Город назначения:</span>
                                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['destination_city']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($viewOrder['delivery_address'])): ?>
                                    <div>
                                        <span class="text-sm text-gray-600">Адрес доставки:</span>
                                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['delivery_address']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($viewOrder['delivery_method'])): ?>
                                    <div>
                                        <span class="text-sm text-gray-600">Способ доставки:</span>
                                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['delivery_method']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($viewOrder['desired_arrival_date'])): ?>
                                    <div>
                                        <span class="text-sm text-gray-600">Желаемая дата прибытия:</span>
                                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($viewOrder['desired_arrival_date']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Комментарии -->
                        <?php if (!empty($viewOrder['notes'])): ?>
                            <div class="lg:col-span-2">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Комментарии</h3>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($viewOrder['notes'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Действия -->
                    <div class="mt-6 flex justify-end space-x-4">
                        <a href="/admin/edit_order.php?id=<?php echo $viewOrder['id']; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            Редактировать заказ
                        </a>
                        <a href="/admin/orders.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            Закрыть
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>