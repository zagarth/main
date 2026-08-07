<?php
/**
 * Fix Sterling Weight Correction Script
 * 
 * Problem: 843 sterling items have weights copied from gold instead of density-adjusted
 * Solution: Update sterling_grams = gold_grams × 0.8954 (density ratio)
 * 
 * Usage: 
 *   - Dry run (preview only): php fix_sterling_weights.php
 *   - Execute changes: php fix_sterling_weights.php --execute
 */

require_once '../includes/db_config.php';

// Density ratio: Sterling (10.36 g/cm³) / 10K Gold (11.57 g/cm³)
define('DENSITY_RATIO', 0.8954);
define('STERLING_GF', 130);
define('STERLING_PRICE_PER_GRAM', 0.03215076); // GF × troy oz conversion

$execute = isset($argv[1]) && $argv[1] === '--execute';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        STERLING WEIGHT CORRECTION SCRIPT                      ║\n";
echo "║        Density Ratio: 0.8954 (Sterling/10K)                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if (!$execute) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n";
    echo "   Run with --execute to apply changes\n\n";
} else {
    echo "🔧 EXECUTE MODE - Changes will be applied to database\n\n";
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find sterling items where weight = gold weight (WRONG - needs correction)
    // Only update items where sterling_grams matches gold_grams (within 0.01g)
    // Leave items alone if weight is already different (manually set or density-adjusted)
    $stmt = $pdo->query("
        SELECT 
            ster.variant_id,
            ster.full_item_code,
            ster.sterling_grams as current_weight,
            gold.gold_grams,
            ster.selling_price,
            ster.total_cost,
            p.base_code,
            p.labor_hours,
            p.labor_cost,
            p.markup_percent,
            p.stone_cost
        FROM product_variants ster
        JOIN products p ON ster.product_id = p.product_id
        LEFT JOIN product_variants gold ON gold.product_id = p.product_id 
            AND gold.metal_type = '10K'
        WHERE ster.metal_type = 'STER'
        AND ster.sterling_grams > 0
        AND gold.gold_grams > 0
        AND ABS(ster.sterling_grams - gold.gold_grams) < 0.01
        ORDER BY p.base_code
    ");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalItems = count($items);
    
    echo "Found $totalItems sterling items with incorrect weights\n\n";
    
    if ($totalItems === 0) {
        echo "✅ No corrections needed!\n";
        exit(0);
    }
    
    // Get system settings for calculation
    $settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    $baseMargin = $settings['base_margin'] ?? 20;
    
    echo "System Settings:\n";
    echo "  Base Margin: {$baseMargin}%\n";
    echo "  Sterling GF: " . STERLING_GF . "\n";
    echo "  Sterling $/gram: $" . number_format(STERLING_PRICE_PER_GRAM, 5) . "\n\n";
    
    $updates = 0;
    $totalWeightChange = 0;
    $totalPriceChange = 0;
    
    // Preview first 10 items
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "PREVIEW OF CHANGES (first 10 items):\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    for ($i = 0; $i < min(10, $totalItems); $i++) {
        $item = $items[$i];
        $newWeight = $item['gold_grams'] * DENSITY_RATIO;
        $weightChange = $item['current_weight'] - $newWeight;
        
        // Calculate new cost
        $newSterlingCost = $newWeight * STERLING_GF * STERLING_PRICE_PER_GRAM;
        $newTotalCost = $newSterlingCost + $item['labor_cost'] + $item['stone_cost'];
        
        // Calculate new price with markup and base margin
        $newPrice = $newTotalCost * (1 + $item['markup_percent'] / 100) * (1 + $baseMargin / 100);
        $priceChange = $item['selling_price'] - $newPrice;
        
        echo "{$item['full_item_code']}:\n";
        echo "  Weight: {$item['current_weight']}g → " . number_format($newWeight, 3) . "g (";
        echo ($weightChange > 0 ? "-" : "+") . number_format(abs($weightChange), 3) . "g)\n";
        echo "  Price: \${$item['selling_price']} → $" . number_format($newPrice, 2);
        echo " (" . ($priceChange > 0 ? "-" : "+") . "$" . number_format(abs($priceChange), 2) . ")\n\n";
    }
    
    if ($totalItems > 10) {
        echo "... and " . ($totalItems - 10) . " more items\n\n";
    }
    
    // Execute updates if requested
    if ($execute) {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "EXECUTING UPDATES...\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $pdo->beginTransaction();
        
        try {
            $updateStmt = $pdo->prepare("
                UPDATE product_variants 
                SET sterling_grams = :new_weight,
                    total_cost = :new_total_cost,
                    selling_price = :new_price
                WHERE variant_id = :variant_id
            ");
            
            foreach ($items as $item) {
                $newWeight = $item['gold_grams'] * DENSITY_RATIO;
                $weightChange = $item['current_weight'] - $newWeight;
                
                // Calculate new cost
                $newSterlingCost = $newWeight * STERLING_GF * STERLING_PRICE_PER_GRAM;
                $newTotalCost = $newSterlingCost + $item['labor_cost'] + $item['stone_cost'];
                
                // Calculate new price with markup and base margin
                $newPrice = $newTotalCost * (1 + $item['markup_percent'] / 100) * (1 + $baseMargin / 100);
                $priceChange = $item['selling_price'] - $newPrice;
                
                $updateStmt->execute([
                    ':new_weight' => $newWeight,
                    ':new_total_cost' => $newTotalCost,
                    ':new_price' => $newPrice,
                    ':variant_id' => $item['variant_id']
                ]);
                
                $updates++;
                $totalWeightChange += abs($weightChange);
                $totalPriceChange += abs($priceChange);
                
                if ($updates % 100 === 0) {
                    echo "  Updated $updates items...\n";
                }
            }
            
            $pdo->commit();
            
            echo "\n✅ SUCCESS! Updated $updates sterling items\n\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
            echo "   Transaction rolled back - no changes made\n";
            exit(1);
        }
        
    } else {
        // Calculate summary for dry run
        foreach ($items as $item) {
            $newWeight = $item['gold_grams'] * DENSITY_RATIO;
            $weightChange = $item['current_weight'] - $newWeight;
            
            $newSterlingCost = $newWeight * STERLING_GF * STERLING_PRICE_PER_GRAM;
            $newTotalCost = $newSterlingCost + $item['labor_cost'] + $item['stone_cost'];
            $newPrice = $newTotalCost * (1 + $item['markup_percent'] / 100) * (1 + $baseMargin / 100);
            $priceChange = $item['selling_price'] - $newPrice;
            
            $totalWeightChange += abs($weightChange);
            $totalPriceChange += abs($priceChange);
        }
    }
    
    // Summary
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "SUMMARY:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Items affected: $totalItems\n";
    echo "Total weight reduction: " . number_format($totalWeightChange, 2) . "g\n";
    echo "Average weight change: " . number_format($totalWeightChange / $totalItems, 3) . "g per item\n";
    echo "Total price impact: $" . number_format($totalPriceChange, 2) . "\n";
    echo "Average price change: $" . number_format($totalPriceChange / $totalItems, 2) . " per item\n\n";
    
    if (!$execute) {
        echo "To apply these changes, run:\n";
        echo "  php fix_sterling_weights.php --execute\n\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
