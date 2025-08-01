<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Notification;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $notifications = new Notification();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get notifications
        $limit = $_GET['limit'] ?? 20;
        $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
        
        $result = $notifications->getAll(['limit' => $limit, 'unread_only' => $unread_only]);
        
        echo json_encode([
            'success' => true,
            'notifications' => $result,
            'unread_count' => $notifications->getUnreadCount()
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create notification
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['title', 'message', 'type'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                exit;
            }
        }
        
        $result = $notifications->create([
            'title' => $input['title'],
            'message' => $input['message'],
            'type' => $input['type'],
            'user_id' => Auth::getCurrentUser()['id']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'notification' => $result]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create notification']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Mark as read/unread
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['id'] ?? null;
        $mark_read = $input['read'] ?? true;
        
        if (!$notification_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing notification ID']);
            exit;
        }
        
        if ($input['action'] === 'mark_all_read') {
            $result = $notifications->markAllAsRead(Auth::getCurrentUser()['id']);
        } else {
            $result = $notifications->markAsRead($notification_id, $mark_read);
        }
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update notification']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete notification
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['id'] ?? null;
        
        if (!$notification_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing notification ID']);
            exit;
        }
        
        $result = $notifications->delete($notification_id);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete notification']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Notifications API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>