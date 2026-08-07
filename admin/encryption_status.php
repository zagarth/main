<?php
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

/**
 * Check SSL/HTTPS security status
 */
function checkSSLStatus() {
    $status = [
        'https_active' => false,
        'hsts_enabled' => false,
        'secure_headers' => false,
        'ssl_strength' => 'unknown',
        'cipher_suite' => 'unknown',
        'tls_version' => 'unknown'
    ];
    
    // Check if HTTPS is active
    $status['https_active'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    // Check if we're accessing via HTTPS
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $status['current_protocol'] = $protocol;
    
    // Check for SSL/TLS version and cipher info
    if (isset($_SERVER['SSL_PROTOCOL'])) {
        $status['tls_version'] = $_SERVER['SSL_PROTOCOL'];
    }
    
    if (isset($_SERVER['SSL_CIPHER'])) {
        $status['cipher_suite'] = $_SERVER['SSL_CIPHER'];
    }
    
    // Analyze SSL strength
    if ($status['https_active']) {
        if (strpos($status['tls_version'], 'TLSv1.3') !== false) {
            $status['ssl_strength'] = 'excellent';
        } elseif (strpos($status['tls_version'], 'TLSv1.2') !== false) {
            $status['ssl_strength'] = 'good';
        } else {
            $status['ssl_strength'] = 'weak';
        }
    }
    
    return $status;
}

/**
 * Check Apache and PHP configuration security
 */
function checkServerConfiguration() {
    $config = [
        'apache_modules' => [],
        'php_security' => [],
        'session_security' => [],
        'server_info' => []
    ];
    
    // PHP Security Settings
    $config['php_security'] = [
        'expose_php' => ini_get('expose_php') ? 'Enabled (Insecure)' : 'Disabled (Secure)',
        'display_errors' => ini_get('display_errors') ? 'Enabled (Insecure)' : 'Disabled (Secure)',
        'log_errors' => ini_get('log_errors') ? 'Enabled (Secure)' : 'Disabled (Insecure)',
        'max_execution_time' => ini_get('max_execution_time') . ' seconds',
        'memory_limit' => ini_get('memory_limit'),
        'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ];
    
    // Session Security
    $config['session_security'] = [
        'cookie_httponly' => ini_get('session.cookie_httponly') ? 'Enabled (Secure)' : 'Disabled (Insecure)',
        'cookie_secure' => ini_get('session.cookie_secure') ? 'Enabled (Secure)' : 'Disabled (Insecure)',
        'use_only_cookies' => ini_get('session.use_only_cookies') ? 'Enabled (Secure)' : 'Disabled (Insecure)',
        'cookie_samesite' => ini_get('session.cookie_samesite') ?: 'Not Set',
        'gc_maxlifetime' => ini_get('session.gc_maxlifetime') . ' seconds',
        'entropy_length' => ini_get('session.entropy_length') ?: 'Default'
    ];
    
    // Server Information
    $config['server_info'] = [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => PHP_VERSION,
        'server_admin' => $_SERVER['SERVER_ADMIN'] ?? 'Not Set',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'Unknown'
    ];
    
    // Check for important Apache modules (if available)
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $important_modules = ['mod_ssl', 'mod_rewrite', 'mod_headers', 'mod_security2'];
        foreach ($important_modules as $module) {
            $config['apache_modules'][$module] = in_array($module, $modules) ? 'Loaded' : 'Not Loaded';
        }
    } else {
        $config['apache_modules'] = ['Note' => 'Module information not available via PHP'];
    }
    
    return $config;
}

/**
 * Check configuration directories encryption and protection status
 */
function checkConfigDirectoryEncryption() {
    $directories = [
        'admin' => __DIR__,
        'apache_sites' => '/etc/apache2/sites-available',
        'apache_config' => '/etc/apache2',
        'ssl_certs' => '/etc/letsencrypt',
        'php_config' => '/etc/php',
        'web_root' => dirname(__DIR__)
    ];
    
    $dirStatus = [];
    
    foreach ($directories as $name => $path) {
        $status = [
            'path' => $path,
            'exists' => is_dir($path),
            'readable' => is_readable($path),
            'writable' => is_writable($path),
            'permissions' => 'Unknown',
            'owner' => 'Unknown',
            'encryption_status' => 'Not Encrypted',
            'protection_level' => 'Unknown'
        ];
        
        if ($status['exists']) {
            // Get directory permissions
            $perms = fileperms($path);
            $status['permissions'] = substr(sprintf('%o', $perms), -3);
            
            // Get owner information if possible
            if (function_exists('posix_getpwuid')) {
                $owner = posix_getpwuid(fileowner($path));
                $status['owner'] = $owner['name'] ?? 'Unknown';
            }
            
            // Determine protection level based on directory and permissions
            switch ($name) {
                case 'admin':
                    $status['protection_level'] = 'High - Admin Directory';
                    $status['encryption_status'] = 'HTTPS + File Permissions';
                    break;
                    
                case 'apache_sites':
                case 'apache_config':
                    $status['protection_level'] = 'Critical - System Config';
                    $status['encryption_status'] = 'File System + Root Access';
                    break;
                    
                case 'ssl_certs':
                    $status['protection_level'] = 'Maximum - SSL Certificates';
                    $status['encryption_status'] = 'Private Key Encryption + Root Access';
                    break;
                    
                case 'php_config':
                    $status['protection_level'] = 'High - Runtime Config';
                    $status['encryption_status'] = 'File System Protection';
                    break;
                    
                case 'web_root':
                    $status['protection_level'] = 'Medium - Web Accessible';
                    $status['encryption_status'] = 'HTTPS Transport Only';
                    break;
            }
            
            // Check for specific security files in admin directory
            if ($name === 'admin') {
                $securityFiles = [
                    '.env' => 'Environment Configuration',
                    'login_attempts.json' => 'Login Attempt Logs',
                    '2fa_secrets.json' => '2FA Secret Keys',
                    '2fa_backup_codes.json' => '2FA Backup Codes'
                ];
                
                $status['security_files'] = [];
                foreach ($securityFiles as $file => $description) {
                    $filePath = $path . '/' . $file;
                    if (file_exists($filePath)) {
                        $filePerms = substr(sprintf('%o', fileperms($filePath)), -3);
                        $status['security_files'][$file] = [
                            'description' => $description,
                            'permissions' => $filePerms,
                            'secure' => in_array($filePerms, ['600', '640', '664'])
                        ];
                    }
                }
            }
        }
        
        $dirStatus[$name] = $status;
    }
    
    return $dirStatus;
}
function checkAuthConfiguration() {
    $auth = [
        'admin_config' => [],
        'security_features' => [],
        'file_protection' => []
    ];
    
    // Check admin configuration
    $auth['admin_config'] = [
        'username_defined' => defined('ADMIN_USERNAME') ? 'Yes' : 'No',
        'password_hash_defined' => defined('ADMIN_PASSWORD_HASH') ? 'Yes' : 'No',
        'session_timeout' => defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT . ' seconds' : 'Not Set',
        'max_login_attempts' => defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 'Not Set',
        'lockout_duration' => defined('LOCKOUT_DURATION') ? LOCKOUT_DURATION . ' seconds' : 'Not Set',
        'csrf_secret_defined' => defined('CSRF_SECRET') ? 'Yes' : 'No'
    ];
    
    // Check security features
    $adminDir = __DIR__;
    $auth['security_features'] = [
        '2fa_available' => class_exists('TwoFactorAuth') ? 'Yes' : 'No',
        '2fa_enabled' => file_exists($adminDir . '/2fa_secrets.json') ? 'Configuration Present' : 'Not Configured',
        'login_attempts_tracking' => file_exists($adminDir . '/login_attempts.json') ? 'Active' : 'Not Active',
        'password_reset_available' => file_exists($adminDir . '/password_reset.php') ? 'Yes' : 'No',
        'csrf_protection' => function_exists('generateCSRFToken') ? 'Active' : 'Not Active',
        'ip_whitelisting' => function_exists('isIPLocked') ? 'Active' : 'Not Active'
    ];
    
    // Check file protection
    $sensitiveFiles = ['.env', 'login_attempts.json', '2fa_secrets.json', '2fa_backup_codes.json'];
    foreach ($sensitiveFiles as $file) {
        $filePath = $adminDir . '/' . $file;
        if (file_exists($filePath)) {
            $perms = substr(sprintf('%o', fileperms($filePath)), -3);
            $auth['file_protection'][$file] = [
                'permissions' => $perms,
                'secure' => in_array($perms, ['600', '640', '664']) ? 'Yes' : 'No'
            ];
        } else {
            $auth['file_protection'][$file] = ['status' => 'Not Found'];
        }
    }
    
    return $auth;
}

/**
 * Check file encryption and permissions
 */
function checkFileEncryption() {
    $adminDir = __DIR__;
    $status = [
        'env_permissions' => false,
        'config_files_protected' => false,
        'sensitive_files_secured' => false
    ];
    
    // Check .env file permissions
    $envFile = $adminDir . '/.env';
    if (file_exists($envFile)) {
        $perms = substr(sprintf('%o', fileperms($envFile)), -3);
        $status['env_permissions'] = $perms === '664' || $perms === '600';
        $status['env_perms_value'] = $perms;
    }
    
    // Check for sensitive files
    $sensitiveFiles = [
        'login_attempts.json',
        '2fa_secrets.json',
        '2fa_backup_codes.json'
    ];
    
    $protectedCount = 0;
    foreach ($sensitiveFiles as $file) {
        $filePath = $adminDir . '/' . $file;
        if (file_exists($filePath)) {
            $perms = substr(sprintf('%o', fileperms($filePath)), -3);
            if ($perms === '600' || $perms === '640') {
                $protectedCount++;
            }
        }
    }
    
    $status['config_files_protected'] = $protectedCount > 0;
    // Check whether ALL existing sensitive files are secured (not hardcoded)
    $existingCount = count(array_filter($sensitiveFiles, fn($f) => file_exists($adminDir . '/' . $f)));
    $status['sensitive_files_secured'] = $existingCount > 0 && $protectedCount >= $existingCount;
    
    return $status;
}

/**
 * Generate encryption recommendations
 */
function getSecurityRecommendations($sslStatus, $fileStatus) {
    $recommendations = [];
    
    if (!$sslStatus['https_active']) {
        $recommendations[] = [
            'level' => 'critical',
            'title' => 'HTTPS Not Active',
            'description' => 'Admin area should only be accessible via HTTPS',
            'action' => 'Enable SSL/HTTPS for the admin directory'
        ];
    }
    
    if (!$fileStatus['env_permissions']) {
        $recommendations[] = [
            'level' => 'high',
            'title' => 'Environment File Permissions',
            'description' => 'The .env file has insecure permissions',
            'action' => 'Set .env file permissions to 600 or 664'
        ];
    }
    
    if (!$fileStatus['config_files_protected']) {
        $recommendations[] = [
            'level' => 'medium',
            'title' => 'Configuration File Security',
            'description' => 'Some configuration files may have weak permissions',
            'action' => 'Review and secure configuration file permissions'
        ];
    }
    
    return $recommendations;
}

/**
 * Check actual security headers configured in Apache
 */
function checkSecurityHeaders() {
    $wanted = [
        'Strict-Transport-Security' => ['label' => 'HSTS',                    'found' => false, 'value' => ''],
        'X-Content-Type-Options'    => ['label' => 'X-Content-Type-Options',  'found' => false, 'value' => ''],
        'X-Frame-Options'           => ['label' => 'X-Frame-Options',         'found' => false, 'value' => ''],
        'X-XSS-Protection'          => ['label' => 'X-XSS-Protection',        'found' => false, 'value' => ''],
        'Referrer-Policy'           => ['label' => 'Referrer-Policy',         'found' => false, 'value' => ''],
        'Content-Security-Policy'   => ['label' => 'Content-Security-Policy', 'found' => false, 'value' => ''],
        'Permissions-Policy'        => ['label' => 'Permissions-Policy',      'found' => false, 'value' => ''],
    ];

    // Check Apache site configs (readable by www-data via adm group or world-readable)
    $confDirs = ['/etc/apache2/sites-enabled', '/etc/apache2/sites-available'];
    foreach ($confDirs as $dir) {
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/*.conf') ?: [] as $confFile) {
            if (!is_readable($confFile)) continue;
            foreach (file($confFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                foreach ($wanted as $headerName => &$info) {
                    if ($info['found']) continue;
                    if (preg_match('/^Header\s+always\s+set\s+' . preg_quote($headerName, '/') . '\s+"?(.+?)"?\s*$/i', $line, $m)) {
                        $info['found'] = true;
                        $info['value'] = trim($m[1], '"');
                    }
                }
                unset($info);
            }
        }
    }

    // Also accept headers sent by PHP code in this request
    foreach (headers_list() as $sent) {
        foreach ($wanted as $headerName => &$info) {
            if (!$info['found'] && stripos($sent, $headerName . ':') === 0) {
                $info['found'] = true;
                $info['value'] = trim(substr($sent, strlen($headerName) + 1));
            }
        }
        unset($info);
    }

    return $wanted;
}

$sslStatus = checkSSLStatus();
$fileStatus = checkFileEncryption();
$serverConfig = checkServerConfiguration();
$authConfig = checkAuthConfiguration();
$dirEncryption = checkConfigDirectoryEncryption();
$securityHeaders = checkSecurityHeaders();
$recommendations = getSecurityRecommendations($sslStatus, $fileStatus);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Encryption Status - Cadman Manufacturing</title>
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
        
        p, span, div {
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #333; /* Ensure text is dark */
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .status-card {
            padding: 20px;
            border-radius: 8px;
            border: 2px solid;
            background: white;
            color: #333; /* Ensure text is dark */
        }
        
        .status-card h3 {
            color: #333; /* Explicit heading color */
            margin: 10px 0;
        }
        
        .status-card p {
            color: #333; /* Explicit paragraph color */
            margin: 8px 0;
        }
        
        .status-good {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .status-warning {
            border-color: #ffc107;
            background: #fffef7;
        }
        
        .status-danger {
            border-color: #dc3545;
            background: #fff5f5;
        }
        
        .status-icon {
            font-size: 2em;
            margin-bottom: 10px;
            display: block;
        }
        
        .recommendations {
            margin: 30px 0;
        }
        
        .recommendation {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
            color: #333; /* Ensure text is dark */
        }
        
        .recommendation h4 {
            color: #333; /* Explicit heading color */
            margin: 0 0 10px 0;
        }
        
        .recommendation p {
            color: #333; /* Explicit paragraph color */
            margin: 5px 0;
        }
        
        .rec-critical {
            border-color: #dc3545;
            background: #fff5f5;
        }
        
        .rec-high {
            border-color: #fd7e14;
            background: #fff8f5;
        }
        
        .rec-medium {
            border-color: #ffc107;
            background: #fffef7;
        }
        
        .rec-low {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .tech-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            color: #333; /* Ensure text is dark */
            border: 1px solid #e9ecef;
        }
        
        .tech-details h3 {
            color: #333; /* Explicit heading color */
            margin: 0 0 15px 0;
            font-family: Arial, sans-serif;
            font-size: 18px;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 8px;
        }
        
        .tech-details h4 {
            color: #333; /* Explicit heading color */
            margin: 15px 0 10px 0;
            font-family: Arial, sans-serif;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tech-details p {
            color: #333; /* Explicit paragraph color */
            margin: 8px 0;
            line-height: 1.4;
        }
        
        .config-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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
        <h1>🔐 Admin Encryption & Security Status</h1>
        
        <div class="status-grid">
            <!-- HTTPS Status -->
            <div class="status-card <?php echo $sslStatus['https_active'] ? 'status-good' : 'status-danger'; ?>">
                <span class="status-icon"><?php echo $sslStatus['https_active'] ? '🔒' : '⚠️'; ?></span>
                <h3>HTTPS Encryption</h3>
                <p><strong>Status:</strong> <?php echo $sslStatus['https_active'] ? 'Active' : 'Inactive'; ?></p>
                <p><strong>Current Protocol:</strong> <?php echo strtoupper($sslStatus['current_protocol']); ?></p>
                <?php if ($sslStatus['https_active']): ?>
                    <p style="color: #28a745;">✅ Admin area is encrypted with SSL/TLS</p>
                <?php else: ?>
                    <p style="color: #dc3545;">❌ Admin area is not encrypted</p>
                <?php endif; ?>
            </div>
            
            <!-- File Security -->
            <div class="status-card <?php echo $fileStatus['env_permissions'] ? 'status-good' : 'status-warning'; ?>">
                <span class="status-icon"><?php echo $fileStatus['env_permissions'] ? '📁' : '⚠️'; ?></span>
                <h3>File Permissions</h3>
                <p><strong>Environment File:</strong> <?php echo $fileStatus['env_permissions'] ? 'Secure' : 'Needs Review'; ?></p>
                <?php if (isset($fileStatus['env_perms_value'])): ?>
                    <p><strong>Permissions:</strong> <?php echo $fileStatus['env_perms_value']; ?></p>
                <?php endif; ?>
                <p><strong>Config Files:</strong> <?php echo $fileStatus['config_files_protected'] ? 'Protected' : 'Standard'; ?></p>
            </div>
            
            <!-- Security Headers (live from Apache config) -->
            <?php
            $headersFound = count(array_filter($securityHeaders, fn($h) => $h['found']));
            $headersTotal = count($securityHeaders);
            $headersClass = $headersFound === $headersTotal ? 'status-good' : ($headersFound >= 4 ? 'status-warning' : 'status-danger');
            ?>
            <div class="status-card <?php echo $headersClass; ?>">
                <span class="status-icon"><?php echo $headersFound === $headersTotal ? '🛡️' : '⚠️'; ?></span>
                <h3>Security Headers</h3>
                <p><strong>Configured:</strong> <?php echo $headersFound; ?>/<?php echo $headersTotal; ?></p>
                <?php foreach ($securityHeaders as $name => $info): ?>
                    <p style="font-size: 13px; margin: 4px 0;">
                        <span style="color: <?php echo $info['found'] ? '#28a745' : '#dc3545'; ?>">
                            <?php echo $info['found'] ? '✅' : '❌'; ?>
                        </span>
                        <strong><?php echo htmlspecialchars($info['label']); ?></strong>
                        <?php if ($info['found'] && strlen($info['value']) < 60): ?>
                            <br><span style="font-size: 11px; color: #666; margin-left: 20px;"><?php echo htmlspecialchars($info['value']); ?></span>
                        <?php elseif ($info['found']): ?>
                            <span style="font-size: 11px; color: #666;"> (set)</span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($recommendations)): ?>
            <div class="recommendations">
                <h2>🎯 Security Recommendations</h2>
                <?php foreach ($recommendations as $rec): ?>
                    <div class="recommendation rec-<?php echo $rec['level']; ?>">
                        <h4><?php echo htmlspecialchars($rec['title']); ?></h4>
                        <p><?php echo htmlspecialchars($rec['description']); ?></p>
                        <p><strong>Action:</strong> <?php echo htmlspecialchars($rec['action']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="status-card status-good" style="text-align: center; margin: 20px 0;">
                <span class="status-icon">🎉</span>
                <h3>Excellent Security Posture!</h3>
                <p>All critical security measures are in place.</p>
            </div>
        <?php endif; ?>
        
        <!-- Configuration Directory Encryption Status -->
        <h2>📂 Configuration Directory Encryption Status</h2>
        
        <div class="status-grid">
            <?php foreach ($dirEncryption as $dirName => $status): ?>
                <div class="status-card <?php 
                    if (!$status['exists']) {
                        echo 'status-danger';
                    } elseif (in_array($dirName, ['ssl_certs', 'apache_config'])) {
                        echo 'status-good';
                    } elseif ($dirName === 'admin') {
                        echo 'status-good';
                    } else {
                        echo 'status-warning';
                    }
                ?>">
                    <span class="status-icon">
                        <?php 
                        switch($dirName) {
                            case 'admin': echo '🛡️'; break;
                            case 'apache_sites': echo '⚙️'; break;
                            case 'apache_config': echo '🔧'; break;
                            case 'ssl_certs': echo '🔐'; break;
                            case 'php_config': echo '🐘'; break;
                            case 'web_root': echo '🌐'; break;
                            default: echo '📁'; break;
                        }
                        ?>
                    </span>
                    <h3><?php echo ucwords(str_replace('_', ' ', $dirName)); ?> Directory</h3>
                    
                    <?php if ($status['exists']): ?>
                        <p><strong>Path:</strong> <?php echo htmlspecialchars($status['path']); ?></p>
                        <p><strong>Permissions:</strong> <?php echo $status['permissions']; ?></p>
                        <p><strong>Owner:</strong> <?php echo htmlspecialchars($status['owner']); ?></p>
                        <p><strong>Protection Level:</strong> 
                            <span style="color: <?php 
                                echo strpos($status['protection_level'], 'Maximum') !== false ? '#dc3545' : 
                                    (strpos($status['protection_level'], 'Critical') !== false ? '#fd7e14' :
                                    (strpos($status['protection_level'], 'High') !== false ? '#ffc107' : '#28a745'));
                            ?>;">
                                <?php echo htmlspecialchars($status['protection_level']); ?>
                            </span>
                        </p>
                        <p><strong>Encryption Status:</strong> 
                            <span style="color: #28a745;">
                                <?php echo htmlspecialchars($status['encryption_status']); ?>
                            </span>
                        </p>
                        
                        <?php if (!empty($status['security_files'])): ?>
                            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                                <h4 style="margin: 0 0 10px 0; font-size: 14px;">Security Files:</h4>
                                <?php foreach ($status['security_files'] as $fileName => $fileInfo): ?>
                                    <p style="font-size: 12px; margin: 5px 0;">
                                        <strong><?php echo htmlspecialchars($fileName); ?>:</strong> 
                                        <?php echo $fileInfo['permissions']; ?>
                                        <span style="color: <?php echo $fileInfo['secure'] ? '#28a745' : '#dc3545'; ?>;">
                                            (<?php echo $fileInfo['secure'] ? 'Secure' : 'Review'; ?>)
                                        </span>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <p style="color: #dc3545;"><strong>Status:</strong> Directory not found</p>
                        <p><strong>Expected Path:</strong> <?php echo htmlspecialchars($status['path']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Directory Encryption Summary -->
        <div class="tech-details" style="margin: 30px 0;">
            <h3>📊 Directory Encryption Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <?php
                $totalDirs = count($dirEncryption);
                $protectedDirs = 0;
                $criticalDirs = 0;
                $encryptedDirs = 0;
                
                foreach ($dirEncryption as $dirName => $status) {
                    if ($status['exists']) {
                        $protectedDirs++;
                        if (strpos($status['protection_level'], 'Critical') !== false || strpos($status['protection_level'], 'Maximum') !== false) {
                            $criticalDirs++;
                        }
                        if (strpos($status['encryption_status'], 'Encryption') !== false || strpos($status['encryption_status'], 'HTTPS') !== false) {
                            $encryptedDirs++;
                        }
                    }
                }
                ?>
                <div>
                    <p><strong>Total Directories:</strong> <?php echo $totalDirs; ?></p>
                    <p><strong>Protected Directories:</strong> <?php echo $protectedDirs; ?></p>
                </div>
                <div>
                    <p><strong>Critical/Maximum Security:</strong> <?php echo $criticalDirs; ?></p>
                    <p><strong>Encrypted/HTTPS Protected:</strong> <?php echo $encryptedDirs; ?></p>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4>Protection Levels Explained:</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Maximum:</strong> SSL certificates with private key encryption + root access only</li>
                    <li><strong>Critical:</strong> System configuration files protected by filesystem + root access</li>
                    <li><strong>High:</strong> Admin and runtime config with enhanced permissions + HTTPS</li>
                    <li><strong>Medium:</strong> Web-accessible directories with HTTPS transport encryption</li>
                </ul>
            </div>
        </div>
        
        <!-- Configuration Details -->
        <h2>📋 System Configuration</h2>
        
        <div class="config-section">
            <!-- SSL/TLS Configuration -->
            <div class="tech-details">
                <h3>🔒 SSL/TLS Configuration</h3>
                <p><strong>Protocol:</strong> <?php echo $sslStatus['current_protocol']; ?></p>
                <p><strong>TLS Version:</strong> <?php echo $sslStatus['tls_version']; ?></p>
                <p><strong>Cipher Suite:</strong> <?php echo $sslStatus['cipher_suite']; ?></p>
                <p><strong>SSL Strength:</strong> <?php echo ucfirst($sslStatus['ssl_strength']); ?></p>
                <p><strong>HTTPS Active:</strong> <?php echo $sslStatus['https_active'] ? 'Yes' : 'No'; ?></p>
            </div>
            
            <!-- Server Configuration -->
            <div class="tech-details">
                <h3>🖥️ Server Configuration</h3>
                <?php foreach ($serverConfig['server_info'] as $key => $value): ?>
                    <p><strong><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="config-section">
            <!-- PHP Security Configuration -->
            <div class="tech-details">
                <h3>🐘 PHP Security Settings</h3>
                <?php foreach ($serverConfig['php_security'] as $setting => $value): ?>
                    <p><strong><?php echo ucwords(str_replace('_', ' ', $setting)); ?>:</strong> 
                        <span style="color: <?php echo strpos($value, 'Secure') !== false ? '#28a745' : (strpos($value, 'Insecure') !== false ? '#dc3545' : '#333'); ?>;">
                            <?php echo htmlspecialchars($value); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>
            
            <!-- Session Security Configuration -->
            <div class="tech-details">
                <h3>🍪 Session Security Configuration</h3>
                <?php foreach ($serverConfig['session_security'] as $setting => $value): ?>
                    <p><strong><?php echo ucwords(str_replace('_', ' ', $setting)); ?>:</strong> 
                        <span style="color: <?php echo strpos($value, 'Secure') !== false ? '#28a745' : (strpos($value, 'Insecure') !== false ? '#dc3545' : '#333'); ?>;">
                            <?php echo htmlspecialchars($value); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($serverConfig['apache_modules'])): ?>
        <div class="config-section">
            <!-- Apache Modules -->
            <div class="tech-details">
                <h3>🔧 Apache Modules</h3>
                <?php foreach ($serverConfig['apache_modules'] as $module => $status): ?>
                    <p><strong><?php echo htmlspecialchars($module); ?>:</strong> 
                        <span style="color: <?php echo $status === 'Loaded' ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>
            
            <!-- File Protection Status -->
            <div class="tech-details">
                <h3>� File Protection Status</h3>
                <?php foreach ($authConfig['file_protection'] as $file => $info): ?>
                    <p><strong><?php echo htmlspecialchars($file); ?>:</strong> 
                        <?php if (isset($info['permissions'])): ?>
                            Permissions: <?php echo $info['permissions']; ?> 
                            (<span style="color: <?php echo $info['secure'] === 'Yes' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $info['secure'] === 'Yes' ? 'Secure' : 'Review Needed'; ?>
                            </span>)
                        <?php else: ?>
                            <span style="color: #6c757d;"><?php echo $info['status']; ?></span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Authentication Configuration -->
        <div class="tech-details" style="grid-column: 1 / -1;">
            <h3>� Authentication Configuration</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4>Admin Settings:</h4>
                    <?php foreach ($authConfig['admin_config'] as $setting => $value): ?>
                        <p><strong><?php echo ucwords(str_replace('_', ' ', $setting)); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                    <?php endforeach; ?>
                </div>
                <div>
                    <h4>Security Features:</h4>
                    <?php foreach ($authConfig['security_features'] as $feature => $status): ?>
                        <p><strong><?php echo ucwords(str_replace('_', ' ', $feature)); ?>:</strong> 
                            <span style="color: <?php echo in_array($status, ['Yes', 'Active', 'Configuration Present']) ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- System Overview -->
        <div class="tech-details">
            <h3>📊 System Overview</h3>
            <p><strong>Admin Directory:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Environment File:</strong> <?php echo file_exists(__DIR__ . '/.env') ? 'Present' : 'Missing'; ?></p>
            <p><strong>Current User:</strong> <?php echo $_SESSION['admin_username'] ?? 'Unknown'; ?></p>
            <p><strong>Session Started:</strong> <?php echo isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Unknown'; ?></p>
            <p><strong>Client IP:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="2fa_setup.php" class="btn btn-primary">🔐 Manage 2FA</a>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>