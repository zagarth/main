<?php
/**
 * Update Page References from XML
 * 
 * This script reads the page_products.xml file and updates the database
 * with the correct PDF page references for all products.
 */

require_once __DIR__ . '/includes/db_config.php';

class PageReferenceUpdater {
    private $pdo;
    private $updateCount = 0;
    private $processedCount = 0;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        echo "Starting page reference update from page_products.xml...\n";
    }
    
    public function updatePageReferences() {
        $xmlFile = __DIR__ . '/page_products.xml';
        
        if (!file_exists($xmlFile)) {
            die("Error: page_products.xml file not found!\n");
        }
        
        $xml = simplexml_load_file($xmlFile);
        if (!$xml) {
            die("Error: Could not parse page_products.xml!\n");
        }
        
        echo "Processing XML file...\n";
        
        // Process each category
        foreach ($xml->category as $category) {
            $categoryName = (string)$category['name'];
            echo "Processing category: $categoryName\n";
            
            // Process each product group (which has page references)
            foreach ($category->product_group as $group) {
                $page = (string)$group['page'];
                $pdfFile = $this->convertPageToPdfFile($page);
                
                echo "  Processing page $page -> $pdfFile\n";
                
                // Process each product in this group
                foreach ($group->product as $product) {
                    $productId = (string)$product['id'];
                    $this->updateProductPage($productId, $pdfFile, $page);
                    $this->processedCount++;
                }
            }
        }
        
        echo "Update completed!\n";
        echo "Total products processed: {$this->processedCount}\n";
        echo "Products updated: {$this->updateCount}\n";
    }
    
    private function convertPageToPdfFile($page) {
        // Convert page references like "23A" to "page_23a.pdf"
        $page = strtolower($page);
        
        // Handle special cases
        $specialMappings = [
            'celtic' => 'page_celtic.pdf',
            'fancy' => 'page_fancy.pdf', 
            'plain' => 'page_plain.pdf'
        ];
        
        if (isset($specialMappings[$page])) {
            return $specialMappings[$page];
        }
        
        // Convert standard page references
        return "page_{$page}.pdf";
    }
    
    private function updateProductPage($productId, $pdfFile, $originalPage) {
        try {
            // Check if product exists
            $checkStmt = $this->pdo->prepare(
                "SELECT product_id, pdf_file FROM catalog_products WHERE product_id = ?"
            );
            $checkStmt->execute([$productId]);
            $existing = $checkStmt->fetch();
            
            if (!$existing) {
                echo "    Warning: Product $productId not found in database\n";
                return;
            }
            
            // Update the PDF file reference
            $updateStmt = $this->pdo->prepare(
                "UPDATE catalog_products 
                 SET pdf_file = ?, page_reference = ?, has_pdf_page = 1 
                 WHERE product_id = ?"
            );
            
            $result = $updateStmt->execute([$pdfFile, $originalPage, $productId]);
            
            if ($result) {
                $oldFile = $existing['pdf_file'] ?? 'NULL';
                if ($oldFile !== $pdfFile) {
                    echo "    Updated $productId: $oldFile -> $pdfFile\n";
                    $this->updateCount++;
                }
            }
            
        } catch (PDOException $e) {
            echo "    Error updating product $productId: " . $e->getMessage() . "\n";
        }
    }
}

// Run the updater
$updater = new PageReferenceUpdater();
$updater->updatePageReferences();

echo "\nDone! You can now test the search functionality with more products.\n";
?>