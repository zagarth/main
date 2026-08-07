<?php
// Script to compare Celtic images in file system vs database

// Create database user if needed
exec('sudo mysql -e "CREATE USER IF NOT EXISTS \'scanner\'@\'localhost\' IDENTIFIED BY \'scan123\'; GRANT ALL PRIVILEGES ON CadmanClients.* TO \'scanner\'@\'localhost\'; FLUSH PRIVILEGES;" 2>/dev/null');

$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "=== Celtic Images Analysis: File System vs Database ===\n\n";
    
    // Get Celtic images from file system
    $celticDir = 'bands_php/images/celtic/';
    $filesystemImages = [];
    
    if (is_dir($celticDir)) {
        $files = scandir($celticDir);
        foreach ($files as $file) {
            if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
                // Extract product ID (remove _alt1, _alt2, etc. and file extension)
                $productId = preg_replace('/(_alt.*)?\..*$/', '', $file);
                $filesystemImages[] = $productId;
            }
        }
    }
    
    // Get unique product IDs from file system
    $filesystemImages = array_unique($filesystemImages);
    sort($filesystemImages);
    
    echo "Celtic images found in file system: " . count($filesystemImages) . "\n";
    echo "Product IDs with images: " . implode(', ', $filesystemImages) . "\n\n";
    
    // Get Celtic products from database
    $stmt = $pdo->query("
        SELECT product_id, product_name, category, has_images, image_files 
        FROM catalog_products 
        WHERE category = 'celtic_bands' 
        ORDER BY product_id
    ");
    $dbCelticProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Celtic products in database: " . count($dbCelticProducts) . "\n\n";
    
    // Find matches and misses
    $dbProductIds = array_column($dbCelticProducts, 'product_id');
    $matches = array_intersect($filesystemImages, $dbProductIds);
    $imagesWithoutDbEntry = array_diff($filesystemImages, $dbProductIds);
    $dbWithoutImages = array_diff($dbProductIds, $filesystemImages);
    
    echo "=== ANALYSIS ===\n";
    echo "Products with images in file system AND in database: " . count($matches) . "\n";
    echo "Images in file system but NOT in database: " . count($imagesWithoutDbEntry) . "\n";
    echo "Database entries without corresponding images: " . count($dbWithoutImages) . "\n\n";
    
    if (!empty($matches)) {
        echo "=== MATCHES (File System + Database) ===\n";
        foreach ($matches as $productId) {
            $dbEntry = array_filter($dbCelticProducts, function($p) use ($productId) {
                return $p['product_id'] === $productId;
            });
            $dbEntry = reset($dbEntry);
            
            echo "✓ $productId: {$dbEntry['product_name']} - has_images={$dbEntry['has_images']}, image_files={$dbEntry['image_files']}\n";
        }
        echo "\n";
    }
    
    if (!empty($imagesWithoutDbEntry)) {
        echo "=== IMAGES WITHOUT DATABASE ENTRY ===\n";
        foreach ($imagesWithoutDbEntry as $productId) {
            echo "⚠ $productId: Image exists but no database entry\n";
        }
        echo "\n";
    }
    
    if (!empty($dbWithoutImages)) {
        echo "=== DATABASE ENTRIES MISSING IMAGES ===\n";
        foreach ($dbWithoutImages as $productId) {
            $dbEntry = array_filter($dbCelticProducts, function($p) use ($productId) {
                return $p['product_id'] === $productId;
            });
            $dbEntry = reset($dbEntry);
            
            echo "✗ $productId: {$dbEntry['product_name']} - has_images={$dbEntry['has_images']}\n";
        }
    }
    
    // Check how many database entries could be updated
    $needsUpdate = 0;
    foreach ($matches as $productId) {
        $dbEntry = array_filter($dbCelticProducts, function($p) use ($productId) {
            return $p['product_id'] === $productId;
        });
        $dbEntry = reset($dbEntry);
        
        if ($dbEntry['has_images'] != 1 || empty($dbEntry['image_files']) || $dbEntry['image_files'] === 'no images found') {
            $needsUpdate++;
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Database entries that could be updated with image paths: $needsUpdate\n";
    echo "Potential coverage improvement: " . count($matches) . "/" . count($dbCelticProducts) . " = " . round((count($matches) / count($dbCelticProducts)) * 100, 1) . "%\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>