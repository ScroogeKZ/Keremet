<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

// Logout user
Auth::logout();

// Redirect to login page
header('Location: /crm/login.php');
exit;
?>