#!/usr/bin/env php
<?php
/**
 * Check Classic Bands (Plain) collection for missing images
 * Compares catalog index with actual image files
 * Note: M/L versions share images - only need one image for both genders
 */

// Classic Bands product base IDs from catalog (without M/L suffix where both genders available)
$catalogProducts = [
    // Tiffany Profile bands (3T, 4T, 5T, 6T, 7T, 8T series)
    '3T18',      // M & L
    '4T00R',     // M & L
    '4T18',      // M & L
    '5T00R',     // M & L
    '5T18',      // M & L
    '6T00R',     // M & L
    '6T18',      // M & L
    '7T00R',     // M & L
    '7T18',      // M & L
    '8T00R',     // M & L
    
    // Standard Round Profile (300, 400, 500, 600, 700 series)
    '200',       // M & L
    '200R',      // M & L
    '300',       // M & L
    '300R',      // M & L
    '400',       // M & L
    '400R',      // M & L
    '400T',      // M & L
    '400B',      // L only (but treat as base)
    '450',       // M & L
    '500',       // M & L
    '500T',      // M & L
    '550T',      // M & L
    '600',       // M & L
    '600T',      // M & L
    '700T',      // M & L
    
    // Comfort Fit bands (3001, 4001, 5001 series)
    '3001',      // M & L
    '4001',      // M & L
    '5001',      // M & L
    
    // Silver bands (S prefix)
    'S200R',     // M & L
    'S300R',     // M & L
    'S400R',     // M & L
    'S500R',     // M & L
    'S600R',     // M & L
    
    // Special designs
    '5344P',     // M & L
    '5345P',     // M & L
    
    // 1000 series
    '1000',      // M & L
    
    // 250 series
    '250',       // M & L
    
    // 380 series
    '380',       // M & L
];

// Get actual image files
$imagePath = '/var/www/html/homesite/bands_php/images/plain/';
$actualFiles = glob($imagePath . '*.png');

// Extract product IDs from files (remove gender suffix M/L)
$foundProducts = [];
foreach ($actualFiles as $file) {
    $filename = basename($file);
    // Extract base product ID (remove _alt1, _alt2, _view2, etc. AND M/L suffix)
    $productId = preg_replace('/(_.+)?\.png$/', '', $filename);
    // Remove M or L suffix
    $baseId = preg_replace('/[ML]$/i', '', $productId);
    $foundProducts[$baseId] = isset($foundProducts[$baseId]) ? $foundProducts[$baseId] + 1 : 1;
}

// Sort for comparison
sort($catalogProducts);
$foundProductIds = array_keys($foundProducts);
sort($foundProductIds);

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║         CLASSIC BANDS IMAGE INVENTORY CHECK                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Statistics
$totalCatalog = count($catalogProducts);
$totalFound = count($foundProducts);
$totalFiles = count($actualFiles);

echo "📊 STATISTICS:\n";
echo "   Total products in catalog: $totalCatalog\n";
echo "   Unique products with images: $totalFound\n";
echo "   Total image files: $totalFiles\n";
echo "   Average images per product: " . round($totalFiles / max($totalFound, 1), 1) . "\n\n";

// Missing images
echo "❌ MISSING IMAGES (Products in catalog but no images):\n";
echo "   ┌─────────────────────────────────────────────────────────┐\n";
$missingCount = 0;
foreach ($catalogProducts as $productId) {
    if (!isset($foundProducts[$productId])) {
        $missingCount++;
        echo "   │ " . str_pad($productId, 20) . " - MISSING" . str_repeat(' ', 33) . "│\n";
    }
}
if ($missingCount === 0) {
    echo "   │ " . str_pad("✓ All catalog products have images!", 57) . "│\n";
}
echo "   └─────────────────────────────────────────────────────────┘\n";
echo "   Total missing: $missingCount\n\n";

// Extra images (not in catalog)
echo "⚠️  EXTRA IMAGES (Images found but not in catalog):\n";
echo "   ┌─────────────────────────────────────────────────────────┐\n";
$extraCount = 0;
foreach ($foundProductIds as $productId) {
    // Normalize for comparison (handle case differences)
    $normalizedId = strtoupper($productId);
    $found = false;
    foreach ($catalogProducts as $catalogId) {
        if (strtoupper($catalogId) === $normalizedId) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $extraCount++;
        $imageCount = $foundProducts[$productId];
        echo "   │ " . str_pad($productId, 20) . " - " . $imageCount . " image(s)" . str_repeat(' ', 29 - strlen($imageCount)) . "│\n";
    }
}
if ($extraCount === 0) {
    echo "   │ " . str_pad("✓ No extra images found", 57) . "│\n";
}
echo "   └─────────────────────────────────────────────────────────┘\n";
echo "   Total extra: $extraCount\n\n";

// Products with multiple images
echo "📸 PRODUCTS WITH MULTIPLE VIEWS:\n";
echo "   ┌─────────────────────────────────────────────────────────┐\n";
$multiCount = 0;
foreach ($foundProducts as $productId => $count) {
    if ($count > 1) {
        $multiCount++;
        echo "   │ " . str_pad($productId, 20) . " - " . $count . " images" . str_repeat(' ', 31 - strlen($count)) . "│\n";
    }
}
if ($multiCount === 0) {
    echo "   │ " . str_pad("No products with multiple views", 57) . "│\n";
}
echo "   └─────────────────────────────────────────────────────────┘\n";
echo "   Total with multiple views: $multiCount\n\n";

// Detailed file list grouped by base product
echo "📁 DETAILED FILE LIST (Grouped by base product ID):\n";
foreach ($foundProductIds as $productId) {
    // Find all files for this base product (including M/L variants)
    $files = array_filter($actualFiles, function($file) use ($productId) {
        $filename = basename($file);
        $fileBase = preg_replace('/(_.+)?\.png$/', '', $filename);
        $fileBase = preg_replace('/[ML]$/i', '', $fileBase);
        return strcasecmp($fileBase, $productId) === 0;
    });
    echo "   $productId (" . count($files) . " file(s)):\n";
    foreach ($files as $file) {
        echo "      - " . basename($file) . "\n";
    }
}

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY                                    ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Missing products: " . str_pad($missingCount, 42) . "║\n";
echo "║  Extra products: " . str_pad($extraCount, 44) . "║\n";
echo "║  Products with images: " . str_pad($totalFound, 38) . "║\n";
echo "║  Total image files: " . str_pad($totalFiles, 41) . "║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

?>
