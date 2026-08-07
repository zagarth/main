<?php
session_start();
echo "<h1>🔍 Form Submission Debug</h1>";
echo "<hr>";

echo "<h3>Current Session:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>POST Data Received:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: lightgreen; padding: 10px;'>";
    echo "<strong>✅ POST REQUEST DETECTED!</strong><br>";
    echo "</div>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>Checking Login Button:</h3>";
    if (isset($_POST['login'])) {
        echo "✅ 'login' parameter found in POST data<br>";
    } else {
        echo "❌ 'login' parameter NOT found in POST data<br>";
        echo "Available POST keys: " . implode(', ', array_keys($_POST)) . "<br>";
    }
    
    echo "<h3>Form Data Analysis:</h3>";
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    echo "Username: '" . htmlspecialchars($username) . "' (length: " . strlen($username) . ")<br>";
    echo "Password: '" . str_repeat('*', strlen($password)) . "' (length: " . strlen($password) . ")<br>";
    echo "CSRF Token: '" . htmlspecialchars(substr($csrf, 0, 20)) . "...' (length: " . strlen($csrf) . ")<br>";
    
} else {
    echo "<div style='background: lightyellow; padding: 10px;'>";
    echo "⚠️ No POST request detected yet. Form hasn't been submitted.";
    echo "</div>";
}

echo "<h3>Server Info:</h3>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "<br>";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set') . "<br>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        input { padding: 8px; margin: 5px 0; width: 200px; display: block; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="test-form">
        <h3>Test Form (Same as Login)</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="test_token_123">
            <label>Username:</label>
            <input type="text" name="username" value="cadman_admin" required>
            <label>Password:</label>
            <input type="password" name="password" value="cadman123" required>
            <button type="submit" name="login">Test Submit</button>
        </form>
    </div>
    
    <div class="test-form">
        <h4>Quick Links:</h4>
        <p><a href="login.php">Back to Real Login</a></p>
        <p><a href="index.php">Try Admin Portal Direct</a></p>
    </div>
</body>
</html>