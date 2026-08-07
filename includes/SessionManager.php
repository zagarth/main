<?php
/**
 * Session Management Class
 * Provides secure session handling and encapsulation
 * Cadman Manufacturing - Security Framework
 */

class SessionManager {
    
    private static $instance = null;
    
    private function __construct() {
        $this->initializeSession();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize secure session
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Enhanced session security configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1 hour session timeout
            
            session_start();
            
            // Initialize session security if new session
            if (!isset($_SESSION['session_token'])) {
                $this->regenerateSession();
            }
        }
    }
    
    /**
     * Regenerate session ID for security
     */
    public function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_start'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if session is valid
     */
    public function isValidSession() {
        if (!isset($_SESSION['session_token']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check session timeout (1 hour)
        if ((time() - $_SESSION['last_activity']) > 3600) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Safely set session value
     */
    public function set($key, $value) {
        $this->isValidSession(); // Validate session first
        $_SESSION[htmlspecialchars($key, ENT_QUOTES, 'UTF-8')] = $value;
    }
    
    /**
     * Safely get session value with HTML encoding for output
     */
    public function get($key, $default = null, $htmlEncode = true) {
        $this->isValidSession(); // Validate session first
        $value = $_SESSION[$key] ?? $default;
        
        if ($value !== null && $htmlEncode && is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }
    
    /**
     * Get raw session value (use with caution)
     */
    public function getRaw($key, $default = null) {
        $this->isValidSession();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has($key) {
        $this->isValidSession();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public function remove($key) {
        $this->isValidSession();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy entire session
     */
    public function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get admin username safely for display
     */
    public function getAdminUsername() {
        $username = $this->getRaw('admin_username', null);
        if ($username === null || $username === '') {
            $username = $this->getRaw('username', null);
        }
        if ($username === null || $username === '') {
            return 'Unknown Admin';
        }
        return (string) $username;
    }
    
    /**
     * Check if user is authenticated admin
     */
    public function isAuthenticatedAdmin() {
        return $this->has('admin_username') && $this->has('authenticated') && $this->getRaw('authenticated') === true;
    }
    
    /**
     * Set admin authentication
     */
    public function setAdminAuth($username, $role = 'admin') {
        $this->regenerateSession(); // New session for security
        $this->set('admin_username', $username);
        $this->set('username', $username);
        $this->set('user_role', $role);
        $this->set('authenticated', true);
        $this->set('login_time', time());
    }
    
    /**
     * Get session info for display
     */
    public function getSessionInfo() {
        return [
            'username' => $this->getAdminUsername(),
            'role' => $this->get('user_role', 'user'),
            'login_time' => $this->getRaw('login_time'),
            'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0,
            'last_activity' => $this->getRaw('last_activity'),
            'is_authenticated' => $this->isAuthenticatedAdmin()
        ];
    }
    
    /**
     * Get cart count safely
     */
    public function getCartCount() {
        $cart = $this->getRaw('cart', []);
        return is_array($cart) ? count($cart) : 0;
    }
    
    /**
     * Add item to cart
     */
    public function addToCart($item) {
        $cart = $this->getRaw('cart', []);
        $cart[] = $item;
        $this->set('cart', $cart);
    }
    
    /**
     * Clear cart
     */
    public function clearCart() {
        $this->remove('cart');
    }
    
    /**
     * Generate contact form CSRF token (separate from admin CSRF)
     */
    public function generateContactCSRFToken() {
        if (!isset($_SESSION['contact_csrf_token'])) {
            $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
            error_log("SessionManager: Generated new contact CSRF token. Session ID: " . session_id());
        }
        error_log("SessionManager: Returning contact CSRF token. Session ID: " . session_id() . ", Token exists: " . (isset($_SESSION['contact_csrf_token']) ? 'yes' : 'no'));
        
        // Force session write to ensure token is saved
        session_write_close();
        session_start(); // Restart for continued use
        
        return $_SESSION['contact_csrf_token'];
    }
    
    /**
     * Verify contact form CSRF token
     */
    public function verifyContactCSRFToken($token) {
        $exists = isset($_SESSION['contact_csrf_token']);
        $matches = $exists && hash_equals($_SESSION['contact_csrf_token'], $token);
        error_log("SessionManager: Verify contact CSRF. Session ID: " . session_id() . ", Token exists: " . ($exists ? 'yes' : 'no') . ", Matches: " . ($matches ? 'yes' : 'no'));
        return $matches;
    }
    
    /**
     * Store captcha code
     */
    public function setCaptchaCode($code) {
        $_SESSION['captcha_code'] = strtoupper($code);
    }
    
    /**
     * Get and clear captcha code
     */
    public function getCaptchaCode() {
        $code = $_SESSION['captcha_code'] ?? null;
        unset($_SESSION['captcha_code']); // Clear after reading
        return $code;
    }
    
    /**
     * Check if captcha exists
     */
    public function hasCaptcha() {
        return isset($_SESSION['captcha_code']);
    }
    
    /**
     * Set last contact form submission time
     */
    public function setLastContactSubmit() {
        $_SESSION['last_contact_submit'] = time();
    }
    
    /**
     * Get last contact form submission time
     */
    public function getLastContactSubmit() {
        return $_SESSION['last_contact_submit'] ?? 0;
    }
    
    /**
     * Generate honeypot field name
     */
    public function getHoneypotField() {
        if (!isset($_SESSION['honeypot_field'])) {
            $_SESSION['honeypot_field'] = 'field_' . bin2hex(random_bytes(8));
        }
        return $_SESSION['honeypot_field'];
    }
}