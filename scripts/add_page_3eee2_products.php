<?php
// Add new products from page_3eee2.pdf to catalog database
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// New products that weren't found in the database
$new_products = [
    '5468M', '5520', '5522', '5523', '5850M', '5850L',
    '5852L', '5852M', '59', '62', '74ES', '74P', '75P', '76ES',
    'CC73', 'CC74M', 'CC75', 'CC76AM', 'CC76BM', 'CC77', 'CC79', 'CC80',
    'ER5600'
];

$page_reference = 'page_3eee2';
$pdf_file = 'page_3eee2.pdf';

echo "Adding " . count($new_products) . " new products to catalog_products table\n";
echo "Page reference: $page_reference\n";
echo "PDF file: $pdf_file\n";
echo "Products to add: " . implode(', ', $new_products) . "\n\n";

$added_count = 0;
$error_count = 0;

// Check what columns exist in the table first
try {
    $columns_query = "SHOW COLUMNS FROM catalog_products";
    $columns_result = $pdo->query($columns_query);
    $columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
    echo "Available columns: " . implode(', ', $columns) . "\n\n";
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage() . "\n";
}

foreach ($new_products as $product_id) {
    try {
        // Insert new product with basic information
        $insert_sql = "INSERT INTO catalog_products (product_id, page_reference, pdf_file) VALUES (?, ?, ?)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        if ($insert_stmt->execute([$product_id, $page_reference, $pdf_file])) {
            echo "✓ Added $product_id with page reference '$page_reference'\n";
            $added_count++;
        } else {
            echo "✗ Error adding $product_id\n";
            $error_count++;
        }
    } catch (PDOException $e) {
        echo "✗ Error adding $product_id: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "\n=== ADD SUMMARY ===\n";
echo "Total products to add: " . count($new_products) . "\n";
echo "Successfully added: $added_count\n";
echo "Errors: $error_count\n";
echo "Page reference used: $page_reference\n";
echo "PDF file name used: $pdf_file\n";

// Close connection
$pdo = null;
?>