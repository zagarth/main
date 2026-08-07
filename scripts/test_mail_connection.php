<?php
/**
 * Mail Server Connection Test
 * Tests connectivity to raspimail and diagnoses issues
 */

require_once 'mail_config.php';

echo "=== MAIL SERVER CONNECTION TEST ===\n\n";

// Test 1: DNS Resolution
echo "1. DNS Resolution Test:\n";
$hostname = MAIL_HOST;
echo "   Resolving: $hostname\n";
$ip = gethostbyname($hostname);
if ($ip === $hostname) {
    echo "   ❌ FAILED: Cannot resolve hostname '$hostname'\n";
} else {
    echo "   ✓ SUCCESS: $hostname resolves to $ip\n";
}
echo "\n";

// Test 2: Port Connectivity
echo "2. Port Connectivity Test:\n";
$ports_to_test = [
    25 => 'SMTP',
    587 => 'Submission (TLS)',
    465 => 'SMTPS (SSL)',
    2525 => 'Alternate SMTP'
];

foreach ($ports_to_test as $port => $description) {
    echo "   Testing port $port ($description)...\n";
    $connection = @fsockopen($hostname, $port, $errno, $errstr, 5);
    if ($connection) {
        echo "   ✓ Port $port is OPEN\n";
        fclose($connection);
    } else {
        echo "   ❌ Port $port is CLOSED ($errno: $errstr)\n";
    }
}
echo "\n";

// Test 3: SMTP Banner
echo "3. SMTP Banner Test (port " . MAIL_PORT . "):\n";
$fp = @fsockopen($hostname, MAIL_PORT, $errno, $errstr, 10);
if ($fp) {
    echo "   Connected successfully!\n";
    $banner = fgets($fp, 512);
    echo "   Banner: " . trim($banner) . "\n";
    
    // Try EHLO command
    fputs($fp, "EHLO test.local\r\n");
    $response = '';
    while ($line = fgets($fp, 512)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    echo "   EHLO Response:\n";
    echo "   " . str_replace("\n", "\n   ", trim($response)) . "\n";
    
    fputs($fp, "QUIT\r\n");
    fclose($fp);
    echo "   ✓ SMTP communication successful\n";
} else {
    echo "   ❌ Cannot connect: $errstr ($errno)\n";
}
echo "\n";

// Test 4: Authentication Test
echo "4. Authentication Test:\n";
echo "   Username: " . MAIL_USERNAME . "\n";
echo "   Password: " . (MAIL_PASSWORD ? str_repeat('*', strlen(MAIL_PASSWORD)) : 'NOT SET') . "\n";
echo "   Auth Required: " . (MAIL_USE_AUTH ? 'YES' : 'NO') . "\n";
echo "\n";

// Test 5: Current Configuration Summary
echo "5. Current Mail Configuration:\n";
echo "   Primary Host: " . MAIL_HOST . "\n";
echo "   Backup Host: " . MAIL_HOST_BACKUP . "\n";
echo "   Port: " . MAIL_PORT . "\n";
echo "   Encryption: " . MAIL_ENCRYPTION . "\n";
echo "   From: " . MAIL_FROM_EMAIL . " (" . MAIL_FROM_NAME . ")\n";
echo "   To: " . MAIL_TO_EMAIL . "\n";
echo "   Timeout: " . MAIL_TIMEOUT . " seconds\n";
echo "   Debug Mode: " . (MAIL_DEBUG ? 'ENABLED' : 'DISABLED') . "\n";
echo "\n";

// Test 6: File Permissions
echo "6. File Permissions:\n";
$files_to_check = [
    'packemail.php',
    'contact_form.php',
    'contact_modal.php',
    'mail_config.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "   $file: $perms\n";
    } else {
        echo "   $file: NOT FOUND\n";
    }
}
echo "\n";

// Test 7: Session Check
echo "7. PHP Session Test:\n";
session_start();
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "\n";

// Test 8: Log File Permissions
echo "8. Log File Test:\n";
$log_file = '/tmp/cadman_mail.log';
if (file_exists($log_file)) {
    echo "   Log file exists: $log_file\n";
    echo "   Size: " . filesize($log_file) . " bytes\n";
    echo "   Writable: " . (is_writable($log_file) ? 'YES' : 'NO') . "\n";
} else {
    echo "   Log file does not exist, attempting to create...\n";
    $result = @file_put_contents($log_file, "Test log entry\n", FILE_APPEND);
    if ($result !== false) {
        echo "   ✓ Successfully created log file\n";
    } else {
        echo "   ❌ Cannot create log file (check /tmp permissions)\n";
    }
}
echo "\n";

echo "=== TEST COMPLETE ===\n";
echo "\nTo send a test email, run: php test_send_email.php\n";
?>
