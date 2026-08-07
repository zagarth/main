<?php
/**
 * Script to add proper coordinates for Canadian retailers
 * Starting with key locations mentioned by user
 */

// Load current data
$retailers = json_decode(file_get_contents('retailers.json'), true);

// Known coordinates for major Canadian cities
$cityCoordinates = [
    'Churchill, MB' => [58.7684, -94.1648],      // Churchill, Manitoba (Bazlik location)
    'Toronto, ON' => [43.6532, -79.3832],
    'Vancouver, BC' => [49.2827, -123.1207],
    'Calgary, AB' => [51.0447, -114.0719],
    'Ottawa, ON' => [45.4215, -75.6972],
    'Montreal, QC' => [45.5017, -73.5673],
    'Edmonton, AB' => [53.5461, -113.4938],
    'Winnipeg, MB' => [49.8951, -97.1384],
    'Halifax, NS' => [44.6488, -63.5752],
    'Kingston, ON' => [44.2312, -76.4860],
    'London, ON' => [42.9849, -81.2453],
    'Saskatoon, SK' => [52.1579, -106.6702],
    'Victoria, BC' => [48.4284, -123.3656],
    'Brandon, MB' => [49.8480, -99.9533],
];

$updated = 0;

foreach ($retailers as &$retailer) {
    // Skip if already has proper coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    $city = $retailer['city'];
    $state = $retailer['state'];
    $location_key = $city . ', ' . $state;
    
    // Check for direct match
    if (isset($cityCoordinates[$location_key])) {
        $retailer['lat'] = $cityCoordinates[$location_key][0];
        $retailer['lng'] = $cityCoordinates[$location_key][1];
        $updated++;
        echo "Updated: {$retailer['name']} in $location_key\n";
        continue;
    }
    
    // Check for city name match (without state)
    foreach ($cityCoordinates as $coord_city => $coords) {
        if (strpos($coord_city, $city) !== false && $city != '') {
            $retailer['lat'] = $coords[0];
            $retailer['lng'] = $coords[1];
            $updated++;
            echo "Updated: {$retailer['name']} in $city (matched $coord_city)\n";
            break;
        }
    }
}

// Save updated data
file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));

echo "\nUpdated $updated retailers with proper coordinates\n";
echo "Remaining retailers with placeholder coordinates: " . (141 - $updated) . "\n";

// Check Bazlik specifically
foreach ($retailers as $retailer) {
    if (strpos($retailer['name'], 'Bazlik') !== false) {
        echo "\nBazlik coordinates: lat={$retailer['lat']}, lng={$retailer['lng']}\n";
        echo "Location: {$retailer['city']}, {$retailer['state']}\n";
        break;
    }
}
?>
