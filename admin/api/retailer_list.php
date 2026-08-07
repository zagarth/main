<?php
require_once '../auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load read-only database configuration  
require_once '../../includes/db_config_readonly.php';

try {
    // Get retailers from database using read-only connection
    $pdo = getReadOnlyDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            client_id,
            business_name,
            address,
            city,
            province,
            postal_code,
            country,
            phone,
            email,
            website,
            latitude,
            longitude,
            status
        FROM clients 
        WHERE client_type = 'Retailer' 
        AND status = 'Active'
        ORDER BY business_name ASC
    ");
    
    $stmt->execute();
    $dbRetailers = $stmt->fetchAll();
    
    $retailers = [];
    foreach ($dbRetailers as $retailer) {
        $retailers[] = [
            'ID' => $retailer['client_id'],
            'name' => $retailer['business_name'],
            'address' => $retailer['address'],
            'street' => $retailer['address'],
            'city' => $retailer['city'],
            'province' => $retailer['province'],
            'postal_code' => $retailer['postal_code'],
            'country' => $retailer['country'],
            'phone' => $retailer['phone'],
            'email' => $retailer['email'],
            'website' => $retailer['website'],
            'lat' => (float)$retailer['latitude'],
            'lng' => (float)$retailer['longitude']
        ];
    }
    
    $incompleteRetailers = [];
    $completedCount = 0;
    
    foreach ($retailers as $retailer) {
        $lat = $retailer['lat'] ?? null;
        $lng = $retailer['lng'] ?? null;
        
        if (empty($lat) || empty($lng) || ($lat == 50 && $lng == -100)) {
            $incompleteRetailers[] = $retailer;
        } else {
            $completedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'incomplete_retailers' => $incompleteRetailers,
            'total_retailers' => count($retailers),
            'completed_count' => $completedCount,
            'pending_count' => count($incompleteRetailers),
            'progress_percent' => count($retailers) > 0 ? round(($completedCount / count($retailers)) * 100) : 0
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>