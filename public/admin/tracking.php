<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

// Поиск заказа для отслеживания
$trackingData = null;
$searchError = '';

if (isset($_GET['track_id'])) {
    $trackId = trim($_GET['track_id']);
    
    try {
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   u.username as last_updated_by
            FROM shipment_orders o
            LEFT JOIN users u ON u.id = (
                SELECT al.user_id 
                FROM activity_logs al 
                WHERE al.order_id = o.id 
                ORDER BY al.created_at DESC 
                LIMIT 1
            )
            WHERE o.id = ?
        ");
        
        $stmt->execute([$trackId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Получаем историю изменений
            $historyStmt = $pdo->prepare("
                SELECT al.*, u.username
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE al.order_id = ?
                ORDER BY al.created_at ASC
            ");
            $historyStmt->execute([$trackId]);
            $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $trackingData = [
                'order' => $order,
                'history' => $history
            ];
        } else {
            $searchError = 'Заказ с номером ' . htmlspecialchars($trackId) . ' не найден';
        }
        
    } catch (Exception $e) {
        $searchError = 'Ошибка поиска: ' . $e->getMessage();
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание грузов - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Отслеживание грузов</h1>
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
        <!-- Поиск заказа -->
        <div class="bg-white border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Поиск заказа для отслеживания</h2>
            
            <form method="GET" class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" name="track_id" value="<?php echo htmlspecialchars($_GET['track_id'] ?? ''); ?>" 
                           placeholder="Введите номер заказа для отслеживания..."
                           class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-blue-500 text-sm">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 text-sm hover:bg-blue-700">
                    Отследить
                </button>
            </form>
            
            <?php if ($searchError): ?>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm">
                    <?php echo $searchError; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($trackingData): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Информация о заказе -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-gray-200 p-6 mb-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Заказ №<?php echo $trackingData['order']['id']; ?>
                            </h3>
                            <?php 
                            $statusColors = [
                                'new' => 'bg-red-100 text-red-800',
                                'processing' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-100 text-green-800'
                            ];
                            $statusText = [
                                'new' => 'Новый заказ',
                                'processing' => 'В обработке',
                                'completed' => 'Доставлен'
                            ];
                            ?>
                            <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo $statusColors[$trackingData['order']['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $statusText[$trackingData['order']['status']] ?? $trackingData['order']['status']; ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Детали отправления</h4>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-gray-500">Тип:</span>
                                        <span class="ml-2"><?php echo $trackingData['order']['order_type'] === 'astana' ? 'Местная доставка' : 'Межгород'; ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Адрес забора:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['pickup_address'] ?? '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Направление:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['destination_city'] ?? 'Астана'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Тип груза:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['cargo_type'] ?? '-'); ?></span>
                                    </div>
                                    <?php if ($trackingData['order']['weight']): ?>
                                    <div>
                                        <span class="text-gray-500">Вес:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['weight']); ?> кг</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($trackingData['order']['dimensions']): ?>
                                    <div>
                                        <span class="text-gray-500">Размеры:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['dimensions']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Контактная информация</h4>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-gray-500">Отправитель:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['contact_name'] ?? '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Телефон:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['contact_phone'] ?? '-'); ?></span>
                                    </div>
                                    <?php if ($trackingData['order']['recipient_contact']): ?>
                                    <div>
                                        <span class="text-gray-500">Получатель:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['recipient_contact']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($trackingData['order']['recipient_phone']): ?>
                                    <div>
                                        <span class="text-gray-500">Телефон получателя:</span>
                                        <span class="ml-2"><?php echo htmlspecialchars($trackingData['order']['recipient_phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($trackingData['order']['shipping_cost']): ?>
                                    <div>
                                        <span class="text-gray-500">Стоимость:</span>
                                        <span class="ml-2 font-medium"><?php echo number_format($trackingData['order']['shipping_cost'], 0, ',', ' '); ?> ₸</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($trackingData['order']['notes']): ?>
                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200">
                            <div class="text-sm">
                                <span class="font-medium text-gray-700">Примечания:</span>
                                <span class="ml-2"><?php echo nl2br(htmlspecialchars($trackingData['order']['notes'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- История изменений -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">История отслеживания</h3>
                        
                        <div class="space-y-4">
                            <?php foreach ($trackingData['history'] as $index => $event): 
                                $isLast = $index === count($trackingData['history']) - 1;
                                $actionText = match($event['action']) {
                                    'order_created' => 'Заказ создан',
                                    'status_updated' => 'Статус изменен',
                                    'order_updated' => 'Данные обновлены',
                                    default => $event['action']
                                };
                                
                                $details = json_decode($event['details'] ?? '{}', true);
                            ?>
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-3 h-3 <?php echo $isLast ? 'bg-green-500' : 'bg-blue-500'; ?> rounded-full"></div>
                                        <?php if (!$isLast): ?>
                                            <div class="w-0.5 h-8 bg-gray-200 mx-auto mt-2"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="font-medium text-gray-900"><?php echo $actionText; ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('d.m.Y H:i', strtotime($event['created_at'])); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (isset($details['old_status']) && isset($details['new_status'])): ?>
                                            <div class="text-sm text-gray-600 mt-1">
                                                Статус изменен с "<?php echo $details['old_status']; ?>" на "<?php echo $details['new_status']; ?>"
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($event['username']): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Выполнил: <?php echo htmlspecialchars($event['username']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($trackingData['history'])): ?>
                                <div class="text-center text-gray-500 py-4">
                                    История изменений отсутствует
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Боковая панель -->
                <div class="space-y-6">
                    <!-- Быстрые действия -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h4 class="font-medium text-gray-900 mb-4">Быстрые действия</h4>
                        <div class="space-y-3">
                            <a href="/admin/panel.php?order_id=<?php echo $trackingData['order']['id']; ?>" 
                               class="block w-full text-center bg-blue-600 text-white py-2 px-4 text-sm hover:bg-blue-700">
                                Редактировать заказ
                            </a>
                            
                            <?php if ($trackingData['order']['status'] !== 'completed'): ?>
                                <button onclick="updateStatus(<?php echo $trackingData['order']['id']; ?>, 'completed')"
                                        class="block w-full text-center bg-green-600 text-white py-2 px-4 text-sm hover:bg-green-700">
                                    Отметить доставленным
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="printTrackingInfo()"
                                    class="block w-full text-center border border-gray-300 text-gray-700 py-2 px-4 text-sm hover:border-gray-400">
                                Распечатать этикетку
                            </button>
                        </div>
                    </div>

                    <!-- Прогноз доставки -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h4 class="font-medium text-gray-900 mb-4">Прогноз доставки</h4>
                        
                        <?php 
                        $estimatedDays = match($trackingData['order']['destination_city']) {
                            'Алматы', 'Шымкент' => '2-3 дня',
                            'Караганда', 'Павлодар' => '1-2 дня',
                            'Атырау', 'Актау' => '5-7 дней',
                            default => '3-5 дней'
                        };
                        
                        $createdDate = new DateTime($trackingData['order']['created_at']);
                        $estimatedDate = clone $createdDate;
                        $estimatedDate->add(new DateInterval('P' . (strstr($estimatedDays, '-') ? '5' : '3') . 'D'));
                        ?>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500">Ожидаемое время:</span>
                                <div class="font-medium"><?php echo $estimatedDays; ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500">Ожидаемая дата:</span>
                                <div class="font-medium"><?php echo $estimatedDate->format('d.m.Y'); ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500">Создан:</span>
                                <div><?php echo $createdDate->format('d.m.Y H:i'); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($trackingData['order']['status'] === 'completed'): ?>
                            <div class="mt-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                                ✓ Заказ успешно доставлен
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Контакты для связи -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h4 class="font-medium text-gray-900 mb-4">Нужна помощь?</h4>
                        <div class="space-y-3 text-sm">
                            <div>
                                <div class="font-medium">Служба поддержки:</div>
                                <div class="text-gray-600">+7 (XXX) XXX-XX-XX</div>
                            </div>
                            <div>
                                <div class="font-medium">Email:</div>
                                <div class="text-gray-600">support@hrom-kz.com</div>
                            </div>
                            <div>
                                <div class="font-medium">Часы работы:</div>
                                <div class="text-gray-600">Пн-Пт: 9:00-18:00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateStatus(orderId, newStatus) {
            if (confirm('Изменить статус заказа?')) {
                fetch('/admin/api.php?action=update_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: orderId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    alert('Ошибка соединения');
                });
            }
        }
        
        function printTrackingInfo() {
            window.print();
        }
    </script>
</body>
</html>