<?php
// Create a simple test page that mimics how the cart works in the browser
session_start();

// Generate a CSRF token for testing
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Remove Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .cart-item {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            background: #f9f9f9;
        }
        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .add-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 3px;
            cursor: pointer;
        }
        #output {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Cart Remove Functionality Test</h1>
    
    <div class="test-section">
        <h3>Add Test Item</h3>
        <button class="add-btn" onclick="addTestItem()">Add Silver Celtic Cross</button>
    </div>
    
    <div class="test-section">
        <h3>Current Cart Items</h3>
        <div id="cart-display">Loading...</div>
        <button onclick="refreshCart()">Refresh Cart</button>
    </div>
    
    <div class="test-section">
        <h3>Debug Output</h3>
        <div id="output"></div>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        
        function log(message) {
            const output = document.getElementById('output');
            output.textContent += new Date().toLocaleTimeString() + ': ' + message + '\n';
        }
        
        async function addTestItem() {
            try {
                log('Adding test item...');
                
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('csrf_token', csrfToken);
                formData.append('collection', 'accessories');
                formData.append('item_id', 'ACC_CROSS_001');
                formData.append('category', 'crosses');
                formData.append('name', 'Silver Celtic Cross');
                formData.append('image', '/images/accessories/crosses/celtic_cross.jpg');
                formData.append('quantity', '1');
                
                const response = await fetch('cart/cart_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                log('Add result: ' + JSON.stringify(result, null, 2));
                
                if (result.success) {
                    refreshCart();
                }
                
            } catch (error) {
                log('Error adding item: ' + error.message);
            }
        }
        
        async function removeItem(cartItemId) {
            try {
                log('Removing item: ' + cartItemId);
                
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('csrf_token', csrfToken);
                formData.append('cart_item_id', cartItemId);
                
                const response = await fetch('cart/cart_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                log('Remove result: ' + JSON.stringify(result, null, 2));
                
                if (result.success) {
                    refreshCart();
                } else {
                    log('Remove failed: ' + result.message);
                }
                
            } catch (error) {
                log('Error removing item: ' + error.message);
            }
        }
        
        async function refreshCart() {
            try {
                const response = await fetch('cart/cart_api.php?action=get_items', {
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                log('Cart refresh: ' + JSON.stringify(result, null, 2));
                
                const cartDisplay = document.getElementById('cart-display');
                
                if (result.success && result.cart && Object.keys(result.cart).length > 0) {
                    let html = '';
                    for (const [cartItemId, item] of Object.entries(result.cart)) {
                        html += `
                            <div class="cart-item">
                                <strong>${item.name}</strong><br>
                                Collection: ${item.collection}<br>
                                Price: $${item.price}<br>
                                Quantity: ${item.quantity}<br>
                                Cart Item ID: <code>${cartItemId}</code><br>
                                <button class="remove-btn" onclick="removeItem('${cartItemId}')">Remove</button>
                            </div>
                        `;
                    }
                    cartDisplay.innerHTML = html;
                } else {
                    cartDisplay.innerHTML = '<p>Cart is empty</p>';
                }
                
            } catch (error) {
                log('Error refreshing cart: ' + error.message);
            }
        }
        
        // Load cart on page load
        refreshCart();
    </script>
</body>
</html>