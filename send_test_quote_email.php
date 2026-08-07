<?php
/**
 * Direct email test for quote request functionality
 */

// Set up environment for testing
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

// Start session and set up test data
session_start();

// Simulate product quote data
$_SESSION['quote_product_data'] = [
    'product_id' => '5310',
    'product_name' => 'Celtic Knot Wedding Band',
    'category' => 'celtic', 
    'collection' => 'Celtic',
    'configured_options' => [
        'Metal' => '14K White Gold',
        'Size' => '8',
        'Finish' => 'Polished'
    ],
    'timestamp' => date('c')
];

$_SESSION['contact_source'] = [
    'page' => 'Product Modal',
    'section' => 'Quote Request - Celtic Knot Wedding Band',
    'url' => 'http://localhost/homesite/Celtic.php',
    'timestamp' => date('c'),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test Browser'
];

// Include the mail functionality directly
require_once 'mail_config.php';

// Include SimpleSMTP class from packemail.php
$packemail_content = file_get_contents('packemail.php');

// Extract just the SimpleSMTP class
preg_match('/class SimpleSMTP.*?(?=class|\?>.*)$/s', $packemail_content, $matches);
eval($matches[0] . '}'); // Add closing brace and evaluate

// Now test sending email with our quote data
$name = 'Test Customer';
$email = 'test@example.com';
$message = 'I would like to request a quote for this Celtic ring. Please provide pricing and availability.';

// Build email content with product info (copied from packemail.php logic)
$subject = "Contact Form Submission - Cadman Manufacturing";

// Build tracking information
$trackingRows = '';
if (isset($_SESSION['contact_source'])) {
    $source = $_SESSION['contact_source'];
    $trackingRows = "
        <tr style='background-color: #f0f8ff;'>
            <td colspan='2'><strong style='color: #0066cc;'>Source Tracking Information</strong></td>
        </tr>
        <tr>
            <td><strong>Source Page:</strong></td>
            <td>{$source['page']}</td>
        </tr>
        <tr>
            <td><strong>Source Section:</strong></td>
            <td>{$source['section']}</td>
        </tr>
        <tr>
            <td><strong>Page URL:</strong></td>
            <td><a href='{$source['url']}'>{$source['url']}</a></td>
        </tr>
        <tr>
            <td><strong>Clicked At:</strong></td>
            <td>{$source['timestamp']}</td>
        </tr>";
}

// Build product information
$productRows = '';
if (isset($_SESSION['quote_product_data'])) {
    $product = $_SESSION['quote_product_data'];
    $productRows = "
        <tr style='background-color: #fff4e6;'>
            <td colspan='2'><strong style='color: #ff6600;'>Product Quote Request</strong></td>
        </tr>
        <tr>
            <td><strong>Product ID:</strong></td>
            <td>{$product['product_id']}</td>
        </tr>
        <tr>
            <td><strong>Product Name:</strong></td>
            <td>{$product['product_name']}</td>
        </tr>
        <tr>
            <td><strong>Category:</strong></td>
            <td>{$product['category']}</td>
        </tr>
        <tr>
            <td><strong>Collection:</strong></td>
            <td>{$product['collection']}</td>
        </tr>";
    
    // Add configured options
    if (!empty($product['configured_options'])) {
        $productRows .= "
        <tr>
            <td><strong>Selected Options:</strong></td>
            <td>";
        foreach ($product['configured_options'] as $option => $value) {
            $productRows .= "<br>• " . htmlspecialchars($option) . ": " . htmlspecialchars($value);
        }
        $productRows .= "</td>
        </tr>";
    }
    
    $productRows .= "
        <tr>
            <td><strong>Product Viewed At:</strong></td>
            <td>{$product['timestamp']}</td>
        </tr>";
}

$emailMessage = "
<html>
<head>
    <title>Contact Form Submission</title>
</head>
<body>
    <h2>New Contact Form Submission</h2>
    <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>
        <tr>
            <td><strong>Name:</strong></td>
            <td>{$name}</td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td>{$email}</td>
        </tr>
        <tr>
            <td><strong>Message:</strong></td>
            <td>" . nl2br($message) . "</td>
        </tr>
        <tr>
            <td><strong>Date:</strong></td>
            <td>" . date('Y-m-d H:i:s') . "</td>
        </tr>
        <tr>
            <td><strong>IP Address:</strong></td>
            <td>127.0.0.1</td>
        </tr>
        {$trackingRows}
        {$productRows}
    </table>
</body>
</html>";

echo "Attempting to send email...\n";
echo "Subject: {$subject}\n";
echo "To: " . MAIL_TO_EMAIL . "\n\n";

$mailer = new SimpleSMTP(MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION);
$result = $mailer->sendMail(
    MAIL_TO_EMAIL,
    $subject,
    $emailMessage,
    MAIL_FROM_EMAIL,
    MAIL_FROM_NAME,
    $email
);

if ($result['success']) {
    echo "✅ EMAIL SENT SUCCESSFULLY!\n";
    echo "Check your email inbox at: " . MAIL_TO_EMAIL . "\n";
    echo "The email includes both contact info AND product details!\n";
} else {
    echo "❌ EMAIL FAILED TO SEND\n";
    echo "Error: " . $result['error'] . "\n";
}

echo "\nEmail content preview:\n";
echo "======================\n";
echo strip_tags($emailMessage) . "\n";
?>