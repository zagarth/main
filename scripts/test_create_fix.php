<?php
// Test create.php functionality
echo "Testing create.php functionality...\n";

// Set up the environment
$_GET['PHPSESSID'] = 'test123';
$_GET['debug'] = '1';

// Capture output
ob_start();

// Include create.php
include 'create.php';

$output = ob_get_contents();
ob_end_clean();

echo "Output length: " . strlen($output) . " characters\n";

if (strpos($output, 'Generated word') !== false) {
    echo "✓ Debug output generated successfully\n";
} else {
    echo "✗ No debug output found\n";
}

if (isset($GLOBALS['CURRENT_CAPTCHA']) && !empty($GLOBALS['CURRENT_CAPTCHA'])) {
    echo "✓ Global captcha set: " . $GLOBALS['CURRENT_CAPTCHA'] . "\n";
} else {
    echo "✗ Global captcha not set\n";
}

echo "\nFirst 200 characters of output:\n";
echo substr($output, 0, 200) . "\n";
?>
