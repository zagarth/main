<?php
// Quick debug to check what page references look like in database
require_once __DIR__ . '/config/EncryptedConfig.php';

try {
    $dbConfig = EncryptedConfig::getSecureConfig();
    
    $pdo = new PDO(
        "mysql:host={$dbConfig['DB_HOST']};dbname={$dbConfig['DB_NAME']};charset=utf8mb4",
        $dbConfig['DB_USER'],
        $dbConfig['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get a few corp products to see page reference format
    $stmt = $pdo->prepare("SELECT product_id, page_reference, pdf_file FROM catalog_products WHERE category = 'corporate' LIMIT 5");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "<h3>Sample Corp Products Page References:</h3>";
    foreach ($results as $row) {
        echo "Product: {$row['product_id']} | Page Ref: '{$row['page_reference']}' | PDF File: '{$row['pdf_file']}'<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>