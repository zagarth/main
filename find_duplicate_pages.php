<?php
// Find products that appear on multiple pages

$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "ANALYZING PRODUCTS APPEARING ON MULTIPLE PAGES\n";
    echo "==============================================\n\n";

    // Find duplicate product IDs
    $stmt = $pdo->query("
        SELECT product_id, COUNT(*) as page_count, GROUP_CONCAT(DISTINCT pdf_file) as pages
        FROM catalog_products 
        WHERE pdf_file IS NOT NULL
        GROUP BY product_id 
        HAVING COUNT(*) > 1 
        ORDER BY page_count DESC, product_id
    ");
    
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✓ No products found on multiple pages!\n";
    } else {
        echo "Found " . count($duplicates) . " products appearing on multiple pages:\n\n";
        
        foreach ($duplicates as $duplicate) {
            $productId = $duplicate['product_id'];
            $pageCount = $duplicate['page_count'];
            $pages = $duplicate['pages'];
            
            echo "📄 $productId appears on $pageCount pages: $pages\n";
            
            // Get detailed info for each occurrence
            $detailStmt = $pdo->prepare("
                SELECT pdf_file, page_reference, category, special_notes 
                FROM catalog_products 
                WHERE product_id = ? 
                ORDER BY pdf_file
            ");
            $detailStmt->execute([$productId]);
            $details = $detailStmt->fetchAll();
            
            foreach ($details as $detail) {
                echo "   - {$detail['pdf_file']} (page {$detail['page_reference']}) [{$detail['category']}]";
                if ($detail['special_notes']) {
                    echo " - {$detail['special_notes']}";
                }
                echo "\n";
            }
            echo "\n";
        }
        
        echo "RESOLUTION OPTIONS:\n";
        echo "===================\n";
        echo "1. Keep primary page (usually lower page number)\n";
        echo "2. Keep most recently added/updated entry\n";
        echo "3. Merge information and keep best entry\n";
        echo "4. Manual review case by case\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>