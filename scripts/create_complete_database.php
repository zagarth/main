<?php
/**
 * Create Complete Product Database
 * Scans ALL sources: page_products.xml, band mapping XMLs, and image directories
 * Creates database entries for every unique product found
 */

require_once 'includes/db_config.php';

class CompleteProductDatabase {
    private $pdo;
    private $allProducts = [];
    private $imageFiles = [];
    
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
    
    public function buildCompleteDatabase() {
        echo "=== BUILDING COMPLETE PRODUCT DATABASE ===\n";
        
        // 1. Scan all image files first to get complete product list
        $this->scanAllImageFiles();
        echo "Found " . count($this->imageFiles) . " products with images\n";
        
        // 2. Load products from page_products.xml
        $this->loadPageProductsData();
        echo "Loaded page_products.xml data\n";
        
        // 3. Load band mapping data
        $this->loadAllBandMappings();
        echo "Loaded band mapping data\n";
        
        // 4. Create database entries for all unique products
        $this->createCompleteTable();
        $this->insertAllProducts();
        
        // 5. Show final statistics
        $this->showFinalStats();
    }
    
    private function scanAllImageFiles() {
        $baseDir = '/var/www/html/homesite';
        $phpDirs = glob($baseDir . '/*_php', GLOB_ONLYDIR);
        
        foreach ($phpDirs as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
                        // Skip alternate images for product counting
                        if (!preg_match('/_alt[0-9]/', $baseFilename)) {
                            $relativePath = str_replace('/var/www/html/homesite/', '', $file->getPathname());
                            
                            if (!isset($this->imageFiles[$baseFilename])) {
                                $this->imageFiles[$baseFilename] = [];
                            }
                            $this->imageFiles[$baseFilename][] = $relativePath;
                            
                            // Initialize product entry
                            if (!isset($this->allProducts[$baseFilename])) {
                                $this->allProducts[$baseFilename] = [
                                    'product_id' => $baseFilename,
                                    'product_name' => $this->generateProductName($baseFilename),
                                    'category' => $this->determineCategoryFromDirectory($dir),
                                    'subcategory' => null,
                                    'source' => 'image_scan',
                                    'has_images' => true,
                                    'image_files' => implode(',', $this->imageFiles[$baseFilename]),
                                    'has_pdf_page' => false,
                                    'page_reference' => null,
                                    'pdf_file' => null
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function loadPageProductsData() {
        $xmlFile = '/var/www/html/homesite/page_products.xml';
        if (!file_exists($xmlFile)) {
            echo "Warning: page_products.xml not found\n";
            return;
        }
        
        $xml = simplexml_load_file($xmlFile);
        if (!$xml) {
            echo "Warning: Could not parse page_products.xml\n";
            return;
        }
        
        foreach ($xml->category as $category) {
            $categoryName = (string)$category['name'];
            $subcategory = (string)$category['subcategory'];
            
            foreach ($category->product_group as $group) {
                $page = (string)$group['page'];
                $isException = in_array($page, ['plain', 'fancy', 'celtic']);
                
                foreach ($group->product as $product) {
                    $productId = (string)$product['id'];
                    
                    // Update or create product entry
                    if (!isset($this->allProducts[$productId])) {
                        $this->allProducts[$productId] = [];
                    }
                    
                    $this->allProducts[$productId] = array_merge($this->allProducts[$productId], [
                        'product_id' => $productId,
                        'product_name' => (string)$product['name'],
                        'category' => $categoryName,
                        'subcategory' => $subcategory,
                        'page_reference' => $page,
                        'has_pdf_page' => !$isException,
                        'pdf_file' => $isException ? null : 'page_' . strtolower($page) . '.pdf',
                        'white_gold_available' => isset($product['white_gold']) ? 
                            ((string)$product['white_gold'] === 'true' ? 1 : 0) : 0,
                        'gender' => isset($product['gender']) ? (string)$product['gender'] : 'unisex',
                        'source' => isset($this->allProducts[$productId]['source']) ? 
                            $this->allProducts[$productId]['source'] . ',page_products' : 'page_products'
                    ]);
                    
                    // Set image info if not already set
                    if (!isset($this->allProducts[$productId]['has_images'])) {
                        $images = $this->findImagesForProduct($productId);
                        $this->allProducts[$productId]['has_images'] = !empty($images);
                        $this->allProducts[$productId]['image_files'] = empty($images) ? 'no images found' : implode(',', $images);
                    }
                }
            }
        }
    }
    
    private function loadAllBandMappings() {
        $mappingFiles = [
            'plain' => '/var/www/html/homesite/bands_php/plain_bands_mapping.xml',
            'fancy' => '/var/www/html/homesite/bands_php/fancy_bands_mapping.xml', 
            'celtic' => '/var/www/html/homesite/bands_php/celtic_bands_mapping.xml'
        ];
        
        foreach ($mappingFiles as $type => $file) {
            if (file_exists($file)) {
                $this->loadBandMapping($type, $file);
            }
        }
    }
    
    private function loadBandMapping($type, $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) {
            echo "Warning: Could not parse $file\n";
            return;
        }
        
        switch ($type) {
            case 'plain':
                $this->loadPlainBandMapping($xml);
                break;
            case 'fancy':
                $this->loadFancyBandMapping($xml);
                break;
            case 'celtic':
                $this->loadCelticBandMapping($xml);
                break;
        }
    }
    
    private function loadPlainBandMapping($xml) {
        foreach ($xml->series as $series) {
            $profile = (string)$series['profile'];
            $thickness = (string)$series['thickness'];
            $seriesName = (string)$series['name'];
            
            foreach ($series->band as $band) {
                $width = (string)$band['width'];
                $style = (string)$band['style'];
                
                $productIdM = (string)$band->product_id_m;
                $productIdL = (string)$band->product_id_l;
                $baseId = (string)$band->base_id;
                
                foreach ([$productIdM, $productIdL] as $productId) {
                    if ($productId) {
                        if (!isset($this->allProducts[$productId])) {
                            $this->allProducts[$productId] = [
                                'product_id' => $productId,
                                'product_name' => $this->generateProductName($productId),
                                'source' => 'plain_mapping'
                            ];
                        }
                        
                        $this->allProducts[$productId] = array_merge($this->allProducts[$productId], [
                            'category' => 'plain_bands',
                            'subcategory' => 'wedding_bands',
                            'width_mm' => $this->parseWidth($width),
                            'thickness_mm' => $this->parseThickness($thickness),
                            'profile' => $this->normalizeProfile($profile),
                            'style' => $style,
                            'series' => $seriesName,
                            'base_id' => $baseId,
                            'gender_variant' => (substr($productId, -1) === 'M') ? 'M' : 'L',
                            'data_complete' => 1,
                            'needs_research' => 0,
                            'source' => isset($this->allProducts[$productId]['source']) ? 
                                $this->allProducts[$productId]['source'] . ',plain_mapping' : 'plain_mapping'
                        ]);
                        
                        // Set image info if not already set
                        if (!isset($this->allProducts[$productId]['has_images'])) {
                            $images = $this->findImagesForProduct($productId);
                            $this->allProducts[$productId]['has_images'] = !empty($images);
                            $this->allProducts[$productId]['image_files'] = empty($images) ? 'no images found' : implode(',', $images);
                        }
                    }
                }
            }
        }
    }
    
    private function loadFancyBandMapping($xml) {
        foreach ($xml->band as $band) {
            $width = (string)$band['width'];
            $diamonds = (string)$band['diamonds'];
            
            $productIdM = (string)$band->product_id_m;
            $productIdL = (string)$band->product_id_l;
            $baseId = (string)$band->base_id;
            
            $diamondCount = isset($band->diamond_count) ? (int)$band->diamond_count : 0;
            $diamondWeight = $this->parseDiamondWeight($diamonds);
            
            foreach ([$productIdM, $productIdL] as $productId) {
                if ($productId) {
                    if (!isset($this->allProducts[$productId])) {
                        $this->allProducts[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $this->generateProductName($productId),
                            'source' => 'fancy_mapping'
                        ];
                    }
                    
                    $this->allProducts[$productId] = array_merge($this->allProducts[$productId], [
                        'category' => 'fancy_bands',
                        'subcategory' => 'galaxy_bands',
                        'width_mm' => $this->parseWidth($width),
                        'style' => 'Galaxy',
                        'base_id' => $baseId,
                        'gender_variant' => (substr($productId, -1) === 'M') ? 'M' : 'L',
                        'diamond_count' => $diamondCount,
                        'diamond_weight_ct' => $diamondWeight,
                        'data_complete' => 1,
                        'needs_research' => 0,
                        'source' => isset($this->allProducts[$productId]['source']) ? 
                            $this->allProducts[$productId]['source'] . ',fancy_mapping' : 'fancy_mapping'
                    ]);
                    
                    // Set image info if not already set
                    if (!isset($this->allProducts[$productId]['has_images'])) {
                        $images = $this->findImagesForProduct($productId);
                        $this->allProducts[$productId]['has_images'] = !empty($images);
                        $this->allProducts[$productId]['image_files'] = empty($images) ? 'no images found' : implode(',', $images);
                    }
                }
            }
        }
    }
    
    private function loadCelticBandMapping($xml) {
        foreach ($xml->pattern as $pattern) {
            $patternName = (string)$pattern['name'];
            
            foreach ($pattern->band as $band) {
                $width = (string)$band['width'];
                $productId = (string)$band->product_id;
                
                if ($productId) {
                    if (!isset($this->allProducts[$productId])) {
                        $this->allProducts[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $this->generateProductName($productId),
                            'source' => 'celtic_mapping'
                        ];
                    }
                    
                    $this->allProducts[$productId] = array_merge($this->allProducts[$productId], [
                        'category' => 'celtic_bands',
                        'subcategory' => 'celtic_wedding_bands',
                        'width_mm' => $this->parseWidth($width),
                        'style' => $patternName,
                        'pattern' => $patternName,
                        'data_complete' => 1,
                        'needs_research' => 0,
                        'source' => isset($this->allProducts[$productId]['source']) ? 
                            $this->allProducts[$productId]['source'] . ',celtic_mapping' : 'celtic_mapping'
                    ]);
                    
                    // Set image info if not already set
                    if (!isset($this->allProducts[$productId]['has_images'])) {
                        $images = $this->findImagesForProduct($productId);
                        $this->allProducts[$productId]['has_images'] = !empty($images);
                        $this->allProducts[$productId]['image_files'] = empty($images) ? 'no images found' : implode(',', $images);
                    }
                }
            }
        }
    }
    
    private function findImagesForProduct($productId) {
        $matchedImages = [];
        
        // Extract base ID (remove M/L suffix if present)
        $baseId = $productId;
        if (preg_match('/^(.+)([ML])$/', $productId, $matches)) {
            $baseId = $matches[1];
        }
        
        // Case-insensitive search patterns
        $searchPatterns = [
            $productId, $baseId,
            strtolower($productId), strtolower($baseId),
            strtoupper($productId), strtoupper($baseId)
        ];
        
        foreach ($searchPatterns as $pattern) {
            if (isset($this->imageFiles[$pattern])) {
                $matchedImages = array_merge($matchedImages, $this->imageFiles[$pattern]);
            }
            
            // Also check for case-insensitive matches
            foreach ($this->imageFiles as $imageName => $paths) {
                if (strcasecmp($imageName, $pattern) === 0 && !empty(array_diff($paths, $matchedImages))) {
                    $matchedImages = array_merge($matchedImages, $paths);
                }
            }
        }
        
        return array_unique($matchedImages);
    }
    
    private function generateProductName($productId) {
        // Generate a reasonable product name based on ID patterns
        if (preg_match('/^[CT]/', $productId)) return 'Celtic Band';
        if (preg_match('/T/', $productId)) return 'Galaxy Band';
        if (preg_match('/M$/', $productId)) return 'Wedding Band';
        if (preg_match('/L$/', $productId)) return 'Wedding Band';
        if (preg_match('/^S/', $productId)) return 'Signet Ring';
        if (preg_match('/^MK/', $productId)) return 'Engagement Ring';
        if (preg_match('/^WK|^WN/', $productId)) return 'Engagement Ring';
        if (preg_match('/^\d+$/', $productId)) return 'Ring';
        return 'Jewelry Item';
    }
    
    private function determineCategoryFromDirectory($dir) {
        $dirName = basename($dir);
        $categoryMap = [
            'bands_php' => 'plain_bands',
            'accessories_php' => 'crosses',
            'signet_php' => 'signets',
            'Engagement_php' => 'engagement',
            'family_php' => 'family',
            'school_php' => 'school',
            'corp_php' => 'corporate',
            'Frontline_Workers_php' => 'professional',
            'ladys_stoneset_php' => 'ladies_jewelry'
        ];
        
        return isset($categoryMap[$dirName]) ? $categoryMap[$dirName] : 'other';
    }
    
    private function parseWidth($width) {
        if (preg_match('/(\d+\.?\d*)\s*mm/', $width, $matches)) {
            return (float)$matches[1];
        }
        return null;
    }
    
    private function parseThickness($thickness) {
        if (preg_match('/(\d+\.?\d*)\s*mm/', $thickness, $matches)) {
            return (float)$matches[1];
        }
        return null;
    }
    
    private function parseDiamondWeight($diamonds) {
        if (preg_match('/(\d+\.?\d*)\s*ct/', $diamonds, $matches)) {
            return (float)$matches[1];
        } elseif (is_numeric($diamonds) && $diamonds > 0) {
            return (float)$diamonds;
        }
        return null;
    }
    
    private function normalizeProfile($profile) {
        $profileMap = [
            'rounded_comfort_fit' => 'rounded_comfort_fit',
            'sharp_flat' => 'sharp_flat',
            'comfort_curve' => 'comfort_curve',
            'rectangular_comfort_curve' => 'rectangular_comfort_curve',
            'premium' => 'premium'
        ];
        
        return isset($profileMap[$profile]) ? $profileMap[$profile] : null;
    }
    
    private function createCompleteTable() {
        // Update the schema to include all the new categories we found
        $sql = "DROP TABLE IF EXISTS catalog_products";
        $this->pdo->exec($sql);
        
        $sql = "CREATE TABLE catalog_products (
            product_id VARCHAR(50) PRIMARY KEY,
            product_name VARCHAR(255),
            category ENUM('crosses', 'lockets', 'engagement', 'wedding', 'signets', 'gents_rings', 'bracelets', 
                         'emblematic', 'medical', 'pendants', 'mens_jewelry', 'plain_bands', 'fancy_bands', 
                         'celtic_bands', 'family', 'school', 'corporate', 'professional', 'ladies_jewelry', 'other'),
            subcategory VARCHAR(100),
            page_reference VARCHAR(10),
            base_id VARCHAR(50),
            gender_variant ENUM('M', 'L', 'unisex') DEFAULT 'unisex',
            
            -- Technical specifications
            width_mm DECIMAL(4,2) NULL,
            thickness_mm DECIMAL(4,2) NULL,
            height_mm DECIMAL(4,2) NULL,
            profile ENUM('rounded_comfort_fit', 'sharp_flat', 'comfort_curve', 'rectangular_comfort_curve', 'premium') NULL,
            style VARCHAR(100) NULL,
            pattern VARCHAR(255) NULL,
            series VARCHAR(100) NULL,
            
            -- Material and options
            material_options JSON NULL,
            white_gold_available BOOLEAN DEFAULT FALSE,
            special_notes TEXT NULL,
            size_restrictions TEXT NULL,
            
            -- Diamond specifications
            diamond_count INT DEFAULT 0,
            diamond_weight_ct DECIMAL(6,4) NULL,
            
            -- Data completeness tracking
            data_complete BOOLEAN DEFAULT FALSE,
            needs_research BOOLEAN DEFAULT TRUE,
            
            -- PDF and image integration
            has_pdf_page BOOLEAN DEFAULT FALSE,
            pdf_file VARCHAR(255) NULL,
            image_files TEXT NULL,
            has_images BOOLEAN DEFAULT FALSE,
            
            -- Source tracking
            source TEXT NULL,
            
            -- Pricing
            base_price DECIMAL(10,2) NULL,
            
            -- Metadata
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_category (category),
            INDEX idx_page (page_reference),
            INDEX idx_base_id (base_id),
            INDEX idx_gender (gender_variant),
            INDEX idx_profile (profile),
            INDEX idx_style (style),
            INDEX idx_complete (data_complete),
            INDEX idx_research (needs_research),
            INDEX idx_has_images (has_images),
            FULLTEXT idx_search (product_name, special_notes)
        )";
        
        $this->pdo->exec($sql);
        echo "Created expanded catalog_products table\n";
    }
    
    private function insertAllProducts() {
        echo "\nInserting all products into database...\n";
        
        $sql = "INSERT IGNORE INTO catalog_products (
            product_id, product_name, category, subcategory, page_reference,
            base_id, gender_variant, width_mm, thickness_mm, profile, style, pattern, series,
            white_gold_available, diamond_count, diamond_weight_ct,
            data_complete, needs_research, has_pdf_page, pdf_file, image_files, has_images, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $count = 0;
        
        foreach ($this->allProducts as $product) {
            $stmt->execute([
                $product['product_id'],
                $product['product_name'] ?? $this->generateProductName($product['product_id']),
                $product['category'] ?? 'other',
                $product['subcategory'] ?? null,
                $product['page_reference'] ?? null,
                $product['base_id'] ?? null,
                $product['gender_variant'] ?? 'unisex',
                $product['width_mm'] ?? null,
                $product['thickness_mm'] ?? null,
                $product['profile'] ?? null,
                $product['style'] ?? null,
                $product['pattern'] ?? null,
                $product['series'] ?? null,
                isset($product['white_gold_available']) ? ($product['white_gold_available'] ? 1 : 0) : 0,
                $product['diamond_count'] ?? 0,
                $product['diamond_weight_ct'] ?? null,
                isset($product['data_complete']) ? ($product['data_complete'] ? 1 : 0) : 0,
                isset($product['needs_research']) ? ($product['needs_research'] ? 1 : 0) : 1,
                isset($product['has_pdf_page']) ? ($product['has_pdf_page'] ? 1 : 0) : 0,
                $product['pdf_file'] ?? null,
                $product['image_files'] ?? 'no images found',
                isset($product['has_images']) ? ($product['has_images'] ? 1 : 0) : 0,
                $product['source'] ?? 'unknown'
            ]);
            
            $count++;
            if ($count % 100 == 0) {
                echo "Inserted $count products...\n";
            }
        }
        
        echo "Inserted $count total products\n";
    }
    
    private function showFinalStats() {
        echo "\n=== FINAL DATABASE STATISTICS ===\n";
        
        $stats = [
            "SELECT COUNT(*) as total FROM catalog_products" => "Total Products",
            "SELECT category, COUNT(*) as count FROM catalog_products GROUP BY category ORDER BY count DESC" => "By Category",
            "SELECT COUNT(*) as complete FROM catalog_products WHERE data_complete = 1" => "Complete Records",
            "SELECT COUNT(*) as with_images FROM catalog_products WHERE has_images = 1" => "With Images",
            "SELECT COUNT(*) as with_pdfs FROM catalog_products WHERE has_pdf_page = 1" => "With PDF Pages"
        ];
        
        foreach ($stats as $sql => $label) {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (strpos($sql, 'GROUP BY') !== false) {
                echo "$label:\n";
                foreach ($result as $row) {
                    echo "  " . ($row['category'] ?? 'Other') . ": " . $row['count'] . "\n";
                }
            } else {
                echo "$label: " . $result[0][array_keys($result[0])[0]] . "\n";
            }
        }
    }
}

// Execute the complete database build
$builder = new CompleteProductDatabase();
$builder->buildCompleteDatabase();

?>