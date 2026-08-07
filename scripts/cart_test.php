<!DOCTYPE html>
<html>
<head>
    <title>Cart Test Page</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php include 'cart/cart_display.php'; includeCart(); ?>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('test'); ?>
    
    <h1>Cart System Test</h1>
    
    <!-- Test Product -->
    <div style="margin: 50px; padding: 20px; border: 1px solid #ccc;">
        <h2>Test Product</h2>
        <p>This is a test product for cart functionality</p>
        <button class="add-to-cart-btn"
                data-item-id="TEST001"
                data-collection="test"
                data-category="test_category"
                data-name="Test Product"
                data-price-range="$100 - $200"
                data-image="test.jpg">
            🛒 Add to Cart
        </button>
    </div>
    
    <!-- Debug Section -->
    <div style="margin: 50px; padding: 20px; background: #f0f0f0;">
        <h3>Debug Information</h3>
        <button onclick="testCart()" style="margin: 10px; padding: 10px;">Test Cart System</button>
        <button onclick="checkToken()" style="margin: 10px; padding: 10px;">Check CSRF Token</button>
        <button onclick="viewCart()" style="margin: 10px; padding: 10px;">View Cart</button>
        <div id="debug-output" style="margin-top: 20px; padding: 10px; background: white; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;"></div>
    </div>

    <script>
    let cadmanCart;
    let debugDiv = document.getElementById('debug-output');
    
    function log(message) {
        debugDiv.textContent += new Date().toLocaleTimeString() + ': ' + message + '\n';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        log('Initializing cart system...');
        cadmanCart = new CadmanCart();
        
        // Add cart toggle functionality
        const cartToggle = document.querySelector('.cart-toggle');
        const cartModal = document.getElementById('cartModal');
        
        if (cartToggle && cartModal) {
            cartToggle.addEventListener('click', function() {
                cartModal.style.display = 'flex';
                cadmanCart.updateCartDisplay();
            });
        }
        
        log('Cart system initialization complete');
    });
    
    async function testCart() {
        try {
            log('Testing cart system...');
            
            // Test token
            const token = await cadmanCart.getCSRFTokenValue();
            log('CSRF Token: ' + token);
            
            // Test add item
            const testItem = {
                item_id: 'TEST001',
                collection: 'test',
                category: 'test_category',
                name: 'Test Product',
                price_range: '$100 - $200',
                image: 'test.jpg'
            };
            
            await cadmanCart.addItem(testItem);
            log('Item added successfully');
            
        } catch (error) {
            log('Error testing cart: ' + error.message);
        }
    }
    
    async function checkToken() {
        try {
            const response = await fetch('cart/cart_api.php?action=get_token');
            const data = await response.json();
            log('Token check response: ' + JSON.stringify(data, null, 2));
        } catch (error) {
            log('Error checking token: ' + error.message);
        }
    }
    
    async function viewCart() {
        try {
            const response = await fetch('cart/cart_api.php?action=get', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'csrf_token=' + encodeURIComponent(await cadmanCart.getCSRFTokenValue())
            });
            const data = await response.json();
            log('Cart contents: ' + JSON.stringify(data, null, 2));
        } catch (error) {
            log('Error viewing cart: ' + error.message);
        }
    }
    </script>
</body>
</html>