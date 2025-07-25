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

$cities = [
    'Алматы', 'Шымкент', 'Тараз', 'Караганда', 'Актобе', 'Павлодар', 'Усть-Каменогорск',
    'Семей', 'Атырау', 'Костанай', 'Петропавловск', 'Уральск', 'Кызылорда', 'Актау',
    'Темиртау', 'Туркестан', 'Кокшетау', 'Талдыкорган', 'Экибастуз', 'Рудный'
];

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
            'order_type' => 'regional',
            'pickup_city' => $_POST['pickup_city'] ?? '',
            'pickup_address' => $_POST['pickup_address'] ?? '',
            'ready_time' => $readyTime,
            'contact_name' => $_POST['contact_name'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'cargo_type' => $_POST['cargo_type'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'dimensions' => $_POST['dimensions'] ?? '',
            'destination_city' => $_POST['destination_city'] ?? '',
            'delivery_address' => $_POST['delivery_address'] ?? '',
            'delivery_method' => $_POST['delivery_method'] ?? '',
            'desired_arrival_date' => $_POST['desired_arrival_date'] ?? '',
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
    <title>Межгородские перевозки - Хром-KZ Логистика</title>
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
                    <a href="/astana.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Заказы по Астане</a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Панель управления</a>
                </div>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 pt-4 pb-4">
                <div class="flex flex-col space-y-3">
                    <a href="/" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Главная</a>
                    <a href="/astana.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Заказы по Астане</a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">Панель управления</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6 md:py-12">
        <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl p-4 md:p-10 border border-gray-100">
            <div class="text-center mb-6 md:mb-8">
                <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold mb-2 md:mb-4">
                    <span class="gradient-text">Создание межгородского заказа</span>
                </h1>
                <p class="text-gray-600 text-base md:text-lg">Заполните форму для создания заявки на доставку между городами</p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <strong>Успешно!</strong> Ваша заявка принята. Мы свяжемся с вами в ближайшее время.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <strong>Ошибка:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Город отправления *</label>
                        <select name="pickup_city" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите город</option>
                            <option value="Астана">Астана</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Город назначения *</label>
                        <select name="destination_city" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите город</option>
                            <option value="Астана">Астана</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Адрес забора груза *</label>
                        <input type="text" name="pickup_address" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Адрес доставки *</label>
                        <input type="text" name="delivery_address" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Время готовности груза *</label>
                        <input type="text" name="ready_time" required placeholder="например: 14:00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Желаемая дата прибытия *</label>
                        <input type="date" name="desired_arrival_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тип груза *</label>
                        <select name="cargo_type" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Способ доставки *</label>
                        <select name="delivery_method" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите способ</option>
                            <option value="до_двери">Доставка до двери</option>
                            <option value="до_терминала">Доставка до терминала</option>
                            <option value="самовывоз">Самовывоз</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Вес груза (кг) *</label>
                        <input type="number" name="weight" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Габариты *</label>
                        <input type="text" name="dimensions" required placeholder="например: 30x20x10 см"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Контактное лицо *</label>
                        <input type="text" name="contact_person" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Телефон отправителя *</label>
                        <input type="tel" name="phone" required placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                               pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Контактное лицо получателя *</label>
                        <input type="text" name="recipient_contact" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Телефон получателя *</label>
                        <input type="tel" name="recipient_phone" required placeholder="+77xxxxxxxxx или 87xxxxxxxxx"
                               pattern="(\+77|87)\d{9}" title="Формат: +77xxxxxxxxx или 87xxxxxxxxx"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                    <textarea name="comment" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">📷 Фотография груза</label>
                    <input type="file" name="photo" accept="image/*" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-sm text-gray-500 mt-2">Поддерживаемые форматы: JPG, PNG, GIF. Максимальный размер: 5MB</p>
                </div>
                
                <div class="text-center">
                    <button type="submit" 
                            class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                        Оформить заказ
                    </button>
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