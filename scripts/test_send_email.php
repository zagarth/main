<?php
/**
 * Test Email Send
 * Sends a test email through the SimpleSMTP class directly
 */

require_once 'mail_config.php';

// Include just the SimpleSMTP class from packemail.php
// We'll extract it inline to avoid the POST processing
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
    
    public function sendMail($to, $subject, $message, $from, $fromName = '', $replyTo = '') {
        echo "Attempting to send email...\n";
        echo "  To: $to\n";
        echo "  From: $from\n";
        echo "  Subject: $subject\n\n";
        
        try {
            // Connect to mail server
            echo "Connecting to {$this->host}:{$this->port}...\n";
            $this->connection = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            
            if (!$this->connection) {
                return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
            }
            
            echo "✓ Connected\n";
            
            // Read banner
            $response = fgets($this->connection, 512);
            echo "Server: " . trim($response) . "\n";
            
            // EHLO
            fputs($this->connection, "EHLO " . gethostname() . "\r\n");
            $response = $this->getResponse();
            echo "EHLO Response: " . substr($response, 0, 50) . "...\n";
            
            // STARTTLS if using TLS
            if ($this->encryption === 'tls') {
                echo "Starting TLS...\n";
                fputs($this->connection, "STARTTLS\r\n");
                $response = $this->getResponse();
                
                if (strpos($response, '220') === false) {
                    return ['success' => false, 'error' => 'STARTTLS failed: ' . $response];
                }
                
                stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after STARTTLS
                fputs($this->connection, "EHLO " . gethostname() . "\r\n");
                $this->getResponse();
                echo "✓ TLS established\n";
            }
            
            // AUTH LOGIN
            if ($this->useAuth) {
                echo "Authenticating as {$this->username}...\n";
                fputs($this->connection, "AUTH LOGIN\r\n");
                $response = $this->getResponse();
                
                fputs($this->connection, base64_encode($this->username) . "\r\n");
                $this->getResponse();
                
                fputs($this->connection, base64_encode($this->password) . "\r\n");
                $response = $this->getResponse();
                
                if (strpos($response, '235') === false) {
                    return ['success' => false, 'error' => 'Authentication failed: ' . $response];
                }
                echo "✓ Authenticated\n";
            }
            
            // MAIL FROM
            fputs($this->connection, "MAIL FROM: <$from>\r\n");
            $response = $this->getResponse();
            if (strpos($response, '250') === false) {
                return ['success' => false, 'error' => 'MAIL FROM failed: ' . $response];
            }
            
            // RCPT TO
            fputs($this->connection, "RCPT TO: <$to>\r\n");
            $response = $this->getResponse();
            if (strpos($response, '250') === false) {
                return ['success' => false, 'error' => 'RCPT TO failed: ' . $response];
            }
            
            // DATA
            fputs($this->connection, "DATA\r\n");
            $response = $this->getResponse();
            if (strpos($response, '354') === false) {
                return ['success' => false, 'error' => 'DATA command failed: ' . $response];
            }
            
            // Send headers and message
            $headers = "From: $fromName <$from>\r\n";
            $headers .= "Reply-To: " . ($replyTo ?: $from) . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "\r\n";
            
            fputs($this->connection, $headers . $message . "\r\n.\r\n");
            $response = $this->getResponse();
            
            if (strpos($response, '250') === false) {
                return ['success' => false, 'error' => 'Message sending failed: ' . $response];
            }
            
            echo "✓ Message sent\n";
            
            // QUIT
            fputs($this->connection, "QUIT\r\n");
            fclose($this->connection);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getResponse() {
        $response = '';
        while ($line = fgets($this->connection, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
}

echo "=== SENDING TEST EMAIL ===\n\n";

echo "Test Configuration:\n";
echo "  From: " . MAIL_FROM_EMAIL . "\n";
echo "  To: " . MAIL_TO_EMAIL . "\n";
echo "  Server: " . MAIL_HOST . ":" . MAIL_PORT . "\n";
echo "  Encryption: " . MAIL_ENCRYPTION . "\n\n";

$mailer = new SimpleSMTP(MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION);


$testMessage = "
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <h2>Test Contact Form Submission</h2>
    <table border='1' cellpadding='10' cellspacing='0'>
        <tr>
            <td><strong>Name:</strong></td>
            <td>Test User</td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td>test@example.com</td>
        </tr>
        <tr>
            <td><strong>Message:</strong></td>
            <td>This is a test email from the troubleshooting script.</td>
        </tr>
        <tr>
            <td><strong>Date:</strong></td>
            <td>" . date('Y-m-d H:i:s') . "</td>
        </tr>
        <tr style='background-color: #f0f8ff;'>
            <td colspan='2'><strong style='color: #0066cc;'>Source Tracking Information</strong></td>
        </tr>
        <tr>
            <td><strong>Source Page:</strong></td>
            <td>Test Script</td>
        </tr>
        <tr>
            <td><strong>Source Section:</strong></td>
            <td>Manual Test</td>
        </tr>
    </table>
</body>
</html>";

echo "Sending email...\n\n";

$result = $mailer->sendMail(
    MAIL_TO_EMAIL,
    'Test Contact Form - Troubleshooting',
    $testMessage,
    MAIL_FROM_EMAIL,
    MAIL_FROM_NAME,
    'test@example.com'
);

echo "\n";

if ($result['success']) {
    echo "✓ SUCCESS! Email sent successfully.\n";
    echo "  Check " . MAIL_TO_EMAIL . " for the test email.\n";
} else {
    echo "❌ FAILED! Email could not be sent.\n";
    echo "  Error: " . $result['error'] . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
