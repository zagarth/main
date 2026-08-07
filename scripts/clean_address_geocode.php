<?php
/**
 * Clean address geocoding using postal code database approach
 * Only uses street, city, and business name - excludes mall/square/plaza
 */

function cleanAddressComponent($text) {
    // Remove problematic terms
    $problematicTerms = ['mall', 'square', 'plaza', 'centre', 'center', 'unit', 'suite', 'apt'];
    $cleaned = $text;
    
    foreach ($problematicTerms as $term) {
        $cleaned = preg_replace('/\b' . preg_quote($term, '/') . '\b/i', '', $cleaned);
    }
    
    // Remove unit numbers and extra formatting
    $cleaned = preg_replace('/\b(unit|suite|apt)\s*\d+\b/i', '', $cleaned);
    $cleaned = preg_replace('/\b\d+\s*-\s*\d+\b/', '', $cleaned); // Remove range numbers like "402 - "
    $cleaned = preg_replace('/\s+/', ' ', $cleaned); // Normalize spaces
    $cleaned = trim($cleaned, ' ,-');
    
    return $cleaned;
}

function buildCleanSearchQuery($retailer) {
    $searchParts = [];
    
    // Add business name (always include)
    $businessName = trim($retailer['name']);
    if (!empty($businessName)) {
        $searchParts[] = $businessName;
    }
    
    // Add cleaned street address if available
    $street = cleanAddressComponent($retailer['street']);
    if (!empty($street) && strlen($street) > 3) {
        $searchParts[] = $street;
    }
    
    // Add cleaned city if it's a real city name
    $city = cleanAddressComponent($retailer['city']);
    if (!empty($city) && strlen($city) > 2) {
        // Only add if it doesn't look like a building/mall name
        if (!preg_match('/\b(building|bldg|tower)\b/i', $city)) {
            $searchParts[] = $city;
        }
    }
    
    // Add province
    $province = trim($retailer['province']);
    if (!empty($province)) {
        $searchParts[] = $province;
    }
    
    // Always add Canada for context
    $searchParts[] = 'Canada';
    
    return implode(', ', array_filter($searchParts));
}

function geocodeCleanAddress($retailer) {
    $searchQuery = buildCleanSearchQuery($retailer);
    
    if (empty($searchQuery) || $searchQuery === 'Canada') {
        echo "  Insufficient clean address data\n";
        return false;
    }
    
    echo "  Clean search: '{$searchQuery}'\n";
    
    $encoded = urlencode($searchQuery);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=5&countrycodes=ca";
    
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
    
    // Score results based on relevance
    $bestResult = null;
    $bestScore = 0;
    
    foreach ($data as $result) {
        $displayName = strtolower($result['display_name']);
        $score = 0;
        
        // Score for business name match
        $businessWords = explode(' ', strtolower($retailer['name']));
        foreach ($businessWords as $word) {
            if (strlen($word) > 3 && strpos($displayName, $word) !== false) {
                $score += 3;
            }
        }
        
        // Score for city match
        $cleanCity = strtolower(cleanAddressComponent($retailer['city']));
        if (!empty($cleanCity) && strpos($displayName, $cleanCity) !== false) {
            $score += 5;
        }
        
        // Score for province match
        if (!empty($retailer['province']) && strpos($displayName, strtolower($retailer['province'])) !== false) {
            $score += 2;
        }
        
        // Prefer results that don't mention malls/plazas
        if (!preg_match('/\b(mall|plaza|shopping)\b/i', $displayName)) {
            $score += 1;
        }
        
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestResult = $result;
        }
    }
    
    if ($bestResult && $bestScore > 0) {
        echo "  Best match: {$bestResult['display_name']} (score: {$bestScore})\n";
        return [
            'lat' => round((float)$bestResult['lat'], 4),
            'lng' => round((float)$bestResult['lon'], 4)
        ];
    }
    
    return false;
}

// Load retailers and process those with default coordinates
$retailers = json_decode(file_get_contents('retailers.json'), true);
$updated = 0;
$tested = 0;

echo "Starting clean address geocoding for remaining retailers...\n";
echo "Using only: business name + street + city (excluding mall/plaza/square)\n\n";

foreach ($retailers as &$retailer) {
    // Only process retailers with default coordinates
    if ($retailer['lat'] != 50 || $retailer['lng'] != -100) {
        continue;
    }
    
    $tested++;
    echo "#{$tested} {$retailer['name']}\n";
    echo "  Original: {$retailer['address']}\n";
    
    $coords = geocodeCleanAddress($retailer);
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
    echo "\nUpdated {$updated} retailers successfully using clean addresses.\n";
    echo "Tested {$tested} retailers total.\n";
    
    $remaining = 0;
    $valid = 0;
    foreach ($retailers as $r) {
        if ($r['lat'] == 50 && $r['lng'] == -100) {
            $remaining++;
        } else {
            $valid++;
        }
    }
    echo "Now showing: {$valid} retailers on map\n";
    echo "Remaining with default coordinates: {$remaining}\n";
} else {
    echo "No retailers were updated.\n";
}
?>
