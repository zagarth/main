<?php
echo "=== WORKING BACKWARDS FROM MAINFRAME PRICE ===\n\n";

$mainframePrice = 78.00;
$baseMarkup = 8; // percent they were using
$itemMarkup = 50; // percent
$laborCost = 25.56;
$sterlingGrams = 4.5;

echo "Mainframe final price: $" . $mainframePrice . "\n";

// Work backwards
$beforeBaseMarkup = $mainframePrice / (1 + $baseMarkup / 100);
echo "Before 8% base markup: $" . number_format($beforeBaseMarkup, 2) . "\n";

$totalCost = $beforeBaseMarkup / (1 + $itemMarkup / 100);
echo "Before 50% item markup (total cost): $" . number_format($totalCost, 2) . "\n";

$sterlingCostNeeded = $totalCost - $laborCost;
echo "Labor cost: $" . number_format($laborCost, 2) . "\n";
echo "Sterling cost needed: $" . number_format($sterlingCostNeeded, 2) . "\n\n";

// What formula gets us there?
$ourFormula = $sterlingGrams * 130 * 0.03215076;
echo "Our current formula: $sterlingGrams × 130 × 0.03215076 = $" . number_format($ourFormula, 2) . "\n";

$perGramNeeded = $sterlingCostNeeded / $sterlingGrams;
echo "Per gram needed: $" . number_format($sterlingCostNeeded, 2) . " / $sterlingGrams = $" . number_format($perGramNeeded, 2) . " per gram\n\n";

// Check different GF values
echo "=== TESTING DIFFERENT GF VALUES ===\n";
foreach ([130, 140, 150, 160, 170, 180, 190, 200] as $gf) {
    $cost = $sterlingGrams * $gf * 0.03215076;
    echo "GF $gf: $sterlingGrams × $gf × 0.03215076 = $" . number_format($cost, 2);
    if (abs($cost - $sterlingCostNeeded) < 0.5) {
        echo " ← CLOSE!";
    }
    echo "\n";
}

echo "\n=== TESTING DIFFERENT MULTIPLIERS ===\n";
foreach ([0.03215076, 0.04, 0.045, 0.05, 0.055, 0.06] as $mult) {
    $cost = $sterlingGrams * 130 * $mult;
    echo "Multiplier $mult: $sterlingGrams × 130 × $mult = $" . number_format($cost, 2);
    if (abs($cost - $sterlingCostNeeded) < 0.5) {
        echo " ← CLOSE!";
    }
    echo "\n";
}
