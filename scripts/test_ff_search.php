<?php
// Test FF search specifically
require_once 'catalog_search_functions.php';

echo "=== Testing FF Search Functionality ===\n\n";

echo "Testing exact search for 'FF4':\n";
$exact_results = searchProductDatabase('FF4');
if ($exact_results) {
    print_r($exact_results);
} else {
    echo "No results for exact FF4 search\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "Testing partial search for 'FF':\n";
$partial_results = searchProductDatabase('FF');
if ($partial_results) {
    print_r($partial_results);
} else {
    echo "No results for partial FF search\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "Testing direct SQL query for FF products:\n";
require_once __DIR__ . '/includes/db_config.php';
$pdo = getDBConnection();

$sql = "SELECT product_id, page_reference, pdf_file FROM catalog_products WHERE product_id LIKE ? ORDER BY product_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['%FF%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($results) . " products:\n";
foreach ($results as $row) {
    echo "  - {$row['product_id']} on {$row['page_reference']}\n";
}
?>