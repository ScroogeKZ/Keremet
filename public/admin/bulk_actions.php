<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;
use App\EmailService;

header('Content-Type: application/json');

if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action']) || !isset($data['order_ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверные данные']);
    exit;
}

$action = $data['action'];
$orderIds = $data['order_ids'];
$newStatus = $data['status'] ?? null;

if (empty($orderIds) || !is_array($orderIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Не выбраны заказы']);
    exit;
}

try {
    $pdo = \Database::getInstance()->getConnection();
    $emailService = new EmailService();
    $successCount = 0;
    
    switch ($action) {
        case 'update_status':
            if (!$newStatus || !in_array($newStatus, ['new', 'processing', 'completed'])) {
                throw new Exception('Неверный статус');
            }
            
            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE shipment_orders 
                SET status = ?, updated_at = NOW() 
                WHERE id IN ($placeholders)
            ");
            
            $params = array_merge([$newStatus], $orderIds);
            $stmt->execute($params);
            $successCount = $stmt->rowCount();
            
            // Отправляем уведомления для каждого заказа
            $orderStmt = $pdo->prepare("
                SELECT * FROM shipment_orders WHERE id IN ($placeholders)
            ");
            $orderStmt->execute($orderIds);
            $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orders as $order) {
                try {
                    $order['status'] = $newStatus; // Обновляем статус для уведомления
                    $emailService->sendOrderNotification($order, 'status_updated');
                } catch (Exception $e) {
                    error_log("Email notification failed for order {$order['id']}: " . $e->getMessage());
                }
            }
            
            break;
            
        case 'delete':
            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM shipment_orders WHERE id IN ($placeholders)");
            $stmt->execute($orderIds);
            $successCount = $stmt->rowCount();
            break;
            
        case 'export':
            // Экспорт выбранных заказов
            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT id, order_type, status, pickup_address, destination_city, 
                       cargo_type, weight, dimensions, contact_name, contact_phone, 
                       shipping_cost, created_at
                FROM shipment_orders 
                WHERE id IN ($placeholders)
                ORDER BY created_at DESC
            ");
            $stmt->execute($orderIds);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Создаем CSV
            $filename = 'selected_orders_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            $file = fopen($filepath, 'w');
            // BOM для корректного отображения в Excel
            fwrite($file, "\xEF\xBB\xBF");
            
            // Заголовки
            fputcsv($file, [
                'ID', 'Тип заказа', 'Статус', 'Адрес забора', 'Город назначения',
                'Тип груза', 'Вес', 'Размеры', 'Контакт', 'Телефон', 'Стоимость', 'Дата создания'
            ], ';');
            
            // Данные
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order['id'],
                    $order['order_type'] === 'astana' ? 'Астана' : 'Межгород',
                    match($order['status']) {
                        'new' => 'Новый',
                        'processing' => 'В работе',
                        'completed' => 'Завершен',
                        default => $order['status']
                    },
                    $order['pickup_address'] ?? '',
                    $order['destination_city'] ?? 'Астана',
                    $order['cargo_type'] ?? '',
                    $order['weight'] ?? '',
                    $order['dimensions'] ?? '',
                    $order['contact_name'] ?? '',
                    $order['contact_phone'] ?? '',
                    $order['shipping_cost'] ?? '',
                    date('d.m.Y H:i', strtotime($order['created_at']))
                ], ';');
            }
            
            fclose($file);
            
            echo json_encode([
                'success' => true,
                'download_url' => '/admin/download_temp.php?file=' . urlencode($filename),
                'message' => 'Файл готов к скачиванию'
            ]);
            exit;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Обработано заказов: $successCount"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>