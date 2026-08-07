<?php
// Final test of the captcha system with session ID parameter
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

session_start();
echo "Session ID: " . session_id() . "\n";

// Simulate what happens when create.php generates a captcha
$_SESSION['captcha'] = 'TEST123';
echo "Set captcha in session: " . $_SESSION['captcha'] . "\n";

// Check if session data persists
if (isset($_SESSION['captcha'])) {
    echo "Captcha retrieved from session: " . $_SESSION['captcha'] . "\n";
} else {
    echo "ERROR: Captcha not found in session\n";
}

// Test what create.php would generate
echo "\nTesting create.php URL with session ID:\n";
$url = "create.php?" . session_name() . "=" . session_id();
echo "URL: " . $url . "\n";

// Parse the URL to see what session ID would be extracted
$parsed = parse_url($url);
if (isset($parsed['query'])) {
    parse_str($parsed['query'], $params);
    echo "Session ID from URL: " . $params[session_name()] . "\n";
}

echo "\nSession status: " . session_status() . "\n";
echo "Session name: " . session_name() . "\n";
?>
