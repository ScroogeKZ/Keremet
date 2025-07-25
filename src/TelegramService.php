<?php

namespace App;

use Exception;

class TelegramService {
    private $botToken;
    private $chatId;
    
    public function __construct() {
        $this->botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
        $this->chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID');
    }
    
    public function isConfigured(): bool {
        return !empty($this->botToken) && !empty($this->chatId);
    }
    
    public function sendNewOrderNotification($order): bool {
        if (!$this->isConfigured()) {
            error_log('Telegram не настроен - пропускаем уведомление');
            return false;
        }
        
        try {
            $orderTypeText = $order['order_type'] === 'astana' ? 'Астана' : 'Регионы';
            
            $message = "🚚 *Новый заказ #{$order['id']}*\n\n" .
                "*Тип:* {$orderTypeText}\n" .
                "*Клиент:* {$order['contact_name']}\n" .
                "*Телефон:* {$order['contact_phone']}\n" .
                "*Груз:* {$order['cargo_type']} ({$order['weight']} кг)\n";
            
            if (!empty($order['pickup_city'])) {
                $message .= "*Город отправления:* {$order['pickup_city']}\n";
            }
            
            $message .= "*Адрес забора:* {$order['pickup_address']}\n";
            
            if (!empty($order['destination_city'])) {
                $message .= "*Город назначения:* {$order['destination_city']}\n";
            }
            
            if (!empty($order['delivery_address'])) {
                $message .= "*Адрес доставки:* {$order['delivery_address']}\n";
            }
            
            $message .= "*Время готовности:* {$order['ready_time']}\n";
            $message .= "*Создан:* " . date('d.m.Y H:i', strtotime($order['created_at']));
            
            return $this->sendMessage($message);
            
        } catch (Exception $e) {
            error_log('Ошибка отправки Telegram сообщения: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sendStatusUpdateNotification($order, $oldStatus, $newStatus): bool {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $statusMap = [
                'new' => '🆕 Новый',
                'processing' => '⏳ В обработке',
                'completed' => '✅ Завершен'
            ];
            
            $oldStatusText = $statusMap[$oldStatus] ?? $oldStatus;
            $newStatusText = $statusMap[$newStatus] ?? $newStatus;
            
            $message = "📋 *Изменение статуса заказа #{$order['id']}*\n\n" .
                "*Статус изменен:* {$oldStatusText} → {$newStatusText}\n" .
                "*Клиент:* {$order['contact_name']}\n" .
                "*Телефон:* {$order['contact_phone']}\n" .
                "*Обновлено:* " . date('d.m.Y H:i');
            
            return $this->sendMessage($message);
            
        } catch (Exception $e) {
            error_log('Ошибка отправки Telegram уведомления о статусе: ' . $e->getMessage());
            return false;
        }
    }
    
    private function sendMessage($message): bool {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ]);
        
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log('Ошибка отправки HTTP запроса в Telegram');
            return false;
        }
        
        $response = json_decode($result, true);
        
        if (!$response['ok']) {
            error_log('Telegram API ошибка: ' . $response['description']);
            return false;
        }
        
        return true;
    }
}