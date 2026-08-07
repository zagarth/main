<?php
session_start();

echo "<h2>Captcha Session Debug</h2>";

echo "<h3>Before Loading Captcha Image:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<pre>Session Data: ";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Loading Captcha Image:</h3>";
echo "<img src='create.php?debug=1&t=" . time() . "' alt='Captcha'><br><br>";

echo "<h3>After Loading Captcha Image:</h3>";
// Force reload session data
session_write_close();
session_start();
echo "<pre>Session Data: ";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['captcha_code'])) {
    echo "<p style='color: green;'>✓ Captcha session found: " . $_SESSION['captcha_code'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Captcha session NOT found</p>";
}

// Test validation function
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'mail_config.php';
    
    echo "<h3>Testing Validation:</h3>";
    $userInput = $_POST['test_code'] ?? '';
    echo "<p>User entered: " . htmlspecialchars($userInput) . "</p>";
    echo "<p>User input MD5: " . md5(strtolower(trim($userInput))) . "</p>";
    
    if (isset($_SESSION['captcha_code'])) {
        echo "<p>Session captcha: " . $_SESSION['captcha_code'] . "</p>";
        $match = (md5(strtolower(trim($userInput))) === $_SESSION['captcha_code']);
        echo "<p style='color: " . ($match ? 'green' : 'red') . ";'>" . ($match ? "✓ MATCH" : "✗ NO MATCH") . "</p>";
        
        // Don't clear session for debugging
        // unset($_SESSION['captcha_code']);
    } else {
        echo "<p style='color: red;'>✗ No session captcha to compare against</p>";
    }
} else {
?>
<h3>Test Captcha Validation:</h3>
<form method="post">
    <p>Enter the captcha code from the image above:</p>
    <input type="text" name="test_code" required>
    <button type="submit">Test Validation</button>
</form>
<?php } ?>

<h3>Session Configuration:</h3>
<table border="1">
    <tr><td>session.use_cookies</td><td><?php echo ini_get('session.use_cookies'); ?></td></tr>
    <tr><td>session.use_only_cookies</td><td><?php echo ini_get('session.use_only_cookies'); ?></td></tr>
    <tr><td>session.cookie_lifetime</td><td><?php echo ini_get('session.cookie_lifetime'); ?></td></tr>
    <tr><td>session.gc_maxlifetime</td><td><?php echo ini_get('session.gc_maxlifetime'); ?></td></tr>
    <tr><td>session.save_path</td><td><?php echo ini_get('session.save_path'); ?></td></tr>
</table>
?>
