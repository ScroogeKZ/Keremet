<?php
namespace App;

class EmailService
{
    private $from_email;
    private $from_name;
    
    public function __construct()
    {
        $this->from_email = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@chrome-kz.com';
        $this->from_name = 'Хром-KZ Логистика';
    }
    
    public function sendOrderNotification($order, $type = 'created')
    {
        // Ensure order array has required fields
        if (!is_array($order) || empty($order['id'])) {
            error_log("EmailService: Invalid order data provided");
            return false;
        }
        
        $subject = match($type) {
            'created' => "Новый заказ №{$order['id']} - Хром-KZ",
            'status_updated' => "Статус заказа №{$order['id']} изменен - Хром-KZ",
            default => "Уведомление по заказу №{$order['id']} - Хром-KZ"
        };
        
        $message = $this->buildOrderEmailTemplate($order, $type);
        
        // Отправляем администратору
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@chrome-kz.com';
        $this->sendEmail($admin_email, $subject, $message);
        
        // Отправляем клиенту если есть email
        if (!empty($order['contact_email'])) {
            $this->sendEmail($order['contact_email'], $subject, $message);
        }
        
        return true;
    }
    
    private function buildOrderEmailTemplate($order, $type)
    {
        // Safely get status with fallback
        $status = $order['status'] ?? 'new';
        $statusText = match($status) {
            'new' => 'Новый',
            'processing' => 'В обработке', 
            'completed' => 'Завершен',
            default => $status
        };
        
        $orderTypeText = ($order['order_type'] ?? 'astana') === 'astana' ? 'Доставка по Астане' : 'Межгородская доставка';
        
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #1f2937;'>Хром-KZ Логистика</h2>
            <h3 style='color: #374151;'>Заказ №{$order['id']} - {$orderTypeText}</h3>
            
            <div style='background: #f9fafb; padding: 20px; margin: 20px 0;'>
                <h4 style='margin-top: 0;'>Основная информация:</h4>
                <p><strong>Статус:</strong> {$statusText}</p>
                <p><strong>Тип груза:</strong> " . ($order['cargo_type'] ?? 'Не указан') . "</p>
                <p><strong>Вес:</strong> " . ($order['weight'] ?? 'Не указан') . " кг</p>
                <p><strong>Габариты:</strong> " . ($order['dimensions'] ?? 'Не указаны') . "</p>
            </div>
            
            <div style='background: #f9fafb; padding: 20px; margin: 20px 0;'>
                <h4 style='margin-top: 0;'>Контактная информация:</h4>
                <p><strong>Имя:</strong> " . ($order['contact_name'] ?? 'Не указано') . "</p>
                <p><strong>Телефон:</strong> " . ($order['contact_phone'] ?? 'Не указан') . "</p>
            </div>
            
            <div style='background: #f9fafb; padding: 20px; margin: 20px 0;'>
                <h4 style='margin-top: 0;'>Адреса:</h4>
                <p><strong>Адрес забора:</strong> " . ($order['pickup_address'] ?? 'Не указан') . "</p>";
        
        if (!empty($order['delivery_address'])) {
            $html .= "<p><strong>Адрес доставки:</strong> {$order['delivery_address']}</p>";
        }
        
        if (!empty($order['pickup_city'])) {
            $html .= "<p><strong>Город отправления:</strong> {$order['pickup_city']}</p>";
        }
        
        if (!empty($order['destination_city'])) {
            $html .= "<p><strong>Город назначения:</strong> {$order['destination_city']}</p>";
        }
        
        $html .= "
            </div>
            
            <div style='background: #f3f4f6; padding: 15px; margin: 20px 0; border-left: 4px solid #3b82f6;'>
                <p style='margin: 0;'><strong>Время готовности:</strong> " . ($order['ready_time'] ?? 'Не указано') . "</p>
            </div>
        </div>";
        
        return $html;
    }
    
    private function sendEmail($to, $subject, $message)
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$this->from_name} <{$this->from_email}>",
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Log email instead of actually sending (for development)
        error_log("Email would be sent to: {$to}");
        error_log("Subject: {$subject}");
        
        return true; // Always return true for development
    }
}