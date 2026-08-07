<?php
require_once dirname(__FILE__) . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
if (!isset($input['address']) || !isset($input['city']) || !isset($input['province'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required address fields']);
    exit;
}

try {
    $address = trim($input['address']);
    $city = trim($input['city']);
    $province = trim($input['province']);
    $postalCode = trim($input['postal_code'] ?? '');
    
    // Build geocoding strategies
    $strategies = [];
    
    // Strategy 1: Full address with postal code
    if ($postalCode && preg_match('/^[A-Z]\d[A-Z] ?\d[A-Z]\d$/', strtoupper(str_replace(' ', '', $postalCode)))) {
        $strategies[] = "$address, $city, $province $postalCode, Canada";
    }
    
    // Strategy 2: Address without postal code
    $strategies[] = "$address, $city, $province, Canada";
    
    // Strategy 3: Just postal code for Canadian addresses
    if ($postalCode && preg_match('/^[A-Z]\d[A-Z] ?\d[A-Z]\d$/', strtoupper(str_replace(' ', '', $postalCode)))) {
        $strategies[] = "$postalCode, Canada";
    }
    
    // Strategy 4: City and province only
    $strategies[] = "$city, $province, Canada";
    
    $result = null;
    $lastError = '';
    
    foreach ($strategies as $addressString) {
        // Try Nominatim (OpenStreetMap)
        $result = geocodeWithNominatim($addressString);
        if ($result) {
            $result['strategy'] = $addressString;
            break;
        }
        
        // Add delay between attempts
        usleep(500000); // 0.5 seconds
    }
    
    if ($result) {
        // Log successful geocoding
        logAdminAction('GEOCODING_SUCCESS', "Address: $address, $city, $province | Coordinates: {$result['lat']}, {$result['lng']}");
        
        echo json_encode([
            'success' => true,
            'coordinates' => [
                'lat' => $result['lat'],
                'lng' => $result['lng']
            ],
            'strategy' => $result['strategy'],
            'source' => $result['source'],
            'full_address' => $result['display_name'] ?? ''
        ]);
    } else {
        // Log failed geocoding
        logAdminAction('GEOCODING_FAILED', "Address: $address, $city, $province");
        
        echo json_encode([
            'success' => false,
            'error' => 'Could not geocode address with any strategy',
            'strategies_tried' => $strategies,
            'suggestions' => [
                'Try simplifying the address (remove suite/unit numbers)',
                'Verify city and province spelling',
                'For Canadian postal codes, use format: A1A 1A1',
                'Try using a nearby major intersection',
                'Consider entering coordinates manually from Google Maps'
            ]
        ]);
    }
    
} catch (Exception $e) {
    logAdminAction('GEOCODING_ERROR', "Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function geocodeWithNominatim($address) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'q' => $address,
        'countrycodes' => 'ca',
        'limit' => 1,
        'addressdetails' => 1
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Cadman-Manufacturing-Admin/1.0',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data && count($data) > 0) {
        $result = $data[0];
        return [
            'lat' => floatval($result['lat']),
            'lng' => floatval($result['lon']),
            'source' => 'nominatim',
            'display_name' => $result['display_name'] ?? '',
            'confidence' => calculateConfidence($result)
        ];
    }
    
    return null;
}

function calculateConfidence($result) {
    // Simple confidence calculation based on result type
    $importance = floatval($result['importance'] ?? 0);
    $placeRank = intval($result['place_rank'] ?? 30);
    
    // Lower place_rank = higher importance
    $confidence = max(0, min(100, (50 - $placeRank) * 2 + $importance * 50));
    
    return round($confidence, 1);
}
?>