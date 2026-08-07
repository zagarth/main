<?php
/**
 * Postal code only geocoding - most reliable for Canadian addresses
 */

function shouldSkipRetailer($retailer) {
    // Skip retailers with problematic address components
    $problematicTerms = ['unit', 'mall', 'square', 'plaza', 'centre', 'center'];
    
    $addressComponents = [
        strtolower($retailer['street']),
        strtolower($retailer['city']),
        strtolower($retailer['address'])
    ];
    
    foreach ($addressComponents as $component) {
        foreach ($problematicTerms as $term) {
            if (strpos($component, $term) !== false) {
                return true;
            }
        }
    }
    
    return false;
}

function geocodePostalCode($postalCode) {
    $postal = trim($postalCode);
    if (empty($postal)) {
        echo "  No postal code\n";
        return false;
    }
    
    // Format Canadian postal codes properly (A1A 1A1)
    $postal = strtoupper($postal);
    $postal = preg_replace('/[^A-Z0-9]/', '', $postal); // Remove spaces/chars
    if (strlen($postal) == 6) {
        $postal = substr($postal, 0, 3) . ' ' . substr($postal, 3);
    }
    
    echo "  Trying postal code: '{$postal}'\n";
    
    $encoded = urlencode($postal . ' Canada');
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
    
    return [
        'lat' => round((float)$data[0]['lat'], 4),
        'lng' => round((float)$data[0]['lon'], 4)
    ];
}

// Load retailers and try postal code geocoding
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Starting postal code geocoding...\n\n";

foreach ($retailers as &$retailer) {
    // Skip if already has good coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    // Skip retailers with problematic address components
    if (shouldSkipRetailer($retailer)) {
        echo "Skipping: {$retailer['name']} (contains mall/unit/plaza/square)\n";
        continue;
    }
    
    $tested++;
    echo "#{$tested} {$retailer['name']}\n";
    echo "  City: {$retailer['city']}, Province: {$retailer['province']}\n";
    
    $coords = geocodePostalCode($retailer['postal_code']);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        echo "  SUCCESS: {$coords['lat']}, {$coords['lng']}\n";
        $updated++;
    } else {
        echo "  FAILED\n";
    }
    
    echo "\n";
    
    // Stop after 20 updates 
    if ($updated >= 20) {
        echo "Stopping after {$updated} successful updates.\n";
        break;
    }
    
    // Rate limiting
    sleep(1);
}

// Save updated data
if ($updated > 0) {
    file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
    echo "\nUpdated {$updated} retailers successfully using postal codes.\n";
    echo "Tested {$tested} retailers total.\n";
    
    $remaining = 0;
    foreach ($retailers as $r) {
        if ($r['lat'] == 50 && $r['lng'] == -100) $remaining++;
    }
    echo "Remaining with default coordinates: {$remaining}\n";
} else {
    echo "No retailers were updated.\n";
}
?>
