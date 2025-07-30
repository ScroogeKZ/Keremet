<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

$db = \Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $trackingNumber = $_GET['tracking'] ?? '';
    
    if (empty($trackingNumber)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tracking number required']);
        exit;
    }
    
    try {
        // Проверяем, является ли trackingNumber небольшим положительным числом (ID заказа)
        // ID заказа должен быть от 1 до 999999, телефоны будут больше
        $isOrderId = is_numeric($trackingNumber) && intval($trackingNumber) > 0 && intval($trackingNumber) < 1000000 && !str_contains($trackingNumber, '+');
        
        if ($isOrderId) {
            // Поиск только по ID заказа
            $stmt = $db->prepare("
                SELECT 
                    id,
                    pickup_address,
                    delivery_address,
                    cargo_type,
                    weight,
                    status,
                    created_at,
                    shipping_cost,
                    contact_name,
                    recipient_contact,
                    notes,
                    contact_phone
                FROM shipment_orders 
                WHERE id = ?
            ");
            $stmt->execute([intval($trackingNumber)]);
        } else {
            // Поиск только по номеру телефона заказчика - возвращаем все заказы
            $stmt = $db->prepare("
                SELECT 
                    id,
                    pickup_address,
                    delivery_address,
                    cargo_type,
                    weight,
                    status,
                    created_at,
                    shipping_cost,
                    contact_name,
                    recipient_contact,
                    notes,
                    contact_phone
                FROM shipment_orders 
                WHERE contact_phone = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$trackingNumber]);
        }
        
        if ($isOrderId) {
            // Для поиска по ID - один заказ
            $order = $stmt->fetch();
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                exit;
            }
            $orders = [$order];
        } else {
            // Для поиска по телефону - все заказы
            $orders = $stmt->fetchAll();
            if (empty($orders)) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                exit;
            }
        }
        
        // Функция для получения реальной информации о заказе
        function generateOrderInfo($order) {
            global $db;
            
            // Get real tracking history from database
            $trackingStmt = $db->prepare("
                SELECT status, location, description, timestamp, created_by 
                FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY timestamp ASC
            ");
            $trackingStmt->execute([$order['id']]);
            $trackingEntries = $trackingStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $statusHistory = [];
            
            if (empty($trackingEntries)) {
                // Fallback: Create initial tracking entry if none exists
                $statusHistory[] = [
                    'status' => 'Заказ создан',
                    'timestamp' => $order['created_at'],
                    'description' => 'Заявка на доставку принята в обработку'
                ];
            } else {
                // Use real tracking data
                foreach ($trackingEntries as $entry) {
                    $statusHistory[] = [
                        'status' => $entry['status'],
                        'timestamp' => $entry['timestamp'],
                        'description' => $entry['description'] ?: $entry['status'],
                        'location' => $entry['location']
                    ];
                }
            }
            
            // Расчет примерного времени доставки
            $created = new DateTime($order['created_at']);
            $estimatedDelivery = clone $created;
            if (strpos($order['delivery_address'], 'Астана') !== false) {
                $estimatedDelivery->add(new DateInterval('PT4H')); // 4 часа для Астаны
            } else {
                $estimatedDelivery->add(new DateInterval('P1D')); // 1 день для регионов
            }
            
            return [
                'id' => $order['id'],
                'tracking_number' => "KZ" . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                'pickup_address' => $order['pickup_address'],
                'delivery_address' => $order['delivery_address'],
                'cargo_type' => $order['cargo_type'],
                'weight' => $order['weight'],
                'status' => $order['status'],
                'status_ru' => [
                    'pending' => 'Ожидает обработки',
                    'new' => 'Новый',
                    'in_progress' => 'В пути',
                    'completed' => 'Доставлено',
                    'cancelled' => 'Отменен'
                ][$order['status']] ?? 'Неизвестно',
                'created_at' => $order['created_at'],
                'estimated_delivery' => $estimatedDelivery->format('Y-m-d H:i:s'),
                'shipping_cost' => $order['shipping_cost'],
                'pickup_contact' => $order['contact_name'],
                'delivery_contact' => $order['recipient_contact'],
                'notes' => $order['notes'],
                'status_history' => $statusHistory,
                'tracking_info' => [
                    'current_location' => !empty($trackingEntries) ? 
                        (end($trackingEntries)['location'] ?: 'Астана, офис') : 
                        ($order['status'] === 'completed' ? $order['delivery_address'] : 'Астана, офис'),
                    'next_checkpoint' => $order['status'] === 'completed' ? null : $order['delivery_address'],
                    'progress_percentage' => [
                        'pending' => 10,
                        'new' => 5,
                        'confirmed' => 20,
                        'processing' => 30,
                        'in_progress' => 60,
                        'out_for_delivery' => 80,
                        'completed' => 100,
                        'delivered' => 100,
                        'cancelled' => 0
                    ][$order['status']] ?? 0
                ]
            ];
        }
        
        // Генерируем ответ
        if (count($orders) === 1) {
            // Один заказ - возвращаем в старом формате для совместимости
            $orderInfo = generateOrderInfo($orders[0]);
            $response = [
                'success' => true,
                'order' => $orderInfo,
                'status_history' => $orderInfo['status_history'],
                'tracking_info' => $orderInfo['tracking_info']
            ];
            unset($response['order']['status_history']);
            unset($response['order']['tracking_info']);
        } else {
            // Несколько заказов - возвращаем массив
            $ordersList = [];
            foreach ($orders as $order) {
                $ordersList[] = generateOrderInfo($order);
            }
            $response = [
                'success' => true,
                'multiple_orders' => true,
                'total_orders' => count($orders),
                'phone_number' => $trackingNumber,
                'orders' => $ordersList
            ];
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>