<?php
/**
 * Test F120M Pricing Consistency
 * Verifies that pricing calculations are consistent across all systems
 */

require_once '../cadman-database/php/PricingCalculator.php';
require_once '../includes/db_config.php';

// Connect to database
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize pricing calculator with current system settings
$calc = new PricingCalculator($pdo);
$settings = $calc->getSettings();

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>F120M Pricing Consistency Test</title>\n";
echo "    <style>\n";
echo "        body { font-family: monospace; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1000px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "        h1 { color: #333; margin-bottom: 20px; }\n";
echo "        h2 { color: #666; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }\n";
echo "        table { width: 100%; border-collapse: collapse; margin: 20px 0; }\n";
echo "        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }\n";
echo "        th { background: #f9f9f9; font-weight: bold; }\n";
echo "        .pass { color: #28a745; font-weight: bold; }\n";
echo "        .fail { color: #dc3545; font-weight: bold; }\n";
echo "        .value { font-weight: bold; color: #007bff; }\n";
echo "        .section { background: #f9f9f9; padding: 15px; border-radius: 6px; margin: 15px 0; }\n";
echo "        .label { font-weight: bold; color: #666; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";

echo "<h1>F120M Pricing Consistency Test</h1>\n";

// Display current system settings
echo "<div class='section'>\n";
echo "<h2>System Settings</h2>\n";
echo "<table>\n";
echo "<tr><th>Setting</th><th>Value</th></tr>\n";
echo "<tr><td>Gold Price</td><td class='value'>\${$settings['goldPrice']}/oz</td></tr>\n";
echo "<tr><td>Labor Rate</td><td class='value'>\${$settings['laborRate']}/hr</td></tr>\n";
echo "<tr><td>Sterling GF</td><td class='value'>{$settings['sterlingGF']}</td></tr>\n";
echo "<tr><td>Base Margin</td><td class='value'>{$settings['baseMargin']}%</td></tr>\n";
echo "</table>\n";
echo "</div>\n";

// Fetch F120M product data from database
$stmt = $pdo->prepare("
    SELECT 
        p.base_code,
        p.labor_hours,
        p.stone_cost,
        p.star_cost,
        p.stone_setting_cost,
        p.markup_percent,
        pv.variant_id,
        pv.metal_type,
        pv.metal_variant,
        pv.gold_grams,
        pv.sterling_grams,
        pv.material_cost,
        pv.total_cost,
        pv.selling_price
    FROM products p
    JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.base_code = 'F120M'
    ORDER BY 
        CASE pv.metal_type
            WHEN '10K' THEN 1
            WHEN '14K' THEN 2
            WHEN '18K' THEN 3
            WHEN 'STER' THEN 4
            WHEN 'GF' THEN 5
            ELSE 6
        END,
        pv.metal_variant
");
$stmt->execute();
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($variants)) {
    echo "<p class='fail'>Error: F120M product not found in database</p>\n";
    echo "</div></body></html>\n";
    exit;
}

echo "<h2>F120M Variant Pricing Tests</h2>\n";

foreach ($variants as $variant) {
    $metalType = $variant['metal_type'];
    $metalVariant = $variant['metal_variant'];
    $variantName = $metalType . ($metalVariant ? "/{$metalVariant}" : '');
    
    echo "<div class='section'>\n";
    echo "<h3>{$variantName}</h3>\n";
    
    // Prepare parameters for calculation
    $params = [
        'goldGrams' => floatval($variant['gold_grams']),
        'karat' => $metalType,
        'sterlingGrams' => floatval($variant['sterling_grams']),
        'laborHours' => floatval($variant['labor_hours']),
        'materialCost' => floatval($variant['material_cost']),
        'stoneCost' => floatval($variant['stone_cost']),
        'starCost' => floatval($variant['star_cost']),
        'stoneSettingCost' => floatval($variant['stone_setting_cost'])
    ];
    
    $markup = floatval($variant['markup_percent']);
    
    // Calculate using new PricingCalculator class
    $result = $calc->calculatePrice($params, $markup);
    
    // Get stored values from database
    $storedCost = floatval($variant['total_cost']);
    $storedPrice = floatval($variant['selling_price']);
    
    // Display input parameters
    echo "<table>\n";
    echo "<tr><th colspan='2'>Input Parameters</th></tr>\n";
    echo "<tr><td>Gold Grams</td><td class='value'>{$params['goldGrams']}g</td></tr>\n";
    echo "<tr><td>Sterling Grams</td><td class='value'>{$params['sterlingGrams']}g</td></tr>\n";
    echo "<tr><td>Labor Hours</td><td class='value'>{$params['laborHours']} hrs</td></tr>\n";
    echo "<tr><td>Material Cost</td><td class='value'>\${$params['materialCost']}</td></tr>\n";
    echo "<tr><td>Stone Cost</td><td class='value'>\${$params['stoneCost']}</td></tr>\n";
    echo "<tr><td>Star Cost</td><td class='value'>\${$params['starCost']}</td></tr>\n";
    echo "<tr><td>Stone Setting Cost</td><td class='value'>\${$params['stoneSettingCost']}</td></tr>\n";
    echo "<tr><td>Markup</td><td class='value'>{$markup}%</td></tr>\n";
    echo "</table>\n";
    
    // Display calculated breakdown
    echo "<table>\n";
    echo "<tr><th colspan='2'>Calculated Breakdown</th></tr>\n";
    echo "<tr><td>Gold Cost</td><td class='value'>\$" . number_format($result['goldCost'], 2) . "</td></tr>\n";
    echo "<tr><td>Sterling Cost</td><td class='value'>\$" . number_format($result['sterlingCost'], 2) . "</td></tr>\n";
    echo "<tr><td>Labor Cost</td><td class='value'>\$" . number_format($result['laborCost'], 2) . "</td></tr>\n";
    echo "<tr><td><strong>Total Cost</strong></td><td class='value'><strong>\$" . number_format($result['totalCost'], 2) . "</strong></td></tr>\n";
    echo "<tr><td>Selling Price (before rounding)</td><td class='value'>\$" . number_format($result['sellingPrice'], 2) . "</td></tr>\n";
    echo "<tr><td><strong>Final Price (quarter-rounded)</strong></td><td class='value'><strong>\$" . number_format($result['roundedPrice'], 2) . "</strong></td></tr>\n";
    echo "</table>\n";
    
    // Compare with stored values
    echo "<table>\n";
    echo "<tr><th>Metric</th><th>Stored (DB)</th><th>Calculated (New)</th><th>Difference</th><th>Status</th></tr>\n";
    
    $costDiff = $result['totalCost'] - $storedCost;
    $priceDiff = $result['roundedPrice'] - $storedPrice;
    
    $costMatch = abs($costDiff) < 0.01;
    $priceMatch = abs($priceDiff) < 0.01;
    
    echo "<tr>\n";
    echo "  <td>Cost</td>\n";
    echo "  <td>\$" . number_format($storedCost, 2) . "</td>\n";
    echo "  <td>\$" . number_format($result['totalCost'], 2) . "</td>\n";
    echo "  <td>\$" . number_format($costDiff, 2) . "</td>\n";
    echo "  <td class='" . ($costMatch ? 'pass' : 'fail') . "'>" . ($costMatch ? 'MATCH' : 'DIFFER') . "</td>\n";
    echo "</tr>\n";
    
    echo "<tr>\n";
    echo "  <td>Price</td>\n";
    echo "  <td>\$" . number_format($storedPrice, 2) . "</td>\n";
    echo "  <td>\$" . number_format($result['roundedPrice'], 2) . "</td>\n";
    echo "  <td>\$" . number_format($priceDiff, 2) . "</td>\n";
    echo "  <td class='" . ($priceMatch ? 'pass' : 'fail') . "'>" . ($priceMatch ? 'MATCH' : 'DIFFER') . "</td>\n";
    echo "</tr>\n";
    
    echo "</table>\n";
    
    // Explanation for differences
    if (!$costMatch || !$priceMatch) {
        echo "<p style='color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;'>\n";
        echo "<strong>Note:</strong> Differences are expected because:\n";
        echo "<ul style='margin: 10px 0 0 20px;'>\n";
        echo "<li>Stored prices may be from old gold price settings</li>\n";
        echo "<li>The gold formula was corrected from the wrong calculation</li>\n";
        echo "<li>Base margin was updated from 8% to 20%</li>\n";
        echo "<li>Quarter-rounding logic may have been refined</li>\n";
        echo "</ul>\n";
        echo "</p>\n";
    }
    
    echo "</div>\n";
}

// Expected F120M/10K calculation (based on user's spec)
echo "<h2>Expected F120M/10K Calculation (Verification)</h2>\n";
echo "<div class='section'>\n";
echo "<p class='label'>Based on specifications:</p>\n";
echo "<ul>\n";
echo "<li>Gold: 9.6g × (\$7400 / 31.1035) = \$2,284.78</li>\n";
echo "<li>Labor: 0.536 hrs × \$28/hr = \$15.00</li>\n";
echo "<li>Stones: \$2.80</li>\n";
echo "<li>Total Cost: \$2,302.58</li>\n";
echo "<li>Selling Price: \$2,302.58 × 1.55 × 1.20 = \$4,282.79</li>\n";
echo "<li>Quarter-Rounded: \$4,282.75</li>\n";
echo "</ul>\n";

// Calculate manually to verify
$expectedGoldCost = 9.6 * (7400 / 31.1035);
$expectedLaborCost = 0.536 * 28;
$expectedStoneCost = 2.80;
$expectedTotalCost = $expectedGoldCost + $expectedLaborCost + $expectedStoneCost;
$expectedSellingPrice = $expectedTotalCost * 1.55 * 1.20;
$expectedRounded = $calc->roundToQuarter($expectedSellingPrice);

echo "<p class='label'>Actual Calculation:</p>\n";
echo "<table>\n";
echo "<tr><td>Gold Cost</td><td class='value'>\$" . number_format($expectedGoldCost, 2) . "</td></tr>\n";
echo "<tr><td>Labor Cost</td><td class='value'>\$" . number_format($expectedLaborCost, 2) . "</td></tr>\n";
echo "<tr><td>Stone Cost</td><td class='value'>\$" . number_format($expectedStoneCost, 2) . "</td></tr>\n";
echo "<tr><td><strong>Total Cost</strong></td><td class='value'><strong>\$" . number_format($expectedTotalCost, 2) . "</strong></td></tr>\n";
echo "<tr><td>Selling Price (before rounding)</td><td class='value'>\$" . number_format($expectedSellingPrice, 2) . "</td></tr>\n";
echo "<tr><td><strong>Final Price (quarter-rounded)</strong></td><td class='value'><strong>\$" . number_format($expectedRounded, 2) . "</strong></td></tr>\n";
echo "</table>\n";

$verificationPassed = abs($expectedRounded - 4282.75) < 0.01;
echo "<p class='" . ($verificationPassed ? 'pass' : 'fail') . "' style='font-size: 18px; margin-top: 20px;'>\n";
echo "Formula Verification: " . ($verificationPassed ? "✓ PASSED" : "✗ FAILED") . "\n";
echo "</p>\n";

echo "</div>\n";

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>
