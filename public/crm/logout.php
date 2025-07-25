<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::logout();
header('Location: /crm/login.php');
exit;