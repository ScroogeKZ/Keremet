<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Setting;
use App\Models\User;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

// Initialize models
$settingModel = new Setting();
$userModel = new User();
$currentUserId = $_SESSION['user_id'] ?? 1;

// Handle form submissions
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $settingModel->updateBatch([
                    'admin_name' => ['value' => $_POST['admin_name'], 'category' => 'profile'],
                    'admin_email' => ['value' => $_POST['admin_email'], 'category' => 'profile'],
                    'admin_phone' => ['value' => $_POST['admin_phone'], 'category' => 'profile']
                ], $currentUserId);
                $message = 'Профиль успешно обновлен';
                break;
                
            case 'change_password':
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception('Пароли не совпадают');
                }
                if (strlen($_POST['new_password']) < 6) {
                    throw new Exception('Пароль должен содержать минимум 6 символов');
                }
                // Update password in users table
                $userModel->updatePassword($currentUserId, $_POST['new_password']);
                $message = 'Пароль успешно изменен';
                break;
                
            case 'update_notifications':
                $settingModel->updateBatch([
                    'email_notifications' => ['value' => isset($_POST['email_notifications']), 'type' => 'boolean', 'category' => 'notifications'],
                    'telegram_notifications' => ['value' => isset($_POST['telegram_notifications']), 'type' => 'boolean', 'category' => 'notifications'],
                    'sms_notifications' => ['value' => isset($_POST['sms_notifications']), 'type' => 'boolean', 'category' => 'notifications'],
                    'telegram_bot_token' => ['value' => $_POST['telegram_bot_token'], 'category' => 'notifications'],
                    'telegram_chat_id' => ['value' => $_POST['telegram_chat_id'], 'category' => 'notifications']
                ], $currentUserId);
                $message = 'Настройки уведомлений обновлены';
                break;
                
            case 'update_system':
                $settingModel->updateBatch([
                    'company_name' => ['value' => $_POST['company_name'], 'category' => 'system'],
                    'company_address' => ['value' => $_POST['company_address'], 'category' => 'system'],
                    'company_phone' => ['value' => $_POST['company_phone'], 'category' => 'system'],
                    'timezone' => ['value' => $_POST['timezone'], 'category' => 'system'],
                    'currency' => ['value' => $_POST['currency'], 'category' => 'system'],
                    'working_hours' => ['value' => $_POST['working_hours'], 'category' => 'system']
                ], $currentUserId);
                $message = 'Системные настройки обновлены';
                break;
                
            case 'update_security':
                $settingModel->updateBatch([
                    'session_timeout' => ['value' => $_POST['session_timeout'], 'type' => 'integer', 'category' => 'security'],
                    'max_login_attempts' => ['value' => $_POST['max_login_attempts'], 'type' => 'integer', 'category' => 'security'],
                    'password_min_length' => ['value' => $_POST['password_min_length'], 'type' => 'integer', 'category' => 'security'],
                    'two_factor_auth' => ['value' => isset($_POST['two_factor_auth']), 'type' => 'boolean', 'category' => 'security']
                ], $currentUserId);
                $message = 'Настройки безопасности обновлены';
                break;
                
            case 'update_pricing':
                $settingModel->updateBatch([
                    'base_delivery_price' => ['value' => $_POST['base_delivery_price'], 'type' => 'integer', 'category' => 'pricing'],
                    'price_per_kg' => ['value' => $_POST['price_per_kg'], 'type' => 'integer', 'category' => 'pricing'],
                    'urgent_delivery_multiplier' => ['value' => $_POST['urgent_delivery_multiplier'], 'category' => 'pricing']
                ], $currentUserId);
                $message = 'Настройки тарифов обновлены';
                break;
                
            case 'test_telegram':
                $result = $settingModel->testTelegramConnection();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                break;
        }
    } catch (Exception $e) {
        $message = 'Ошибка: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current settings
$settings = $settingModel->getFormSettings();

// Get current user info  
$currentUser = $_SESSION['admin_user'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, activeTab: 'profile' }">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-white shadow-lg transition-all duration-300 flex flex-col" 
             :class="sidebarOpen ? 'w-64' : 'w-16'">
            <!-- Logo -->
            <div class="flex items-center px-4 py-6 border-b border-gray-200">
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
                <a href="/crm/clients.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-lg transition-all duration-200 group"
                   :title="!sidebarOpen ? 'Пользователи' : ''">
                    <i class="fas fa-users w-5 text-gray-500 group-hover:text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Пользователи</span>
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
                <a href="/crm/settings.php" class="flex items-center px-4 py-3 text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 border-r-4 border-blue-500 rounded-lg shadow-sm group"
                   :title="!sidebarOpen ? 'Настройки' : ''">
                    <i class="fas fa-cog w-5 text-blue-600" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i>
                    <span class="font-semibold transition-opacity duration-300" 
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                          x-show="sidebarOpen">Настройки</span>
                </a>
            </nav>

            <!-- User Profile -->
            <div class="mt-auto px-4 py-4 border-t border-gray-200" x-data="{ profileOpen: false }">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="transition-opacity duration-300" 
                         :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                         x-show="sidebarOpen">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser) ?></p>
                        <p class="text-xs text-gray-500">Администратор</p>
                    </div>
                    <button @click="profileOpen = !profileOpen" 
                            class="ml-auto p-1 rounded hover:bg-gray-100 transition-colors duration-200"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0'" 
                            x-show="sidebarOpen">
                        <i class="fas fa-chevron-up text-xs text-gray-400" :class="profileOpen ? 'rotate-180' : ''"></i>
                    </button>
                </div>
                
                <!-- Profile Dropdown -->
                <div class="mt-2 space-y-1 transition-all duration-200" 
                     x-show="profileOpen && sidebarOpen" 
                     x-transition>
                    <a href="/crm/logout.php" class="flex items-center px-2 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">
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
                    <h1 class="text-2xl font-bold text-gray-900">Настройки</h1>
                    <p class="text-sm text-gray-600">Управление системными настройками и профилем</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-auto p-6">
                <?php if (isset($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                    <div class="flex">
                        <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6">
                            <button @click="activeTab = 'profile'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-user mr-2"></i>Профиль
                            </button>
                            <button @click="activeTab = 'notifications'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-bell mr-2"></i>Уведомления
                            </button>
                            <button @click="activeTab = 'system'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'system' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-cogs mr-2"></i>Система
                            </button>
                            <button @click="activeTab = 'backup'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'backup' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-database mr-2"></i>Резервные копии
                            </button>
                            <button @click="activeTab = 'security'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-shield-alt mr-2"></i>Безопасность
                            </button>
                            <button @click="activeTab = 'pricing'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'pricing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-calculator mr-2"></i>Тарифы
                            </button>
                            <button @click="activeTab = 'templates'" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                    :class="activeTab === 'templates' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                <i class="fas fa-file-alt mr-2"></i>Шаблоны
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Profile Settings -->
                        <div x-show="activeTab === 'profile'" x-transition>
                            <form method="POST" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Имя пользователя</label>
                                        <input type="text" name="username" value="<?= htmlspecialchars($currentUser) ?>" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" value="admin@hrom-kz.com"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Новый пароль</label>
                                        <input type="password" name="new_password" placeholder="Оставьте пустым для сохранения"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Подтверждение пароля</label>
                                        <input type="password" name="confirm_password" placeholder="Подтвердите новый пароль"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" name="update_profile" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        <i class="fas fa-save mr-2"></i>Сохранить изменения
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Notification Settings -->
                        <div x-show="activeTab === 'notifications'" x-transition>
                            <form method="POST" class="space-y-6">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between py-4 border-b border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Email уведомления о новых заказах</h3>
                                            <p class="text-sm text-gray-500">Получать уведомления на email при создании заказов</p>
                                        </div>
                                        <input type="checkbox" name="email_orders" checked 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex items-center justify-between py-4 border-b border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Telegram уведомления</h3>
                                            <p class="text-sm text-gray-500">Отправлять уведомления в Telegram бот</p>
                                        </div>
                                        <input type="checkbox" name="telegram_notifications" checked
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex items-center justify-between py-4 border-b border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Уведомления об изменении статуса</h3>
                                            <p class="text-sm text-gray-500">Уведомлять об изменениях статуса заказов</p>
                                        </div>
                                        <input type="checkbox" name="status_notifications" checked
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex items-center justify-between py-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Ежедневные отчеты</h3>
                                            <p class="text-sm text-gray-500">Получать ежедневную сводку по заказам</p>
                                        </div>
                                        <input type="checkbox" name="daily_reports"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" name="update_notifications" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        <i class="fas fa-save mr-2"></i>Сохранить настройки
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- System Settings -->
                        <div x-show="activeTab === 'system'" x-transition>
                            <?php if (isset($message) && isset($_POST['update_system'])): ?>
                            <div class="mb-4 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?>">
                                <div class="flex items-center">
                                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                                    <?= htmlspecialchars($message) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <form method="POST" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Название компании</label>
                                        <input type="text" name="company_name" 
                                               value="<?= htmlspecialchars($_SESSION['app_settings']['system']['company_name']) ?>"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Телефон поддержки</label>
                                        <input type="text" name="support_phone" value="+7 777 123 45 67"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email поддержки</label>
                                        <input type="email" name="support_email" 
                                               value="<?= htmlspecialchars($_SESSION['app_settings']['system']['support_email']) ?>"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Часовой пояс</label>
                                        <select name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="Asia/Almaty" selected>Алматы (UTC+6)</option>
                                            <option value="Asia/Astana">Нур-Султан (UTC+6)</option>
                                            <option value="Europe/Moscow">Москва (UTC+3)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Валюта по умолчанию</label>
                                        <select name="default_currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="KZT" selected>Тенге (₸)</option>
                                            <option value="USD">Доллар США ($)</option>
                                            <option value="EUR">Евро (€)</option>
                                            <option value="RUB">Рубль (₽)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Автоудаление заказов (дни)</label>
                                        <input type="number" name="auto_delete_days" 
                                               value="<?= $_SESSION['app_settings']['system']['auto_delete_days'] ?>" 
                                               min="1" max="9999"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Интеграции</h3>
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-500 mr-2">Telegram статус:</span>
                                            <?php 
                                            $telegram_configured = !empty($_SESSION['app_settings']['system']['telegram_token']) && 
                                                                   !empty($_SESSION['app_settings']['system']['telegram_chat_id']);
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $telegram_configured ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <i class="fas <?= $telegram_configured ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                                <?= $telegram_configured ? 'Настроен' : 'Не настроен' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Telegram Bot Token</label>
                                            <input type="password" name="telegram_token" id="telegram_token" 
                                                   value="<?= htmlspecialchars($_SESSION['app_settings']['system']['telegram_token']) ?>"
                                                   placeholder="Введите токен бота (например: 123456789:ABCdef...)"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <p class="text-xs text-gray-500 mt-1">Получите токен у @BotFather в Telegram</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Chat ID для уведомлений</label>
                                            <input type="text" name="telegram_chat_id" id="telegram_chat_id"
                                                   value="<?= htmlspecialchars($_SESSION['app_settings']['system']['telegram_chat_id']) ?>"
                                                   placeholder="-1001234567890 или @username"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <p class="text-xs text-gray-500 mt-1">ID чата или группы для получения уведомлений</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <button type="button" onclick="testTelegramConnection()"
                                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                                        <i class="fas fa-paper-plane mr-2"></i>Тест Telegram
                                    </button>
                                    <button type="submit" name="update_system" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        <i class="fas fa-save mr-2"></i>Сохранить настройки
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Backup Settings -->
                        <div x-show="activeTab === 'backup'" x-transition>
                            <div class="space-y-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-blue-800">🗄️ Резервные копии базы данных</h3>
                                    <p class="text-sm text-blue-700 mt-1">Управление созданием и восстановлением резервных копий</p>
                                </div>

                                <div class="border border-gray-200 rounded-lg p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Создание резервной копии</h3>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-600">Создать полную резервную копию базы данных</p>
                                            <p class="text-xs text-gray-500 mt-1">Включает: заказы, пользователи, настройки</p>
                                        </div>
                                        <button onclick="createBackup()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                            <i class="fas fa-download mr-2"></i>Создать бэкап
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div x-show="activeTab === 'security'" x-transition>
                            <form method="POST" class="space-y-6">
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-red-800">🔒 Настройки безопасности</h3>
                                    <p class="text-sm text-red-700 mt-1">Управление доступом и безопасностью системы</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Время сессии (минуты)</label>
                                        <input type="number" name="session_timeout" value="480" min="5" max="1440"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Попыток входа до блокировки</label>
                                        <input type="number" name="max_login_attempts" value="5" min="1" max="10"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between py-4 border-b border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Двухфакторная аутентификация</h3>
                                            <p class="text-sm text-gray-500">Дополнительная защита учетной записи</p>
                                        </div>
                                        <input type="checkbox" name="enable_2fa" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex items-center justify-between py-4 border-b border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Логирование входов</h3>
                                            <p class="text-sm text-gray-500">Ведение журнала попыток входа</p>
                                        </div>
                                        <input type="checkbox" name="log_logins" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" name="update_security" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                        <i class="fas fa-shield-alt mr-2"></i>Сохранить настройки безопасности
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Pricing Settings -->
                        <div x-show="activeTab === 'pricing'" x-transition>
                            <form method="POST" class="space-y-6">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-green-800">💰 Тарифы доставки</h3>
                                    <p class="text-sm text-green-700 mt-1">Настройка цен на доставку по городам и типам груза</p>
                                </div>

                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Базовые тарифы</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Астана (базовая цена)</label>
                                            <input type="number" name="astana_base_rate" value="2000" min="0"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Тенге за доставку</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Региональная доставка</label>
                                            <input type="number" name="regional_base_rate" value="5000" min="0"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Тенге за доставку</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Срочная доставка (+)</label>
                                            <input type="number" name="urgent_multiplier" value="1.5" min="1" max="3" step="0.1"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Коэффициент наценки</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" name="update_pricing" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        <i class="fas fa-calculator mr-2"></i>Сохранить тарифы
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Templates Settings -->
                        <div x-show="activeTab === 'templates'" x-transition>
                            <div class="space-y-6">
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <h3 class="text-sm font-medium text-purple-800">📧 Шаблоны уведомлений</h3>
                                    <p class="text-sm text-purple-700 mt-1">Настройка текстов email и SMS уведомлений</p>
                                </div>

                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Email шаблоны</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Новый заказ (для администратора)</label>
                                            <textarea name="email_new_order_admin" rows="4" 
                                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">Получен новый заказ #{order_id} от {client_name}.
Телефон: {client_phone}
Тип доставки: {order_type}
Адрес получения: {pickup_address}

Необходимо обработать заказ в CRM системе.</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Подтверждение заказа (для клиента)</label>
                                            <textarea name="email_order_confirmation" rows="4" 
                                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">Здравствуйте, {client_name}!

Ваш заказ #{order_id} принят в обработку.
Адрес получения: {pickup_address}
Примерная стоимость: {estimated_cost} тенге

Мы свяжемся с вами для уточнения деталей.

С уважением, команда Хром-KZ</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" name="update_templates" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                        <i class="fas fa-file-alt mr-2"></i>Сохранить шаблоны
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Function to show toast notifications
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Slide in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 5000);
    }

    function testTelegramConnection() {
        const tokenInput = document.getElementById('telegram_token');
        const chatIdInput = document.getElementById('telegram_chat_id');
        const button = event.target;
        
        const token = tokenInput.value.trim();
        const chatId = chatIdInput.value.trim();
        
        if (!token || !chatId) {
            showToast('Пожалуйста, заполните токен бота и Chat ID перед тестированием', 'error');
            return;
        }
        
        // Disable button during test
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Тестирование...';
        
        const formData = new FormData();
        formData.append('test_telegram', '1');
        formData.append('telegram_token', token);
        formData.append('telegram_chat_id', chatId);
        
        fetch('/crm/settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Ошибка при тестировании соединения', 'error');
        })
        .finally(() => {
            // Re-enable button
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Тест Telegram';
        });
    }

    function createBackup() {
        if (confirm('Создать резервную копию базы данных? Это может занять несколько минут.')) {
            alert('Создание резервной копии запущено. Вы получите уведомление по завершении.');
        }
    }
    </script>
</body>
</html>