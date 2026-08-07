<?php
/**
 * Simple Contact Form Submission Test
 * Tests the actual contact form submission flow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate form submission
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_REFERER'] = 'http://localhost/test.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once 'session_manager.php';

// Set up session variables that the form expects
if (!isset($_SESSION['contact_csrf_token'])) {
    $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['honeypot_field'])) {
    $_SESSION['honeypot_field'] = 'field_test123';
}

// Set up CAPTCHA (simulate it being correct)
$_SESSION['captcha_code'] = 'TEST123';

// Simulate form POST data
$_POST = [
    'name' => 'Test User',
    'email' => 'testuser@example.com',
    'message' => 'This is a test message from the contact form. If you receive this, the email system is working correctly.',
    'verify' => 'TEST123', // Match the captcha
    'csrf_token' => $_SESSION['contact_csrf_token'],
    'form_timestamp' => time() - 5, // 5 seconds ago
    $_SESSION['honeypot_field'] => '' // Empty honeypot (not a bot)
];

echo "=== Simulating Contact Form Submission ===\n\n";
echo "Name: " . $_POST['name'] . "\n";
echo "Email: " . $_POST['email'] . "\n";
echo "Message: " . substr($_POST['message'], 0, 50) . "...\n";
echo "CAPTCHA: " . $_POST['verify'] . "\n";
echo "CSRF Token: " . substr($_POST['csrf_token'], 0, 20) . "...\n\n";

echo "Processing...\n\n";

// Capture output
ob_start();

// Include the packemail script (which will process the form)
try {
    include 'packemail.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "=== Result ===\n";
echo $output;

echo "\n\n=== Mail Logs ===\n";
if (file_exists('/tmp/cadman_mail.log')) {
    $logs = file('/tmp/cadman_mail.log');
    $logs = array_slice($logs, -15);
    echo implode('', $logs);
} else {
    echo "No log file created.\n";
}
?>
