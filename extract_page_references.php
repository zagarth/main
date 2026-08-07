<?php
/**
 * Extract Page References from Index PDF Files
 * Updates products that have pdf_file but no page_reference
 */

require_once 'includes/db_config_encrypted.php';

class PageReferenceExtractor {
    private $pdo;
    private $catalogDir = '/var/www/html/homesite/Cadman_catalog';
    private $updatedProducts = [];
    
    public function __construct() {
        $this->pdo = getDBConnection();
        echo "Database connected successfully.\n";
    }
    
    public function processAllIndexFiles() {
        echo "=== EXTRACTING PAGE REFERENCES FROM INDEX PDF FILES ===\n";
        
        // Get products that need page references
        $productsToUpdate = $this->getProductsNeedingPageRefs();
        
        if (empty($productsToUpdate)) {
            echo "No products need page reference updates.\n";
            return;
        }
        
        echo "Found " . count($productsToUpdate) . " products needing page references.\n\n";
        
        // Group by index file
        $fileGroups = [];
        foreach ($productsToUpdate as $product) {
            $file = $product['pdf_file'];
            if (!isset($fileGroups[$file])) {
                $fileGroups[$file] = [];
            }
            $fileGroups[$file][] = $product;
        }
        
        // Process each index file
        foreach ($fileGroups as $fileName => $products) {
            $this->processIndexFile($fileName, $products);
        }
        
        $this->showResults();
    }
    
    private function getProductsNeedingPageRefs() {
        $stmt = $this->pdo->query('
            SELECT product_id, pdf_file, category
            FROM catalog_products 
            WHERE pdf_file IS NOT NULL 
            AND pdf_file != ""
            AND (page_reference IS NULL OR page_reference = "")
            ORDER BY pdf_file, product_id
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function processIndexFile($fileName, $products) {
        echo "\nProcessing: $fileName\n";
        echo "Products to find: " . implode(', ', array_column($products, 'product_id')) . "\n";
        
        $filePath = $this->catalogDir . '/' . $fileName;
        
        if (!file_exists($filePath)) {
            echo "  Error: File not found!\n";
            return;
        }
        
        // Extract text from PDF
        $text = $this->extractPDFText($filePath);
        if (!$text) {
            echo "  Error: Could not extract text from PDF\n";
            return;
        }
        
        // Find page references for each product
        $foundMappings = $this->findPageReferences($text, $products);
        
        // Update database
        $this->updateProductPageReferences($foundMappings);
        
        echo "  Found page references for " . count($foundMappings) . " products\n";
    }
    
    private function extractPDFText($filePath) {
        $tempFile = '/tmp/pdf_extract_' . uniqid() . '.txt';
        $command = "pdftotext '$filePath' '$tempFile' 2>/dev/null";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($tempFile)) {
            return false;
        }
        
        $text = file_get_contents($tempFile);
        unlink($tempFile);
        return $text;
    }
    
    private function findPageReferences($text, $products) {
        $mappings = [];
        
        foreach ($products as $product) {
            $productId = $product['product_id'];
            
            // Look for patterns like:
            // "PM79 . . . . . . 20A" (dots with page reference)
            $patterns = [
                // Dot pattern: "PRODUCT . . . . PAGE" (with spaces between dots)
                '/(' . preg_quote($productId, '/') . ')\s*[\.\s]+\s*([0-9]+[A-Z])/i',
                
                // Direct page reference patterns  
                '/(' . preg_quote($productId, '/') . '.*?page[_\s]*([0-9]+[a-z]*))/',
                '/(' . preg_quote($productId, '/') . '.*?Page[_\s]*:?[_\s]*([0-9]+[a-z]*))/',
                '/(' . preg_quote($productId, '/') . '.*?pg[_\s]*([0-9]+[a-z]*))/',
                
                // Line-based patterns (product on one line, page on next)
                '/(' . preg_quote($productId, '/') . '.*?\n.*?page[_\s]*([0-9]+[a-z]*))/',
                '/(' . preg_quote($productId, '/') . '.*?\n.*?Page[_\s]*:?[_\s]*([0-9]+[a-z]*))/',
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern . 'i', $text, $matches)) {
                    // Convert format like "20A" to "page_20a"
                    $pageRef = 'page_' . strtolower($matches[2]);
                    $mappings[$productId] = $pageRef;
                    echo "    Found: $productId -> $pageRef\n";
                    break;
                }
            }
            
            // If still not found, try broader search
            if (!isset($mappings[$productId])) {
                // Look for the product ID and any nearby page reference
                $productPos = strpos($text, $productId);
                if ($productPos !== false) {
                    // Get text around the product ID (500 chars before and after)
                    $start = max(0, $productPos - 500);
                    $length = 1000;
                    $context = substr($text, $start, $length);
                    
                    // Look for page patterns in the context
                    if (preg_match('/page[_\s]*([0-9]+[a-z]*)/i', $context, $matches)) {
                        $pageRef = 'page_' . $matches[1];
                        $mappings[$productId] = $pageRef;
                        echo "    Found (context): $productId -> $pageRef\n";
                    }
                }
            }
        }
        
        return $mappings;
    }
    
    private function updateProductPageReferences($mappings) {
        if (empty($mappings)) {
            return;
        }
        
        $updateStmt = $this->pdo->prepare('
            UPDATE catalog_products 
            SET page_reference = ? 
            WHERE product_id = ?
        ');
        
        foreach ($mappings as $productId => $pageRef) {
            try {
                $updateStmt->execute([$pageRef, $productId]);
                $this->updatedProducts[] = "$productId -> $pageRef";
            } catch (PDOException $e) {
                echo "    Error updating $productId: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function showResults() {
        echo "\n=== RESULTS ===\n";
        echo "Updated " . count($this->updatedProducts) . " products with page references:\n";
        
        foreach ($this->updatedProducts as $update) {
            echo "  $update\n";
        }
        
        if (count($this->updatedProducts) > 0) {
            echo "\nVerifying PM79 specifically:\n";
            $stmt = $this->pdo->prepare('SELECT product_id, page_reference FROM catalog_products WHERE product_id = ?');
            $stmt->execute(['PM79']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo "  PM79 page_reference: " . ($result['page_reference'] ?: 'NULL') . "\n";
            }
        }
    }
}

// Run the extractor
$extractor = new PageReferenceExtractor();
$extractor->processAllIndexFiles();