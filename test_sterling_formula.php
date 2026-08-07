<?php
require_once 'includes/db_config.php';

echo "=== STERLING COST CALCULATION COMPARISON ===\n\n";

$sterlingGrams = 4.500;
$laborHours = 0.913;
$laborRate = 28;
$itemMarkup = 50; // percent

// Our current (wrong) calculation
$sterlingCostOurs = $sterlingGrams * 130 * 0.03215076;
echo "OUR CURRENT FORMULA:\n";
echo "Sterling cost = $sterlingGrams grams × 130 × 0.03215076 = $" . number_format($sterlingCostOurs, 2) . "\n\n";

// Mainframe's actual calculation  
$sterlingCostMainframe = $sterlingGrams * 0.42;
echo "MAINFRAME FORMULA (AR12 line 391):\n";
echo "Sterling cost = $sterlingGrams grams × \$0.42 = $" . number_format($sterlingCostMainframe, 2) . "\n\n";

echo "DIFFERENCE: $" . number_format($sterlingCostOurs - $sterlingCostMainframe, 2) . "\n\n";

// Full price calculation with our formula
$laborCost = $laborHours * $laborRate;
$totalCostOurs = $laborCost + $sterlingCostOurs;
$afterMarkupOurs = $totalCostOurs * (1 + $itemMarkup / 100);
$priceWith8Ours = $afterMarkupOurs * 1.08;

echo "=== WITH OUR FORMULA ==\n";
echo "Labor: $" . number_format($laborCost, 2) . "\n";
echo "Sterling: $" . number_format($sterlingCostOurs, 2) . "\n";
echo "Total cost: $" . number_format($totalCostOurs, 2) . "\n";
echo "After 50% markup: $" . number_format($afterMarkupOurs, 2) . "\n";
echo "After 8% base: $" . number_format($priceWith8Ours, 2) . " → $72.00 rounded\n\n";

// Full price calculation with mainframe formula
$totalCostMF = $laborCost + $sterlingCostMainframe;
$afterMarkupMF = $totalCostMF * (1 + $itemMarkup / 100);

echo "=== WITH MAINFRAME FORMULA ===\n";
echo "Labor: $" . number_format($laborCost, 2) . "\n";
echo "Sterling: $" . number_format($sterlingCostMainframe, 2) . "\n";
echo "Total cost: $" . number_format($totalCostMF, 2) . "\n";
echo "After 50% markup: $" . number_format($afterMarkupMF, 2) . "\n";

// What market markup gets us to $78?
$neededPrice = 78.00;
$neededMarkup = (($neededPrice / $afterMarkupMF) - 1) * 100;
echo "To get $78: " . number_format($neededMarkup, 1) . "% market markup\n";
echo "Calculation: $" . number_format($afterMarkupMF, 2) . " × " . number_format(1 + $neededMarkup/100, 3) . " = $" . number_format($afterMarkupMF * (1 + $neededMarkup/100), 2) . "\n";
