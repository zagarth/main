<?php
// Script to assign categories to products that are missing them

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
    
    echo "=== Assigning Categories to Products Missing Them ===\n";
    
    // Category assignments based on page patterns observed
    $categoryMappings = [
        'page_09a'  => 'other',        // Mostly 'other' (12/18)
        'page_09aa' => 'other',        // Mostly 'other' (12/20) 
        'page_09b'  => 'other',        // Mostly 'other' (9/15)
        'page_10'   => 'family',       // Predominantly 'family' (10/10)
        'page_10a'  => 'family',       // Predominantly 'family' (5/5)
        'page_10b'  => 'family',       // Predominantly 'family' (11/12)
        'page_10c'  => 'family',       // Predominantly 'family' (17/18)
        'page_10d'  => 'family',       // Predominantly 'family' (2/2)
        'page_3eee2' => 'celtic_bands' // Contains CC codes which are Celtic products
    ];
    
    $totalUpdated = 0;
    
    foreach ($categoryMappings as $pageRef => $category) {
        echo "\n--- Processing $pageRef -> $category ---\n";
        
        // Find products with NULL category on this page
        $stmt = $pdo->prepare("
            SELECT product_id, product_name 
            FROM catalog_products 
            WHERE page_reference = ? AND category IS NULL
        ");
        $stmt->execute([$pageRef]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($products)) {
            echo "No products found missing category on $pageRef\n";
            continue;
        }
        
        echo "Found " . count($products) . " products missing category:\n";
        foreach ($products as $product) {
            echo "  - {$product['product_id']}: {$product['product_name']}\n";
        }
        
        // Update all products on this page to the appropriate category
        $updateStmt = $pdo->prepare("
            UPDATE catalog_products 
            SET category = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE page_reference = ? AND category IS NULL
        ");
        $updateStmt->execute([$category, $pageRef]);
        
        $updated = $updateStmt->rowCount();
        $totalUpdated += $updated;
        echo "Updated $updated products to category '$category'\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "Total products updated: $totalUpdated\n";
    
    // Check remaining NULL categories
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM catalog_products WHERE category IS NULL");
    $remaining = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Remaining products with NULL category: $remaining\n";
    
    if ($remaining > 0) {
        echo "\nRemaining products needing attention:\n";
        $stmt = $pdo->query("
            SELECT page_reference, COUNT(*) as count 
            FROM catalog_products 
            WHERE category IS NULL 
            GROUP BY page_reference 
            ORDER BY page_reference
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['page_reference']}: {$row['count']} products\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>