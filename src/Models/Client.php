<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

require_once __DIR__ . '/../../config/database.php';

class Client {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO clients (phone, email, password_hash) VALUES (:phone, :email, :password_hash) RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':phone' => $data['phone'],
                ':email' => $data['email'] ?? null,
                ':password_hash' => $hashedPassword
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                throw new Exception("Пользователь с таким номером телефона уже существует");
            }
            error_log("Error creating client: " . $e->getMessage());
            throw new Exception("Ошибка создания пользователя");
        }
    }
    
    public function findByPhone($phone) {
        $sql = "SELECT * FROM clients WHERE phone = :phone";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':phone' => $phone]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding client: " . $e->getMessage());
            throw new Exception("Ошибка поиска пользователя");
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM clients WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding client by ID: " . $e->getMessage());
            throw new Exception("Ошибка поиска пользователя");
        }
    }
    
    public function verifyPassword($plainPassword, $hashedPassword) {
        if ($hashedPassword === null) {
            return false;
        }
        return password_verify($plainPassword, $hashedPassword);
    }
    
    public function setVerificationCode($phone, $code) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Обновляем клиента
        $sql = "UPDATE clients SET verification_code = :code, verification_expires_at = :expires_at WHERE phone = :phone";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':code' => $code,
                ':expires_at' => $expiresAt,
                ':phone' => $phone
            ]);
            
            // Добавляем запись в таблицу кодов
            $sql2 = "INSERT INTO verification_codes (phone, code, expires_at) VALUES (:phone, :code, :expires_at)";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([
                ':phone' => $phone,
                ':code' => $code,
                ':expires_at' => $expiresAt
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error setting verification code: " . $e->getMessage());
            throw new Exception("Ошибка установки кода верификации");
        }
    }
    
    public function verifyCode($phone, $code) {
        $sql = "SELECT * FROM clients WHERE phone = :phone AND verification_code = :code 
                AND verification_expires_at > NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':phone' => $phone,
                ':code' => $code
            ]);
            
            $client = $stmt->fetch();
            
            if ($client) {
                // Активируем аккаунт
                $this->activateAccount($phone);
                
                // Помечаем код как использованный
                $sql2 = "UPDATE verification_codes SET used = TRUE WHERE phone = :phone AND code = :code";
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([':phone' => $phone, ':code' => $code]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error verifying code: " . $e->getMessage());
            throw new Exception("Ошибка верификации кода");
        }
    }
    
    public function activateAccount($phone) {
        $sql = "UPDATE clients SET is_verified = TRUE, verification_code = NULL, 
                verification_expires_at = NULL WHERE phone = :phone";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':phone' => $phone]);
            return true;
        } catch (PDOException $e) {
            error_log("Error activating account: " . $e->getMessage());
            throw new Exception("Ошибка активации аккаунта");
        }
    }
    
    public function isVerified($phone) {
        $sql = "SELECT is_verified FROM clients WHERE phone = :phone";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':phone' => $phone]);
            $result = $stmt->fetch();
            
            return $result ? (bool)$result['is_verified'] : false;
        } catch (PDOException $e) {
            error_log("Error checking verification status: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanExpiredCodes() {
        $sql = "DELETE FROM verification_codes WHERE expires_at < NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error cleaning expired codes: " . $e->getMessage());
            return false;
        }
    }
}