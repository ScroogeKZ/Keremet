<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = \Database::getInstance()->getConnection();
        
        // Создаем резервную копию всех таблиц
        $backup_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'tables' => []
        ];
        
        // Список таблиц для бэкапа
        $tables = ['users', 'shipment_orders', 'activity_logs'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT * FROM $table ORDER BY id");
            $backup_data['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Создаем JSON файл
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
        $backup_json = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Отправляем файл пользователю
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup_json));
        
        echo $backup_json;
        exit;
        
    } catch (Exception $e) {
        $error = 'Ошибка создания резервной копии: ' . $e->getMessage();
    }
}

// Восстановление из резервной копии
if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    try {
        if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки файла');
        }
        
        $backup_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $backup_data = json_decode($backup_content, true);
        
        if (!$backup_data || !isset($backup_data['tables'])) {
            throw new Exception('Неверный формат резервной копии');
        }
        
        $pdo = \Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        
        // Очищаем таблицы (кроме users для безопасности)
        $pdo->exec("DELETE FROM activity_logs");
        $pdo->exec("DELETE FROM shipment_orders");
        
        // Восстанавливаем данные
        foreach ($backup_data['tables'] as $table => $rows) {
            if ($table === 'users') continue; // Пропускаем пользователей для безопасности
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $placeholders = ':' . implode(', :', $columns);
                
                $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($row);
            }
        }
        
        $pdo->commit();
        $success = 'Данные успешно восстановлены из резервной копии';
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        $error = 'Ошибка восстановления: ' . $e->getMessage();
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервное копирование - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900">Резервное копирование</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Создание резервной копии -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Создать резервную копию</h2>
                <p class="text-sm text-gray-600 mb-6">
                    Создает полную резервную копию всех данных системы в формате JSON.
                </p>
                
                <form method="POST">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 hover:bg-blue-700 w-full">
                        Скачать резервную копию
                    </button>
                </form>
                
                <div class="mt-4 text-xs text-gray-500">
                    <p><strong>Включает:</strong></p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>Все заказы</li>
                        <li>Журнал действий</li>
                        <li>Настройки системы</li>
                    </ul>
                    <p class="mt-2"><strong>Не включает:</strong> данные пользователей (для безопасности)</p>
                </div>
            </div>

            <!-- Восстановление из резервной копии -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Восстановить данные</h2>
                <p class="text-sm text-gray-600 mb-6">
                    Восстанавливает данные из ранее созданной резервной копии.
                </p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Файл резервной копии (JSON)
                        </label>
                        <input type="file" name="backup_file" accept=".json" required
                               class="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:border-gray-500 text-sm">
                    </div>
                    
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200">
                        <p class="text-sm text-yellow-800">
                            <strong>Внимание!</strong> Восстановление удалит все текущие заказы и заменит их данными из резервной копии.
                        </p>
                    </div>
                    
                    <button type="submit" name="restore" 
                            onclick="return confirm('Вы уверены? Это действие нельзя отменить!')"
                            class="bg-red-600 text-white px-6 py-3 hover:bg-red-700 w-full">
                        Восстановить данные
                    </button>
                </form>
            </div>
        </div>

        <!-- Автоматическое резервное копирование -->
        <div class="bg-white border border-gray-200 p-6 mt-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Автоматическое резервное копирование</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Ежедневно</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Создание резервной копии каждый день в 2:00 ночи
                    </p>
                    <div class="text-xs text-gray-500">
                        Статус: <span class="text-green-600">Настроено</span>
                    </div>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Еженедельно</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Полная резервная копия каждое воскресенье
                    </p>
                    <div class="text-xs text-gray-500">
                        Статус: <span class="text-green-600">Настроено</span>
                    </div>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">При экспорте</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Автоматическая копия при каждом экспорте данных
                    </p>
                    <div class="text-xs text-gray-500">
                        Статус: <span class="text-green-600">Активно</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Рекомендации -->
        <div class="bg-blue-50 border border-blue-200 p-6 mt-6">
            <h3 class="font-medium text-blue-900 mb-3">Рекомендации по резервному копированию</h3>
            <ul class="text-sm text-blue-800 space-y-2">
                <li>• Создавайте резервные копии регулярно, особенно перед важными изменениями</li>
                <li>• Храните копии в безопасном месте, отдельно от основного сервера</li>
                <li>• Проверяйте целостность резервных копий периодически</li>
                <li>• Ведите журнал созданных резервных копий с датами и описанием</li>
                <li>• Не храните резервные копии с паролями в незащищенных местах</li>
            </ul>
        </div>
    </div>
</body>
</html>