<?php
/**
 * Citadel Mail Server Test Script
 * Tests connection to raspimail Citadel server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'mail_config.php';

echo "=== Citadel Mail Server Test ===\n\n";

echo "Configuration:\n";
echo "Host: " . MAIL_HOST . "\n";
echo "Port: " . MAIL_PORT . "\n";
echo "Username: " . MAIL_USERNAME . "\n";
echo "From: " . MAIL_FROM_EMAIL . "\n";
echo "To: " . MAIL_TO_EMAIL . "\n\n";

// Test different ports that Citadel commonly uses
$portsToTest = [25, 587, 465];

foreach ($portsToTest as $port) {
    echo "Testing port $port...\n";
    $fp = @fsockopen(MAIL_HOST, $port, $errno, $errstr, 10);
    if ($fp) {
        echo "  ✓ Connected to port $port\n";
        
        // Read server greeting
        $greeting = fgets($fp, 512);
        echo "  Server greeting: " . trim($greeting) . "\n";
        
        // Try EHLO
        fputs($fp, "EHLO raspberrypi\r\n");
        $response = '';
        while ($line = fgets($fp, 512)) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        echo "  EHLO response:\n";
        foreach (explode("\n", trim($response)) as $line) {
            echo "    " . trim($line) . "\n";
        }
        
        // Quit
        fputs($fp, "QUIT\r\n");
        fclose($fp);
        echo "\n";
    } else {
        echo "  ✗ Failed to connect: $errno - $errstr\n\n";
    }
}

echo "\n=== Testing Full SMTP Transaction ===\n\n";

// Now test actual email sending with proper Citadel support
function testSMTPSend($host, $port, $useAuth = true, $useTLS = false) {
    echo "Testing SMTP on port $port (Auth: " . ($useAuth ? 'Yes' : 'No') . ", TLS: " . ($useTLS ? 'Yes' : 'No') . ")\n";
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]
    ]);
    
    $connectionString = $useTLS === 'ssl' ? "ssl://$host:$port" : "$host:$port";
    $connection = @stream_socket_client($connectionString, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    
    if (!$connection) {
        echo "  ✗ Connection failed: $errno - $errstr\n\n";
        return false;
    }
    
    $response = fgets($connection, 512);
    echo "  Greeting: " . trim($response) . "\n";
    
    // Send EHLO
    fputs($connection, "EHLO raspberrypi\r\n");
    $response = '';
    while ($line = fgets($connection, 512)) {
        $response .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    echo "  EHLO: " . trim(substr($response, 0, 100)) . "...\n";
    
    // Start TLS if needed
    if ($useTLS === 'tls') {
        fputs($connection, "STARTTLS\r\n");
        $response = fgets($connection, 512);
        echo "  STARTTLS: " . trim($response) . "\n";
        
        if (substr($response, 0, 3) == '220') {
            stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Send EHLO again after TLS
            fputs($connection, "EHLO raspberrypi\r\n");
            $response = '';
            while ($line = fgets($connection, 512)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', $line)) {
                    break;
                }
            }
            echo "  EHLO (after TLS): " . trim(substr($response, 0, 100)) . "...\n";
        }
    }
    
    // Authenticate if requested
    if ($useAuth) {
        fputs($connection, "AUTH LOGIN\r\n");
        $response = fgets($connection, 512);
        echo "  AUTH LOGIN: " . trim($response) . "\n";
        
        if (substr($response, 0, 3) == '334') {
            fputs($connection, base64_encode(MAIL_USERNAME) . "\r\n");
            $response = fgets($connection, 512);
            echo "  Username: " . trim($response) . "\n";
            
            fputs($connection, base64_encode(MAIL_PASSWORD) . "\r\n");
            $response = fgets($connection, 512);
            echo "  Password: " . trim($response) . "\n";
            
            if (substr($response, 0, 3) != '235') {
                echo "  ✗ Authentication failed!\n";
                fputs($connection, "QUIT\r\n");
                fclose($connection);
                return false;
            }
        }
    }
    
    // Send email
    fputs($connection, "MAIL FROM: <" . MAIL_FROM_EMAIL . ">\r\n");
    $response = fgets($connection, 512);
    echo "  MAIL FROM: " . trim($response) . "\n";
    
    fputs($connection, "RCPT TO: <" . MAIL_TO_EMAIL . ">\r\n");
    $response = fgets($connection, 512);
    echo "  RCPT TO: " . trim($response) . "\n";
    
    fputs($connection, "DATA\r\n");
    $response = fgets($connection, 512);
    echo "  DATA: " . trim($response) . "\n";
    
    if (substr($response, 0, 3) == '354') {
        $emailContent = "From: " . MAIL_FROM_EMAIL . "\r\n";
        $emailContent .= "To: " . MAIL_TO_EMAIL . "\r\n";
        $emailContent .= "Subject: Test Email - " . date('Y-m-d H:i:s') . "\r\n";
        $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailContent .= "\r\n";
        $emailContent .= "<html><body><h2>Citadel Test Email</h2>";
        $emailContent .= "<p>Test sent at " . date('Y-m-d H:i:s') . "</p>";
        $emailContent .= "<p>Port: $port, Auth: " . ($useAuth ? 'Yes' : 'No') . ", TLS: " . ($useTLS ? 'Yes' : 'No') . "</p>";
        $emailContent .= "</body></html>\r\n";
        $emailContent .= ".\r\n";
        
        fputs($connection, $emailContent);
        $response = fgets($connection, 512);
        echo "  Send result: " . trim($response) . "\n";
        
        if (substr($response, 0, 3) == '250') {
            echo "  ✓ Email sent successfully!\n";
            fputs($connection, "QUIT\r\n");
            fclose($connection);
            return true;
        }
    }
    
    fputs($connection, "QUIT\r\n");
    fclose($connection);
    echo "  ✗ Email sending failed\n\n";
    return false;
}

// Test different configurations
$configs = [
    ['port' => 587, 'auth' => true, 'tls' => 'tls'],
    ['port' => 587, 'auth' => true, 'tls' => false],
    ['port' => 25, 'auth' => false, 'tls' => false],
    ['port' => 25, 'auth' => true, 'tls' => false],
];

foreach ($configs as $config) {
    if (testSMTPSend(MAIL_HOST, $config['port'], $config['auth'], $config['tls'])) {
        echo "\n✓✓✓ SUCCESS with port {$config['port']}, auth: " . ($config['auth'] ? 'true' : 'false') . ", TLS: " . ($config['tls'] ?: 'none') . "\n";
        echo "\nUpdate mail_config.php with these settings:\n";
        echo "MAIL_PORT: {$config['port']}\n";
        echo "MAIL_USE_AUTH: " . ($config['auth'] ? 'true' : 'false') . "\n";
        echo "MAIL_ENCRYPTION: '" . ($config['tls'] ?: 'none') . "'\n";
        break;
    }
    echo "\n";
}

echo "\nDone.\n";
?>
