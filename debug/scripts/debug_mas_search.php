<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

echo "=== DEBUGGING MAS PRODUCT SEARCHES ===\n";

// Test exact MAS product searches
$mas_products = ['1108MAS', '433MAS', 'C553MAS', '231T MAS', '8GMAS'];

foreach ($mas_products as $product) {
    echo "Testing exact search for: '$product'\n";
    $results = $search->searchProductDatabase($product);
    
    if (!empty($results) && isset($results['exact'])) {
        echo "  ✓ Found exact match: {$results['exact'][0]['product_id']} on {$results['exact'][0]['page_reference']}\n";
    } else {
        echo "  ✗ No exact match found\n";
    }
}

echo "\n=== TESTING PARTIAL PATTERNS ===\n";

// Test partial patterns that should work
$partial_patterns = [
    'MAS' => 'All MAS products',
    '1108' => 'Should find 1108MAS', 
    '433' => 'Should find 433MAS',
    'C553' => 'Should find C553MAS',
    '231T' => 'Should find 231T MAS',
    '8G' => 'Should find 8GMAS'
];

foreach ($partial_patterns as $pattern => $description) {
    echo "Testing pattern: '$pattern' ($description)\n";
    $results = $search->searchProductDatabase($pattern);
    
    $mas_found = [];
    
    if (!empty($results)) {
        if (isset($results['exact'])) {
            foreach ($results['exact'] as $result) {
                if (strpos($result['product_id'], 'MAS') !== false) {
                    $mas_found[] = $result['product_id'];
                }
            }
        }
        if (isset($results['partial'])) {
            foreach ($results['partial'] as $result) {
                if (strpos($result['product_id'], 'MAS') !== false) {
                    $mas_found[] = $result['product_id'];
                }
            }
        }
    }
    
    if (!empty($mas_found)) {
        echo "  ✓ Found MAS products: " . implode(', ', $mas_found) . "\n";
    } else {
        echo "  ✗ No MAS products found\n";
    }
}

echo "\n=== TESTING SEARCH TERM CLEANING ===\n";

// Test if search term cleaning affects results
$test_terms = [
    'mas',      // lowercase
    'MAS',      // uppercase
    ' MAS',     // with space
    'MAS ',     // with trailing space
    '1108mas',  // lowercase combination
    '1108MAS'   // uppercase combination
];

foreach ($test_terms as $term) {
    echo "Testing: '" . $term . "'\n";
    $results = $search->searchProductDatabase($term);
    
    if (!empty($results)) {
        $total = 0;
        if (isset($results['exact'])) $total += count($results['exact']);
        if (isset($results['partial'])) $total += count($results['partial']);
        echo "  ✓ Found $total results\n";
    } else {
        echo "  ✗ No results\n";
    }
}
?>