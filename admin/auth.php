<?php
// Security headers for all admin pages
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');

// Use unified SessionManager for secure session initialization
require_once __DIR__ . '/../includes/SessionManager.php';
SessionManager::getInstance(); // Initializes session with secure settings

// Update last activity timestamp and initialize session data
if (isset($_SESSION['username'])) {
    $_SESSION['last_activity'] = time();
    $_SESSION['session_start'] = $_SESSION['session_start'] ?? time();
    $_SESSION['max_lifetime'] = ini_get('session.gc_maxlifetime');
}

// Load encrypted configuration and database functions
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db_config_encrypted.php';

// Admin system configuration from encrypted environment
define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? 'admin');
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '');
define('SESSION_TIMEOUT', (int)($_ENV['SESSION_TIMEOUT'] ?? 3600));
define('MAX_LOGIN_ATTEMPTS', (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));
define('LOCKOUT_DURATION', (int)($_ENV['LOCKOUT_DURATION'] ?? 900));
define('CSRF_SECRET', $_ENV['SESSION_SECRET_KEY'] ?? 'default_secret');

// Rate limiting storage
$attemptFile = __DIR__ . '/login_attempts.json';

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify user credentials - checks database for both admin and business users
 * Returns user data array or false
 */
function verifyCredentials($username, $password) {
    // Check database users (both admin and business retailers)
    try {
        error_log("verifyCredentials: Checking user '$username'");
        $user = verifyUser($username, $password);
        error_log("verifyCredentials: verifyUser returned " . ($user ? 'SUCCESS' : 'FAILED'));
        if ($user) {
            error_log("verifyCredentials: User role = " . $user['role']);
            return $user;
        }
    } catch (Exception $e) {
        error_log("Database verification error: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
    }
    
    error_log("verifyCredentials: Returning false");
    return false;
}

/**
 * Get redirect URL based on user role
 */
function getRedirectURL($role) {
    if ($role === 'admin') {
        return '/admin/index.php';
    } else {
        // Business users go to retailer dashboard
        return '/user/dashboard.php';
    }
}

// Get client IP (handles proxies)
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    return 'unknown';
}

// Rate limiting functions
function getLoginAttempts($ip) {
    global $attemptFile;
    if (!file_exists($attemptFile)) return [];
    
    $attempts = json_decode(file_get_contents($attemptFile), true) ?: [];
    return $attempts[$ip] ?? [];
}

function recordLoginAttempt($ip, $success = false) {
    global $attemptFile;
    $attempts = [];
    if (file_exists($attemptFile)) {
        $attempts = json_decode(file_get_contents($attemptFile), true) ?: [];
    }
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = [];
    }
    
    $attempts[$ip][] = [
        'time' => time(),
        'success' => $success
    ];
    
    // Clean old attempts (older than lockout duration)
    $cutoff = time() - LOCKOUT_DURATION;
    $attempts[$ip] = array_filter($attempts[$ip], function($attempt) use ($cutoff) {
        return $attempt['time'] > $cutoff;
    });
    
    // Remove empty IP records
    $attempts = array_filter($attempts);
    
    file_put_contents($attemptFile, json_encode($attempts), LOCK_EX);
}

function isIPLocked($ip) {
    $attempts = getLoginAttempts($ip);
    $recentFailures = 0;
    $cutoff = time() - LOCKOUT_DURATION;
    
    foreach ($attempts as $attempt) {
        if ($attempt['time'] > $cutoff && !$attempt['success']) {
            $recentFailures++;
        }
    }
    
    return $recentFailures >= MAX_LOGIN_ATTEMPTS;
}

// Function to check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || 
        !isset($_SESSION['login_time']) || 
        !isset($_SESSION['session_token']) ||
        $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Verify session integrity using the original login time
    $expectedToken = hash_hmac('sha256', $_SESSION['username'] . $_SESSION['login_time'], CSRF_SECRET);
    if (!hash_equals($_SESSION['session_token'], $expectedToken)) {
        session_destroy();
        return false;
    }
    
    return true;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to require admin role
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../user/dashboard.php');
        exit();
    }
}

// Function to log admin actions
function logAdminAction($action, $details = '') {
    $logFile = '/var/log/admin_actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $user = $_SESSION['username'] ?? 'unknown';
    $role = $_SESSION['role'] ?? 'unknown';
    
    $logEntry = "[$timestamp] User: $user ($role) | IP: $ip | Action: $action";
    if ($details) {
        $logEntry .= " | Details: $details";
    }
    $logEntry .= "\n";
    
    // Fallback to local log if system log fails
    if (!@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX)) {
        $localLog = __DIR__ . '/admin_actions.log';
        file_put_contents($localLog, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logAdminAction('LOGOUT');
    session_destroy();
    header('Location: login.php');
    exit();
}

// Function to generate a new password hash (for setup/password changes)
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3          // 3 threads
    ]);
}

// Function to get session data for JavaScript
function getSessionDataForJS() {
    if (!isset($_SESSION['username'])) {
        return null;
    }
    
    $sessionStart = $_SESSION['session_start'] ?? time();
    $maxLifetime = $_SESSION['max_lifetime'] ?? ini_get('session.gc_maxlifetime');
    $currentTime = time();
    $sessionAge = $currentTime - $sessionStart;
    $timeRemaining = max(0, $maxLifetime - $sessionAge);
    
    return [
        'session_start' => $sessionStart,
        'max_lifetime' => (int)$maxLifetime,
        'current_time' => $currentTime,
        'session_age' => $sessionAge,
        'time_remaining' => $timeRemaining,
        'expires_at' => $sessionStart + $maxLifetime,
        'username' => $_SESSION['admin_username']
    ];
}

// Function to render session data script tag
function renderSessionScript() {
    $sessionData = getSessionDataForJS();
    if (!$sessionData) {
        return '';
    }
    
    $jsonData = json_encode($sessionData, JSON_UNESCAPED_SLASHES);
    return "<script>window.adminSessionData = $jsonData;</script>\n";
}
?>
