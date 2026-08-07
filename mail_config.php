<?php
/**
 * Mail Configuration for Cadman Manufacturing
 * Secure mail server settings
 */

// Mail server configuration - Postfix on raspimail (LAN relay)
define('MAIL_HOST', 'raspimail'); // 192.168.1.104
define('MAIL_HOST_BACKUP', 'mail.hddoc.ca');
define('MAIL_HOST_EXTERNAL', 'smtp.gmail.com'); // External fallback
define('MAIL_PORT', 25); // LAN relay - no auth needed for mynetworks
define('MAIL_PORT_SECURE', 465); // SSL port alternative
define('MAIL_ENCRYPTION', ''); // No encryption for LAN relay

// Fallback ports for external networks
define('MAIL_PORT_ALT', 25); // Alternative port
define('MAIL_PORT_ALT2', 2525); // Another alternative port

// Email addresses
define('MAIL_FROM_EMAIL', 'info@cadmanmfg.com'); // Sender address
define('MAIL_FROM_NAME', 'Cadman Manufacturing');
define('MAIL_TO_EMAIL', 'info@cadmanmfg.com'); // Contact form emails go here
define('MAIL_CC_EMAIL', ''); // No CC needed
define('MAIL_REPLY_TO', 'info@cadmanmfg.com');

// SMTP Authentication (not needed for mynetworks)
define('MAIL_USERNAME', ''); // No auth for LAN clients
define('MAIL_PASSWORD', ''); // No auth for LAN clients
define('MAIL_USE_AUTH', false); // LAN clients in mynetworks don't need auth

// Security settings
define('MAIL_TIMEOUT', 30);
define('MAIL_DEBUG', true); // Set to true for debugging - TEMPORARILY ENABLED
define('MAIL_MAX_RETRIES', 3); // Number of retry attempts
define('MAIL_RETRY_DELAY', 2); // Seconds between retries

// Network diagnostics settings
define('MAIL_ENABLE_DIAGNOSTICS', true); // Enable network diagnostics
define('MAIL_LOG_ALL_ATTEMPTS', true); // Log all connection attempts

// Verification settings
define('CAPTCHA_SESSION_KEY', 'captcha_code');

?>
