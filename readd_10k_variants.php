<?php
// Re-add the 10K variants we removed with correct page assignments

$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "RE-ADDING 10K VARIANTS WITH CORRECT PAGE ASSIGNMENTS\n";
    echo "====================================================\n\n";

    // The 11 variants we removed and their correct pages based on our analysis
    $variantsToAdd = [
        // Medical variants (page 25A)
        '14EMC-10K' => ['page' => '25A', 'category' => 'medical'],
        '15M-10K' => ['page' => '25A', 'category' => 'medical'], 
        '1BEMC-10K' => ['page' => '25A', 'category' => 'medical'],
        '1BM-10K' => ['page' => '25A', 'category' => 'medical'],
        '2BMC-10K' => ['page' => '25A', 'category' => 'medical'],
        '2EMC-10K' => ['page' => '25A', 'category' => 'medical'],
        '6BMC-10K' => ['page' => '25A', 'category' => 'medical'],
        '6MMC-10K' => ['page' => '25A', 'category' => 'medical'],
        'P120-10K' => ['page' => '25A', 'category' => 'medical'],
        'P21BM-10K' => ['page' => '25A', 'category' => 'medical'],
        'P24M-10K' => ['page' => '25A', 'category' => 'medical']
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($variantsToAdd as $productId => $info) {
        $pageFile = "page_{$info['page']}.pdf";
        $pageRef = $info['page'];
        $category = $info['category'];
        
        // Check if variant already exists
        $checkStmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE product_id = ?");
        $checkStmt->execute([$productId]);
        
        if ($checkStmt->fetch()) {
            echo "⚠ $productId already exists - skipping\n";
            $skipped++;
            continue;
        }
        
        // Add the 10K variant
        $stmt = $pdo->prepare("
            INSERT INTO catalog_products 
            (product_id, pdf_file, page_reference, category, pattern, style, special_notes, 
             product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
             series, white_gold_available, base_price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $productId,              // product_id
            $pageFile,              // pdf_file
            $pageRef,               // page_reference
            $category,              // category
            null,                   // pattern
            null,                   // style
            'Re-added 10K variant with correct page assignment', // special_notes
            null,                   // product_name
            null,                   // subcategory
            null,                   // width_mm
            null,                   // profile
            null,                   // diamond_count
            null,                   // gender_variant
            null,                   // series
            0,                      // white_gold_available
            null                    // base_price
        ]);
        
        if ($success) {
            echo "✓ Added $productId -> $pageFile (page $pageRef)\n";
            $added++;
        } else {
            echo "✗ Failed to add $productId\n";
        }
    }
    
    echo "\nSummary: Added $added variants, Skipped $skipped existing\n";
    
    // Verify total count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM catalog_products");
    $totalProducts = $countStmt->fetchColumn();
    echo "Total products in database: $totalProducts\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>