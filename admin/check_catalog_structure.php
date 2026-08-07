<?php
// Quick script to check catalog_products table structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db_config.php';

try {
    $pdo = getDBConnection();
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE catalog_products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>catalog_products Table Structure:</h2><pre>";
    print_r($columns);
    echo "</pre>";
    
    // Get a few sample rows for product_id = '21'
    $stmt = $pdo->prepare("SELECT * FROM catalog_products WHERE product_id = '21' LIMIT 5");
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample rows for product_id = '21':</h2><pre>";
    print_r($samples);
    echo "</pre>";
    
    // Check for any rows matching '21'  
    $stmt = $pdo->prepare("SELECT product_id, pdf_file, image_files, page_reference FROM catalog_products WHERE product_id LIKE '21%' LIMIT 10");
    $stmt->execute();
    $likeMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Rows with product_id LIKE '21%':</h2><pre>";
    print_r($likeMatches);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
