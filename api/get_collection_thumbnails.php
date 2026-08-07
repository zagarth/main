<?php
// API endpoint to get available collection width thumbnails (Celtic/Cultural/Plain/Fancy)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Rate limiting (simple implementation)
session_start();
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'cli';
$rateLimitKey = 'collection_thumbnails_' . $remoteAddr;
$currentTime = time();
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = [];
}
$_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], function($timestamp) use ($currentTime) {
    return ($currentTime - $timestamp) < 60; // 1 minute window
});

if (count($_SESSION[$rateLimitKey]) >= 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}
$_SESSION[$rateLimitKey][] = $currentTime;

// Get parameters from query
$productId = $_GET['product_id'] ?? '';
$collection = $_GET['collection'] ?? 'celtic';

if (empty($productId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

// Validate collection
$allowedCollections = ['celtic', 'cultural', 'plain', 'fancy'];
if (!in_array($collection, $allowedCollections)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid collection']);
    exit;
}

// Function to check available images for a product in a specific collection
function getCollectionImageOptions($baseProductId, $collection) {
    $collectionPath = __DIR__ . "/../bands_php/images/{$collection}";
    $availableImages = [];
    
    if (!is_dir($collectionPath)) {
        return $availableImages;
    }
    
    // Get all files in the directory
    $files = scandir($collectionPath);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg'])) continue;
        
        $filename = pathinfo($file, PATHINFO_FILENAME);
        
        // Standard handling for all collections
        if (preg_match('/^' . preg_quote($baseProductId, '/') . '([ML]?)(?:_alt\d*)?$/', $filename, $matches)) {
            $suffix = $matches[1] ?? '';
            
            $availableImages[] = [
                'suffix' => $suffix,
                'file' => $file,
                'path' => "/bands_php/images/{$collection}/" . $file,
                'gender' => $suffix === 'M' ? 'mens' : ($suffix === 'L' ? 'ladies' : 'neutral'),
                'is_main' => !preg_match('/_alt\d*/', $filename)
            ];
        }
    }
    
    return $availableImages;
}

// Function to determine the best thumbnail for each gender option
function getBestThumbnails($baseProductId, $collection) {
    $available = getCollectionImageOptions($baseProductId, $collection);
    $result = [
        'M' => null,
        'L' => null,
        'available_images' => $available
    ];
    
    // Sort available images to prefer main images over alts
    usort($available, function($a, $b) {
        if ($a['is_main'] && !$b['is_main']) return -1;
        if (!$a['is_main'] && $b['is_main']) return 1;
        return 0;
    });
    
    // Standard handling for all collections
    // First, try to find exact matches
    foreach ($available as $image) {
        if ($image['suffix'] === 'M' && !$result['M']) {
            $result['M'] = $image['path'];
        } elseif ($image['suffix'] === 'L' && !$result['L']) {
            $result['L'] = $image['path'];
        }
    }
    
    // If we don't have exact matches, use fallbacks
    if (!$result['M'] && !$result['L']) {
        // If no gender-specific images, use the base image for both
        foreach ($available as $image) {
            if ($image['suffix'] === '') {
                $result['M'] = $image['path'];
                $result['L'] = $image['path'];
                break;
            }
        }
    } elseif (!$result['M'] && $result['L']) {
        // If only L exists, use it for M too
        $result['M'] = $result['L'];
    } elseif ($result['M'] && !$result['L']) {
        // If only M exists, use it for L too
        $result['L'] = $result['M'];
    }
    
    return $result;
}

try {
    $thumbnails = getBestThumbnails($productId, $collection);
    echo json_encode($thumbnails);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>