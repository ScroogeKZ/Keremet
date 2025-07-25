<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password) RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':username' => $data['username'],
                ':password' => $hashedPassword
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception("Failed to create user");
        }
    }
    
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user: " . $e->getMessage());
            throw new Exception("Failed to find user");
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            throw new Exception("Failed to find user");
        }
    }
    
    public function verifyPassword($plainPassword, $hashedPassword) {
        if ($hashedPassword === null) {
            return false;
        }
        return password_verify($plainPassword, $hashedPassword);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            throw new Exception("Failed to find user");
        }
    }
    
    public function getAll() {
        $sql = "SELECT id, username, created_at, updated_at FROM users ORDER BY created_at ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            throw new Exception("Failed to fetch users");
        }
    }
    
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $result = $stmt->execute([
                ':id' => $id,
                ':password' => $hashedPassword
            ]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            throw new Exception("Failed to update password");
        }
    }
    
    public function getCount() {
        $sql = "SELECT COUNT(*) as count FROM users";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error counting users: " . $e->getMessage());
            return 0;
        }
    }
}