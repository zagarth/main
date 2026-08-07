<?php
// Script to fix Celtic image detection using XML mapping and file system scan

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
    
    echo "=== Fixing Celtic Image Detection ===\n\n";
    
    // Load XML mapping to understand which products share images
    $xmlFile = 'bands_php/celtic_bands_mapping.xml';
    if (!file_exists($xmlFile)) {
        echo "ERROR: Celtic XML mapping file not found: $xmlFile\n";
        exit(1);
    }
    
    $xml = simplexml_load_file($xmlFile);
    $patternGroups = [];
    
    // Parse XML to group products by pattern (they share images)
    foreach ($xml->pattern as $pattern) {
        $patternName = (string)$pattern['name'];
        $patternGroups[$patternName] = [];
        
        foreach ($pattern->band as $band) {
            $width = (string)$band['width'];
            $baseId = (string)$band->product_id;
            
            $genders = [];
            if ($band->available_genders) {
                foreach ($band->available_genders->gender as $gender) {
                    $genders[] = (string)$gender;
                }
            }
            
            $patternGroups[$patternName][] = [
                'base_id' => $baseId,
                'width' => $width,
                'genders' => $genders
            ];
        }
    }
    
    echo "Loaded " . count($patternGroups) . " Celtic patterns from XML\n";
    
    // Scan file system for available images
    $celticDir = 'bands_php/images/celtic/';
    $availableImages = [];
    
    if (is_dir($celticDir)) {
        $files = scandir($celticDir);
        foreach ($files as $file) {
            if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
                // Clean filename to get product ID
                $productId = preg_replace('/(_alt.*)?\..*$/', '', $file);
                if (!isset($availableImages[$productId])) {
                    $availableImages[$productId] = [];
                }
                $availableImages[$productId][] = $file;
            }
        }
    }
    
    echo "Found images for " . count($availableImages) . " Celtic products\n\n";
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    // Process each pattern group
    foreach ($patternGroups as $patternName => $products) {
        echo "Processing pattern: $patternName\n";
        
        // Find which products in this pattern have images
        $patternWithImages = [];
        foreach ($products as $product) {
            $baseId = $product['base_id'];
            
            // Check for M and L variants
            foreach (['M', 'L', ''] as $suffix) {
                $checkId = $baseId . $suffix;
                if (isset($availableImages[$checkId])) {
                    $patternWithImages[$checkId] = $availableImages[$checkId];
                }
            }
        }
        
        if (empty($patternWithImages)) {
            echo "  No images found for pattern $patternName\n";
            continue;
        }
        
        echo "  Found images for: " . implode(', ', array_keys($patternWithImages)) . "\n";
        
        // Update database for all products in this pattern that exist
        foreach ($products as $product) {
            $baseId = $product['base_id'];
            $width = $product['width'];
            
            // Check each potential product variant
            foreach (['M', 'L', ''] as $suffix) {
                $productId = $baseId . $suffix;
                
                // Check if this product exists in database
                $stmt = $pdo->prepare("SELECT product_id, has_images, image_files FROM catalog_products WHERE product_id = ? AND category = 'celtic_bands'");
                $stmt->execute([$productId]);
                $dbProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$dbProduct) {
                    continue; // Product not in database
                }
                
                // Determine which image to use - prefer exact match, fallback to pattern group
                $imageToUse = null;
                $imagePath = null;
                
                if (isset($availableImages[$productId])) {
                    // Exact match - use main image (first one)
                    $imageToUse = $availableImages[$productId][0];
                    $imagePath = "bands_php/thumbs/images/celtic/$imageToUse";
                } else {
                    // Look for any image in this pattern group
                    foreach ($patternWithImages as $availableId => $files) {
                        $imageToUse = $files[0]; // Use main image
                        $imagePath = "bands_php/thumbs/images/celtic/$imageToUse";
                        break; // Use first available image from pattern
                    }
                }
                
                if ($imageToUse && $imagePath) {
                    // Check if thumbs path exists, fallback to main images path
                    $thumbPath = "bands_php/thumbs/images/celtic/$imageToUse";
                    $mainPath = "bands_php/images/celtic/$imageToUse";
                    
                    $finalPath = file_exists($thumbPath) ? $thumbPath : $mainPath;
                    
                    if (file_exists($finalPath)) {
                        // Update database
                        $stmt = $pdo->prepare("
                            UPDATE catalog_products 
                            SET has_images = 1, image_files = ?, updated_at = CURRENT_TIMESTAMP 
                            WHERE product_id = ? AND category = 'celtic_bands'
                        ");
                        $stmt->execute([$finalPath, $productId]);
                        
                        if ($stmt->rowCount() > 0) {
                            echo "  ✓ Updated $productId -> $finalPath\n";
                            $updated++;
                        } else {
                            echo "  ⚠ No update needed for $productId\n";
                            $skipped++;
                        }
                    } else {
                        echo "  ✗ Image file not found: $finalPath\n";
                        $errors++;
                    }
                }
            }
        }
        echo "\n";
    }
    
    echo "=== Update Summary ===\n";
    echo "Products updated: $updated\n";
    echo "Products skipped: $skipped\n";
    echo "Errors: $errors\n\n";
    
    // Verify results
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM catalog_products WHERE category = 'celtic_bands' AND has_images = 1");
    $newCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Celtic products with images after update: $newCount\n";
    
    // Show sample updated products
    $stmt = $pdo->query("
        SELECT product_id, product_name, pattern, width_mm, has_images, image_files 
        FROM catalog_products 
        WHERE category = 'celtic_bands' AND has_images = 1 
        LIMIT 10
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($samples)) {
        echo "\nSample updated products:\n";
        foreach ($samples as $sample) {
            echo "  {$sample['product_id']}: {$sample['pattern']} ({$sample['width_mm']}mm) -> {$sample['image_files']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>