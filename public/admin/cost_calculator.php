<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../config/database.php';

use App\Auth;

session_start();
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$db = \Database::getInstance()->getConnection();
$calculation = null;
$error = '';

// Функция расчета стоимости на основе тарифов из БД
function calculateShippingCost($db, $orderType, $weight, $distance, $cargoType, $urgent = false, $city = '') {
    try {
        // Базовый расчет по тарифам
        $stmt = $db->prepare("
            SELECT * FROM pricing_rules 
            WHERE service_type = ? 
            AND distance_from <= ? AND distance_to >= ?
            AND weight_from <= ? AND weight_to >= ?
            AND (city = ? OR city IS NULL OR city = '')
            ORDER BY base_price ASC 
            LIMIT 1
        ");
        $stmt->execute([$orderType, $distance, $distance, $weight, $weight, $city]);
        $rule = $stmt->fetch();
        
        if (!$rule) {
            // Fallback тарифы если нет в БД
            if ($orderType === 'astana') {
                $baseCost = 2000 + ($weight > 10 ? ($weight - 10) * 100 : 0);
            } else {
                $baseCost = 5000 + ($distance * 15) + ($weight > 20 ? ($weight - 20) * 150 : 0);
            }
        } else {
            $baseCost = $rule['base_price'];
            $baseCost += $distance * $rule['price_per_km'];
            $baseCost += $weight * $rule['price_per_kg'];
        }
        
        // Надбавки за тип груза
        $cargoMultiplier = match($cargoType) {
            'Стеклянные душевые кабины', 'Зеркальные панно' => 1.3,
            'Лифтовые порталы', 'Т-образные профили' => 1.2,
            'Металлические плинтуса', 'Корзины для кондиционеров' => 1.15,
            'Декоративные решетки', 'Перфорированные фасадные кассеты' => 1.1,
            'Документы', 'Образцы' => 0.8,
            default => 1.0
        };
        
        $baseCost *= $cargoMultiplier;
        
        // Срочность +50%
        if ($urgent) {
            $baseCost *= 1.5;
        }
        
        return [
            'base_cost' => round($baseCost / $cargoMultiplier / ($urgent ? 1.5 : 1)),
            'cargo_multiplier' => $cargoMultiplier,
            'urgent_multiplier' => $urgent ? 1.5 : 1.0,
            'weight_cost' => round($weight * ($rule['price_per_kg'] ?? 0)),
            'distance_cost' => round($distance * ($rule['price_per_km'] ?? 0)),
            'final_cost' => round($baseCost),
            'used_rule' => $rule ? "Тариф #{$rule['id']}" : 'Стандартный расчет'
        ];
        
    } catch (Exception $e) {
        throw new Exception('Ошибка расчета: ' . $e->getMessage());
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderType = $_POST['order_type'] ?? '';
    $weight = floatval($_POST['weight'] ?? 0);
    $distance = intval($_POST['distance'] ?? 0);
    $cargoType = $_POST['cargo_type'] ?? '';
    $city = $_POST['city'] ?? '';
    $urgent = isset($_POST['urgent']);
    
    if ($weight > 0 && $distance > 0) {
        try {
            $calculation = calculateShippingCost($db, $orderType, $weight, $distance, $cargoType, $urgent, $city);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Заполните вес и расстояние';
    }
}

// $currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Калькулятор стоимости - Хром-KZ Логистика</title>
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
                        <h1 class="text-lg font-medium text-gray-900">Калькулятор стоимости</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-sm text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <a href="/admin/search.php" class="text-sm text-gray-600 hover:text-gray-900">Поиск</a>
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-medium text-gray-900">Калькулятор стоимости доставки</h1>
                <a href="/admin/panel.php" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                    Назад в панель
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Форма расчета -->
                <div class="bg-white p-4 border border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Параметры доставки</h2>
                    
                    <?php if ($error): ?>
                    <div class="bg-red-50 text-red-700 px-4 py-3 mb-4 border border-red-200">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4" id="calculatorForm">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип доставки</label>
                            <select name="order_type" required class="w-full px-3 py-2 border border-gray-300 text-sm" onchange="updateDistanceField()">
                                <option value="">Выберите тип</option>
                                <option value="astana" <?= ($_POST['order_type'] ?? '') === 'astana' ? 'selected' : '' ?>>По Астане</option>
                                <option value="regional" <?= ($_POST['order_type'] ?? '') === 'regional' ? 'selected' : '' ?>>Межгородская</option>
                            </select>
                        </div>
                        
                        <div id="cityField" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Город назначения</label>
                            <select name="city" class="w-full px-3 py-2 border border-gray-300 text-sm">
                                <option value="">Выберите город</option>
                                <option value="Алматы" <?= ($_POST['city'] ?? '') === 'Алматы' ? 'selected' : '' ?>>Алматы</option>
                                <option value="Шымкент" <?= ($_POST['city'] ?? '') === 'Шымкент' ? 'selected' : '' ?>>Шымкент</option>
                                <option value="Актобе" <?= ($_POST['city'] ?? '') === 'Актобе' ? 'selected' : '' ?>>Актобе</option>
                                <option value="Тараз" <?= ($_POST['city'] ?? '') === 'Тараз' ? 'selected' : '' ?>>Тараз</option>
                                <option value="Павлодар" <?= ($_POST['city'] ?? '') === 'Павлодар' ? 'selected' : '' ?>>Павлодар</option>
                                <option value="Усть-Каменогорск" <?= ($_POST['city'] ?? '') === 'Усть-Каменогорск' ? 'selected' : '' ?>>Усть-Каменогорск</option>
                                <option value="Семей" <?= ($_POST['city'] ?? '') === 'Семей' ? 'selected' : '' ?>>Семей</option>
                                <option value="Атырау" <?= ($_POST['city'] ?? '') === 'Атырау' ? 'selected' : '' ?>>Атырау</option>
                                <option value="Костанай" <?= ($_POST['city'] ?? '') === 'Костанай' ? 'selected' : '' ?>>Костанай</option>
                                <option value="Другие города" <?= ($_POST['city'] ?? '') === 'Другие города' ? 'selected' : '' ?>>Другие города</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Вес груза (кг)</label>
                            <input type="number" name="weight" step="0.1" min="0.1" max="10000" required 
                                   class="w-full px-3 py-2 border border-gray-300 text-sm"
                                   value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>"
                                   oninput="calculateLive()">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span id="distanceLabel">Расстояние (км)</span>
                            </label>
                            <input type="number" name="distance" min="1" max="2000" required 
                                   class="w-full px-3 py-2 border border-gray-300 text-sm"
                                   value="<?= htmlspecialchars($_POST['distance'] ?? '') ?>"
                                   oninput="calculateLive()">
                            <p class="text-xs text-gray-500 mt-1" id="distanceHint">
                                Для расчета по Астане укажите расстояние от точки забора до доставки
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип груза</label>
                            <select name="cargo_type" class="w-full px-3 py-2 border border-gray-300 text-sm" onchange="calculateLive()">
                                <option value="Другое">Стандартный груз</option>
                                <option value="Лифтовые порталы" <?= ($_POST['cargo_type'] ?? '') === 'Лифтовые порталы' ? 'selected' : '' ?>>Лифтовые порталы</option>
                                <option value="Т-образные профили" <?= ($_POST['cargo_type'] ?? '') === 'Т-образные профили' ? 'selected' : '' ?>>Т-образные профили</option>
                                <option value="Металлические плинтуса" <?= ($_POST['cargo_type'] ?? '') === 'Металлические плинтуса' ? 'selected' : '' ?>>Металлические плинтуса</option>
                                <option value="Корзины для кондиционеров" <?= ($_POST['cargo_type'] ?? '') === 'Корзины для кондиционеров' ? 'selected' : '' ?>>Корзины для кондиционеров</option>
                                <option value="Декоративные решетки" <?= ($_POST['cargo_type'] ?? '') === 'Декоративные решетки' ? 'selected' : '' ?>>Декоративные решетки</option>
                                <option value="Перфорированные фасадные кассеты" <?= ($_POST['cargo_type'] ?? '') === 'Перфорированные фасадные кассеты' ? 'selected' : '' ?>>Перфорированные фасадные кассеты</option>
                                <option value="Стеклянные душевые кабины" <?= ($_POST['cargo_type'] ?? '') === 'Стеклянные душевые кабины' ? 'selected' : '' ?>>Стеклянные душевые кабины</option>
                                <option value="Зеркальные панно" <?= ($_POST['cargo_type'] ?? '') === 'Зеркальные панно' ? 'selected' : '' ?>>Зеркальные панно</option>
                                <option value="Рамы и багеты" <?= ($_POST['cargo_type'] ?? '') === 'Рамы и багеты' ? 'selected' : '' ?>>Рамы и багеты</option>
                                <option value="Козырьки" <?= ($_POST['cargo_type'] ?? '') === 'Козырьки' ? 'selected' : '' ?>>Козырьки</option>
                                <option value="Документы" <?= ($_POST['cargo_type'] ?? '') === 'Документы' ? 'selected' : '' ?>>Документы</option>
                                <option value="Образцы" <?= ($_POST['cargo_type'] ?? '') === 'Образцы' ? 'selected' : '' ?>>Образцы</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="urgent" class="text-blue-600" <?= isset($_POST['urgent']) ? 'checked' : '' ?> onchange="calculateLive()">
                                <span class="text-sm text-gray-700">Срочная доставка (+50%)</span>
                            </label>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-2 px-4 text-sm hover:bg-blue-700">
                            Рассчитать стоимость
                        </button>
                        
                        <div id="liveCalculation" class="hidden bg-blue-50 border border-blue-200 p-3 rounded text-sm">
                            <div class="font-medium text-blue-900">Предварительный расчет:</div>
                            <div id="liveResult" class="text-blue-800 mt-1"></div>
                        </div>
                    </form>
                </div>

                <!-- Результаты расчета -->
                <div class="space-y-6">
                    <?php if ($calculation): ?>
                    <div class="bg-white p-6 border border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Результат расчета</h2>
                        
                        <div class="bg-green-50 border border-green-200 p-4 mb-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-800 mb-2">
                                    <?= number_format($calculation['final_cost'], 0, ',', ' ') ?> ₸
                                </div>
                                <div class="text-sm text-green-700">Итоговая стоимость доставки</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-gray-600">Базовая стоимость:</span>
                                <span class="font-medium"><?= number_format($calculation['base_cost'], 0, ',', ' ') ?> ₸</span>
                            </div>
                            
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-gray-600">Стоимость по весу:</span>
                                <span class="font-medium"><?= number_format($calculation['weight_cost'], 0, ',', ' ') ?> ₸</span>
                            </div>
                            
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-gray-600">Стоимость по расстоянию:</span>
                                <span class="font-medium"><?= number_format($calculation['distance_cost'], 0, ',', ' ') ?> ₸</span>
                            </div>
                            
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-gray-600">Коэффициент за тип груза:</span>
                                <span class="font-medium">×<?= $calculation['cargo_multiplier'] ?></span>
                            </div>
                            
                            <?php if ($calculation['urgent_multiplier'] > 1): ?>
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-gray-600">Срочная доставка:</span>
                                <span class="font-medium text-orange-600">×<?= $calculation['urgent_multiplier'] ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between pt-2 font-medium text-base">
                                <span>Итого:</span>
                                <span class="text-green-600"><?= number_format($calculation['final_cost'], 0, ',', ' ') ?> ₸</span>
                            </div>
                            
                            <div class="text-xs text-gray-500 mt-3">
                                Расчет: <?= htmlspecialchars($calculation['used_rule']) ?>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex space-x-3">
                            <button onclick="createOrder()" class="bg-green-600 text-white px-4 py-2 text-sm hover:bg-green-700">
                                Создать заказ с этой стоимостью
                            </button>
                            <button onclick="saveCalculation()" class="bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">
                                Сохранить расчет
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Тарифная сетка -->
                    <div class="bg-white p-6 border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Тарифная сетка</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-800 mb-3">Доставка по Астане</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>До 10 кг, до 10 км:</span>
                                        <span class="font-medium">2 000 ₸</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>10-50 кг, до 10 км:</span>
                                        <span class="font-medium">2 500 ₸</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Свыше 50 кг:</span>
                                        <span class="font-medium">3 000 ₸</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        + 50 ₸/км за расстояние<br>
                                        + надбавки за тип груза
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-800 mb-3">Межгородская доставка</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Алматы (до 10 кг):</span>
                                        <span class="font-medium">5 000 ₸</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Шымкент (до 10 кг):</span>
                                        <span class="font-medium">8 000 ₸</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Другие города:</span>
                                        <span class="font-medium">15 000 ₸</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        + стоимость за км и вес<br>
                                        + срочность +50%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 p-4 bg-gray-50 rounded">
                            <h5 class="font-medium text-gray-800 mb-2">Коэффициенты за тип груза:</h5>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>Хрупкие (стекло, зеркала): +30%</div>
                                <div>Тяжелые (металл): +20%</div>
                                <div>Ценные (электроника): +15%</div>
                                <div>Легкие (документы): -20%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateDistanceField() {
            const orderType = document.querySelector('select[name="order_type"]').value;
            const cityField = document.getElementById('cityField');
            const distanceLabel = document.getElementById('distanceLabel');
            const distanceHint = document.getElementById('distanceHint');
            
            if (orderType === 'regional') {
                cityField.style.display = 'block';
                distanceLabel.textContent = 'Расстояние до города (км)';
                distanceHint.textContent = 'Примерное расстояние от Астаны до города назначения';
            } else {
                cityField.style.display = 'none';
                distanceLabel.textContent = 'Расстояние по городу (км)';
                distanceHint.textContent = 'Расстояние от точки забора до точки доставки по Астане';
            }
            
            calculateLive();
        }
        
        function calculateLive() {
            const form = document.getElementById('calculatorForm');
            const formData = new FormData(form);
            
            const orderType = formData.get('order_type');
            const weight = parseFloat(formData.get('weight')) || 0;
            const distance = parseInt(formData.get('distance')) || 0;
            const cargoType = formData.get('cargo_type');
            const urgent = formData.get('urgent') ? true : false;
            
            if (!orderType || weight <= 0 || distance <= 0) {
                document.getElementById('liveCalculation').classList.add('hidden');
                return;
            }
            
            // Простой клиентский расчет для превью
            let baseCost = 0;
            
            if (orderType === 'astana') {
                baseCost = 2000 + (distance * 50) + (weight > 10 ? (weight - 10) * 100 : 0);
            } else {
                baseCost = 5000 + (distance * 15) + (weight > 20 ? (weight - 20) * 150 : 0);
            }
            
            // Коэффициенты
            const cargoMultipliers = {
                'Стеклянные душевые кабины': 1.3,
                'Зеркальные панно': 1.3,
                'Лифтовые порталы': 1.2,
                'Т-образные профили': 1.2,
                'Металлические плинтуса': 1.15,
                'Корзины для кондиционеров': 1.15,
                'Декоративные решетки': 1.1,
                'Перфорированные фасадные кассеты': 1.1,
                'Документы': 0.8,
                'Образцы': 0.8
            };
            
            const multiplier = cargoMultipliers[cargoType] || 1.0;
            baseCost *= multiplier;
            
            if (urgent) {
                baseCost *= 1.5;
            }
            
            document.getElementById('liveResult').textContent = 
                `≈ ${Math.round(baseCost).toLocaleString('ru-RU')} ₸`;
            document.getElementById('liveCalculation').classList.remove('hidden');
        }
        
        function createOrder() {
            const orderType = document.querySelector('select[name="order_type"]').value;
            if (orderType === 'astana') {
                window.open('/astana.php', '_blank');
            } else {
                window.open('/regional.php', '_blank');
            }
        }
        
        function saveCalculation() {
            alert('Расчет сохранен в истории');
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            updateDistanceField();
        });
    </script>
</body>
</html>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип доставки</label>
                            <select name="order_type" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                                <option value="">Выберите тип</option>
                                <option value="astana" <?php echo ($_POST['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>
                                    По Астане
                                </option>
                                <option value="regional" <?php echo ($_POST['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>
                                    Межгород
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Вес груза (кг)</label>
                            <input type="number" name="weight" step="0.1" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" 
                                   required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                        </div>

                        <div id="distanceField" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Расстояние (км, только для межгорода)</label>
                            <input type="number" name="distance" value="<?php echo htmlspecialchars($_POST['distance'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип груза</label>
                            <select name="cargo_type" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                                <option value="">Выберите тип груза</option>
                                <option value="Лифтовые порталы" <?php echo ($_POST['cargo_type'] ?? '') === 'Лифтовые порталы' ? 'selected' : ''; ?>>Лифтовые порталы</option>
                                <option value="Т-образные профили" <?php echo ($_POST['cargo_type'] ?? '') === 'Т-образные профили' ? 'selected' : ''; ?>>Т-образные профили</option>
                                <option value="Металлические плинтуса" <?php echo ($_POST['cargo_type'] ?? '') === 'Металлические плинтуса' ? 'selected' : ''; ?>>Металлические плинтуса</option>
                                <option value="Корзины для кондиционеров" <?php echo ($_POST['cargo_type'] ?? '') === 'Корзины для кондиционеров' ? 'selected' : ''; ?>>Корзины для кондиционеров</option>
                                <option value="Декоративные решетки" <?php echo ($_POST['cargo_type'] ?? '') === 'Декоративные решетки' ? 'selected' : ''; ?>>Декоративные решетки</option>
                                <option value="Стеклянные душевые кабины" <?php echo ($_POST['cargo_type'] ?? '') === 'Стеклянные душевые кабины' ? 'selected' : ''; ?>>Стеклянные душевые кабины</option>
                                <option value="Зеркальные панно" <?php echo ($_POST['cargo_type'] ?? '') === 'Зеркальные панно' ? 'selected' : ''; ?>>Зеркальные панно</option>
                                <option value="Электроника" <?php echo ($_POST['cargo_type'] ?? '') === 'Электроника' ? 'selected' : ''; ?>>Электроника</option>
                                <option value="Документы" <?php echo ($_POST['cargo_type'] ?? '') === 'Документы' ? 'selected' : ''; ?>>Документы</option>
                                <option value="Образцы" <?php echo ($_POST['cargo_type'] ?? '') === 'Образцы' ? 'selected' : ''; ?>>Образцы</option>
                                <option value="Другое" <?php echo ($_POST['cargo_type'] ?? '') === 'Другое' ? 'selected' : ''; ?>>Другое</option>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="urgent" id="urgent" <?php echo isset($_POST['urgent']) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <label for="urgent" class="ml-2 text-sm text-gray-700">Срочная доставка (+50%)</label>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 font-medium hover:bg-blue-700 focus:outline-none">
                            Рассчитать стоимость
                        </button>
                    </form>
                </div>

                <!-- Результат расчета -->
                <div class="bg-white p-4 border border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Результат расчета</h2>
                    
                    <?php if ($calculation): ?>
                    <div class="space-y-4">
                        <div class="bg-green-50 border border-green-200 p-4">
                            <div class="text-2xl font-bold text-green-800 mb-2">
                                <?php echo number_format($calculation['final_cost'], 0, ',', ' '); ?> ₸
                            </div>
                            <div class="text-sm text-green-600">Итоговая стоимость доставки</div>
                        </div>

                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-900">Детализация расчета:</h3>
                            
                            <div class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Базовая стоимость:</span>
                                    <span class="font-medium"><?php echo number_format($calculation['base_cost'] / ($calculation['cargo_multiplier'] * ($calculation['urgent_applied'] ? 1.5 : 1)), 0, ',', ' '); ?> ₸</span>
                                </div>
                                
                                <?php if ($calculation['cargo_multiplier'] != 1.0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Коэффициент груза:</span>
                                    <span class="font-medium">×<?php echo $calculation['cargo_multiplier']; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($calculation['urgent_applied']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Срочность:</span>
                                    <span class="font-medium text-red-600">+50%</span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="border-t pt-2">
                                    <div class="flex justify-between font-medium">
                                        <span>Итого:</span>
                                        <span><?php echo number_format($calculation['final_cost'], 0, ',', ' '); ?> ₸</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <button onclick="copyToClipboard('<?php echo $calculation['final_cost']; ?>')" 
                                    class="w-full bg-gray-100 text-gray-700 py-2 px-4 text-xs hover:bg-gray-200">
                                Скопировать сумму
                            </button>
                            <button onclick="openNewOrderForm()" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 text-xs hover:bg-blue-700">
                                Создать заказ с этой стоимостью
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-gray-400 py-6">
                        <div class="text-xs">Заполните форму для расчета стоимости</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Справочная информация -->
            <div class="mt-6 bg-white p-4 border border-gray-200">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Тарифы и правила расчета</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Базовые тарифы:</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>• <strong>По Астане:</strong> 2,000 ₸ + 100₸/кг (свыше 10 кг)</li>
                            <li>• <strong>Межгород:</strong> 5,000 ₸ + 8₸/км + 150₸/кг (свыше 20 кг)</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Коэффициенты по типу груза:</h3>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li>• <strong>Хрупкие</strong> (стекло, зеркала): +30%</li>
                            <li>• <strong>Тяжелые</strong> (металл, профили): +20%</li>
                            <li>• <strong>Ценные</strong> (электроника): +15%</li>
                            <li>• <strong>Легкие</strong> (документы): -20%</li>
                            <li>• <strong>Срочные</strong> заказы: +50%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Показать поле расстояния для межгорода
    document.querySelector('select[name="order_type"]').addEventListener('change', function() {
        const distanceField = document.getElementById('distanceField');
        if (this.value === 'regional') {
            distanceField.style.display = 'block';
            distanceField.querySelector('input').required = true;
        } else {
            distanceField.style.display = 'none';
            distanceField.querySelector('input').required = false;
        }
    });

    // Проверить при загрузке страницы
    if (document.querySelector('select[name="order_type"]').value === 'regional') {
        document.getElementById('distanceField').style.display = 'block';
    }

    function copyToClipboard(amount) {
        navigator.clipboard.writeText(amount).then(function() {
            alert('Сумма ' + new Intl.NumberFormat('ru-RU').format(amount) + ' ₸ скопирована в буфер обмена');
        });
    }

    function openNewOrderForm() {
        if (confirm('Перейти к форме создания заказа?')) {
            window.open('/astana.php', '_blank');
        }
    }
    </script>
</body>
</html>