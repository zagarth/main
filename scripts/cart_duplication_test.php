<?php
include 'navigation.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cart Duplication Test</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php renderNavigation('test'); ?>
    
    <div style="padding: 120px 20px; max-width: 600px; margin: 0 auto;">
        <h1>🔍 Cart Duplication Diagnostic</h1>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h2>Testing Cart System Consolidation</h2>
            <p><strong>✅ Fixes Applied:</strong></p>
            <ul>
                <li>Removed deprecated <code>includeCart()</code> function</li>
                <li>Added cart initialization protection</li>
                <li>Prevented duplicate event listener setup</li>
                <li>Consolidated all cart code in navigation.php</li>
            </ul>
        </div>
        
        <!-- Test Items -->
        <div style="display: grid; gap: 20px; margin: 30px 0;">
            <div style="border: 2px solid #b8860b; border-radius: 10px; padding: 20px; text-align: center;">
                <h3>Test Item A</h3>
                <p><strong>Expected:</strong> Should add exactly 1 item per click</p>
                <button 
                    class="add-to-cart-btn" 
                    data-item-id="TEST_A"
                    data-item-name="Test Item A"
                    data-item-price="100.00"
                    data-item-image="PNG/logo.png"
                    style="background: #b8860b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    🛒 Add Test Item A
                </button>
            </div>
            
            <div style="border: 2px solid #b8860b; border-radius: 10px; padding: 20px; text-align: center;">
                <h3>Test Item B</h3>
                <p><strong>Expected:</strong> Should add exactly 1 item per click</p>
                <button 
                    class="add-to-cart-btn" 
                    data-item-id="TEST_B"
                    data-item-name="Test Item B"
                    data-item-price="200.00"
                    data-item-image="PNG/logo.png"
                    style="background: #b8860b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    🛒 Add Test Item B
                </button>
            </div>
        </div>
        
        <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h2>🧪 Debug Instructions</h2>
            <ol>
                <li><strong>Open Browser Console</strong> (F12 → Console tab)</li>
                <li><strong>Look for initialization messages:</strong>
                    <ul>
                        <li>"===== CART.JS LOADING ====="</li>
                        <li>"===== CART INITIALIZATION ====="</li>
                        <li>"===== SETTING UP CART EVENT LISTENERS ====="</li>
                    </ul>
                </li>
                <li><strong>Test adding items:</strong> Click buttons above and watch console</li>
                <li><strong>Check for duplicate prevention:</strong> Should see "ALREADY INITIALIZED" or "ALREADY SETUP" if working</li>
                <li><strong>Test cart modal:</strong> Click cart icon (🛒) in navigation</li>
            </ol>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h2>📋 Expected Console Messages</h2>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; overflow-x: auto;">
===== CART.JS LOADING =====
===== CART INITIALIZATION =====
===== SETTING UP CART EVENT LISTENERS =====
Event listeners setup completed
CadmanCart created: CadmanCart {...}
After init - Cart modal exists: true
After init - Cart button exists: true</pre>
            
            <p><strong>⚠️ Warning Signs to Watch For:</strong></p>
            <ul>
                <li>Multiple "CART.JS LOADING" messages (script loaded twice)</li>
                <li>Multiple "SETTING UP CART EVENT LISTENERS" (duplicate listeners)</li>
                <li>No "ALREADY INITIALIZED" messages when expected</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="Bands.php" style="background: #b8860b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin: 0 10px; display: inline-block;">Test Bands Page</a>
            <a href="unified_detail.php?collection=family&id=F1379" style="background: #b8860b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin: 0 10px; display: inline-block;">Test Detail Page</a>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        console.log('===== DIAGNOSTIC PAGE READY =====');
        
        // Monitor cart events
        $(document).on('cartUpdated', function(e, data) {
            console.log('🛒 Cart Updated Event:', data);
        });
        
        $(document).on('cartError', function(e, error) {
            console.error('❌ Cart Error Event:', error);
        });
        
        // Add visual feedback
        $('.add-to-cart-btn').on('click', function() {
            const button = $(this);
            const originalText = button.text();
            const itemId = button.data('item-id');
            
            console.log('🔘 Button clicked for item:', itemId);
            
            button.text('Adding...').prop('disabled', true);
            
            setTimeout(() => {
                button.text(originalText).prop('disabled', false);
            }, 2000);
        });
    });
    </script>
</body>
</html>