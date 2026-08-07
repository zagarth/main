<?php
/**
 * Contact Form Source Tracking
 * Tracks where users are accessing the contact form from
 */

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Extract tracking data
$sourcePage = isset($data['source_page']) ? trim($data['source_page']) : 'Unknown';
$sourceSection = isset($data['source_section']) ? trim($data['source_section']) : 'Unknown';
$pageUrl = isset($data['page_url']) ? trim($data['page_url']) : '';
$timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('c');
$productData = isset($data['product_data']) ? $data['product_data'] : null;

// Store in session for use when form is submitted
$_SESSION['contact_source'] = [
    'page' => $sourcePage,
    'section' => $sourceSection,
    'url' => $pageUrl,
    'timestamp' => $timestamp,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// Store product data separately if provided (for quote requests)
if ($productData) {
    $_SESSION['quote_product_data'] = [
        'product_id' => $productData['productId'] ?? 'unknown',
        'product_name' => $productData['name'] ?? 'Unknown Product',
        'category' => $productData['category'] ?? 'General',
        'collection' => $productData['collection'] ?? 'Unknown',
        'configured_options' => $productData['configuredOptions'] ?? [],
        'timestamp' => $productData['timestamp'] ?? date('c')
    ];
}

// Log the tracking event
$logMessage = sprintf(
    "[%s] Contact form accessed - Page: %s, Section: %s, URL: %s, IP: %s\n",
    date('Y-m-d H:i:s'),
    $sourcePage,
    $sourceSection,
    $pageUrl,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
);

error_log($logMessage, 3, '/tmp/contact_tracking.log');

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Source tracked',
    'data' => [
        'source_page' => $sourcePage,
        'source_section' => $sourceSection
    ]
]);
?>
