#!/usr/bin/env php
<?php
/**
 * Check if missing Classic Bands products exist in other band categories
 */

// The 16 missing Classic Bands products
$missingProducts = [
    '200' => 'Plain 2mm',
    '250' => 'Plain 2.5mm', 
    '300' => 'Plain 3mm',
    '380' => 'Plain 3.8mm',
    '400' => 'Plain 4mm',
    '400R' => 'Rectangular 4mm',
    '400B' => 'Beaded 4mm',
    '450' => 'Plain 4.5mm',
    '500' => 'Plain 5mm',
    '600' => 'Plain 6mm',
    '1000' => 'Plain 10mm',
    '6T18' => '6mm Tiffany 1.8mm thick',
    '7T00R' => '7mm Tiffany Round',
    '7T18' => '7mm Tiffany 1.8mm thick',
    'S300R' => 'Silver 3mm Round',
    'S600R' => 'Silver 6mm Round',
];

// Check other band categories
$categories = ['celtic', 'cultural', 'fancy'];
$basePath = '/var/www/html/homesite/bands_php/images/';

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║   MISSING CLASSIC BANDS - CHECK OTHER CATEGORIES             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$foundInOther = [];
$notFoundAnywhere = [];

foreach ($missingProducts as $productId => $description) {
    $found = false;
    $foundLocations = [];
    
    foreach ($categories as $category) {
        $categoryPath = $basePath . $category . '/';
        if (!is_dir($categoryPath)) continue;
        
        $files = glob($categoryPath . '*.png');
        foreach ($files as $file) {
            $filename = basename($file);
            // Remove suffixes and extensions
            $baseId = preg_replace('/(_.+)?\.png$/', '', $filename);
            $baseId = preg_replace('/[ML]$/i', '', $baseId);
            
            // Check for exact match or similar patterns
            if (stripos($baseId, $productId) !== false || stripos($filename, $productId) !== false) {
                $found = true;
                $foundLocations[] = "$category: " . basename($file);
            }
        }
    }
    
    if ($found) {
        $foundInOther[$productId] = [
            'description' => $description,
            'locations' => $foundLocations
        ];
    } else {
        $notFoundAnywhere[] = "$productId - $description";
    }
}

// Report findings
if (count($foundInOther) > 0) {
    echo "✅ FOUND IN OTHER CATEGORIES (" . count($foundInOther) . " products):\n";
    echo "   ┌─────────────────────────────────────────────────────────┐\n";
    foreach ($foundInOther as $productId => $info) {
        echo "   │ $productId - {$info['description']}\n";
        foreach ($info['locations'] as $location) {
            echo "   │    → $location\n";
        }
    }
    echo "   └─────────────────────────────────────────────────────────┘\n\n";
} else {
    echo "❌ NONE FOUND IN OTHER CATEGORIES\n\n";
}

if (count($notFoundAnywhere) > 0) {
    echo "❌ NOT FOUND ANYWHERE IN bands_php (" . count($notFoundAnywhere) . " products):\n";
    echo "   ┌─────────────────────────────────────────────────────────┐\n";
    foreach ($notFoundAnywhere as $item) {
        echo "   │ " . str_pad($item, 57) . "│\n";
    }
    echo "   └─────────────────────────────────────────────────────────┘\n\n";
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY                                    ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Total missing from Classic Bands: " . str_pad(count($missingProducts), 27) . "║\n";
echo "║  Found in other categories: " . str_pad(count($foundInOther), 32) . "║\n";
echo "║  Not found anywhere: " . str_pad(count($notFoundAnywhere), 39) . "║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

?>
