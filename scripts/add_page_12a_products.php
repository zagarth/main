<?php
// Add/update page_12a products (pearl set rings P2100 series)
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// Products that actually belong on page_12a (pearl set rings)
$page_12a_products = [
    'P2112', 'P2113', 'P2114', 'P2115', 'P2116', 'P2117', 'P2118', 'P2119',
    'P2120', 'P2121', 'P2122', 'P2123', 'P2124', 'P2126', 'P2127', 'P2128'
];

$page_reference = 'page_12a';
$pdf_file = 'page_12a.pdf';

echo "Processing " . count($page_12a_products) . " pearl set ring products for page_12a\n";
echo "Page reference: $page_reference\n";
echo "PDF file: $pdf_file\n";
echo "Products: " . implode(', ', $page_12a_products) . "\n\n";

$updated_count = 0;
$added_count = 0;
$error_count = 0;

foreach ($page_12a_products as $product_id) {
    // Check if product exists
    $check_sql = "SELECT page_reference, pdf_file FROM catalog_products WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        // Product exists - update it
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_page = $row['page_reference'];
        
        $update_sql = "UPDATE catalog_products SET page_reference = ?, pdf_file = ? WHERE product_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$page_reference, $pdf_file, $product_id])) {
            if ($current_page != $page_reference) {
                echo "✓ Updated $product_id: '$current_page' → '$page_reference'\n";
            } else {
                echo "→ $product_id already correct ($page_reference)\n";
            }
            $updated_count++;
        } else {
            echo "✗ Error updating $product_id\n";
            $error_count++;
        }
    } else {
        // Product doesn't exist - add it
        try {
            $insert_sql = "INSERT INTO catalog_products (product_id, page_reference, pdf_file, category) VALUES (?, ?, ?, 'ladies_jewelry')";
            $insert_stmt = $pdo->prepare($insert_sql);
            
            if ($insert_stmt->execute([$product_id, $page_reference, $pdf_file])) {
                echo "✓ Added $product_id to page_12a (pearl set ring)\n";
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
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "Total products processed: " . count($page_12a_products) . "\n";
echo "Updated existing: $updated_count\n";
echo "Added new: $added_count\n";
echo "Errors: $error_count\n";
echo "Page reference used: $page_reference\n";
echo "PDF file name used: $pdf_file\n";

// Close connection
$pdo = null;
?>