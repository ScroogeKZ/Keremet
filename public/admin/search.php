<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$searchResults = [];
$searchQuery = '';

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchQuery = trim($_GET['q']);
    
    try {
        $pdo = \Database::getInstance()->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, order_type, status, pickup_address, destination_city, 
                   cargo_type, contact_name, contact_phone, shipping_cost, created_at
            FROM shipment_orders 
            WHERE 
                id::text ILIKE :query 
                OR contact_name ILIKE :query 
                OR contact_phone ILIKE :query 
                OR pickup_address ILIKE :query 
                OR destination_city ILIKE :query
                OR cargo_type ILIKE :query
            ORDER BY created_at DESC
            LIMIT 50
        ");
        
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->execute([':query' => $searchTerm]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $searchResults = [];
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск заказов - Хром-KZ Логистика</title>
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
                        <h1 class="text-lg font-medium text-gray-900">Поиск заказов</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-sm text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/cost_calculator.php" class="text-sm text-gray-600 hover:text-gray-900">Калькулятор</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Поисковая форма -->
        <div class="bg-white border border-gray-200 p-6 mb-6">
            <form method="GET" class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           placeholder="Поиск по номеру заказа, имени, телефону, адресу..."
                           class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                </div>
                <button type="submit" class="bg-gray-900 text-white px-6 py-2 text-sm hover:bg-gray-800">
                    Найти
                </button>
                <?php if ($searchQuery): ?>
                    <a href="/admin/search.php" class="text-gray-600 border border-gray-300 px-4 py-2 text-sm hover:border-gray-400">
                        Очистить
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($searchQuery): ?>
            <!-- Результаты поиска -->
            <div class="bg-white border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Результаты поиска
                        <?php if ($searchResults): ?>
                            <span class="text-sm text-gray-500 font-normal">
                                (найдено <?php echo count($searchResults); ?> заказов)
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>

                <?php if ($searchResults): ?>
                    <!-- Таблица результатов -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Контакт</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес забора</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Направление</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Стоимость</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($searchResults as $order): 
                                    $statusColors = [
                                        'new' => 'bg-red-100 text-red-800',
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800'
                                    ];
                                    
                                    $statusText = [
                                        'new' => 'Новый',
                                        'processing' => 'В работе',
                                        'completed' => 'Завершен'
                                    ];
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            <?php echo $order['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo $order['order_type'] === 'astana' ? 'Астана' : 'Межгород'; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $statusText[$order['status']] ?? $order['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($order['contact_name'] ?? ''); ?><br>
                                            <span class="text-gray-500"><?php echo htmlspecialchars($order['contact_phone'] ?? ''); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars(substr($order['pickup_address'] ?? '', 0, 40)); ?>
                                            <?php if (strlen($order['pickup_address'] ?? '') > 40): ?>...<?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($order['destination_city'] ?? 'Астана'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($order['shipping_cost']): ?>
                                                <?php echo number_format($order['shipping_cost'], 0, ',', ' '); ?> ₸
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <a href="/admin/panel.php?order_id=<?php echo $order['id']; ?>" 
                                               class="text-gray-600 hover:text-gray-900">Открыть</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- Пустой результат -->
                    <div class="px-6 py-8 text-center">
                        <div class="text-gray-500 text-sm">
                            По запросу "<?php echo htmlspecialchars($searchQuery); ?>" ничего не найдено
                        </div>
                        <div class="text-xs text-gray-400 mt-2">
                            Попробуйте изменить поисковый запрос или проверьте правильность ввода
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Подсказки для поиска -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Как искать заказы</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">По номеру заказа:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• 123 - найдет заказ №123</li>
                            <li>• 45 - найдет все заказы содержащие "45"</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">По контактным данным:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• Иван - найдет всех Иванов</li>
                            <li>• +7701 - найдет номера содержащие эти цифры</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">По адресу:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• Абая - найдет адреса на пр. Абая</li>
                            <li>• Алматы - найдет заказы в Алматы</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">По типу груза:</h4>
                        <ul class="space-y-1 text-gray-600">
                            <li>• Документы - найдет все документы</li>
                            <li>• Металл - найдет металлические грузы</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>