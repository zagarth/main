<?php
// Analyze variant cleanup impact

$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Get all variant products
    $stmt = $pdo->query("
        SELECT product_id, SUBSTRING_INDEX(product_id, '-10K', 1) as base_product 
        FROM catalog_products 
        WHERE product_id LIKE '%-10K' 
        ORDER BY product_id
    ");
    
    $variants = $stmt->fetchAll();
    
    echo "VARIANT CLEANUP ANALYSIS\n";
    echo "========================\n\n";
    echo "Total products in database: 2081\n";
    echo "Products with -10K variants: " . count($variants) . "\n\n";
    
    $wouldLose = 0;
    $wouldKeep = 0;
    $baseExists = [];
    $baseDoesntExist = [];
    
    foreach ($variants as $variant) {
        $variantId = $variant['product_id'];
        $baseId = $variant['base_product'];
        
        // Check if base product exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_products WHERE product_id = ?");
        $checkStmt->execute([$baseId]);
        $hasBase = $checkStmt->fetchColumn() > 0;
        
        if ($hasBase) {
            $baseExists[] = "$variantId (base: $baseId exists)";
            $wouldLose++;
        } else {
            $baseDoesntExist[] = "$variantId (base: $baseId MISSING)";
            $wouldKeep++;
        }
    }
    
    echo "IMPACT OF CLEANING -10K VARIANTS:\n";
    echo "==================================\n";
    echo "Would REMOVE (base product exists): $wouldLose products\n";
    foreach ($baseExists as $item) {
        echo "  ✗ $item\n";
    }
    
    echo "\nWould KEEP (no base product): $wouldKeep products\n";
    foreach ($baseDoesntExist as $item) {
        echo "  ✓ $item\n";
    }
    
    echo "\nSUMMARY:\n";
    echo "========\n";
    echo "Current total: 2081 products\n";
    echo "Would remove: $wouldLose variant products\n";
    echo "New total: " . (2081 - $wouldLose) . " products\n";
    echo "Data loss: " . round(($wouldLose / 2081) * 100, 1) . "%\n";
    
    // Check for other variant patterns
    echo "\nOTHER VARIANT PATTERNS TO CONSIDER:\n";
    echo "===================================\n";
    
    $otherPatterns = [
        'SIZE variants' => "SELECT COUNT(*) FROM catalog_products WHERE product_id REGEXP '-[0-9]+(MM|MF|LF|LM)$'",
        'LETTER variants' => "SELECT COUNT(*) FROM catalog_products WHERE product_id REGEXP '[A-Z]+-[A-Z]$'",
        'NUMBER variants' => "SELECT COUNT(*) FROM catalog_products WHERE product_id REGEXP '-[0-9]+$'"
    ];
    
    foreach ($otherPatterns as $name => $query) {
        $stmt = $pdo->query($query);
        $count = $stmt->fetchColumn();
        echo "$name: $count products\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>