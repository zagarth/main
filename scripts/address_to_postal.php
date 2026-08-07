<?php
/**
 * Geocode addresses to get both coordinates and postal codes
 * For retailers missing postal codes
 */

function geocodeFullAddress($address) {
    if (empty($address)) {
        echo "  Empty address\n";
        return false;
    }
    
    echo "  Trying: '{$address}'\n";
    
    $encoded = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=1&countrycodes=ca,us&addressdetails=1";
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CadmanRetailerUpdater/1.0\r\n",
            'timeout' => 15
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
    
    $result = [
        'lat' => round((float)$data[0]['lat'], 4),
        'lng' => round((float)$data[0]['lon'], 4),
        'display_name' => $data[0]['display_name']
    ];
    
    // Try to extract postal code from the response
    if (isset($data[0]['address'])) {
        $addr = $data[0]['address'];
        if (isset($addr['postcode'])) {
            $result['postal_code'] = $addr['postcode'];
        }
    }
    
    echo "  Found: {$result['display_name']}\n";
    if (isset($result['postal_code'])) {
        echo "  Postal: {$result['postal_code']}\n";
    }
    
    return $result;
}

// Load retailers
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Finding retailers with missing postal codes and geocoding their addresses...\n\n";

foreach ($retailers as &$retailer) {
    // Skip if already has good coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    // Only process retailers with missing postal codes
    if (!empty($retailer['postal_code'])) {
        continue;
    }
    
    $tested++;
    echo "#{$tested} {$retailer['name']}\n";
    echo "  Current address: {$retailer['address']}\n";
    
    $coords = geocodeFullAddress($retailer['address']);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        
        // Update postal code if we found one
        if (isset($coords['postal_code'])) {
            $retailer['postal_code'] = $coords['postal_code'];
            echo "  SUCCESS: {$coords['lat']}, {$coords['lng']} (Postal: {$coords['postal_code']})\n";
        } else {
            echo "  SUCCESS: {$coords['lat']}, {$coords['lng']} (No postal code found)\n";
        }
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
    sleep(1.5);
}

// Save updated data
if ($updated > 0) {
    file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
    echo "\nUpdated {$updated} retailers successfully using full addresses.\n";
    echo "Tested {$tested} retailers with missing postal codes.\n";
    
    $remaining = 0;
    foreach ($retailers as $r) {
        if ($r['lat'] == 50 && $r['lng'] == -100) $remaining++;
    }
    echo "Remaining with default coordinates: {$remaining}\n";
} else {
    echo "No retailers were updated.\n";
}
?>
