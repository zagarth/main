<?php
session_start();
require_once 'mail_config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Contact Form Complete Test</title></head><body>";
echo "<h1>Complete Contact Form Functionality Test</h1>";

// Test 1: Check configuration
echo "<h2>Test 1: Configuration Check</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><td><strong>Setting</strong></td><td><strong>Value</strong></td><td><strong>Status</strong></td></tr>";

$configs = [
    'CAPTCHA_SESSION_KEY' => CAPTCHA_SESSION_KEY,
    'MAIL_HOST' => MAIL_HOST,
    'MAIL_TO_EMAIL' => MAIL_TO_EMAIL,
    'MAIL_FROM_EMAIL' => MAIL_FROM_EMAIL,
    'MAIL_PORT' => MAIL_PORT,
    'MAIL_USE_AUTH' => MAIL_USE_AUTH ? 'true' : 'false'
];

foreach ($configs as $key => $value) {
    $status = !empty($value) ? '✓ OK' : '✗ Missing';
    $color = !empty($value) ? 'green' : 'red';
    echo "<tr><td>{$key}</td><td>{$value}</td><td style='color: {$color};'>{$status}</td></tr>";
}
echo "</table>";

// Test 2: Captcha functionality
echo "<h2>Test 2: Captcha System</h2>";
echo "<p>Generated captcha image:</p>";
echo "<img src='create.php?t=" . time() . "' alt='Captcha' style='border: 1px solid #ccc;'><br><br>";

echo "<p><strong>Session data after captcha generation:</strong></p>";
if (isset($_SESSION['captcha_code'])) {
    echo "<p style='color: green;'>✓ Captcha session exists: " . $_SESSION['captcha_code'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Captcha session NOT found</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// Test 3: File accessibility
echo "<h2>Test 3: File Accessibility</h2>";
$files_to_check = [
    'contact_form.php' => file_exists('contact_form.php'),
    'packemail.php' => file_exists('packemail.php'),
    'create.php' => file_exists('create.php'),
    'mail_config.php' => file_exists('mail_config.php')
];

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><td><strong>File</strong></td><td><strong>Status</strong></td></tr>";
foreach ($files_to_check as $file => $exists) {
    $status = $exists ? '✓ Exists' : '✗ Missing';
    $color = $exists ? 'green' : 'red';
    echo "<tr><td>{$file}</td><td style='color: {$color};'>{$status}</td></tr>";
}
echo "</table>";

// Test 4: Contact form rendering
echo "<h2>Test 4: Contact Form Rendering</h2>";
if (file_exists('contact_form.php')) {
    try {
        include 'contact_form.php';
        renderContactForm('Test Contact Form', 'This is a test message');
        echo "<p style='color: green;'>✓ Contact form rendered successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error rendering contact form: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ contact_form.php not found</p>";
}

// Test 5: Form processing simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Test 5: Form Processing Results</h2>";
    
    // Simulate the validation functions from packemail.php
    function validateCaptcha($userInput) {
        if (!isset($_SESSION[CAPTCHA_SESSION_KEY])) {
            return false;
        }
        
        $sessionCaptcha = $_SESSION[CAPTCHA_SESSION_KEY];
        unset($_SESSION[CAPTCHA_SESSION_KEY]);
        
        return md5(strtolower(trim($userInput))) === $sessionCaptcha;
    }
    
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $verify = sanitizeInput($_POST['verify'] ?? '');
    
    $errors = [];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><td><strong>Field</strong></td><td><strong>Value</strong></td><td><strong>Validation</strong></td></tr>";
    
    // Name validation
    $nameValid = !empty($name) && $name !== 'name';
    echo "<tr><td>Name</td><td>" . htmlspecialchars($name) . "</td><td style='color: " . ($nameValid ? 'green' : 'red') . ";'>" . ($nameValid ? '✓ Valid' : '✗ Invalid') . "</td></tr>";
    if (!$nameValid) $errors[] = 'Name is required';
    
    // Email validation
    $emailValid = !empty($email) && $email !== 'email@email.com' && isValidEmail($email);
    echo "<tr><td>Email</td><td>" . htmlspecialchars($email) . "</td><td style='color: " . ($emailValid ? 'green' : 'red') . ";'>" . ($emailValid ? '✓ Valid' : '✗ Invalid') . "</td></tr>";
    if (!$emailValid) $errors[] = 'Valid email is required';
    
    // Message validation
    $messageValid = !empty($message) && strlen($message) >= 10;
    echo "<tr><td>Message</td><td>" . htmlspecialchars(substr($message, 0, 50)) . "...</td><td style='color: " . ($messageValid ? 'green' : 'red') . ";'>" . ($messageValid ? '✓ Valid' : '✗ Invalid') . "</td></tr>";
    if (!$messageValid) $errors[] = 'Message must be at least 10 characters';
    
    // Captcha validation
    $captchaValid = validateCaptcha($verify);
    echo "<tr><td>Captcha</td><td>" . htmlspecialchars($verify) . "</td><td style='color: " . ($captchaValid ? 'green' : 'red') . ";'>" . ($captchaValid ? '✓ Valid' : '✗ Invalid') . "</td></tr>";
    if (!$captchaValid) $errors[] = 'Captcha verification failed';
    
    echo "</table>";
    
    if (empty($errors)) {
        echo "<h3 style='color: green;'>✓ All validations passed! Form would be processed successfully.</h3>";
        echo "<p><strong>Next step:</strong> Email would be sent to " . MAIL_TO_EMAIL . "</p>";
    } else {
        echo "<h3 style='color: red;'>✗ Validation errors found:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: red;'>{$error}</li>";
        }
        echo "</ul>";
    }
}

echo "<h2>Test 6: Check Log File</h2>";
if (file_exists('/tmp/cadman_mail.log')) {
    echo "<p style='color: green;'>✓ Log file exists</p>";
    $logContent = file_get_contents('/tmp/cadman_mail.log');
    if (!empty($logContent)) {
        echo "<h3>Recent log entries:</h3>";
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -10);
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
        echo htmlspecialchars(implode("\n", $recentLines));
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠ Log file is empty</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Log file doesn't exist yet (will be created on first form submission)</p>";
}

echo "</body></html>";
?>
