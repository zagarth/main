<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

echo "=== TESTING ENHANCED MAS SEARCH ===\n";

// Test MAS search patterns
$mas_tests = [
    'MAS' => 'All products ending with MAS',
    'mas' => 'Lowercase MAS test',
    'HM' => 'All products ending with HM',
    'RM' => 'All products ending with RM',
    'M' => 'Single letter M (should be cautious)',
    'B' => 'Single letter B (should be cautious)'
];

foreach ($mas_tests as $search_term => $description) {
    echo "Testing: '$search_term' ($description)\n";
    $results = $search->searchProductDatabase($search_term);
    
    $total_found = 0;
    $mas_products = [];
    
    if (!empty($results)) {
        // Check all result categories
        foreach (['exact', 'suffix', 'starts_with', 'partial'] as $category) {
            if (isset($results[$category])) {
                $total_found += count($results[$category]);
                echo "  $category matches (" . count($results[$category]) . "):\n";
                
                foreach (array_slice($results[$category], 0, 5) as $result) {
                    echo "    - {$result['product_id']} on {$result['page_reference']}\n";
                    if (stripos($result['product_id'], 'MAS') !== false) {
                        $mas_products[] = $result['product_id'];
                    }
                }
                if (count($results[$category]) > 5) {
                    echo "    ... and " . (count($results[$category]) - 5) . " more\n";
                }
            }
        }
        
        echo "  ✓ Total found: $total_found results\n";
        if (!empty($mas_products)) {
            echo "  ✓ MAS products found: " . implode(', ', array_unique($mas_products)) . "\n";
        }
    } else {
        echo "  ✗ No results found\n";
    }
    echo str_repeat("-", 60) . "\n";
}

echo "\n=== TESTING SPECIFIC PROBLEMATIC CASES ===\n";

$specific_tests = [
    '231T' => 'Should find 231T MAS via suffix matching when searching MAS',
    '8G' => 'Should find 8GMAS',
    'T' => 'Should find 231T MAS (if T suffix matching works)',
];

foreach ($specific_tests as $search_term => $expected) {
    echo "Testing: '$search_term' ($expected)\n";
    $results = $search->searchProductDatabase($search_term);
    
    if (!empty($results)) {
        $found_targets = [];
        
        foreach (['exact', 'suffix', 'starts_with', 'partial'] as $category) {
            if (isset($results[$category])) {
                foreach ($results[$category] as $result) {
                    if (in_array($result['product_id'], ['231T MAS', '8GMAS'])) {
                        $found_targets[] = $result['product_id'];
                    }
                }
            }
        }
        
        if (!empty($found_targets)) {
            echo "  ✓ Found target products: " . implode(', ', array_unique($found_targets)) . "\n";
        } else {
            echo "  ⚠ Found results but no target products\n";
        }
    } else {
        echo "  ✗ No results found\n";
    }
    echo str_repeat("-", 50) . "\n";
}
?>