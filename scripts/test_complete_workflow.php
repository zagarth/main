<?php
// Test complete captcha workflow
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "=== COMPLETE CAPTCHA WORKFLOW TEST ===\n";
$session_id = session_id();
echo "Session ID: $session_id\n\n";

// Step 1: Generate captcha (simulating create.php being called by browser)
echo "1. Generating captcha image...\n";
$_GET['PHPSESSID'] = $session_id;

ob_start();
include 'create.php';
$image_output = ob_get_contents();
ob_end_clean();

echo "   Image generated: " . strlen($image_output) . " bytes\n";

// Check session after image generation
if (isset($_SESSION['captcha'])) {
    $generated_captcha = $_SESSION['captcha'];
    echo "   Captcha stored in session: $generated_captcha\n";
} else {
    echo "   ERROR: No captcha in session after generation\n";
    exit;
}

// Step 2: Simulate form submission with correct captcha
echo "\n2. Simulating form submission...\n";
$_POST = [
    'name' => 'Test User',
    'email' => 'test@example.com', 
    'message' => 'Test message',
    'verify' => $generated_captcha
];

echo "   Submitted captcha: " . $_POST['verify'] . "\n";

// Step 3: Test validation (simulating packemail.php)
function validateCaptcha($input) {
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    
    $session_captcha = strtoupper(trim($_SESSION['captcha']));
    $user_captcha = strtoupper(trim($input));
    
    // Clear the captcha from session after use
    unset($_SESSION['captcha']);
    
    return $session_captcha === $user_captcha;
}

echo "\n3. Validating captcha...\n";
$is_valid = validateCaptcha($_POST['verify']);

if ($is_valid) {
    echo "   ✓ CAPTCHA VALIDATION SUCCESSFUL!\n";
    echo "   Form would be processed and email sent.\n";
} else {
    echo "   ✗ CAPTCHA VALIDATION FAILED!\n";
}

// Check that captcha was cleared from session
if (isset($_SESSION['captcha'])) {
    echo "   WARNING: Captcha still in session after validation\n";
} else {
    echo "   ✓ Captcha properly cleared from session\n";
}

echo "\n=== WORKFLOW COMPLETE ===\n";
?>
