<?php
/**
 * Clean geocoding using only business name + street address
 */

function cleanStreetAddress($street) {
    $street = trim($street);
    
    // Remove problematic terms
    $problematicTerms = ['mall', 'plaza', 'square', 'centre', 'center', 'unit', 'suite'];
    foreach ($problematicTerms as $term) {
        $street = preg_replace('/\b' . preg_quote($term, '/') . '\b.*$/i', '', $street);
    }
    
    // Remove unit numbers at the end (like "123 Main St 45" -> "123 Main St")
    $street = preg_replace('/\s+\d+\s*$/', '', $street);
    
    // Clean up extra spaces and commas
    $street = preg_replace('/\s+/', ' ', $street);
    $street = trim($street, ' ,-');
    
    return $street;
}

function geocodeBusinessAndStreet($retailer) {
    $businessName = trim($retailer['name']);
    $street = cleanStreetAddress($retailer['street']);
    
    // Build simple search query
    $searchTerms = [];
    
    if (!empty($businessName)) {
        $searchTerms[] = $businessName;
    }
    
    if (!empty($street) && strlen($street) > 3) {
        $searchTerms[] = $street;
    }
    
    if (empty($searchTerms)) {
        echo "  No valid search terms\n";
        return false;
    }
    
    $searchQuery = implode(', ', $searchTerms) . ', Canada';
    echo "  Trying: '{$searchQuery}'\n";
    
    $encoded = urlencode($searchQuery);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=1&countrycodes=ca";
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CadmanRetailerUpdater/1.0\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        echo "  Network error\n";
        return false;
    }
    
    $data = json_decode($response, true);
    if (empty($data)) {
        echo "  No results\n";
        return false;
    }
    
    echo "  Found: " . substr($data[0]['display_name'], 0, 100) . "...\n";
    return [
        'lat' => round((float)$data[0]['lat'], 4),
        'lng' => round((float)$data[0]['lon'], 4)
    ];
}

// Load retailers and try clean geocoding
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Starting clean geocoding (business name + street only)...\n\n";

foreach ($retailers as &$retailer) {
    // Only process retailers with default coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    $tested++;
    echo "#{$tested} {$retailer['name']}\n";
    echo "  Original street: {$retailer['street']}\n";
    echo "  Cleaned street: " . cleanStreetAddress($retailer['street']) . "\n";
    
    $coords = geocodeBusinessAndStreet($retailer);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        echo "  SUCCESS: {$coords['lat']}, {$coords['lng']}\n";
        $updated++;
    } else {
        echo "  FAILED\n";
    }
    
    echo "\n";
    
    // Stop after 15 updates
    if ($updated >= 15) {
        echo "Stopping after {$updated} successful updates.\n";
        break;
    }
    
    // Rate limiting
    sleep(1);
}

// Save updated data
if ($updated > 0) {
    file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
    echo "\nUpdated {$updated} retailers using clean business name + street.\n";
    echo "Tested {$tested} retailers total.\n";
    
    $remaining = 0;
    foreach ($retailers as $r) {
        if ($r['lat'] == 50 && $r['lng'] == -100) $remaining++;
    }
    echo "Remaining with default coordinates: {$remaining}\n";
    
    $valid = count($retailers) - $remaining;
    echo "Total valid coordinates: {$valid}\n";
} else {
    echo "No retailers were updated.\n";
}
?>
