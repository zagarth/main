<?php
// Quick test for the API access
echo "Testing API access from browser context...\n";

// Set up a test like the browser would
$_GET['product_id'] = '5310';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Include the API file
ob_start();
include 'api/get_celtic_thumbnails.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output;
?>