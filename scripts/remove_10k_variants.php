<?php
// Script to remove -10K variant products where base product exists

// Database connection
$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Find all -10K products and their base products
    $stmt = $pdo->query("
        SELECT product_id, SUBSTRING_INDEX(product_id, '-10K', 1) as base_product 
        FROM catalog_products 
        WHERE product_id LIKE '%-10K'
        ORDER BY product_id
    ");
    
    $variants = $stmt->fetchAll();
    
    echo "Found " . count($variants) . " products with -10K variants\n\n";
    
    $removedCount = 0;
    $skippedCount = 0;
    
    foreach ($variants as $variant) {
        $variantId = $variant['product_id'];
        $baseId = $variant['base_product'];
        
        // Check if base product exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_products WHERE product_id = ?");
        $checkStmt->execute([$baseId]);
        $baseExists = $checkStmt->fetchColumn() > 0;
        
        if ($baseExists) {
            // Remove the -10K variant
            $deleteStmt = $pdo->prepare("DELETE FROM catalog_products WHERE product_id = ?");
            $deleteStmt->execute([$variantId]);
            
            echo "✓ Removed $variantId (base product $baseId exists)\n";
            $removedCount++;
        } else {
            echo "⚠ Kept $variantId (base product $baseId does NOT exist)\n";
            $skippedCount++;
        }
    }
    
    echo "\nSummary:\n";
    echo "- Removed: $removedCount -10K variants\n";
    echo "- Kept: $skippedCount -10K variants (no base product)\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>