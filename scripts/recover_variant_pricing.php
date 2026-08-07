<?php
/**
 * Recover flattened product_variants pricing for a single base code.
 *
 * Default mode is DRY-RUN. Pass --apply to write changes.
 *
 * Usage:
 *   php scripts/recover_variant_pricing.php --product F2520
 *   php scripts/recover_variant_pricing.php --product F2520 --apply
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../cadman-database/php/PricingCalculator.php';

const GOLD_RATIOS = [
    '10K' => 1.117,
    '10KTT' => 1.117,
    '14K' => 1.262,
    '18K' => 1.504,
];

$apply = in_array('--apply', $argv, true);
$baseCode = null;
foreach ($argv as $index => $arg) {
    if ($arg === '--product' && isset($argv[$index + 1])) {
        $baseCode = trim($argv[$index + 1]);
    }
}

if ($baseCode === null || $baseCode === '') {
    fwrite(STDERR, "Usage: php scripts/recover_variant_pricing.php --product BASECODE [--apply]\n");
    exit(1);
}

try {
    $pdo = getDBConnection();
    $calc = new PricingCalculator($pdo);
} catch (Throwable $e) {
    fwrite(STDERR, "DB: " . $e->getMessage() . "\n");
    exit(2);
}

$stmt = $pdo->prepare(
    "SELECT p.product_id, p.base_code, p.labor_hours, p.labor_cost, p.stone_cost, p.star_cost,
            p.stone_setting_cost, p.markup_percent,
            pv.variant_id, pv.full_item_code, pv.metal_type, pv.metal_variant,
            pv.gold_grams, pv.sterling_grams, pv.material_cost,
            pv.gold_cost, pv.sterling_cost, pv.total_cost, pv.selling_price, pv.previous_price
     FROM products p
     JOIN product_variants pv ON pv.product_id = p.product_id
     WHERE p.base_code = :base_code
     ORDER BY FIELD(pv.metal_type, '10K', '10KTT', '14K', '18K', 'STER', 'GF'), pv.variant_id"
);
$stmt->execute([':base_code' => $baseCode]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    fwrite(STDERR, "No variants found for {$baseCode}\n");
    exit(3);
}

$base = $rows[0];
$referenceSterling = null;
foreach ($rows as $row) {
    if (in_array($row['metal_type'], ['STER', 'GF'], true) && (float)$row['sterling_grams'] > 0) {
        $referenceSterling = $row;
        break;
    }
}

if ($referenceSterling === null) {
    fwrite(STDERR, "No sterling-weight source found for {$baseCode}; aborting.\n");
    exit(4);
}

$referenceSterlingGrams = (float)$referenceSterling['sterling_grams'];
$markupPercent = (float)$base['markup_percent'];
$laborHours = (float)$base['labor_hours'];
$stoneCost = (float)$base['stone_cost'];
$starCost = (float)$base['star_cost'];
$stoneSettingCost = (float)$base['stone_setting_cost'];

$plan = [];
foreach ($rows as $row) {
    $metalType = $row['metal_type'];
    $newGoldGrams = (float)$row['gold_grams'];
    $newSterlingGrams = (float)$row['sterling_grams'];

    if (isset(GOLD_RATIOS[$metalType])) {
        $newGoldGrams = round($referenceSterlingGrams * GOLD_RATIOS[$metalType], 3);
        $newSterlingGrams = 0.0;
    } elseif (in_array($metalType, ['STER', 'GF'], true)) {
        $newGoldGrams = 0.0;
        $newSterlingGrams = $referenceSterlingGrams;
    }

    $priceResult = $calc->calculatePrice([
        'goldGrams' => $newGoldGrams,
        'karat' => $metalType,
        'sterlingGrams' => $newSterlingGrams,
        'laborHours' => $laborHours,
        'materialCost' => (float)$row['material_cost'],
        'stoneCost' => $stoneCost,
        'starCost' => $starCost,
        'stoneSettingCost' => $stoneSettingCost,
    ], $markupPercent);

    $plan[] = [
        'variant_id' => (int)$row['variant_id'],
        'full_item_code' => $row['full_item_code'],
        'metal_type' => $metalType,
        'old_gold_grams' => (float)$row['gold_grams'],
        'new_gold_grams' => $newGoldGrams,
        'old_sterling_grams' => (float)$row['sterling_grams'],
        'new_sterling_grams' => $newSterlingGrams,
        'material_cost' => (float)$row['material_cost'],
        'old_total_cost' => (float)$row['total_cost'],
        'new_total_cost' => $priceResult['totalCost'],
        'old_selling_price' => (float)$row['selling_price'],
        'new_selling_price' => $priceResult['roundedPrice'],
        'previous_price' => (float)$row['previous_price'],
        'gold_cost' => $priceResult['goldCost'],
        'sterling_cost' => $priceResult['sterlingCost'],
    ];
}

echo "Recovery plan for {$baseCode}\n";
echo str_repeat('=', 96) . "\n";
printf("%-12s %-6s %10s %10s %10s %10s %12s %12s %12s\n",
    'Variant', 'Metal', 'Gold Old', 'Gold New', 'Ster Old', 'Ster New', 'Price Old', 'Price New', 'Prev Price');
echo str_repeat('-', 96) . "\n";
foreach ($plan as $row) {
    printf("%-12s %-6s %10.3f %10.3f %10.3f %10.3f %12.2f %12.2f %12.2f\n",
        $row['full_item_code'],
        $row['metal_type'],
        $row['old_gold_grams'],
        $row['new_gold_grams'],
        $row['old_sterling_grams'],
        $row['new_sterling_grams'],
        $row['old_selling_price'],
        $row['new_selling_price'],
        $row['previous_price']
    );
}

echo "\nReference sterling source: {$referenceSterling['full_item_code']} ({$referenceSterlingGrams}g)\n";
echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN\n";

if (!$apply) {
    exit(0);
}

$pdo->beginTransaction();
try {
    $update = $pdo->prepare(
        "UPDATE product_variants
         SET gold_grams = :gold_grams,
             sterling_grams = :sterling_grams,
             gold_cost = :gold_cost,
             sterling_cost = :sterling_cost,
             total_cost = :total_cost,
             selling_price = :selling_price,
             cost_change_date = CURDATE(),
             price_change_date = CURDATE()
         WHERE variant_id = :variant_id"
    );

    foreach ($plan as $row) {
        $update->execute([
            ':gold_grams' => $row['new_gold_grams'],
            ':sterling_grams' => $row['new_sterling_grams'],
            ':gold_cost' => $row['gold_cost'],
            ':sterling_cost' => $row['sterling_cost'],
            ':total_cost' => $row['new_total_cost'],
            ':selling_price' => $row['new_selling_price'],
            ':variant_id' => $row['variant_id'],
        ]);
    }

    $pdo->commit();
    echo "Applied " . count($plan) . " variant updates for {$baseCode}.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . " -- ROLLED BACK\n");
    exit(5);
}
