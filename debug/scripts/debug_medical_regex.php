<?php
// Debug regex patterns for medical PDF
$pdfFile = 'Cadman_catalog/index_page_medical_01.pdf';

// Extract text from PDF
$textCommand = "pdftotext \"$pdfFile\" -";
$text = shell_exec($textCommand);

echo "=== DEBUGGING REGEX PATTERNS ===\n";

// Test different patterns
$patterns = [
    'Original' => '/\b([A-Z0-9]{2,10}(?:-[A-Z0-9]{1,4})?)\s*\.\s*\.\s*\.\s*[0-9A-Z]+/i',
    'Simpler' => '/([A-Z0-9-]+)\s*\.\s*\.\s*\.\s*[0-9A-Z]+/',
    'More flexible dots' => '/([A-Z0-9-]+)\s*\..*?[0-9A-Z]+A/',
    'Very simple' => '/^([A-Z0-9-]+)\s+\.\s+\.\s+\.\s+/m',
    'Line-based' => '/^([A-Z0-9-]+).*?[0-9A-Z]+A$/m'
];

foreach ($patterns as $name => $pattern) {
    echo "\n--- Testing $name pattern: $pattern ---\n";
    if (preg_match_all($pattern, $text, $matches)) {
        $products = array_unique($matches[1]);
        echo "Found " . count($products) . " products:\n";
        foreach (array_slice($products, 0, 10) as $product) { // Show first 10
            echo "  - $product\n";
        }
        if (count($products) > 10) {
            echo "  ... and " . (count($products) - 10) . " more\n";
        }
        
        // Check if 2BMC is in results
        if (in_array('2BMC', $products)) {
            echo "  ✓ 2BMC found in results!\n";
        } else {
            echo "  ✗ 2BMC not in results\n";
        }
    } else {
        echo "No matches found\n";
    }
}

// Let's try to parse line by line
echo "\n=== LINE BY LINE ANALYSIS ===\n";
$lines = explode("\n", $text);
$productLines = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^([A-Z0-9-]+)\s+\.\s+\.\s+\.\s+/', $line, $match)) {
        $productLines[] = $line;
        echo "Product line: $line -> Product: {$match[1]}\n";
    }
}

echo "\nFound " . count($productLines) . " product lines\n";
?>