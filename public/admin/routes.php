<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

// Получение маршрутов и расчет стоимости
if ($_POST) {
    $fromCity = $_POST['from_city'] ?? '';
    $toCity = $_POST['to_city'] ?? '';
    $cargoWeight = (float)($_POST['cargo_weight'] ?? 0);
    $cargoVolume = (float)($_POST['cargo_volume'] ?? 0);
    $serviceType = $_POST['service_type'] ?? 'standard';
    
    // Базовые тарифы (можно вынести в настройки)
    $baseTariffs = [
        'Алматы' => ['base' => 15000, 'per_kg' => 120, 'per_m3' => 8000],
        'Шымкент' => ['base' => 12000, 'per_kg' => 100, 'per_m3' => 6500],
        'Актобе' => ['base' => 18000, 'per_kg' => 140, 'per_m3' => 9000],
        'Караганда' => ['base' => 8000, 'per_kg' => 80, 'per_m3' => 5000],
        'Павлодар' => ['base' => 10000, 'per_kg' => 90, 'per_m3' => 5500],
        'Костанай' => ['base' => 14000, 'per_kg' => 110, 'per_m3' => 7000],
        'Петропавловск' => ['base' => 16000, 'per_kg' => 130, 'per_m3' => 8500],
        'Атырау' => ['base' => 22000, 'per_kg' => 180, 'per_m3' => 12000],
        'Актау' => ['base' => 25000, 'per_kg' => 200, 'per_m3' => 15000]
    ];
    
    $serviceMult = match($serviceType) {
        'express' => 1.5,
        'premium' => 2.0,
        default => 1.0
    };
    
    $calculated = null;
    if ($fromCity && $toCity && isset($baseTariffs[$toCity])) {
        $tariff = $baseTariffs[$toCity];
        $weightCost = $cargoWeight * $tariff['per_kg'];
        $volumeCost = $cargoVolume * $tariff['per_m3'];
        $totalCost = ($tariff['base'] + max($weightCost, $volumeCost)) * $serviceMult;
        
        $calculated = [
            'base_cost' => $tariff['base'],
            'weight_cost' => $weightCost,
            'volume_cost' => $volumeCost,
            'service_multiplier' => $serviceMult,
            'total_cost' => $totalCost,
            'delivery_time' => match($toCity) {
                'Алматы', 'Шымкент' => $serviceType === 'express' ? '1-2 дня' : '2-3 дня',
                'Караганда', 'Павлодар' => $serviceType === 'express' ? '2-3 дня' : '3-4 дня',
                'Атырау', 'Актау' => $serviceType === 'express' ? '3-4 дня' : '5-7 дней',
                default => $serviceType === 'express' ? '2-4 дня' : '4-6 дней'
            }
        ];
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расчет маршрутов - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Расчет маршрутов</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Калькулятор стоимости -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Калькулятор стоимости доставки</h2>
                
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Откуда</label>
                            <select name="from_city" class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                <option value="Астана" selected>Астана</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Куда</label>
                            <select name="to_city" required class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                                <option value="">Выберите город</option>
                                <option value="Алматы" <?php echo ($_POST['to_city'] ?? '') === 'Алматы' ? 'selected' : ''; ?>>Алматы</option>
                                <option value="Шымкент" <?php echo ($_POST['to_city'] ?? '') === 'Шымкент' ? 'selected' : ''; ?>>Шымкент</option>
                                <option value="Актобе" <?php echo ($_POST['to_city'] ?? '') === 'Актобе' ? 'selected' : ''; ?>>Актобе</option>
                                <option value="Караганда" <?php echo ($_POST['to_city'] ?? '') === 'Караганда' ? 'selected' : ''; ?>>Караганда</option>
                                <option value="Павлодар" <?php echo ($_POST['to_city'] ?? '') === 'Павлодар' ? 'selected' : ''; ?>>Павлодар</option>
                                <option value="Костанай" <?php echo ($_POST['to_city'] ?? '') === 'Костанай' ? 'selected' : ''; ?>>Костанай</option>
                                <option value="Петропавловск" <?php echo ($_POST['to_city'] ?? '') === 'Петропавловск' ? 'selected' : ''; ?>>Петропавловск</option>
                                <option value="Атырау" <?php echo ($_POST['to_city'] ?? '') === 'Атырау' ? 'selected' : ''; ?>>Атырау</option>
                                <option value="Актау" <?php echo ($_POST['to_city'] ?? '') === 'Актау' ? 'selected' : ''; ?>>Актау</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Вес груза (кг)</label>
                            <input type="number" name="cargo_weight" step="0.1" min="0" 
                                   value="<?php echo $_POST['cargo_weight'] ?? ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Объем (м³)</label>
                            <input type="number" name="cargo_volume" step="0.01" min="0"
                                   value="<?php echo $_POST['cargo_volume'] ?? ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тип сервиса</label>
                        <select name="service_type" class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                            <option value="standard" <?php echo ($_POST['service_type'] ?? '') === 'standard' ? 'selected' : ''; ?>>Стандартная доставка</option>
                            <option value="express" <?php echo ($_POST['service_type'] ?? '') === 'express' ? 'selected' : ''; ?>>Экспресс доставка (+50%)</option>
                            <option value="premium" <?php echo ($_POST['service_type'] ?? '') === 'premium' ? 'selected' : ''; ?>>Премиум доставка (+100%)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 hover:bg-blue-700">
                        Рассчитать стоимость
                    </button>
                </form>
                
                <?php if (isset($calculated)): ?>
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200">
                        <h3 class="font-medium text-blue-900 mb-3">Результат расчета</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Базовая стоимость:</span>
                                <span><?php echo number_format($calculated['base_cost'], 0, ',', ' '); ?> ₸</span>
                            </div>
                            <div class="flex justify-between">
                                <span>По весу:</span>
                                <span><?php echo number_format($calculated['weight_cost'], 0, ',', ' '); ?> ₸</span>
                            </div>
                            <div class="flex justify-between">
                                <span>По объему:</span>
                                <span><?php echo number_format($calculated['volume_cost'], 0, ',', ' '); ?> ₸</span>
                            </div>
                            <?php if ($calculated['service_multiplier'] > 1): ?>
                                <div class="flex justify-between">
                                    <span>Коэффициент сервиса:</span>
                                    <span>×<?php echo $calculated['service_multiplier']; ?></span>
                                </div>
                            <?php endif; ?>
                            <hr class="border-blue-300">
                            <div class="flex justify-between font-medium text-blue-900">
                                <span>Итого к оплате:</span>
                                <span><?php echo number_format($calculated['total_cost'], 0, ',', ' '); ?> ₸</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Время доставки:</span>
                                <span><?php echo $calculated['delivery_time']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Тарифная сетка -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Тарифная сетка</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Направление</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">База</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">За кг</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">За м³</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            $tariffs = [
                                'Алматы' => ['base' => 15000, 'per_kg' => 120, 'per_m3' => 8000],
                                'Шымкент' => ['base' => 12000, 'per_kg' => 100, 'per_m3' => 6500],
                                'Актобе' => ['base' => 18000, 'per_kg' => 140, 'per_m3' => 9000],
                                'Караганда' => ['base' => 8000, 'per_kg' => 80, 'per_m3' => 5000],
                                'Павлодар' => ['base' => 10000, 'per_kg' => 90, 'per_m3' => 5500],
                                'Костанай' => ['base' => 14000, 'per_kg' => 110, 'per_m3' => 7000],
                                'Петропавловск' => ['base' => 16000, 'per_kg' => 130, 'per_m3' => 8500],
                                'Атырау' => ['base' => 22000, 'per_kg' => 180, 'per_m3' => 12000],
                                'Актау' => ['base' => 25000, 'per_kg' => 200, 'per_m3' => 15000]
                            ];
                            
                            foreach ($tariffs as $city => $tariff): ?>
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900"><?php echo $city; ?></td>
                                    <td class="px-3 py-2 text-gray-500"><?php echo number_format($tariff['base'], 0, ',', ' '); ?> ₸</td>
                                    <td class="px-3 py-2 text-gray-500"><?php echo $tariff['per_kg']; ?> ₸</td>
                                    <td class="px-3 py-2 text-gray-500"><?php echo number_format($tariff['per_m3'], 0, ',', ' '); ?> ₸</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-xs text-gray-500">
                    <p><strong>Примечание:</strong> Окончательная стоимость рассчитывается как базовая стоимость + максимум из (вес × тариф за кг) или (объем × тариф за м³)</p>
                </div>
            </div>
        </div>

        <!-- Быстрые расчеты -->
        <div class="mt-6 bg-white border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Популярные направления</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 border border-gray-200">
                    <h3 class="font-medium text-gray-900">Астана → Алматы</h3>
                    <div class="text-sm text-gray-600 mt-2">
                        <div>От 15,000 ₸ + груз</div>
                        <div>Время: 2-3 дня</div>
                        <div>Расстояние: ~1,200 км</div>
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 border border-gray-200">
                    <h3 class="font-medium text-gray-900">Астана → Шымкент</h3>
                    <div class="text-sm text-gray-600 mt-2">
                        <div>От 12,000 ₸ + груз</div>
                        <div>Время: 2-3 дня</div>
                        <div>Расстояние: ~1,100 км</div>
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 border border-gray-200">
                    <h3 class="font-medium text-gray-900">Астана → Актау</h3>
                    <div class="text-sm text-gray-600 mt-2">
                        <div>От 25,000 ₸ + груз</div>
                        <div>Время: 5-7 дней</div>
                        <div>Расстояние: ~1,700 км</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>