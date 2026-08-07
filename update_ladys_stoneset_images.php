<?php
/**
 * Update Lady's Stoneset products with image file paths
 * Scans the image directories and matches files to product IDs
 */

include 'includes/db_config.php';

try {
    $pdo = getDBConnection();
    
    // Get all Lady's Stoneset products
    $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE category = 'ladies_jewelry'");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($products) . " Lady's Stoneset products\n";
    
    $imageDirectories = [
        'ladys_stoneset_php/Gems',
        'ladys_stoneset_php/Pearls'
    ];
    
    $updateCount = 0;
    
    foreach ($products as $productId) {
        $mainImagePath = null;
        
        // Scan each image directory for the main image file matching the product ID
        foreach ($imageDirectories as $directory) {
            if (is_dir($directory)) {
                $files = scandir($directory);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $fileWithoutExt = pathinfo($file, PATHINFO_FILENAME);
                    
                    // Check if file name matches product ID exactly (main image, no _alt suffix)
                    if ($fileWithoutExt === $productId) {
                        $mainImagePath = $directory . '/' . $file;
                        echo "  Found main image for $productId: $mainImagePath\n";
                        break 2; // Break out of both loops
                    }
                }
            }
        }
        
        // Update database if we found a main image
        if ($mainImagePath) {
            $updateStmt = $pdo->prepare("
                UPDATE catalog_products 
                SET image_files = ?, has_images = 1 
                WHERE product_id = ? AND category = 'ladies_jewelry'
            ");
            
            if ($updateStmt->execute([$mainImagePath, $productId])) {
                $updateCount++;
                echo "  ✓ Updated $productId with image: $mainImagePath\n";
            } else {
                echo "  ✗ Failed to update $productId\n";
            }
        } else {
            echo "  - No main image found for $productId\n";
        }
    }
    
    echo "\nCompleted! Updated $updateCount products with image data.\n";
    
    // Test one product to verify
    echo "\nTesting updated data for product 1898:\n";
    $testStmt = $pdo->prepare("SELECT image_files, has_images FROM catalog_products WHERE product_id = '1898'");
    $testStmt->execute();
    $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testResult) {
        echo "Has images: " . ($testResult['has_images'] ? 'YES' : 'NO') . "\n";
        echo "Image files: " . $testResult['image_files'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>