<?php
/**
 * Simplified geocoding with essential address components only
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

function buildSimpleAddress($retailer) {
    $parts = [];
    
    // Extract street number and name (remove unit numbers)
    $street = trim($retailer['street']);
    if (!empty($street)) {
        // Remove unit/suite numbers from street (keep only street number + name)
        $street = preg_replace('/\s+\d+\s*$/', '', $street); // Remove trailing numbers
        $street = preg_replace('/\s+(Unit|Suite|Apt|#)\s*\d+.*$/i', '', $street); // Remove unit designations
        $parts[] = $street;
    }
    
    // Add city if available
    $city = trim($retailer['city']);
    if (!empty($city) && $city !== 'Downeast Mall') { // Skip mall names
        $parts[] = $city;
    }
    
    // Add province/state if available
    $province = trim($retailer['state']);
    if (!empty($province)) {
        $parts[] = $province;
    }
    
    // Add postal code if available and looks valid
    $postal = trim($retailer['postal_code']);
    if (!empty($postal) && strlen($postal) >= 3) {
        $parts[] = $postal;
    }
    
    return implode(', ', $parts);
}

function geocodeSimpleAddress($address) {
    if (empty($address)) {
        echo "  Empty address\n";
        return false;
    }
    
    echo "  Trying: '{$address}'\n";
    
    $encoded = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=1&countrycodes=ca,us";
    
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

// Load retailers and try simplified geocoding
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Starting simplified geocoding...\n\n";

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
    echo "  Full address: {$retailer['address']}\n";
    
    // Build simplified address
    $simpleAddress = buildSimpleAddress($retailer);
    
    $coords = geocodeSimpleAddress($simpleAddress);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        echo "  SUCCESS: {$coords['lat']}, {$coords['lng']}\n";
        $updated++;
    } else {
        echo "  FAILED\n";
    }
    
    echo "\n";
    
    // Stop after 15 updates to be respectful
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
    echo "\nUpdated {$updated} retailers successfully.\n";
    echo "Tested {$tested} retailers total.\n";
} else {
    echo "No retailers were updated.\n";
}
?>
