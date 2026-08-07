<?php
// Same session configuration as index.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "<h2>Captcha Validation Debug</h2>";

// Test the exact captcha flow
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Processing Form Submission</h3>";
    
    $userInput = $_POST['verify'] ?? '';
    echo "<p><strong>User entered:</strong> '" . htmlspecialchars($userInput) . "'</p>";
    echo "<p><strong>User input length:</strong> " . strlen($userInput) . "</p>";
    echo "<p><strong>User input (trimmed, lowercase):</strong> '" . htmlspecialchars(strtolower(trim($userInput))) . "'</p>";
    
    if (isset($_SESSION['captcha_code'])) {
        echo "<p><strong>Session captcha:</strong> '" . htmlspecialchars($_SESSION['captcha_code']) . "'</p>";
        echo "<p><strong>Session captcha length:</strong> " . strlen($_SESSION['captcha_code']) . "</p>";
        echo "<p><strong>Session captcha (trimmed, lowercase):</strong> '" . htmlspecialchars(strtolower(trim($_SESSION['captcha_code']))) . "'</p>";
        
        $match = (strtolower(trim($userInput)) === strtolower(trim($_SESSION['captcha_code'])));
        echo "<p style='color: " . ($match ? 'green' : 'red') . "; font-weight: bold; font-size: 18px;'>";
        echo $match ? "✓ VALIDATION PASSED" : "✗ VALIDATION FAILED";
        echo "</p>";
        
        if (!$match) {
            echo "<h4>Character by character comparison:</h4>";
            $user = strtolower(trim($userInput));
            $session = strtolower(trim($_SESSION['captcha_code']));
            echo "<table border='1'>";
            echo "<tr><th>Position</th><th>User Input</th><th>Session Code</th><th>Match</th></tr>";
            $maxLen = max(strlen($user), strlen($session));
            for ($i = 0; $i < $maxLen; $i++) {
                $userChar = isset($user[$i]) ? $user[$i] : '';
                $sessionChar = isset($session[$i]) ? $session[$i] : '';
                $charMatch = $userChar === $sessionChar;
                echo "<tr style='background:" . ($charMatch ? '#d4edda' : '#f8d7da') . ";'>";
                echo "<td>{$i}</td>";
                echo "<td>'" . htmlspecialchars($userChar) . "' (" . ord($userChar) . ")</td>";
                echo "<td>'" . htmlspecialchars($sessionChar) . "' (" . ord($sessionChar) . ")</td>";
                echo "<td>" . ($charMatch ? "✓" : "✗") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Don't clear session for debugging
        // unset($_SESSION['captcha_code']);
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> No captcha found in session!</p>";
    }
    
    echo "<h3>All Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
} else {
    echo "<h3>Generate New Captcha and Test</h3>";
    echo "<p>Captcha Image:</p>";
    echo "<img src='create.php?t=" . time() . "' alt='Captcha' style='border: 2px solid #333; background: white;'><br><br>";
    
    // Force session write and reload to check if captcha was stored
    session_write_close();
    session_start();
    
    if (isset($_SESSION['captcha_code'])) {
        echo "<p style='color: green;'><strong>Captcha stored in session:</strong> '" . htmlspecialchars($_SESSION['captcha_code']) . "'</p>";
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> Captcha NOT stored in session!</p>";
    }
    
    echo "<form method='post'>";
    echo "<p>Enter the exact code from the image: <input type='text' name='verify' required style='padding: 5px; font-size: 16px;'></p>";
    echo "<p><button type='submit' style='padding: 8px 16px; font-size: 16px;'>Test Validation</button></p>";
    echo "</form>";
    
    echo "<h3>Session Debug Info:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}
?>
