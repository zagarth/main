<?php
/**
 * Bill of Materials Data API - AR12 Pricing Calculator
 * Returns BOM (BM) data from database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get query parameters
$itemCode = $_GET['item_code'] ?? '';
$limit = min(intval($_GET['limit'] ?? 10000), 100000);

// Build query
$sql = "
    SELECT 
        bom_id,
        item_code,
        component_part,
        class,
        quantity
    FROM bill_of_materials
    WHERE 1=1
";

$params = [];

// Filter by item code
if (!empty($itemCode)) {
    $sql .= " AND item_code = :itemCode";
    $params[':itemCode'] = $itemCode;
}

$sql .= " ORDER BY item_code, component_part LIMIT :limit";

$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert numeric strings to numbers
foreach ($results as &$row) {
    $row['bom_id'] = intval($row['bom_id']);
    $row['quantity'] = floatval($row['quantity']);
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($results),
    'data' => $results
], JSON_PRETTY_PRINT);
