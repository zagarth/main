<?php 
// Global captcha variable
$GLOBALS['CURRENT_CAPTCHA'] = '';

// Session manager only for compatibility - we use stateless captcha
require_once __DIR__ . '/session_manager.php';

// Get timestamp from URL for cache key
$timestamp = $_GET['t'] ?? time();

error_log("Captcha image requested with timestamp: " . $timestamp);

// Debug mode - don't output image if debug parameter is set
if (isset($_GET['debug'])) {
    header('Content-Type: text/html');
    echo "<h3>Captcha Generation Debug</h3>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session before: ";
    print_r($_SESSION);
    echo "</p>";
}

// Generate captcha code - use only uppercase and numbers for clarity
$ltr_grab = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // Removed confusing characters like I, O, 0, 1
$ltr_grab = str_shuffle($ltr_grab);

$words = "";
for($i=0; $i<=3; $i++){ 
    $words .= $ltr_grab[$i];
}
$word = rand(100,999) . $words;

// Store the captcha word in uppercase for consistency
$captcha_value = strtoupper($word);

// Store in file cache using timestamp as key (stateless approach)
$cacheDir = sys_get_temp_dir() . '/captcha_cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Clean old cache files (older than 10 minutes)
$files = glob($cacheDir . '/captcha_*');
$now = time();
foreach ($files as $file) {
    if (is_file($file) && ($now - filemtime($file)) > 600) {
        unlink($file);
    }
}

// Store captcha with timestamp key
$cacheFile = $cacheDir . '/captcha_' . $timestamp;
file_put_contents($cacheFile, $captcha_value);
chmod($cacheFile, 0644);

error_log("Captcha stored in cache: " . $captcha_value . " for timestamp: " . $timestamp);

// Always store in global variable as fallback
$GLOBALS['CURRENT_CAPTCHA'] = $captcha_value;

// Log for debugging
error_log("Captcha generated: " . $captcha_value . " Session ID: " . session_id());

if (isset($_GET['debug'])) {
    echo "<p>Generated word: " . htmlspecialchars($word) . "</p>";
    echo "<p>Generated word (uppercase): " . htmlspecialchars(strtoupper($word)) . "</p>";
    echo "<p>Stored in session: " . (isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : 'not available') . "</p>";
    echo "<p>Stored in global: " . $GLOBALS['CURRENT_CAPTCHA'] . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session status: " . session_status() . "</p>";
    echo "<p>Session before write_close: ";
    if (session_status() == PHP_SESSION_ACTIVE) {
        print_r($_SESSION);
    } else {
        echo "Session not active";
    }
    echo "</p>";
    
    // Force session write for debug
    if (!session_write_close()) {
        echo "<p style='color: red;'>Failed to write session!</p>";
    } else {
        echo "<p style='color: green;'>Session written successfully</p>";
    }
    
    // Restart to verify it was saved
    if (!session_start()) {
        echo "<p style='color: red;'>Failed to restart session!</p>";
    } else {
        echo "<p style='color: green;'>Session restarted successfully</p>";
    }
    
    echo "<p>Session after write_close and restart: ";
    print_r($_SESSION);
    echo "</p>";
    return;
}

// Force session write for normal operation - with error checking
if (!session_write_close()) {
    error_log("Failed to write session in create.php");
}

// Set content type header for image
header('Content-Type: image/jpeg');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Create image
$im = @imagecreate(120,40) or die("Cannot initialize new GD image stream");

// Set colors
$bg_color = imagecolorallocate($im, 240, 240, 240);
$border_color = imagecolorallocate($im, 100, 100, 100);
$text_color = imagecolorallocate($im, 20, 20, 20);
$noise_color = imagecolorallocate($im, 150, 150, 150);

// Add border
imagerectangle($im, 0, 0, 119, 39, $border_color);

// Add some noise lines
for($i = 0; $i < 3; $i++) {
    imageline($im, rand(0,120), rand(0,40), rand(0,120), rand(0,40), $noise_color);
}

// Add some noise dots
for($i = 0; $i < 20; $i++) {
    imagesetpixel($im, rand(0,120), rand(0,40), $noise_color);
}

// Add text with better positioning
imagestring($im, 5, 30, 12, strtoupper($word), $text_color);

// Output image
imagejpeg($im, null, 90);
imagedestroy($im);
?>