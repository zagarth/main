<?php
// Update database with products from page_09aa.pdf
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// Products detected from page_09aa.pdf (from Gemini analysis)
$products = [
    'EMT1', 'LNAI', 'EMT31M', 'CNA301B', 'MLT1', 'LPN31M', 'HSW301B', 'BSN3', 
    'EMR31M', 'VET15L', 'VET14M', 'P-BNI', 'LPN301B', 'CRW301B', 'CCA301B', 
    'PAR14M', 'PAR17B', 'P-FF4', 'P-FF5', 'P-FF3L'
];

$page_reference = 'page_09aa';
$pdf_file = 'page_09aa.pdf';

echo "Starting database update for " . count($products) . " products from $pdf_file\n";
echo "Page reference: $page_reference\n";
echo "Products to update: " . implode(', ', $products) . "\n\n";

$updated_count = 0;
$not_found_count = 0;
$new_products = [];

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
        echo "→ Product $product_id not found in database - will need to be added\n";
        $new_products[] = $product_id;
        $not_found_count++;
    }
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "Total products processed: " . count($products) . "\n";
echo "Successfully updated: $updated_count\n";
echo "Not found in database: $not_found_count\n";
echo "Page reference used: $page_reference\n";
echo "PDF file name used: $pdf_file\n";

if (!empty($new_products)) {
    echo "\nProducts that need to be added:\n";
    foreach ($new_products as $product) {
        echo "  - $product\n";
    }
}

// Close connection
$pdo = null;
?>