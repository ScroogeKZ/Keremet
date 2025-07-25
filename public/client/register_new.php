<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\Client;
use App\VerificationService;

$error = '';
$success = '';
$step = $_GET['step'] ?? 'register';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'register') {
        // Шаг 1: Регистрация и отправка кода
        $phone = VerificationService::formatPhoneNumber($_POST['phone'] ?? '');
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            $error = 'Телефон и пароль обязательны для заполнения';
        } elseif (!VerificationService::validatePhone($phone)) {
            $error = 'Неверный формат номера телефона. Используйте формат +77xxxxxxxxx или 87xxxxxxxxx';
        } elseif (!empty($email) && !VerificationService::validateEmail($email)) {
            $error = 'Неверный формат email адреса';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен содержать минимум 6 символов';
        } elseif ($password !== $confirmPassword) {
            $error = 'Пароли не совпадают';
        } else {
            try {
                $clientModel = new Client();
                
                // Проверяем, существует ли уже пользователь
                $existingClient = $clientModel->findByPhone($phone);
                
                if ($existingClient) {
                    if ($existingClient['is_verified']) {
                        $error = 'Пользователь с таким номером телефона уже существует';
                    } else {
                        // Аккаунт существует, но не верифицирован - отправляем новый код
                        $code = VerificationService::generateCode();
                        $clientModel->setVerificationCode($phone, $code);
                        
                        if (VerificationService::sendVerificationCode($phone, $email, $code)) {
                            session_start();
                            $_SESSION['registration_phone'] = $phone;
                            $_SESSION['registration_email'] = $email;
                            header('Location: register_new.php?step=verify');
                            exit;
                        } else {
                            $error = 'Ошибка отправки кода верификации';
                        }
                    }
                } else {
                    // Создаем нового пользователя
                    $clientData = [
                        'phone' => $phone,
                        'email' => $email,
                        'password' => $password
                    ];
                    
                    $client = $clientModel->create($clientData);
                    
                    if ($client) {
                        // Генерируем и отправляем код верификации
                        $code = VerificationService::generateCode();
                        $clientModel->setVerificationCode($phone, $code);
                        
                        if (VerificationService::sendVerificationCode($phone, $email, $code)) {
                            session_start();
                            $_SESSION['registration_phone'] = $phone;
                            $_SESSION['registration_email'] = $email;
                            header('Location: register_new.php?step=verify');
                            exit;
                        } else {
                            $error = 'Ошибка отправки кода верификации';
                        }
                    } else {
                        $error = 'Ошибка при создании аккаунта';
                    }
                }
            } catch (Exception $e) {
                $error = 'Ошибка системы: ' . $e->getMessage();
            }
        }
    } elseif ($step === 'verify') {
        // Шаг 2: Верификация кода
        session_start();
        $phone = $_SESSION['registration_phone'] ?? '';
        $code = $_POST['verification_code'] ?? '';
        
        if (empty($code)) {
            $error = 'Введите код верификации';
        } elseif (strlen($code) !== 6 || !is_numeric($code)) {
            $error = 'Код должен содержать 6 цифр';
        } else {
            try {
                $clientModel = new Client();
                
                if ($clientModel->verifyCode($phone, $code)) {
                    unset($_SESSION['registration_phone']);
                    unset($_SESSION['registration_email']);
                    $success = 'Аккаунт успешно активирован! Теперь вы можете войти в систему.';
                    $step = 'complete';
                } else {
                    $error = 'Неверный или истекший код верификации';
                }
            } catch (Exception $e) {
                $error = 'Ошибка системы: ' . $e->getMessage();
            }
        }
    }
}

// Получаем данные для отображения
if ($step === 'verify') {
    session_start();
    $registrationPhone = $_SESSION['registration_phone'] ?? '';
    $registrationEmail = $_SESSION['registration_email'] ?? '';
    
    if (empty($registrationPhone)) {
        header('Location: register_new.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация с верификацией - Хром-KZ Логистика</title>
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
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-10 w-10 mx-auto mb-4" onerror="this.style.display='none'">
            <h1 class="text-2xl font-bold text-gray-900">Хром-KZ Логистика</h1>
            <?php if ($step === 'register'): ?>
                <p class="text-gray-600 mt-2">Создание аккаунта</p>
            <?php elseif ($step === 'verify'): ?>
                <p class="text-gray-600 mt-2">Подтверждение номера телефона</p>
            <?php else: ?>
                <p class="text-gray-600 mt-2">Регистрация завершена</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'register'): ?>
            <!-- Форма регистрации -->
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Номер телефона *</label>
                    <input type="tel" name="phone" required 
                           pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                           placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">На этот номер будет отправлен код подтверждения</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email (необязательно)</label>
                    <input type="email" name="email"
                           placeholder="your@email.com"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Для дублирования уведомлений</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Пароль *</label>
                    <input type="password" name="password" required 
                           placeholder="Минимум 6 символов"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите пароль *</label>
                    <input type="password" name="confirm_password" required
                           placeholder="Повторите пароль"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                    Создать аккаунт
                </button>
            </form>

        <?php elseif ($step === 'verify'): ?>
            <!-- Форма верификации -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Введите код подтверждения</h2>
                <p class="text-gray-600">
                    Мы отправили 6-значный код на номер<br>
                    <strong><?= htmlspecialchars($registrationPhone ?? '') ?></strong>
                    <?php if (!empty($registrationEmail)): ?>
                        <br>и на email <strong><?= htmlspecialchars($registrationEmail) ?></strong>
                    <?php endif; ?>
                </p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-center">Код подтверждения</label>
                    <input type="text" name="verification_code" required 
                           maxlength="6" pattern="[0-9]{6}"
                           placeholder="123456"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-2xl font-mono tracking-widest"
                           autocomplete="one-time-code">
                    <p class="text-xs text-gray-500 mt-2 text-center">Код действителен 10 минут</p>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                    Подтвердить код
                </button>

                <div class="text-center">
                    <button type="button" onclick="window.location.href='register_new.php'" 
                            class="text-blue-600 hover:text-blue-800 text-sm underline">
                        ← Вернуться к регистрации
                    </button>
                </div>
            </form>

        <?php elseif ($step === 'complete'): ?>
            <!-- Завершение регистрации -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Регистрация завершена!</h2>
                <p class="text-gray-600 mb-8">Ваш аккаунт успешно создан и подтвержден. Теперь вы можете войти в личный кабинет.</p>
                
                <div class="space-y-3">
                    <a href="/client/login.php" 
                       class="block w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 text-center font-medium">
                        Войти в личный кабинет
                    </a>
                    <a href="/" 
                       class="block w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 text-center font-medium">
                        На главную страницу
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step === 'register' || $step === 'verify'): ?>
            <div class="mt-8 text-center border-t border-gray-200 pt-6">
                <p class="text-sm text-gray-600">
                    Уже есть аккаунт? 
                    <a href="/client/login.php" class="text-blue-600 hover:text-blue-800 font-medium">Войти</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Автофокус на поле кода при загрузке страницы верификации
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.querySelector('input[name="verification_code"]');
            if (codeInput) {
                codeInput.focus();
                
                // Автоматическая отправка формы при вводе 6 цифр
                codeInput.addEventListener('input', function() {
                    if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
                        this.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>