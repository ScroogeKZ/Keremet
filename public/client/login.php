<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = \Database::getInstance()->getConnection();
        
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Function to validate Kazakhstan phone numbers
        function validateKazakhstanPhone($phoneNumber) {
            $phone = preg_replace('/[^0-9+]/', '', $phoneNumber); // Remove all non-digits except +
            
            // Valid formats: +77xxxxxxxxx or 87xxxxxxxxx
            if (preg_match('/^\+77\d{9}$/', $phone) || preg_match('/^87\d{9}$/', $phone)) {
                return true;
            }
            return false;
        }
        
        if (empty($phone) || empty($password)) {
            throw new Exception('Заполните все поля');
        }
        
        if (!validateKazakhstanPhone($phone)) {
            throw new Exception('Неверный формат номера телефона. Используйте формат +77xxxxxxxxx или 87xxxxxxxxx');
        }
        
        $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
        $stmt->execute([$phone]);
        $client = $stmt->fetch();
        
        if (!$client || !password_verify($password, $client['password_hash'])) {
            throw new Exception('Неверный телефон или пароль');
        }
        
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['name'];
        $_SESSION['client_phone'] = $client['phone'];
        
        header('Location: /client/dashboard.php');
        exit;
        
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
    <title>Вход в личный кабинет - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function validatePhone(input) {
            const phone = input.value;
            const phonePattern = /^(\+77|87)\d{9}$/;
            
            if (phone && !phonePattern.test(phone)) {
                input.setCustomValidity('Используйте формат +77xxxxxxxxx или 87xxxxxxxxx');
            } else {
                input.setCustomValidity('');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.querySelector('input[type="tel"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    validatePhone(this);
                });
            }
        });
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-8 border border-gray-200">
        <div class="text-center mb-8">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 mx-auto mb-4" onerror="this.style.display='none'">
            <h1 class="text-2xl font-medium text-gray-900">Личный кабинет</h1>
            <p class="text-gray-600 mt-2">Войдите для отслеживания заказов</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                <input type="tel" name="phone" required 
                       pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                <input type="password" name="password" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 px-4 text-lg hover:bg-blue-700 focus:outline-none">
                Войти
            </button>
        </form>

        <div class="mt-6 text-center space-y-3">
            <p class="text-gray-600">
                Нет аккаунта? 
                <a href="/client/register.php" class="text-blue-600 hover:text-blue-700">Зарегистрироваться</a>
            </p>
            <a href="/" class="text-gray-500 hover:text-gray-700 text-sm">← На главную</a>
        </div>
    </div>
</body>
</html>