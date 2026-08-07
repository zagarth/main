<?php
// Test search for page_12a products
require_once 'catalog_search_functions.php';

echo "=== Testing Page 12a Pearl Set Ring Products ===\n\n";

$test_products = ['P2128', 'P2112', 'P2121'];

foreach ($test_products as $product) {
    echo "Testing search for: $product\n";
    
    $results = searchProductDatabase($product);
    
    if ($results && !empty($results)) {
        echo "✓ Found in database:\n";
        
        if (isset($results['exact']) && !empty($results['exact'])) {
            $exact = $results['exact'][0];
            echo "  - Product ID: {$exact['product_id']}\n";
            echo "  - Page: {$exact['page_reference']}\n";
            echo "  - Category: {$exact['category']}\n";
        }
        echo "\n";
    } else {
        echo "✗ Not found in database\n\n";
    }
}

echo "=== Testing Complete ===\n";
?>