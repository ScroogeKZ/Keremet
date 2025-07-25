<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

// Require authentication
Auth::requireAuth();

// Set headers for Excel download (CSV format with Excel-compatible encoding)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment;filename="orders_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');
header('Pragma: public');

try {
    $shipmentOrder = new ShipmentOrder();
    
    // Get filters from URL parameters
    $filters = [];
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    if (!empty($_GET['order_type'])) {
        $filters['order_type'] = $_GET['order_type'];
    }
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    
    // Get orders with filters
    $orders = $shipmentOrder->getAll($filters);
    
    // Create Excel content as CSV (simpler approach)
    $output = fopen('php://output', 'w');
    
    // Write BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header row
    $headers = [
        'ID',
        'Тип заказа',
        'Статус',
        'Адрес забора',
        'Время готовности',
        'Тип груза',
        'Вес (кг)',
        'Габариты',
        'Контактное лицо',
        'Телефон',
        'Город отправления',
        'Город назначения',
        'Адрес доставки',
        'Способ доставки',
        'Желаемая дата прибытия',
        'Получатель',
        'Телефон получателя',
        'Стоимость отгрузки (₸)',
        'Примечания',
        'Комментарий',
        'Дата создания',
        'Дата обновления'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Write data rows
    foreach ($orders as $order) {
        $row = [
            $order['id'],
            $order['order_type'] === 'astana' ? 'Доставка по Астане' : 'Межгородская доставка',
            match($order['status']) {
                'new' => 'Новый',
                'processing' => 'В обработке',
                'completed' => 'Завершен',
                'cancelled' => 'Отменен',
                default => $order['status']
            },
            $order['pickup_address'] ?? '',
            $order['pickup_ready_time'] ?? '',
            $order['cargo_type'] ?? '',
            $order['weight'] ?? '',
            $order['dimensions'] ?? '',
            $order['contact_name'] ?? '',
            $order['contact_phone'] ?? '',
            $order['pickup_city'] ?? '',
            $order['destination_city'] ?? '',
            $order['delivery_address'] ?? '',
            $order['delivery_method'] ?? '',
            $order['desired_arrival_date'] ?? '',
            $order['recipient_contact'] ?? '',
            $order['recipient_phone'] ?? '',
            $order['shipping_cost'] ?? '',
            $order['notes'] ?? '',
            $order['comment'] ?? '',
            $order['created_at'] ? date('d.m.Y H:i', strtotime($order['created_at'])) : '',
            $order['updated_at'] ? date('d.m.Y H:i', strtotime($order['updated_at'])) : ''
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // Clear any previous output
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Set error headers
    header('Content-Type: text/html; charset=utf-8');
    header('HTTP/1.1 500 Internal Server Error');
    
    echo '<html><body><h1>Ошибка экспорта</h1>';
    echo '<p>Произошла ошибка при экспорте данных: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="/admin/panel.php">Вернуться к панели управления</a></p>';
    echo '</body></html>';
}
?>