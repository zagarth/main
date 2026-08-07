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
    'category' => 'crosses',
    'itemName' => 'Silver Celtic Cross',
    'imageSrc' => '/images/accessories/crosses/celtic_cross.jpg'
];

echo "<h3>Test Data Being Sent:</h3>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Simulate the POST request by including the cart API directly
$_POST = $testData;

echo "<h3>Cart API Response:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
ob_start();
include 'cart/cart_api.php';
$response = ob_get_clean();
echo $response;
echo "</div>";

echo "<h3>Session Cart Contents:</h3>";
echo "<pre>" . print_r($_SESSION['cart'] ?? 'No cart data', true) . "</pre>";

echo "<h3>Test Additional Collections:</h3>";

// Test other collections
$testCollections = [
    ['collection' => 'bands', 'category' => 'celtic', 'itemName' => 'Gold Celtic Band'],
    ['collection' => 'engagement', 'category' => 'MK_series', 'itemName' => 'Diamond Solitaire MK-101'],
    ['collection' => 'family', 'category' => 'mother', 'itemName' => 'Mother\'s Ring'],
    ['collection' => 'corp', 'category' => 'executive', 'itemName' => 'Executive Award Pin']
];

foreach ($testCollections as $test) {
    $test['csrf_token'] = $_SESSION['csrf_token'];
    $test['action'] = 'add';
    $test['imageSrc'] = '/images/' . $test['collection'] . '/sample.jpg';
    
    $_POST = $test;
    echo "<h4>Testing: {$test['collection']} - {$test['category']} - {$test['itemName']}</h4>";
    ob_start();
    include 'cart/cart_api.php';
    $response = ob_get_clean();
    echo "<div style='border: 1px solid #ddd; padding: 5px; margin: 5px 0;'>$response</div>";
}

echo "<h3>Final Cart Contents:</h3>";
echo "<pre>" . print_r($_SESSION['cart'] ?? 'No cart data', true) . "</pre>";
?>