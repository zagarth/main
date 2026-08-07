<?php
// Test captcha with same session config as main site
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "<h2>Simple Captcha Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['captcha'] ?? '';
    
    echo "<h3>Validation Result</h3>";
    echo "<p>You entered: <strong>" . htmlspecialchars($userInput) . "</strong></p>";
    echo "<p>Session had: <strong>" . htmlspecialchars($_SESSION['captcha_code'] ?? 'NONE') . "</strong></p>";
    
    if (isset($_SESSION['captcha_code'])) {
        $sessionCode = $_SESSION['captcha_code'];
        $userUpper = strtoupper(trim($userInput));
        $sessionUpper = strtoupper(trim($sessionCode));
        
        echo "<p>Comparing: '{$userUpper}' vs '{$sessionUpper}'</p>";
        
        $match = ($userUpper === $sessionUpper);
        echo "<p style='color: " . ($match ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>";
        echo $match ? "✓ SUCCESS!" : "✗ FAILED!";
        echo "</p>";
        
        unset($_SESSION['captcha_code']); // Clear after use
    } else {
        echo "<p style='color: red;'>ERROR: No captcha in session!</p>";
    }
} else {
    echo "<p>1. Look at the captcha image below</p>";
    echo "<p>2. Enter the exact code you see (case doesn't matter)</p>";
    echo "<p>3. Click Submit</p>";
    
    echo "<br><img src='create.php?t=" . time() . "' alt='Captcha' style='border: 3px solid #000; padding: 5px; background: white;'><br><br>";
    
    echo "<form method='post'>";
    echo "<input type='text' name='captcha' placeholder='Enter captcha code' style='padding: 10px; font-size: 18px; width: 200px;' required>";
    echo "<button type='submit' style='padding: 10px 20px; font-size: 18px; margin-left: 10px;'>Submit</button>";
    echo "</form>";
}
?>
