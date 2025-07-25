<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Models\ActivityLog;
use App\Auth;

header('Content-Type: application/json');

if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$action = $_GET['action'] ?? '';
$orderModel = new ShipmentOrder();
$activityLog = new ActivityLog();

try {
    switch ($action) {
        case 'get_order':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID заказа не указан');
            }
            
            $order = $orderModel->getById($id);
            if (!$order) {
                throw new Exception('Заказ не найден');
            }
            
            echo json_encode($order);
            break;
            
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не поддерживается');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            $newStatus = $data['status'] ?? null;
            
            if (!$id || !$newStatus) {
                throw new Exception('Не указаны обязательные параметры');
            }
            
            if (!in_array($newStatus, ['new', 'processing', 'completed'])) {
                throw new Exception('Неверный статус');
            }
            
            $oldOrder = $orderModel->getById($id);
            $result = $orderModel->updateStatus($id, $newStatus);
            
            if ($result) {
                $activityLog->log('status_updated', $id, [
                    'old_status' => $oldOrder['status'],
                    'new_status' => $newStatus
                ]);
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Ошибка обновления статуса');
            }
            break;
            
        case 'delete_order':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не поддерживается');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID заказа не указан');
            }
            
            $order = $orderModel->getById($id);
            $result = $orderModel->delete($id);
            
            if ($result) {
                $activityLog->log('order_deleted', $id, [
                    'order_data' => $order
                ]);
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Ошибка удаления заказа');
            }
            break;
            
        case 'get_stats':
            $pdo = \Database::getInstance()->getConnection();
            
            $stats = [
                'total_orders' => 0,
                'new_orders' => 0,
                'processing_orders' => 0,
                'completed_orders' => 0,
                'total_cost' => 0,
                'avg_cost' => 0
            ];
            
            // Общая статистика
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(COALESCE(shipping_cost, 0)) as total_cost,
                    AVG(COALESCE(shipping_cost, 0)) as avg_cost
                FROM shipment_orders
            ");
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $stats = [
                    'total_orders' => (int)$result['total'],
                    'new_orders' => (int)$result['new_count'],
                    'processing_orders' => (int)$result['processing_count'],
                    'completed_orders' => (int)$result['completed_count'],
                    'total_cost' => (float)$result['total_cost'],
                    'avg_cost' => (float)$result['avg_cost']
                ];
            }
            
            echo json_encode($stats);
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                echo json_encode([]);
                break;
            }
            
            $pdo = \Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                SELECT id, order_type, status, pickup_address, contact_name, 
                       contact_phone, created_at
                FROM shipment_orders 
                WHERE 
                    id::text ILIKE :query 
                    OR contact_name ILIKE :query 
                    OR contact_phone ILIKE :query 
                    OR pickup_address ILIKE :query
                ORDER BY created_at DESC
                LIMIT 10
            ");
            
            $searchTerm = '%' . $query . '%';
            $stmt->execute([':query' => $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($results);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>