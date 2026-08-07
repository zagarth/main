#!/usr/bin/env php
<?php
// Test search to see full results
require_once 'catalog_search_functions.php';

echo "=== Testing Search Results Detail ===\n";

$test_term = "xyz";
echo "Searching for: $test_term\n\n";

$results = searchProductDatabase($test_term);

foreach ($results as $type => $matches) {
    echo "=== $type matches ===\n";
    foreach ($matches as $match) {
        echo "  Product: {$match['product_id']}\n";
        echo "  PDF: {$match['pdf_file']}\n";
        echo "  Page: {$match['page_reference']}\n";
        echo "  Category: {$match['category']}\n";
        echo "  ---\n";
    }
    echo "\n";
}
?>