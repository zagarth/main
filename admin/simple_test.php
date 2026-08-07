<?php
// Ultra simple test - just show if PHP is working
echo "<h1>PHP Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";

// Test if we can start session
if (session_start()) {
    echo "<p>✅ Session started successfully</p>";
} else {
    echo "<p>❌ Session failed to start</p>";
}

echo "<p>✅ Basic PHP is working</p>";
?>
