<?php
// Full IP record layout from IP copybook:
// IP-ITEM (15) + IP-DESC (24) + IP-PRCE (6) + IP-COST (6) + IP-MTRL (5) + IP-LABR (5)
// + IP-METL (5) + IP-GOLD-GRMS (5) + IP-GOLD-COST (7) + IP-STER-GRMS (5) + IP-STER-COST (5)
// + IP-STNE-COST (5) + IP-STAR-COST (5) + IP-STNE-SET (5)
// + IP-SALES-ACCT (9) + IP-SALES-NEXT (5) + IP-SALES-TABL (60) [12 x 5]
// + IP-MARK-UP (2) + IP-SALES-TAX (4)

$record = "LPN301B/10K    10K LIC.PRAC.NURSE RING 009725006003000000255610K  04500003447000000000000000000000000030401    00001000020000100004000030000100002000010000000000000000000100000500000                                                      004080220713U01435401";

echo "=== FULL MAINFRAME RECORD PARSING ===\n\n";

$pos = 0;
$item = substr($record, $pos, 15); $pos += 15;
echo sprintf("%-20s (15): '%s'\n", "IP-ITEM", $item);

$desc = substr($record, $pos, 24); $pos += 24;
echo sprintf("%-20s (24): '%s'\n", "IP-DESC", $desc);

$prce = substr($record, $pos, 6); $pos += 6;
echo sprintf("%-20s  (6): '%s' = \$%.2f\n", "IP-PRCE", $prce, floatval($prce) / 100);

$cost = substr($record, $pos, 6); $pos += 6;
echo sprintf("%-20s  (6): '%s' = \$%.2f\n", "IP-COST", $cost, floatval($cost) / 100);

$mtrl = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-MTRL", $mtrl, floatval($mtrl) / 100);

$labr = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-LABR", $labr, floatval($labr) / 100);

$metl = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s'\n", "IP-METL", $metl);

$goldGrms = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = %.3f grams\n", "IP-GOLD-GRMS", $goldGrms, floatval($goldGrms) / 1000);

$goldCost = substr($record, $pos, 7); $pos += 7;
echo sprintf("%-20s  (7): '%s' = \$%.2f\n", "IP-GOLD-COST", $goldCost, floatval($goldCost) / 100);

$sterGrms = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = %.3f grams\n", "IP-STER-GRMS", $sterGrms, floatval($sterGrms) / 1000);

$sterCost = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-STER-COST", $sterCost, floatval($sterCost) / 100);

$stneCost = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-STNE-COST", $stneCost, floatval($stneCost) / 100);

$starCost = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-STAR-COST", $starCost, floatval($starCost) / 100);

$stneSet = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s' = \$%.2f\n", "IP-STNE-SET", $stneSet, floatval($stneSet) / 100);

$salesAcct = substr($record, $pos, 9); $pos += 9;
echo sprintf("%-20s  (9): '%s'\n", "IP-SALES-ACCT", $salesAcct);

$salesNext = substr($record, $pos, 5); $pos += 5;
echo sprintf("%-20s  (5): '%s'\n", "IP-SALES-NEXT", $salesNext);

$salesTable = substr($record, $pos, 60); $pos += 60;
echo sprintf("%-20s (60): '%s'\n", "IP-SALES-TABLE", $salesTable);

$markup = substr($record, $pos, 2); $pos += 2;
echo sprintf("%-20s  (2): '%s' = %d%%\n", "IP-MARK-UP", $markup, intval($markup));

$salesTax = substr($record, $pos, 4); $pos += 4;
echo sprintf("%-20s  (4): '%s' = %.2f%%\n", "IP-SALES-TAX", $salesTax, floatval($salesTax) / 100);

// Continue parsing rest
$info1 = substr($record, $pos, 30); $pos += 30;
$info2 = substr($record, $pos, 24); $pos += 24;
$analPage = substr($record, $pos, 3); $pos += 3;
$analLine = substr($record, $pos, 3); $pos += 3;
$catlPage = substr($record, $pos, 3); $pos += 3;
$catlLine = substr($record, $pos, 3); $pos += 3;
$catg = substr($record, $pos, 3); $pos += 3;

echo sprintf("%-20s  (3): '%s'\n", "IP-CATG", $catg);

echo "\n=== PRICE CALCULATION WITH 50% MARKUP ===\n";
$laborCost = floatval($labr) / 100;
$sterlingCost = floatval($sterGrms) / 1000 * 0.42;
$totalCost = $laborCost + $sterlingCost;
$afterItemMarkup = $totalCost * 1.50;

echo "Labor: \$" . number_format($laborCost, 2) . "\n";
echo "Sterling (if STER): " . (floatval($sterGrms)/1000) . " grams × \$0.42 = \$" . number_format($sterlingCost, 2) . "\n";
echo "Total cost: \$" . number_format($totalCost, 2) . "\n";
echo "After 50% item markup: \$" . number_format($afterItemMarkup, 2) . "\n";

foreach ([8, 12.3, 17, 20] as $baseMarkup) {
    $price = $afterItemMarkup * (1 + $baseMarkup / 100);
    echo "  With " . $baseMarkup . "% base: \$" . number_format($price, 2) . "\n";
}
