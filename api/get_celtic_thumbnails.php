<?php
// API endpoint to get available Celtic width thumbnails
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Rate limiting (simple implementation)
session_start();
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'cli';
$rateLimitKey = 'celtic_thumbnails_' . $remoteAddr;
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

// Get product ID from query parameter
$productId = $_GET['product_id'] ?? '';
if (empty($productId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

// Function to check available Celtic images for a product
function getCelticImageOptions($baseProductId) {
    $celticPath = __DIR__ . '/../bands_php/images/celtic';
    $availableImages = [];
    
    if (!is_dir($celticPath)) {
        return $availableImages;
    }
    
    // Get all files in the directory
    $files = scandir($celticPath);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg'])) continue;
        
        $filename = pathinfo($file, PATHINFO_FILENAME);
        
        // Check if this file matches our base product ID
        if (preg_match('/^' . preg_quote($baseProductId, '/') . '([ML]?)(?:_alt\d*)?$/', $filename, $matches)) {
            $suffix = $matches[1] ?? '';
            
            $availableImages[] = [
                'suffix' => $suffix,
                'file' => $file,
                'path' => '/bands_php/images/celtic/' . $file,
                'gender' => $suffix === 'M' ? 'mens' : ($suffix === 'L' ? 'ladies' : 'neutral'),
                'is_main' => !preg_match('/_alt\d*/', $filename)
            ];
        }
    }
    
    return $availableImages;
}

// Function to determine the best thumbnail for each gender option
function getBestThumbnails($baseProductId) {
    $available = getCelticImageOptions($baseProductId);
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
    $thumbnails = getBestThumbnails($productId);
    echo json_encode($thumbnails);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>