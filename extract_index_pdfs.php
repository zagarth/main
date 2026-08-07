<?php
/**
 * Extract Product IDs from Index PDF Files
 * Scans all index_page_*.pdf files in Cadman_catalog directory
 * Extracts product IDs and adds them to the database
 */

require_once 'includes/db_config.php';

class IndexPDFExtractor {
    private $pdo;
    private $catalogDir = '/var/www/html/homesite/Cadman_catalog';
    private $extractedProducts = [];
    
    public function __construct() {
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=CadmanClients",
                "cadman_admin",
                "Admin2025!Cadman",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "Database connected successfully.\n";
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function extractFromAllIndexes() {
        echo "=== EXTRACTING PRODUCTS FROM INDEX PDF FILES ===\n";
        
        // Find all index_page_*.pdf files
        $indexFiles = glob($this->catalogDir . '/index_page_*.pdf');
        
        if (empty($indexFiles)) {
            die("No index PDF files found in {$this->catalogDir}\n");
        }
        
        echo "Found " . count($indexFiles) . " index PDF files\n";
        
        foreach ($indexFiles as $file) {
            $this->extractFromPDF($file);
        }
        
        $this->addProductsToDatabase();
        $this->showResults();
    }
    
    private function extractFromPDF($filePath) {
        $fileName = basename($filePath);
        echo "\nProcessing: $fileName\n";
        
        // Extract text from PDF using pdftotext
        $tempFile = '/tmp/pdf_extract_' . uniqid() . '.txt';
        $command = "pdftotext '$filePath' '$tempFile' 2>/dev/null";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($tempFile)) {
            echo "  Warning: Could not extract text from $fileName\n";
            return;
        }
        
        $text = file_get_contents($tempFile);
        unlink($tempFile);
        
        // Determine category from filename
        $category = $this->determineCategoryFromFilename($fileName);
        
        // Extract product IDs using various patterns
        $productIds = $this->extractProductIDs($text, $fileName);
        
        echo "  Found " . count($productIds) . " product IDs\n";
        
        foreach ($productIds as $productId) {
            if (!isset($this->extractedProducts[$productId])) {
                $this->extractedProducts[$productId] = [
                    'product_id' => $productId,
                    'category' => $category,
                    'source_file' => $fileName,
                    'product_name' => $this->generateProductName($productId, $category),
                    'subcategory' => $this->determineSubcategory($category, $fileName)
                ];
            }
        }
    }
    
