<?php
/**
 * Generate Invoice After Payment
 * Creates and displays invoice PDF after successful payment
 */

require_once '../includes/db_config.php';

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: orders.php?error=no_order');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Load order from database
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE order_id = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php?error=order_not_found');
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
    
    // Prepare invoice data
    $invoiceData = [
        'customerName' => $order['customer_name'],
        'customerPhone' => '', // TODO: Get from customer record
        'customerLocation' => '', // TODO: Get from customer record
        'accountNumber' => $order['customer_code'],
        'salesRep' => $order['sales_rep'],
        'orderNumber' => $order['order_number'],
        'orderDate' => $order['order_date'],
        'terms' => $order['terms'],
        'items' => array_map(function($item) {
            return [
                'line' => $item['line_number'],
                'itemCode' => $item['item_code'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['unit_price'],
                'engraving_requested' => !empty($item['engraving_requested']) ? 1 : 0,
                'engraving_text' => $item['engraving_text'] ?? '',
                'engraving_cost' => $item['engraving_cost'] ?? 0,
                'line_note' => $item['line_note'] ?? $item['notes'] ?? '',
                'notes' => $item['line_note'] ?? $item['notes'] ?? ''
            ];
        }, $lineItems),
        'subtotal' => $order['subtotal'],
        'discount' => $order['discount_amount'],
        'total' => $order['total_amount']
    ];
    
    // Generate invoice PDF
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/cadman-database/generate_invoice.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invoiceData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if ($result['success']) {
            // Redirect to invoice PDF
            header('Location: ' . $result['url']);
            exit;
        }
    }
    
    // If we get here, invoice generation failed
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Invoice Generation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .success {
            color: #10b981;
            font-size: 1.5em;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }
        .button:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="success">✓ Payment Successful!</div>
    <div class="message">
        Order #' . htmlspecialchars($order['order_number']) . '<br>
        Total: $' . number_format($order['total_amount'], 2) . '<br><br>
        <strong>Invoice generation in progress...</strong><br>
        Generating your invoice PDF...
    </div>
    <a href="orders.php" class="button">Return to Orders</a>
    
    <script>
        // Try to generate invoice again after 2 seconds
setTimeout(() => {
            fetch("generate_invoice.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(' . json_encode($invoiceData) . ')
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.url;
                }
            });
        }, 2000);
    </script>
</body>
</html>';
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
