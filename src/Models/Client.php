<?php

namespace App\Models;

use Exception;

class Client
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    /**
     * Create a new client
     */
    public function create($data)
    {
        $sql = "INSERT INTO clients (name, phone, email, password_hash, is_verified) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['phone'], 
            $data['email'] ?? null,
            $data['password_hash'],
            $data['is_verified'] ?? false
        ]);
    }

    /**
     * Get client by phone number
     */
    public function getByPhone($phone)
    {
        $sql = "SELECT * FROM clients WHERE phone = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }

    /**
     * Get client by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM clients WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Update client verification status
     */
    public function updateVerification($phone, $isVerified = true)
    {
        $sql = "UPDATE clients SET is_verified = ?, updated_at = CURRENT_TIMESTAMP WHERE phone = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$isVerified, $phone]);
    }

    /**
     * Get all clients for CRM
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT c.*, 
                       COUNT(so.id) as total_orders,
                       COALESCE(SUM(so.shipping_cost), 0) as total_spent
                FROM clients c
                LEFT JOIN shipment_orders so ON c.id = so.client_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.name ILIKE ? OR c.phone ILIKE ? OR c.email ILIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
            $sql .= " AND c.is_verified = ?";
            $params[] = (bool)$filters['is_verified'];
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get client statistics for dashboard
     */
    public function getStatistics()
    {
        $sql = "SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN is_verified = true THEN 1 END) as verified_clients,
                    COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as new_this_month
                FROM clients";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}