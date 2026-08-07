<!DOCTYPE html>
<html>
<head>
    <title>Cart Modal Debug Test</title>
    <style>
        .debug-section {
            background: #f0f0f0;
            padding: 15px;
            margin: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .status-good { color: green; font-weight: bold; }
        .status-bad { color: red; font-weight: bold; }
        .debug-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            margin: 5px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Cart Modal Debug Test</h1>
    
    <div class="debug-section">
        <h3>Cart System Status</h3>
        <div id="cart-system-status"></div>
    </div>
    
    <div class="debug-section">
        <h3>Manual Cart Tests</h3>
        <button class="debug-button" onclick="testShowCart()">Test Show Cart Modal</button>
        <button class="debug-button" onclick="testAddItem()">Test Add Item</button>
        <button class="debug-button" onclick="inspectCartModal()">Inspect Cart Modal</button>
    </div>
    
    <div class="debug-section">
        <h3>Console Output</h3>
        <div id="console-output" style="background: black; color: lime; padding: 10px; font-family: monospace; height: 200px; overflow-y: auto;"></div>
    </div>

    <?php include 'navigation.php'; renderNavigation('debug'); ?>
    
    <script>
        function logToDiv(message) {
            const output = document.getElementById('console-output');
            output.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '\n';
            output.scrollTop = output.scrollHeight;
            console.log(message);
        }
        
        function updateStatus() {
            const statusDiv = document.getElementById('cart-system-status');
            
            // Check all components
            const cartModal = document.getElementById('cartModal');
            const cartButton = document.querySelector('.cart-toggle');
            const cartNavButton = document.querySelector('.cart-nav-style');
            const cadmanCart = window.cadmanCart;
            
            let html = '<h4>Component Status:</h4>';
            html += `<p>Cart Modal Element: <span class="${cartModal ? 'status-good' : 'status-bad'}">${cartModal ? 'EXISTS' : 'MISSING'}</span></p>`;
            html += `<p>Cart Toggle Button: <span class="${cartButton ? 'status-good' : 'status-bad'}">${cartButton ? 'EXISTS' : 'MISSING'}</span></p>`;
            html += `<p>Cart Nav Button: <span class="${cartNavButton ? 'status-good' : 'status-bad'}">${cartNavButton ? 'EXISTS' : 'MISSING'}</span></p>`;
            html += `<p>CadmanCart Object: <span class="${cadmanCart ? 'status-good' : 'status-bad'}">${cadmanCart ? 'LOADED' : 'MISSING'}</span></p>`;
            
            if (cartModal) {
                html += `<p>Modal Display: ${window.getComputedStyle(cartModal).display}</p>`;
                html += `<p>Modal Classes: ${cartModal.className}</p>`;
            }
            
            if (cartButton) {
                html += `<p>Button onclick: ${cartButton.onclick ? 'SET' : 'NOT SET'}</p>`;
                html += `<p>Button Classes: ${cartButton.className}</p>`;
            }
            
            statusDiv.innerHTML = html;
        }
        
        function testShowCart() {
            logToDiv('=== Testing Show Cart ===');
            
            const cartModal = document.getElementById('cartModal');
            if (!cartModal) {
                logToDiv('ERROR: Cart modal not found!');
                return;
            }
            
            logToDiv('Cart modal found, attempting to show...');
            
            // Try different methods to show the cart
            if (window.cadmanCart && typeof window.cadmanCart.showCartDisplay === 'function') {
                logToDiv('Using cadmanCart.showCartDisplay()');
                window.cadmanCart.showCartDisplay();
            } else if (window.cadmanCart && typeof window.cadmanCart.toggleCart === 'function') {
                logToDiv('Using cadmanCart.toggleCart()');
                window.cadmanCart.toggleCart();
            } else {
                logToDiv('CadmanCart methods not available, trying manual modal show');
                cartModal.classList.add('show');
                cartModal.style.display = 'flex';
            }
            
            logToDiv('Show cart test completed');
        }
        
        function testAddItem() {
            logToDiv('=== Testing Add Item ===');
            
            if (!window.cadmanCart) {
                logToDiv('ERROR: CadmanCart not loaded!');
                return;
            }
            
            const testItem = {
                collection: 'test',
                item_id: 'TEST_001',
                category: 'debug',
                name: 'Debug Test Item',
                price: 99,
                image: '/test.jpg',
                quantity: 1
            };
            
            logToDiv('Adding test item: ' + JSON.stringify(testItem));
            
            if (typeof window.cadmanCart.addItem === 'function') {
                window.cadmanCart.addItem(testItem).then(result => {
                    logToDiv('Add item result: ' + JSON.stringify(result));
                }).catch(error => {
                    logToDiv('Add item error: ' + error.message);
                });
            } else {
                logToDiv('ERROR: addItem method not found on cadmanCart');
            }
        }
        
        function inspectCartModal() {
            logToDiv('=== Inspecting Cart Modal ===');
            
            const cartModal = document.getElementById('cartModal');
            if (!cartModal) {
                logToDiv('ERROR: Cart modal element not found!');
                return;
            }
            
            logToDiv('Modal element exists');
            logToDiv('Display style: ' + cartModal.style.display);
            logToDiv('Computed display: ' + window.getComputedStyle(cartModal).display);
            logToDiv('Classes: ' + cartModal.className);
            logToDiv('Parent: ' + (cartModal.parentElement ? cartModal.parentElement.tagName : 'null'));
            
            // Check for event listeners
            const events = getEventListeners ? getEventListeners(cartModal) : 'Event inspection not available';
            logToDiv('Event listeners: ' + JSON.stringify(events));
            
            // Check all cart buttons
            const cartButtons = document.querySelectorAll('.cart-toggle, .cart-nav-style');
            logToDiv(`Found ${cartButtons.length} cart buttons`);
            
            cartButtons.forEach((btn, index) => {
                logToDiv(`Button ${index}: classes=${btn.className}, onclick=${!!btn.onclick}`);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            logToDiv('DOM loaded, checking cart system...');
            
            setTimeout(() => {
                updateStatus();
                logToDiv('Initial status updated');
                
                // Check if cart initialization completed
                if (window.cadmanCart) {
                    logToDiv('CadmanCart found: ' + typeof window.cadmanCart);
                    logToDiv('CadmanCart methods: ' + Object.getOwnPropertyNames(window.cadmanCart.constructor.prototype));
                } else {
                    logToDiv('CadmanCart not found, checking again in 2 seconds...');
                    setTimeout(() => {
                        if (window.cadmanCart) {
                            logToDiv('CadmanCart loaded after delay');
                            updateStatus();
                        } else {
                            logToDiv('CadmanCart still not loaded after delay');
                        }
                    }, 2000);
                }
            }, 500);
        });
    </script>
</body>
</html>