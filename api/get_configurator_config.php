<?php
/**
 * Secure Product Configurator Config API
 * Serves configuration data with validation and security checks
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Rate limiting - simple implementation
session_start();
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'cli';
$rate_limit_key = 'config_requests_' . $remote_addr;
$requests = $_SESSION[$rate_limit_key] ?? ['count' => 0, 'time' => time()];

// Reset counter every 60 seconds
if (time() - $requests['time'] > 60) {
    $requests = ['count' => 0, 'time' => time()];
}

// Allow max 30 requests per minute
if ($requests['count'] > 30) {
    http_response_code(429); // Too Many Requests
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

$requests['count']++;
$_SESSION[$rate_limit_key] = $requests;

// Validate referrer (must be from our domain)
$allowed_domains = ['www.cadmanmfg.com', 'cadmanmfg.com', 'www.hddoc.ca', 'hddoc.ca', 'localhost'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$referer_host = parse_url($referer, PHP_URL_HOST);

// Skip referer check for CLI or if no referer provided
if (!empty($referer) && !in_array($referer_host, $allowed_domains) && $referer_host !== null) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid referrer']);
    exit;
}

// Optional: Validate collection parameter
$collection = $_GET['collection'] ?? null;
$allowed_collections = ['bands', 'celtic', 'cultural', 'fancy', 'designer', 'plain', 'engagement', 'family', 'daughter', 'father', 'mother', 'corp'];

if ($collection && !in_array($collection, $allowed_collections)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid collection']);
    exit;
}

// Load the configuration file - check for collection-specific file first
$config_file = __DIR__ . '/../product_configurator.json';
$collection_specific_file = null;

// Map collection names to their directory paths
$collection_paths = [
    'bands' => 'bands_php/configurator.json',
    'celtic' => 'bands_php/celtic_configurator.json',
    'cultural' => 'bands_php/cultural_configurator.json',
    'fancy' => 'bands_php/fancy_configurator.json',
    'designer' => 'bands_php/fancy_configurator.json',
    'plain' => 'bands_php/plain_configurator.json',
    'engagement' => 'Engagement_php/configurator.json',
    'family' => 'family_php/configurator.json',
    'daughter' => 'family_php/daughter_configurator.json',
    'father' => 'family_php/father_configurator.json',
    'mother' => 'family_php/mother_configurator.json',
    'corp' => 'corp_php/configurator.json'
];

// If specific collection requested, check for collection-specific config first
if ($collection && isset($collection_paths[$collection])) {
    $collection_specific_file = __DIR__ . '/../' . $collection_paths[$collection];
    
    if (file_exists($collection_specific_file)) {
        // Use collection-specific configuration
        $config_data = file_get_contents($collection_specific_file);
        $config = json_decode($config_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fall back to main config if collection config is invalid
            error_log("Invalid collection-specific config for $collection, falling back to main config");
        } else {
            // Return collection-specific config directly
            $response = $config;
            
            // Cache control - allow browser caching for 1 hour
            header('Cache-Control: public, max-age=3600');
            header('ETag: "' . md5($config_data) . '"');
            
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit;
        }
    }
}

// Fallback to main configuration file
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration not found']);
    exit;
}

$config_data = file_get_contents($config_file);
$config = json_decode($config_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid configuration']);
    exit;
}

// If specific collection requested from main config, return only that collection
if ($collection) {
    if (isset($config['collections'][$collection])) {
        $response = [
            'version' => $config['version'],
            'last_updated' => $config['last_updated'],
            'collection' => $collection,
            'data' => $config['collections'][$collection],
            'ui_config' => $config['ui_config']
        ];
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Collection not found']);
        exit;
    }
} else {
    // Return full config
    $response = $config;
}

// Cache control - allow browser caching for 1 hour
header('Cache-Control: public, max-age=3600');
header('ETag: "' . md5($config_data) . '"');

// Output JSON
echo json_encode($response, JSON_PRETTY_PRINT);
