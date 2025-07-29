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

// Get delivery objects (unique addresses from orders)
try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Filter query based on search parameters
    $searchFilter = '';
    $statusFilter = '';
    $params = array();
    
    if (!empty($_GET['search'])) {
        $searchFilter = " AND pickup_address LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    $query = "
        SELECT 
            pickup_address as address,
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            MAX(created_at) as last_delivery,
            CASE WHEN COUNT(*) >= 5 THEN 'active' ELSE 'inactive' END as status
        FROM shipment_orders 
        WHERE pickup_address IS NOT NULL AND pickup_address != '' {$searchFilter}
        GROUP BY pickup_address
        ORDER BY total_orders DESC
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $deliveryObjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $objectStats = [
        'total_objects' => count($deliveryObjects),
        'active_objects' => count(array_filter($deliveryObjects, fn($obj) => $obj['status'] === 'active')),
        'new_this_month' => count(array_filter($deliveryObjects, fn($obj) => 
            strtotime($obj['last_delivery']) >= strtotime('first day of this month')
        ))
    ];
    
} catch (Exception $e) {
    $deliveryObjects = [];
    $objectStats = ['total_objects' => 0, 'active_objects' => 0, 'new_this_month' => 0];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список объектов - CRM</title>
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
                <a href="/crm/orders.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Заказы' : ''">
                    <i class="fas fa-box w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Заказы</span>
                </a>
                <a href="/crm/clients.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm group"
                   :title="!sidebarOpen ? 'Объекты' : ''">
                    <i class="fas fa-building w-5 text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="font-semibold transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Объекты</span>
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
                    <i class="fas fa-chart-line w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Отчеты</span>
                </a>
                <a href="/crm/analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Аналитика' : ''">
                    <i class="fas fa-chart-pie w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Аналитика</span>
                </a>
            </nav>
            
            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
                <div class="flex items-center" x-data="{ profileOpen: false }">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-gray-600 text-sm"></i>
                    </div>
                    <div class="flex-1 transition-opacity duration-300" 
                         :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                         x-show="sidebarOpen">
                        <p class="text-sm font-medium text-gray-900">Админ</p>
                        <p class="text-xs text-gray-500">admin@khrom-kz.com</p>
                    </div>
                    <button @click="profileOpen = !profileOpen" 
                            class="p-1 rounded hover:bg-gray-100 transition-opacity duration-300" 
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                            x-show="sidebarOpen">
                        <i class="fas fa-chevron-up text-gray-500 text-xs" :class="profileOpen ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                    </button>
                </div>
                
                <div x-show="profileOpen" x-transition class="mt-2 space-y-1">
                    <a href="/crm/logout.php" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sign-out-alt mr-2"></i>Выйти
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Список объектов</h1>
                    <p class="text-sm text-gray-600">Адреса и объекты доставки</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                        <i class="fas fa-globe mr-2"></i>На сайт
                    </a>
                    <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-search text-gray-600"></i>
                    </button>
                    <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-bell text-gray-600"></i>
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-building text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Всего объектов</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $objectStats['total_objects'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-map-marker-alt text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Активные</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $objectStats['active_objects'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-plus text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Новые за месяц</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $objectStats['new_this_month'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
                    <form method="GET" class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-64">
                            <input type="text" name="search" 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   placeholder="Поиск по адресу объекта..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <select name="is_verified" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Все объекты</option>
                            <option value="1" <?= ($_GET['is_verified'] ?? '') === '1' ? 'selected' : '' ?>>Активные</option>
                            <option value="0" <?= ($_GET['is_verified'] ?? '') === '0' ? 'selected' : '' ?>>Неактивные</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-search mr-2"></i>Поиск
                        </button>
                        <a href="/crm/clients.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-refresh mr-2"></i>Сбросить
                        </a>
                    </form>
                </div>

                <!-- Delivery Objects Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-xl border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Объекты доставки</h3>
                        <p class="mt-1 text-sm text-gray-600">Адреса с историей доставок и статистикой</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Адрес объекта</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Всего заказов</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Завершено</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последняя доставка</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($deliveryObjects)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-building text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">Объекты доставки не найдены</p>
                                        <p class="text-sm">Создайте заказы для появления объектов в списке</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($deliveryObjects as $object): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-building text-white text-sm"></i>
                                            </div>
                                            <div class="max-w-xs">
                                                <div class="text-sm font-medium text-gray-900 truncate" title="<?= htmlspecialchars($object['address']) ?>">
                                                    <?= htmlspecialchars($object['address']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $object['total_orders'] ?></div>
                                        <div class="text-xs text-gray-500">доставок</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-green-600"><?= $object['completed_orders'] ?></div>
                                        <div class="text-xs text-gray-500">успешно</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($object['status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Активный
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-minus-circle mr-1"></i>Обычный
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d.m.Y', strtotime($object['last_delivery'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="/crm/orders.php?search=<?= urlencode($object['address']) ?>" 
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="fas fa-list mr-1"></i>Заказы
                                        </a>
                                        <button class="text-green-600 hover:text-green-800 ml-2" 
                                                onclick="showObjectDetails('<?= htmlspecialchars($object['address']) ?>')">
                                            <i class="fas fa-info-circle mr-1"></i>Детали
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['verified'])): ?>
    <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition
         x-init="setTimeout(() => show = false, 3000)">
        <i class="fas fa-check-circle mr-2"></i>Объект успешно обновлен
    </div>
    <?php endif; ?>

    <script>
    function showObjectDetails(address) {
        alert('Подробная информация об объекте:\n\nАдрес: ' + address + '\n\nДля получения детальной информации перейдите в раздел "Заказы" и найдите заказы по этому адресу.');
    }
    </script>
</body>
</html>