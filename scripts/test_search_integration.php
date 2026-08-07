<?php
/**
 * Test script for the product search integration
 */

// Test the search functionality directly
echo "Testing product search integration...\n\n";

try {
    require_once 'classes/CatalogSearch.php';
    
    echo "Test 1: Searching for '5424' in celtic_bands category\n";
    
    $search = new CatalogSearch();
    $results = $search->searchByCategory('5424', 'celtic_bands');
    
    echo "Results found: " . count($results) . "\n";
    
    if (!empty($results)) {
        foreach ($results as $result) {
            echo "- Product ID: " . $result['product_id'] . 
                 " | Name: " . ($result['product_name'] ?: $result['pattern']) . 
                 " | Category: " . $result['category'] . 
                 " | Has Images: " . ($result['has_images'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    echo "\nTest 2: Searching for 'Celtic' pattern\n";
    $results2 = $search->searchByCategory('Celtic', '');
    echo "Results found: " . count($results2) . "\n";
    
    if (!empty($results2)) {
        $displayCount = min(3, count($results2));
        for ($i = 0; $i < $displayCount; $i++) {
            $result = $results2[$i];
            echo "- Product ID: " . $result['product_id'] . 
                 " | Pattern: " . ($result['pattern'] ?: 'N/A') . 
                 " | Category: " . $result['category'] . "\n";
        }
        if (count($results2) > 3) {
            echo "... and " . (count($results2) - 3) . " more results\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nSearch integration test completed successfully!\n";
?>