<?php
// Script to create initial admin user
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;

try {
    $userModel = new User();
    
    // Check if admin already exists
    $existingAdmin = $userModel->findByUsername('admin');
    
    if (!$existingAdmin) {
        $admin = $userModel->create('admin', 'admin123');
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Please change the password after login.\n";
    } else {
        echo "Admin user already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>