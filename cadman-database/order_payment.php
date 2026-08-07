<?php
/**
 * Order Payment Page
 * Accept.js payment form for saved orders
 */

session_start();
require_once '../includes/db_config.php';

// Get order ID from URL
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: orders.php?error=no_order');
    exit;
}

// Load order from database
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(ol.line_id) as item_count
        FROM orders o
        LEFT JOIN order_lines ol ON o.order_id = ol.order_id
        WHERE o.order_id = :order_id
        GROUP BY o.order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php?error=order_not_found');
        exit;
    }
    
    // Check if already paid
    if ($order['payment_status'] === 'PAID') {
        header('Location: orders.php?message=already_paid');
        exit;
    }
    
    // Load order line items
    $stmt = $pdo->prepare("
        SELECT * FROM order_lines 
        WHERE order_id = :order_id 
        ORDER BY line_number
    ");
    $stmt->execute([':order_id' => $orderId]);
    $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error loading order: ' . $e->getMessage());
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Order <?php echo htmlspecialchars($order['order_number']); ?></title>
    
    <!-- Accept.js for PCI-compliant payment processing -->
    <script type="text/javascript" src="https://jstest.authorize.net/v1/Accept.js" charset="utf-8"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #434343 0%, #000000 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 25px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .order-summary h2 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-row strong {
            color: #374151;
        }
        
        .total-row {
            font-size: 1.3em;
            font-weight: bold;
            color: #6366f1;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #6366f1;
        }
        
        .payment-section {
            padding: 30px;
        }
        
        .payment-section h3 {
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        .pay-button {
            background: #10b981;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        
        .pay-button:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .pay-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6b7280;
            text-decoration: none;
        }
        
        .cancel-link:hover {
            color: #374151;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            display: none;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
            display: none;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .security-badge {
            text-align: center;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 5px;
            margin-top: 20px;
            color: #065f46;
            font-size: 0.9em;
        }
        
        .security-badge::before {
            content: "🔒 ";
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>💳 Secure Payment</h1>
            <p>Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
        </header>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-row">
                <span>Customer:</span>
                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
            </div>
            <div class="summary-row">
                <span>Order Date:</span>
                <span><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="summary-row">
                <span>Items:</span>
                <span><?php echo $order['item_count']; ?> item(s)</span>
            </div>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
            <div class="summary-row">
                <span>Discount (<?php echo $order['discount_percent']; ?>%):</span>
                <span>-$<?php echo number_format($order['discount_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['tax_amount'] > 0): ?>
            <div class="summary-row">
                <span>Tax:</span>
                <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row total-row">
                <span>Total Due:</span>
                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <!-- Payment Form -->
        <div class="payment-section">
            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>
            
            <h3>Payment Information</h3>
            
            <form id="paymentForm" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                <input type="hidden" name="dataValue" id="dataValue">
                <input type="hidden" name="dataDescriptor" id="dataDescriptor">
                
                <div class="form-group">
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="20" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expMonth">Expiration Date</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="text" id="expMonth" placeholder="MM" maxlength="2" required>
                            <input type="text" id="expYear" placeholder="YYYY" maxlength="4" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cardCode">CVV</label>
                        <input type="text" id="cardCode" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cardholderName">Cardholder Name</label>
                    <input type="text" id="cardholderName" placeholder="John Doe" value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                </div>
                
                <button type="submit" class="pay-button" id="payButton">
                    Pay $<?php echo number_format($order['total_amount'], 2); ?>
                </button>
                
                <a href="orders.php" class="cancel-link">Cancel and Return to Orders</a>
            </form>
            
            <div class="security-badge">
                Secure payment processing powered by Authorize.Net<br>
                Your payment information is encrypted and never stored on our servers
            </div>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <script>
        const orderData = <?php echo json_encode([
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
            'customer_name' => $order['customer_name'],
            'total_amount' => $order['total_amount'],
            'items' => $lineItems
        ]); ?>;
        
        // Authorize.Net API credentials (sandbox)
        const API_LOGIN_ID = 'YOUR_SANDBOX_LOGIN_ID'; // Replace with actual
        const CLIENT_KEY = 'YOUR_SANDBOX_PUBLIC_CLIENT_KEY'; // Replace with actual
        
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            processPayment();
        });
        
        function processPayment() {
            const payButton = document.getElementById('payButton');
            const errorDiv = document.getElementById('errorMessage');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Disable button and show loading
            payButton.disabled = true;
            payButton.textContent = 'Processing...';
            errorDiv.style.display = 'none';
            loadingOverlay.classList.add('active');
            
            // Prepare card data
            const secureData = {
                authData: {
                    apiLoginID: API_LOGIN_ID,
                    clientKey: CLIENT_KEY
                },
                cardData: {
                    cardNumber: document.getElementById('cardNumber').value.replace(/\s/g, ''),
                    month: document.getElementById('expMonth').value.padStart(2, '0'),
                    year: document.getElementById('expYear').value,
                    cardCode: document.getElementById('cardCode').value
                }
            };
            
            // Call Accept.js to get payment nonce
            Accept.dispatchData(secureData, responseHandler);
        }
        
        function responseHandler(response) {
            const payButton = document.getElementById('payButton');
            const errorDiv = document.getElementById('errorMessage');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            if (response.messages.resultCode === 'Error') {
                // Show error
                let errorMessage = '';
                for (let i = 0; i < response.messages.message.length; i++) {
                    errorMessage += response.messages.message[i].text + '<br>';
                }
                errorDiv.innerHTML = errorMessage;
                errorDiv.style.display = 'block';
                
                // Re-enable button
                payButton.disabled = false;
                payButton.textContent = 'Pay $' + orderData.total_amount.toFixed(2);
                loadingOverlay.classList.remove('active');
                
            } else {
                // Success - got payment nonce
                document.getElementById('dataValue').value = response.opaqueData.dataValue;
                document.getElementById('dataDescriptor').value = response.opaqueData.dataDescriptor;
                
                // Submit to server for processing
                submitPaymentToServer();
            }
        }
        
        async function submitPaymentToServer() {
            const formData = new FormData(document.getElementById('paymentForm'));
            
            try {
                const response = await fetch('api/process_payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Payment successful - redirect to invoice
                    document.getElementById('successMessage').textContent = 'Payment successful! Generating invoice...';
                    document.getElementById('successMessage').style.display = 'block';
                    
                    setTimeout(() => {
                        window.location.href = 'generate_invoice_after_payment.php?order_id=' + orderData.order_id;
                    }, 1500);
                    
                } else {
                    // Payment failed
                    document.getElementById('errorMessage').textContent = result.message || 'Payment failed. Please try again.';
                    document.getElementById('errorMessage').style.display = 'block';
                    
                    document.getElementById('payButton').disabled = false;
                    document.getElementById('payButton').textContent = 'Pay $' + orderData.total_amount.toFixed(2);
                    document.getElementById('loadingOverlay').classList.remove('active');
                }
                
            } catch (error) {
                console.error('Payment error:', error);
                document.getElementById('errorMessage').textContent = 'Network error. Please try again.';
                document.getElementById('errorMessage').style.display = 'block';
                
                document.getElementById('payButton').disabled = false;
                document.getElementById('payButton').textContent = 'Pay $' + orderData.total_amount.toFixed(2);
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }
        
        // Format card number with spaces
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        // Allow only numbers
        ['cardNumber', 'expMonth', 'expYear', 'cardCode'].forEach(id => {
            document.getElementById(id).addEventListener('keypress', function(e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
