<?php

namespace App\Models;

class Notification
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    /**
     * Create a new notification
     */
    public function create($data)
    {
        $sql = "INSERT INTO notifications (type, title, message, related_id, related_type, icon, color, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['type'],
            $data['title'],
            $data['message'],
            $data['related_id'] ?? null,
            $data['related_type'] ?? null,
            $data['icon'] ?? 'fas fa-bell',
            $data['color'] ?? 'blue',
            $data['user_id'] ?? null
        ]);
    }

    /**
     * Get all notifications for a user
     */
    public function getForUser($userId = null, $limit = 50)
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id IS NULL OR user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT * FROM notifications WHERE 1=1";
        $params = [];
        
        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $sql .= " AND is_read = FALSE";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read original method
     */
    public function markAsReadOriginal($id, $userId = null)
    {
        $sql = "UPDATE notifications SET is_read = TRUE 
                WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsReadOriginal($userId = null)
    {
        $sql = "UPDATE notifications SET is_read = TRUE 
                WHERE user_id IS NULL OR user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Delete notification
     */
    public function delete($id, $userId = null)
    {
        $sql = "DELETE FROM notifications 
                WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE is_read = FALSE AND (user_id IS NULL OR user_id = ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Create notification for new order
     */
    public function createNewOrderNotification($orderId, $orderType, $contactName)
    {
        return $this->create([
            'type' => 'new_order',
            'title' => "Новый заказ #{$orderId}",
            'message' => "Получен новый {$orderType} заказ от {$contactName}",
            'related_id' => $orderId,
            'related_type' => 'order',
            'icon' => 'fas fa-box',
            'color' => 'blue'
        ]);
    }

    /**
     * Create notification for status change
     */
    public function createStatusChangeNotification($orderId, $oldStatus, $newStatus)
    {
        $statusNames = [
            'new' => 'новый',
            'confirmed' => 'подтвержден',
            'in_transit' => 'в пути',
            'delivered' => 'доставлен',
            'cancelled' => 'отменен'
        ];

        return $this->create([
            'type' => 'status_change',
            'title' => "Заказ #{$orderId} - изменение статуса",
            'message' => "Статус изменен с '{$statusNames[$oldStatus]}' на '{$statusNames[$newStatus]}'",
            'related_id' => $orderId,
            'related_type' => 'order',
            'icon' => $newStatus === 'delivered' ? 'fas fa-check-circle' : 'fas fa-sync',
            'color' => $newStatus === 'delivered' ? 'green' : 'yellow'
        ]);
    }

    /**
     * Create system notification
     */
    public function createSystemNotification($title, $message, $urgent = false)
    {
        return $this->create([
            'type' => $urgent ? 'urgent' : 'system',
            'title' => $title,
            'message' => $message,
            'icon' => $urgent ? 'fas fa-exclamation-triangle' : 'fas fa-cogs',
            'color' => $urgent ? 'red' : 'gray'
        ]);
    }

    /**
     * Format time for display
     */
    public function formatTime($timestamp)
    {
        $now = new \DateTime();
        $time = new \DateTime($timestamp);
        $diff = $now->diff($time);

        if ($diff->days > 0) {
            return $diff->days . ' дн. назад';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ч. назад';
        } elseif ($diff->i > 0) {
            return $diff->i . ' мин. назад';
        } else {
            return 'только что';
        }
    }
    
    public function getUnreadCount($user_id = null) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE is_read = FALSE";
        $params = [];
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function markAllAsRead($user_id) {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$user_id]);
        } catch (\PDOException $e) {
            error_log("Error marking all as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAsRead($id, $read = true) {
        $sql = "UPDATE notifications SET is_read = ? WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$read, $id]);
        } catch (\PDOException $e) {
            error_log("Error marking as read: " . $e->getMessage());
            return false;
        }
    }
}