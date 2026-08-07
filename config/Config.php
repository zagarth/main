<?php
/**
 * Secure Configuration Manager
 * Loads environment variables and API credentials securely
 * Cadman Manufacturing - Jewelry E-commerce
 */

class Config {
    private static $instance = null;
    private $config = [];
    private $environment = 'development';
    
    private function __construct() {
        $this->loadEnvironment();
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
     * Load environment configuration
     */
    private function loadEnvironment() {
        // Determine environment
        $this->environment = $_SERVER['ENVIRONMENT'] ?? 'development';
        
        // Config file path (outside web root)
        $configPath = '/var/www/config/.env.' . $this->environment;
        
        // Fallback to development if specific env file doesn't exist
        if (!file_exists($configPath)) {
            $configPath = '/var/www/config/.env.development';
        }
        
        // Load configuration file
        if (file_exists($configPath)) {
            $this->parseEnvFile($configPath);
        } else {
            throw new Exception("Configuration file not found: " . $configPath);
        }
        
        // Validate required configuration
        $this->validateConfig();
    }
    
    /**
     * Parse .env file
     */
    private function parseEnvFile($filePath) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                
                // Store in config array
                $this->config[$key] = $value;
                
                // Also set as environment variable if not already set
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
    
    /**
     * Validate required configuration keys
     */
    private function validateConfig() {
        $required = [
            'ENVIRONMENT',
            'DB_HOST',
            'DB_NAME',
            'DB_USERNAME',
            'DB_PASSWORD',
            'AUTHORIZE_NET_API_LOGIN_ID',
            'AUTHORIZE_NET_TRANSACTION_KEY',
            'AUTHORIZE_NET_ENVIRONMENT',
            'ENCRYPTION_KEY'
        ];
        
        $missing = [];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Missing required configuration keys: " . implode(', ', $missing));
        }
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get all configuration as array
     */
    public function getAll() {
        return $this->config;
    }
    
    /**
     * Check if running in development mode
     */
    public function isDevelopment() {
        return $this->get('ENVIRONMENT') === 'development';
    }
    
    /**
     * Check if running in production mode
     */
    public function isProduction() {
        return $this->get('ENVIRONMENT') === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode() {
        return filter_var($this->get('DEBUG_MODE', false), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get database configuration
     */
    public function getDatabaseConfig() {
        return [
            'host' => $this->get('DB_HOST'),
            'name' => $this->get('DB_NAME'),
            'username' => $this->get('DB_USERNAME'),
            'password' => $this->get('DB_PASSWORD'),
            'charset' => $this->get('DB_CHARSET', 'utf8mb4')
        ];
    }
    
    /**
     * Get Authorize.Net configuration
     */
    public function getAuthorizeNetConfig() {
        $environment = $this->get('AUTHORIZE_NET_ENVIRONMENT', 'sandbox');
        
        return [
            'api_login_id' => $this->get('AUTHORIZE_NET_API_LOGIN_ID'),
            'transaction_key' => $this->get('AUTHORIZE_NET_TRANSACTION_KEY'),
            'signature_key' => $this->get('AUTHORIZE_NET_SIGNATURE_KEY'),
            'environment' => $environment,
            'api_url' => $environment === 'production' 
                ? $this->get('AUTHORIZE_NET_PRODUCTION_URL')
                : $this->get('AUTHORIZE_NET_SANDBOX_URL'),
            'accept_js_url' => $environment === 'production'
                ? $this->get('ACCEPT_JS_PRODUCTION_URL')
                : $this->get('ACCEPT_JS_SANDBOX_URL')
        ];
    }
    
    /**
     * Get email configuration
     */
    public function getEmailConfig() {
        return [
            'host' => $this->get('SMTP_HOST'),
            'port' => (int)$this->get('SMTP_PORT', 587),
            'username' => $this->get('SMTP_USERNAME'),
            'password' => $this->get('SMTP_PASSWORD'),
            'encryption' => $this->get('SMTP_ENCRYPTION', 'tls'),
            'from_email' => $this->get('FROM_EMAIL'),
            'from_name' => $this->get('FROM_NAME'),
            'admin_email' => $this->get('ADMIN_EMAIL'),
            'order_email' => $this->get('ORDER_NOTIFICATION_EMAIL')
        ];
    }
    
    /**
     * Get payment configuration
     */
    public function getPaymentConfig() {
        return [
            'currency' => $this->get('CURRENCY', 'USD'),
            'tax_rate' => (float)$this->get('TAX_RATE', 0.08),
            'shipping_rate' => (float)$this->get('SHIPPING_RATE', 15.00),
            'free_shipping_threshold' => (float)$this->get('FREE_SHIPPING_THRESHOLD', 500.00)
        ];
    }
    
    /**
     * Get security configuration
     */
    public function getSecurityConfig() {
        return [
            'encryption_key' => $this->get('ENCRYPTION_KEY'),
            'session_lifetime' => (int)$this->get('SESSION_LIFETIME', 7200),
            'csrf_token_lifetime' => (int)$this->get('CSRF_TOKEN_LIFETIME', 3600)
        ];
    }
    
    /**
     * Get logging configuration
     */
    public function getLoggingConfig() {
        return [
            'log_path' => $this->get('LOG_FILE_PATH', '/var/log/cadman/'),
            'error_log' => $this->get('ERROR_LOG_FILE', 'error.log'),
            'access_log' => $this->get('ACCESS_LOG_FILE', 'access.log'),
            'payment_log' => $this->get('PAYMENT_LOG_FILE', 'payments.log'),
            'log_level' => $this->get('LOG_LEVEL', 'info')
        ];
    }
    
    /**
     * Encrypt sensitive data using the encryption key
     */
    public function encrypt($data) {
        $key = $this->get('ENCRYPTION_KEY');
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData) {
        $key = $this->get('ENCRYPTION_KEY');
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}

/**
 * Helper function to get config instance
 */
function config($key = null, $default = null) {
    $config = Config::getInstance();
    
    if ($key === null) {
        return $config;
    }
    
    return $config->get($key, $default);
}
?>