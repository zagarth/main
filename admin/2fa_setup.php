<?php
require_once 'auth.php';
require_once 'TwoFactorAuth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';
$step = $_GET['step'] ?? 'status';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $username = $_SESSION['admin_username'];
        
        if (isset($_POST['enable_2fa'])) {
            // Step 1: Generate secret and show QR code
            $secret = TwoFactorAuth::generateSecret();
            $_SESSION['temp_2fa_secret'] = $secret;
            $step = 'setup';
            
        } elseif (isset($_POST['verify_setup'])) {
            // Step 2: Verify setup code
            $code = $_POST['verification_code'] ?? '';
            $secret = $_SESSION['temp_2fa_secret'] ?? '';
            
            if (empty($secret)) {
                $error = 'Setup session expired. Please start again.';
                $step = 'status';
            } elseif (TwoFactorAuth::verifyCode($secret, $code)) {
                // Save the secret and generate backup codes
                if (TwoFactorAuth::saveSecret($username, $secret)) {
                    $backupCodes = TwoFactorAuth::generateBackupCodes();
                    TwoFactorAuth::saveBackupCodes($username, $backupCodes);
                    
                    $_SESSION['backup_codes'] = $backupCodes;
                    unset($_SESSION['temp_2fa_secret']);
                    
                    logAdminAction('2FA_ENABLED');
                    $step = 'backup_codes';
                } else {
                    $error = 'Failed to save 2FA configuration. Please try again.';
                }
            } else {
                $error = 'Invalid verification code. Please try again.';
                $step = 'setup';
            }
            
        } elseif (isset($_POST['disable_2fa'])) {
            // Disable 2FA
            $password = $_POST['password'] ?? '';
            
            if (password_verify($password, ADMIN_PASSWORD_HASH)) {
                TwoFactorAuth::removeSecret($username);
                logAdminAction('2FA_DISABLED');
                $message = '2FA has been successfully disabled.';
                $step = 'status';
            } else {
                $error = 'Invalid password. 2FA was not disabled.';
            }
            
        } elseif (isset($_POST['regenerate_backup'])) {
            // Regenerate backup codes
            $password = $_POST['password'] ?? '';
            
            if (password_verify($password, ADMIN_PASSWORD_HASH)) {
                $backupCodes = TwoFactorAuth::generateBackupCodes();
                if (TwoFactorAuth::saveBackupCodes($username, $backupCodes)) {
                    $_SESSION['backup_codes'] = $backupCodes;
                    logAdminAction('2FA_BACKUP_REGENERATED');
                    $step = 'backup_codes';
                } else {
                    $error = 'Failed to regenerate backup codes.';
                }
            } else {
                $error = 'Invalid password. Backup codes were not regenerated.';
            }
        }
    }
}

$is2FAEnabled = TwoFactorAuth::isEnabled($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Setup - Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            background: #f5f5f5;
            color: #333;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #333;
        }
        
        p, span, div, li {
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #333; /* Ensure text is dark */
        }
        
        .step-container {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #FFD700;
            border-radius: 8px;
            background: #fffef7;
            color: #333; /* Ensure text is dark */
        }
        
        .step-container h2, .step-container h3 {
            color: #333; /* Explicit heading color */
        }
        
        .step-container p, .step-container li {
            color: #333; /* Explicit text color */
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .backup-codes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #dc3545;
        }
        
        .backup-code {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            padding: 8px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background: #FFD700;
            color: black;
            border: 2px solid #FFD700;
        }
        
        .btn-primary:hover {
            background: #FFA500;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Two-Factor Authentication</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'status'): ?>
            <div class="step-container">
                <h2>Current Status</h2>
                <p>
                    Two-Factor Authentication: 
                    <span class="status-badge <?php echo $is2FAEnabled ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $is2FAEnabled ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </p>
                
                <?php if (!$is2FAEnabled): ?>
                    <div class="alert alert-warning">
                        <strong>Security Recommendation:</strong> Enable two-factor authentication to add an extra layer of security to your admin account.
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="enable_2fa" class="btn btn-primary">
                            🔒 Enable Two-Factor Authentication
                        </button>
                    </form>
                <?php else: ?>
                    <p>Your account is protected with two-factor authentication.</p>
                    
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="regenerate_backup" class="btn btn-secondary">
                            🔄 Regenerate Backup Codes
                        </button>
                        <div class="form-group" style="margin-top: 10px;">
                            <input type="password" name="password" placeholder="Enter password to confirm" required>
                        </div>
                    </form>
                    
                    <form method="POST" style="display: inline-block; margin-left: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="disable_2fa" class="btn btn-danger" onclick="return confirm('Are you sure you want to disable 2FA? This will reduce your account security.')">
                            ❌ Disable 2FA
                        </button>
                        <div class="form-group" style="margin-top: 10px;">
                            <input type="password" name="password" placeholder="Enter password to confirm" required>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
        <?php elseif ($step === 'setup'): ?>
            <div class="step-container">
                <h2>Step 1: Scan QR Code</h2>
                <p>Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):</p>
                
                <?php
                $secret = $_SESSION['temp_2fa_secret'];
                $qrUrl = TwoFactorAuth::getQRCodeUrl($secret, $_SESSION['admin_username']);
                $qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrUrl);
                ?>
                
                <div class="qr-code">
                    <img src="<?php echo htmlspecialchars($qrCodeApiUrl); ?>" alt="2FA QR Code" style="max-width: 200px;">
                    <p><strong>Manual Entry Key:</strong></p>
                    <code style="font-size: 14px; background: #f8f9fa; padding: 10px; border-radius: 4px; display: block; margin: 10px 0;"><?php echo htmlspecialchars($secret); ?></code>
                </div>
                
                <h3>Step 2: Enter Verification Code</h3>
                <p>Enter the 6-digit code from your authenticator app:</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <input type="text" name="verification_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                    </div>
                    <button type="submit" name="verify_setup" class="btn btn-primary">
                        ✅ Verify & Enable 2FA
                    </button>
                    <a href="?step=status" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
            
        <?php elseif ($step === 'backup_codes'): ?>
            <div class="step-container">
                <h2>🚨 Important: Save Your Backup Codes</h2>
                
                <div class="alert alert-warning">
                    <strong>Critical:</strong> Save these backup codes in a secure location. You can use them to access your account if you lose your authenticator device. Each code can only be used once.
                </div>
                
                <?php if (isset($_SESSION['backup_codes'])): ?>
                    <div class="backup-codes">
                        <?php foreach ($_SESSION['backup_codes'] as $code): ?>
                            <div class="backup-code"><?php echo htmlspecialchars($code); ?></div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p><strong>Instructions:</strong></p>
                    <ul>
                        <li>Print or write down these codes and store them securely</li>
                        <li>Each code can only be used once</li>
                        <li>Use these codes if you lose access to your authenticator app</li>
                        <li>You can regenerate new codes anytime from the 2FA settings</li>
                    </ul>
                    
                    <?php unset($_SESSION['backup_codes']); ?>
                <?php endif; ?>
                
                <a href="?step=status" class="btn btn-primary">Continue to Settings</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="index.php" class="btn btn-secondary">← Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>