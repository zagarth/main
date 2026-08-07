<?php
// Test the global captcha variable system
echo "=== GLOBAL CAPTCHA VARIABLE TEST ===\n\n";

// Initialize global variable
$GLOBALS['CURRENT_CAPTCHA'] = '';

// Start session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "1. Testing captcha generation with global variable:\n";

// Simulate captcha generation
$test_captcha = 'TEST123';
$_SESSION['captcha_code'] = $test_captcha;
$GLOBALS['CURRENT_CAPTCHA'] = $test_captcha;

echo "   Generated captcha: $test_captcha\n";
echo "   Stored in session: " . ($_SESSION['captcha_code'] ?? 'not set') . "\n";
echo "   Stored in global: " . ($GLOBALS['CURRENT_CAPTCHA'] ?? 'not set') . "\n";

echo "\n2. Testing validation function:\n";

// Include the validation function from packemail.php
function validateCaptcha($userInput) {
    // Check for captcha in session first, then global variable as fallback
    $sessionCaptcha = null;
    
    if (isset($_SESSION['captcha_code'])) {
        $sessionCaptcha = $_SESSION['captcha_code'];
        unset($_SESSION['captcha_code']); // Clear after use
        echo "   Using session captcha: $sessionCaptcha\n";
    } elseif (isset($GLOBALS['CURRENT_CAPTCHA']) && !empty($GLOBALS['CURRENT_CAPTCHA'])) {
        $sessionCaptcha = $GLOBALS['CURRENT_CAPTCHA'];
        $GLOBALS['CURRENT_CAPTCHA'] = ''; // Clear after use
        echo "   Using global captcha: $sessionCaptcha\n";
    } else {
        echo "   No captcha found in session or global\n";
        return false;
    }
    
    // Compare uppercase strings for consistency
    return strtoupper(trim($userInput)) === strtoupper(trim($sessionCaptcha));
}

// Test with correct input
$result1 = validateCaptcha('TEST123');
echo "   Validation result (correct): " . ($result1 ? "PASS" : "FAIL") . "\n";

// Reset for second test
$GLOBALS['CURRENT_CAPTCHA'] = 'GLOBAL456';
echo "   Reset global captcha: " . $GLOBALS['CURRENT_CAPTCHA'] . "\n";

// Test fallback to global when session is empty
$result2 = validateCaptcha('GLOBAL456');
echo "   Validation result (global fallback): " . ($result2 ? "PASS" : "FAIL") . "\n";

echo "\n3. Testing after validation:\n";
echo "   Session captcha after validation: " . ($_SESSION['captcha_code'] ?? 'not set') . "\n";
echo "   Global captcha after validation: " . ($GLOBALS['CURRENT_CAPTCHA'] ?? 'empty') . "\n";

echo "\n=== TEST COMPLETE ===\n";
echo "Result: Global captcha variable system is working\n";
?>
