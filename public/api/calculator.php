<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validation
$required_fields = ['cargo_type', 'weight', 'zone'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Get tariff for cargo type and zone
    $stmt = $pdo->prepare("
        SELECT base_price, price_per_kg, price_per_km 
        FROM delivery_tariffs 
        WHERE cargo_type = ? AND zone = ?
        LIMIT 1
    ");
    
    $stmt->execute([$input['cargo_type'], $input['zone']]);
    $tariff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tariff) {
        // Fallback to default tariff
        $tariff = [
            'base_price' => $input['zone'] === 'astana' ? 2000.00 : 4000.00,
            'price_per_kg' => $input['zone'] === 'astana' ? 100.00 : 200.00,
            'price_per_km' => $input['zone'] === 'astana' ? 80.00 : 150.00
        ];
    }
    
    // Calculate base cost
    $baseCost = $tariff['base_price'];
    $weightCost = $input['weight'] * $tariff['price_per_kg'];
    
    // Distance cost (if provided)
    $distanceCost = 0;
    if (isset($input['distance']) && $input['distance'] > 0) {
        $distanceCost = $input['distance'] * $tariff['price_per_km'];
    }
    
    // Priority multiplier
    $priorityMultiplier = 1.0;
    if (isset($input['priority'])) {
        switch ($input['priority']) {
            case 'urgent':
                $priorityMultiplier = 1.5;
                break;
            case 'express':
                $priorityMultiplier = 2.0;
                break;
        }
    }
    
    $subtotal = ($baseCost + $weightCost + $distanceCost) * $priorityMultiplier;
    
    // Insurance (optional)
    $insuranceCost = 0;
    if (isset($input['insurance_value']) && $input['insurance_value'] > 0) {
        $insuranceCost = $input['insurance_value'] * 0.02; // 2% of declared value
    }
    
    $total = $subtotal + $insuranceCost;
    
    echo json_encode([
        'success' => true,
        'calculation' => [
            'base_cost' => $baseCost,
            'weight_cost' => $weightCost,
            'distance_cost' => $distanceCost,
            'insurance_cost' => $insuranceCost,
            'priority_multiplier' => $priorityMultiplier,
            'subtotal' => $subtotal,
            'total' => round($total, 2),
            'currency' => 'KZT'
        ],
        'breakdown' => [
            'Базовая стоимость' => number_format($baseCost, 0, '.', ' ') . ' ₸',
            'Стоимость по весу' => number_format($weightCost, 0, '.', ' ') . ' ₸',
            'Стоимость по расстоянию' => number_format($distanceCost, 0, '.', ' ') . ' ₸',
            'Страхование' => number_format($insuranceCost, 0, '.', ' ') . ' ₸',
            'Общая стоимость' => number_format($total, 0, '.', ' ') . ' ₸'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Calculator error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Calculation error']);
}
?>