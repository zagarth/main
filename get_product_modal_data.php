<?php
// Get Product Modal Data Endpoint
// Fetches comprehensive product data for catalog detail modal

// Session must start before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_start();

header('Content-Type: application/json');

// Include security validation
require_once 'includes/URLSecurity.php';
require_once 'includes/VerboseLogger.php';
require_once __DIR__ . '/includes/catalog/ProductPricingProfile.php';
require_once __DIR__ . '/includes/catalog/PlainBandPayloadBuilder.php';

// Price visibility: only logged-in users (admin or business) see item prices
// Must be defined before site_config.php (which has a guard) to ensure session value wins
define('SHOW_PRICING',
    isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
    && isset($_SESSION['role'])
    && in_array($_SESSION['role'], ['admin', 'business'], true)
);

require_once 'includes/site_config.php';

if (SHOW_PRICING) {
    require_once __DIR__ . '/cadman-database/php/PricingCalculator.php';
}

// Start performance monitoring
$startTime = microtime(true);

// Log API access
VerboseLogger::info(VerboseLogger::CATEGORY_API, "Product modal data requested", [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
]);

// Rate limiting check
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!URLSecurity::checkRateLimit($clientIP, 200, 3600)) {
    VerboseLogger::security("Rate limit exceeded - request blocked", ['client_ip' => $clientIP]);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
    exit;
}

// Database connection
try {
    // Create database user if needed
    exec('sudo mysql -e "CREATE USER IF NOT EXISTS \'scanner\'@\'localhost\' IDENTIFIED BY \'scan123\'; GRANT ALL PRIVILEGES ON CadmanClients.* TO \'scanner\'@\'localhost\'; FLUSH PRIVILEGES;" 2>/dev/null');
    
    $host = 'localhost';
    $dbname = 'CadmanClients';  
    $username = 'scanner';
    $password = 'scan123';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get POST parameters with validation
$productId = $_POST['product_id'] ?? '';
$searchTerm = $_POST['search_term'] ?? '';

// Validate product ID
$productId = URLSecurity::validateProductId($productId);
if (!$productId) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID format']);
    exit;
}

// Validate search term
if ($searchTerm) {
    $searchTerm = URLSecurity::validateSearchTerm($searchTerm);
    if ($searchTerm === false) {
        echo json_encode(['success' => false, 'error' => 'Invalid search term']);
        exit;
    }
}

// Check for injection attempts
if (URLSecurity::containsInjection($productId) || ($searchTerm && URLSecurity::containsInjection($searchTerm))) {
    echo json_encode(['success' => false, 'error' => 'Invalid input detected']);
    exit;
}

