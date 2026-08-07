<?php
/**
 * Public Carousel Data Endpoint
 * Returns carousel data from catalog_products table
 */

// Carousel data is public - allow from any origin
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Include database connection
require_once 'includes/db_config.php';

try {
    $pdo = getDBConnection();
    
    // Read current carousel configuration
    $configFile = 'admin/carousel_filter_data.json';
    $carouselConfig = ['active' => false];
    
    if (file_exists($configFile)) {
        $carouselConfig = json_decode(file_get_contents($configFile), true) ?: ['active' => false];
    }
    
    $carouselItems = [];
    
    if ($carouselConfig['active'] && !empty($carouselConfig['collection']) && !empty($carouselConfig['filter'])) {
        // Get items from database based on admin configuration
        $category = $carouselConfig['filter']; // filter is actually the category
        
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, category, subcategory, image_files
            FROM catalog_products 
            WHERE category = ? AND has_images = 1 AND image_files IS NOT NULL
            AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
            AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
            ORDER BY RAND()
            LIMIT 20
        ");
        $stmt->execute([$category]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $carouselItems[] = [
                'product_id' => $product['product_id'],
                'category' => $product['category'],
                'src' => $product['image_files'],
                'name' => $product['product_name']
            ];
        }
    }
    
    // If no admin config or no items found, use default items
    if (empty($carouselItems)) {
        // Get default mix from multiple categories, excluding variants
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, category, subcategory, image_files
            FROM catalog_products 
            WHERE category IN ('celtic_bands', 'plain_bands', 'fancy_bands', 'family', 'engagement') 
            AND has_images = 1 AND image_files IS NOT NULL
            AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
            AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
            ORDER BY RAND()
            LIMIT 15
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $carouselItems[] = [
                'product_id' => $product['product_id'],
                'category' => $product['category'],
                'src' => $product['image_files'],
                'name' => $product['product_name']
            ];
        }
    }
    
    // Return carousel data
    echo json_encode([
        'active' => !empty($carouselItems),
        'collection' => $carouselConfig['collection'] ?? 'mixed',
        'filter' => $carouselConfig['filter'] ?? 'default',
        'timestamp' => time(),
        'count' => count($carouselItems),
        'items' => $carouselItems,
        'source' => 'catalog_products_database'
    ]);
    
} catch (Exception $e) {
    // Fallback to hardcoded items if database fails
    $defaultItems = [
        ['product_id' => '5310L', 'category' => 'celtic_bands', 'src' => 'bands_php/thumbs/images/celtic/5310L.png', 'name' => 'Celtic Knot Band'],
        ['product_id' => '5310M', 'category' => 'celtic_bands', 'src' => 'bands_php/thumbs/images/celtic/5310M.png', 'name' => 'Celtic Design'],
        ['product_id' => '5410M', 'category' => 'celtic_bands', 'src' => 'bands_php/thumbs/images/celtic/5410M.png', 'name' => 'Celtic Pattern'],
        ['product_id' => '5854M', 'category' => 'celtic_bands', 'src' => 'bands_php/thumbs/images/celtic/5854M.png', 'name' => 'Celtic Twist']
    ];
    
    echo json_encode([
        'active' => true,
        'collection' => 'fallback',
        'filter' => 'hardcoded',
        'timestamp' => time(),
        'count' => count($defaultItems),
        'items' => $defaultItems,
        'source' => 'fallback_hardcoded',
        'error' => $e->getMessage()
    ]);
}
?>