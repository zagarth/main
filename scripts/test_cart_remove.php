<?php
session_start();

echo "<h2>Testing Cart Add and Remove Functionality</h2>";

// Generate a CSRF token for testing
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h3>Step 1: Adding an item to cart</h3>";

$addData = [
    'csrf_token' => $_SESSION['csrf_token'],
    'action' => 'add',
    'collection' => 'accessories',
    'item_id' => 'ACC_CROSS_001',
    'category' => 'crosses',
    'name' => 'Silver Celtic Cross',
    'image' => '/images/accessories/crosses/celtic_cross.jpg',
    'quantity' => 1
];

// Simulate adding item
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $addData;

ob_start();
include 'cart/cart_api.php';
$addResponse = ob_get_clean();
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>$addResponse</div>";

echo "<h3>Current Cart Contents:</h3>";
echo "<pre>" . print_r($_SESSION['cart'] ?? 'No cart data', true) . "</pre>";

// Get cart items to see what cart_item_id was generated
echo "<h3>Step 2: Getting cart items</h3>";
unset($_POST);
$_GET = ['action' => 'get_items'];

ob_start();
include 'cart/cart_api.php';
$itemsResponse = ob_get_clean();
echo "<div style='border: 1px solid #ddd; padding: 5px;'>$itemsResponse</div>";

// Parse the response to get cart item ID
$itemsData = json_decode($itemsResponse, true);
if ($itemsData && isset($itemsData['cart']) && !empty($itemsData['cart'])) {
    $cartItemIds = array_keys($itemsData['cart']);
    $firstCartItemId = $cartItemIds[0];
    
    echo "<h3>Step 3: Attempting to remove item with ID: $firstCartItemId</h3>";
    
    // Test remove
    unset($_GET);
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'action' => 'remove',
        'cart_item_id' => $firstCartItemId
    ];
    
    ob_start();
    include 'cart/cart_api.php';
    $removeResponse = ob_get_clean();
    echo "<div style='border: 1px solid #ffcccc; padding: 10px;'>$removeResponse</div>";
    
    echo "<h3>Cart Contents After Remove:</h3>";
    echo "<pre>" . print_r($_SESSION['cart'] ?? 'No cart data', true) . "</pre>";
} else {
    echo "<p style='color: red;'>No items found in cart to remove!</p>";
}
?>