<?php
/**
 * Inventory Data API - AR12 Pricing Calculator
 * Returns inventory (IC) data from database
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
$search = $_GET['search'] ?? '';
$class = $_GET['class'] ?? '';
$limit = min(intval($_GET['limit'] ?? 5000), 10000);

// Build query
$sql = "
    SELECT 
        inventory_id,
        part_number,
        description,
        class,
        cost,
        material_cost,
        metal_hi,
        metal_lo,
        group_code,
        gold_grams,
        gold_cost,
        sterling_grams,
        sterling_cost
    FROM inventory
    WHERE 1=1
";

$params = [];

// Filter by search term
if (!empty($search)) {
    $sql .= " AND (part_number LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Filter by class
if (!empty($class)) {
    $sql .= " AND class = :class";
    $params[':class'] = $class;
}

$sql .= " ORDER BY part_number LIMIT :limit";

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
    $row['inventory_id'] = intval($row['inventory_id']);
    $row['cost'] = floatval($row['cost']);
    $row['material_cost'] = floatval($row['material_cost']);
    $row['gold_grams'] = floatval($row['gold_grams']);
    $row['gold_cost'] = floatval($row['gold_cost']);
    $row['sterling_grams'] = floatval($row['sterling_grams']);
    $row['sterling_cost'] = floatval($row['sterling_cost']);
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($results),
    'data' => $results
], JSON_PRETTY_PRINT);
