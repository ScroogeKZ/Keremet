<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Auth;

Auth::requireAuth();

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo 'Файл не указан';
    exit;
}

$filename = basename($_GET['file']);
$filepath = sys_get_temp_dir() . '/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'Файл не найден';
    exit;
}

// Проверяем, что файл создан недавно (не старше 1 часа)
if (time() - filemtime($filepath) > 3600) {
    unlink($filepath);
    http_response_code(410);
    echo 'Файл устарел';
    exit;
}

// Отправляем файл
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);

// Удаляем временный файл
unlink($filepath);
?>