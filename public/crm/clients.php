<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Client;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

$clientModel = new Client();

// Handle client actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify_client' && isset($_POST['client_id'])) {
        $clientModel->updateVerification($_POST['client_phone'], true);
        header('Location: /crm/clients.php?verified=1');
        exit;
    }
}

// Get clients with filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'is_verified' => $_GET['is_verified'] ?? ''
];

$clients = $clientModel->getAll($filters);
$clientStats = $clientModel->getStatistics();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Клиенты - CRM</title>
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
                   :title="!sidebarOpen ? 'Клиенты' : ''">
                    <i class="fas fa-users w-5 text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="font-semibold transition-opacity duration-300" 
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
                    <h1 class="text-2xl font-bold text-gray-900">Управление клиентами</h1>
                    <p class="text-sm text-gray-600">Личные кабинеты и регистрации клиентов</p>
                </div>
                <div class="flex items-center space-x-4">
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
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Всего клиентов</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $clientStats['total_clients'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Подтвержденные</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $clientStats['verified_clients'] ?></p>
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
                                <p class="text-2xl font-bold text-gray-900"><?= $clientStats['new_this_month'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
                    <form method="GET" class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-64">
                            <input type="text" name="search" 
                                   value="<?= htmlspecialchars($filters['search']) ?>"
                                   placeholder="Поиск по имени, телефону или email..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <select name="is_verified" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Все клиенты</option>
                            <option value="1" <?= $filters['is_verified'] === '1' ? 'selected' : '' ?>>Подтвержденные</option>
                            <option value="0" <?= $filters['is_verified'] === '0' ? 'selected' : '' ?>>Не подтвержденные</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-search mr-2"></i>Поиск
                        </button>
                        <a href="/crm/clients.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-refresh mr-2"></i>Сбросить
                        </a>
                    </form>
                </div>

                <!-- Clients Table -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Список клиентов</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Контакты</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Заказы</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Регистрация</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($clients as $client): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($client['name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= $client['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($client['phone']) ?></div>
                                        <?php if ($client['email']): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($client['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($client['is_verified']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Подтвержден
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Ожидает подтверждения
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="font-medium"><?= $client['total_orders'] ?></span> заказов
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= number_format($client['total_spent'], 0, '.', ' ') ?> ₸
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y', strtotime($client['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="/crm/orders.php?search=<?= urlencode($client['phone']) ?>" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                               title="Посмотреть заказы клиента">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!$client['is_verified']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="verify_client">
                                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                                <input type="hidden" name="client_phone" value="<?= $client['phone'] ?>">
                                                <button type="submit" 
                                                        class="text-green-600 hover:text-green-900 transition-colors duration-200"
                                                        title="Подтвердить клиента"
                                                        onclick="return confirm('Подтвердить клиента?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($clients)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Клиенты не найдены</h3>
                            <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
                        </div>
                        <?php endif; ?>
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
        <i class="fas fa-check-circle mr-2"></i>Клиент успешно подтвержден
    </div>
    <?php endif; ?>
</body>
</html>