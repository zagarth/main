<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

// Test the newly added products
$test_products = ['231T MAS', '8GMAS'];

echo "=== TESTING NEWLY ADDED PRODUCTS ===\n";

foreach ($test_products as $product) {
    echo "Testing search for: $product\n";
    $results = $search->searchProductDatabase($product);
    
    if (!empty($results)) {
        if (isset($results['exact'])) {
            foreach ($results['exact'] as $result) {
                echo "✓ Found: {$result['product_id']} on {$result['page_reference']}\n";
            }
        }
        if (isset($results['partial'])) {
            foreach ($results['partial'] as $result) {
                echo "✓ Partial match: {$result['product_id']} on {$result['page_reference']}\n";
            }
        }
    } else {
        echo "✗ No results found\n";
    }
    echo "---\n";
}

echo "\n=== TESTING PARTIAL SEARCHES ===\n";
// Test partial searches for the new products
$partial_tests = [
    '231T' => '231T MAS',
    '8G' => '8GMAS',
    'MAS' => 'should find multiple MAS products'
];

foreach ($partial_tests as $search_term => $expected) {
    echo "Testing partial search for: $search_term (expecting: $expected)\n";
    $results = $search->searchProductDatabase($search_term);
    
    if (!empty($results)) {
        $total_count = 0;
        if (isset($results['exact'])) {
            $total_count += count($results['exact']);
            foreach ($results['exact'] as $result) {
                echo "  - EXACT: {$result['product_id']} on {$result['page_reference']}\n";
            }
        }
        if (isset($results['partial'])) {
            $total_count += count($results['partial']);
            foreach ($results['partial'] as $result) {
                echo "  - PARTIAL: {$result['product_id']} on {$result['page_reference']}\n";
            }
        }
        echo "✓ Found $total_count results total\n";
    } else {
        echo "✗ No results found\n";
    }
    echo "---\n";
}
?>