<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

// Check if user is logged in
if (Auth::isLoggedIn()) {
    // User is logged in, redirect to dashboard
    header('Location: panel.php');
    exit();
} else {
    // User is not logged in, redirect to login
    header('Location: login.php');
    exit();
}
?>