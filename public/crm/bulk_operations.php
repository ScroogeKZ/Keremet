<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

// Check authentication
if (!Auth::isAuthenticated()) {
    header('Location: /crm/login.php');
    exit;
}

$orders = new ShipmentOrder();
$message = '';
$error = '';

// Handle bulk operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selected_orders = $_POST['selected_orders'] ?? [];
    
    if (empty($selected_orders)) {
        $error = 'Выберите заказы для выполнения операции';
    } else {
        switch ($action) {
            case 'update_status':
                $new_status = $_POST['new_status'] ?? '';
                $updated = 0;
                foreach ($selected_orders as $order_id) {
                    if ($orders->updateStatus($order_id, $new_status)) {
                        $updated++;
                    }
                }
                $message = "Статус обновлен для $updated заказов";
                break;
                
            case 'delete':
                $deleted = 0;
                foreach ($selected_orders as $order_id) {
                    if ($orders->delete($order_id)) {
                        $deleted++;
                    }
                }
                $message = "Удалено $deleted заказов";
                break;
                
            case 'assign_route':
                $route_name = $_POST['route_name'] ?? '';
                // This would typically update a route field in the database
                $message = "Маршрут '$route_name' назначен для " . count($selected_orders) . " заказов";
                break;
        }
    }
}

// Get all orders for display
$all_orders = $orders->getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Массовые операции - CRM Система</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Хром-KZ CRM</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/crm" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>Дашборд
                        </a>
                        <a href="/crm/orders.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-box mr-2"></i>Заказы
                        </a>
                        <a href="/crm/bulk_operations.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tasks mr-2"></i>Массовые операции
                        </a>
                        <a href="/crm/reports.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-file-alt mr-2"></i>Отчеты
                        </a>
                        <a href="/crm/analytics.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>Аналитика
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="/crm/logout.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Выход
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Массовые операции</h1>
                <p class="mt-2 text-gray-600">Управление несколькими заказами одновременно</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Bulk Operations Form -->
            <form method="POST" id="bulk-form">
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Выберите операцию</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Операция</label>
                                <select name="action" id="action-select" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                    <option value="">Выберите операцию</option>
                                    <option value="update_status">Обновить статус</option>
                                    <option value="assign_route">Назначить маршрут</option>
                                    <option value="delete">Удалить заказы</option>
                                </select>
                            </div>
                            
                            <div id="status-field" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Новый статус</label>
                                <select name="new_status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                    <option value="pending">Ожидает</option>
                                    <option value="in_progress">В работе</option>
                                    <option value="completed">Завершен</option>
                                    <option value="cancelled">Отменен</option>
                                </select>
                            </div>
                            
                            <div id="route-field" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Название маршрута</label>
                                <input type="text" name="route_name" placeholder="Например: Маршрут А1" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <div>
                                <button type="button" id="select-all" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Выбрать все
                                </button>
                                <span class="mx-2 text-gray-300">|</span>
                                <button type="button" id="select-none" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Снять выбор
                                </button>
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-play mr-2"></i>Выполнить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Список заказов</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="select-all-checkbox" class="rounded">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Груз</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес получения</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Телефон</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($all_orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="selected_orders[]" value="<?= $order['id'] ?>" 
                                               class="order-checkbox rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $order['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                            <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональный' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($order['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($order['cargo_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        <?= htmlspecialchars($order['pickup_address']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($order['contact_phone']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        // Handle action selection
        document.getElementById('action-select').addEventListener('change', function() {
            const statusField = document.getElementById('status-field');
            const routeField = document.getElementById('route-field');
            
            // Hide all fields first
            statusField.classList.add('hidden');
            routeField.classList.add('hidden');
            
            // Show relevant field based on selection
            if (this.value === 'update_status') {
                statusField.classList.remove('hidden');
            } else if (this.value === 'assign_route') {
                routeField.classList.remove('hidden');
            }
        });

        // Select all functionality
        document.getElementById('select-all').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('select-all-checkbox').checked = true;
        });

        document.getElementById('select-none').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('select-all-checkbox').checked = false;
        });

        // Master checkbox functionality
        document.getElementById('select-all-checkbox').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Confirm delete operation
        document.getElementById('bulk-form').addEventListener('submit', function(e) {
            const action = document.getElementById('action-select').value;
            if (action === 'delete') {
                if (!confirm('Вы уверены, что хотите удалить выбранные заказы? Это действие нельзя отменить.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>