<?php
/**
 * Secure Authentication System
 * Designed for easy migration from file-based to SQL database storage
 */

class SecureAuth {
    private $config;
    private $sessionTimeout;
    private $maxLoginAttempts;
    private $lockoutDuration;
    private $csrfSecret;
    
    // File paths for local storage (easily replaceable with DB)
    private $envFile;
    private $attemptsFile;
    private $logFile;
    
    public function __construct() {
        $this->envFile = __DIR__ . '/.env';
        $this->attemptsFile = __DIR__ . '/.login_attempts';
        $this->logFile = __DIR__ . '/admin_security.log';
        
        $this->loadConfig();
        $this->initSession();
    }
    
    /**
     * Load configuration from .env file
     * TODO: Replace with database query when migrating to SQL
     */
    private function loadConfig() {
        $this->config = [];
        
        if (file_exists($this->envFile)) {
            $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                    [$key, $value] = explode('=', $line, 2);
                    $this->config[trim($key)] = trim($value);
                }
            }
        }
        
        // Set defaults with secure values
        $this->sessionTimeout = (int)($this->config['SESSION_TIMEOUT'] ?? 3600);
        $this->maxLoginAttempts = (int)($this->config['MAX_LOGIN_ATTEMPTS'] ?? 5);
        $this->lockoutDuration = (int)($this->config['LOCKOUT_DURATION'] ?? 900);
        $this->csrfSecret = $this->config['CSRF_SECRET'] ?? 'default_secret_change_me';
    }
    
    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration']) || 
                time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Authenticate user credentials
     * TODO: Replace file lookup with SQL query
     */
    public function authenticate($username, $password, $csrfToken = null) {
        // Verify CSRF token
        if (!$this->validateCSRF($csrfToken)) {
            $this->logSecurityEvent('CSRF_VALIDATION_FAILED', $username);
            return ['success' => false, 'error' => 'Security token validation failed'];
        }
        
        // Check rate limiting
        if ($this->isIPLocked()) {
            $this->logSecurityEvent('IP_LOCKED_ATTEMPT', $username);
            return ['success' => false, 'error' => 'Too many failed attempts. Please try again later.'];
        }
        
        // Get stored credentials (from file now, SQL later)
        $storedUsername = $this->config['ADMIN_USERNAME'] ?? '';
        $storedPasswordHash = $this->config['ADMIN_PASSWORD_HASH'] ?? '';
        
        // Timing-safe comparison for username
        $usernameValid = hash_equals($storedUsername, $username);
        
        // Verify password hash
        $passwordValid = false;
        if ($storedPasswordHash) {
            $passwordValid = password_verify($password, $storedPasswordHash);
        }
        
        if ($usernameValid && $passwordValid) {
            // Success - clear failed attempts
            $this->clearFailedAttempts();
            $this->createSession($username);
            $this->logSecurityEvent('LOGIN_SUCCESS', $username);
            
            return ['success' => true, 'redirect' => 'index.php'];
        } else {
            // Failed - record attempt
            $this->recordFailedAttempt();
            $this->logSecurityEvent('LOGIN_FAILED', $username);
            
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    }
    
    /**
     * Create authenticated session
     */
    private function createSession($username) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_logged_in']) || 
            !isset($_SESSION['login_time']) || 
            $_SESSION['admin_logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['admin_username'])) {
            $this->logSecurityEvent('LOGOUT', $_SESSION['admin_username']);
        }
        
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRF() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_time'] = time();
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    private function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_time'])) {
            return false;
        }
        
        // Token expires after 1 hour
        if (time() - $_SESSION['csrf_time'] > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting - check if IP is locked
     * TODO: Replace with database table for distributed systems
     */
    private function isIPLocked() {
        $ip = $this->getClientIP();
        $attempts = $this->getFailedAttempts();
        
        if (!isset($attempts[$ip])) {
            return false;
        }
        
        $ipData = $attempts[$ip];
        
        // Check if lockout period has expired
        if (time() - $ipData['last_attempt'] > $this->lockoutDuration) {
            $this->clearFailedAttempts($ip);
            return false;
        }
        
        return $ipData['count'] >= $this->maxLoginAttempts;
    }
    
    /**
     * Record failed login attempt
     * TODO: Replace with database insert
     */
    private function recordFailedAttempt() {
        $ip = $this->getClientIP();
        $attempts = $this->getFailedAttempts();
        
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
        
        file_put_contents($this->attemptsFile, json_encode($attempts, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get failed attempts data
     * TODO: Replace with database query
     */
    private function getFailedAttempts() {
        if (!file_exists($this->attemptsFile)) {
            return [];
        }
        
        $data = file_get_contents($this->attemptsFile);
        return json_decode($data, true) ?: [];
    }
    
    /**
     * Clear failed attempts for IP
     * TODO: Replace with database delete
     */
    private function clearFailedAttempts($ip = null) {
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        $attempts = $this->getFailedAttempts();
        unset($attempts[$ip]);
        
        file_put_contents($this->attemptsFile, json_encode($attempts, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log security events
     * TODO: Replace with database logging table
     */
    private function logSecurityEvent($event, $username = '', $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Hash password (for setup/password changes)
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Get user info (for database migration preparation)
     * TODO: Replace with user table query
     */
    public function getUserInfo() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'username' => $_SESSION['admin_username'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity'],
            'session_token' => $_SESSION['session_token'] ?? null
        ];
    }
    
    /**
     * Database migration helper - get current config for export
     */
    public function exportConfigForDatabase() {
        return [
            'username' => $this->config['ADMIN_USERNAME'] ?? '',
            'password_hash' => $this->config['ADMIN_PASSWORD_HASH'] ?? '',
            'settings' => [
                'session_timeout' => $this->sessionTimeout,
                'max_login_attempts' => $this->maxLoginAttempts,
                'lockout_duration' => $this->lockoutDuration
            ]
        ];
    }
}
?>
