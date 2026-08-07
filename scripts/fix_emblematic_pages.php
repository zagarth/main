<?php
// Parse emblematic index and fix product page references
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// Manual parsing of the emblematic index raw text to extract correct page mappings
// Based on the pattern: "PRODUCT_ID........PAGE_REF"
$product_page_mappings = [
    // From raw text analysis - these are the correct mappings from the emblematic index
    
    // Page 9A products
    'RN1' => 'page_09a',
    'RN3' => 'page_09a', 
    'RN301B' => 'page_09a',
    'RNAS' => 'page_09a',
    'RPN1' => 'page_09a',
    'LPN1' => 'page_09a',
    
    // Page 9AA products  
    'HSW301B' => 'page_09aa',
    'LPN301B' => 'page_09aa',
    'LPN31M' => 'page_09aa',
    'MLT1' => 'page_09aa',
    'VET14M' => 'page_09aa',
    'EMR31M' => 'page_09aa',
    
    // Page 9B products (Firefighter/MAS series and 4900s)
    'FF3' => 'page_09b',
    'FF4' => 'page_09b', 
    'FF5' => 'page_09b',
    'C553MAS' => 'page_09b',
    'C58MAS' => 'page_09b',
    'C64MAS' => 'page_09b',
    '4911' => 'page_09b',
    '4912' => 'page_09b',
    '4913' => 'page_09b',
    
    // Page 9C products (S-series)
    'S14M' => 'page_09c',
    'S16DM' => 'page_09c',
    'S180M' => 'page_09c', 
    'S185M' => 'page_09c',
    'S20M' => 'page_09c',
    'S240L' => 'page_09c',
    'S24M' => 'page_09c',
    'S25M' => 'page_09c',
    'S26M' => 'page_09c',
    'S30HM' => 'page_09c',
    'S31HM' => 'page_09c',
    'S32HM' => 'page_09c',
    'S33HM' => 'page_09c',
    'S36HM' => 'page_09c',
    'S38M' => 'page_09c',
    'S46M' => 'page_09c',
    'S6RM' => 'page_09c',
];

echo "Fixing page references for emblematic products...\n";
echo "Products to update: " . count($product_page_mappings) . "\n\n";

$updated_count = 0;
$error_count = 0;
$not_found_count = 0;

foreach ($product_page_mappings as $product_id => $correct_page) {
    // Check if product exists
    $check_sql = "SELECT page_reference FROM catalog_products WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_page = $row['page_reference'];
        
        // Update with correct page reference
        $correct_pdf = str_replace('page_', 'page_', $correct_page) . '.pdf';
        $update_sql = "UPDATE catalog_products SET page_reference = ?, pdf_file = ? WHERE product_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$correct_page, $correct_pdf, $product_id])) {
            if ($current_page != $correct_page) {
                echo "✓ Updated $product_id: '$current_page' → '$correct_page'\n";
                $updated_count++;
            } else {
                echo "→ $product_id already correct ($correct_page)\n";
            }
        } else {
            echo "✗ Error updating $product_id\n";
            $error_count++;
        }
    } else {
        echo "✗ Product $product_id not found in database\n";
        $not_found_count++;
    }
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "Total products processed: " . count($product_page_mappings) . "\n";
echo "Successfully updated: $updated_count\n";
echo "Errors: $error_count\n";
echo "Not found: $not_found_count\n";

// Close connection
$pdo = null;
?>