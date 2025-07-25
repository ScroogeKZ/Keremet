<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = \Database::getInstance()->getConnection();
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($phone) || empty($password)) {
            throw new Exception('Заполните все обязательные поля');
        }
        
        // Генерируем код подтверждения
        $verificationCode = rand(100000, 999999);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO clients (name, email, phone, password_hash, verification_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $passwordHash, $verificationCode]);
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация клиента - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-8 border border-gray-200">
        <div class="text-center mb-8">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 mx-auto mb-4" onerror="this.style.display='none'">
            <h1 class="text-2xl font-medium text-gray-900">Регистрация</h1>
            <p class="text-gray-600 mt-2">Создайте личный кабинет для отслеживания заказов</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 mb-6">
                <p class="font-medium">Регистрация успешна!</p>
                <p class="text-sm mt-1">Код подтверждения отправлен на ваш телефон</p>
                <div class="mt-4">
                    <a href="/client/login.php" class="text-green-700 underline">Войти в личный кабинет</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Имя *</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон *</label>
                <input type="tel" name="phone" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       placeholder="+7 777 123 45 67"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Пароль *</label>
                <input type="password" name="password" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       minlength="6">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 px-4 text-lg hover:bg-blue-700 focus:outline-none">
                Зарегистрироваться
            </button>
        </form>

        <div class="mt-6 text-center space-y-3">
            <p class="text-gray-600">
                Уже есть аккаунт? 
                <a href="/client/login.php" class="text-blue-600 hover:text-blue-700">Войти</a>
            </p>
            <a href="/" class="text-gray-500 hover:text-gray-700 text-sm">← На главную</a>
        </div>
    </div>
</body>
</html>