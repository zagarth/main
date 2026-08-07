<!DOCTYPE html>
<html>
<head>
    <title>Debug Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php include 'cart/cart_display.php'; includeCart(); ?>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('debug'); ?>
    
    <div style="padding: 120px 20px; text-align: center;">
        <h1>Debug Cart Button</h1>
        
        <div style="margin: 20px 0;">
            <p>Check browser console for any JavaScript errors.</p>
            <p>Try clicking the cart button in the top-right navigation.</p>
        </div>
        
        <div style="margin: 20px 0;">
            <button onclick="debugCart()" style="padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Debug Cart System
            </button>
        </div>
        
        <div id="debug-output" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; text-align: left; font-family: monospace;"></div>
    </div>
    
    <script>
    function debugCart() {
        const output = document.getElementById('debug-output');
        let debug = '';
        
        // Check if cart modal exists
        const cartModal = document.getElementById('cartModal');
        debug += 'Cart Modal exists: ' + (cartModal ? 'YES' : 'NO') + '\n';
        
        if (cartModal) {
            debug += 'Modal display style: ' + cartModal.style.display + '\n';
            debug += 'Modal computed display: ' + window.getComputedStyle(cartModal).display + '\n';
        }
        
        // Check if cadmanCart exists
        debug += 'CadmanCart object exists: ' + (typeof window.cadmanCart !== 'undefined' ? 'YES' : 'NO') + '\n';
        
        // Check if CadmanCart class exists
        debug += 'CadmanCart class exists: ' + (typeof window.CadmanCart !== 'undefined' ? 'YES' : 'NO') + '\n';
        
        // Check cart button
        const cartButton = document.querySelector('.cart-toggle');
        debug += 'Cart button exists: ' + (cartButton ? 'YES' : 'NO') + '\n';
        
        if (cartButton) {
            debug += 'Cart button classes: ' + cartButton.className + '\n';
            
            // Test clicking the cart button
            debug += '\nTesting cart button click...\n';
            cartButton.click();
            debug += 'Button clicked\n';
        }
        
        // Check all scripts loaded
        const scripts = document.querySelectorAll('script[src]');
        debug += '\nLoaded scripts:\n';
        scripts.forEach(script => {
            debug += '- ' + script.src + '\n';
        });
        
        // Try to manually toggle cart
        if (typeof window.cadmanCart !== 'undefined') {
            debug += '\nManually toggling cart...\n';
            try {
                window.cadmanCart.toggleCartDisplay();
                debug += 'Manual toggle successful\n';
            } catch (e) {
                debug += 'Manual toggle error: ' + e.message + '\n';
            }
        }
        
        output.textContent = debug;
    }
    
    // Log any JavaScript errors
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
    });
    </script>
</body>
</html>