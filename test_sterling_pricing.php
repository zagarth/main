<?php
require_once 'includes/db_config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$code = 'LPN301B/Ster';

$stmt = $pdo->prepare('
    SELECT 
        pv.full_item_code,
        p.category,
        p.markup_percent,
        p.labor_hours,
        pv.gold_grams,
        pv.material_cost,
        pv.sterling_grams,
        p.stone_cost,
        p.star_cost,
        p.stone_setting_cost,
        p.description
    FROM products p
    JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE pv.full_item_code = :code
');
$stmt->execute([':code' => $code]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "$code not found\n";
    exit;
}

echo "=== ITEM DATA ===\n";
echo "Code: " . $item['full_item_code'] . "\n";
echo "Description: " . $item['description'] . "\n";
echo "Category: " . $item['category'] . "\n";
echo "Markup: " . $item['markup_percent'] . "%\n";
echo "\n=== COST COMPONENTS ===\n";
echo "Material cost: $" . $item['material_cost'] . "\n";
echo "Labor hours: " . $item['labor_hours'] . " x $28 = $" . ($item['labor_hours'] * 28) . "\n";
echo "Gold grams: " . $item['gold_grams'] . "\n";
echo "Sterling grams: " . $item['sterling_grams'] . "\n";
echo "Stone cost: $" . $item['stone_cost'] . "\n";
echo "Star cost: $" . $item['star_cost'] . "\n";
echo "Stone setting: $" . $item['stone_setting_cost'] . "\n";

// Current system calculation
$goldPrice = 7400;
$laborRate = 28;
$sterlingGF = 130;
$baseMargin = 20;

$pricePerGram = ($goldPrice * 32.15076 * 0.0004182) + 0.400;
$goldCost = $pricePerGram * floatval($item['gold_grams']);
$laborCost = floatval($item['labor_hours']) * $laborRate;
$sterlingCost = floatval($item['sterling_grams']) * $sterlingGF * 0.03215076;

echo "\n=== CALCULATED COSTS ===\n";
echo "Gold cost: $" . number_format($goldCost, 4) . "\n";
echo "Sterling cost: " . $item['sterling_grams'] . " grams x $sterlingGF x 0.03215076 = $" . number_format($sterlingCost, 4) . "\n";
echo "Labor cost: $" . number_format($laborCost, 2) . "\n";

$totalCost = floatval($item['material_cost']) + $laborCost + $goldCost + $sterlingCost +
             floatval($item['stone_cost']) + floatval($item['star_cost']) + 
             floatval($item['stone_setting_cost']);

echo "\nTotal cost: $" . number_format($totalCost, 2) . "\n";

$afterMarkup = $totalCost * (1 + floatval($item['markup_percent']) / 100);
echo "After " . $item['markup_percent'] . "% markup: $" . number_format($afterMarkup, 2) . "\n";

$priceWith8 = $afterMarkup * 1.08;
$priceWith20 = $afterMarkup * 1.20;

echo "\n=== MARKET MARKUP ===\n";
echo "With 8% base: $" . number_format($priceWith8, 2) . "\n";
echo "With 20% base: $" . number_format($priceWith20, 2) . "\n";

// Round to quarter
function roundToQuarter($price) {
    $cents = ($price - floor($price)) * 100;
    if ($cents > 75) return ceil($price);
    elseif ($cents > 50) return floor($price) + 0.75;
    elseif ($cents > 25) return floor($price) + 0.50;
    else return floor($price) + 0.25;
}

$rounded8 = roundToQuarter($priceWith8);
$rounded20 = roundToQuarter($priceWith20);

echo "\n=== ROUNDED PRICES ===\n";
echo "With 8% base, rounded: $" . number_format($rounded8, 2) . "\n";
echo "With 20% base, rounded: $" . number_format($rounded20, 2) . "\n";

echo "\n=== MAINFRAME COMPARISON ===\n";
echo "Mainframe shows: $78.00\n";
echo "Our system (8%): $" . number_format($rounded8, 2) . " (difference: $" . number_format(78 - $rounded8, 2) . ")\n";
echo "Our system (20%): $" . number_format($rounded20, 2) . "\n";
