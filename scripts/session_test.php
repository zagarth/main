<?php
// Test session persistence across requests
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "<h2>Session Persistence Test</h2>";

echo "<h3>Current Session Info:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

// Set a test value in session
$_SESSION['test_value'] = 'This is a test - ' . time();

echo "<h3>Session Data Before Captcha:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Generate Captcha (in iframe to simulate separate request):</h3>";
echo "<iframe src='create.php?debug=1' width='800' height='400' style='border: 1px solid #ccc;'></iframe>";

echo "<h3>After Captcha Generation - Reload Session:</h3>";
session_write_close();
session_start(); // Restart session

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['captcha_code'])) {
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Captcha successfully stored in session!</p>";
    echo "<p><strong>Captcha Value:</strong> " . htmlspecialchars($_SESSION['captcha_code']) . "</p>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 18px;'>✗ Captcha NOT found in session!</p>";
}

echo "<h3>Session Configuration:</h3>";
echo "<table border='1'>";
$config_items = [
    'session.save_path' => ini_get('session.save_path'),
    'session.save_handler' => ini_get('session.save_handler'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
];

foreach ($config_items as $key => $value) {
    echo "<tr><td>{$key}</td><td>{$value}</td></tr>";
}
echo "</table>";

echo "<h3>Test Direct Captcha Call:</h3>";
echo "<p><a href='create.php?debug=1' target='_blank'>Open Captcha Debug in New Tab</a></p>";
echo "<p><a href='simple_captcha_test.php' target='_blank'>Test Simple Captcha Form</a></p>";
?>
