<?php
/**
 * Mail Server Test Script
 * Tests connection and email sending to raspimail
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'mail_config.php';
require_once 'session_manager.php';

echo "<h2>Mail Server Test</h2>\n";
echo "<pre>\n";

echo "=== Configuration ===\n";
echo "Mail Host: " . MAIL_HOST . "\n";
echo "Mail Port: " . MAIL_PORT . "\n";
echo "Encryption: " . MAIL_ENCRYPTION . "\n";
echo "Username: " . MAIL_USERNAME . "\n";
echo "From Email: " . MAIL_FROM_EMAIL . "\n";
echo "To Email: " . MAIL_TO_EMAIL . "\n";
echo "\n";

echo "=== Testing Connectivity ===\n";
$ip = gethostbyname(MAIL_HOST);
echo "Hostname: " . MAIL_HOST . "\n";
echo "Resolved IP: " . $ip . "\n";

// Test port connectivity
$testSocket = @fsockopen($ip, MAIL_PORT, $errno, $errstr, 10);
if ($testSocket) {
    echo "Port " . MAIL_PORT . ": CONNECTED\n";
    fclose($testSocket);
} else {
    echo "Port " . MAIL_PORT . ": FAILED - $errno: $errstr\n";
}
echo "\n";

echo "=== Testing SMTP Connection ===\n";

// Include the SimpleSMTP class from packemail.php
require_once 'packemail.php';

// Create mailer instance
$mailer = new SimpleSMTP(MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION);

// Test email
$testSubject = "Test Email from Cadman Website - " . date('Y-m-d H:i:s');
$testMessage = "<html><body><h2>Test Email</h2><p>This is a test email sent at " . date('Y-m-d H:i:s') . "</p><p>If you receive this, the mail server is working correctly.</p></body></html>";

echo "Attempting to send test email...\n\n";

$result = $mailer->sendMail(
    MAIL_TO_EMAIL,
    $testSubject,
    $testMessage,
    MAIL_FROM_EMAIL,
    MAIL_FROM_NAME,
    MAIL_FROM_EMAIL
);

echo "=== Result ===\n";
if ($result['success']) {
    echo "✓ SUCCESS: " . $result['message'] . "\n";
    echo "\nCheck your inbox at: " . MAIL_TO_EMAIL . "\n";
} else {
    echo "✗ FAILED: " . $result['error'] . "\n";
    if (isset($result['user_message'])) {
        echo "User Message: " . $result['user_message'] . "\n";
    }
}

echo "\n=== Check Logs ===\n";
echo "Log file: /tmp/cadman_mail.log\n";
if (file_exists('/tmp/cadman_mail.log')) {
    echo "\nLast 20 log entries:\n";
    echo "---\n";
    $logs = file('/tmp/cadman_mail.log');
    $logs = array_slice($logs, -20);
    echo implode('', $logs);
} else {
    echo "Log file not created yet.\n";
}

echo "</pre>\n";
?>
