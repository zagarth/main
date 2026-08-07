<?php
// header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0'); security headers first
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\';');

// Set proper cache headers for login page
header('Cache-Control: no-cache, no-store, must-revalidate, private');

// Set charset in header
header('Content-Type: text/html; charset=UTF-8');

require_once 'auth.php';
require_once 'TwoFactorAuth.php';

// Initialize variables
$loginError = '';
$message = '';
$debugInfo = '';
$step = 'login'; // Current step: 'login' or '2fa_verify'

// Handle login processing FIRST, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugInfo .= "POST request detected<br>";
    
    if (isset($_POST['login'])) {
        $debugInfo .= "POST login button detected<br>";
    } else {
        $debugInfo .= "POST request but no 'login' button - checking all POST keys<br>";
        foreach ($_POST as $key => $value) {
            $debugInfo .= "POST[$key] = " . (is_string($value) ? substr($value, 0, 20) . "..." : gettype($value)) . "<br>";
        }
    }

    if (isset($_POST['login']) || isset($_POST['username'])) {
        $debugInfo .= "Processing login attempt<br>";
        
        $ip = getClientIP();
        $debugInfo .= "Client IP: " . $ip . "<br>";
        
        // Check if IP is locked
        if (isIPLocked($ip)) {
            $debugInfo .= "IP is locked<br>";
            logAdminAction('LOGIN_BLOCKED', "IP locked: $ip");
            $loginError = 'Too many failed attempts. Please try again later.';
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            $debugInfo .= "Username: " . htmlspecialchars($username) . "<br>";
            $debugInfo .= "Password length: " . strlen($password) . "<br>";
            $debugInfo .= "CSRF token length: " . strlen($csrfToken) . "<br>";
            
            // Check if constants are defined
            $debugInfo .= "ADMIN_USERNAME defined: " . (defined('ADMIN_USERNAME') ? 'YES' : 'NO') . "<br>";
            $debugInfo .= "ADMIN_PASSWORD_HASH defined: " . (defined('ADMIN_PASSWORD_HASH') ? 'YES' : 'NO') . "<br>";
            
            if (defined('ADMIN_USERNAME')) {
                $debugInfo .= "Expected username: " . ADMIN_USERNAME . "<br>";
            }
            
            // Verify CSRF token
            $csrfValid = verifyCSRFToken($csrfToken);
            $debugInfo .= "CSRF validation: " . ($csrfValid ? 'VALID' : 'INVALID') . "<br>";
            
            if (!$csrfValid) {
                logAdminAction('CSRF_VIOLATION', "IP: $ip");
                $debugInfo .= "CSRF tokens - Session: " . ($_SESSION['csrf_token'] ?? 'not set') . "<br>";
                $debugInfo .= "CSRF tokens - Received: " . $csrfToken . "<br>";
                $loginError = 'Security token validation failed. Please try again.';
                $debugInfo .= "❌ AUTHENTICATION STOPPED: CSRF validation failed<br>";
            } else {
                // Use new unified credential verification
                $debugInfo .= "Calling verifyCredentials()<br>";
                
                // Debug: Test database connection directly
                try {
                    require_once __DIR__ . '/../includes/db_config_encrypted.php';
                    $debugInfo .= "db_config_encrypted.php loaded<br>";
                    
                    $testConn = getViewerConnection();
                    $debugInfo .= "Viewer connection: SUCCESS<br>";
                    
                    // Test direct query
                    $testStmt = $testConn->prepare("SELECT user_id, username, role FROM users WHERE username = :username");
                    $testStmt->execute([':username' => $username]);
                    $testUser = $testStmt->fetch();
                    $debugInfo .= "Direct query result: " . ($testUser ? "Found user ID {$testUser['user_id']}, role {$testUser['role']}" : "No user found") . "<br>";
                    
                    // Test password verification
                    if ($testUser) {
                        $testStmt2 = $testConn->prepare("SELECT password_hash FROM users WHERE username = :username");
                        $testStmt2->execute([':username' => $username]);
                        $hashRow = $testStmt2->fetch();
                        $passwordMatch = password_verify($password, $hashRow['password_hash']);
                        $debugInfo .= "Password verification: " . ($passwordMatch ? "MATCH" : "NO MATCH") . "<br>";
                    }
                    
                } catch (Exception $e) {
                    $debugInfo .= "Database test error: " . $e->getMessage() . "<br>";
                }
                
                $user = verifyCredentials($username, $password);
                
                $debugInfo .= "Credential verification: " . ($user ? 'SUCCESS' : 'FAILED') . "<br>";
                
                if ($user) {
                    $debugInfo .= "User data returned: " . print_r($user, true) . "<br>";
                    $debugInfo .= "LOGIN SUCCESS - User role: " . $user['role'] . "<br>";
                    
                    // Check if user has 2FA enabled (only for admin users)
                    $has2FA = ($user['role'] === 'admin') ? TwoFactorAuth::isEnabled($username) : false;
                    $debugInfo .= "2FA enabled: " . ($has2FA ? 'YES' : 'NO') . "<br>";
                    
                    if ($has2FA) {
                        // 2FA is enabled - set temporary session and require 2FA verification
                        $_SESSION['temp_username'] = $username;
                        $_SESSION['temp_user_data'] = $user;
                        $_SESSION['temp_login_time'] = time();
                        $_SESSION['awaiting_2fa'] = true;
                        
                        recordLoginAttempt($ip, true); // Password was correct
                        logAdminAction('LOGIN_PASSWORD_SUCCESS', "Username: $username | Role: {$user['role']} | IP: $ip | Awaiting 2FA");
                        
                        $debugInfo .= "2FA required - redirecting to 2FA verification<br>";
                        $step = '2fa_verify';
                        $message = 'Please enter your two-factor authentication code.';
                    } else {
                        // No 2FA - complete login immediately
                        recordLoginAttempt($ip, true);
                        
                        // Update last login timestamp in database
                        if (isset($user['user_id']) && $user['user_id'] > 0) {
                            updateLastLogin($user['user_id']);
                        }
                            
                        $_SESSION['logged_in'] = true;
                        $_SESSION['username'] = $username;
                        $_SESSION['admin_username'] = $username;
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['authenticated'] = true;
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['client_id'] = $user['client_id'] ?? null;
                        $_SESSION['login_time'] = time();
                        $_SESSION['session_start'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['max_lifetime'] = ini_get('session.gc_maxlifetime');
                        $_SESSION['session_token'] = hash_hmac('sha256', $username . $_SESSION['login_time'], CSRF_SECRET);
                        
                        $debugInfo .= "Session variables set for {$user['role']} user<br>";
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        $debugInfo .= "Session ID regenerated<br>";
                        
                        logAdminAction('LOGIN_SUCCESS', "Role: {$user['role']}");
                        
                        // Redirect based on role
                        $redirect = getRedirectURL($user['role']);
                        $debugInfo .= "Attempting redirect to: " . $redirect . "<br>";
                        
                        if (headers_sent($file, $line)) {
                            $debugInfo .= "ERROR: Headers already sent in $file on line $line<br>";
                        } else {
                            $debugInfo .= "Headers OK - sending redirect<br>";
                            header('Location: ' . $redirect);
                            exit();
                        }
                    }
                } else {
                    $debugInfo .= "LOGIN FAILED<br>";
                    // Failed login
                    recordLoginAttempt($ip, false);
                    logAdminAction('LOGIN_FAILED', "Username: $username | IP: $ip");
                    $loginError = 'Invalid username or password';
                    
                    // Add delay to slow down brute force
                    usleep(rand(500000, 1500000)); // 0.5-1.5 second delay
                }
            } // Close the CSRF validation else block
        }
    } elseif (isset($_POST['verify_2fa'])) {
        // Handle 2FA verification
        $debugInfo .= "Processing 2FA verification<br>";
        
        $ip = getClientIP();
        $code = $_POST['verification_code'] ?? '';
        $backupCode = $_POST['backup_code'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        $debugInfo .= "2FA code length: " . strlen($code) . "<br>";
        $debugInfo .= "Backup code length: " . strlen($backupCode) . "<br>";
        
        // Verify CSRF token
        if (!verifyCSRFToken($csrfToken)) {
            logAdminAction('CSRF_VIOLATION', "IP: $ip | 2FA verification");
            $loginError = 'Security token validation failed. Please try again.';
            $debugInfo .= "❌ 2FA STOPPED: CSRF validation failed<br>";
        } elseif (!isset($_SESSION['awaiting_2fa']) || !isset($_SESSION['temp_username'])) {
            $loginError = '2FA session expired. Please log in again.';
            $debugInfo .= "❌ 2FA STOPPED: No valid 2FA session<br>";
            unset($_SESSION['awaiting_2fa'], $_SESSION['temp_username'], $_SESSION['temp_login_time']);
        } elseif (time() - ($_SESSION['temp_login_time'] ?? 0) > 300) { // 5 minute timeout
            $loginError = '2FA verification timeout. Please log in again.';
            $debugInfo .= "❌ 2FA STOPPED: Timeout<br>";
            unset($_SESSION['awaiting_2fa'], $_SESSION['temp_username'], $_SESSION['temp_login_time']);
            logAdminAction('2FA_TIMEOUT', "Username: {$_SESSION['temp_username']} | IP: $ip");
        } else {
            $username = $_SESSION['temp_username'];
            $verified = false;
            
            // Try verification code first
            if (!empty($code)) {
                $secret = TwoFactorAuth::getUserSecret($username);
                if ($secret && TwoFactorAuth::verifyCode($secret, $code)) {
                    $verified = true;
                    $debugInfo .= "2FA code verified successfully<br>";
                    logAdminAction('2FA_CODE_SUCCESS', "Username: $username | IP: $ip");
                } else {
                    $debugInfo .= "2FA code verification failed<br>";
                }
            }
            
            // Try backup code if main code failed
            if (!$verified && !empty($backupCode)) {
                if (TwoFactorAuth::verifyBackupCode($username, $backupCode)) {
                    $verified = true;
                    $debugInfo .= "Backup code verified successfully<br>";
                    logAdminAction('2FA_BACKUP_CODE_SUCCESS', "Username: $username | IP: $ip");
                } else {
                    $debugInfo .= "Backup code verification failed<br>";
                }
            }
            
            if ($verified) {
                // Get user data from temp session
                $user = $_SESSION['temp_user_data'] ?? null;
                
                if (!$user) {
                    // Fallback: re-verify credentials if temp data lost
                    $user = verifyCredentials($username, ''); // Won't work, but safe fallback
                    if (!$user) {
                        $loginError = 'Session expired. Please log in again.';
                        $step = 'login';
                        unset($_SESSION['awaiting_2fa'], $_SESSION['temp_username'], $_SESSION['temp_login_time'], $_SESSION['temp_user_data']);
                        return;
                    }
                }
                
                // Update last login timestamp in database
                if (isset($user['user_id']) && $user['user_id'] > 0) {
                    updateLastLogin($user['user_id']);
                }
                
                // Complete the login process with new session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['admin_username'] = $username;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['authenticated'] = true;
                $_SESSION['email'] = $user['email'];
                $_SESSION['client_id'] = $user['client_id'] ?? null;
                $_SESSION['login_time'] = time();
                $_SESSION['session_start'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['max_lifetime'] = ini_get('session.gc_maxlifetime');
                $_SESSION['session_token'] = hash_hmac('sha256', $username . $_SESSION['login_time'], CSRF_SECRET);
                
                // Clean up temp session variables
                unset($_SESSION['awaiting_2fa'], $_SESSION['temp_username'], $_SESSION['temp_login_time'], $_SESSION['temp_user_data']);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                logAdminAction('LOGIN_SUCCESS', "Role: {$user['role']} | 2FA verified");
                
                // Redirect based on role
                $redirect = getRedirectURL($user['role']);
                $debugInfo .= "2FA complete - redirecting to: " . $redirect . "<br>";
                
                if (!headers_sent()) {
                    header('Location: ' . $redirect);
                    exit();
                }
            } else {
                recordLoginAttempt($ip, false);
                logAdminAction('2FA_FAILED', "Username: $username | IP: $ip");
                $loginError = 'Invalid verification code. Please try again.';
                $step = '2fa_verify';
                
                // Add delay to slow down brute force
                usleep(rand(500000, 1500000));
            }
        }
    } else {
        $debugInfo .= "No login credentials found in POST data<br>";
    }
} else {
    $debugInfo .= "No POST request detected (method: " . $_SERVER['REQUEST_METHOD'] . ")<br>";
}

// Additional debug info - Check what request method and data we're receiving
$debugInfo .= "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
$debugInfo .= "POST data keys: " . implode(', ', array_keys($_POST)) . "<br>";
$debugInfo .= "GET data keys: " . implode(', ', array_keys($_GET)) . "<br>";
if (!empty($_POST)) {
    $debugInfo .= "POST login isset: " . (isset($_POST['login']) ? 'YES' : 'NO') . "<br>";
    $debugInfo .= "POST submit isset: " . (isset($_POST['submit']) ? 'YES' : 'NO') . "<br>";
}

// Check for messages
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $message = 'You have been successfully logged out.';
            break;
        case 'session_expired':
            $message = 'Your session has expired. Please log in again.';
            break;
    }
}

