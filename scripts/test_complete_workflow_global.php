<?php
// Test complete workflow with actual files
$GLOBALS['CURRENT_CAPTCHA'] = '';

echo "=== COMPLETE WORKFLOW TEST WITH GLOBAL CAPTCHA ===\n\n";

// Test 1: Generate captcha
echo "1. Testing captcha generation:\n";

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
$session_id = session_id();

// Simulate URL parameter for create.php
$_GET['PHPSESSID'] = $session_id;

echo "   Session ID: $session_id\n";

// Include create.php to generate captcha
ob_start();
include 'create.php';
$image_data = ob_get_contents();
ob_end_clean();

echo "   Image generated: " . strlen($image_data) . " bytes\n";
echo "   Session captcha: " . ($_SESSION['captcha_code'] ?? 'not set') . "\n";
echo "   Global captcha: " . ($GLOBALS['CURRENT_CAPTCHA'] ?? 'not set') . "\n";

// Test 2: Simulate form submission
if (isset($_SESSION['captcha_code']) || isset($GLOBALS['CURRENT_CAPTCHA'])) {
    $test_captcha = $_SESSION['captcha_code'] ?? $GLOBALS['CURRENT_CAPTCHA'];
    echo "\n2. Testing form validation:\n";
    echo "   Using captcha code: $test_captcha\n";
    
    // Simulate POST data
    $_POST = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'message' => 'Test message',
        'verify' => $test_captcha
    ];
    
    // Test the validation function
    include_once 'packemail.php';
    
    // Mock the validation to avoid email sending
    $result = validateCaptcha($_POST['verify']);
    echo "   Validation result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    echo "   Session after validation: " . ($_SESSION['captcha_code'] ?? 'cleared') . "\n";
    echo "   Global after validation: " . ($GLOBALS['CURRENT_CAPTCHA'] ?: 'cleared') . "\n";
} else {
    echo "\n2. ERROR: No captcha generated\n";
}

echo "\n=== WORKFLOW TEST COMPLETE ===\n";
echo "Status: " . (($result ?? false) ? "CAPTCHA SYSTEM WORKING" : "CAPTCHA SYSTEM FAILED") . "\n";
?>
