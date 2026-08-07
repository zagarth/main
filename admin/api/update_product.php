<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../cadman-database/php/PricingCalculator.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['base_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$baseCode = $input['base_code'];
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$activeVariantId = isset($input['active_variant_id']) ? (int)$input['active_variant_id'] : 0;

try {
    $pdo->beginTransaction();
    
    // Update products table (base product data)
    $sql = "UPDATE products SET ";
    $updates = [];
    $params = [];
    
    $productFields = [
        'labor_hours', 'labor_cost', 'stone_cost', 'star_cost', 
        'stone_setting_cost', 'markup_percent', 'sales_tax_percent',
        'category', 'group_code', 'info_1', 'info_2',
        'stone_min', 'stone_max', 'stone_size'
    ];
    
    foreach ($productFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }
    
    if (!empty($updates)) {
        $sql .= implode(', ', $updates);
        $sql .= " WHERE product_id = :product_id";
        $params[':product_id'] = $productId;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    // Update product_variants table (metal-specific data)
    // Get all variants for this product
    $variantSql = "SELECT variant_id, metal_type, metal_variant, gold_grams, sterling_grams, material_cost, selling_price FROM product_variants WHERE product_id = :product_id";
    $variantStmt = $pdo->prepare($variantSql);
    $variantStmt->execute([':product_id' => $productId]);
    $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update variant fields if provided
    $variantFields = [
        'material_cost', 'gold_grams', 'sterling_grams'
    ];
    
    $variantUpdates = [];
    $variantParams = [];
    
    foreach ($variantFields as $field) {
        if (isset($input[$field])) {
            $variantUpdates[] = "$field = :$field";
            $variantParams[":$field"] = $input[$field];
        }
    }
    
    if (!empty($variantUpdates)) {
        if ($activeVariantId <= 0) {
            throw new RuntimeException('Missing active variant id');
        }

        $variantFound = false;
        foreach ($variants as $variant) {
            if ((int)$variant['variant_id'] === $activeVariantId) {
                $variantFound = true;
                break;
            }
        }
        if (!$variantFound) {
            throw new RuntimeException('Active variant not found for this product');
        }

        $updateSql = "UPDATE product_variants SET " . implode(', ', $variantUpdates) . " WHERE variant_id = :variant_id AND product_id = :product_id";
        $variantParams[':variant_id'] = $activeVariantId;
        $variantParams[':product_id'] = $productId;

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute($variantParams);

        $variantStmt->execute([':product_id' => $productId]);
        $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $pricingCalc = new PricingCalculator($pdo);
    $markupPercent = isset($input['markup_percent']) ? (float)$input['markup_percent'] : 0.0;
    $laborHours = isset($input['labor_hours']) ? (float)$input['labor_hours'] : 0.0;
    $stoneCost = isset($input['stone_cost']) ? (float)$input['stone_cost'] : 0.0;
    $starCost = isset($input['star_cost']) ? (float)$input['star_cost'] : 0.0;
    $stoneSettingCost = isset($input['stone_setting_cost']) ? (float)$input['stone_setting_cost'] : 0.0;

    $recalcSql = "UPDATE product_variants
                  SET gold_cost = :gold_cost,
                      sterling_cost = :sterling_cost,
                      total_cost = :total_cost,
                      previous_price = selling_price,
                      selling_price = :selling_price,
                      cost_change_date = CURDATE(),
                      price_change_date = CURDATE()
                  WHERE variant_id = :variant_id";
    $recalcStmt = $pdo->prepare($recalcSql);

    foreach ($variants as $variant) {
        $priceResult = $pricingCalc->calculatePrice([
            'goldGrams' => (float)($variant['gold_grams'] ?? 0),
            'karat' => $variant['metal_type'] ?? '10K',
            'sterlingGrams' => (float)($variant['sterling_grams'] ?? 0),
            'laborHours' => $laborHours,
            'materialCost' => (float)($variant['material_cost'] ?? 0),
            'stoneCost' => $stoneCost,
            'starCost' => $starCost,
            'stoneSettingCost' => $stoneSettingCost
        ], $markupPercent);

        $recalcStmt->execute([
            ':gold_cost' => $priceResult['goldCost'],
            ':sterling_cost' => $priceResult['sterlingCost'],
            ':total_cost' => $priceResult['totalCost'],
            ':selling_price' => $priceResult['roundedPrice'],
            ':variant_id' => $variant['variant_id']
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'base_code' => $baseCode,
        'variants_updated' => count($variants)
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (RuntimeException $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
