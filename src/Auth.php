<?php

namespace App;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Models/User.php';

use App\Models\User;
use Exception;

class Auth {
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    public static function login($username, $password) {
        try {
            $userModel = new User();
            $user = $userModel->findByUsername($username);
            
            if ($user && $userModel->verifyPassword($password, $user['password'])) {
                self::startSession();
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
        }
        
        return false;
    }
    
    public static function logout() {
        self::startSession();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ];
        }
        return null;
    }
    
    // Alias for backward compatibility
    public static function isAuthenticated() {
        return self::isLoggedIn();
    }
}