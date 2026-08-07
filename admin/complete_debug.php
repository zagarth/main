<?php
// Complete auth debug test
session_start();

echo "<h2>Complete Auth Debug</h2>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Load environment
$env_file = __DIR__ . '/.env';
$env_vars = [];
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env_vars[trim($key)] = trim($value);
        }
    }
}

echo "<h3>Environment Variables</h3>";
echo "<pre>";
foreach ($env_vars as $key => $value) {
    if ($key === 'ADMIN_PASSWORD_HASH') {
        echo "$key = " . substr($value, 0, 20) . "...\n";
    } else {
        echo "$key = $value\n";
    }
}
echo "</pre>";

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check session
    echo "<h3>Session Data</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Check if login button was pressed
    $loginPressed = isset($_POST['login']);
    echo "<p><strong>Login button pressed:</strong> " . ($loginPressed ? "YES" : "NO") . "</p>";
    
    if ($loginPressed) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        echo "<h3>Login Attempt</h3>";
        echo "<p>Username: " . htmlspecialchars($username) . "</p>";
        echo "<p>Password: " . htmlspecialchars($password) . "</p>";
        echo "<p>CSRF Token: " . htmlspecialchars(substr($csrfToken, 0, 10)) . "...</p>";
        
        // Check CSRF
        $csrfValid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken);
        echo "<p><strong>CSRF Valid:</strong> " . ($csrfValid ? "YES" : "NO") . "</p>";
        
        if (isset($_SESSION['csrf_token'])) {
            echo "<p>Session CSRF: " . substr($_SESSION['csrf_token'], 0, 10) . "...</p>";
        } else {
            echo "<p>No CSRF token in session</p>";
        }
        
        // Check auth
        $stored_username = $env_vars['ADMIN_USERNAME'] ?? '';
        $stored_hash = $env_vars['ADMIN_PASSWORD_HASH'] ?? '';
        
        $usernameValid = hash_equals($stored_username, $username);
        $passwordValid = password_verify($password, $stored_hash);
        
        echo "<p><strong>Username Valid:</strong> " . ($usernameValid ? "YES" : "NO") . "</p>";
        echo "<p><strong>Password Valid:</strong> " . ($passwordValid ? "YES" : "NO") . "</p>";
        
        if ($csrfValid && $usernameValid && $passwordValid) {
            echo "<p style='color: green; font-size: 18px;'><strong>✅ ALL CHECKS PASSED - LOGIN SHOULD WORK!</strong></p>";
        } else {
            echo "<p style='color: red; font-size: 18px;'><strong>❌ LOGIN FAILED</strong></p>";
            if (!$csrfValid) echo "<p style='color: red;'>- CSRF token invalid</p>";
            if (!$usernameValid) echo "<p style='color: red;'>- Username invalid</p>";
            if (!$passwordValid) echo "<p style='color: red;'>- Password invalid</p>";
        }
    }
}

// Generate CSRF token for form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complete Auth Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form { background: #f5f5f5; padding: 20px; border-radius: 8px; max-width: 400px; margin: 20px 0; }
        input { display: block; margin: 10px 0; padding: 8px; width: 100%; box-sizing: border-box; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="form">
        <h3>Test Login Form (Debug Mode)</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="text" name="username" placeholder="Username" value="cadman_admin" required>
            <input type="password" name="password" placeholder="Password" value="cadman123" required>
            <button type="submit" name="login">Test Login</button>
        </form>
    </div>
    
    <p><a href="login.php">Back to Real Login</a></p>
</body>
</html>
