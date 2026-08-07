<?php
// Test create.php functionality
$_GET['PHPSESSID'] = '5galsunlpf481f6bfu0ms7lja8';

// Include create.php to test its functionality
ob_start();
include 'create.php';
$output = ob_get_contents();
ob_end_clean();

// Check what was generated
echo "Create.php output length: " . strlen($output) . " bytes\n";
echo "Content starts with PNG header: " . (substr($output, 0, 8) === "\x89PNG\r\n\x1a\n" ? "Yes" : "No") . "\n";

// Check if session was properly restored
session_start();
if (isset($_SESSION['captcha'])) {
    echo "Captcha found in session: " . $_SESSION['captcha'] . "\n";
} else {
    echo "No captcha found in session\n";
}
?>
