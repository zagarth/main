#!/usr/bin/env php
<?php
// Test search functionality for updated products
require_once 'catalog_search_functions.php';

echo "=== Testing Search Functionality for Updated Products ===\n\n";

// Test products that we just updated
$test_products = [
    'LPN31M' => 'Should be on page_09aa',
    '4911' => 'Should be on page_09b', 
    'S14M' => 'Should be on page_09c',
    'FF4' => 'Should be on page_09b',
    'C553MAS' => 'Should be on page_09b'
];

foreach ($test_products as $product => $expected) {
    echo "Testing search for: $product ($expected)\n";
    
    // Test database search
    $results = searchProductDatabase($product);
    
    if ($results && !empty($results)) {
        echo "✓ Found in database:\n";
        
        if (isset($results['exact']) && !empty($results['exact'])) {
            $exact = $results['exact'][0];
            echo "  - Product ID: {$exact['product_id']}\n";
            echo "  - Page: {$exact['page_reference']}\n";
            echo "  - PDF File: {$exact['pdf_file']}\n";
            if (isset($exact['category'])) {
                echo "  - Category: {$exact['category']}\n";
            }
        }
        echo "\n";
    } else {
        echo "✗ Not found in database\n\n";
    }
}

// Test broader search
echo "Testing broader search for 'FF'...\n";
$ff_results = searchProductDatabase('FF');
if ($ff_results && !empty($ff_results)) {
    if (isset($ff_results['partial']) && !empty($ff_results['partial'])) {
        echo "Found " . count($ff_results['partial']) . " FF-related products:\n";
        foreach (array_slice($ff_results['partial'], 0, 5) as $result) {
            echo "  - {$result['product_id']} on {$result['page_reference']}\n";
        }
    }
} else {
    echo "No FF products found\n";
}

echo "\n=== Testing Complete ===\n";
?>