<?php
/**
 * Add Series Mapping Information to Database
 * Extracts series info from hardcoded logic and adds to catalog_products table
 */

require_once 'includes/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=CadmanClients",
        "cadman_admin",
        "Admin2025!Cadman",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Product series mappings from hardcoded logic
    $seriesPages = [
        '50' => ['page_03a.pdf', 'page_03b.pdf'], // 5000 series wedding
        '51' => ['page_03c.pdf', 'page_03d.pdf'],
        '52' => ['page_03e.pdf', 'page_03g.pdf'],
        '60' => ['page_06a.pdf', 'page_06b.pdf'], // 6000 series mens
        '61' => ['page_06c.pdf', 'page_06d.pdf'],
        '70' => ['page_07a.pdf', 'page_07b.pdf'], // 7000 series
        '80' => ['page_08a.pdf', 'page_08b.pdf'], // 8000 series ladies
        '90' => ['page_09a.pdf', 'page_09b.pdf'], // 9000 series
        '10' => ['page_10a.pdf', 'page_10b.pdf'], // 1000 series
    ];
    
    // Specific product mappings
    $productMappings = [
        '5666M' => ['page_03a.pdf', 'page_03b.pdf'],
        '5000' => ['page_01a.pdf', 'page_01b.pdf'],
        '6000' => ['page_06a.pdf', 'page_06b.pdf'],
        '7000' => ['page_07a.pdf', 'page_07b.pdf'],
        '8000' => ['page_08a.pdf', 'page_08b.pdf'],
        '9000' => ['page_09a.pdf', 'page_09b.pdf'],
        '1000' => ['page_10a.pdf', 'page_10b.pdf'],
    ];
    
    $updatedCount = 0;
    
    // Update products with series information
    $stmt = $pdo->prepare("UPDATE catalog_products SET product_series = ?, content_pages = ? WHERE product_id = ?");
    
    // Get all products to analyze
    $products = $pdo->query("SELECT product_id FROM catalog_products")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($products as $productId) {
        $series = null;
        $contentPages = null;
        
        // Extract series from product ID (first 1-2 digits)
        if (preg_match('/^(\d{1,2})/', $productId, $matches)) {
            $potentialSeries = $matches[1];
            
            // Check if this series exists in our mappings
            if (isset($seriesPages[$potentialSeries])) {
                $series = $potentialSeries;
                $contentPages = json_encode($seriesPages[$potentialSeries]);
            }
        }
        
        // Check for specific product mappings
        $baseProductId = preg_replace('/[ML]$/', '', $productId); // Remove M/L suffix
        if (isset($productMappings[$productId])) {
            $contentPages = json_encode($productMappings[$productId]);
        } elseif (isset($productMappings[$baseProductId])) {
            $contentPages = json_encode($productMappings[$baseProductId]);
        }
        
        // Update if we have series or content page info
        if ($series || $contentPages) {
            $stmt->execute([$series, $contentPages, $productId]);
            $updatedCount++;
            
            if ($updatedCount % 50 == 0) {
                echo "Updated $updatedCount products...\n";
            }
        }
    }
    
    echo "\n=== SERIES MAPPING UPDATE COMPLETE ===\n";
    echo "Products updated: $updatedCount\n";
    
    // Show summary of series distribution
    $seriesStats = $pdo->query("
        SELECT product_series, COUNT(*) as count 
        FROM catalog_products 
        WHERE product_series IS NOT NULL 
        GROUP BY product_series 
        ORDER BY product_series
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== SERIES DISTRIBUTION ===\n";
    foreach ($seriesStats as $stat) {
        $seriesDescription = [
            '10' => '1000 series',
            '50' => '5000 series (wedding)',
            '51' => '5100 series',
            '52' => '5200 series',
            '60' => '6000 series (mens)',
            '61' => '6100 series',
            '70' => '7000 series',
            '80' => '8000 series (ladies)',
            '90' => '9000 series'
        ];
        
        $description = $seriesDescription[$stat['product_series']] ?? $stat['product_series'] . ' series';
        echo "Series {$stat['product_series']}: {$stat['count']} products ($description)\n";
    }
    
    // Show sample products with content pages
    echo "\n=== SAMPLE PRODUCTS WITH CONTENT PAGES ===\n";
    $samples = $pdo->query("
        SELECT product_id, product_series, content_pages 
        FROM catalog_products 
        WHERE content_pages IS NOT NULL 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        $pages = json_decode($sample['content_pages'], true);
        $pagesStr = implode(', ', $pages);
        echo "{$sample['product_id']} (Series {$sample['product_series']}): {$pagesStr}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>