<?php
// Parse mainframe IP record based on IP copybook layout
// IP-KEY (15) + IP-DESC (24) + IP-PRCE (6) + IP-COST (6) + IP-MTRL (5) + IP-LABR (5)

$record = "LPN301B/10K    10K LIC.PRAC.NURSE RING 009725006003000000255610K  04500003447000000000";

echo "=== PARSING MAINFRAME RECORD ===\n";
echo "Full record: $record\n\n";

$pos = 0;
$item = substr($record, $pos, 15); $pos += 15;
echo "IP-ITEM (15): '$item'\n";

$desc = substr($record, $pos, 24); $pos += 24;
echo "IP-DESC (24): '$desc'\n";

$prce = substr($record, $pos, 6); $pos += 6;
echo "IP-PRCE (6): '$prce' = $" . (floatval($prce) / 100) . "\n";

$cost = substr($record, $pos, 6); $pos += 6;
echo "IP-COST (6): '$cost' = $" . (floatval($cost) / 100) . "\n";

$mtrl = substr($record, $pos, 5); $pos += 5;
echo "IP-MTRL (5): '$mtrl' = $" . (floatval($mtrl) / 100) . "\n";

$labr = substr($record, $pos, 5); $pos += 5;
echo "IP-LABR (5): '$labr' = $" . (floatval($labr) / 100) . "\n";

$metl = substr($record, $pos, 5); $pos += 5;
echo "IP-METL (5): '$metl'\n";

$goldGrms = substr($record, $pos, 5); $pos += 5;
echo "IP-GOLD-GRMS (5): '$goldGrms' = " . (floatval($goldGrms) / 1000) . " grams\n";

echo "\n=== INTERPRETATION ===\n";
echo "This 10K item has a stored PRICE of $" . (floatval($prce) / 100) . "\n";
echo "For GOLD items (10K/14K/18K), OE27 CALCULATES the price\n";
echo "For STERLING items, OE27 uses the STORED price directly\n";
