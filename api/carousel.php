<?php
/**
 * Secure Carousel API
 * Provides carousel data for the main site using hash-based authentication
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Define a secret key for hash verification (should be stored securely)
$secretKey = 'cadman_carousel_2025_secure_key'; // In production, use env variable

// Check for hash authentication
$timestamp = $_GET['t'] ?? '';
$hash = $_GET['h'] ?? '';

// Generate expected hash: md5(timestamp + secret_key)
$expectedHash = md5($timestamp . $secretKey);

// Verify the hash and timestamp (must be within 1 hour)
$currentTime = time();
$requestTime = (int)$timestamp;

if (empty($hash) || empty($timestamp) || 
    $hash !== $expectedHash || 
    abs($currentTime - $requestTime) > 3600) {
    
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Load carousel data directly (without requiring admin session)
$filterDataFile = __DIR__ . '/../admin/carousel_filter_data.json';

function getCarouselData() {
    global $filterDataFile;
    
    if (!file_exists($filterDataFile)) {
        return [
            'active' => false,
            'message' => 'No admin filter set, using default carousel',
            'items' => getDefaultCarouselItems()
        ];
    }
    
    $data = json_decode(file_get_contents($filterDataFile), true);
    if (!$data || !$data['active'] || !$data['collection'] || !$data['filter']) {
        return [
            'active' => false,
            'message' => 'No admin filter set, using default carousel', 
            'items' => getDefaultCarouselItems()
        ];
    }
    
    // Load the filtered items using the same logic as admin
    $items = getFilteredItems($data['collection'], $data['filter']);
    
    if (isset($items['error'])) {
        return [
            'active' => false,
            'error' => $items['error'],
            'items' => getDefaultCarouselItems()
        ];
    }
    
    // Convert to carousel format
    $carouselItems = convertToCarouselFormat($items['items'], $data['collection'], $data['filter']);
    
    return [
        'active' => true,
        'collection' => $data['collection'],
        'filter' => $data['filter'],
        'timestamp' => $data['timestamp'],
        'count' => count($carouselItems),
        'items' => $carouselItems
    ];
}

// Include necessary functions from carousel manager
require_once __DIR__ . '/../admin/carousel_filter_functions.php';

// Return the carousel data
echo json_encode(getCarouselData());
?>