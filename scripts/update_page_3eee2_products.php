<?php
// Update database with products from page_3eee2.pdf
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// Products detected from page_3eee2.pdf
$products = [
    '5462L', '5462M', '5468M', '5520', '5522', '5523', '5850M', '5850L',
    '5852L', '5852M', '5854L', '5854M', '59', '62', '74ES', '74P', '75P', '76ES',
    'C79', 'CC73', 'CC74M', 'CC75', 'CC76AM', 'CC76BM', 'CC77', 'CC79', 'CC80',
    'ER5600'
];

$page_reference = 'page_3eee2';
$pdf_file = 'page_3eee2.pdf';

echo "Starting database update for " . count($products) . " products from $pdf_file\n";
echo "Page reference: $page_reference\n";
echo "Products to update: " . implode(', ', $products) . "\n\n";

$updated_count = 0;
$not_found_count = 0;

foreach ($products as $product_id) {
    // Check if product exists
    $check_sql = "SELECT page_reference, pdf_file FROM catalog_products WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_page_ref = $row['page_reference'];
        $current_pdf_file = $row['pdf_file'];
        
        // Always update with new page reference and PDF file
        $update_sql = "UPDATE catalog_products SET page_reference = ?, pdf_file = ? WHERE product_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$page_reference, $pdf_file, $product_id])) {
            if (!empty($current_page_ref)) {
                echo "✓ Updated $product_id: changed from '$current_page_ref' to '$page_reference'\n";
            } else {
                echo "✓ Updated $product_id with page reference '$page_reference' and PDF file '$pdf_file'\n";
            }
            $updated_count++;
        } else {
            echo "✗ Error updating $product_id\n";
        }
    } else {
        echo "✗ Product $product_id not found in database\n";
        $not_found_count++;
    }
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "Total products processed: " . count($products) . "\n";
echo "Successfully updated: $updated_count\n";
echo "Not found in database: $not_found_count\n";
echo "Page reference used: $page_reference\n";
echo "PDF file name used: $pdf_file\n";

// Close connection
$pdo = null;
?>