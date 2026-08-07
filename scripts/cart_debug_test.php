<!DOCTYPE html>
<html>
<head>
    <title>Cart Debug Test</title>
</head>
<body>
    <h1>Cart Debug Test</h1>
    
    <div id="debug-info">
        <h3>Cart System Status:</h3>
        <div id="status"></div>
    </div>
    
    <div id="test-buttons">
        <h3>Test Cart Button:</h3>
        <button class="add-to-cart-btn" 
                data-collection="accessories" 
                data-item-id="TEST_001" 
                data-category="crosses" 
                data-name="Test Cross" 
                data-price="125" 
                data-image="/images/test.jpg">
            Add Test Item to Cart
        </button>
    </div>
    
    <div id="cart-status">
        <h3>Cart Status:</h3>
        <div id="cart-info"></div>
    </div>

    <?php include 'navigation.php'; renderNavigation('debug'); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== CART DEBUG TEST ===');
            
            // Check if cart system is loaded
            console.log('cadmanCart exists:', typeof window.cadmanCart !== 'undefined');
            console.log('cadmanCart object:', window.cadmanCart);
            
            // Check cart modal exists
            const cartModal = document.getElementById('cartModal');
            console.log('Cart modal exists:', !!cartModal);
            
            // Check cart button exists
            const cartButton = document.querySelector('.cart-toggle');
            console.log('Cart toggle button exists:', !!cartButton);
            
            // Check if add-to-cart button exists
            const addButton = document.querySelector('.add-to-cart-btn');
            console.log('Add to cart button exists:', !!addButton);
            
            // Update status display
            const statusDiv = document.getElementById('status');
            statusDiv.innerHTML = `
                <p>Cart Object: ${typeof window.cadmanCart !== 'undefined' ? 'LOADED' : 'MISSING'}</p>
                <p>Cart Modal: ${cartModal ? 'EXISTS' : 'MISSING'}</p>
                <p>Cart Button: ${cartButton ? 'EXISTS' : 'MISSING'}</p>
                <p>Add Button: ${addButton ? 'EXISTS' : 'MISSING'}</p>
            `;
            
            // Test clicking the add button
            if (addButton) {
                addButton.addEventListener('click', function() {
                    console.log('Test button clicked!');
                    if (window.cadmanCart) {
                        console.log('Cart object available, testing add...');
                    } else {
                        console.log('Cart object NOT available!');
                    }
                });
            }
            
            // Monitor cart changes
            setInterval(function() {
                if (window.cadmanCart) {
                    const cartInfo = document.getElementById('cart-info');
                    cartInfo.innerHTML = `
                        <p>Cart Count: ${window.cadmanCart.cartCount || 0}</p>
                        <p>Is Loading: ${window.cadmanCart.isLoading || false}</p>
                    `;
                }
            }, 1000);
        });
    </script>
</body>
</html>