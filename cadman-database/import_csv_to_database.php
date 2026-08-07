<?php
/**
 * Import IP-EXP01.csv into Products and Product Variants Tables
 * 
 * This script:
 * 1. Parses IP-EXP01.csv (15,233 records)
 * 2. Extracts base codes from full item codes (e.g., "1000L/10K" → "1000L")
 * 3. Inserts deduplicated base products into `products` table
 * 4. Inserts all metal variants into `product_variants` table
 * 5. Calculates labor hours from labor_cost / labor_rate
 * 6. Links variants to base products via foreign key
 */

require_once '../includes/db_config.php';

// Configuration
$csvFile = 'IP-EXP01.csv';
$laborRate = 28.00; // From SY file settings

// Statistics
$stats = [
    'csv_lines' => 0,
    'products_inserted' => 0,
    'products_skipped' => 0,
    'variants_inserted' => 0,
    'variants_failed' => 0,
    'errors' => []
];

// Track base products we've already inserted (avoid duplicates)
$insertedBaseProducts = [];

echo "================================================================================\n";
echo "CSV to Database Import - AR12 Pricing Data\n";
echo "================================================================================\n\n";

// Check if CSV file exists
if (!file_exists($csvFile)) {
    die("ERROR: CSV file not found: $csvFile\n");
}

echo "Reading CSV file: $csvFile\n";
$csvData = file_get_contents($csvFile);
$lines = explode("\n", trim($csvData));
$stats['csv_lines'] = count($lines);
echo "Found {$stats['csv_lines']} lines in CSV\n\n";

// Get database connection
try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    echo "Database connection established\n";
    echo "Transaction started\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Prepare INSERT statements
