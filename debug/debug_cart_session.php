<?php
session_start();

echo "<h2>Cart Debug Information</h2>";
echo "<pre>";

echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n\n";

echo "Full Session Contents:\n";
var_dump($_SESSION);

echo "\nCart Status:\n";
if (isset($_SESSION['cart'])) {
    echo "Cart exists in session\n";
    echo "Cart contents:\n";
    var_dump($_SESSION['cart']);
    echo "Cart count: " . count($_SESSION['cart']) . "\n";
    echo "Cart empty? " . (empty($_SESSION['cart']) ? 'YES' : 'NO') . "\n";
} else {
    echo "Cart does NOT exist in session\n";
}

echo "\nCookies:\n";
var_dump($_COOKIE);

echo "</pre>";

// Let's also check the cart_session.php file
echo "<h2>Cart Session File Check</h2>";
$cart_session_file = __DIR__ . '/cart_session.php';
if (file_exists($cart_session_file)) {
    echo "<p>cart_session.php exists</p>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents($cart_session_file));
    echo "</pre>";
} else {
    echo "<p>cart_session.php does NOT exist</p>";
}
?>