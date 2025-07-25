<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

// Параметры отчета
$period = $_GET['period'] ?? 'month';
$format = $_GET['format'] ?? 'table';

try {
    $pdo = \Database::getInstance()->getConnection();
    
    // Определяем период
    $dateFilter = '';
    $params = [];
    
    switch ($period) {
        case 'today':
            $dateFilter = 'WHERE DATE(created_at) = CURRENT_DATE';
            $periodTitle = 'за сегодня';
            break;
        case 'week':
            $dateFilter = 'WHERE created_at >= CURRENT_DATE - INTERVAL \'7 days\'';
            $periodTitle = 'за неделю';
            break;
        case 'month':
            $dateFilter = 'WHERE created_at >= CURRENT_DATE - INTERVAL \'30 days\'';
            $periodTitle = 'за месяц';
            break;
        default:
            $periodTitle = 'за все время';
    }
    
    // Получаем данные
    $stmt = $pdo->prepare("
        SELECT id, order_type, status, pickup_address, destination_city,
               cargo_type, weight, contact_name, contact_phone, 
               shipping_cost, created_at
        FROM shipment_orders 
        $dateFilter
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Статистика
    $totalOrders = count($orders);
    $totalCost = array_sum(array_column($orders, 'shipping_cost'));
    $avgCost = $totalOrders > 0 ? $totalCost / $totalOrders : 0;
    
    $statusCounts = [
        'new' => 0,
        'processing' => 0,
        'completed' => 0
    ];
    
    foreach ($orders as $order) {
        if (isset($statusCounts[$order['status']])) {
            $statusCounts[$order['status']]++;
        }
    }
    
    // Создаем HTML для PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Отчет по заказам ' . $periodTitle . '</title>
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            .stats { margin: 20px 0; }
            .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .stats-table th, .stats-table td { 
                border: 1px solid #ddd; 
                padding: 8px; 
                text-align: left; 
            }
            .stats-table th { background-color: #f5f5f5; }
            .orders-table { width: 100%; border-collapse: collapse; font-size: 10px; }
            .orders-table th, .orders-table td { 
                border: 1px solid #ddd; 
                padding: 4px; 
                text-align: left; 
                word-wrap: break-word;
            }
            .orders-table th { background-color: #f5f5f5; }
            .status-new { color: #dc2626; }
            .status-processing { color: #d97706; }
            .status-completed { color: #059669; }
            .page-break { page-break-before: always; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Хром-KZ Логистика</h1>
            <h2>Отчет по заказам ' . $periodTitle . '</h2>
            <p>Сформирован: ' . date('d.m.Y H:i') . '</p>
        </div>
        
        <div class="stats">
            <h3>Общая статистика</h3>
            <table class="stats-table">
                <tr>
                    <th>Показатель</th>
                    <th>Значение</th>
                </tr>
                <tr>
                    <td>Всего заказов</td>
                    <td>' . $totalOrders . '</td>
                </tr>
                <tr>
                    <td>Новые заказы</td>
                    <td class="status-new">' . $statusCounts['new'] . '</td>
                </tr>
                <tr>
                    <td>В обработке</td>
                    <td class="status-processing">' . $statusCounts['processing'] . '</td>
                </tr>
                <tr>
                    <td>Завершенные</td>
                    <td class="status-completed">' . $statusCounts['completed'] . '</td>
                </tr>
                <tr>
                    <td>Общие затраты</td>
                    <td>' . number_format($totalCost, 0, ',', ' ') . ' ₸</td>
                </tr>
                <tr>
                    <td>Средние затраты</td>
                    <td>' . number_format($avgCost, 0, ',', ' ') . ' ₸</td>
                </tr>
            </table>
        </div>';
    
    if ($format === 'table' && !empty($orders)) {
        $html .= '
        <div class="page-break">
            <h3>Детальный список заказов</h3>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Адрес забора</th>
                        <th>Направление</th>
                        <th>Груз</th>
                        <th>Контакт</th>
                        <th>Стоимость</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($orders as $order) {
            $statusClass = 'status-' . $order['status'];
            $statusText = match($order['status']) {
                'new' => 'Новый',
                'processing' => 'В работе',
                'completed' => 'Завершен',
                default => $order['status']
            };
            
            $html .= '<tr>
                <td>' . $order['id'] . '</td>
                <td>' . ($order['order_type'] === 'astana' ? 'Астана' : 'Межгород') . '</td>
                <td class="' . $statusClass . '">' . $statusText . '</td>
                <td>' . htmlspecialchars(substr($order['pickup_address'] ?? '', 0, 30)) . '</td>
                <td>' . htmlspecialchars($order['destination_city'] ?? 'Астана') . '</td>
                <td>' . htmlspecialchars(substr($order['cargo_type'] ?? '', 0, 20)) . '</td>
                <td>' . htmlspecialchars($order['contact_name'] ?? '') . '</td>
                <td>' . ($order['shipping_cost'] ? number_format($order['shipping_cost'], 0, ',', ' ') . ' ₸' : '-') . '</td>
                <td>' . date('d.m.Y', strtotime($order['created_at'])) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    $html .= '
        <div style="margin-top: 40px; font-size: 10px; color: #666;">
            <p>Отчет сформирован автоматически системой управления заказами Хром-KZ Логистика</p>
            <p>Для вопросов обращайтесь к администратору системы</p>
        </div>
    </body>
    </html>';
    
    // Отправляем HTML как есть (для упрощения, вместо реального PDF)
    $filename = 'report_' . $period . '_' . date('Y-m-d_H-i-s') . '.html';
    
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo $html;
    
} catch (Exception $e) {
    echo 'Ошибка формирования отчета: ' . $e->getMessage();
}
?>