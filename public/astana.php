<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\EmailService;

$success = false;
$error = '';

// Function to validate Kazakhstan phone numbers
function validateKazakhstanPhone($phoneNumber) {
    $phone = preg_replace('/[^0-9+]/', '', $phoneNumber); // Remove all non-digits except +
    
    // Valid formats: +77xxxxxxxxx or 87xxxxxxxxx
    if (preg_match('/^\+77\d{9}$/', $phone) || preg_match('/^87\d{9}$/', $phone)) {
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $shipmentOrder = new ShipmentOrder();
        
        // Handle file upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['photo']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $photoPath = '/uploads/' . $fileName;
                }
            }
        }
        
        // Validate and format time
        $readyTime = $_POST['ready_time'] ?? '';
        if ($readyTime && !empty($readyTime)) {
            // If time doesn't contain colon, assume it's in format like "1400" and convert to "14:00"
            if (!str_contains($readyTime, ':')) {
                if (strlen($readyTime) === 3) {
                    $readyTime = '0' . substr($readyTime, 0, 1) . ':' . substr($readyTime, 1);
                } elseif (strlen($readyTime) === 4) {
                    $readyTime = substr($readyTime, 0, 2) . ':' . substr($readyTime, 2);
                }
            }
            // Validate time format
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $readyTime)) {
                throw new Exception('Неверный формат времени. Используйте формат ЧЧ:ММ (например: 14:30)');
            }
        }

        // Validate phone numbers
        $phone = $_POST['phone'] ?? '';
        $recipientPhone = $_POST['recipient_phone'] ?? '';
        
        if (!validateKazakhstanPhone($phone)) {
            throw new Exception('Неверный формат номера телефона отправителя. Используйте формат +77xxxxxxxxx или 87xxxxxxxxx');
        }
        
        if (!validateKazakhstanPhone($recipientPhone)) {
            throw new Exception('Неверный формат номера телефона получателя. Используйте формат +77xxxxxxxxx или 87xxxxxxxxx');
        }

        $data = [
            'order_type' => 'astana',
            'pickup_address' => $_POST['pickup_address'] ?? '',
            'ready_time' => $readyTime,
            'contact_name' => $_POST['contact_person'] ?? '',
            'contact_phone' => $_POST['phone'] ?? '',
            'cargo_type' => $_POST['cargo_type'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'dimensions' => $_POST['dimensions'] ?? '',
            'delivery_address' => $_POST['delivery_address'] ?? '',
            'recipient_contact' => $_POST['recipient_contact'] ?? '',
            'recipient_phone' => $_POST['recipient_phone'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'comment' => $_POST['comment'] ?? ''
        ];
        
        $result = $shipmentOrder->create($data);
        if ($result) {
            $success = true;
            
            // Отправляем email уведомление
            try {
                $emailService = new EmailService();
                $orderData = array_merge($data, ['id' => $result['id']]);
                $emailService->sendOrderNotification($orderData, 'created');
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        $error = 'Ошибка при создании заказа: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доставка по Астане - Хром-KZ Логистика</title>
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
        .gradient-text {
            background: linear-gradient(135deg, #1e40af, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
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
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validatePhone(this);
                });
            });
        });
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-primary to-primary-dark p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6 md:h-8 md:w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold gradient-text">Хром-KZ</h1>
                        <p class="text-xs md:text-sm text-gray-600 font-medium">Логистика</p>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-600 hover:text-primary p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <!-- Desktop menu -->
                <div class="hidden md:flex space-x-4">
                    <a href="/" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Главная</a>
                    <a href="/regional.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Межгородские заказы</a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Панель управления</a>
                </div>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 pt-4 pb-4">
                <div class="flex flex-col space-y-3">
                    <a href="/" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Главная</a>
                    <a href="/regional.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Межгородские заказы</a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Панель управления</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6 md:py-12">
        <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl p-4 md:p-10 border border-gray-100">
            <div class="text-center mb-6 md:mb-8">
                <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold mb-2 md:mb-4">
                    <span class="gradient-text">Создание заказа по Астане</span>
                </h1>
                <p class="text-gray-600 text-base md:text-lg">Заполните форму для создания заявки на доставку в пределах города</p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-accent text-green-800 px-6 py-4 rounded-xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">✅</span>
                        </div>
                        <div class="ml-3">
                            <p class="font-semibold">Заказ успешно создан!</p>
                            <p class="text-sm">Автоматическое уведомление отправлено ответственным сотрудникам.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">❌</span>
                        </div>
                        <div class="ml-3">
                            <p class="font-semibold">Произошла ошибка</p>
                            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6 md:space-y-8">
                <div class="grid md:grid-cols-2 gap-4 md:gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📍 Адрес забора груза *
                            </span>
                        </label>
                        <input type="text" name="pickup_address" required 
                               class="w-full px-3 md:px-4 py-3 md:py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-sm md:text-lg"
                               placeholder="Укажите точный адрес">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                🎯 Адрес доставки *
                            </span>
                        </label>
                        <input type="text" name="delivery_address" required 
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg"
                               placeholder="Укажите точный адрес">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                ⏰ Время готовности груза *
                            </span>
                        </label>
                        <input type="time" name="ready_time" required
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📦 Тип груза *
                            </span>
                        </label>
                        <select name="cargo_type" required 
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                            <option value="">Выберите тип груза</option>
                            <option value="лифтовые порталы">Лифтовые порталы</option>
                            <option value="т-образные профили">Т-образные профили</option>
                            <option value="металлические плинтуса">Металлические плинтуса</option>
                            <option value="корзины для кондиционеров">Корзины для кондиционеров</option>
                            <option value="декоративные решетки">Декоративные решетки</option>
                            <option value="перфорированные фасадные кассеты">Перфорированные фасадные кассеты</option>
                            <option value="стеклянные душевые кабины">Стеклянные душевые кабины</option>
                            <option value="зеркальные панно">Зеркальные панно</option>
                            <option value="рамы и багеты">Рамы и багеты</option>
                            <option value="козырьки">Козырьки</option>
                            <option value="документы">Документы</option>
                            <option value="образцы">Образцы</option>
                            <option value="другое">Другое</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                ⚖️ Вес груза (кг) *
                            </span>
                        </label>
                        <input type="number" name="weight" required min="1" placeholder="Укажите вес в килограммах"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📐 Габариты *
                            </span>
                        </label>
                        <input type="text" name="dimensions" required placeholder="например: 30x20x10 см"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                👤 Контактное лицо отправителя *
                            </span>
                        </label>
                        <input type="text" name="contact_person" required placeholder="ФИО отправителя"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📱 Телефон отправителя *
                            </span>
                        </label>
                        <input type="tel" name="phone" required placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                               pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                👥 Контактное лицо получателя *
                            </span>
                        </label>
                        <input type="text" name="recipient_contact" required placeholder="ФИО получателя"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📞 Телефон получателя *
                            </span>
                        </label>
                        <input type="tel" name="recipient_phone" required placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                               pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <span class="flex items-center">
                            💬 Комментарий
                        </span>
                    </label>
                    <textarea name="comment" rows="4" placeholder="Дополнительная информация о доставке..."
                              class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg resize-none"></textarea>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <span class="flex items-center">
                            📷 Фотография груза
                        </span>
                    </label>
                    <input type="file" name="photo" accept="image/*" 
                           class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                    <p class="text-sm text-gray-500 mt-2">Поддерживаемые форматы: JPG, PNG, GIF. Максимальный размер: 5MB</p>
                </div>
                
                <div class="pt-6">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-dark text-white py-4 px-8 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-300 font-bold text-xl">
                        <span class="flex items-center justify-center">
                            📋 Создать заказ
                        </span>
                    </button>
                    <p class="text-center text-gray-500 text-sm mt-4">
                        После создания заказа автоматически будет отправлено уведомление в Telegram
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            
            if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>