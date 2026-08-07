<?php
require_once 'includes/db_config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$goldPrice = 7400;
$laborRate = 28;

// Get all unique categories
$stmt = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category');
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "=================================================================\n";
echo "CATEGORY MARKET MARKUP ANALYSIS\n";
echo "=================================================================\n\n";

$results = [];

foreach ($categories as $category) {
    // Get a sample item from this category
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
            p.stone_setting_cost,
            pv.selling_price as old_price,
            pv.total_cost as old_cost,
            p.category,
            COUNT(*) OVER (PARTITION BY p.category) as category_count
        FROM products p
        JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE p.category = :category
        AND pv.gold_grams > 0
        LIMIT 1
    ');
    $stmt->execute([':category' => $category]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) continue;
    
    // Calculate cost at new gold price
    $pricePerGram = ($goldPrice * 32.15076 * 0.0004182) + 0.400;
    $goldCost = $pricePerGram * floatval($item['gold_grams']);
    $laborCost = floatval($item['labor_hours']) * $laborRate;
    $sterlingCost = floatval($item['sterling_grams']) * 130 * 0.03215076;
    
    $totalCost = floatval($item['material_cost']) + $laborCost + $goldCost + $sterlingCost +
                 floatval($item['stone_cost']) + floatval($item['star_cost']) + floatval($item['stone_setting_cost']);
    
    $afterMarkup = $totalCost * (1 + floatval($item['markup_percent']) / 100);
    
    // Calculate what the old price implies for market markup
    $oldGoldPricePerGram = floatval($item['old_cost']) - floatval($item['material_cost']) - 
                           floatval($item['labor_hours']) * $laborRate - $sterlingCost -
                           floatval($item['stone_cost']) - floatval($item['star_cost']) - 
                           floatval($item['stone_setting_cost']);
    $oldGoldPricePerGram = $oldGoldPricePerGram / floatval($item['gold_grams']);
    
    $oldAfterMarkup = floatval($item['old_cost']) * (1 + floatval($item['markup_percent']) / 100);
    $impliedMarketMarkup = ((floatval($item['old_price']) / $oldAfterMarkup) - 1) * 100;
    
    // Round to nearest common percentage
    if ($impliedMarketMarkup >= 11) {
        $suggestedMarkup = 12.3;
    } else {
        $suggestedMarkup = 8.0;
    }
    
    $results[$category] = [
        'sample_item' => $item['full_item_code'],
        'count' => $item['category_count'],
        'implied_markup' => $impliedMarketMarkup,
        'suggested_markup' => $suggestedMarkup
    ];
}

// Group by suggested markup
$markup8 = [];
$markup12 = [];

foreach ($results as $cat => $data) {
    if ($data['suggested_markup'] == 8.0) {
        $markup8[$cat] = $data;
    } else {
        $markup12[$cat] = $data;
    }
}

echo "CATEGORIES USING 8% MARKET MARKUP:\n";
echo str_repeat('-', 65) . "\n";
$total8 = 0;
foreach ($markup8 as $cat => $data) {
    echo sprintf("  %-6s  (%3d items)  Sample: %-15s  Implied: %5.2f%%\n", 
        $cat, $data['count'], $data['sample_item'], $data['implied_markup']);
    $total8 += $data['count'];
}
echo "\n  SUBTOTAL: $total8 items\n\n";

echo "CATEGORIES USING 12.3% MARKET MARKUP:\n";
echo str_repeat('-', 65) . "\n";
$total12 = 0;
foreach ($markup12 as $cat => $data) {
    echo sprintf("  %-6s  (%3d items)  Sample: %-15s  Implied: %5.2f%%\n", 
        $cat, $data['count'], $data['sample_item'], $data['implied_markup']);
    $total12 += $data['count'];
}
echo "\n  SUBTOTAL: $total12 items\n\n";

echo "=================================================================\n";
echo "TOTAL: " . ($total8 + $total12) . " items\n";
echo "=================================================================\n\n";

// Output solution options
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              SOLUTION OPTIONS                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "OPTION 1: Add category_market_markup to database\n";
echo "  - Add a new column to 'products' table\n";
echo "  - Set specific markup % for each category\n";
echo "  - Update calculator to use category-specific markup\n";
echo "  + PRO: Most accurate, matches mainframe exactly\n";
echo "  - CON: Database schema change required\n\n";

echo "OPTION 2: Create a category_markups lookup table\n";
echo "  - Create new table with category => markup_percent\n";
echo "  - Join this table in queries\n";
echo "  + PRO: Easy to manage, can update without code changes\n";
echo "  - CON: Adds JOIN to all pricing queries\n\n";

echo "OPTION 3: Hardcode markup map in JavaScript/PHP\n";
echo "  - Define categoryMarkups = {'W01': 12.3, 'W20': 8.0, ...}\n";
echo "  - Look up by category in calculator\n";
echo "  + PRO: Quick to implement, no database changes\n";
echo "  - CON: Must update code to change markups\n\n";

echo "OPTION 4: Use single 12.3% for all items\n";
echo "  - Change base_margin from 8% to 12.3%\n";
echo "  - Accept that W20/W80 items will be slightly higher\n";
echo "  + PRO: Simplest solution, one setting\n";
echo "  - CON: ~500 items will be overpriced by 4%\n\n";

echo "RECOMMENDATION: Option 1 or 2 for accuracy\n";
echo "                Option 3 for quick fix\n\n";
