<?php
/**
 * Mail Handler for Cadman Manufacturing Contact Form
 * Handles form submission and sends emails via SMTP
 * Enhanced with robust error checking for external networks
 */

// Global captcha variable
$GLOBALS['CURRENT_CAPTCHA'] = '';

// Enable error reporting for debugging
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Use centralized session manager for consistent session handling
require_once __DIR__ . '/session_manager.php';
require_once 'mail_config.php';

// Define secret for HMAC validation
if (!defined('CSRF_SECRET')) {
    define('CSRF_SECRET', $_ENV['SESSION_SECRET_KEY'] ?? 'cadman-mfg-secret-2026');
}

// Create error log function
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logMessage = "[{$timestamp}] IP: {$clientIP} | {$message}";
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    $logMessage .= " | UA: {$userAgent}" . PHP_EOL;
    
    // Log to file and system log
    error_log($logMessage, 3, '/tmp/cadman_mail.log');
    error_log("Cadman Mail: {$message}");
}

/**
 * Simple SMTP mail sender class
 */
class SimpleSMTP {
    private $connection;
    private $host;
    private $port;
    private $encryption;
    private $username;
    private $password;
    private $useAuth;
    private $timeout;
    private $debug;
    
    public function __construct($host, $port = 587, $encryption = 'tls') {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = MAIL_USERNAME;
        $this->password = MAIL_PASSWORD;
        $this->useAuth = MAIL_USE_AUTH;
        $this->timeout = MAIL_TIMEOUT;
        $this->debug = MAIL_DEBUG;
    }
    
    /**
     * Send email via SMTP with enhanced error handling
     */
    public function sendMail($to, $subject, $message, $from, $fromName = '', $replyTo = '') {
        $startTime = microtime(true);
        
        try {
            logError("Attempting to send email", [
                'to' => $to,
                'from' => $from,
                'subject' => substr($subject, 0, 50),
                'primary_host' => $this->host,
                'backup_host' => MAIL_HOST_BACKUP ?? 'none'
            ]);
            
            // Try primary mail server first
            $primaryResult = $this->attemptConnection($this->host, 'primary');
            if ($primaryResult['success']) {
                $result = $this->sendMessage($to, $subject, $message, $from, $fromName, $replyTo);
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($result['success']) {
                    logError("Email sent successfully via primary server in {$duration}ms");
                } else {
                    logError("Email send failed via primary server", ['error' => $result['error']]);
                }
                return $result;
            }
            
            logError("Primary server failed", ['error' => $primaryResult['error']]);
            
            // Try backup server if primary fails
            if (MAIL_HOST_BACKUP) {
                $backupResult = $this->attemptConnection(MAIL_HOST_BACKUP, 'backup');
                if ($backupResult['success']) {
                    $result = $this->sendMessage($to, $subject, $message, $from, $fromName, $replyTo);
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    
                    if ($result['success']) {
                        logError("Email sent successfully via backup server in {$duration}ms");
                    } else {
                        logError("Email send failed via backup server", ['error' => $result['error']]);
                    }
                    return $result;
                }
                logError("Backup server also failed", ['error' => $backupResult['error']]);
            }
            
            // Both servers failed
            $errorMsg = "All mail servers unavailable. Primary: {$primaryResult['error']}";
            if (MAIL_HOST_BACKUP) {
                $errorMsg .= ", Backup: {$backupResult['error']}";
            }
            
            logError("Complete mail failure", ['duration_ms' => round((microtime(true) - $startTime) * 1000, 2)]);
            
            return array(
                'success' => false, 
                'error' => $errorMsg,
                'user_message' => 'Email service temporarily unavailable. Please try again later or contact us directly at (519) 688-2121.'
            );
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            logError("Mail exception caught", [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => $duration
            ]);
            
            return array(
                'success' => false, 
                'error' => 'Mail system exception: ' . $e->getMessage(),
                'user_message' => 'Email service error. Please contact us directly at (519) 688-2121.'
            );
        }
    }
    