    private function extractProductIDs($text, $fileName) {
        $productIds = [];
        
        // Common product ID patterns for jewelry
        $patterns = [
            // Standard patterns like 1000M, 200L, 5001M, etc.
            '/\b([A-Z0-9]{2,6}[ML])\b/',
            
            // Celtic/Wedding patterns like C125, 5296, etc.
            '/\b([C][0-9]{2,4}[A-Z]*)\b/',
            
            // Engagement patterns like MK56, WK61, etc.
            '/\b([MW]K[0-9]{2,4}[A-Z]*)\b/',
            
            // Galaxy/Tech patterns like 1T026, 4T18, etc.
            '/\b([0-9]+T[0-9]+[ML]*)\b/',
            
            // School ring patterns like SR119, etc.
            '/\b(SR[0-9]{2,4}[A-Z]*)\b/',
            
            // General numeric patterns 1000-9999
            '/\b([0-9]{3,4})\b/',
            
            // Special patterns based on filename
        ];
        
        // Add filename-specific patterns
        if (strpos($fileName, 'wedding') !== false) {
            $patterns[] = '/\b([0-9]{2,4}[RML]*)\b/';  // Wedding band patterns
            $patterns[] = '/\b([0-9]+T[0-9]*[ML]*)\b/'; // Tech wedding bands
        }
        
        if (strpos($fileName, 'ladies') !== false) {
            $patterns[] = '/\b([0-9]{4}[A-Z]*)\b/';     // Ladies jewelry patterns
        }
        
        if (strpos($fileName, 'mother') !== false) {
            $patterns[] = '/\b([0-9]{4}[A-Z]*)\b/';     // Mother/family patterns
        }
        
        if (strpos($fileName, 'engagement') !== false) {
            $patterns[] = '/\b([MW][A-Z][0-9]{2,4}[A-Z]*)\b/'; // Engagement patterns
        }
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $cleanId = trim($match);
                    
                    // Filter out obvious non-product IDs
                    if ($this->isValidProductID($cleanId, $fileName)) {
                        $productIds[] = $cleanId;
                    }
                }
            }
        }
        
        // Remove duplicates and sort
        $productIds = array_unique($productIds);
        sort($productIds);
        
        return $productIds;
    }
    
    private function isValidProductID($id, $fileName) {
        // Filter out common false positives
        $invalidPatterns = [
            '/^(10|14|18)$/',           // Karat values
            '/^(20[0-9]{2})$/',         // Years
            '/^(100|200|300|400|500)$/', // Round hundreds (unless in wedding context)
            '/^(PDF|PAGE|INDEX)/',      // PDF metadata
            '/^[0-9]{1,2}$/',          // Too short numbers
            '/^(MM|CM|IN)$/',          // Units
        ];
        
        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $id)) {
                // Allow some exceptions for wedding bands
                if (strpos($fileName, 'wedding') !== false && preg_match('/^[0-9]{3,4}$/', $id)) {
                    continue;
                }
                return false;
            }
        }
        
        // Must be at least 2 characters
        if (strlen($id) < 2) {
            return false;
        }
        
        return true;
    }
    
    private function determineCategoryFromFilename($fileName) {
        $categoryMap = [
            'wedding' => 'plain_bands',
            'ladies' => 'ladies_jewelry', 
            'mother' => 'family',
            'engagement' => 'engagement',
            'signets' => 'signets',
            'gents' => 'gents_rings',
            'crosses' => 'crosses',
            'lockets' => 'lockets',
            'bracelets' => 'bracelets',
            'medical' => 'medical',
            'emblematic' => 'emblematic',
            'pendants' => 'pendants',
            'mens' => 'mens_jewelry'
        ];
        
        foreach ($categoryMap as $keyword => $category) {
            if (strpos(strtolower($fileName), $keyword) !== false) {
                return $category;
            }
        }
        
        return 'other';
    }
    
    private function determineSubcategory($category, $fileName) {
        $subcategoryMap = [
            'plain_bands' => 'wedding_bands',
            'ladies_jewelry' => 'stone_set',
            'family' => 'mother_daughter',
            'engagement' => 'engagement_sets',
            'signets' => 'class_rings',
            'crosses' => '10k_crosses',
            'lockets' => '10k_lockets'
        ];
        
        return isset($subcategoryMap[$category]) ? $subcategoryMap[$category] : null;
    }
    
    private function generateProductName($productId, $category) {
        $nameMap = [
            'plain_bands' => 'Wedding Band',
            'ladies_jewelry' => 'Ladies Ring',
            'family' => 'Family Ring', 
            'engagement' => 'Engagement Ring',
            'signets' => 'Signet Ring',
            'crosses' => 'Cross',
            'lockets' => 'Locket',
            'gents_rings' => 'Gents Ring'
        ];
        
        $baseName = isset($nameMap[$category]) ? $nameMap[$category] : 'Jewelry Item';
        
        // Add gender suffix if present
        if (preg_match('/^(.+)([ML])$/', $productId, $matches)) {
            $gender = $matches[2] === 'M' ? ' (Mens)' : ' (Ladies)';
            return $baseName . $gender;
        }
        
        return $baseName;
    }
    
    private function addProductsToDatabase() {
        echo "\n=== ADDING EXTRACTED PRODUCTS TO DATABASE ===\n";
        
        $newCount = 0;
        $duplicateCount = 0;
        
        $sql = "INSERT IGNORE INTO catalog_products (
            product_id, product_name, category, subcategory, 
            has_images, image_files, has_pdf_page, data_complete, needs_research, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($this->extractedProducts as $product) {
            // Check if product already exists
            $checkSql = "SELECT product_id FROM catalog_products WHERE product_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$product['product_id']]);
            
            if ($checkStmt->fetch()) {
                $duplicateCount++;
                continue;
            }
            
            // Find images for this product
            $images = $this->findImagesForProduct($product['product_id']);
            $hasImages = !empty($images);
            $imageFiles = $hasImages ? implode(',', $images) : 'no images found';
            
            $stmt->execute([
                $product['product_id'],
                $product['product_name'],
                $product['category'],
                $product['subcategory'],
                $hasImages ? 1 : 0,
                $imageFiles,
                0, // has_pdf_page - index extracts don't have individual PDF pages
                0, // data_complete - needs further specification
                1, // needs_research
                'index_pdf_extract:' . $product['source_file']
            ]);
            
            $newCount++;
            
            if ($newCount % 100 == 0) {
                echo "Added $newCount new products...\n";
            }
        }
        
        echo "Added $newCount new products\n";
        echo "Skipped $duplicateCount existing products\n";
    }
    
    private function findImagesForProduct($productId) {
        // This is a simplified version - could be enhanced to search image directories
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $imageDirs = glob('/var/www/html/homesite/*_php', GLOB_ONLYDIR);
        $foundImages = [];
        
        foreach ($imageDirs as $dir) {
            foreach ($imageExtensions as $ext) {
                $imagePath = $dir . "/thumbs/images/**/" . $productId . "." . $ext;
                $matches = glob($imagePath, GLOB_BRACE);
                foreach ($matches as $match) {
                    $relativePath = str_replace('/var/www/html/homesite/', '', $match);
                    $foundImages[] = $relativePath;
                }
            }
        }
        
        return array_unique($foundImages);
    }
    
    private function showResults() {
        echo "\n=== EXTRACTION RESULTS ===\n";
        
        $stats = [
            "SELECT COUNT(*) as total FROM catalog_products" => "Total Products in Database",
            "SELECT COUNT(*) as from_index FROM catalog_products WHERE source LIKE '%index_pdf_extract%'" => "From Index PDFs",
            "SELECT category, COUNT(*) as count FROM catalog_products WHERE source LIKE '%index_pdf_extract%' GROUP BY category ORDER BY count DESC" => "New Products by Category"
        ];
        
        foreach ($stats as $sql => $label) {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (strpos($sql, 'GROUP BY') !== false) {
                echo "$label:\n";
                foreach ($result as $row) {
                    echo "  " . $row['category'] . ": " . $row['count'] . "\n";
                }
            } else {
                echo "$label: " . $result[0][array_keys($result[0])[0]] . "\n";
            }
        }
        
        echo "\nExtracted " . count($this->extractedProducts) . " unique product IDs from index PDFs\n";
    }
}

// Check if pdftotext is available
exec('which pdftotext', $output, $returnCode);
if ($returnCode !== 0) {
    die("Error: pdftotext not found. Please install poppler-utils:\nsudo apt-get install poppler-utils\n");
}

// Execute the extraction
$extractor = new IndexPDFExtractor();
$extractor->extractFromAllIndexes();

?>