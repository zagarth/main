<?php
session_start();
require_once 'cart/cart_session.php';

// Initialize cart session
$cartSession = new CartSession();

// Add a test item to the cart
$testItem = [
    'collection' => 'bands',
    'item_id' => 'TEST001',
    'category' => 'rings',
    'name' => 'Test Wedding Band',
    'price' => 299.99,
    'image' => 'test-band.jpg',
    'quantity' => 1,
    'customization' => '',
    'notes' => 'Test item for checkout'
];

$result = $cartSession->addItem($testItem);

echo "<h2>Test Item Added to Cart</h2>";
echo "<pre>";
print_r($result);
echo "</pre>";

echo "<h2>Cart Summary</h2>";
echo "<pre>";
print_r($cartSession->getCartSummary());
echo "</pre>";

echo "<p><a href='cart/checkout.php'>Go to Checkout</a></p>";
?>