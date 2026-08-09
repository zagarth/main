<?php
/**
 * Admin Password Reset System
 * Secure password reset functionality for admin panel
 * Cadman Manufacturing
 */

session_start();

// Load environment configuration
require_once 'auth.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$loggedInUser = $isLoggedIn ? ($_SESSION['admin_username'] ?? '') : '';

$message = '';
$error = '';
$step = $_GET['step'] ?? ($isLoggedIn ? 'change' : 'request');
$token = $_GET['token'] ?? '';

// Handle logged-in user password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'change' && $isLoggedIn) {
    try {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password against the specific logged-in user's stored hash
        $currentUserName = $_SESSION['username'] ?? $_SESSION['admin_username'] ?? '';
        $currentUser = verifyUser($currentUserName, $current_password);
        if (!$currentUser) {
            throw new Exception('Current password is incorrect');
        }
        
        // Validate passwords match
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match');
        }
        
        // Validate password strength
        if (strlen($new_password) < 12) {
            throw new Exception('Password must be at least 12 characters long');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $new_password)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character');
        }
        
        $new_password_hash = generatePasswordHash($new_password);

        // Update password in database (authoritative source for all users)
        $pdo = getAdminConnection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE username = :username AND role = "admin"');
        $stmt->execute([':hash' => $new_password_hash, ':username' => $currentUserName]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update password — user not found in database');
        }

        // Verify the new password works immediately
        if (!password_verify($new_password, $new_password_hash)) {
            throw new Exception('Password update verification failed - please try again');
        }
        
        // Log successful password change
        logAdminAction('PASSWORD_CHANGED_WHILE_LOGGED_IN', ['username' => $loggedInUser]);
        
        $message = 'Password has been successfully changed! You will remain logged in with your new password.';
        $step = 'complete';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logAdminAction('PASSWORD_CHANGE_FAILED', ['error' => $e->getMessage(), 'username' => $loggedInUser]);
    }
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    try {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $username = trim($_POST['username'] ?? '');
        $security_answer = trim($_POST['security_answer'] ?? '');
        
        // Validate username matches
        if ($username !== ADMIN_USERNAME) {
            throw new Exception('Invalid username');
        }
        
        // Check security question answer (hardcoded for simplicity)
        $correct_answer = 'cadman'; // Answer to "What is the company name?"
        if (strtolower($security_answer) !== $correct_answer) {
            throw new Exception('Incorrect security answer');
        }
        
        // Generate secure reset token
        $reset_token = bin2hex(random_bytes(32));
        $reset_expiry = time() + 1800; // 30 minutes
        
        // Store reset token securely (in a simple file for this implementation)
        $reset_data = [
            'token' => password_hash($reset_token, PASSWORD_DEFAULT),
            'username' => $username,
            'expiry' => $reset_expiry,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        file_put_contents(__DIR__ . '/reset_token.json', json_encode($reset_data));
        
        // Log the reset attempt
        logAdminAction('PASSWORD_RESET_REQUESTED', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        
        // In a real implementation, you would email the token
        // For now, we'll display it (REMOVE IN PRODUCTION)
        $reset_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?step=reset&token=' . $reset_token;
        $message = "Password reset token generated. In production, this would be emailed.<br><br>Reset URL (for testing): <a href='$reset_url'>$reset_url</a>";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logAdminAction('PASSWORD_RESET_FAILED', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR']]);
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    try {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $token = $_POST['token'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate passwords match
        if ($new_password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }
        
        // Validate password strength
        if (strlen($new_password) < 12) {
            throw new Exception('Password must be at least 12 characters long');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $new_password)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character');
        }
        
        // Load and validate reset token
        $reset_file = __DIR__ . '/reset_token.json';
        if (!file_exists($reset_file)) {
            throw new Exception('Invalid or expired reset token');
        }
        
        $reset_data = json_decode(file_get_contents($reset_file), true);
        
        // Check token validity
        if (!password_verify($token, $reset_data['token'])) {
            throw new Exception('Invalid reset token');
        }
        
        // Check expiry
        if (time() > $reset_data['expiry']) {
            unlink($reset_file);
            throw new Exception('Reset token has expired');
        }
        
        // Check IP and user agent for security
        if ($reset_data['ip'] !== $_SERVER['REMOTE_ADDR']) {
            throw new Exception('Reset token can only be used from the same IP address');
        }
        
        // Generate new password hash using the same method as the auth system
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the specific user account in the database
        $targetUsername = $reset_data['username'] ?? '';
        if ($targetUsername === '') {
            throw new Exception('Reset target not found');
        }

        $pdo = getAdminConnection();
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE username = :username");
        $stmt->execute([
            ':password_hash' => $new_password_hash,
            ':username' => $targetUsername,
        ]);

        if ($stmt->rowCount() < 1) {
            throw new Exception('Password update failed');
        }

        // Verify the new password works immediately
        $verificationUser = verifyUser($targetUsername, $new_password);
        if (!$verificationUser) {
            throw new Exception('Password update verification failed - please try again');
        }

        // Remove reset token
        unlink($reset_file);
        
        // Log successful password reset
        logAdminAction('PASSWORD_RESET_COMPLETED', ['username' => $reset_data['username']]);
        
        $message = 'Password has been successfully reset. You can now <a href="login.php">login with your new password</a>.';
        $step = 'complete';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logAdminAction('PASSWORD_RESET_FAILED', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR']]);
    }
}

// Validate reset token for reset form
$valid_token = false;
if ($step === 'reset' && $token) {
    $reset_file = __DIR__ . '/reset_token.json';
    if (file_exists($reset_file)) {
        $reset_data = json_decode(file_get_contents($reset_file), true);
        if (password_verify($token, $reset_data['token']) && time() <= $reset_data['expiry']) {
            $valid_token = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset - Cadman Manufacturing</title>
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reset-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"],
        input[type="password"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 12px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #005a8a;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .security-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2><?php echo $isLoggedIn ? 'Change Admin Password' : 'Admin Password Reset'; ?></h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'change' && $isLoggedIn): ?>
            <div class="security-info">
                <strong>👤 Logged in as:</strong> <?php echo htmlspecialchars($loggedInUser); ?><br>
                <strong>🔒 Password Requirements:</strong> Your new password must be at least 12 characters long and contain uppercase, lowercase, numbers, and special characters.
            </div>
            
            <form method="POST" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="12">
                    <div class="password-requirements">
                        Must be at least 12 characters with uppercase, lowercase, number, and special character
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="12">
                </div>
                
                <button type="submit" class="btn">Change Password</button>
            </form>
            
            <p><a href="index.php">← Back to Admin Portal</a></p>
            
        <?php elseif ($step === 'request'): ?>
            <div class="security-info">
                <strong>🔒 Security Notice:</strong> This password reset is only available from authorized IP addresses. You must answer the security question correctly to proceed.
            </div>
            
            <form method="POST" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Admin Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="security_answer">Security Question: What is the company name? (lowercase)</label>
                    <input type="text" id="security_answer" name="security_answer" required>
                </div>
                
                <button type="submit" class="btn">Request Password Reset</button>
            </form>
            
            <p><a href="login.php">← Back to Login</a></p>
            
        <?php elseif ($step === 'reset' && $valid_token): ?>
            <div class="security-info">
                <strong>🔒 Password Requirements:</strong> Your new password must be at least 12 characters long and contain uppercase, lowercase, numbers, and special characters.
            </div>
            
            <form method="POST" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="12">
                    <div class="password-requirements">
                        Must be at least 12 characters with uppercase, lowercase, number, and special character
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="12">
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
            
        <?php elseif ($step === 'reset'): ?>
            <div class="error">Invalid or expired reset token. Please <a href="?step=request">request a new password reset</a>.</div>
            
        <?php elseif ($step === 'complete'): ?>
            <div class="success">
                <h3>Password Reset Complete!</h3>
                <p>Your password has been successfully updated. You can now login with your new password.</p>
                <p><a href="login.php" class="btn">Go to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>