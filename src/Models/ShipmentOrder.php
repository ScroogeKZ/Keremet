<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

require_once __DIR__ . '/../../config/database.php';

class ShipmentOrder {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO shipment_orders (
            order_type, pickup_city, pickup_address, ready_time, contact_name, contact_phone,
            cargo_type, weight, dimensions, destination_city, delivery_address,
            delivery_method, desired_arrival_date, recipient_contact, recipient_phone, notes, comment, status
        ) VALUES (
            :order_type, :pickup_city, :pickup_address, :ready_time, :contact_name, :contact_phone,
            :cargo_type, :weight, :dimensions, :destination_city, :delivery_address,
            :delivery_method, :desired_arrival_date, :recipient_contact, :recipient_phone, :notes, :comment, :status
        ) RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $status = $data['status'] ?? 'new';
            
            $stmt->execute([
                ':order_type' => $data['order_type'],
                ':pickup_city' => $data['pickup_city'] ?? null,
                ':pickup_address' => $data['pickup_address'],
                ':ready_time' => $data['ready_time'],
                ':contact_name' => $data['contact_name'],
                ':contact_phone' => $data['contact_phone'],
                ':cargo_type' => $data['cargo_type'],
                ':weight' => $data['weight'] ? floatval($data['weight']) : null,
                ':dimensions' => $data['dimensions'],
                ':destination_city' => $data['destination_city'] ?? null,
                ':delivery_address' => $data['delivery_address'] ?? null,
                ':delivery_method' => $data['delivery_method'] ?? null,
                ':desired_arrival_date' => $data['desired_arrival_date'] ?? null,
                ':recipient_contact' => $data['recipient_contact'] ?? null,
                ':recipient_phone' => $data['recipient_phone'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':comment' => $data['comment'] ?? null,
                ':status' => $status
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating shipment order: " . $e->getMessage());
            throw new Exception("Failed to create shipment order: " . $e->getMessage());
        }
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT * FROM shipment_orders WHERE 1=1";
        $params = [];
        
        if (isset($filters['order_type'])) {
            $sql .= " AND order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (contact_name ILIKE :search OR contact_phone ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = (int)$filters['offset'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching shipment orders: " . $e->getMessage());
            throw new Exception("Failed to fetch shipment orders");
        }
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM shipment_orders WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching shipment order: " . $e->getMessage());
            throw new Exception("Failed to fetch shipment order");
        }
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE shipment_orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':status' => $status
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating shipment order status: " . $e->getMessage());
            throw new Exception("Failed to update shipment order status");
        }
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'order_type', 'pickup_address', 'ready_time', 'cargo_type', 'weight', 'dimensions',
            'contact_name', 'contact_phone', 'notes', 'pickup_city', 'destination_city',
            'delivery_address', 'delivery_method', 'desired_arrival_date', 'status', 'shipping_cost'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            throw new Exception("No fields to update");
        }
        
        $sql = "UPDATE shipment_orders SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating shipment order: " . $e->getMessage());
            throw new Exception("Failed to update shipment order");
        }
    }
    
    public function getByClientPhone($phone) {
        $sql = "SELECT * FROM shipment_orders WHERE contact_phone = :phone ORDER BY created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':phone' => $phone]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching orders by client phone: " . $e->getMessage());
            throw new Exception("Failed to fetch client orders");
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM shipment_orders WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);
            return $result;
        } catch (PDOException $e) {
            error_log("Error deleting shipment order: " . $e->getMessage());
            throw new Exception("Failed to delete shipment order");
        }
    }
    
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM shipment_orders WHERE 1=1";
        $params = [];
        
        if (isset($filters['order_type'])) {
            $sql .= " AND order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (contact_name ILIKE :search OR contact_phone ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error counting shipment orders: " . $e->getMessage());
            throw new Exception("Failed to count shipment orders");
        }
    }
    
    public function getOrdersByDateRange($days = 7) {
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM shipment_orders 
                WHERE created_at >= NOW() - INTERVAL :days DAY 
                GROUP BY DATE(created_at) 
                ORDER BY date DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting orders by date range: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPopularDestinations($limit = 5) {
        $sql = "SELECT destination_city, COUNT(*) as count 
                FROM shipment_orders 
                WHERE destination_city IS NOT NULL AND destination_city != '' 
                GROUP BY destination_city 
                ORDER BY count DESC 
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':limit' => $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting popular destinations: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStatusDistribution() {
        $sql = "SELECT status, COUNT(*) as count FROM shipment_orders GROUP BY status ORDER BY count DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting status distribution: " . $e->getMessage());
            return [];
        }
    }
    
    public function getOrderTypeDistribution() {
        $sql = "SELECT order_type, COUNT(*) as count FROM shipment_orders GROUP BY order_type";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting order type distribution: " . $e->getMessage());
            return [];
        }
    }
}