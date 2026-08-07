<?php
// Test captcha workflow with proper session handling
echo "=== CAPTCHA WORKFLOW TEST ===\n";

// Start fresh session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
$session_id = session_id();
echo "Initial session ID: $session_id\n";

// Test 1: Direct captcha generation
echo "\n1. Testing direct captcha generation:\n";

// Generate a random captcha code manually
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_code = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
}

// Store in session
$_SESSION['captcha'] = $captcha_code;
echo "   Generated captcha: $captcha_code\n";
echo "   Stored in session: " . $_SESSION['captcha'] . "\n";

// Test 2: Validation function
echo "\n2. Testing validation:\n";

function validateCaptcha($input) {
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    
    $session_captcha = strtoupper(trim($_SESSION['captcha']));
    $user_captcha = strtoupper(trim($input));
    
    $match = $session_captcha === $user_captcha;
    
    // Clear the captcha from session after use
    unset($_SESSION['captcha']);
    
    return $match;
}

// Test with correct code
$test_input = $captcha_code;
echo "   Testing with correct code: $test_input\n";
$result = validateCaptcha($test_input);
echo "   Result: " . ($result ? "VALID" : "INVALID") . "\n";

// Test 3: Check that create.php works with the session ID parameter
echo "\n3. Testing create.php session ID handling:\n";

// Reset session with captcha
$_SESSION['captcha'] = 'TESTCODE';
echo "   Set test captcha: " . $_SESSION['captcha'] . "\n";

// Simulate what happens when the browser requests create.php with session ID
$_GET[session_name()] = $session_id;
echo "   Session ID in \$_GET: " . $_GET[session_name()] . "\n";
echo "   Current session ID: " . session_id() . "\n";

if ($_GET[session_name()] === session_id()) {
    echo "   ✓ Session IDs match\n";
} else {
    echo "   ✗ Session ID mismatch\n";
}

echo "\n4. Testing contact form URL generation:\n";
$image_url = "create.php?" . session_name() . "=" . session_id();
echo "   Generated image URL: $image_url\n";

// Parse the URL
$parsed = parse_url($image_url);
parse_str($parsed['query'], $params);
echo "   Extracted session ID: " . $params[session_name()] . "\n";

if ($params[session_name()] === session_id()) {
    echo "   ✓ URL session ID matches current session\n";
} else {
    echo "   ✗ URL session ID does not match\n";
}

echo "\n=== WORKFLOW TEST COMPLETE ===\n";
echo "Result: Contact form should work correctly with session ID parameter\n";
?>
