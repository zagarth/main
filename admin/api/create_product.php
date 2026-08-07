<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db_config.php';

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

if (!$input || !isset($input['base_code']) || !isset($input['variants']) || empty($input['variants'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input - base_code and at least one variant required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if base_code already exists
    $checkSql = "SELECT COUNT(*) FROM products WHERE base_code = :base_code";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':base_code' => $input['base_code']]);
    
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Product with base code '{$input['base_code']}' already exists");
    }
    
    // Insert into products table
    $productSql = "
        INSERT INTO products (
            base_code, description, labor_cost, labor_hours,
            stone_cost, star_cost, stone_setting_cost,
            markup_percent, sales_tax_percent,
            category, group_code, info_1, info_2,
            stone_min, stone_max, stone_size
        ) VALUES (
            :base_code, :description, :labor_cost, :labor_hours,
            :stone_cost, :star_cost, :stone_setting_cost,
            :markup_percent, :sales_tax_percent,
            :category, :group_code, :info_1, :info_2,
            :stone_min, :stone_max, :stone_size
        )
    ";

    $productStmt = $pdo->prepare($productSql);
    $productStmt->execute([
        ':base_code' => $input['base_code'],
        ':description' => $input['description'],
        ':labor_cost' => $input['labor_cost'] ?? 0,
        ':labor_hours' => $input['labor_hours'] ?? 0,
        ':stone_cost' => $input['stone_cost'] ?? 0,
        ':star_cost' => $input['star_cost'] ?? 0,
        ':stone_setting_cost' => $input['stone_setting_cost'] ?? 0,
        ':markup_percent' => $input['markup_percent'] ?? 0,
        ':sales_tax_percent' => $input['sales_tax_percent'] ?? 0,
        ':category' => $input['category'] ?? '',
        ':group_code' => $input['group_code'] ?? '',
        ':info_1' => $input['info_1'] ?? '',
        ':info_2' => $input['info_2'] ?? '',
        ':stone_min' => $input['stone_min'] ?? null,
        ':stone_max' => $input['stone_max'] ?? null,
        ':stone_size' => $input['stone_size'] ?? null
    ]);
    
    $productId = $pdo->lastInsertId();
    
    // System settings for calculations
    $goldPrice = 7300.00;
    $sterlingGF = 130.00;
    $baseMargin = 8.0;
    
    $variantIds = [];
    
    // Insert variants
    foreach ($input['variants'] as $variant) {
        $metalType = $variant['metal_type'];
        $metalVariant = $variant['metal_variant'] ?? null;
        $materialCost = $variant['material_cost'] ?? 0;
        $goldGrams = $variant['gold_grams'] ?? 0;
        $sterlingGrams = $variant['sterling_grams'] ?? 0;
        
        // Calculate costs
        $goldCost = ($goldGrams * $goldPrice) / 31.1035;
        $sterlingCost = $sterlingGrams * $sterlingGF;
        
        $totalCost = 
            ($input['labor_cost'] ?? 0) +
            $materialCost +
            $goldCost +
            $sterlingCost +
            ($input['stone_cost'] ?? 0) +
            ($input['star_cost'] ?? 0) +
            ($input['stone_setting_cost'] ?? 0);
        
        // Calculate selling price
        $markup = $input['markup_percent'] ?? 0;
        $sellingPrice = $totalCost * (1 + ($markup / 100) + ($baseMargin / 100));
        
        // Determine metal_hi and metal_lo
        $metalHi = $metalType;
        $metalLo = $metalVariant;
        
        // Build full item code
        $fullItemCode = $input['base_code'] . '/' . $metalType;
        if ($metalVariant) {
            $fullItemCode .= $metalVariant;
        }
        
        $variantSql = "
            INSERT INTO product_variants (
                product_id, full_item_code, description,
                metal_type, metal_variant, metal_hi, metal_lo,
                material_cost, gold_grams, gold_cost,
                sterling_grams, sterling_cost,
                total_cost, selling_price, previous_price,
                price_change_date
            ) VALUES (
                :product_id, :full_item_code, :description,
                :metal_type, :metal_variant, :metal_hi, :metal_lo,
                :material_cost, :gold_grams, :gold_cost,
                :sterling_grams, :sterling_cost,
                :total_cost, :selling_price, :selling_price,
                CURDATE()
            )
        ";
        
        $variantStmt = $pdo->prepare($variantSql);
        $variantStmt->execute([
            ':product_id' => $productId,
            ':full_item_code' => $fullItemCode,
            ':description' => $input['description'],
            ':metal_type' => $metalType,
            ':metal_variant' => $metalVariant,
            ':metal_hi' => $metalHi,
            ':metal_lo' => $metalLo,
            ':material_cost' => $materialCost,
            ':gold_grams' => $goldGrams,
            ':gold_cost' => $goldCost,
            ':sterling_grams' => $sterlingGrams,
            ':sterling_cost' => $sterlingCost,
            ':total_cost' => $totalCost,
            ':selling_price' => $sellingPrice
        ]);
        
        $variantIds[] = [
            'variant_id' => $pdo->lastInsertId(),
            'full_item_code' => $fullItemCode,
            'selling_price' => $sellingPrice
        ];
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product created successfully',
        'product_id' => $productId,
        'base_code' => $input['base_code'],
        'variants' => $variantIds
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
