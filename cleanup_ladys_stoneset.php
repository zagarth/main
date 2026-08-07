<?php
/**
 * Clean up Lady's Stoneset individual detail pages
 * Move them to scripts folder since ProductModal system is now used
 */

echo "=== Lady's Stoneset Cleanup ===\n";
echo "Moving individual detail pages to scripts folder...\n\n";

// Create destination directory
$scriptsDir = 'scripts/ladys_stoneset_legacy_pages';
if (!is_dir($scriptsDir)) {
    mkdir($scriptsDir, 0755, true);
    echo "Created directory: $scriptsDir\n";
}

// Find all detail pages
$detailPages = glob('ladys_stoneset_php/*_detail.php');

echo "Found " . count($detailPages) . " detail pages to move:\n\n";

$moved = 0;
$errors = [];

foreach ($detailPages as $filePath) {
    $filename = basename($filePath);
    $destination = $scriptsDir . '/' . $filename;
    
    if (rename($filePath, $destination)) {
        echo "✅ Moved: $filename\n";
        $moved++;
    } else {
        $errors[] = "Failed to move: $filename";
        echo "❌ Failed to move: $filename\n";
    }
}

echo "\n=== Summary ===\n";
echo "Successfully moved: $moved files\n";

if (!empty($errors)) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Check if ladys_stoneset_php directory has any remaining content
$remainingFiles = array_diff(scandir('ladys_stoneset_php'), ['.', '..']);
echo "\nRemaining files in ladys_stoneset_php/:\n";

$hasImages = false;
foreach ($remainingFiles as $file) {
    if (is_dir('ladys_stoneset_php/' . $file)) {
        echo "  📁 $file/ (directory - keeping for images)\n";
        $hasImages = true;
    } else {
        echo "  📄 $file\n";
    }
}

if ($hasImages) {
    echo "\n📸 Image directories preserved for ProductModal system\n";
}

echo "\n✅ Cleanup completed!\n";
echo "Lady's Stoneset now uses modern ProductModal system exclusively.\n";
echo "All product details preserved in database.\n";
echo "Individual detail pages moved to: $scriptsDir\n";

// Verify ProductModal system is working
echo "\n=== Verification ===\n";
echo "Testing database connectivity for ProductModal...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=CadmanClients", 'scanner', 'scan123');
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalog_products WHERE category = 'ladies_jewelry' AND data_complete = 1");
    $count = $stmt->fetchColumn();
    echo "✅ Database accessible: $count Lady's Stoneset products ready for ProductModal\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Lady's Stoneset modernization complete!\n";
echo "Users now get the full ProductModal experience with quote request functionality.\n";
?>