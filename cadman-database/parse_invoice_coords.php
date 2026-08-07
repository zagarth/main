<?php
/**
 * Parse invoice template coordinates from PDF bbox XML
 */

$xml = file_get_contents('/tmp/invoice_coords.xml');
$doc = new DOMDocument();
$doc->loadXML($xml);

// Get page dimensions
$page = $doc->getElementsByTagName('page')->item(0);
$pageWidth = $page->getAttribute('width');
$pageHeight = $page->getAttribute('height');

echo "PDF Page Size: {$pageWidth} x {$pageHeight} pts\n";
echo "PNG at 300 DPI would be: " . round($pageWidth * 300/72) . " x " . round($pageHeight * 300/72) . " pixels\n\n";

// Extract all words with their coordinates
$words = $doc->getElementsByTagName('word');
$coordinates = [];

foreach ($words as $word) {
    $text = $word->textContent;
    $xMin = floatval($word->getAttribute('xMin'));
    $yMin = floatval($word->getAttribute('yMin'));
    $xMax = floatval($word->getAttribute('xMax'));
    $yMax = floatval($word->getAttribute('yMax'));
    
    // Convert PDF pts to PNG pixels at 300 DPI
    $scale = 300 / 72;
    $xMinPx = round($xMin * $scale);
    $yMinPx = round($yMin * $scale);
    $xMaxPx = round($xMax * $scale);
    $yMaxPx = round($yMax * $scale);
    
    // Store coordinates for specific fields
    $key = trim($text);
    if (!isset($coordinates[$key])) {
        $coordinates[$key] = [];
    }
    
    $coordinates[$key][] = [
        'text' => $text,
        'pts' => ['xMin' => $xMin, 'yMin' => $yMin, 'xMax' => $xMax, 'yMax' => $yMax],
        'px' => ['xMin' => $xMinPx, 'yMin' => $yMinPx, 'xMax' => $xMaxPx, 'yMax' => $yMaxPx]
    ];
}

// Output key field coordinates
$fields = ['Sold', 'to', 'Ship', 'ACC#', 'SLS', 'Order#', 'Shipper', 'Ship Date', 'Date Invoiced', 'TERMS', 'QTYorder', 'QTY SHIP', 'ITEM NO', 'DESC.', 'UNIT PRICE', 'EXTENDED PRICE'];

foreach ($fields as $field) {
    if (isset($coordinates[$field])) {
        foreach ($coordinates[$field] as $idx => $coord) {
            echo "{$field} [{$idx}]:\n";
            echo "  PDF pts: ({$coord['pts']['xMin']}, {$coord['pts']['yMin']}) to ({$coord['pts']['xMax']}, {$coord['pts']['yMax']})\n";
            echo "  PNG px:  ({$coord['px']['xMin']}, {$coord['px']['yMin']}) to ({$coord['px']['xMax']}, {$coord['px']['yMax']})\n\n";
        }
    }
}

// Look for pipe-delimited line items
echo "\n=== LINE ITEM ROWS ===\n";
foreach ($coordinates as $text => $instances) {
    if (str_contains($text, '|')) {
        echo "Found pipe-delimited field: {$text}\n";
        foreach ($instances as $idx => $coord) {
            echo "  Row {$idx}: Y={$coord['px']['yMin']}-{$coord['px']['yMax']} px\n";
        }
    }
}
