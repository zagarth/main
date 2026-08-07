<?php
/**
 * Get Single Item API
 * Returns pricing and details for a specific item code
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if db_config.php exists and is readable
if (!file_exists('../../includes/db_config.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Database config file not found',
        'debug' => 'Looking for: ' . realpath('../../includes/') . '/db_config.php',
        'data' => []
    ]);
    exit;
}

require_once '../../includes/db_config.php';
require_once '../php/PricingCalculator.php';

// Check if constants are defined
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration constants not defined',
        'data' => []
    ]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Initialize pricing calculator
    $pricingCalculator = new PricingCalculator($pdo);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'debug' => 'Host: ' . DB_HOST . ', DB: ' . DB_NAME . ', User: ' . DB_USER,
        'data' => []
    ]);
    exit;
}

try {
    // Get item code parameter
    $itemCode = $_GET['item_code'] ?? ($_GET['code'] ?? '');
    
    if (empty($itemCode)) {
        echo json_encode([
            'success' => false,
            'message' => 'Item code is required',
            'data' => null
        ]);
        exit;
    }
    
    // Sanitize input
    $itemCode = trim($itemCode);
    
    $sql = "
        SELECT 
            pv.full_item_code,
            pv.description,
            pv.metal_type,
            pv.metal_variant,
            pv.metal_hi,
            pv.metal_lo,
            pv.gold_grams,
            pv.sterling_grams,
            pv.material_cost,
            pv.sales_month_1, pv.sales_month_2, pv.sales_month_3, pv.sales_month_4,
            pv.sales_month_5, pv.sales_month_6, pv.sales_month_7, pv.sales_month_8,
            pv.sales_month_9, pv.sales_month_10, pv.sales_month_11, pv.sales_month_12,
            p.labor_cost,
            p.labor_hours,
            p.stone_cost,
            p.star_cost,
            p.stone_setting_cost,
            p.markup_percent,
            p.category,
            p.group_code,
            p.stone_min,
            p.stone_max,
            p.stone_size,
            c.pdf_file,
            c.image_files,
            c.page_reference,
            c.base_id
        FROM product_variants pv
        LEFT JOIN products p ON pv.product_id = p.product_id
        LEFT JOIN catalog_products c ON (pv.full_item_code LIKE CONCAT(c.product_id, '%') OR pv.full_item_code LIKE CONCAT(c.base_id, '%'))
        WHERE pv.full_item_code = ?
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$itemCode]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Item not found',
            'data' => null
        ]);
        exit;
    }
    
    // Calculate total sales
    $salesHistory = [
            floatval($product['sales_month_1'] ?? 0), floatval($product['sales_month_2'] ?? 0),
            floatval($product['sales_month_3'] ?? 0), floatval($product['sales_month_4'] ?? 0),
            floatval($product['sales_month_5'] ?? 0), floatval($product['sales_month_6'] ?? 0),
            floatval($product['sales_month_7'] ?? 0), floatval($product['sales_month_8'] ?? 0),
            floatval($product['sales_month_9'] ?? 0), floatval($product['sales_month_10'] ?? 0),
            floatval($product['sales_month_11'] ?? 0), floatval($product['sales_month_12'] ?? 0)
        ];
        
        // LIVE price calculation using PricingCalculator
        $calculatedPrice = 0;
        $totalCost = 0;
        
        // Get material data from product_variants
        $goldGrams = floatval($product['gold_grams']);
        $sterlingGrams = floatval($product['sterling_grams']);
        
        // Clean up karat - remove TT suffix and handle variations
        $karat = $product['metal_type'] ?? '10K';
        $karat = str_replace('TT', '', $karat); // Remove TT suffix
        if (!in_array($karat, ['10K', '14K', '18K'])) {
            $karat = '10K'; // Default fallback
        }
        
        // Build cost parameters for live calculation
        $costParams = [
            'goldGrams' => $goldGrams,
            'karat' => $karat,
            'sterlingGrams' => $sterlingGrams,
            'laborHours' => floatval($product['labor_hours'] ?? 0),
            'materialCost' => floatval($product['material_cost'] ?? 0),
            'stoneCost' => floatval($product['stone_cost'] ?? 0),
            'starCost' => floatval($product['star_cost'] ?? 0),
            'stoneSettingCost' => floatval($product['stone_setting_cost'] ?? 0)
        ];
        
        // Get markup from products table, default 50%
        $markupPercent = floatval($product['markup_percent'] ?? 50);
        
        try {
            // PricingCalculator handles ALL pricing - markup AND base margin internally
            $priceResult = $pricingCalculator->calculatePrice($costParams, $markupPercent);
            $calculatedPrice = $priceResult['roundedPrice'];
            $totalCost = $priceResult['totalCost'];
            $goldCostCalculated = $priceResult['goldCost'];
            $sterlingCostCalculated = $priceResult['sterlingCost'];
            $laborCostCalculated = $priceResult['laborCost'];
        } catch (Exception $e) {
            // Simple fallback calculation - just cost + markup (no double margin)
            $totalCost = floatval($product['labor_cost'] ?? 0) + 
                        floatval($product['stone_cost'] ?? 0) + 
                        floatval($product['star_cost'] ?? 0) + 
                        floatval($product['stone_setting_cost'] ?? 0);
            $calculatedPrice = $totalCost * (1 + $markupPercent / 100);
            $goldCostCalculated = 0;
            $sterlingCostCalculated = 0;
            $laborCostCalculated = floatval($product['labor_cost'] ?? 0);
        }
        
        $item = [
            'itemCode' => $product['full_item_code'],
            'description' => $product['description'] ?: 'No description',
            'price' => $calculatedPrice, // CALCULATED LIVE not stored
            'cost' => $includeMargins === 'true' ? $totalCost : null,
            'category' => $product['category'],
            'group' => $product['group_code'],
            'metalHi' => $product['metal_hi'],
            'metalLo' => $product['metal_lo'],
            'goldGrams' => floatval($product['gold_grams']),
            'sterlingGrams' => floatval($product['sterling_grams']),
            'laborHours' => floatval($product['labor_hours'] ?? 0),
            'baseCode' => $product['base_id'],
            'pdfFile' => $product['pdf_file'],
            'imageFiles' => $product['image_files'],
            'pageReference' => $product['page_reference'],
            'salesHistory' => $salesHistory,
            'totalSales' => array_sum($salesHistory),
            'metalType' => $product['metal_type'],
            'metalVariant' => $product['metal_variant'],
            'markupPercent' => $markupPercent,
            'stoneCost' => floatval($product['stone_cost'] ?? 0),
            'starCost' => floatval($product['star_cost'] ?? 0),
            'stoneSettingCost' => floatval($product['stone_setting_cost'] ?? 0),
            'materialCost' => floatval($product['material_cost'] ?? 0),

            // New stone fields
            'stoneMin' => isset($product['stone_min']) ? floatval($product['stone_min']) : null,
            'stoneMax' => isset($product['stone_max']) ? floatval($product['stone_max']) : null,
            'stoneSize' => isset($product['stone_size']) ? floatval($product['stone_size']) : null,

            // Duplicate fields (snake_case) for order entry compatibility
            'full_item_code' => $product['full_item_code'],
            'variant_description' => $product['description'] ?: 'No description',
            'selling_price' => $calculatedPrice,
            'gold_grams' => floatval($product['gold_grams']),
            'sterling_grams' => floatval($product['sterling_grams']),
            'labor_hours' => floatval($product['labor_hours'] ?? 0),
            'metal_hi' => $product['metal_hi'],
            'metal_type' => $product['metal_type'],
            'markup_percent' => $markupPercent,
            'stone_cost' => floatval($product['stone_cost'] ?? 0),
            'star_cost' => floatval($product['star_cost'] ?? 0),
            'stone_setting_cost' => floatval($product['stone_setting_cost'] ?? 0),
            'material_cost' => floatval($product['material_cost'] ?? 0),
            'stone_min' => isset($product['stone_min']) ? floatval($product['stone_min']) : null,
            'stone_max' => isset($product['stone_max']) ? floatval($product['stone_max']) : null,
            'stone_size' => isset($product['stone_size']) ? floatval($product['stone_size']) : null
        ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Item found',
        'data' => $item
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving item: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
