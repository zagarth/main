<?php
/**
 * Live Product Search API
 * Searches products directly from database without loading all records
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
    // Get search parameters
    $query = $_GET['q'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    $includeSales = $_GET['sales'] ?? 'false';
    $includeMargins = $_GET['margins'] ?? 'false';
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Search query must be at least 2 characters',
            'data' => []
        ]);
        exit;
    }
    
    // Sanitize inputs
    $query = trim($query);
    $limit = min($limit, 1000); // Cap at 1000 results
    
    // Build search query - search in product codes and names
    $searchParam = "%{$query}%";
    
    // Ensure limit is a safe integer
    $limit = max(1, min(1000, intval($limit))); // Between 1 and 1000
    
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
            c.pdf_file,
            c.image_files,
            c.page_reference,
            c.base_id
        FROM product_variants pv
        LEFT JOIN products p ON pv.product_id = p.product_id
        LEFT JOIN catalog_products c ON (pv.full_item_code LIKE CONCAT(c.product_id, '%') OR pv.full_item_code LIKE CONCAT(c.base_id, '%'))
        WHERE (
            pv.full_item_code LIKE ? OR 
            pv.description LIKE ? OR
            p.base_code LIKE ?
        )
        ORDER BY 
            CASE 
                WHEN pv.full_item_code LIKE ? THEN 1
                WHEN pv.description LIKE ? THEN 2
                ELSE 3
            END,
            pv.full_item_code ASC
        LIMIT {$limit}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $searchParam, $searchParam, $searchParam,  // WHERE conditions
        $query.'%', $query.'%'  // ORDER BY exact matches first
    ]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response data using LIVE pricing calculation (ignore stored prices)
    $results = [];
    foreach ($products as $product) {
        // Calculate total sales for sorting
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
            // Live calculated costs (not stored)
            'goldCost' => $goldCostCalculated ?? 0,
            'sterlingCost' => $sterlingCostCalculated ?? 0,
            'materialCost' => floatval($product['material_cost'] ?? 0),
            'laborCost' => $laborCostCalculated ?? 0,
            'stoneCost' => floatval($product['stone_cost'] ?? 0),
            'starCost' => floatval($product['star_cost'] ?? 0),
            'stoneSettingCost' => floatval($product['stone_setting_cost'] ?? 0)
        ];
        
        $results[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'query' => $query,
        'data' => $results
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in search_products.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => 'PDO Error Code: ' . $e->getCode(),
        'data' => []
    ]);
} catch (Exception $e) {
    error_log("Error in search_products.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Search failed: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
