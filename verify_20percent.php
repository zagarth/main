<?php
require_once 'includes/db_config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$goldPrice = 7400;
$laborRate = 28;
$marketMarkup = 20;

$testItems = ['700M/10KY', 'C20/10K', 'C29/10K', 'C50/10K', '600M/10KY', '400L/10KY'];
$mainframePrices = [
    '700M/10KY' => 1096.50,
    'C20/10K' => 524.75,
    'C29/10K' => 856.25,
    'C50/10K' => 410.00,
    '600M/10KY' => 876.75
];

echo "NEW PRICES WITH 20% MARKET MARKUP:\n";
echo str_repeat('=', 90) . "\n";
printf("%-15s %-8s %12s %12s %12s %12s\n", 
    'Item', 'Category', 'Old 8%/12%', 'New 20%', 'Mainframe', 'vs Mainframe');
echo str_repeat('-', 90) . "\n";

foreach ($testItems as $itemCode) {
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
    
    if (!$item) continue;
    
    $pricePerGram = ($goldPrice * 32.15076 * 0.0004182) + 0.400;
    $goldCost = $pricePerGram * floatval($item['gold_grams']);
    $laborCost = floatval($item['labor_hours']) * $laborRate;
    $sterlingCost = floatval($item['sterling_grams']) * 130 * 0.03215076;
    
    $totalCost = floatval($item['material_cost']) + $laborCost + $goldCost + $sterlingCost +
                 floatval($item['stone_cost']) + floatval($item['star_cost']) + floatval($item['stone_setting_cost']);
    
    $afterMarkup = $totalCost * (1 + floatval($item['markup_percent']) / 100);
    
    // Old markup based on category
    $oldMarkup = (in_array($item['category'], ['Q01', 'W01']) ? 12.3 : 8.0);
    $oldPrice = $afterMarkup * (1 + $oldMarkup / 100);
    $newPrice = $afterMarkup * (1 + $marketMarkup / 100);
    
    $mainframe = $mainframePrices[$itemCode] ?? null;
    
    if ($mainframe) {
        $diff = $newPrice - $mainframe;
        printf("%-15s %-8s $%10.2f $%10.2f $%10.2f  +$%9.2f\n",
            $item['full_item_code'], 
            $item['category'],
            $oldPrice,
            $newPrice,
            $mainframe,
            $diff
        );
    } else {
        printf("%-15s %-8s $%10.2f $%10.2f %12s  %12s\n",
            $item['full_item_code'], 
            $item['category'],
            $oldPrice,
            $newPrice,
            'N/A',
            'N/A'
        );
    }
}

echo str_repeat('=', 90) . "\n";
echo "All items now at 20% market markup (mainframe was 8% or 12.3%)\n";
echo "You're now ahead of mainframe prices by the amounts shown above.\n";
