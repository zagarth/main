<?php
session_start();
require_once 'cart/cart_session.php';

// Initialize cart session
$cartSession = new CartSession();

echo "<h2>Debug: Cart Session Issue</h2>";

// Add a test item
$testItem = [
    'collection' => 'bands',
    'item_id' => 'TEST001', 
    'category' => 'rings',
    'name' => 'Test Wedding Band',
    'price' => 299.99,
    'quantity' => 1
];

echo "<h3>1. Adding test item...</h3>";
$result = $cartSession->addItem($testItem);
echo "<pre>Add result: ";
print_r($result);
echo "</pre>";

echo "<h3>2. Cart items after adding:</h3>";
$items = $cartSession->getItems();
echo "<pre>Items: ";
print_r($items);
echo "</pre>";
echo "<p>Items count: " . count($items) . "</p>";
echo "<p>Items empty? " . (empty($items) ? 'YES' : 'NO') . "</p>";

echo "<h3>3. Full cart summary:</h3>";
$summary = $cartSession->getCartSummary();
echo "<pre>";
print_r($summary);
echo "</pre>";

echo "<h3>4. Session contents:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>5. Test checkout logic:</h3>";
if (empty($cartSession->getItems())) {
    echo "<p style='color: red;'>❌ Cart appears empty - would redirect to homepage</p>";
} else {
    echo "<p style='color: green;'>✅ Cart has items - checkout should work</p>";
}

echo "<p><a href='cart/checkout.php' target='_blank'>Test Checkout Page</a></p>";
?>