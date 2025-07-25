<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;
use App\Auth;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            if (Auth::login($username, $password)) {
                header('Location: /admin/panel.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (Exception $e) {
            $error = 'Ошибка системы: ' . $e->getMessage();
        }
    } else {
        $error = 'Заполните все поля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm mx-auto">
        <div class="text-center mb-8">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 mx-auto mb-4" onerror="this.style.display='none'">
            <h1 class="text-xl font-medium text-gray-900 mb-1">Хром-KZ</h1>
            <p class="text-sm text-gray-500">Вход в систему</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 px-3 py-2 text-sm mb-6 border border-red-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Логин</label>
                <input type="text" name="username" required 
                       class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-900">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" required 
                       class="w-full px-3 py-2 text-sm border border-gray-300 focus:outline-none focus:border-gray-900">
            </div>
            
            <button type="submit" 
                    class="w-full bg-gray-900 text-white py-2 px-4 text-sm hover:bg-gray-800 focus:outline-none mt-6">
                Войти
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="/" class="text-gray-600 hover:text-gray-900 text-sm">← Главная</a>
        </div>
    </div>
</body>
</html>