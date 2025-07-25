<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            Auth::requireAuth();
            $shipmentOrder = new ShipmentOrder();
            $filters = [];
            
            if (isset($_GET['order_type'])) {
                $filters['order_type'] = $_GET['order_type'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            $orders = $shipmentOrder->getAll($filters);
            echo json_encode(['success' => true, 'data' => $orders]);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
                exit;
            }
            
            $shipmentOrder = new ShipmentOrder();
            $result = $shipmentOrder->create($input);
            
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to create order']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>