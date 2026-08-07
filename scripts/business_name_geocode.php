<?php
/**
 * Geocode using business name + city + province for remaining retailers
 */

function geocodeByBusinessName($retailer) {
    // Build search query using business name and location
    $searchTerms = [];
    
    // Add business name
    $businessName = trim($retailer['name']);
    if (!empty($businessName)) {
        $searchTerms[] = $businessName;
    }
    
    // Add city if it's not a mall/plaza name
    $city = trim($retailer['city']);
    $problematicCityTerms = ['mall', 'plaza', 'centre', 'center', 'square', 'unit'];
    $cityIsGood = true;
    foreach ($problematicCityTerms as $term) {
        if (stripos($city, $term) !== false) {
            $cityIsGood = false;
            break;
        }
    }
    
    if (!empty($city) && $cityIsGood) {
        $searchTerms[] = $city;
    }
    
    // Add province/state
    $province = trim($retailer['province']);
    if (!empty($province)) {
        $searchTerms[] = $province;
    }
    
    // Add Canada for context
    $searchTerms[] = 'Canada';
    
    $searchQuery = implode(', ', $searchTerms);
    
    if (empty($searchQuery) || $searchQuery === 'Canada') {
        echo "  Insufficient search terms\n";
        return false;
    }
    
    echo "  Searching: '{$searchQuery}'\n";
    
    $encoded = urlencode($searchQuery);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=3&countrycodes=ca,us";
    
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
    
    // Look for the best match - prefer results with the business name or city
    $bestMatch = null;
    $businessWords = explode(' ', strtolower($businessName));
    
    foreach ($data as $result) {
        $displayName = strtolower($result['display_name']);
        $score = 0;
        
        // Score based on business name words in the result
        foreach ($businessWords as $word) {
            if (strlen($word) > 3 && strpos($displayName, $word) !== false) {
                $score += 2;
            }
        }
        
        // Score based on city match
        if (!empty($city) && $cityIsGood && stripos($displayName, strtolower($city)) !== false) {
            $score += 3;
        }
        
        // Score based on province match
        if (!empty($province) && stripos($displayName, strtolower($province)) !== false) {
            $score += 1;
        }
        
        if ($score > 0 && ($bestMatch === null || $score > $bestMatch['score'])) {
            $bestMatch = $result;
            $bestMatch['score'] = $score;
        }
    }
    
    if ($bestMatch) {
        echo "  Found: {$bestMatch['display_name']} (score: {$bestMatch['score']})\n";
        return [
            'lat' => round((float)$bestMatch['lat'], 4),
            'lng' => round((float)$bestMatch['lon'], 4)
        ];
    } else {
        // Fallback to first result if no good match
        echo "  Using first result: {$data[0]['display_name']}\n";
        return [
            'lat' => round((float)$data[0]['lat'], 4),
            'lng' => round((float)$data[0]['lon'], 4)
        ];
    }
}

// Load retailers and try business name geocoding
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Starting business name geocoding for remaining retailers...\n\n";

foreach ($retailers as &$retailer) {
    // Only process retailers with default coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    $tested++;
    echo "#{$tested} {$retailer['name']}\n";
    echo "  City: {$retailer['city']}, Province: {$retailer['province']}\n";
    
    $coords = geocodeByBusinessName($retailer);
    if ($coords) {
        $retailer['lat'] = $coords['lat'];
        $retailer['lng'] = $coords['lng'];
        echo "  SUCCESS: {$coords['lat']}, {$coords['lng']}\n";
        $updated++;
    } else {
        echo "  FAILED\n";
    }
    
    echo "\n";
    
    // Stop after 15 updates to be respectful to the service
    if ($updated >= 15) {
        echo "Stopping after {$updated} successful updates.\n";
        break;
    }
    
    // Rate limiting - be extra careful with business name searches
    sleep(2);
}

// Save updated data
if ($updated > 0) {
    file_put_contents('retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
    echo "\nUpdated {$updated} retailers successfully using business names.\n";
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
