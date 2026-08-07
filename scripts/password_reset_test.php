<?php
/**
 * Direct Password Reset Test
 */

session_start();
require_once 'admin/auth.php';

echo "<h2>🔐 Direct Password Reset Test</h2>\n";

// Simulate being logged in
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'cadman_admin';
$_SESSION['login_time'] = time() - 300;
$_SESSION['session_token'] = hash_hmac('sha256', 'cadman_admin' . $_SESSION['login_time'], CSRF_SECRET);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "Simulated login status: " . (isLoggedIn() ? '✅ LOGGED IN' : '❌ NOT LOGGED IN') . "<br>\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_reset'])) {
    echo "<h3>🧪 Password Reset Test:</h3>\n";
    
    try {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        echo "Testing current password: " . ($current_password ? '✅ PROVIDED' : '❌ MISSING') . "<br>\n";
        echo "Testing new password: " . ($new_password ? '✅ PROVIDED' : '❌ MISSING') . "<br>\n";
        
        // Validate current password
        if (!password_verify($current_password, ADMIN_PASSWORD_HASH)) {
            throw new Exception('Current password is incorrect');
        }
        echo "✅ Current password verified<br>\n";
        
        // Validate password strength
        if (strlen($new_password) < 12) {
            throw new Exception('Password must be at least 12 characters long');
        }
        echo "✅ Password length check passed<br>\n";
        
        // Generate new password hash
        $new_password_hash = generatePasswordHash($new_password);
        echo "✅ New password hash generated: " . substr($new_password_hash, 0, 20) . "...<br>\n";
        
        // Update the .env file with new password
        $env_file = __DIR__ . '/admin/.env';
        echo "Environment file: $env_file<br>\n";
        echo "File writable: " . (is_writable($env_file) ? '✅ YES' : '❌ NO') . "<br>\n";
        
        $env_content = file_get_contents($env_file);
        if ($env_content === false) {
            throw new Exception('Could not read environment file');
        }
        echo "✅ Environment file read (" . strlen($env_content) . " bytes)<br>\n";
        
        $new_env_content = preg_replace('/^ADMIN_PASSWORD_HASH=.*$/m', 'ADMIN_PASSWORD_HASH=' . $new_password_hash, $env_content);
        echo "✅ Content replacement prepared<br>\n";
        
        $write_result = file_put_contents($env_file, $new_env_content);
        if ($write_result === false) {
            $error = error_get_last();
            throw new Exception('Failed to update password file: ' . ($error ? $error['message'] : 'Unknown error'));
        }
        echo "✅ Password file updated ($write_result bytes written)<br>\n";
        
        // Test immediate reload
        unset($_ENV['ADMIN_PASSWORD_HASH']);
        loadEnv($env_file);
        
        // Verify new password works
        if (password_verify($new_password, $_ENV['ADMIN_PASSWORD_HASH'])) {
            echo "✅ New password verification successful!<br>\n";
            echo "<strong style='color: green;'>🎉 PASSWORD RESET COMPLETED SUCCESSFULLY!</strong><br>\n";
        } else {
            echo "❌ New password verification failed<br>\n";
        }
        
    } catch (Exception $e) {
        echo "<strong style='color: red;'>❌ ERROR: " . $e->getMessage() . "</strong><br>\n";
    }
}

?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.test-form { background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
input { padding: 8px; margin: 5px; border: 1px solid #ccc; border-radius: 4px; width: 200px; }
button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
h2, h3 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
</style>

<div class="test-form">
    <h3>🧪 Test Password Reset</h3>
    <form method="POST">
        <label>Current Password:<br>
        <input type="password" name="current_password" value="cadman123" placeholder="Enter current password"></label><br><br>
        
        <label>New Password:<br>
        <input type="password" name="new_password" value="NewTestPassword123!" placeholder="Enter new password (12+ chars)"></label><br><br>
        
        <button type="submit" name="test_reset">🔄 Test Password Reset</button>
    </form>
</div>

<div class="test-form">
    <h3>📋 Current Status</h3>
    <p><strong>Environment File:</strong> /var/www/html/homesite/admin/.env</p>
    <p><strong>Current Hash:</strong> <?php echo substr(ADMIN_PASSWORD_HASH, 0, 30) . '...'; ?></p>
    <p><strong>File Permissions:</strong> <?php echo substr(sprintf('%o', fileperms(__DIR__ . '/admin/.env')), -4); ?></p>
</div>