try {
    // Fetch product data from database
    $queryStart = microtime(true);
    $stmt = $pdo->prepare("
        SELECT 
            product_id,
            product_name,
            category,
            subcategory,
            page_reference,
            base_id,
            gender_variant,
            width_mm,
            thickness_mm,
            height_mm,
            profile,
            style,
            pattern,
            series,
            material_options,
            configurator_options,
            white_gold_available,
            special_notes,
            size_restrictions,
            diamond_count,
            diamond_weight_ct,
            has_images,
            image_files,
            pdf_file,
            source,
            base_price,
            created_at,
            updated_at
        FROM catalog_products 
        WHERE product_id = ?
    ");
    $stmt->execute([$productId]);
    $queryTime = microtime(true) - $queryStart;
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log database query performance
    VerboseLogger::logQuery(
        "SELECT FROM catalog_products WHERE product_id = ?", 
        [$productId], 
        $queryTime, 
        $product ? 1 : 0
    );
    
    // If not found in catalog_products, try jewelry_items with variant matching
    if (!$product) {
        // Try exact match first
        $stmt = $pdo->prepare("
            SELECT 
                item_code as product_id,
                item_name as product_name,
                'celtic_bands' as category,
                'celtic_patterns' as subcategory,
                base_price,
                file_path as image_files,
                1 as has_images,
                description,
                NULL as configurator_options,
                0 as has_configurator,
                NULL as page_reference,
                NULL as pattern,
                NULL as style,
                NULL as width_mm,
                NULL as thickness_mm,
                NULL as height_mm,
                NULL as profile,
                NULL as series,
                'unisex' as gender_variant,
                0 as diamond_count,
                NULL as diamond_weight_ct,
                0 as white_gold_available,
                NULL as special_notes,
                NULL as size_restrictions,
                NULL as material_options
            FROM jewelry_items 
            WHERE item_code = ? AND is_active = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If still not found, try with variant suffix (e.g., 5296L might be stored as 5296L_alt1)
        // BUT exclude entries with 'copy' in the name to avoid confusion
        if (!$product) {
            $stmt = $pdo->prepare("
                SELECT 
                    item_code as product_id,
                    item_name as product_name,
                    'celtic_bands' as category,
                    'celtic_patterns' as subcategory,
                    base_price,
                    file_path as image_files,
                    1 as has_images,
                    description,
                    NULL as configurator_options,
                    0 as has_configurator,
                    NULL as page_reference,
                    NULL as pattern,
                    NULL as style,
                    NULL as width_mm,
                    NULL as thickness_mm,
                    NULL as height_mm,
                    NULL as profile,
                    NULL as series,
                    'unisex' as gender_variant,
                    0 as diamond_count,
                    NULL as diamond_weight_ct,
                    0 as white_gold_available,
                    NULL as special_notes,
                    NULL as size_restrictions,
                    NULL as material_options
                FROM jewelry_items 
                WHERE item_code LIKE ? AND item_code NOT LIKE '%copy%' AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$productId . '%']);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$product) {
            echo json_encode(['success' => false, 'error' => 'Product not found in either catalog_products or jewelry_items']);
            exit;
        }
    }

    // Normalize category so downstream checks are stable.
    $normalizedCategory = strtolower(trim((string)($product['category'] ?? '')));
    $product['category'] = $normalizedCategory;
    
    $plainPayload = null;

    // Override configurator options for specific categories using JSON files
    if ($product['category'] === 'celtic_bands') {
        $celticConfigPath = 'bands_php/celtic_configurator.json';
        $celticConfigFullPath = __DIR__ . '/bands_php/celtic_configurator.json';
        
        if (file_exists($celticConfigPath) || file_exists($celticConfigFullPath)) {
            $pathToUse = file_exists($celticConfigPath) ? $celticConfigPath : $celticConfigFullPath;
            $celticConfig = json_decode(file_get_contents($pathToUse), true);
            if ($celticConfig && isset($celticConfig['data']['options'])) {
                // Dynamically set width options based on the specific Celtic pattern
                $baseProductId = preg_replace('/[LM]$/', '', $product['product_id']);
                
                // Get other Celtic rings with the same pattern from database
                $pattern_stmt = $pdo->prepare("
                    SELECT product_id, width_mm, pattern 
                    FROM catalog_products 
                    WHERE category = 'celtic_bands' 
                    AND pattern = (
                        SELECT pattern FROM catalog_products 
                        WHERE product_id = ? 
                        LIMIT 1
                    )
                    AND pattern IS NOT NULL 
                    AND pattern != ''
                    ORDER BY width_mm DESC
                ");
                $pattern_stmt->execute([$baseProductId]);
                $pattern_siblings = $pattern_stmt->fetchAll();
                
                if (count($pattern_siblings) > 1) {
                    // Build width options from database results
                    $widthOptions = [];
                    $price_base = 0;
                    
                    foreach ($pattern_siblings as $sibling) {
                        $width_mm = floatval($sibling['width_mm']);
                        $width_str = $width_mm . 'mm';
                        
                        // Calculate price modifier (larger widths cost more)
                        $price_modifier = max(0, ($width_mm - 5.5) * 50); // $50 per 0.5mm above 5.5mm
                        
                        $widthOptions[] = [
                            'id' => $width_str,
                            'name' => $width_str . ' Wide (' . $sibling['product_id'] . ')',
                            'price_modifier' => $price_modifier,
                            'product_base' => $sibling['product_id']
                        ];
                    }
                    
                    // Update the width options in the configurator
                    $celticConfig['data']['options']['width']['options'] = $widthOptions;
                }
                
                $product['configurator_options'] = json_encode($celticConfig['data']);
                $product['has_configurator'] = 1;
            }
        }
    } else if ($product['category'] === 'plain_bands') {
        $plainPayload = PlainBandPayloadBuilder::build($pdo, $product);
        if (!empty($plainPayload['configurator_options']) && is_array($plainPayload['configurator_options'])) {
            $product['configurator_options'] = json_encode($plainPayload['configurator_options']);
            $product['has_configurator'] = 1;
        }
    } else if ($product['category'] === 'fancy_bands') {
        $fancyConfigPath = 'bands_php/fancy_configurator.json';
        if (file_exists($fancyConfigPath)) {
            $fancyConfig = json_decode(file_get_contents($fancyConfigPath), true);
            if ($fancyConfig && isset($fancyConfig['data']['options'])) {
                $product['configurator_options'] = json_encode($fancyConfig['data']);
                $product['has_configurator'] = 1;
            }
        }
    } else if ($product['category'] === 'cultural_bands') {
        $culturalConfigPath = 'bands_php/cultural_configurator.json';
        if (file_exists($culturalConfigPath)) {
            $culturalConfig = json_decode(file_get_contents($culturalConfigPath), true);
            if ($culturalConfig && isset($culturalConfig['data']['options'])) {
                $product['configurator_options'] = json_encode($culturalConfig['data']);
                $product['has_configurator'] = 1;
            }
        }
    }
    
    // Process image files
    $images = [];
    if ($product['has_images'] && !empty($product['image_files']) && $product['image_files'] !== 'no images found') {
        $imagePath = $product['image_files'];
        // Use accessories_php images as-is, only rewrite for bands
        if (strpos($imagePath, 'accessories_php/') === 0) {
            $webImagePath = $imagePath;
        } else if (strpos($imagePath, 'bands_php/images/') === 0 || strpos($imagePath, 'bands_php/thumbs/') === 0) {
            $webImagePath = $imagePath;
        } else {
            // Try to detect category for subfolder
            $category = $product['category'] ?? '';
            if ($category === 'celtic_bands') {
                $webImagePath = 'bands_php/images/celtic/' . basename($imagePath);
            } elseif ($category === 'plain_bands') {
                $webImagePath = 'bands_php/images/plain/' . basename($imagePath);
            } elseif ($category === 'fancy_bands') {
                $webImagePath = 'bands_php/images/fancy/' . basename($imagePath);
            } elseif ($category === 'cultural_bands') {
                $webImagePath = 'bands_php/images/cultural/' . basename($imagePath);
            } else {
                $webImagePath = 'bands_php/images/' . basename($imagePath);
            }
        }
        $images[] = [
            'url' => '/' . ltrim($webImagePath, '/'),
            'thumbnail' => '/' . ltrim(str_replace('images/', 'thumbs/images/', $webImagePath), '/'),
            'alt' => $product['product_name'] ?? $product['product_id']
        ];
        // Look for additional variant images (like _alt1, _alt2, etc.)
        $pathInfo = pathinfo($webImagePath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = dirname(__DIR__) . '/' . dirname($webImagePath);
        // Remove existing suffixes to get clean base name
        $cleanBaseName = preg_replace('/(_alt\d*|_view\d*|_art\d*)$/', '', $baseName);
        // Look for variant images in the same directory (server-side)
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (is_file($directory . '/' . $file)) {
                    $fileInfo = pathinfo($file);
                    if (($fileInfo['extension'] ?? '') === $extension) {
                        $fileName = $fileInfo['filename'];
                        // Check if this file is a variant of our base product
                        if (preg_match('/^' . preg_quote($cleanBaseName, '/') . '(_alt\d*|_view\d*|_art\d*)?$/', $fileName) && $fileName !== $baseName) {
                            $variantWebPath = dirname($webImagePath) . '/' . $file;
                            $images[] = [
                                'url' => '/' . ltrim($variantWebPath, '/'),
                                'thumbnail' => '/' . ltrim(str_replace('images/', 'thumbs/images/', $variantWebPath), '/'),
                                'alt' => ($product['product_name'] ?? $product['product_id']) . ' - Alternate View'
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // For Celtic products, try to find pattern group images
    if ($product['category'] === 'celtic_bands' && empty($images)) {
        $celticDir = 'bands_php/images/celtic/';
        if (is_dir($celticDir)) {
            // Extract base pattern name from product ID
            $basePattern = preg_replace('/[ML]?$/', '', $product['product_id']);
            
            // Look for images with similar pattern
            $files = scandir($celticDir);
            foreach ($files as $file) {
                if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
                    $fileBaseName = preg_replace('/(_alt.*)?\..*$/', '', $file);
                    $filePattern = preg_replace('/[ML]?$/', '', $fileBaseName);
                    
                    if ($filePattern === $basePattern || strpos($file, $basePattern) === 0) {
                        $imagePath = $celticDir . $file;
                        $images[] = [
                            'url' => $imagePath,
                            'thumbnail' => str_replace('/images/', '/thumbs/images/', $imagePath),
                            'alt' => ($product['product_name'] ?? $product['product_id']) . ' - Celtic Pattern'
                        ];
                        break; // Use first matching image
                    }
                }
            }
        }
    }
    
    // Get related products (series siblings and width alternatives)
    $relatedProducts = [];
    $widthAlternatives = [];
    $seriesSiblings = [];
    
    // Check if product is part of a series
    if (!empty($product['series'])) {
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, width_mm, series, has_images, image_files
            FROM catalog_products 
            WHERE series = ? 
            AND category = ? 
            AND product_id != ?
            ORDER BY CAST(width_mm AS DECIMAL), product_id
            LIMIT 15
        ");
        $stmt->execute([$product['series'], $product['category'], $productId]);
        $seriesSiblings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group series siblings by width for display
        $widthGroups = [];
        foreach ($seriesSiblings as $sibling) {
            $width = $sibling['width_mm'] ? $sibling['width_mm'] . 'mm' : 'Unknown width';
            if (!isset($widthGroups[$width])) {
                $widthGroups[$width] = [];
            }
            $widthGroups[$width][] = $sibling;
        }
        
        // Create width alternatives array for easy display
        foreach ($widthGroups as $width => $products) {
            $productIds = array_map(function($p) { return $p['product_id']; }, $products);
            $widthAlternatives[] = [
                'width' => $width,
                'product_ids' => $productIds,
                'count' => count($productIds)
            ];
        }
    }
    
    // Check for pattern-based siblings (like T18, T00R, T006, etc.)
    $patternSiblings = [];
    
    // Enhanced pattern matching for different band series patterns
    $patterns = [];
    
    // Match T followed by digits with optional letters (T18, T006, T00R, etc.)
    if (preg_match('/(\d+)(T\d+[A-Z]*|T[A-Z]+\d*)/', $productId, $matches)) {
        $patterns[] = $matches[2]; // T18, T006, T00R, etc.
    }
    
    // Match simpler T+digits pattern (like T006)
    if (preg_match('/(T\d+)/', $productId, $matches)) {
        $patterns[] = $matches[1]; // T006, T010, etc.
    }
    
    // For each found pattern, search for siblings
    foreach (array_unique($patterns) as $pattern) {
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, width_mm, has_images, image_files
            FROM catalog_products 
            WHERE product_id LIKE ? 
            AND category = ? 
            AND product_id != ?
            ORDER BY CAST(width_mm AS DECIMAL), product_id
            LIMIT 10
        ");
        $stmt->execute(['%' . $pattern . '%', $product['category'], $productId]);
        $patternProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge results, avoiding duplicates
        $existingIds = array_map(function($p) { return $p['product_id']; }, $patternSiblings);
        foreach ($patternProducts as $patternProduct) {
            if (!in_array($patternProduct['product_id'], $existingIds)) {
                $patternSiblings[] = $patternProduct;
            }
        }
    }
    
    // For Celtic bands, also check pattern field (legacy support)
    if ($product['category'] === 'celtic_bands' && !empty($product['pattern'])) {
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, width_mm, has_images, image_files
            FROM catalog_products 
            WHERE category = 'celtic_bands' 
            AND pattern = ? 
            AND product_id != ?
            ORDER BY width_mm, product_id
            LIMIT 10
        ");
        $stmt->execute([$product['pattern'], $productId]);
        $patternProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge with pattern siblings, avoiding duplicates
        $existingIds = array_map(function($p) { return $p['product_id']; }, $patternSiblings);
        foreach ($patternProducts as $patternProduct) {
            if (!in_array($patternProduct['product_id'], $existingIds)) {
                $patternSiblings[] = $patternProduct;
            }
        }
    }
    
    // Combine all related products
    $relatedProducts = array_merge($seriesSiblings, $patternSiblings);
    
    // Load configurator options from database
    $configuratorOptions = null;
    if (!empty($product['configurator_options'])) {
        $configuratorData = json_decode($product['configurator_options'], true);
        if ($configuratorData && isset($configuratorData['options'])) {
            // Direct options structure (from JSON override)
            $configuratorOptions = $configuratorData['options'];
        } else if ($configuratorData && isset($configuratorData['data']['options'])) {
            // Nested data.options structure (from database)
            $configuratorOptions = $configuratorData['data']['options'];
        } else if ($configuratorData && is_array($configuratorData)) {
            // Plain-band API stores the option groups directly.
            $configuratorOptions = $configuratorData;
        }
    }
    
    // Calculate price profile server-side using shared logic.
    // base_price is aligned to configurator default karat (if present), while
    // price_by_metal is always returned for accurate per-metal switching.
    $defaultKarat = '10k';
    $calculatedPrice = null;
    $priceByMetal = [];
    $quoteOnly = false;

    if ($product['category'] === 'plain_bands' && is_array($plainPayload)) {
        $defaultKarat = (string)($plainPayload['default_karat'] ?? '950_silver');
        $calculatedPrice = $plainPayload['base_price'] ?? null;
        $priceByMetal = is_array($plainPayload['price_by_metal'] ?? null) ? $plainPayload['price_by_metal'] : [];
        $quoteOnly = (bool)($plainPayload['quote_only'] ?? false);
    } else {
        if (!empty($configuratorOptions['karat_level']['default'])) {
            $defaultKarat = (string)$configuratorOptions['karat_level']['default'];
        }

        $pricingProfile = ProductPricingProfile::build($pdo, $productId, $defaultKarat, false);
        $calculatedPrice = $pricingProfile['base_price'];
        $priceByMetal = $pricingProfile['price_by_metal'];
        $quoteOnly = (SHOW_PRICING && $calculatedPrice === null);
    }

    // If the extracted configurator_options lack a karat_level key, the stored
    // JSON is stale/incomplete. Null it out so the JS synthesizer builds a proper
    // metal selector from price_by_metal instead.
    if (is_array($configuratorOptions)
        && empty($configuratorOptions['karat_level'])) {
        $configuratorOptions = null;
    }

    // Prepare response
    $response = [
        'success' => true,
        'product' => [
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'category' => $product['category'],
            'subcategory' => $product['subcategory'],
            'page_reference' => $product['page_reference'],
            'pattern' => $product['pattern'],
            'style' => $product['style'],
            'width_mm' => $product['width_mm'],
            'thickness_mm' => $product['thickness_mm'],
            'height_mm' => $product['height_mm'],
            'profile' => $product['profile'],
            'series' => $product['series'],
            'gender_variant' => $product['gender_variant'],
            'diamond_count' => $product['diamond_count'],
            'diamond_weight_ct' => $product['diamond_weight_ct'],
            'white_gold_available' => (bool)$product['white_gold_available'],
            'special_notes' => $product['special_notes'],
            'size_restrictions' => $product['size_restrictions'],
            'base_price' => $calculatedPrice,
            'price_by_metal' => $priceByMetal,
            'material_options' => $product['material_options'],
            'images' => $images,
            'related_products' => $relatedProducts,
            'width_alternatives' => $widthAlternatives,
            'series_siblings' => $seriesSiblings,
            'pattern_siblings' => $patternSiblings,
            'configurator_options' => $configuratorOptions,
            'has_configurator' => !empty($configuratorOptions),
            'quote_only' => $quoteOnly,
            'resolved_plain_series' => $plainPayload['resolved_series'] ?? null,
            'default_karat' => $defaultKarat
        ],
        'search_context' => [
            'search_term' => $searchTerm,
            'timestamp' => time()
        ],
        'site_config' => [
            'show_pricing' => SHOW_PRICING
        ]
    ];
    
    // Calculate total response time and log performance
    $totalTime = microtime(true) - $startTime;
    $responseSize = strlen(json_encode($response));
    
    VerboseLogger::logApiAccess('get_product_modal_data.php', $totalTime, $responseSize);
    
    VerboseLogger::info(VerboseLogger::CATEGORY_MODAL, "Product modal data served successfully", [
        'product_id' => $product['product_id'],
        'category' => $product['category'],
        'has_configurator' => !empty($configuratorOptions),
        'has_images' => count($images),
        'related_products_count' => count($relatedProducts),
        'response_time_ms' => round($totalTime * 1000, 2),
        'response_size_kb' => round($responseSize / 1024, 2)
    ]);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    VerboseLogger::error(VerboseLogger::CATEGORY_DATABASE, $errorMessage, [
        'product_id' => $productId,
        'error_code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    error_log('Product modal data error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    $errorMessage = 'General error: ' . $e->getMessage();
    VerboseLogger::error(VerboseLogger::CATEGORY_GENERAL, $errorMessage, [
        'product_id' => $productId,
        'error_code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    error_log('Product modal general error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred loading product data']);
}
?>