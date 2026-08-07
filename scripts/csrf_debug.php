<?php
/**
 * CSRF Debug Tool
 */

require_once 'admin/auth.php';

echo "<h2>CSRF Token Debug</h2>\n";

// Simulate logged-in state for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'cadman_admin';
$_SESSION['login_time'] = time() - 300; // 5 minutes ago
$_SESSION['session_token'] = hash_hmac('sha256', 'cadman_admin' . $_SESSION['login_time'], CSRF_SECRET);

echo "<h3>Session Info:</h3>\n";
echo "Logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>\n";
echo "Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>\n";

// Generate a new token
$newToken = generateCSRFToken();
echo "Generated CSRF token: " . $newToken . "<br>\n";
echo "Session CSRF token after generate: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>\n";

// Test CSRF validation
$testToken = $_SESSION['csrf_token'] ?? '';
echo "<h3>CSRF Validation Test:</h3>\n";
echo "Test token: " . $testToken . "<br>\n";
echo "hash_equals result: " . (hash_equals($_SESSION['csrf_token'] ?? '', $testToken) ? 'VALID' : 'INVALID') . "<br>\n";

// Test with POST data simulation
echo "<h3>POST Simulation Test:</h3>\n";
$_POST['csrf_token'] = $testToken;
echo "POST csrf_token: " . ($_POST['csrf_token'] ?? 'NOT SET') . "<br>\n";
echo "Session csrf_token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>\n";
echo "Validation result: " . (hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '') ? 'VALID' : 'INVALID') . "<br>\n";

// Reset and try a fresh password change
echo "<h3>Fresh Password Change Test:</h3>\n";
unset($_POST);
$freshToken = generateCSRFToken();
$_POST['step'] = 'change';
$_POST['current_password'] = 'cadman123';
$_POST['new_password'] = 'NewPassword123!';
$_POST['confirm_password'] = 'NewPassword123!';
$_POST['csrf_token'] = $freshToken;

echo "Fresh token: " . $freshToken . "<br>\n";
echo "Will validate: " . (hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '') ? 'VALID' : 'INVALID') . "<br>\n";

?>