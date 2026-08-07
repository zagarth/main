<?php
// Test if session ID stays consistent
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "<h2>Session Consistency Test</h2>";
echo "<p><strong>Current Session ID:</strong> " . session_id() . "</p>";

// Store test data
$_SESSION['test_time'] = time();
$_SESSION['test_value'] = 'Main page test';

echo "<h3>Main Page Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Image Request (same session?):</h3>";
echo "<img src='create.php?debug=1&t=" . time() . "' alt='Debug'>";

echo "<h3>After Image Load - Check Session:</h3>";
session_write_close();
session_start();

echo "<p><strong>Session ID after reload:</strong> " . session_id() . "</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['captcha_code'])) {
    echo "<p style='color: green;'>✓ Captcha found: " . $_SESSION['captcha_code'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Captcha NOT found in session</p>";
}

echo "<h3>Cookie Information:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>
