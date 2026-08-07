<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

echo "=== TESTING F25 SEARCH ISSUE ===\n";

// Test the F25 search
$search_term = 'F25';
echo "Testing search for: '$search_term'\n\n";

$results = $search->searchProductDatabase($search_term);

echo "Raw search results structure:\n";
print_r($results);

echo "\n=== DETAILED ANALYSIS ===\n";

if (!empty($results)) {
    foreach (['exact', 'starts_with', 'partial', 'suffix'] as $category) {
        if (isset($results[$category])) {
            echo "$category matches (" . count($results[$category]) . "):\n";
            foreach ($results[$category] as $result) {
                echo "  - Product: {$result['product_id']}\n";
                echo "    Page: {$result['page_reference']}\n";
                echo "    PDF: {$result['pdf_file']}\n";
                echo "    Category: {$result['category']}\n\n";
            }
        }
    }
}

// Now test the main search function that might be doing catalog page suggestion
echo "\n=== TESTING MAIN SEARCH FUNCTION ===\n";
$main_results = $search->search($search_term);
echo "Main search results:\n";
print_r($main_results);
?>