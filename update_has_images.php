<?php
/**
 * Update has_images flag in catalog_products table based on actual image files
 * Scans all *_php/images directories and cross-references with database
 */

require_once 'includes/db_config_encrypted.php';

echo "=== Updating has_images flag based on actual image files ===\n\n";

try {
    $pdo = getDBConnection();
    
    // First, reset all has_images flags to 0
    echo "1. Resetting all has_images flags to 0...\n";
    $stmt = $pdo->prepare("UPDATE catalog_products SET has_images = 0");
    $stmt->execute();
    echo "   Reset complete.\n\n";
    
    // Find all *_php directories
    $phpDirs = glob('*_php', GLOB_ONLYDIR);
    echo "2. Found " . count($phpDirs) . " collection directories:\n";
    foreach ($phpDirs as $dir) {
        echo "   - $dir\n";
    }
    echo "\n";
    
    $totalImagesFound = 0;
    $updatedProducts = 0;
    $imageFiles = [];
    
    // Scan each *_php/images directory
    foreach ($phpDirs as $phpDir) {
        $imagesDir = $phpDir . '/images';
        if (!is_dir($imagesDir)) {
            echo "   Skipping $phpDir - no images directory\n";
            continue;
        }
        
        echo "3. Scanning $imagesDir...\n";
        
        // Use recursive directory iterator to find all image files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $dirImageCount = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $extension = strtolower($file->getExtension());
                
                // Check if it's an image file
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    // Extract base product ID (remove extension)
                    $productId = pathinfo($filename, PATHINFO_FILENAME);
                    
                    // Remove common suffixes that indicate variants
                    $productId = preg_replace('/(_alt|_view|_art|_copy|_backup)$/i', '', $productId);
                    
                    if (!empty($productId)) {
                        $imageFiles[] = [
                            'product_id' => $productId,
                            'file_path' => $file->getPathname(),
                            'collection' => $phpDir
                        ];
                        $dirImageCount++;
                    }
                }
            }
        }
        
        echo "   Found $dirImageCount image files in $phpDir\n";
        $totalImagesFound += $dirImageCount;
    }
    
    echo "\n4. Total image files found: $totalImagesFound\n\n";
    
    // Group by product_id to avoid duplicates
    $uniqueProducts = [];
    foreach ($imageFiles as $img) {
        $uniqueProducts[$img['product_id']] = $img;
    }
    
    echo "5. Unique products with images: " . count($uniqueProducts) . "\n\n";
    
    // Update database for products that have images
    echo "6. Updating database...\n";
    $updateStmt = $pdo->prepare("UPDATE catalog_products SET has_images = 1 WHERE product_id = ?");
    $notFoundProducts = [];
    
    foreach ($uniqueProducts as $productId => $imgInfo) {
        $updateStmt->execute([$productId]);
        if ($updateStmt->rowCount() > 0) {
            $updatedProducts++;
        } else {
            $notFoundProducts[] = $productId;
        }
    }
    
    echo "   Updated $updatedProducts products in database\n";
    echo "   " . count($notFoundProducts) . " image files had no matching database entries\n\n";
    
    // Show summary by category
    echo "7. Summary by category:\n";
    $categoryStmt = $pdo->query("
        SELECT category, 
               COUNT(*) as total_products,
               SUM(CASE WHEN has_images = 1 THEN 1 ELSE 0 END) as with_images
        FROM catalog_products 
        WHERE category IS NOT NULL 
        GROUP BY category 
        ORDER BY with_images DESC, category
    ");
    
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $percentage = $row['total_products'] > 0 ? round(($row['with_images'] / $row['total_products']) * 100, 1) : 0;
        echo sprintf("   %-20s: %3d/%3d products with images (%s%%)\n", 
                    $row['category'], 
                    $row['with_images'], 
                    $row['total_products'], 
                    $percentage);
    }
    
    echo "\n=== Update complete! ===\n";
    echo "The carousel dropdown should now show all categories that have actual image files.\n";
    
    // Show some examples of products not found in database
    if (count($notFoundProducts) > 0) {
        echo "\nNote: " . count($notFoundProducts) . " image files found but not in database.\n";
        echo "Examples of image files not in database:\n";
        foreach (array_slice($notFoundProducts, 0, 10) as $productId) {
            echo "   - $productId\n";
        }
        if (count($notFoundProducts) > 10) {
            echo "   ... and " . (count($notFoundProducts) - 10) . " more\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>