    /**
     * Attempt connection with detailed error reporting
     */
    private function attemptConnection($host, $type = 'unknown') {
        try {
            if ($this->connectToServer($host)) {
                return array('success' => true);
            } else {
                return array('success' => false, 'error' => "Could not connect to {$type} server {$host}:{$this->port}");
            }
        } catch (Exception $e) {
            return array('success' => false, 'error' => "{$type} server exception: " . $e->getMessage());
        }
    }
    
    /**
     * Connect to SMTP server with enhanced diagnostics
     */
    private function connectToServer($host) {
        $connectStart = microtime(true);
        
        // Pre-connection network checks
        if (!$this->performNetworkChecks($host)) {
            return false;
        }
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'ciphers' => 'HIGH:!SSLv2:!SSLv3',
                'disable_compression' => true,
            ],
            'socket' => [
                'bindto' => '0:0'
            ]
        ]);
        
        // Try different connection methods
        $connectionString = ($this->encryption === 'ssl') ? "ssl://{$host}:{$this->port}" : $host;
        
        logError("Attempting SMTP connection", [
            'host' => $host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'connection_string' => $connectionString,
            'timeout' => $this->timeout
        ]);
        
        $this->connection = @stream_socket_client(
            $connectionString . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        $connectTime = round((microtime(true) - $connectStart) * 1000, 2);
        
        if (!$this->connection) {
            logError("SMTP connection failed", [
                'host' => $host,
                'port' => $this->port,
                'errno' => $errno,
                'errstr' => $errstr,
                'connect_time_ms' => $connectTime,
                'timeout' => $this->timeout
            ]);
            return false;
        }
        
        // Set socket timeout
        stream_set_timeout($this->connection, $this->timeout);
        
        // Read server greeting
        $response = $this->readResponse();
        $isPositive = $this->isPositiveResponse($response);
        
        logError("SMTP server greeting", [
            'host' => $host,
            'response' => substr($response, 0, 100),
            'is_positive' => $isPositive,
            'connect_time_ms' => $connectTime
        ]);
        
        if (!$isPositive) {
            fclose($this->connection);
            return false;
        }
        
        return true;
    }
    
    /**
     * Perform pre-connection network diagnostics
     */
    private function performNetworkChecks($host) {
        // Check if host is reachable
        $dnsStart = microtime(true);
        $ip = gethostbyname($host);
        $dnsTime = round((microtime(true) - $dnsStart) * 1000, 2);
        
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            logError("DNS resolution failed", [
                'host' => $host,
                'dns_time_ms' => $dnsTime
            ]);
            return false;
        }
        
        logError("DNS resolution successful", [
            'host' => $host,
            'resolved_ip' => $ip,
            'dns_time_ms' => $dnsTime
        ]);
        
        // Test basic connectivity
        $pingStart = microtime(true);
        $testSocket = @fsockopen($ip, $this->port, $errno, $errstr, 5);
        $pingTime = round((microtime(true) - $pingStart) * 1000, 2);
        
        if (!$testSocket) {
            logError("Basic connectivity test failed", [
                'host' => $host,
                'ip' => $ip,
                'port' => $this->port,
                'errno' => $errno,
                'errstr' => $errstr,
                'ping_time_ms' => $pingTime
            ]);
            return false;
        }
        
        fclose($testSocket);
        logError("Basic connectivity test passed", [
            'host' => $host,
            'ip' => $ip,
            'port' => $this->port,
            'ping_time_ms' => $pingTime
        ]);
        
        return true;
    }
    
    /**
     * Send the actual message
     */
    private function sendMessage($to, $subject, $message, $from, $fromName, $replyTo) {
        // Send EHLO with proper server name
        $serverName = 'raspberrypi'; // Use the local server name
        $this->sendCommand("EHLO {$serverName}");
        $response = $this->readResponse();
        
        // Start TLS if required
        if ($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            $response = $this->readResponse();
            
            if ($this->isPositiveResponse($response)) {
                stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after TLS
                $this->sendCommand("EHLO {$serverName}");
                $response = $this->readResponse();
            }
        }
        
        // Authenticate if required
        if ($this->useAuth && $this->username && $this->password) {
            $this->sendCommand("AUTH LOGIN");
            $response = $this->readResponse();
            
            $this->sendCommand(base64_encode($this->username));
            $response = $this->readResponse();
            
            $this->sendCommand(base64_encode($this->password));
            $response = $this->readResponse();
            
            if (!$this->isPositiveResponse($response)) {
                return array('success' => false, 'error' => 'SMTP Authentication failed');
            }
        }
        
        // Send mail
        $fromEmail = $fromName ? "{$fromName} <{$from}>" : $from;
        
        $this->sendCommand("MAIL FROM: <{$from}>");
        $response = $this->readResponse();
        
        if (!$this->isPositiveResponse($response)) {
            return array('success' => false, 'error' => 'MAIL FROM command failed: ' . $response);
        }
        
        // Add primary recipient
        $this->sendCommand("RCPT TO: <{$to}>");
        $response = $this->readResponse();
        
        if (!$this->isPositiveResponse($response)) {
            return array('success' => false, 'error' => 'RCPT TO command failed for primary recipient: ' . $response);
        }
        
        // Add copy recipient (info@cadmanmfg.com)
        $this->sendCommand("RCPT TO: <info@cadmanmfg.com>");
        $response = $this->readResponse();
        
        if (!$this->isPositiveResponse($response)) {
            logError("Failed to add CC recipient info@cadmanmfg.com", ['response' => $response]);
            // Don't fail the whole email if CC fails, just log it
        }
        
        $this->sendCommand("DATA");
        $response = $this->readResponse();
        
        if (!$this->isPositiveResponse($response)) {
            return array('success' => false, 'error' => 'DATA command failed: ' . $response);
        }
        
        // Prepare headers and message
        $headers = $this->prepareHeaders($to, $subject, $from, $fromName, $replyTo);
        
        // Send message content (headers + body + end marker)
        // Don't use sendCommand here as it adds extra \r\n that we control manually
        $messageContent = $headers . "\r\n\r\n" . $message . "\r\n.\r\n";
        fwrite($this->connection, $messageContent);
        $response = $this->readResponse();
        
        if (!$this->isPositiveResponse($response)) {
            $this->sendCommand("QUIT");
            fclose($this->connection);
            return array('success' => false, 'error' => 'Failed to send email: ' . $response);
        }
        
        // Quit
        $this->sendCommand("QUIT");
        fclose($this->connection);
        
        return array('success' => true, 'message' => 'Email sent successfully');
    }
    
    /**
     * Prepare email headers
     */
    private function prepareHeaders($to, $subject, $from, $fromName, $replyTo) {
        $fromHeader = $fromName ? "{$fromName} <{$from}>" : $from;
        $replyToHeader = $replyTo ? $replyTo : $from;
        
        $headers = array();
        $headers[] = "To: {$to}";
        $headers[] = "Cc: info@cadmanmfg.com";
        $headers[] = "Subject: {$subject}";
        $headers[] = "From: {$fromHeader}";
        $headers[] = "Reply-To: {$replyToHeader}";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . uniqid() . "@cadmanmfg.com>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: Cadman Manufacturing Contact Form";
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Send command to SMTP server
     */
    private function sendCommand($command) {
        if ($this->debug) {
            error_log("SMTP Command: {$command}");
        }
        fwrite($this->connection, $command . "\r\n");
    }
    
    /**
     * Read response from SMTP server
     */
    private function readResponse() {
        $response = '';
        while (($line = fgets($this->connection, 515)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        if ($this->debug) {
            error_log("SMTP Response: {$response}");
        }
        
        return trim($response);
    }
    
    /**
     * Check if response is positive (2xx or 3xx)
     */
    private function isPositiveResponse($response) {
        $code = (int)substr($response, 0, 3);
        return $code >= 200 && $code < 400;
    }
}

/**
 * Validate stateless CSRF token (HMAC-based)
 */
function verifyStatelessCSRFToken($tokenData) {
    if (empty($tokenData)) {
        return false;
    }
    
    $decoded = base64_decode($tokenData);
    if ($decoded === false || strpos($decoded, '|') === false) {
        return false;
    }
    
    list($timestamp, $providedToken) = explode('|', $decoded, 2);
    
    // Check token age (valid for 1 hour)
    if ((time() - $timestamp) > 3600) {
        return false;
    }
    
    // Verify HMAC
    $expectedToken = hash_hmac('sha256', $timestamp, CSRF_SECRET);
    return hash_equals($expectedToken, $providedToken);
}

/**
 * Get honeypot field name for a given date
 */
function getHoneypotFieldName($date = null) {
    $date = $date ?? date('Y-m-d');
    return 'field_' . substr(hash('sha256', $date . CSRF_SECRET), 0, 16);
}

/**
 * Validate captcha verification
 */
function validateCaptcha($userInput, $timestamp) {
    // Read captcha from file cache using timestamp
    $cacheDir = sys_get_temp_dir() . '/captcha_cache';
    $cacheFile = $cacheDir . '/captcha_' . $timestamp;
    
    if (!file_exists($cacheFile)) {
        error_log("Captcha validation failed: cache file not found for timestamp " . $timestamp);
        return false;
    }
    
    // Check cache file age (valid for 10 minutes)
    if ((time() - filemtime($cacheFile)) > 600) {
        unlink($cacheFile);
        error_log("Captcha validation failed: cache file too old");
        return false;
    }
    
    $sessionCaptcha = file_get_contents($cacheFile);
    
    // Delete cache file after reading (one-time use)
    unlink($cacheFile);
    
    if (empty($sessionCaptcha)) {
        return false;
    }
    
    // Compare uppercase strings for consistency
    $result = strtoupper(trim($userInput)) === strtoupper(trim($sessionCaptcha));
    error_log("Captcha validation: user=" . strtoupper(trim($userInput)) . ", expected=" . strtoupper(trim($sessionCaptcha)) . ", result=" . ($result ? 'PASS' : 'FAIL'));
    return $result;
}

/**
 * Sanitize and validate form input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Main form processing with comprehensive error handling
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = array();
    $success = false;
    
    // ===== SECURITY VALIDATION =====
    
    // 1. CSRF Token Validation (stateless HMAC)
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyStatelessCSRFToken($csrfToken)) {
        logError("CSRF token validation failed", [
            'token_provided' => !empty($csrfToken),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $errors[] = 'Security validation failed. Please refresh the page and try again.';
    }
    
    // 2. Honeypot Field Check (anti-bot)
    $honeypotField = getHoneypotFieldName();
    if (isset($_POST[$honeypotField])) {
        $honeypotValue = $_POST[$honeypotField];
        if (!empty($honeypotValue)) {
            // Bot detected - filled honeypot field
            logError("Bot detected - honeypot field filled", [
                'field_value' => substr($honeypotValue, 0, 50),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $errors[] = 'Spam detection triggered. If you are a real person, please contact us directly.';
        }
    }
    
    // 3. Rate Limiting (IP-based file cache)
    $minTimeBetweenSubmits = 10; // seconds
    $currentTime = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitFile = sys_get_temp_dir() . '/contact_rate_' . md5($ip);
    
    if (file_exists($rateLimitFile)) {
        $lastSubmit = (int)file_get_contents($rateLimitFile);
        $timeSinceLastSubmit = $currentTime - $lastSubmit;
        if ($timeSinceLastSubmit < $minTimeBetweenSubmits) {
            logError("Rate limit exceeded", [
                'time_since_last' => $timeSinceLastSubmit,
                'ip' => $ip
            ]);
            $waitTime = $minTimeBetweenSubmits - $timeSinceLastSubmit;
            $errors[] = "Please wait {$waitTime} seconds before submitting another message.";
        }
    }
    
    // 4. Form Timing Check (prevent auto-submit bots)
    if (isset($_POST['form_timestamp'])) {
        $formLoadTime = intval($_POST['form_timestamp']);
        $timeSinceFormLoad = $currentTime - $formLoadTime;
        
        // Form filled too quickly (likely a bot)
        if ($timeSinceFormLoad < 3) {
            logError("Form submitted too quickly - likely bot", [
                'time_to_submit' => $timeSinceFormLoad,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $errors[] = 'Form submitted too quickly. Please take your time filling out the form.';
        }
        
        // Form token expired (older than 1 hour)
        if ($timeSinceFormLoad > 3600) {
            logError("Form token expired", [
                'age' => $timeSinceFormLoad,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $errors[] = 'Form session expired. Please refresh the page and try again.';
        }
    }
    
    // ===== END SECURITY VALIDATION =====
    
    // Debug logging
    logError("Form submission received", [
        'name' => $_POST['name'] ?? 'not set',
        'email' => $_POST['email'] ?? 'not set',
        'verify' => $_POST['verify'] ?? 'not set',
        'form_timestamp' => $_POST['form_timestamp'] ?? 'not set',
        'security_checks_passed' => empty($errors)
    ]);
    
    try {
        // Get and sanitize form data
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $verify = sanitizeInput($_POST['verify'] ?? '');
        $formTimestamp = (int)($_POST['form_timestamp'] ?? 0);
        
        // Get raw message for length validation (before HTML encoding)
        $rawMessage = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name) || $name === 'name') {
            $errors[] = 'Please enter your name.';
        }
        
        if (empty($email) || $email === 'email@email.com' || !isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($rawMessage) || strlen($rawMessage) < 10) {
            $errors[] = 'Please enter a message with at least 10 characters.';
        }
        
        if (empty($verify) || $verify === 'Code') {
            $errors[] = 'Please enter the verification code shown in the image.';
        } elseif (!validateCaptcha($verify, $formTimestamp)) {
            $errors[] = 'The verification code you entered is incorrect. Please try again with the code shown in the image.';
        }
        
        // If no errors, send email
        if (empty($errors)) {
            // Prepare email content
            $subject = "Contact Form Submission - Cadman Manufacturing";
            
            // Build tracking information if available
            $trackingRows = '';
            if (isset($_SESSION['contact_source'])) {
                $source = $_SESSION['contact_source'];
                $trackingRows = "
                    <tr style='background-color: #f0f8ff;'>
                        <td colspan='2'><strong style='color: #0066cc;'>Source Tracking Information</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Source Page:</strong></td>
                        <td>{$source['page']}</td>
                    </tr>
                    <tr>
                        <td><strong>Source Section:</strong></td>
                        <td>{$source['section']}</td>
                    </tr>
                    <tr>
                        <td><strong>Page URL:</strong></td>
                        <td><a href='{$source['url']}'>{$source['url']}</a></td>
                    </tr>
                    <tr>
                        <td><strong>Clicked At:</strong></td>
                        <td>{$source['timestamp']}</td>
                    </tr>";
                
                // Clear tracking data after use
                unset($_SESSION['contact_source']);
            }
            
            // Build product information if this is a quote request
            $productRows = '';
            if (isset($_SESSION['quote_product_data'])) {
                $product = $_SESSION['quote_product_data'];
                $productRows = "
                    <tr style='background-color: #fff4e6;'>
                        <td colspan='2'><strong style='color: #ff6600;'>Product Quote Request</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Product ID:</strong></td>
                        <td>{$product['product_id']}</td>
                    </tr>
                    <tr>
                        <td><strong>Product Name:</strong></td>
                        <td>{$product['product_name']}</td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td>{$product['category']}</td>
                    </tr>
                    <tr>
                        <td><strong>Collection:</strong></td>
                        <td>{$product['collection']}</td>
                    </tr>";
                
                // Add configured options if any
                if (!empty($product['configured_options'])) {
                    $productRows .= "
                    <tr>
                        <td><strong>Selected Options:</strong></td>
                        <td>";
                    foreach ($product['configured_options'] as $option => $value) {
                        $productRows .= "<br>• " . htmlspecialchars($option) . ": " . htmlspecialchars($value);
                    }
                    $productRows .= "</td>
                    </tr>";
                }
                
                $productRows .= "
                    <tr>
                        <td><strong>Product Viewed At:</strong></td>
                        <td>{$product['timestamp']}</td>
                    </tr>";
                
                // Clear product data after use
                unset($_SESSION['quote_product_data']);
            }
            
            $emailMessage = "
            <html>
            <head>
                <title>Contact Form Submission</title>
            </head>
            <body>
                <h2>New Contact Form Submission</h2>
                <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>{$name}</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>{$email}</td>
                    </tr>
                    <tr>
                        <td><strong>Message:</strong></td>
                        <td>" . nl2br($message) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                    <tr>
                        <td><strong>IP Address:</strong></td>
                        <td>" . $_SERVER['REMOTE_ADDR'] . "</td>
                    </tr>
                    {$trackingRows}
                    {$productRows}
                </table>
            </body>
            </html>";
            
            // Send email with comprehensive error handling
            logError("Processing contact form submission", [
                'name' => $name,
                'email' => $email,
                'message_length' => strlen($rawMessage),
                'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $mailer = new SimpleSMTP(MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION);
            $result = $mailer->sendMail(
                MAIL_TO_EMAIL,
                $subject,
                $emailMessage,
                MAIL_FROM_EMAIL,
                MAIL_FROM_NAME,
                $email // Set reply-to as the sender's email
            );
            
            if ($result['success']) {
                $success = true;
                $successMessage = "🎉 Message sent successfully! Thank you {$name} for contacting Cadman Manufacturing. We'll respond to your inquiry within 24 hours.";
                
                // Update rate limiting timestamp (file-based)
                file_put_contents($rateLimitFile, $currentTime);
                
                logError("Contact form submission successful", [
                    'name' => $name,
                    'email' => $email
                ]);
            } else {
                // Use user-friendly error message if available
                $userError = isset($result['user_message']) ? $result['user_message'] : $result['error'];
                $errors[] = $userError;
                
                logError("Contact form submission failed", [
                    'name' => $name,
                    'email' => $email,
                    'error' => $result['error'],
                    'user_message' => $result['user_message'] ?? 'none'
                ]);
            }
        }
        
    } catch (Exception $e) {
        logError("Unexpected exception in form processing", [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $errors[] = "System error occurred. Please try again or contact us directly at (519) 688-2121.";
    } catch (Error $e) {
        logError("Fatal error in form processing", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        $errors[] = "System error occurred. Please contact us directly at (519) 688-2121.";
    }
    
    // Always log the final result
    logError("Form processing completed", [
        'success' => $success,
        'error_count' => count($errors),
        'has_errors' => !empty($errors)
    ]);

    // Redirect back to form with results
    $params = array();

    // Clear any existing error/success parameters by starting fresh
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/';
    
    // Remove existing success/error parameters from the URL
    $redirectUrl = preg_replace('/[?&](success|error)=[^&]*/', '', $redirectUrl);
    $redirectUrl = rtrim($redirectUrl, '?&'); // Clean up trailing separators

    // Only show success if there are no errors - this clears any previous error state
    if (isset($success) && $success && empty($errors)) {
        $params['success'] = urlencode($successMessage);
        // Explicitly ensure no error parameter is set on success
        logError("Success message being sent - errors cleared", [
            'success_message' => substr($successMessage, 0, 50),
            'error_count' => 0
        ]);
    } elseif (!empty($errors)) {
        $params['error'] = urlencode(implode(' ', $errors));
        // Explicitly ensure no success parameter is set on error
        logError("Error message being sent - success cleared", [
            'error_message' => substr(implode(' ', $errors), 0, 100),
            'error_count' => count($errors)
        ]);
    }

    // Add hash parameter to trigger modal opening
    $anchor = '';
    
    if (isset($success) && $success && empty($errors)) {
        // On success, add a flag to auto-open modal and show message
        $anchor = '#contact-success';
    } elseif (!empty($errors)) {
        // On error, add a flag to auto-open modal and show error
        $anchor = '#contact-error';
    }

    if (!empty($params)) {
        $separator = strpos($redirectUrl, '?') !== false ? '&' : '?';
        $redirectUrl .= $separator . http_build_query($params) . $anchor;
    } else {
        $redirectUrl .= $anchor;
    }

    logError("Redirecting to", ['url' => $redirectUrl]);
    header("Location: {$redirectUrl}");
    exit;

} else {
    // If not a POST request, redirect to home page
    header("Location: /");
    exit;
}
?>
