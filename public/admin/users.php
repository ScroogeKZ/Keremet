<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;
use App\Auth;

Auth::requireAuth();

$userModel = new User();
$success = '';
$error = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if ($username && $password) {
            try {
                $existingUser = $userModel->findByUsername($username);
                if ($existingUser) {
                    $error = 'Пользователь с таким именем уже существует';
                } else {
                    $userData = [
                        'username' => $username,
                        'password' => $password
                    ];
                    $newUser = $userModel->create($userData);
                    $success = 'Пользователь успешно создан';
                }
            } catch (Exception $e) {
                $error = 'Ошибка создания пользователя: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все поля';
        }
    }
}

// Get all users
$users = [];
try {
    $users = $userModel->getAll();
} catch (Exception $e) {
    $error = 'Ошибка загрузки пользователей: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Пользователи</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-sm text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/cost_calculator.php" class="text-sm text-gray-600 hover:text-gray-900">Калькулятор</a>
                    <a href="/admin/search.php" class="text-sm text-gray-600 hover:text-gray-900">Поиск</a>
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add User Form -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-base font-medium text-gray-900 mb-6">Добавить пользователя</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
                        <input type="text" 
                               name="username" 
                               required 
                               class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-900"
                               placeholder="Введите логин">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                        <input type="password" 
                               name="password" 
                               required 
                               class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-900"
                               placeholder="Введите пароль">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gray-900 text-white py-2 px-4 text-sm hover:bg-gray-800 focus:outline-none mt-6">
                        Создать пользователя
                    </button>
                </form>
            </div>

            <!-- Users List -->
            <div class="bg-white border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-medium text-gray-900">Список пользователей</h2>
                </div>
                
                <div class="p-6">
                    <?php if (empty($users)): ?>
                        <p class="text-gray-500 text-sm">Пользователи не найдены</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($users as $user): ?>
                                <div class="border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                Создан: <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            ID: <?php echo $user['id']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>