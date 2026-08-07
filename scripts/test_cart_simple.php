<?php
session_start();

// Test the cart API with temporary pricing
echo "<h2>Testing Cart API with Temporary Pricing</h2>";

// Generate a CSRF token for testing
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$testData = [
    'csrf_token' => $_SESSION['csrf_token'],
    'action' => 'add',
    'collection' => 'accessories',
    'item_id' => 'ACC_CROSS_001',
    'category' => 'crosses',
    'name' => 'Silver Celtic Cross',
    'image' => '/images/accessories/crosses/celtic_cross.jpg',
    'quantity' => 1
];

echo "<h3>Test Data Being Sent:</h3>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Simulate a proper POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
$_POST = $testData;

echo "<h3>Cart API Response:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";

// Capture the JSON response
ob_start();
include 'cart/cart_api.php';
$response = ob_get_clean();
echo $response;
echo "</div>";

echo "<h3>Session Cart Contents:</h3>";
echo "<pre>" . print_r($_SESSION['cart'] ?? 'No cart data', true) . "</pre>";

// Test getting the cart count
echo "<h3>Testing Get Cart Count:</h3>";
unset($_POST);
$_GET = ['action' => 'get_count'];
ob_start();
include 'cart/cart_api.php';
$countResponse = ob_get_clean();
echo "<div style='border: 1px solid #ddd; padding: 5px;'>$countResponse</div>";

echo "<h3>Testing Get Cart Items:</h3>";
$_GET = ['action' => 'get_items'];
ob_start();
include 'cart/cart_api.php';
$itemsResponse = ob_get_clean();
echo "<div style='border: 1px solid #ddd; padding: 5px;'>$itemsResponse</div>";
?>