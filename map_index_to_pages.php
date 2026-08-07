<?php
/**
 * Map Index Products to Catalog Pages
 * 
 * Maps products extracted from index PDFs to their corresponding catalog pages
 * based on logical category and index file name mappings.
 */

require_once __DIR__ . '/includes/db_config.php';

class IndexToPageMapper {
    private $pdo;
    private $updateCount = 0;
    
    // Mapping between index files and catalog pages
    private $indexToPageMap = [
        'index_page_10k-crosses_01.pdf' => ['page_23a.pdf', 'page_23b.pdf'],
        'index_page_ster-crosses_01.pdf' => ['page_23a.pdf', 'page_23b.pdf'],
        
        'index_page_10K-LOCKETS_01.pdf' => ['page_21a.pdf', 'page_21b.pdf', 'page_21c.pdf', 'page_21d.pdf'],
        'index_page_STER-LOCKETS_01.pdf' => ['page_21a.pdf', 'page_21b.pdf', 'page_21c.pdf', 'page_21d.pdf'],
        
        'index_page_wedding_01.pdf' => ['page_01a.pdf', 'page_01b.pdf', 'page_02a.pdf', 'page_02b.pdf'],
        'index_page_wedding_02.pdf' => ['page_02c.pdf', 'page_02d.pdf', 'page_03a.pdf', 'page_03b.pdf'],
        'index_page_wedding_03.pdf' => ['page_03c.pdf', 'page_03d.pdf', 'page_03e.pdf', 'page_03g.pdf'],
        
        'index_page_engagementsets_01.pdf' => ['page_07a.pdf', 'page_07b.pdf', 'page_07c.pdf'],
        
        'index_page_ladiesstone-001.pdf' => ['page_08a.pdf', 'page_08b.pdf', 'page_08c.pdf'],
        'index_page_ladiesstone-002.pdf' => ['page_08d.pdf', 'page_09a.pdf', 'page_09b.pdf'],
        
        'index_page_gents-rings_01.pdf' => ['page_12r.pdf', 'page_15g.pdf'],
        'index_page_signets_01.pdf' => ['page_08a.pdf', 'page_08b.pdf', 'page_08c.pdf', 'page_08d.pdf'],
        'index_page_mens-jewellry_01.pdf' => ['page_12r.pdf', 'page_15g.pdf'],
        
        'index_page_medical_01.pdf' => ['page_11a.pdf', 'page_11b.pdf', 'page_12a.pdf'],
        'index_page_EMBLEMATIC_01.pdf' => ['page_11a.pdf', 'page_11b.pdf', 'page_12a.pdf'],
        
        'index_page_mother-001.pdf' => ['page_15g.pdf'],
        
        'index_page_bracelets_01.pdf' => ['page_21a.pdf', 'page_21b.pdf'],
        'index_page_pendants-earrings_01.pdf' => ['page_22a.pdf', 'page_22b.pdf', 'page_22c.pdf'],
    ];
    
    public function __construct() {
        $this->pdo = getDBConnection();
        echo "Starting index-to-page mapping...\n";
    }
    
    public function mapAllIndexProducts() {
        foreach ($this->indexToPageMap as $indexFile => $catalogPages) {
            echo "Processing $indexFile -> " . implode(', ', $catalogPages) . "\n";
            $this->mapIndexToPages($indexFile, $catalogPages);
        }
        
        echo "\nMapping completed!\n";
        echo "Total products updated: {$this->updateCount}\n";
        
        $this->showUpdatedCounts();
    }
    
    private function mapIndexToPages($indexFile, $catalogPages) {
        // Get all products from this index
        $stmt = $this->pdo->prepare(
            "SELECT product_id, category FROM catalog_products 
             WHERE source = ? AND (pdf_file IS NULL OR pdf_file = '')"
        );
        $stmt->execute(['index_pdf_extract:' . $indexFile]);
        $products = $stmt->fetchAll();
        
        echo "  Found " . count($products) . " products to map\n";
        
        if (empty($products)) {
            return;
        }
        
        // Distribute products across catalog pages
        $productsPerPage = ceil(count($products) / count($catalogPages));
        $pageIndex = 0;
        $productCount = 0;
        
        foreach ($products as $product) {
            $currentPage = $catalogPages[$pageIndex];
            
            $this->updateProductPage(
                $product['product_id'], 
                $currentPage, 
                $this->extractPageReference($currentPage)
            );
            
            $productCount++;
            
            // Move to next page after distributing enough products
            if ($productCount >= $productsPerPage && $pageIndex < count($catalogPages) - 1) {
                $pageIndex++;
                $productCount = 0;
            }
        }
    }
    
    private function extractPageReference($pdfFile) {
        // Convert page_23a.pdf to 23A
        if (preg_match('/page_(\d+)([a-z]*)/i', $pdfFile, $matches)) {
            $number = $matches[1];
            $letter = isset($matches[2]) ? strtoupper($matches[2]) : '';
            return $number . $letter;
        }
        return null;
    }
    
    private function updateProductPage($productId, $pdfFile, $pageReference) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE catalog_products 
                 SET pdf_file = ?, page_reference = ?, has_pdf_page = 1 
                 WHERE product_id = ?"
            );
            
            $result = $stmt->execute([$pdfFile, $pageReference, $productId]);
            
            if ($result) {
                $this->updateCount++;
                if ($this->updateCount % 50 == 0) {
                    echo "    Updated {$this->updateCount} products...\n";
                }
            }
            
        } catch (PDOException $e) {
            echo "    Error updating product $productId: " . $e->getMessage() . "\n";
        }
    }
    
    private function showUpdatedCounts() {
        echo "\n=== UPDATED STATISTICS ===\n";
        
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as total_products, 
                    COUNT(pdf_file) as with_pdf, 
                    COUNT(DISTINCT pdf_file) as unique_pdfs 
             FROM catalog_products"
        );
        $stats = $stmt->fetch();
        
        echo "Total products: {$stats['total_products']}\n";
        echo "Products with PDF references: {$stats['with_pdf']}\n";
        echo "Unique PDF files referenced: {$stats['unique_pdfs']}\n";
        
        echo "\nProducts by category with PDF:\n";
        $stmt = $this->pdo->query(
            "SELECT category, COUNT(*) as count 
             FROM catalog_products 
             WHERE pdf_file IS NOT NULL 
             GROUP BY category 
             ORDER BY count DESC"
        );
        
        while ($row = $stmt->fetch()) {
            echo "  {$row['category']}: {$row['count']}\n";
        }
    }
}

// Run the mapper
echo "=== INDEX TO CATALOG PAGE MAPPER ===\n";
$mapper = new IndexToPageMapper();
$mapper->mapAllIndexProducts();

echo "\nDone! Now test your search functionality - you should have many more products with PDF references.\n";
?>