<!DOCTYPE html>
<html>
<head>
    <title>Test Cart Navigation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php include 'cart/cart_display.php'; includeCart(); ?>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('test'); ?>
    
    <div style="padding: 100px 20px; text-align: center;">
        <h1>Test Cart Navigation</h1>
        <p>This page tests the cart button positioning in the navigation.</p>
        
        <div style="margin: 20px 0;">
            <button onclick="testAddToCart()" style="padding: 15px 30px; background: #28a745; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                🛒 Add Test Item to Cart
            </button>
        </div>
        
        <div style="margin: 20px 0;">
            <button onclick="testClearCart()" style="padding: 15px 30px; background: #dc3545; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                🗑️ Clear Cart
            </button>
        </div>
    </div>
    
    <script>
    function testAddToCart() {
        if (typeof cadmanCart !== 'undefined') {
            // Add a test item
            cadmanCart.addItem({
                id: 'test-' + Date.now(),
                name: 'Test Ring',
                collection: 'Test Collection',
                price: 100.00,
                image: 'test.jpg',
                customization: 'Test customization'
            });
            console.log('Test item added to cart');
        } else {
            console.log('cadmanCart not available');
            alert('Cart system not loaded');
        }
    }
    
    function testClearCart() {
        if (typeof cadmanCart !== 'undefined') {
            cadmanCart.clearCart();
            console.log('Cart cleared');
        }
    }
    </script>
</body>
</html>