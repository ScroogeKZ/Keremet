<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Setting;
use App\TelegramService;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication for admin actions
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $settings = new Setting();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get current Telegram configuration status
        $telegram_token = $settings->get('telegram_bot_token');
        $telegram_chat_id = $settings->get('telegram_chat_id');
        
        $telegram = new TelegramService();
        
        echo json_encode([
            'success' => true,
            'configured' => $telegram->isConfigured(),
            'has_token' => !empty($telegram_token),
            'has_chat_id' => !empty($telegram_chat_id),
            'status' => $telegram->isConfigured() ? 'active' : 'not_configured'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Configure Telegram settings
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['test'])) {
            // Test current configuration
            $telegram = new TelegramService();
            
            if (!$telegram->isConfigured()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Telegram не настроен. Добавьте токен бота и ID чата в настройках.'
                ]);
                exit;
            }
            
            $test_message = "🧪 *Тестовое сообщение*\n\n" .
                           "Система уведомлений Хром-KZ работает корректно!\n" .
                           "*Время:* " . date('d.m.Y H:i:s');
            
            $result = $telegram->sendMessage($test_message);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Тестовое сообщение отправлено успешно!' : 'Ошибка отправки сообщения'
            ]);
            
        } else {
            // Update configuration
            $token = $input['bot_token'] ?? '';
            $chat_id = $input['chat_id'] ?? '';
            
            if (empty($token) || empty($chat_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Токен бота и ID чата обязательны']);
                exit;
            }
            
            // Save settings
            $settings->set('telegram_bot_token', $token);
            $settings->set('telegram_chat_id', $chat_id);
            
            // Test new configuration
            putenv("TELEGRAM_BOT_TOKEN=$token");
            putenv("TELEGRAM_CHAT_ID=$chat_id");
            
            $telegram = new TelegramService();
            $test_result = $telegram->sendMessage("✅ *Настройка завершена*\n\nTelegram бот успешно настроен для уведомлений Хром-KZ!");
            
            echo json_encode([
                'success' => true,
                'message' => 'Настройки сохранены',
                'test_sent' => $test_result
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Telegram config API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>