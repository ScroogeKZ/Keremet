<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ActivityLog;
use App\Auth;

Auth::requireAuth();

$activityModel = new ActivityLog();
$activities = $activityModel->getActivities(100);

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История действий - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">История действий</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <a href="/admin/search.php" class="text-sm text-gray-600 hover:text-gray-900">Поиск</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="bg-white border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Последние действия пользователей</h2>
                <p class="text-sm text-gray-500 mt-1">Журнал всех изменений в системе</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Время</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Пользователь</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действие</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Детали</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($activities as $activity): 
                            $details = json_decode($activity['details'] ?? '{}', true);
                            $actionText = match($activity['action']) {
                                'order_created' => 'Создал заказ',
                                'status_updated' => 'Изменил статус',
                                'order_updated' => 'Редактировал заказ',
                                'order_deleted' => 'Удалил заказ',
                                'user_login' => 'Вход в систему',
                                'user_logout' => 'Выход из системы',
                                'bulk_status_update' => 'Массовое изменение статуса',
                                'export_data' => 'Экспорт данных',
                                default => $activity['action']
                            };
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('d.m.Y H:i:s', strtotime($activity['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($activity['username']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $actionText; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if ($activity['order_id']): ?>
                                        <a href="/admin/panel.php?order_id=<?php echo $activity['order_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800">
                                            №<?php echo $activity['order_id']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php if ($details): ?>
                                        <?php if (isset($details['old_status']) && isset($details['new_status'])): ?>
                                            <?php echo htmlspecialchars($details['old_status']); ?> → <?php echo htmlspecialchars($details['new_status']); ?>
                                        <?php elseif (isset($details['changes'])): ?>
                                            Изменено полей: <?php echo count($details['changes']); ?>
                                        <?php elseif (isset($details['count'])): ?>
                                            Заказов: <?php echo $details['count']; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(json_encode($details, JSON_UNESCAPED_UNICODE)); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($activities)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">
                        Нет записей о действиях
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>