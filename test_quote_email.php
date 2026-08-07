<?php
/**
 * Test script for quote request email functionality
 */

// Start session to simulate quote request data
session_start();

// Set up test session data (as if user clicked "Request Quote" on a product)
$_SESSION['contact_csrf_token'] = 'test_token_' . time();
$_SESSION['last_contact_submit'] = 0;
$_SESSION['honeypot_field'] = 'field_test123';

// Simulate product data from ProductModal
$_SESSION['quote_product_data'] = [
    'product_id' => '5310',
    'product_name' => 'Celtic Knot Wedding Band',
    'category' => 'celtic',
    'collection' => 'Celtic',
    'configured_options' => [
        'Metal' => '14K White Gold',
        'Size' => '8',
        'Finish' => 'Polished',
        'Width' => '6mm'
    ],
    'timestamp' => date('c')
];

// Simulate contact source tracking
$_SESSION['contact_source'] = [
    'page' => 'Product Modal',
    'section' => 'Quote Request - Celtic Knot Wedding Band',
    'url' => 'http://localhost/homesite/Celtic.php',
    'timestamp' => date('c'),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test Browser'
];

// Simulate HTTP environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

// Simulate form POST data
$_POST = [
    'name' => 'Test Customer',
    'email' => 'test@customer.com',
    'message' => 'I would like to request a quote for this beautiful Celtic ring. Please provide pricing and availability information.',
    'verify' => 'TEST', // This will need to match captcha
    'csrf_token' => $_SESSION['contact_csrf_token'],
    'form_timestamp' => time(),
    'field_test123' => '' // Honeypot field
];

// Set up captcha for testing
$_SESSION['captcha'] = 'TEST';

echo "<h2>Testing Quote Request Email System</h2>";
echo "<h3>Session Data Set:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>POST Data Set:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Processing through packemail.php...</h3>";

// Capture output from packemail.php
ob_start();
include 'packemail.php';
$output = ob_get_clean();

echo "<h3>Result:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Check if email was sent
if (isset($success) && $success) {
    echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ EMAIL SENT SUCCESSFULLY!<br>";
    echo "Check your email for the quote request with product details.";
    echo "</div>";
} else {
    echo "<div style='color: red; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ EMAIL FAILED TO SEND<br>";
    if (isset($errors) && !empty($errors)) {
        echo "Errors: " . implode(', ', $errors);
    }
    echo "</div>";
}

// Check log files
echo "<h3>Recent Log Entries:</h3>";
$logFile = '/tmp/cadman_mail.log';
if (file_exists($logFile)) {
    $logs = file($logFile);
    $recentLogs = array_slice($logs, -5);
    echo "<pre>" . htmlspecialchars(implode('', $recentLogs)) . "</pre>";
} else {
    echo "<p>No log file found at {$logFile}</p>";
}
?>