// If already logged in, redirect to admin index
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Check if we're in a 2FA pending state
if (isset($_SESSION['awaiting_2fa']) && isset($_SESSION['temp_username'])) {
    // Check if session hasn't expired (5 minutes)
    if (time() - ($_SESSION['temp_login_time'] ?? 0) > 300) {
        // Session expired, clean up
        unset($_SESSION['awaiting_2fa'], $_SESSION['temp_username'], $_SESSION['temp_login_time']);
        $message = '2FA verification timeout. Please log in again.';
        $step = 'login';
    } else {
        // Still valid, show 2FA form
        $step = '2fa_verify';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cadman Manufacturing</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #333, #666, #FFD700);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            text-align: center;
            border: 2px solid #FFD700;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 2em;
            font-weight: bold;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }
        
        .login-button {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: black;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid #FFD700;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            background: linear-gradient(145deg, #FFA500, #FFD700);
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .alert-info {
            background: #fff9e6;
            color: #856404;
            border: 2px solid #FFD700;
        }
        
        .company-logo {
            font-size: 3em;
            margin-bottom: 10px;
            color: #FFD700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .security-note {
            margin-top: 30px;
            padding: 15px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            font-size: 12px;
            color: #666;
            border: 1px solid #FFD700;
        }
        
        .back-link {
            margin-top: 20px;
        }
        
        .back-link a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #FFD700;
            text-decoration: underline;
        }
        
        .forgot-password {
            margin-top: 15px;
            text-align: center;
        }
        
        .forgot-password a {
            color: #007cba;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #005a8a;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="company-logo">🏺</div>
            <?php if ($step === '2fa_verify'): ?>
                <h1>Two-Factor Authentication</h1>
                <p>Secure Access Verification</p>
            <?php else: ?>
                <h1>Admin Portal</h1>
                <p>Collections Management System</p>
            <?php endif; ?>
        </div>
        
        <?php if ($loginError): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($loginError); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php /* Debug output - enabled for troubleshooting */ 
        if ($debugInfo): ?>
            <div style="background: #ffffcc; border: 1px solid #ffcc00; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <strong>Debug Info:</strong><br>
                <?php echo $debugInfo; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === '2fa_verify'): ?>
            <!-- 2FA Verification Form -->
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="verification_code">🔐 Enter Authentication Code:</label>
                    <input type="text" id="verification_code" name="verification_code" 
                           placeholder="6-digit code from your authenticator app" 
                           maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code">
                    <small style="color: #666; font-size: 0.9em; margin-top: 5px; display: block;">
                        Enter the 6-digit code from your authenticator app (Google Authenticator, Authy, etc.)
                    </small>
                </div>
                
                <button type="submit" name="verify_2fa" class="login-button">
                    ✅ Verify Code
                </button>
            </form>
            
            <!-- Backup Code Form -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h4 style="margin-bottom: 10px; color: #666;">Use Backup Code Instead</h4>
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="backup_code">🔑 Backup Recovery Code:</label>
                        <input type="text" id="backup_code" name="backup_code" 
                               placeholder="Enter 8-character backup code" 
                               maxlength="8" autocomplete="off">
                        <small style="color: #666; font-size: 0.9em; margin-top: 5px; display: block;">
                            Use one of your saved backup codes if you can't access your authenticator app
                        </small>
                    </div>
                    
                    <button type="submit" name="verify_2fa" class="login-button" style="background: #f39c12;">
                        🔓 Use Backup Code
                    </button>
                </form>
            </div>
            
            <div class="forgot-password">
                <a href="login.php">← Back to Login</a>
            </div>
            
        <?php else: ?>
            <!-- Standard Login Form -->
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" name="login" class="login-button">
                    🔐 Login to Admin Portal
                </button>
            </form>
            
            <div class="forgot-password">
                <a href="password_reset.php">Forgot your password?</a>
            </div>
        <?php endif; ?>
        
        <div class="security-note">
            <strong>Security Notice:</strong><br>
            This is a secure administrative area. All login attempts are logged.
            Access is restricted to authorized personnel only.
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Back to Main Site</a>
        </div>
    </div>
    
    <!-- Admin Login JavaScript -->
    <script src="js/admin-login.js" type="text/javascript"></script>
</body>
</html>
