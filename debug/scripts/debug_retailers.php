<?php
$data = json_decode(file_get_contents('retailers.json'), true);
$totalRetailers = count($data);
$incompleteCount = 0;
$completeCount = 0;

echo "=== RETAILER COORDINATE ANALYSIS ===\n";
echo "Total retailers: $totalRetailers\n\n";

foreach ($data as $i => $retailer) {
    $lat = $retailer['lat'] ?? null;
    $lng = $retailer['lng'] ?? null;
    
    // Check if coordinates are missing, empty, or invalid
    $isIncomplete = false;
    $reason = '';
    
    if (!isset($retailer['lat'])) {
        $isIncomplete = true;
        $reason = 'missing lat key';
    } elseif (!isset($retailer['lng'])) {
        $isIncomplete = true;
        $reason = 'missing lng key';
    } elseif ($lat === null || $lng === null) {
        $isIncomplete = true;
        $reason = 'null coordinates';
    } elseif ($lat === '' || $lng === '') {
        $isIncomplete = true;
        $reason = 'empty coordinates';
    } elseif (!is_numeric($lat) || !is_numeric($lng)) {
        $isIncomplete = true;
        $reason = 'non-numeric coordinates';
    } elseif ($lat == 0 && $lng == 0) {
        $isIncomplete = true;
        $reason = 'zero coordinates (0,0)';
    }
    
    if ($isIncomplete) {
        $incompleteCount++;
        if ($incompleteCount <= 5) {
            echo "Incomplete #{$incompleteCount}: {$retailer['name']} - $reason\n";
            echo "  lat: " . var_export($lat, true) . "\n";
            echo "  lng: " . var_export($lng, true) . "\n";
        }
    } else {
        $completeCount++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Complete retailers: $completeCount\n";
echo "Incomplete retailers: $incompleteCount\n";

if ($incompleteCount == 0) {
    echo "\nAll retailers appear to have valid coordinates!\n";
    echo "The geocoding tool should show 0 retailers needing attention.\n";
} else {
    echo "\nThe geocoding tool should show $incompleteCount retailers needing attention.\n";
}
?>
