<?php
session_start();
require_once __DIR__ . '/cart_session.php';
require_once __DIR__ . '/config/authorize_net_config.php';
require_once __DIR__ . '/payment/authorize_net.php';
require_once __DIR__ . '/../includes/SessionManager.php';

// Initialize cart session
$cartSession = new CartSession();
$sessionManager = SessionManager::getInstance();

// Check if cart is empty
if (empty($cartSession->getItems())) {
    header('Location: /');
    exit();
}

// Initialize payment processor
$authNetConfig = new AuthorizeNetConfig();
$paymentProcessor = new AuthorizeNetProcessor($authNetConfig);

$error = '';
$success = '';

// Process payment if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_nonce'])) {
    try {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid security token');
        }
        
        // Get cart total
        $cartTotals = $cartSession->getTotals();
        $total = $cartTotals['total'];
        
        // Prepare payment data
        $paymentData = [
            'payment_nonce' => $_POST['payment_nonce'],
            'amount' => $total,
            'customer' => [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone']
            ],
            'billing' => [
                'address' => $_POST['billing_address'],
                'city' => $_POST['billing_city'],
                'state' => $_POST['billing_state'],
                'zip' => $_POST['billing_zip'],
                'country' => $_POST['billing_country']
            ],
            'shipping' => [
                'address' => $_POST['shipping_address'],
                'city' => $_POST['shipping_city'],
                'state' => $_POST['shipping_state'],
                'zip' => $_POST['shipping_zip'],
                'country' => $_POST['shipping_country']
            ],
            'order_items' => $cartSession->getItems()
        ];
        
        // Process payment
        $result = $paymentProcessor->processPayment($paymentData);
        
        if ($result['success']) {
            // Clear cart on successful payment
            $cartSession->clearCart();
            $success = 'Payment processed successfully! Order ID: ' . $result['order_id'];
        } else {
            $error = $result['error'];
        }
        
    } catch (Exception $e) {
        $error = 'Payment processing error: ' . $e->getMessage();
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Calculate cart total and items
$cartTotals = $cartSession->getTotals();
$cartTotal = $cartTotals['total'];
$cartItems = $cartSession->getItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - Cadman Manufacturing</title>
    
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://js.authorize.net https://jstest.authorize.net; style-src 'self' 'unsafe-inline'; connect-src 'self' https://api.authorize.net https://apitest.authorize.net; frame-src https://js.authorize.net https://jstest.authorize.net;">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Accept.js for secure payment processing -->
    <script type="text/javascript" src="<?php echo $authNetConfig->getAcceptJsUrl(); ?>"></script>
    
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
        }
        
        .checkout-form {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }
        
        .order-summary {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            height: fit-content;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
        }
        
        .payment-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #666;
            font-size: 14px;
        }
        
        .item-quantity {
            margin: 0 10px;
            color: #888;
        }
        
        .item-total {
            font-weight: bold;
            color: #333;
        }
        
        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #007cba;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #007cba;
        }
        
        .submit-btn {
            background: #007cba;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            background: #005a8a;
        }
        
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        
        .security-notice {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h1>Secure Checkout</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <div class="checkout-grid">
            <div class="checkout-form">
                <form id="checkout-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo SessionManager::getInstance()->generateCSRFToken(); ?>">
                    <input type="hidden" id="payment_nonce" name="payment_nonce" value="">
                    
                    <!-- Customer Information -->
                    <div class="form-section">
                        <h3>Customer Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Address -->
                    <div class="form-section">
                        <h3>Billing Address</h3>
                        <div class="form-group">
                            <label for="billing_address">Street Address *</label>
                            <input type="text" id="billing_address" name="billing_address" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_city">City *</label>
                                <input type="text" id="billing_city" name="billing_city" required>
                            </div>
                            <div class="form-group">
                                <label for="billing_state">State/Province *</label>
                                <input type="text" id="billing_state" name="billing_state" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_zip">ZIP/Postal Code *</label>
                                <input type="text" id="billing_zip" name="billing_zip" required>
                            </div>
                            <div class="form-group">
                                <label for="billing_country">Country *</label>
                                <select id="billing_country" name="billing_country" required>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="UK">United Kingdom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="form-section">
                        <h3>Shipping Address</h3>
                        <div class="checkbox-group">
                            <input type="checkbox" id="same_as_billing" checked>
                            <label for="same_as_billing">Same as billing address</label>
                        </div>
                        <div id="shipping-fields">
                            <div class="form-group">
                                <label for="shipping_address">Street Address *</label>
                                <input type="text" id="shipping_address" name="shipping_address" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="shipping_city">City *</label>
                                    <input type="text" id="shipping_city" name="shipping_city" required>
                                </div>
                                <div class="form-group">
                                    <label for="shipping_state">State/Province *</label>
                                    <input type="text" id="shipping_state" name="shipping_state" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="shipping_zip">ZIP/Postal Code *</label>
                                    <input type="text" id="shipping_zip" name="shipping_zip" required>
                                </div>
                                <div class="form-group">
                                    <label for="shipping_country">Country *</label>
                                    <select id="shipping_country" name="shipping_country" required>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="UK">United Kingdom</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="form-section">
                        <h3>Payment Information</h3>
                        <div class="security-notice">
                            <strong>🔒 Secure Payment:</strong> Your payment information is encrypted and processed securely using Authorize.Net's tokenization system. We never store your credit card details.
                        </div>
                        
                        <div class="payment-section">
                            <div class="form-group">
                                <label for="card_number">Card Number *</label>
                                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="exp_month">Expiration Month *</label>
                                    <select id="exp_month" name="exp_month" required>
                                        <option value="">Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="exp_year">Expiration Year *</label>
                                    <select id="exp_year" name="exp_year" required>
                                        <option value="">Year</option>
                                        <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="cvv">Security Code (CVV) *</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submit-btn">
                        Complete Secure Payment - $<?php echo number_format($cartTotal, 2); ?>
                    </button>
                </form>
                
                <div class="loading" id="loading">
                    <p>Processing your payment securely...</p>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                    </div>
                    <div class="item-quantity">×<?php echo $item['quantity']; ?></div>
                    <div class="item-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>TBD</span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span>TBD</span>
                    </div>
                    <div class="total-row total-final">
                        <span>Total:</span>
                        <span>$<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Accept.js configuration
        const acceptJsConfig = {
            apiLoginID: '<?php echo $authNetConfig->getApiLoginId(); ?>',
            clientKey: '<?php echo $authNetConfig->getClientKey(); ?>'
        };
        
        // Form handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('checkout-form');
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const sameAsBillingCheckbox = document.getElementById('same_as_billing');
            const shippingFields = document.getElementById('shipping-fields');
            
            // Handle same as billing checkbox
            sameAsBillingCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    shippingFields.style.display = 'none';
                    // Copy billing to shipping
                    document.getElementById('shipping_address').value = document.getElementById('billing_address').value;
                    document.getElementById('shipping_city').value = document.getElementById('billing_city').value;
                    document.getElementById('shipping_state').value = document.getElementById('billing_state').value;
                    document.getElementById('shipping_zip').value = document.getElementById('billing_zip').value;
                    document.getElementById('shipping_country').value = document.getElementById('billing_country').value;
                } else {
                    shippingFields.style.display = 'block';
                }
            });
            
            // Auto-copy billing to shipping when same as billing is checked
            ['billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country'].forEach(function(fieldId) {
                document.getElementById(fieldId).addEventListener('input', function() {
                    if (sameAsBillingCheckbox.checked) {
                        const shippingFieldId = fieldId.replace('billing_', 'shipping_');
                        document.getElementById(shippingFieldId).value = this.value;
                    }
                });
            });
            
            // Format card number input
            document.getElementById('card_number').addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                this.value = value;
            });
            
            // Only allow numbers for CVV
            document.getElementById('cvv').addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
            
            // Form submission with Accept.js
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Disable submit button and show loading
                submitBtn.disabled = true;
                loading.style.display = 'block';
                form.style.display = 'none';
                
                // Prepare card data for Accept.js
                const cardData = {
                    cardNumber: document.getElementById('card_number').value.replace(/\s/g, ''),
                    month: document.getElementById('exp_month').value,
                    year: document.getElementById('exp_year').value,
                    cardCode: document.getElementById('cvv').value
                };
                
                // Validate card data
                if (!cardData.cardNumber || !cardData.month || !cardData.year || !cardData.cardCode) {
                    alert('Please fill in all payment fields');
                    resetForm();
                    return;
                }
                
                // Create payment nonce using Accept.js
                Accept.dispatchData(cardData, function(response) {
                    if (response.messages.resultCode === 'Error') {
                        let errorMsg = 'Payment validation error: ';
                        for (let i = 0; i < response.messages.message.length; i++) {
                            errorMsg += response.messages.message[i].text + ' ';
                        }
                        alert(errorMsg);
                        resetForm();
                    } else {
                        // Success - we have a payment nonce
                        document.getElementById('payment_nonce').value = response.opaqueData.dataValue;
                        
                        // Clear sensitive data from form
                        document.getElementById('card_number').value = '';
                        document.getElementById('cvv').value = '';
                        
                        // Submit the form
                        form.submit();
                    }
                });
            });
            
            function resetForm() {
                submitBtn.disabled = false;
                loading.style.display = 'none';
                form.style.display = 'block';
            }
            
            // Initialize shipping fields visibility
            shippingFields.style.display = sameAsBillingCheckbox.checked ? 'none' : 'block';
        });
    </script>
</body>
</html>