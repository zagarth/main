<?php
// Simple test checkout page that shows what's happening
session_start();
require_once __DIR__ . '/cart/cart_session.php';

$cartSession = new CartSession();

echo "<h2>Checkout Debug</h2>";
echo "<p>Session ID: " . session_id() . "</p>";

$items = $cartSession->getItems();
echo "<p>Cart items found: " . count($items) . "</p>";

if (empty($items)) {
    echo "<p style='color: red;'>Cart is empty - would redirect to homepage</p>";
    echo "<p>This is why you're seeing the redirect!</p>";
    
    // Let's add an item right here to test
    $testItem = [
        'collection' => 'bands',
        'item_id' => 'DIRECT_TEST',
        'category' => 'rings', 
        'name' => 'Direct Test Item',
        'price' => 199.99,
        'quantity' => 1
    ];
    
    echo "<h3>Adding test item directly...</h3>";
    $result = $cartSession->addItem($testItem);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    $items = $cartSession->getItems();
    echo "<p>Cart items after adding: " . count($items) . "</p>";
    
    if (!empty($items)) {
        echo "<p style='color: green;'>✅ Now cart has items! <a href='cart/checkout.php'>Try checkout again</a></p>";
    }
} else {
    echo "<p style='color: green;'>Cart has items! Proceeding to checkout...</p>";
    echo "<h3>Items in cart:</h3>";
    echo "<pre>";
    print_r($items);
    echo "</pre>";
    
    echo "<p><strong>Checkout should work now!</strong></p>";
}
?>