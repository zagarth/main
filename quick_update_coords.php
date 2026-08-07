<?php
/**
 * Simple coordinate updater for retailers with default coordinates
 */

// Function to geocode using Nominatim (free service)
function geocodeAddress($address) {
    $address = urlencode(trim($address));
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}&limit=1&countrycodes=ca,us";
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CadmanRetailerUpdater/1.0\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return false;
    
    $data = json_decode($response, true);
    if (empty($data)) return false;
    
    return [
        'lat' => round((float)$data[0]['lat'], 4),
        'lng' => round((float)$data[0]['lon'], 4)
    ];
}

// Load current retailers.json
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;

echo "Starting coordinate updates...\n";

foreach ($retailers as &$retailer) {
    // Skip if already has good coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    $address = $retailer['address'];
    echo "Updating: {$retailer['name']} - {$address}\n";
    
    $coords = geocodeAddress($address);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        echo "  -> {$coords['lat']}, {$coords['lng']}\n";
        $updated++;
        
        // Stop after 10 updates to avoid overwhelming the service
        if ($updated >= 10) {
            echo "Stopping after 10 updates to be respectful to the geocoding service.\n";
            break;
        }
        
        // Delay between requests
        sleep(1);
    } else {
        echo "  -> Failed to geocode\n";
    }
}

// Save updated data
file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
echo "\nUpdated {$updated} retailers. Refresh the page to see changes.\n";
?>
