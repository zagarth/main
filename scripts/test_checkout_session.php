<?php
session_start();

// Set the session ID from the working session
session_id('sb1um8a5gnunhsfh06cgbefkm7');
session_start();

require_once 'cart/cart_session.php';

$cartSession = new CartSession();

echo "<h2>Session Test for Checkout</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Cart items: " . count($cartSession->getItems()) . "</p>";
echo "<pre>";
print_r($cartSession->getItems());
echo "</pre>";

// Test the exact checkout logic
if (empty($cartSession->getItems())) {
    echo "<p style='color: red;'>Would redirect to homepage (cart empty)</p>";
} else {
    echo "<p style='color: green;'>Checkout would proceed (cart has items)</p>";
}
?>