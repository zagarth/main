<?php
/**
 * Update Celtic Products with New Celtic Page Reference
 * Assigns the "celtic" page to all Celtic products that currently don't have a page reference
 */

// Database configuration
$host = 'localhost';
$dbname = 'CadmanClients';
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "=== Updating Celtic Page References ===\n\n";
    
    // First, let's see what Celtic products currently don't have page references
    $checkSql = "SELECT product_id, product_name, category, subcategory, page_reference, has_pdf_page, pdf_file 
                 FROM catalog_products 
                 WHERE (category = 'celtic_bands' OR subcategory LIKE '%celtic%') 
                 AND (page_reference IS NULL OR page_reference = '' OR has_pdf_page = 0)
                 ORDER BY product_id";
    
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute();
    $celticWithoutPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($celticWithoutPages) . " Celtic products without page references:\n";
    echo "----------------------------------------------------------------\n";
    foreach ($celticWithoutPages as $product) {
        echo "ID: {$product['product_id']} - {$product['product_name']} (Page: '{$product['page_reference']}', PDF: '{$product['pdf_file']}')\n";
    }
    echo "\n";
    
    if (count($celticWithoutPages) == 0) {
        echo "No Celtic products need updating!\n";
        exit;
    }
    
    // Ask for confirmation before updating
    echo "Do you want to assign page 'celtic' to these " . count($celticWithoutPages) . " products? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) !== 'y') {
        echo "Update cancelled.\n";
        exit;
    }
    
    // Update all Celtic products without page references to use the "celtic" page
    $updateSql = "UPDATE catalog_products 
                  SET page_reference = 'celtic',
                      has_pdf_page = 1,
                      pdf_file = 'page_celtic.pdf',
                      data_complete = 1,
                      needs_research = 0
                  WHERE (category = 'celtic_bands' OR subcategory LIKE '%celtic%') 
                  AND (page_reference IS NULL OR page_reference = '' OR has_pdf_page = 0)";
    
    $stmt = $pdo->prepare($updateSql);
    $result = $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    echo "\n=== UPDATE RESULTS ===\n";
    echo "Successfully updated {$updatedCount} Celtic products!\n";
    echo "All updated products now reference: page_celtic.pdf\n\n";
    
    // Verify the updates
    echo "=== VERIFICATION ===\n";
    $verifySql = "SELECT product_id, product_name, page_reference, pdf_file 
                  FROM catalog_products 
                  WHERE page_reference = 'celtic'
                  ORDER BY product_id";
    
    $stmt = $pdo->prepare($verifySql);
    $stmt->execute();
    $updatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products now using 'celtic' page (" . count($updatedProducts) . " total):\n";
    echo "----------------------------------------------------------------\n";
    foreach ($updatedProducts as $product) {
        echo "✓ {$product['product_id']} - {$product['product_name']} → {$product['pdf_file']}\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "✅ Database update completed successfully!\n";
    echo "✅ {$updatedCount} Celtic products now reference page_celtic.pdf\n";
    echo "✅ All Celtic products with missing pages have been assigned to the new celtic page\n";
    echo "\nNext steps:\n";
    echo "- Test search results for Celtic products (5424, 5430, etc.)\n";
    echo "- Verify PDF previews load correctly\n";
    echo "- Check that page_celtic.pdf contains all the expected Celtic patterns\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>