<?php
/**
 * Carousel Filter Manager
 * Handles backend operations for carousel filter selection
 */

header('Content-Type: application/json');

// Simple file-based storage for carousel filter settings
$filterDataFile = __DIR__ . '/carousel_filter_data.json';

// Get the action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        echo json_encode(getCarouselFilter());
        break;
        
    case 'categories':
        $categories = getAvailableCategories();
        echo json_encode(['categories' => $categories]);
        break;
        
    case 'set':
        $collection = $_POST['collection'] ?? '';
        $filter = $_POST['filter'] ?? '';
        
        if (empty($collection) || empty($filter)) {
            http_response_code(400);
            echo json_encode(['error' => 'Collection and filter are required']);
            break;
        }
        
        $result = setCarouselFilter($collection, $filter);
        echo json_encode($result);
        break;
        
    case 'clear':
        $result = clearCarouselFilter();
        echo json_encode($result);
        break;
        
    case 'items':
        $collection = $_GET['collection'] ?? '';
        $filter = $_GET['filter'] ?? '';
        
        if (empty($collection) || empty($filter)) {
            http_response_code(400);
            echo json_encode(['error' => 'Collection and filter are required']);
            break;
        }
        
        $items = getFilteredItems($collection, $filter);
        
        // Add base_name to each item for grouping compatibility
        if (isset($items['items']) && is_array($items['items'])) {
            foreach ($items['items'] as &$item) {
                $item['base_name'] = getBaseName($item['name']);
            }
        }
        
        echo json_encode($items);
        break;
        
    case 'carousel':
        // Get current carousel configuration and return formatted items for front-end carousel
        $carouselConfig = getCarouselFilter();
        
        if (!$carouselConfig['active'] || !$carouselConfig['collection'] || !$carouselConfig['filter']) {
            // Return default hardcoded carousel items if no admin filter is set
            echo json_encode([
                'active' => false,
                'message' => 'No admin filter set, using default carousel',
                'items' => getDefaultCarouselItems()
            ]);
            break;
        }
        
        $items = getFilteredItems($carouselConfig['collection'], $carouselConfig['filter']);
        
        if (isset($items['error'])) {
            echo json_encode([
                'active' => false,
                'error' => $items['error'],
                'items' => getDefaultCarouselItems()
            ]);
            break;
        }
        
        // Convert filtered items to carousel format
        $carouselItems = convertToCarouselFormat($items['items'], $carouselConfig['collection'], $carouselConfig['filter']);
        
        echo json_encode([
            'active' => true,
            'collection' => $carouselConfig['collection'],
            'filter' => $carouselConfig['filter'],
            'timestamp' => $carouselConfig['timestamp'],
            'count' => count($carouselItems),
            'items' => $carouselItems
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Get current carousel filter settings
 */
function getCarouselFilter() {
    global $filterDataFile;
    
    if (!file_exists($filterDataFile)) {
        return [
            'collection' => null,
            'filter' => null,
            'timestamp' => null,
            'active' => false
        ];
    }
    
    $data = json_decode(file_get_contents($filterDataFile), true);
    return $data ?: [
        'collection' => null,
        'filter' => null,
        'timestamp' => null,
        'active' => false
    ];
}

/**
 * Set carousel filter
 */
function setCarouselFilter($collection, $filter) {
    global $filterDataFile;
    
    $data = [
        'collection' => $collection,
        'filter' => $filter,
        'timestamp' => date('c'),
        'active' => true
    ];
    
    // Ensure directory exists and has proper permissions
    $dir = dirname($filterDataFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $success = file_put_contents($filterDataFile, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($success !== false) {
        return [
            'success' => true,
            'message' => "Carousel filter set to {$collection} -> {$filter}",
            'data' => $data
        ];
    } else {
        // Get more detailed error information
        $error = error_get_last();
        return [
            'success' => false,
            'message' => 'Failed to save carousel filter settings',
            'error' => $error ? $error['message'] : 'Unknown file write error',
            'file_path' => $filterDataFile,
            'permissions' => is_writable(dirname($filterDataFile)) ? 'writable' : 'not writable'
        ];
    }
}

/**
 * Clear carousel filter
 */
function clearCarouselFilter() {
    global $filterDataFile;
    
    $data = [
        'collection' => null,
        'filter' => null,
        'timestamp' => date('c'),
        'active' => false
    ];
    
    $success = file_put_contents($filterDataFile, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($success !== false) {
        return [
            'success' => true,
            'message' => 'Carousel filter cleared',
            'data' => $data
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to clear carousel filter settings'
        ];
    }
}

/**
 * Get filtered items from catalog_products database
 */
function getFilteredItems($collection, $filter) {
    try {
        // Include database connection
        require_once dirname(__DIR__) . '/includes/db_config.php';
        $pdo = getDBConnection();
        
        // Map collections to database queries
        // For now, we'll treat 'collection' as the table context and 'filter' as the category
        $category = $filter; // filter is actually the category name
        
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, category, subcategory, image_files
            FROM catalog_products 
            WHERE category = ? AND has_images = 1 AND image_files IS NOT NULL
            AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
            AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
            ORDER BY product_name
            LIMIT 50
        ");
        $stmt->execute([$category]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'product_id' => $product['product_id'],
                'name' => $product['product_name'],
                'filename' => basename($product['image_files']),
                'path' => $product['image_files'],
                'relative_path' => $product['image_files'],
                'thumb_path' => $product['image_files'], // Using same path for thumbnails
                'thumb_relative_path' => $product['image_files'],
                'admin_relative_path' => '../' . $product['image_files'],
                'admin_thumb_path' => '../' . $product['image_files'],
                'category' => $product['category'],
                'subcategory' => $product['subcategory']
            ];
        }
        
        return [
            'collection' => $collection,
            'filter' => $filter,
            'category_path' => $category,
            'full_path' => 'database_query',
            'count' => count($items),
            'items' => $items,
            'source' => 'catalog_products_database'
        ];
        
    } catch (Exception $e) {
        return [
            'error' => 'Database error: ' . $e->getMessage(),
            'fallback' => true
        ];
    }
}

/**
 * Group images by base name (handle variants)
 */
function groupImagesByBaseName($items) {
    $grouped = [];
    
    foreach ($items as $item) {
        $baseName = getBaseName($item['filename']);
        
        if (!isset($grouped[$baseName])) {
            $grouped[$baseName] = [
                'base_name' => $baseName,
                'main_item' => $item,
                'variants' => []
            ];
        }
        
        // Always add to variants
        $grouped[$baseName]['variants'][] = $item;
        
        // If this is the base image (no _alt suffix), make it the main item
        $filename = pathinfo($item['filename'], PATHINFO_FILENAME);
        if ($filename === $baseName) {
            $grouped[$baseName]['main_item'] = $item;
        }
    }
    
    return array_values($grouped);
}

/**
 * Get base name from filename (remove variant suffixes)
 */
function getBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remove _alt1, _alt2, etc. suffixes first
    $name = preg_replace('/_alt\d*$/', '', $name);
    // Remove -alt1, -alt2, etc. suffixes
    $name = preg_replace('/-alt\d*$/', '', $name);
    // Remove other view suffixes
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    
    // Handle ring size suffixes - group different sizes as same design
    // RL (Ring Ladies) and RM (Ring Men's) should group together
    $name = preg_replace('/R[LM]$/', 'R', $name);
    
    // Handle simple L/M suffixes for ring sizes
    // BUT be careful not to affect names where L/M is part of the core design
    // Only remove L/M if preceded by numbers (like 5310L, 5310M)
    $name = preg_replace('/(\d+)[LM]$/', '$1', $name);
    
    return $name;
}

/**
 * Convert filtered items to carousel format
 */
function convertToCarouselFormat($items, $collection, $filter) {
    $carouselItems = [];
    
    // Group items by base name to handle variants
    $grouped = groupImagesByBaseName($items);
    
    foreach ($grouped as $group) {
        $mainItem = $group['main_item'];
        
        // Determine type based on filter or collection
        $type = determineCarouselType($collection, $filter);
        
        // Generate display name
        $displayName = generateDisplayName($mainItem['name'], $collection, $filter);
        
        // Create carousel item
        $carouselItems[] = [
            'src' => $mainItem['thumb_relative_path'] ?? str_replace('../', '', $mainItem['thumb_path']),
            'type' => $type,
            'name' => $displayName,
            'collection' => $collection,
            'filter' => $filter,
            'base_name' => $group['base_name'],
            'variants' => count($group['variants']),
            'category' => $filter // Add category explicitly for unified_detail.php
        ];
    }
    
    return $carouselItems;
}

/**
 * Determine carousel type based on collection and filter
 */
function determineCarouselType($collection, $filter) {
    // Map collections and filters to carousel types
    $typeMap = [
        'bands' => [
            'celtic' => 'celtic',
            'cultural' => 'celtic',
            'fancy' => 'regular',
            'plain' => 'regular'
        ],
        'family' => 'regular',
        'engagement' => 'regular',
        'accessories' => [
            'crosses' => 'regular',
            'idents' => 'regular',
            'pendant_earrings' => 'regular'
        ],
        'corp' => [
            'awards' => 'regular',
            'executive' => 'regular'
        ],
        'signet' => [
            'crest_top' => 'regular',
            'jewel_top' => 'regular'
        ],
        'frontline_workers' => [
            'firefighter' => 'regular',
            'clinical_services' => 'regular'
        ],
        'ladys_stoneset' => [
            'gems' => 'regular',
            'pearls' => 'regular'
        ],
        'school' => [
            'bands' => 'regular',
            'crest_tops' => 'regular',
            'shoulders' => 'regular'
        ]
    ];
    
    if (isset($typeMap[$collection])) {
        if (is_array($typeMap[$collection])) {
            return $typeMap[$collection][$filter] ?? 'regular';
        }
        return $typeMap[$collection];
    }
    
    return 'regular';
}

/**
 * Generate display name for carousel item
 */
function generateDisplayName($baseName, $collection, $filter) {
    // Create descriptive names based on collection and filter
    $filterLabels = [
        'celtic' => 'Celtic',
        'cultural' => 'Cultural',
        'fancy' => 'Designer',
        'plain' => 'Classic',
        'mother' => "Mother's",
        'father' => "Father's", 
        'daughter' => "Daughter's",
        'MK_series' => 'MK Collection',
        'MM_series' => 'MM Collection',
        'WM_series' => 'Wedding Sets',
        'solitaire' => 'Solitaire',
        'halo' => 'Halo',
        'vintage' => 'Vintage',
        'modern' => 'Modern',
        'cufflinks' => 'Cufflink',
        'tieclips' => 'Tie Clip',
        'chains' => 'Chain',
        'crosses' => 'Cross',
        'idents' => 'ID Tag',
        'pendant_earrings' => 'Pendant Earring',
        'awards' => 'Award',
        'executive' => 'Executive',
        'military' => 'Military',
        'specialty' => 'Specialty',
        'standard' => 'Standard',
        'classic' => 'Classic',
        'custom' => 'Custom',
        'crest_top' => 'Crest Top',
        'jewel_top' => 'Jewel Top',
        'firefighter' => 'Firefighter',
        'clinical_services' => 'Clinical Services',
        'gems' => 'Gemstone',
        'pearls' => 'Pearl',
        'bands' => 'School Band',
        'crest_tops' => 'School Crest',
        'shoulders' => 'School Shoulder'
    ];
    
    $filterLabel = $filterLabels[$filter] ?? ucfirst($filter);
    $collectionLabel = ucfirst($collection);
    
    return "{$filterLabel} {$collectionLabel}";
}

/**
 * Get default carousel items (fallback when no admin filter is set)
 */
function getDefaultCarouselItems() {
    return [
        // Celtic bands
        ['src' => "bands_php/thumbs/images/celtic/5310L.png", 'type' => "celtic", 'name' => "Celtic Knot Band", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5310L', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5310M.png", 'type' => "celtic", 'name' => "Celtic Design", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5310M', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5410M.png", 'type' => "celtic", 'name' => "Celtic Pattern", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5410M', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5854M.png", 'type' => "celtic", 'name' => "Celtic Twist", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5854M', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5636L_alt1.png", 'type' => "celtic", 'name' => "Celtic Band", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5636L', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5312M_alt2.png", 'type' => "celtic", 'name' => "Celtic Ring", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5312M', 'category' => 'celtic'],
        ['src' => "bands_php/thumbs/images/celtic/5424L_alt2.png", 'type' => "celtic", 'name' => "Celtic Style", 'collection' => 'bands', 'filter' => 'celtic', 'base_name' => '5424L', 'category' => 'celtic'],
        
        // Regular/Fancy bands  
        ['src' => "bands_php/thumbs/images/plain/5T18M_alt1.png", 'type' => "regular", 'name' => "Classic Band", 'collection' => 'bands', 'filter' => 'plain', 'base_name' => '5T18M', 'category' => 'plain'],
        ['src' => "bands_php/thumbs/images/plain/4T00RL_alt1.png", 'type' => "regular", 'name' => "Plain Ring", 'collection' => 'bands', 'filter' => 'plain', 'base_name' => '4T00RL', 'category' => 'plain'],
        ['src' => "bands_php/thumbs/images/plain/300RM_alt1.png", 'type' => "regular", 'name' => "Simple Band", 'collection' => 'bands', 'filter' => 'plain', 'base_name' => '300RM', 'category' => 'plain'],
        ['src' => "bands_php/thumbs/images/fancy/2291_alt1.png", 'type' => "regular", 'name' => "Fancy Design"],
        ['src' => "bands_php/thumbs/images/fancy/1T026L.png", 'type' => "regular", 'name' => "Textured Band"],
        ['src' => "bands_php/thumbs/images/fancy/5758L.png", 'type' => "regular", 'name' => "Decorative Ring"],
        ['src' => "bands_php/thumbs/images/fancy/7T62L_alt2.png", 'type' => "regular", 'name' => "Designer Band"],
        ['src' => "bands_php/thumbs/images/plain/550TL_alt2.png", 'type' => "regular", 'name' => "Traditional Ring"],
        ['src' => "bands_php/thumbs/images/fancy/5771L.png", 'type' => "regular", 'name' => "Elegant Band"],
        ['src' => "bands_php/thumbs/images/fancy/8T14L_alt1.png", 'type' => "regular", 'name' => "Modern Design"],
        
        // More Celtic
        ['src' => "bands_php/thumbs/images/celtic/5854L_alt2.png", 'type' => "celtic", 'name' => "Celtic Weave"],
        ['src' => "bands_php/thumbs/images/celtic/5636M_alt1.png", 'type' => "celtic", 'name' => "Celtic Art"],
        ['src' => "bands_php/thumbs/images/celtic/5312L.png", 'type' => "celtic", 'name' => "Celtic Heritage"],
        
        // More Regular
        ['src' => "bands_php/thumbs/images/plain/3T18M_alt1.png", 'type' => "regular", 'name' => "Classic Style"],
        ['src' => "bands_php/thumbs/images/fancy/1T028M.png", 'type' => "regular", 'name' => "Contemporary Band"],
        ['src' => "bands_php/thumbs/images/plain/400TL_alt1.png", 'type' => "regular", 'name' => "Timeless Ring"]
    ];
}

/**
 * Get available categories from database with counts and display names
 */
function getAvailableCategories() {
    try {
        require_once __DIR__ . '/../includes/db_config_encrypted.php';
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT category, COUNT(*) as item_count 
            FROM catalog_products 
            WHERE has_images = 1 AND category IS NOT NULL 
            AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
            AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
            GROUP BY category 
            ORDER BY item_count DESC, category
        ");
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Define display names for categories
        $displayNames = [
            'celtic_bands' => 'Celtic Bands',
            'plain_bands' => 'Plain Bands', 
            'fancy_bands' => 'Fancy Bands',
            'family' => 'Family Collection',
            'engagement' => 'Engagement Rings',
            'school' => 'School Collection',
            'corporate' => 'Corporate Collection',
            'professional' => 'Professional Collection',
            'crosses' => 'Crosses',
            'lockets' => 'Lockets',
            'signets' => 'Signet Rings',
            'gents_rings' => 'Gents Rings',
            'ladies_jewelry' => 'Ladies Jewelry',
            'bracelets' => 'Bracelets',
            'medical' => 'Medical Alert',
            'pendants' => 'Pendants',
            'emblematic' => 'Emblematic',
            'mens_jewelry' => 'Mens Jewelry'
        ];
        
        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'name' => $cat['category'],
                'display_name' => $displayNames[$cat['category']] ?? ucfirst(str_replace('_', ' ', $cat['category'])),
                'count' => $cat['item_count']
            ];
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Error getting categories: ' . $e->getMessage());
        return [];
    }
}
?>
