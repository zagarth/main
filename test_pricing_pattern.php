<?php
require_once 'includes/db_config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$goldPrice = 7400;
$laborRate = 28;
$baseMargin = 8;

$testCases = [
    ['code' => '700M/10KY', 'mainframe' => 1096.50],
    ['code' => 'C20/10K', 'mainframe' => 524.75],
    ['code' => 'C29/10K', 'mainframe' => 856.25],
    ['code' => 'C50/10K', 'mainframe' => 410.00],
    ['code' => '600M/10KY', 'mainframe' => null]
];

foreach ($testCases as $test) {
    $stmt = $pdo->prepare('
        SELECT 
            pv.full_item_code,
            p.markup_percent,
            p.labor_hours,
            pv.gold_grams,
            pv.material_cost,
            pv.sterling_grams,
            p.stone_cost,
            p.star_cost,
            p.stone_setting_cost
        FROM products p
        JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE pv.full_item_code = :code
    ');
    $stmt->execute([':code' => $test['code']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) continue;
    
    $pricePerGram = ($goldPrice * 32.15076 * 0.0004182) + 0.400;
    $goldCost = $pricePerGram * floatval($item['gold_grams']);
    $laborCost = floatval($item['labor_hours']) * $laborRate;
    $sterlingCost = floatval($item['sterling_grams']) * 130 * 0.03215076;
    
    $totalCost = floatval($item['material_cost']) + $laborCost + $goldCost + $sterlingCost +
                 floatval($item['stone_cost']) + floatval($item['star_cost']) + floatval($item['stone_setting_cost']);
    
    $afterMarkup = $totalCost * (1 + floatval($item['markup_percent']) / 100);
    $with8percent = $afterMarkup * (1 + $baseMargin / 100);
    
    echo str_pad($item['full_item_code'], 15) . ' ';
    echo 'Our calc: $' . number_format($with8percent, 2);
    
    if ($test['mainframe']) {
        $diff = $test['mainframe'] - $with8percent;
        $neededMarket = (($test['mainframe'] / $afterMarkup) - 1) * 100;
        echo ' | Mainframe: $' . number_format($test['mainframe'], 2);
        echo ' | Diff: $' . number_format($diff, 2);
        echo ' | Needs: ' . number_format($neededMarket, 2) . '%';
    } else {
        echo ' | Works correctly';
    }
    echo PHP_EOL;
}
