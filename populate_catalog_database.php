<?php
/**
 * Populate Catalog Products Database
 * Cross-references page_products.xml with detailed band mapping XML files
 */

require_once 'includes/db_config.php';

class CatalogPopulator {
    private $pdo;
    private $pageProductsData = [];
    private $bandMappings = [];
    
    public function __construct() {
        $this->connectDatabase();
        $this->loadPageProductsData();
        $this->loadBandMappings();
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
    
    private function loadPageProductsData() {
        $xmlFile = '/var/www/html/homesite/page_products.xml';
        if (!file_exists($xmlFile)) {
            die("Page products XML file not found: $xmlFile\n");
        }
        
        $xml = simplexml_load_file($xmlFile);
        if (!$xml) {
            die("Failed to parse page_products.xml\n");
        }
        
        // Process each category
        foreach ($xml->category as $category) {
            $categoryName = (string)$category['name'];
            $subcategory = (string)$category['subcategory'];
            
            foreach ($category->product_group as $group) {
                $page = (string)$group['page'];
                
                // Handle category word pages as exceptions
                $isException = in_array($page, ['plain', 'fancy', 'celtic']);
                
                foreach ($group->product as $product) {
                    $productId = (string)$product['id'];
                    
                    // For exceptions: only add if we don't already have this product
                    // For regular pages: add if we don't have it, or replace if existing has category word page
                    $shouldAdd = false;
                    
                    if ($isException) {
                        // Exception products: add only if not already present
                        if (!isset($this->pageProductsData[$productId])) {
                            $shouldAdd = true;
                        }
                    } else {
                        // Regular pages: add if new, or replace category word pages
                        if (!isset($this->pageProductsData[$productId]) || 
                            in_array($this->pageProductsData[$productId]['page'], ['plain', 'fancy', 'celtic'])) {
                            $shouldAdd = true;
                        }
                    }
                    
                    if ($shouldAdd) {
                        $this->pageProductsData[$productId] = [
                            'name' => (string)$product['name'],
                            'category' => $categoryName,
                            'subcategory' => $subcategory,
                            'page' => $page,
                            'is_exception' => $isException,
                            'white_gold' => isset($product['white_gold']) ? 
                                (string)$product['white_gold'] === 'true' : false,
                            'gender' => isset($product['gender']) ? (string)$product['gender'] : 'unisex',
                            'width' => isset($product['width']) ? (float)$product['width'] : null,
                            'diamonds' => isset($product['diamonds']) ? (float)$product['diamonds'] : null,
                            'count' => isset($product['count']) ? (int)$product['count'] : 0
                        ];
                    }
                }
            }
        }
        
        echo "Loaded " . count($this->pageProductsData) . " products from page_products.xml\n";
    }
    
    private function loadBandMappings() {
        $mappingFiles = [
            'plain' => '/var/www/html/homesite/bands_php/plain_bands_mapping.xml',
            'fancy' => '/var/www/html/homesite/bands_php/fancy_bands_mapping.xml',
            'celtic' => '/var/www/html/homesite/bands_php/celtic_bands_mapping.xml'
        ];
        
        foreach ($mappingFiles as $type => $file) {
            if (file_exists($file)) {
                $this->loadSpecificBandMapping($type, $file);
            }
        }
        
        echo "Loaded band mapping specifications\n";
    }
    
    private function loadSpecificBandMapping($type, $file) {
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
                
                // Process M and L variants
                $productIdM = (string)$band->product_id_m;
                $productIdL = (string)$band->product_id_l;
                $baseId = (string)$band->base_id;
                
                if ($productIdM) {
                    $this->bandMappings[$productIdM] = [
                        'type' => 'plain_bands',
                        'width_mm' => $this->parseWidth($width),
                        'thickness_mm' => $this->parseThickness($thickness),
                        'profile' => $this->normalizeProfile($profile),
                        'style' => $style,
                        'series' => $seriesName,
                        'base_id' => $baseId,
                        'gender_variant' => 'M'
                    ];
                }
                
                if ($productIdL) {
                    $this->bandMappings[$productIdL] = [
                        'type' => 'plain_bands',
                        'width_mm' => $this->parseWidth($width),
                        'thickness_mm' => $this->parseThickness($thickness),
                        'profile' => $this->normalizeProfile($profile),
                        'style' => $style,
                        'series' => $seriesName,
                        'base_id' => $baseId,
                        'gender_variant' => 'L'
                    ];
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
            
            // Check for diamond specifications
            $diamondCount = 0;
            $diamondWeight = null;
            
            if (isset($band->diamond_count)) {
                $diamondCount = (int)$band->diamond_count;
            }
            if ($diamonds && $diamonds !== "0") {
                $diamondWeight = $this->parseDiamondWeight($diamonds);
            }
            
            if ($productIdM) {
                $this->bandMappings[$productIdM] = [
                    'type' => 'fancy_bands',
                    'width_mm' => $this->parseWidth($width),
                    'style' => 'Galaxy',
                    'base_id' => $baseId,
                    'gender_variant' => 'M',
                    'diamond_count' => $diamondCount,
                    'diamond_weight_ct' => $diamondWeight
                ];
            }
            
            if ($productIdL) {
                $this->bandMappings[$productIdL] = [
                    'type' => 'fancy_bands',
                    'width_mm' => $this->parseWidth($width),
                    'style' => 'Galaxy',
                    'base_id' => $baseId,
                    'gender_variant' => 'L',
                    'diamond_count' => $diamondCount,
                    'diamond_weight_ct' => $diamondWeight
                ];
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
                    $this->bandMappings[$productId] = [
                        'type' => 'celtic_bands',
                        'width_mm' => $this->parseWidth($width),
                        'style' => $patternName,
                        'pattern' => $patternName
                    ];
                }
            }
        }
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
    
    private function generateImageFileNames($productId) {
        // Generate potential image file names based on product ID
        $baseId = $productId;
        $genderSuffix = '';
        
        // Extract gender suffix if present
        if (preg_match('/^(.+)([ML])$/', $productId, $matches)) {
            $baseId = $matches[1];
            $genderSuffix = $matches[2];
        }
        
        $imageFiles = [];
        
        // Common image file patterns
        $patterns = [
            $productId . '.jpg',
            $productId . '.png',
            $productId . '.webp',
            $baseId . '.jpg',
            $baseId . '.png',
            $baseId . '.webp'
        ];
        
        // Add variations for different angles/views
        $suffixes = ['_front', '_side', '_back', '_detail', '_thumb'];
        foreach ($suffixes as $suffix) {
            $patterns[] = $productId . $suffix . '.jpg';
            $patterns[] = $baseId . $suffix . '.jpg';
        }
        
        return implode(',', $patterns);
    }
    
    private function determineCategory($productId, $productData, $bandMapping) {
        // If we have band mapping, use that
        if ($bandMapping && isset($bandMapping['type'])) {
            return $bandMapping['type'];
        }
        
        // Use category from page_products.xml
        if (isset($productData['category'])) {
            return $productData['category'];
        }
        
        // Fallback based on product ID patterns
        if (strpos($productId, 'T') !== false) {
            return 'fancy_bands'; // Galaxy bands typically have T in them
        }
        
        return 'wedding'; // Default
    }
    
    public function populateDatabase() {
        // Create the table first
        $this->createTable();
        
        // Clear existing data
        $this->pdo->exec("DELETE FROM catalog_products");
        echo "Cleared existing catalog_products data\n";
        
        $insertedCount = 0;
        $errorCount = 0;
        
        // Process all products from page_products.xml
        foreach ($this->pageProductsData as $productId => $productData) {
            try {
                // Get additional data from band mappings if available
                $bandMapping = isset($this->bandMappings[$productId]) ? 
                    $this->bandMappings[$productId] : null;
                
                // Determine final values
                $category = $this->determineCategory($productId, $productData, $bandMapping);
                $genderVariant = $bandMapping['gender_variant'] ?? $productData['gender'] ?? 'unisex';
                
                // Handle PDF file and exception status
                $isException = $productData['is_exception'] ?? false;
                $hasPdfPage = !$isException;
                $pdfFile = $isException ? null : 'page_' . strtolower($productData['page']) . '.pdf';
                
                // Generate image file names based on product ID
                $imageFiles = $this->generateImageFileNames($productId);
                
                // Determine data completeness
                $dataComplete = $bandMapping ? 1 : 0;
                $needsResearch = $dataComplete ? 0 : 1;
                
                // Convert boolean values properly
                $whiteGoldAvailable = $productData['white_gold'] ? 1 : 0;
                
                $sql = "INSERT INTO catalog_products (
                    product_id, product_name, category, subcategory, page_reference,
                    base_id, gender_variant, width_mm, thickness_mm, profile, style, pattern,
                    white_gold_available, diamond_count, diamond_weight_ct,
                    data_complete, needs_research, has_pdf_page, pdf_file, image_files
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $productId,
                    $productData['name'],
                    $category,
                    $productData['subcategory'],
                    $productData['page'],
                    $bandMapping['base_id'] ?? null,
                    $genderVariant,
                    $bandMapping['width_mm'] ?? $productData['width'],
                    $bandMapping['thickness_mm'] ?? null,
                    $bandMapping['profile'] ?? null,
                    $bandMapping['style'] ?? null,
                    $bandMapping['pattern'] ?? null,
                    $whiteGoldAvailable,
                    $bandMapping['diamond_count'] ?? $productData['count'] ?? 0,
                    $bandMapping['diamond_weight_ct'] ?? $productData['diamonds'],
                    $dataComplete,
                    $needsResearch,
                    $hasPdfPage ? 1 : 0,  // Explicitly convert to integer
                    $pdfFile,
                    $imageFiles
                ]);
                
                $insertedCount++;
                
                if ($insertedCount % 100 == 0) {
                    echo "Inserted $insertedCount products...\n";
                }
                
            } catch (PDOException $e) {
                $errorCount++;
                echo "Error inserting product $productId: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== POPULATION COMPLETE ===\n";
        echo "Successfully inserted: $insertedCount products\n";
        echo "Errors: $errorCount\n";
        echo "Total processed: " . count($this->pageProductsData) . "\n";
        
        // Show summary statistics
        $this->showSummaryStats();
    }
    
    private function createTable() {
        $sql = file_get_contents('/var/www/html/homesite/catalog_products_schema.sql');
        
        // Extract just the CREATE TABLE statement
        if (preg_match('/CREATE TABLE catalog_products.*?;/s', $sql, $matches)) {
            try {
                $this->pdo->exec("DROP TABLE IF EXISTS catalog_products");
                $this->pdo->exec($matches[0]);
                echo "Created catalog_products table successfully\n";
            } catch (PDOException $e) {
                die("Error creating table: " . $e->getMessage() . "\n");
            }
        } else {
            die("Could not extract CREATE TABLE statement from schema file\n");
        }
    }
    
    private function showSummaryStats() {
        $stats = [
            "SELECT COUNT(*) as total FROM catalog_products" => "Total Products",
            "SELECT category, COUNT(*) as count FROM catalog_products GROUP BY category" => "By Category",
            "SELECT COUNT(*) as complete FROM catalog_products WHERE data_complete = 1" => "Complete Records",
            "SELECT COUNT(*) as needs_research FROM catalog_products WHERE needs_research = 1" => "Needs Research",
            "SELECT COUNT(*) as has_diamonds FROM catalog_products WHERE diamond_count > 0" => "With Diamonds"
        ];
        
        echo "\n=== SUMMARY STATISTICS ===\n";
        
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

// Execute the population
$populator = new CatalogPopulator();
$populator->populateDatabase();

?>