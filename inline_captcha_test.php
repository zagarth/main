<?php
// Alternative captcha approach - generate inline with the form
function generateInlineCaptcha() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate captcha code
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    // Store in session
    $_SESSION['captcha_code'] = $code;
    
    // Create base64 encoded image
    $im = imagecreate(120, 40);
    $bg_color = imagecolorallocate($im, 240, 240, 240);
    $border_color = imagecolorallocate($im, 100, 100, 100);
    $text_color = imagecolorallocate($im, 20, 20, 20);
    
    // Add border
    imagerectangle($im, 0, 0, 119, 39, $border_color);
    
    // Add text
    imagestring($im, 5, 30, 12, $code, $text_color);
    
    // Convert to base64
    ob_start();
    imagejpeg($im);
    $imageData = ob_get_clean();
    imagedestroy($im);
    
    return 'data:image/jpeg;base64,' . base64encode($imageData);
}

// Test the inline captcha
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "<h2>Inline Captcha Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['captcha'] ?? '';
    $sessionCode = $_SESSION['captcha_code'] ?? '';
    
    echo "<h3>Validation Result</h3>";
    echo "<p>You entered: <strong>" . htmlspecialchars($userInput) . "</strong></p>";
    echo "<p>Session had: <strong>" . htmlspecialchars($sessionCode) . "</strong></p>";
    
    $match = (strtoupper(trim($userInput)) === strtoupper(trim($sessionCode)));
    echo "<p style='color: " . ($match ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>";
    echo $match ? "✓ SUCCESS!" : "✗ FAILED!";
    echo "</p>";
    
    echo "<p>Session ID: " . session_id() . "</p>";
    
    unset($_SESSION['captcha_code']);
}

$captchaImage = generateInlineCaptcha();

echo "<h3>Inline Captcha (no separate request)</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Captcha stored: " . ($_SESSION['captcha_code'] ?? 'NONE') . "</p>";

echo "<img src='{$captchaImage}' alt='Captcha' style='border: 2px solid #000;'><br><br>";

echo "<form method='post'>";
echo "<input type='text' name='captcha' placeholder='Enter captcha' required style='padding: 10px; font-size: 16px;'>";
echo "<button type='submit' style='padding: 10px 20px; font-size: 16px;'>Test</button>";
echo "</form>";
?>
