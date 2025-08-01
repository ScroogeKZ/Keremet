<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $orders = new ShipmentOrder();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get calendar events
        $date = $_GET['date'] ?? date('Y-m-d');
        $start_date = $_GET['start'] ?? date('Y-m-d', strtotime($date . ' -7 days'));
        $end_date = $_GET['end'] ?? date('Y-m-d', strtotime($date . ' +7 days'));
        
        $calendar_orders = $orders->getByDateRange($start_date, $end_date);
        
        $events = [];
        foreach ($calendar_orders as $order) {
            $events[] = [
                'id' => $order['id'],
                'title' => "#{$order['id']} - {$order['cargo_type']}",
                'start' => $order['ready_time'] ? 
                          date('Y-m-d', strtotime($order['created_at'])) . 'T' . $order['ready_time'] :
                          date('Y-m-d\TH:i:s', strtotime($order['created_at'])),
                'backgroundColor' => match($order['status']) {
                    'new' => '#fbbf24',
                    'processing' => '#3b82f6', 
                    'delivered' => '#10b981',
                    'cancelled' => '#ef4444',
                    default => '#6b7280'
                },
                'borderColor' => match($order['status']) {
                    'new' => '#f59e0b',
                    'processing' => '#2563eb',
                    'delivered' => '#059669', 
                    'cancelled' => '#dc2626',
                    default => '#4b5563'
                },
                'extendedProps' => [
                    'order_type' => $order['order_type'],
                    'status' => $order['status'],
                    'pickup_address' => $order['pickup_address'],
                    'contact_name' => $order['contact_name'],
                    'contact_phone' => $order['contact_phone']
                ]
            ];
        }
        
        echo json_encode(['events' => $events]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update order schedule
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = $input['order_id'] ?? null;
        $new_date = $input['date'] ?? null;
        $new_time = $input['time'] ?? null;
        
        if (!$order_id || !$new_date) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        $result = $orders->updateSchedule($order_id, $new_date, $new_time);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Расписание обновлено']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update schedule']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Calendar API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>