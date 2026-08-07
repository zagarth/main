<?php
/**
 * Authenticated Carousel API
 * Provides secure access to carousel data for the main site
 * Uses hash-based authentication to maintain admin security
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: application/json');

// CORS headers for main site access
header('Access-Control-Allow-Origin: https://' . $_SERVER['HTTP_HOST']);
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, X-Auth-Timestamp');
header('Access-Control-Allow-Credentials: true');

// Load environment configuration
function loadEnv($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    return true;
}

loadEnv(__DIR__ . '/../.env');

// Get API secret from environment
define('API_SECRET', $_ENV['CSRF_SECRET'] ?? 'default_secret');
define('TOKEN_VALIDITY_WINDOW', 300); // 5 minutes

/**
 * Generate API token for main site access
 */
function generateApiToken($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    // Create token using site identifier + timestamp + secret
    $siteId = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $data = $siteId . '|' . $timestamp . '|carousel_api';
    
    return hash_hmac('sha256', $data, API_SECRET);
}

/**
 * Verify API token
 */
function verifyApiToken($token, $timestamp) {
    // Check if timestamp is within validity window
    $currentTime = time();
    $timeDiff = abs($currentTime - $timestamp);
    
    if ($timeDiff > TOKEN_VALIDITY_WINDOW) {
        return false;
    }
    
    // Generate expected token
    $expectedToken = generateApiToken($timestamp);
    
    // Use hash_equals for timing-safe comparison
    return hash_equals($expectedToken, $token);
}

/**
 * Authenticate request
 */
function authenticateRequest() {
    // Check for required headers
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? null;
    $timestamp = $_SERVER['HTTP_X_AUTH_TIMESTAMP'] ?? null;
    
    if (!$token || !$timestamp) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing authentication headers']);
        exit;
    }
    
    // Verify timestamp is numeric
    if (!is_numeric($timestamp)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid timestamp format']);
        exit;
    }
    
    // Verify token
    if (!verifyApiToken($token, (int)$timestamp)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid authentication token']);
        exit;
    }
    
    return true;
}

/**
 * Log API access
 */
function logApiAccess($action, $success = true) {
    $logFile = dirname(__DIR__) . '/api_access.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $status = $success ? 'SUCCESS' : 'FAILED';
    
    $logEntry = "[$timestamp] $status | Action: $action | IP: $ip | UA: $userAgent\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests for carousel data
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate the request
authenticateRequest();

try {
    // Get carousel data by making internal request to carousel_filter_manager.php
    $carouselManagerPath = dirname(__DIR__) . '/carousel_filter_manager.php';
    
    // Capture output from carousel manager
    ob_start();
    $_GET['action'] = 'carousel';
    include $carouselManagerPath;
    $carouselJson = ob_get_clean();
    
    // Parse the response
    $carouselData = json_decode($carouselJson, true);
    
    if (!$carouselData) {
        throw new Exception('Invalid response from carousel manager');
    }
    
    // Extract items from the carousel response
    $items = $carouselData['items'] ?? [];
    
    // Log successful access
    logApiAccess('carousel_data_fetch', true);
    
    // Return carousel data in expected format
    echo json_encode([
        'success' => true,
        'items' => $items,
        'timestamp' => time(),
        'active' => $carouselData['active'] ?? false,
        'collection' => $carouselData['collection'] ?? null,
        'filter' => $carouselData['filter'] ?? null
    ]);
    
} catch (Exception $e) {
    // Log failed access
    logApiAccess('carousel_data_fetch_error', false);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch carousel data',
        'message' => $e->getMessage()
    ]);
}
?>