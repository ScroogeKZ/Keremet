<?php
namespace App\Models;

use PDO;

class ActivityLog
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = \Database::getInstance()->getConnection();
    }
    
    public function log($action, $order_id = null, $details = null)
    {
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_logs (user_id, username, action, order_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $user_id,
            $username,
            $action,
            $order_id,
            json_encode($details, JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    public function getActivities($limit = 50, $order_id = null)
    {
        $where = '';
        $params = [];
        
        if ($order_id) {
            $where = 'WHERE order_id = ?';
            $params[] = $order_id;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM activity_logs 
            $where
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOrderHistory($order_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM activity_logs 
            WHERE order_id = ?
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}