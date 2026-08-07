<?php
/**
 * Product Data API - AR12 Pricing Calculator
 * Returns product and variant data from database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../php/PricingCalculator.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$pricingCalculator = new PricingCalculator($pdo);

// Get query parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$itemCode = $_GET['item_code'] ?? '';
$limit = min(intval($_GET['limit'] ?? 20000), 50000); // Max 50 50000 records

// Build query
$sql = "
    SELECT 
        p.product_id,
        p.base_code,
        p.description as base_description,
        p.labor_cost,
        p.labor_hours,
        p.stone_cost,
        p.star_cost,
        p.stone_setting_cost,
        p.markup_percent,
        p.sales_tax_percent,
        p.category,
        p.group_code,
        p.info_1,
        p.info_2,
        p.stone_min,
        p.stone_max,
        p.stone_size,
        pv.variant_id,
        pv.full_item_code,
        pv.description as variant_description,
        pv.metal_type,
        pv.metal_variant,
        pv.metal_hi,
        pv.metal_lo,
        pv.gold_grams,
        pv.gold_cost,
        pv.sterling_grams,
        pv.sterling_cost,
        pv.material_cost,
        pv.total_cost,
        pv.selling_price,
        pv.previous_price,
        pv.price_change_date,
        pv.cost_change_date,
        pv.sales_month_1,
        pv.sales_month_2,
        pv.sales_month_3,
        pv.sales_month_4,
        pv.sales_month_5,
        pv.sales_month_6,
        pv.sales_month_7,
        pv.sales_month_8,
        pv.sales_month_9,
        pv.sales_month_10,
        pv.sales_month_11,
        pv.sales_month_12,
        cp.pdf_file,
        cp.image_files,
        cp.page_reference
    FROM products p
    JOIN product_variants pv ON p.product_id = pv.product_id
    LEFT JOIN (
        SELECT cp.*
        FROM catalog_products cp
        INNER JOIN (
            SELECT 
                CASE
                    WHEN base_id IS NOT NULL THEN base_id
                    ELSE product_id
                END as match_code,
                MIN(CASE
                    WHEN base_id IS NOT NULL THEN 1
                    WHEN product_id IS NOT NULL THEN 2
                    ELSE 3
                END) as priority
            FROM catalog_products
            GROUP BY match_code
        ) ranked ON (
            (cp.base_id = ranked.match_code OR cp.product_id = ranked.match_code)
            AND CASE
                WHEN cp.base_id IS NOT NULL THEN 1
                WHEN cp.product_id IS NOT NULL THEN 2
                ELSE 3
            END = ranked.priority
        )
    ) cp ON (p.base_code = cp.base_id OR p.base_code = cp.product_id)
    WHERE 1=1
";

$params = [];

// Filter by specific item code
if (!empty($itemCode)) {
    $sql .= " AND pv.full_item_code = :item_code";
    $params[':item_code'] = $itemCode;
}
// Filter by search term (searches both descriptions and codes)
elseif (!empty($search)) {
    $sql .= " AND (
        pv.full_item_code LIKE :search 
        OR p.base_code LIKE :search
        OR pv.description LIKE :search
        OR p.description LIKE :search
    )";
    $params[':search'] = "%$search%";
}

// Filter by category
if (!empty($category)) {
    $sql .= " AND p.category = :category";
    $params[':category'] = $category;
}

$sql .= " ORDER BY p.base_code, pv.metal_type, pv.metal_variant LIMIT :limit";

$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert numeric strings to numbers for JSON
foreach ($results as &$row) {
    $row['product_id'] = intval($row['product_id']);
    $row['variant_id'] = intval($row['variant_id']);
    $row['labor_cost'] = floatval($row['labor_cost']);
    $row['labor_hours'] = floatval($row['labor_hours']);
    $row['stone_cost'] = floatval($row['stone_cost']);
    $row['star_cost'] = floatval($row['star_cost']);
    $row['stone_setting_cost'] = floatval($row['stone_setting_cost']);
    $row['markup_percent'] = floatval($row['markup_percent']);
    $row['sales_tax_percent'] = floatval($row['sales_tax_percent']);
    $row['gold_grams'] = floatval($row['gold_grams']);
    $row['gold_cost'] = floatval($row['gold_cost']);
    $row['sterling_grams'] = floatval($row['sterling_grams']);
    $row['sterling_cost'] = floatval($row['sterling_cost']);
    $row['material_cost'] = floatval($row['material_cost']);
    $row['total_cost'] = floatval($row['total_cost']);
    $row['selling_price'] = floatval($row['selling_price']);
    $row['previous_price'] = floatval($row['previous_price']);

    // Stone fields (can be null)
    $row['stone_min'] = isset($row['stone_min']) ? floatval($row['stone_min']) : null;
    $row['stone_max'] = isset($row['stone_max']) ? floatval($row['stone_max']) : null;
    $row['stone_size'] = isset($row['stone_size']) ? floatval($row['stone_size']) : null;

    // Sales data
    for ($i = 1; $i <= 12; $i++) {
        $key = "sales_month_$i";
        $row[$key] = intval($row[$key]);
    }

    // Use the shared pricing calculator so dropdown prices stay aligned with the selected item.
    try {
        $karat = $row['metal_type'] ?? '10K';
        $karat = str_replace('TT', '', $karat);
        if (!in_array($karat, ['10K', '14K', '18K', '24K'])) {
            $karat = '10K';
        }

        $costParams = [
            'goldGrams' => floatval($row['gold_grams'] ?? 0),
            'karat' => $karat,
            'sterlingGrams' => floatval($row['sterling_grams'] ?? 0),
            'laborHours' => floatval($row['labor_hours'] ?? 0),
            'materialCost' => floatval($row['material_cost'] ?? 0),
            'stoneCost' => floatval($row['stone_cost'] ?? 0),
            'starCost' => floatval($row['star_cost'] ?? 0),
            'stoneSettingCost' => floatval($row['stone_setting_cost'] ?? 0)
        ];

        $markupPercent = floatval($row['markup_percent'] ?? 50);
        $priceResult = $pricingCalculator->calculatePrice($costParams, $markupPercent);
        $calculatedPrice = $priceResult['roundedPrice'];

        $row['price'] = $calculatedPrice;
        $row['calculated_price'] = $calculatedPrice;
        $row['selling_price'] = $calculatedPrice;
        $row['live_price'] = $calculatedPrice;
    } catch (Exception $e) {
        $row['price'] = $row['selling_price'];
        $row['calculated_price'] = $row['selling_price'];
        $row['live_price'] = $row['selling_price'];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($results),
    'data' => $results
], JSON_PRETTY_PRINT);
