<?php
/**
 * Session Debug Tool
 */

// Start session
session_start();

echo "<h2>Session Debug Information</h2>\n";

echo "<h3>Session Status:</h3>\n";
echo "Session ID: " . session_id() . "<br>\n";
echo "Session status: " . session_status() . " (1=disabled, 2=active)<br>\n";

echo "<h3>Session Data:</h3>\n";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Auth System Test:</h3>\n";

require_once 'admin/auth.php';

echo "isLoggedIn() result: " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "<br>\n";

if (isset($_SESSION['admin_logged_in'])) {
    echo "admin_logged_in: " . var_export($_SESSION['admin_logged_in'], true) . "<br>\n";
}
if (isset($_SESSION['login_time'])) {
    echo "login_time: " . $_SESSION['login_time'] . " (current: " . time() . ")<br>\n";
    echo "time_diff: " . (time() - $_SESSION['login_time']) . " seconds<br>\n";
    echo "timeout_limit: " . SESSION_TIMEOUT . " seconds<br>\n";
}
if (isset($_SESSION['session_token'])) {
    echo "session_token: " . $_SESSION['session_token'] . "<br>\n";
    if (isset($_SESSION['admin_username']) && isset($_SESSION['login_time'])) {
        $expectedToken = hash_hmac('sha256', $_SESSION['admin_username'] . $_SESSION['login_time'], CSRF_SECRET);
        echo "expected_token: " . $expectedToken . "<br>\n";
        echo "token_match: " . ($_SESSION['session_token'] === $expectedToken ? 'YES' : 'NO') . "<br>\n";
    }
}

echo "<h3>Login Test:</h3>\n";
echo '<form method="POST" action="admin/login.php">';
echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
echo 'Username: <input type="text" name="username" value="cadman_admin"><br>';
echo 'Password: <input type="password" name="password" value="cadman123"><br>';
echo '<button type="submit" name="login">Test Login</button>';
echo '</form>';

?>