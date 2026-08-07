<?php
// Test script to extract products from medical index PDF
$pdfFile = 'Cadman_catalog/index_page_medical_01.pdf';

// Extract text from PDF
$textCommand = "pdftotext \"$pdfFile\" -";
$text = shell_exec($textCommand);

if (!$text) {
    echo "Failed to extract text from $pdfFile\n";
    exit(1);
}

echo "=== RAW TEXT FROM PDF ===\n";
echo $text;
echo "\n=== EXTRACTED PRODUCTS ===\n";

// Use the same regex pattern from the main script
$pattern = '/\b([A-Z0-9]{2,10}(?:-[A-Z0-9]{1,4})?)\s*\.\s*\.\s*\.\s*[0-9A-Z]+/i';

if (preg_match_all($pattern, $text, $matches)) {
    $products = array_unique($matches[1]);
    echo "Found " . count($products) . " products:\n";
    foreach ($products as $product) {
        echo "  - $product\n";
    }
} else {
    echo "No products found with current regex pattern\n";
}

// Try a simpler pattern specifically for this format
echo "\n=== TRYING SIMPLER PATTERN ===\n";
$simplePattern = '/([A-Z0-9-]+)\s*\.\s*\.\s*\.\s*[0-9A-Z]+/';
if (preg_match_all($simplePattern, $text, $simpleMatches)) {
    $simpleProducts = array_unique($simpleMatches[1]);
    echo "Found " . count($simpleProducts) . " products with simple pattern:\n";
    foreach ($simpleProducts as $product) {
        echo "  - $product\n";
    }
}

// Check specifically for 2BMC
echo "\n=== CHECKING FOR 2BMC ===\n";
if (stripos($text, '2BMC') !== false) {
    echo "✓ 2BMC found in raw text\n";
    
    // Try to match it specifically
    if (preg_match('/2BMC[^A-Z0-9]*\.\s*\.\s*\.\s*[0-9A-Z]+/i', $text, $match)) {
        echo "✓ 2BMC matches pattern: " . trim($match[0]) . "\n";
    } else {
        echo "✗ 2BMC does not match current pattern\n";
    }
} else {
    echo "✗ 2BMC not found in raw text\n";
}
?>