$stmtProduct = $pdo->prepare("
    INSERT INTO products (
        base_code, description, labor_cost, labor_hours, stone_cost, 
        star_cost, stone_setting_cost, markup_percent, sales_tax_percent,
        category, group_code, info_1, info_2
    ) VALUES (
        :base_code, :description, :labor_cost, :labor_hours, :stone_cost,
        :star_cost, :stone_setting_cost, :markup_percent, :sales_tax_percent,
        :category, :group_code, :info_1, :info_2
    )
");

$stmtVariant = $pdo->prepare("
    INSERT INTO product_variants (
        product_id, full_item_code, description, metal_type, metal_variant,
        metal_hi, metal_lo, gold_grams, gold_cost, sterling_grams, sterling_cost,
        material_cost, total_cost, selling_price, previous_price,
        price_change_date, cost_change_date,
        sales_month_1, sales_month_2, sales_month_3, sales_month_4, sales_month_5, sales_month_6,
        sales_month_7, sales_month_8, sales_month_9, sales_month_10, sales_month_11, sales_month_12
    ) VALUES (
        :product_id, :full_item_code, :description, :metal_type, :metal_variant,
        :metal_hi, :metal_lo, :gold_grams, :gold_cost, :sterling_grams, :sterling_cost,
        :material_cost, :total_cost, :selling_price, :previous_price,
        :price_change_date, :cost_change_date,
        :sales_month_1, :sales_month_2, :sales_month_3, :sales_month_4, :sales_month_5, :sales_month_6,
        :sales_month_7, :sales_month_8, :sales_month_9, :sales_month_10, :sales_month_11, :sales_month_12
    )
");

echo "Processing CSV records...\n\n";

// Process each line
foreach ($lines as $lineNum => $line) {
    if (empty(trim($line))) continue;
    
    // Parse CSV by pipe delimiter
    $fields = explode('|', $line);
    
    if (count($fields) < 37) {
        $stats['errors'][] = "Line " . ($lineNum + 1) . ": Insufficient fields (" . count($fields) . ")";
        continue;
    }
    
    // Parse item code to extract base code and metal variant
    $fullItemCode = trim($fields[0]);
    $parsed = parseItemCode($fullItemCode);
    $baseCode = $parsed['base_code'];
    $metalType = $parsed['metal_type'];
    $metalVariant = $parsed['metal_variant'];
    
    if (empty($baseCode)) {
        $stats['errors'][] = "Line " . ($lineNum + 1) . ": Could not parse item code: $fullItemCode";
        continue;
    }
    
    // =========================================================================
    // INSERT BASE PRODUCT (if not already inserted)
    // =========================================================================
    if (!isset($insertedBaseProducts[$baseCode])) {
        try {
            $description = trim($fields[1]);
            $laborCost = floatval($fields[5]);
            $laborHours = $laborCost > 0 ? ($laborCost / $laborRate) : 0;
            
            $stmtProduct->execute([
                ':base_code' => $baseCode,
                ':description' => $description,
                ':labor_cost' => $laborCost,
                ':labor_hours' => round($laborHours, 3),
                ':stone_cost' => floatval($fields[12]),
                ':star_cost' => floatval($fields[13]),
                ':stone_setting_cost' => floatval($fields[14]),
                ':markup_percent' => floatval($fields[27]),
                ':sales_tax_percent' => floatval($fields[28]),
                ':category' => trim($fields[31]) ?: null,
                ':group_code' => trim($fields[32]) ?: null,
                ':info_1' => trim($fields[29]) ?: null,
                ':info_2' => trim($fields[30]) ?: null
            ]);
            
            $productId = $pdo->lastInsertId();
            $insertedBaseProducts[$baseCode] = $productId;
            $stats['products_inserted']++;
            
            if ($stats['products_inserted'] % 100 == 0) {
                echo "Inserted {$stats['products_inserted']} base products...\n";
            }
        } catch (PDOException $e) {
            // Likely duplicate - try to get existing product_id
            $stmt = $pdo->prepare("SELECT product_id FROM products WHERE base_code = :base_code");
            $stmt->execute([':base_code' => $baseCode]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $insertedBaseProducts[$baseCode] = $existing['product_id'];
                $stats['products_skipped']++;
            } else {
                $stats['errors'][] = "Base product insert failed for '$baseCode': " . $e->getMessage();
                continue;
            }
        }
    }
    
    // Get product_id for this base code
    $productId = $insertedBaseProducts[$baseCode];
    
    // =========================================================================
    // INSERT VARIANT
    // =========================================================================
    try {
        // Parse dates from YYYYMMDD format
        $priceChangeDate = parseDate(trim($fields[35]));
        $costChangeDate = parseDate(trim($fields[36]));
        
        $stmtVariant->execute([
            ':product_id' => $productId,
            ':full_item_code' => $fullItemCode,
            ':description' => trim($fields[1]),
            ':metal_type' => $metalType,
            ':metal_variant' => $metalVariant ?: null,
            ':metal_hi' => trim($fields[6]) ?: null,
            ':metal_lo' => trim($fields[7]) ?: null,
            ':gold_grams' => floatval($fields[8]),
            ':gold_cost' => floatval($fields[9]),
            ':sterling_grams' => floatval($fields[10]),
            ':sterling_cost' => floatval($fields[11]),
            ':material_cost' => floatval($fields[4]),
            ':total_cost' => floatval($fields[3]),
            ':selling_price' => floatval($fields[2]),
            ':previous_price' => floatval($fields[34]),
            ':price_change_date' => $priceChangeDate,
            ':cost_change_date' => $costChangeDate,
            ':sales_month_1' => intval($fields[15]),
            ':sales_month_2' => intval($fields[16]),
            ':sales_month_3' => intval($fields[17]),
            ':sales_month_4' => intval($fields[18]),
            ':sales_month_5' => intval($fields[19]),
            ':sales_month_6' => intval($fields[20]),
            ':sales_month_7' => intval($fields[21]),
            ':sales_month_8' => intval($fields[22]),
            ':sales_month_9' => intval($fields[23]),
            ':sales_month_10' => intval($fields[24]),
            ':sales_month_11' => intval($fields[25]),
            ':sales_month_12' => intval($fields[26])
        ]);
        
        $stats['variants_inserted']++;
        
        if ($stats['variants_inserted'] % 500 == 0) {
            echo "Inserted {$stats['variants_inserted']} variants...\n";
        }
        
    } catch (PDOException $e) {
        $stats['variants_failed']++;
        $stats['errors'][] = "Variant insert failed for '$fullItemCode': " . $e->getMessage();
    }
}

// Commit transaction
try {
    $pdo->commit();
    echo "\n✓ Transaction committed successfully\n\n";
} catch (Exception $e) {
    $pdo->rollBack();
    die("\n✗ Transaction rollback: " . $e->getMessage() . "\n");
}

// ============================================================================
// REPORT STATISTICS
// ============================================================================
echo "================================================================================\n";
echo "Import Complete\n";
echo "================================================================================\n\n";
echo "CSV Records Processed:    {$stats['csv_lines']}\n";
echo "Base Products Inserted:   {$stats['products_inserted']}\n";
echo "Base Products Skipped:    {$stats['products_skipped']}\n";
echo "Variants Inserted:        {$stats['variants_inserted']}\n";
echo "Variants Failed:          {$stats['variants_failed']}\n";
echo "Errors:                   " . count($stats['errors']) . "\n\n";

if (count($stats['errors']) > 0 && count($stats['errors']) <= 20) {
    echo "Errors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
    echo "\n";
} elseif (count($stats['errors']) > 20) {
    echo "First 20 errors:\n";
    for ($i = 0; $i < 20; $i++) {
        echo "  - {$stats['errors'][$i]}\n";
    }
    echo "  ... and " . (count($stats['errors']) - 20) . " more\n\n";
}

// Verify counts in database
echo "Database Verification:\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch();
echo "  Products in DB:        {$result['count']}\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM product_variants")->fetch();
echo "  Variants in DB:        {$result['count']}\n\n";

// Sample join query to test integration
echo "================================================================================\n";
echo "Testing Join with catalog_products\n";
echo "================================================================================\n\n";

$testQuery = "
    SELECT 
        p.base_code,
        p.description,
        COUNT(pv.variant_id) as variant_count,
        cp.pdf_file,
        cp.page_reference
    FROM products p
    LEFT JOIN product_variants pv ON pv.product_id = p.product_id
    LEFT JOIN catalog_products cp ON p.base_code = cp.product_id
    WHERE cp.pdf_file IS NOT NULL
    GROUP BY p.product_id
    LIMIT 10
";

$results = $pdo->query($testQuery)->fetchAll();

if (count($results) > 0) {
    echo "✓ Successfully joined pricing data with catalog:\n\n";
    foreach ($results as $row) {
        echo "  {$row['base_code']}: {$row['variant_count']} variants → {$row['pdf_file']}\n";
    }
} else {
    echo "⚠ No matching records found in catalog_products\n";
}

echo "\n================================================================================\n";
echo "Import script completed\n";
echo "================================================================================\n";

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Parse item code to extract base code, metal type, and variant
 * Examples:
 *   "1000L/10K" → base: "1000L", metal: "10K", variant: ""
 *   "1000L/10KW" → base: "1000L", metal: "10K", variant: "W"
 *   "050DT/10KB" → base: "050DT", metal: "10K", variant: "B"
 */
function parseItemCode($fullCode) {
    $parts = explode('/', $fullCode);
    $baseCode = trim($parts[0]);
    
    if (!isset($parts[1])) {
        // No slash - entire code is base
        return [
            'base_code' => $baseCode,
            'metal_type' => '',
            'metal_variant' => ''
        ];
    }
    
    $metalPart = trim($parts[1]);
    
    // Extract variant suffix (B=bulk, W=white, Y=yellow)
    $metalVariant = '';
    if (preg_match('/([BWY])$/', $metalPart, $matches)) {
        $metalVariant = $matches[1];
        $metalType = substr($metalPart, 0, -1);
    } else {
        $metalType = $metalPart;
    }
    
    return [
        'base_code' => $baseCode,
        'metal_type' => $metalType,
        'metal_variant' => $metalVariant
    ];
}

/**
 * Parse date from YYYYMMDD format to MySQL DATE format (YYYY-MM-DD)
 * Returns null if date is invalid or empty
 */
function parseDate($dateStr) {
    $dateStr = trim($dateStr);
    
    // Check for empty or all zeros
    if (empty($dateStr) || $dateStr === '00000000') {
        return null;
    }
    
    // Parse YYYYMMDD
    if (strlen($dateStr) === 8 && ctype_digit($dateStr)) {
        $year = substr($dateStr, 0, 4);
        $month = substr($dateStr, 4, 2);
        $day = substr($dateStr, 6, 2);
        
        // Validate date
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }
    
    return null;
}
