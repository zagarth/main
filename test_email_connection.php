<?php
// Simple email test for quote functionality
require_once 'mail_config.php';

echo "Testing SMTP Connection...\n";
echo "Mail Host: " . MAIL_HOST . "\n";
echo "Mail Port: " . MAIL_PORT . "\n";
echo "Mail To: " . MAIL_TO_EMAIL . "\n";

// Test basic SMTP connectivity
$socket = @fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 10);
if ($socket) {
    echo "✅ SMTP Server reachable at " . MAIL_HOST . ":" . MAIL_PORT . "\n";
    fclose($socket);
} else {
    echo "❌ SMTP Server NOT reachable: $errstr ($errno)\n";
    
    // Try backup server
    $socket = @fsockopen(MAIL_HOST_BACKUP, MAIL_PORT, $errno, $errstr, 10);
    if ($socket) {
        echo "✅ Backup SMTP Server reachable at " . MAIL_HOST_BACKUP . ":" . MAIL_PORT . "\n";
        fclose($socket);
    } else {
        echo "❌ Backup SMTP Server also NOT reachable: $errstr ($errno)\n";
    }
}

// Now let's create a minimal test of our quote request system
session_start();

// Set up test data as if user clicked "Request Quote"
$_SESSION['quote_product_data'] = [
    'product_id' => '5310',
    'product_name' => 'Celtic Knot Wedding Band',
    'category' => 'celtic',
    'collection' => 'Celtic',
    'configured_options' => [
        'Metal' => '14K White Gold',
        'Size' => '8'
    ],
    'timestamp' => date('c')
];

$_SESSION['contact_source'] = [
    'page' => 'Product Modal',
    'section' => 'Quote Request - Celtic Knot Wedding Band',
    'url' => 'http://localhost/homesite/Celtic.php',
    'timestamp' => date('c')
];

echo "\n📧 Quote data captured:\n";
print_r($_SESSION['quote_product_data']);

echo "\n🎯 This data will be included in the email when contact form is submitted.\n";
echo "You can now manually test by:\n";
echo "1. Going to your website\n";
echo "2. Opening any ProductModal\n";  
echo "3. Clicking 'Request Quote'\n";
echo "4. Filling out the contact form\n";
echo "5. The email you receive will include the product details!\n";
?>