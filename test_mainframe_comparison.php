<?php
require_once 'includes/db_config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$goldPrice = 7400;
$laborRate = 28;

// User's test cases from mainframe
$mainframeTests = [
    '700M/10K' => 1096.50,
    'C20/10K' => 524.75,
    'C29/10K' => 856.25,
    'C50/10K' => 410.00,
    '600M/10KY' => 876.75,  // User said this one works
    '400L/10KY' => null,    // User said this one works
];

echo "MAINFRAME vs CALCULATOR COMPARISON\n";
echo str_repeat('=', 100) . "\n";
printf("%-15s %-8s %12s %12s %12s %12s %s\n", 
    "Item", "Category", "With 8%", "With 12.3%", "Mainframe", "Difference", "Needed");
echo str_repeat('-', 100) . "\n";

foreach ($mainframeTests as $itemCode => $mainframePrice) {
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
            p.stone_setting_cost
        FROM products p
        JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE pv.full_item_code = :code
    ');
    $stmt->execute([':code' => $itemCode]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo "$itemCode => NOT FOUND IN DATABASE\n";
        continue;
    }
    
    $pricePerGram = ($goldPrice * 32.15076 * 0.0004182) + 0.400;
    $goldCost = $pricePerGram * floatval($item['gold_grams']);
    $laborCost = floatval($item['labor_hours']) * $laborRate;
    $sterlingCost = floatval($item['sterling_grams']) * 130 * 0.03215076;
    
    $totalCost = floatval($item['material_cost']) + $laborCost + $goldCost + $sterlingCost +
                 floatval($item['stone_cost']) + floatval($item['star_cost']) + floatval($item['stone_setting_cost']);
    
    $afterMarkup = $totalCost * (1 + floatval($item['markup_percent']) / 100);
    $with8 = $afterMarkup * 1.08;
    $with12 = $afterMarkup * 1.123;
    
    if ($mainframePrice) {
        $diff8 = $mainframePrice - $with8;
        $diff12 = $mainframePrice - $with12;
        $best = abs($diff8) < abs($diff12) ? '8%' : '12.3%';
        
        printf("%-15s %-8s $%10.2f $%10.2f $%10.2f  $%9.2f  %s\n",
            $item['full_item_code'], 
            $item['category'],
            $with8,
            $with12,
            $mainframePrice,
            abs($diff8) < abs($diff12) ? $diff8 : $diff12,
            $best
        );
    } else {
        printf("%-15s %-8s $%10.2f $%10.2f %12s  %12s  %s\n",
            $item['full_item_code'], 
            $item['category'],
            $with8,
            $with12,
            '(works)',
            '',
            '8%'
        );
    }
}

echo str_repeat('=', 100) . "\n";
