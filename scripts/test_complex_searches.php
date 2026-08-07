<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

echo "=== TESTING DOUBLE DIGITS AND COMPLEX SEARCHES ===\n";

// Test various search patterns
$complex_tests = [
    // Double digit tests
    '12' => 'Products with 12',
    '14' => 'Products with 14', 
    '31' => 'Products with 31',
    '301' => 'Products with 301',
    
    // Mixed alphanumeric
    'S33' => 'S-series with 33',
    'P2' => 'P2000 series products',
    'RN' => 'RN series products', 
    'FF' => 'FF (Firefighter) products',
    
    // Special characters
    'P-' => 'Products with P- prefix',
    'S3' => 'S3 series products',
    
    // MAS products (various patterns)
    '1108' => 'Should find 1108MAS',
    '433' => 'Should find 433MAS',
    'C553' => 'Should find C553MAS',
    
    // Test edge cases
    '9' => 'Single digit 9',
    'A' => 'Single letter A',
    'HM' => 'Products ending in HM',
    'M' => 'Products ending in M'
];

foreach ($complex_tests as $search_term => $description) {
    echo "Testing: '$search_term' ($description)\n";
    $results = $search->searchProductDatabase($search_term);
    
    $total_found = 0;
    
    if (!empty($results)) {
        if (isset($results['exact'])) {
            $total_found += count($results['exact']);
            echo "  EXACT matches (" . count($results['exact']) . "):\n";
            foreach (array_slice($results['exact'], 0, 3) as $result) {
                echo "    - {$result['product_id']} on {$result['page_reference']}\n";
            }
            if (count($results['exact']) > 3) {
                echo "    ... and " . (count($results['exact']) - 3) . " more exact matches\n";
            }
        }
        
        if (isset($results['partial'])) {
            $total_found += count($results['partial']);
            echo "  PARTIAL matches (" . count($results['partial']) . "):\n";
            foreach (array_slice($results['partial'], 0, 3) as $result) {
                echo "    - {$result['product_id']} on {$result['page_reference']}\n";
            }
            if (count($results['partial']) > 3) {
                echo "    ... and " . (count($results['partial']) - 3) . " more partial matches\n";
            }
        }
        
        if (isset($results['page_reference_exact'])) {
            echo "  PAGE REFERENCE match:\n";
            foreach ($results['page_reference_exact'] as $result) {
                echo "    - Page {$result['page_reference']} with {$result['product_id']}\n";
            }
        }
        
        echo "  ✓ Total found: $total_found results\n";
    } else {
        echo "  ✗ No results found\n";
    }
    echo str_repeat("-", 60) . "\n";
}

echo "\n=== TESTING SPECIFIC NEW PRODUCT PATTERNS ===\n";

$new_product_patterns = [
    '231T' => 'Should find 231T MAS',
    '231' => 'Should find products with 231',
    '8G' => 'Should find 8GMAS',
    'GMAS' => 'Should find 8GMAS',
    'T MAS' => 'Should find 231T MAS',
    ' MAS' => 'Products ending with MAS'
];

foreach ($new_product_patterns as $search_term => $expected) {
    echo "Testing: '$search_term' ($expected)\n";
    $results = $search->searchProductDatabase($search_term);
    
    if (!empty($results)) {
        $found_new_products = false;
        
        if (isset($results['exact'])) {
            foreach ($results['exact'] as $result) {
                if (in_array($result['product_id'], ['231T MAS', '8GMAS'])) {
                    echo "  ✓ FOUND NEW PRODUCT: {$result['product_id']} on {$result['page_reference']}\n";
                    $found_new_products = true;
                }
            }
        }
        
        if (isset($results['partial'])) {
            foreach ($results['partial'] as $result) {
                if (in_array($result['product_id'], ['231T MAS', '8GMAS'])) {
                    echo "  ✓ FOUND NEW PRODUCT: {$result['product_id']} on {$result['page_reference']}\n";
                    $found_new_products = true;
                }
            }
        }
        
        if (!$found_new_products) {
            echo "  ⚠ Found results but no new products in them\n";
        }
        
        $total = 0;
        if (isset($results['exact'])) $total += count($results['exact']);
        if (isset($results['partial'])) $total += count($results['partial']);
        echo "  Total results: $total\n";
    } else {
        echo "  ✗ No results found\n";
    }
    echo str_repeat("-", 60) . "\n";
}
?>