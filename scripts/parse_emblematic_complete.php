<?php
// Parse emblematic index raw text to extract complete product-to-page mappings
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// Read the emblematic index analysis
$analysis_file = 'Cadman_catalog/index_page_EMBLEMATIC_01_analysis.json';
$json_data = file_get_contents($analysis_file);
$data = json_decode($json_data, true);

$raw_text = $data['pages'][0]['raw_text'];

echo "Parsing emblematic index raw text for complete product mappings...\n\n";

// Parse the raw text line by line to extract product ID and page mappings
// Format appears to be: PRODUCT_ID .... PAGE_REF
$lines = explode("\n", $raw_text);
$product_mappings = [];

// Parse the raw text to extract ALL product mappings
// Format examples from raw text:
// "1108MAS_ ..9B", "RN1........9A", "HSW301B ..9AA", "S33HM ....9C"
$product_mappings = [];

// Split into smaller chunks and look for patterns
$text_segments = preg_split('/[|\[\]]/', $raw_text);
foreach ($text_segments as $segment) {
    // Look for multiple patterns in each segment
    // Pattern 1: PRODUCT_ID followed by dots/spaces and page ref
    preg_match_all('/([A-Z0-9\/\-_]+)\s*[\.\/\s]+\s*(9[A-C]+)/i', $segment, $matches1, PREG_SET_ORDER);
    
    // Pattern 2: Product followed immediately by page (like "Firefighters 9A")
    preg_match_all('/([A-Z0-9\/\-_]{3,})\s+(9[A-C]+)/i', $segment, $matches2, PREG_SET_ORDER);
    
    $all_matches = array_merge($matches1, $matches2);
    
    foreach ($all_matches as $match) {
        $product_id = trim($match[1], '_ .');
        $page_ref = strtoupper($match[2]);
        
        // Convert page reference to proper format
        $page_reference = '';
        if ($page_ref == '9A') $page_reference = 'page_09a';
        elseif ($page_ref == '9AA') $page_reference = 'page_09aa';
        elseif ($page_ref == '9B') $page_reference = 'page_09b';
        elseif ($page_ref == '9C') $page_reference = 'page_09c';
        
        if ($page_reference && $product_id && strlen($product_id) >= 2) {
            $product_mappings[$product_id] = $page_reference;
            echo "Found: $product_id → $page_reference\n";
        }
    }
}

echo "\nExtracted " . count($product_mappings) . " product mappings from emblematic index\n\n";

// Now check each product in the database and update if needed
$updated_count = 0;
$not_found_count = 0;
$already_correct = 0;

foreach ($product_mappings as $product_id => $correct_page) {
    $check_sql = "SELECT page_reference, pdf_file FROM catalog_products WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_page = $row['page_reference'];
        
        if ($current_page != $correct_page) {
            // Update with correct page reference
            $correct_pdf = $correct_page . '.pdf';
            $update_sql = "UPDATE catalog_products SET page_reference = ?, pdf_file = ? WHERE product_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            
            if ($update_stmt->execute([$correct_page, $correct_pdf, $product_id])) {
                echo "✓ Updated $product_id: '$current_page' → '$correct_page'\n";
                $updated_count++;
            } else {
                echo "✗ Error updating $product_id\n";
            }
        } else {
            echo "→ $product_id already correct ($correct_page)\n";
            $already_correct++;
        }
    } else {
        echo "✗ Product $product_id not found in database\n";
        $not_found_count++;
    }
}

echo "\n=== EMBLEMATIC INDEX UPDATE SUMMARY ===\n";
echo "Products found in emblematic index: " . count($product_mappings) . "\n";
echo "Successfully updated: $updated_count\n";
echo "Already correct: $already_correct\n";
echo "Not found in database: $not_found_count\n";

if ($not_found_count > 0) {
    echo "\nProducts found in emblematic index but missing from database:\n";
    foreach ($product_mappings as $product_id => $page) {
        $check_sql = "SELECT 1 FROM catalog_products WHERE product_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$product_id]);
        if ($check_stmt->rowCount() == 0) {
            echo "  - $product_id (should be on $page)\n";
        }
    }
}

// Close connection
$pdo = null;
?>