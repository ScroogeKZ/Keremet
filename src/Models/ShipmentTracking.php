<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class ShipmentTracking
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    /**
     * Add a tracking entry for an order
     */
    public function addTrackingEntry($orderId, $status, $location = null, $description = null, $createdBy = 'system')
    {
        $sql = "INSERT INTO shipment_tracking (order_id, status, location, description, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId, $status, $location, $description, $createdBy]);
        } catch (PDOException $e) {
            error_log("Error adding tracking entry: " . $e->getMessage());
            throw new Exception("Failed to add tracking entry");
        }
    }

    /**
     * Get tracking history for an order
     */
    public function getTrackingHistory($orderId)
    {
        $sql = "SELECT status, location, description, timestamp, created_by 
                FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY timestamp ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting tracking history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get latest tracking entry for an order
     */
    public function getLatestTracking($orderId)
    {
        $sql = "SELECT status, location, description, timestamp, created_by 
                FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY timestamp DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting latest tracking: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update order status and add tracking entry
     */
    public function updateOrderStatus($orderId, $newStatus, $location = null, $description = null, $createdBy = 'admin')
    {
        try {
            $this->db->beginTransaction();

            // Update the main order status
            $updateSql = "UPDATE shipment_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$newStatus, $orderId]);

            // Add tracking entry
            $this->addTrackingEntry($orderId, $newStatus, $location, $description, $createdBy);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating order status: " . $e->getMessage());
            throw new Exception("Failed to update order status");
        }
    }

    /**
     * Initialize tracking for a new order
     */
    public function initializeOrderTracking($orderId)
    {
        $this->addTrackingEntry($orderId, 'Заказ создан', 'Астана, офис', 'Заявка на доставку принята в обработку', 'system');
    }

    /**
     * Get tracking statistics
     */
    public function getTrackingStats($orderId)
    {
        $sql = "SELECT COUNT(*) as total_entries, 
                       MIN(timestamp) as first_entry, 
                       MAX(timestamp) as last_entry
                FROM shipment_tracking 
                WHERE order_id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting tracking stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate progress percentage based on tracking history
     */
    public function calculateProgress($orderId, $currentStatus)
    {
        $statusProgressMap = [
            'pending' => 10,
            'new' => 5,
            'confirmed' => 20,
            'processing' => 30,
            'in_progress' => 60,
            'out_for_delivery' => 80,
            'completed' => 100,
            'delivered' => 100,
            'cancelled' => 0
        ];

        return $statusProgressMap[$currentStatus] ?? 0;
    }
}