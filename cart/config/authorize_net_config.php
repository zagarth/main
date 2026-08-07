<?php
/**
 * Authorize.Net Configuration
 * Modern secure configuration for payment processing
 * Cadman Manufacturing
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Import Authorize.Net classes
use net\authorize\api\constants\ANetEnvironment;

// Environment Configuration
define('AUTHORIZE_NET_ENVIRONMENT', 'sandbox'); // Change to 'production' for live

// Sandbox Credentials (Replace with your actual credentials)
if (AUTHORIZE_NET_ENVIRONMENT === 'sandbox') {
    define('AUTHORIZE_NET_LOGIN_ID', 'YOUR_SANDBOX_LOGIN_ID');
    define('AUTHORIZE_NET_TRANSACTION_KEY', 'YOUR_SANDBOX_TRANSACTION_KEY');
    define('AUTHORIZE_NET_PUBLIC_KEY', 'YOUR_SANDBOX_PUBLIC_CLIENT_KEY');
    define('AUTHORIZE_NET_SIGNATURE_KEY', 'YOUR_SANDBOX_SIGNATURE_KEY');
    define('AUTHORIZE_NET_ENDPOINT', ANetEnvironment::SANDBOX);
} else {
    // Production Credentials (Store in encrypted config)
    define('AUTHORIZE_NET_LOGIN_ID', $_ENV['AUTHORIZE_NET_LOGIN_ID']);
    define('AUTHORIZE_NET_TRANSACTION_KEY', $_ENV['AUTHORIZE_NET_TRANSACTION_KEY']);
    define('AUTHORIZE_NET_PUBLIC_KEY', $_ENV['AUTHORIZE_NET_PUBLIC_KEY']);
    define('AUTHORIZE_NET_SIGNATURE_KEY', $_ENV['AUTHORIZE_NET_SIGNATURE_KEY']);
    define('AUTHORIZE_NET_ENDPOINT', ANetEnvironment::PRODUCTION);
}

// Security Configuration
define('PAYMENT_FORM_TIMEOUT', 900); // 15 minutes
define('MAX_PAYMENT_ATTEMPTS', 3);
define('ENABLE_FRAUD_DETECTION', true);
define('REQUIRE_CVV', true);
define('REQUIRE_AVS', true);

// Order Configuration
define('DEFAULT_CURRENCY', 'USD');
define('TAX_RATE', 0.08); // 8% default tax rate
define('FREE_SHIPPING_THRESHOLD', 100.00);
define('DEFAULT_SHIPPING_COST', 15.00);

// Email Configuration
define('ORDER_NOTIFICATION_EMAIL', 'orders@cadmanmfg.com');
define('CUSTOMER_SERVICE_EMAIL', 'support@cadmanmfg.com');

/**
 * Authorize.Net Configuration Class
 */
class AuthorizeNetConfig {
    
    /**
     * Get API Login ID
     */
    public function getApiLoginId() {
        return AUTHORIZE_NET_LOGIN_ID;
    }
    
    /**
     * Get Transaction Key  
     */
    public function getTransactionKey() {
        return AUTHORIZE_NET_TRANSACTION_KEY;
    }
    
    /**
     * Get Client Key (Public Key)
     */
    public function getClientKey() {
        return AUTHORIZE_NET_PUBLIC_KEY;
    }
    
    /**
     * Get Signature Key
     */
    public function getSignatureKey() {
        return AUTHORIZE_NET_SIGNATURE_KEY;
    }
    
    /**
     * Get API Endpoint
     */
    public function getEndpoint() {
        return AUTHORIZE_NET_ENDPOINT;
    }
    
    /**
     * Get Accept.js URL
     */
    public function getAcceptJsUrl() {
        if (AUTHORIZE_NET_ENVIRONMENT === 'sandbox') {
            return 'https://jstest.authorize.net/v1/Accept.js';
        } else {
            return 'https://js.authorize.net/v1/Accept.js';
        }
    }
    
    /**
     * Get environment (sandbox/production)
     */
    public function getEnvironment() {
        return AUTHORIZE_NET_ENVIRONMENT;
    }
    
    /**
     * Check if in sandbox mode
     */
    public function isSandbox() {
        return AUTHORIZE_NET_ENVIRONMENT === 'sandbox';
    }
    
    /**
     * Get security configuration
     */
    public function getSecurityConfig() {
        return [
            'require_cvv' => REQUIRE_CVV,
            'require_avs' => REQUIRE_AVS,
            'fraud_detection' => ENABLE_FRAUD_DETECTION,
            'form_timeout' => PAYMENT_FORM_TIMEOUT,
            'max_attempts' => MAX_PAYMENT_ATTEMPTS
        ];
    }
}

/**
 * Get Authorize.Net configuration array
 */
function getAuthorizeNetConfig() {
    return [
        'environment' => AUTHORIZE_NET_ENVIRONMENT,
        'login_id' => AUTHORIZE_NET_LOGIN_ID,
        'transaction_key' => AUTHORIZE_NET_TRANSACTION_KEY,
        'public_key' => AUTHORIZE_NET_PUBLIC_KEY,
        'signature_key' => AUTHORIZE_NET_SIGNATURE_KEY,
        'endpoint' => AUTHORIZE_NET_ENDPOINT,
        'security' => [
            'require_cvv' => REQUIRE_CVV,
            'require_avs' => REQUIRE_AVS,
            'fraud_detection' => ENABLE_FRAUD_DETECTION,
            'form_timeout' => PAYMENT_FORM_TIMEOUT,
            'max_attempts' => MAX_PAYMENT_ATTEMPTS
        ]
    ];
}

/**
 * Validate environment configuration
 */
function validateAuthorizeNetConfig() {
    $required_constants = [
        'AUTHORIZE_NET_LOGIN_ID',
        'AUTHORIZE_NET_TRANSACTION_KEY',
        'AUTHORIZE_NET_PUBLIC_KEY',
        'AUTHORIZE_NET_SIGNATURE_KEY'
    ];
    
    $missing = [];
    foreach ($required_constants as $constant) {
        if (!defined($constant) || empty(constant($constant))) {
            $missing[] = $constant;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Missing Authorize.Net configuration: ' . implode(', ', $missing));
    }
    
    return true;
}

// Security headers for payment pages
function setPaymentSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\' https://js.authorize.net https://jstest.authorize.net; script-src \'self\' \'unsafe-inline\' https://js.authorize.net https://jstest.authorize.net; style-src \'self\' \'unsafe-inline\'');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Ensure HTTPS in production
    if (AUTHORIZE_NET_ENVIRONMENT === 'production' && !isset($_SERVER['HTTPS'])) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>