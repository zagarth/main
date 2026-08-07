<?php
/**
 * View Order by ID
 * Retrieve and display saved orders by order_id
 */

require_once '../includes/db_config.php';

$orderId = $_GET['order_id'] ?? null;
$storeName = $_GET['store_name'] ?? null;
$orderData = null;
$storeOrders = [];
$errorMessage = null;
$backToSalesUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';

/**
 * Resolve orders-table schema differences across environments.
 */
function getOrderSchemaMeta(PDO $pdo): array {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }

    $hasCustomerId = in_array('customer_id', $columns, true);
    $hasClientId = in_array('client_id', $columns, true);

    return [
        'columns' => $columns,
        'joinColumn' => $hasCustomerId ? 'customer_id' : ($hasClientId ? 'client_id' : null),
        'hasCustomerName' => in_array('customer_name', $columns, true),
        'hasPaymentStatus' => in_array('payment_status', $columns, true),
        'hasTerms' => in_array('terms', $columns, true),
        'hasSubtotal' => in_array('subtotal', $columns, true),
        'hasDiscountPercent' => in_array('discount_percent', $columns, true),
        'hasDiscountAmount' => in_array('discount_amount', $columns, true),
        'hasTaxAmount' => in_array('tax_amount', $columns, true),
        'hasStatus' => in_array('status', $columns, true),
        'orderByColumn' => in_array('created_at', $columns, true) ? 'created_at' : 'order_date',
    ];
}

function normalizeOrderRecord(array &$order): void {
    $order['customer_name'] = $order['customer_name'] ?? ($order['business_name'] ?? '');
    $order['payment_status'] = $order['payment_status'] ?? 'PENDING';
    $order['terms'] = $order['terms'] ?? 'NET30';
    $order['status'] = $order['status'] ?? 'OPEN';
    $order['discount_percent'] = isset($order['discount_percent']) ? (float)$order['discount_percent'] : 0.0;
    $order['discount_amount'] = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0.0;
    $order['tax_amount'] = isset($order['tax_amount']) ? (float)$order['tax_amount'] : 0.0;

    $province = trim((string)($order['province'] ?? ''));
    $breakdown = calculateOrderBreakdownFromProvince((float)($order['total_amount'] ?? 0.0), $province, $order['discount_amount']);
    $order['subtotal'] = $breakdown['subtotal'];
    $order['tax_amount'] = $breakdown['tax_amount'];
    $order['total_amount'] = $breakdown['total_amount'];
}

// Handle store name search
if ($storeName && !$orderId) {
    try {
        $pdo = getDBConnection();
        $schema = getOrderSchemaMeta($pdo);
        $joinClause = $schema['joinColumn'] ? "LEFT JOIN clients c ON o.{$schema['joinColumn']} = c.client_id" : "LEFT JOIN clients c ON 1=0";
        $customerNameExpr = $schema['hasCustomerName'] ? 'o.customer_name' : 'COALESCE(c.business_name, "")';
        $paymentStatusExpr = $schema['hasPaymentStatus'] ? 'o.payment_status' : '"PENDING"';
        $statusExpr = $schema['hasStatus'] ? 'o.status' : '"OPEN"';
        $customerNameWhere = $schema['hasCustomerName'] ? 'o.customer_name LIKE :search OR ' : '';
        
        // Search orders by store/business name
        $stmt = $pdo->prepare("
            SELECT o.order_id, o.order_number, {$customerNameExpr} AS customer_name, o.order_date, o.total_amount, {$paymentStatusExpr} AS payment_status, {$statusExpr} AS status,
                   c.business_name, c.customer_code, c.phone, c.city, c.province
            FROM orders o
            {$joinClause}
            WHERE ({$customerNameWhere}
                   OR c.business_name LIKE :search 
                   OR c.customer_code LIKE :search)
            ORDER BY o.order_date DESC
        ");
        $searchTerm = '%' . $storeName . '%';
        $stmt->execute([':search' => $searchTerm]);
        $storeOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($storeOrders as &$order) {
            normalizeOrderRecord($order);
        }
        unset($order);
        
        if (empty($storeOrders)) {
            $errorMessage = "No orders found for store: " . htmlspecialchars($storeName);
        }
    } catch (Exception $e) {
        $errorMessage = "Error searching orders: " . $e->getMessage();
    }
}

// Handle individual order lookup
if ($orderId) {
    try {
        $pdo = getDBConnection();
        $schema = getOrderSchemaMeta($pdo);
        $joinClause = $schema['joinColumn'] ? "LEFT JOIN clients c ON o.{$schema['joinColumn']} = c.client_id" : "LEFT JOIN clients c ON 1=0";
        
        // Load order with customer info
        $stmt = $pdo->prepare("
            SELECT o.*, c.business_name, c.customer_code, c.phone, c.city, c.province, c.country
            FROM orders o
            {$joinClause}
            WHERE o.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $orderId]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderData) {
            // Fallback: support sales_history order ids (sale_id) used by the order search dashboard
            $stmt = $pdo->prepare("SELECT invoice_number FROM sales_history WHERE sale_id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$orderId]);
            $invoiceRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoiceRow) {
                $errorMessage = "Order not found";
            } else {
                $invoiceNumber = $invoiceRow['invoice_number'];

                $stmt = $pdo->prepare("
                    SELECT
                        MIN(sh.sale_id)          AS order_id,
                        sh.invoice_number        AS order_number,
                        MIN(sh.transaction_date) AS order_date,
                        SUM(sh.amount)           AS total_amount,
                        'PENDING'                AS payment_status,
                        'NET30'                  AS terms,
                        'OPEN'                   AS status,
                        0                        AS discount_percent,
                        0                        AS discount_amount,
                        0                        AS tax_amount,
                        SUM(sh.amount)           AS subtotal,
                        sh.customer_code         AS customer_code,
                        MAX(c.business_name)     AS business_name,
                        MAX(c.phone)             AS phone,
                        MAX(c.city)              AS city,
                        MAX(c.province)          AS province,
                        MAX(c.country)           AS country
                    FROM sales_history sh
                    LEFT JOIN clients c ON sh.client_id = c.client_id
                    WHERE sh.invoice_number = :inv
                    GROUP BY sh.invoice_number, sh.customer_code
                ");
                $stmt->execute([':inv' => $invoiceNumber]);
                $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($orderData) {
                    normalizeOrderRecord($orderData);

                    $stmt = $pdo->prepare("
                        SELECT
                            sh.sale_id,
                            sh.category_code   AS item_code,
                            sh.description,
                            1                  AS quantity,
                            sh.amount          AS unit_price,
                            sh.amount          AS extended_price,
                            sh.cost,
                            sh.salesman_code,
                            sh.order_method
                        FROM sales_history sh
                        WHERE sh.invoice_number = :inv
                        ORDER BY sh.sale_id
                    ");
                    $stmt->execute([':inv' => $invoiceNumber]);
                    $orderData['line_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $errorMessage = "Order not found";
                }
            }
        } else {
            normalizeOrderRecord($orderData);

            // Load order line items
            $stmt = $pdo->prepare("
                SELECT * FROM order_lines 
                WHERE order_id = :order_id 
                ORDER BY line_number
            ");
            $stmt->execute([':order_id' => $orderId]);
            $orderData['line_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $errorMessage = "Error loading order: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order <?php echo $orderData ? htmlspecialchars($orderData['order_number']) : ''; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        header {
            background: linear-gradient(135deg, #434343 0%, #000000 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .order-info, .customer-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
        }
        
        .info-value {
            color: #6b7280;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        .line-items {
            margin: 30px 0;
        }
        
        .line-items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .line-items th,
        .line-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .line-items th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .totals {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row.grand-total {
            font-weight: 700;
            font-size: 1.2em;
            color: #6366f1;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .search-box {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .search-box input {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1em;
            width: 300px;
            margin-right: 10px;
        }
        
        .search-box button {
            padding: 12px 20px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <h1>Order Viewer</h1>
        <p>View saved orders by Order ID</p>
    </header>
    
    <div class="container">
        <?php if (!$orderId && !$storeName): ?>
            <div class="search-container">
                <div class="search-tabs">
                    <button class="tab-button active" onclick="showTab('order-search')">Search by Order ID</button>
                    <button class="tab-button" onclick="showTab('store-search')">Search by Store Name</button>
                </div>
                
                <div id="order-search" class="search-tab active">
                    <h2>Enter Order ID</h2>
                    <form method="GET">
                        <input type="number" name="order_id" placeholder="Enter Order ID (e.g. 123)" required>
                        <button type="submit">Load Order</button>
                    </form>
                </div>
                
                <div id="store-search" class="search-tab">
                    <h2>Search by Store Name</h2>
                    <form method="GET">
                        <input type="text" name="store_name" placeholder="Enter store/business name or customer code" required>
                        <button type="submit">Find Orders</button>
                    </form>
                </div>
                
                <p style="margin-top: 15px; color: #6b7280;">
                    <a href="orders.php" class="btn btn-secondary">← Back to Order Entry</a>
                </p>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>Recent Orders</h3>
                <?php
                try {
                    $pdo = getDBConnection();
                    $schema = getOrderSchemaMeta($pdo);
                    $customerNameExpr = $schema['hasCustomerName'] ? 'o.customer_name' : 'COALESCE(c.business_name, "")';
                    $paymentStatusExpr = $schema['hasPaymentStatus'] ? 'o.payment_status' : '"PENDING"';
                    $statusExpr = $schema['hasStatus'] ? 'o.status' : '"OPEN"';
                    $joinClause = $schema['joinColumn'] ? "LEFT JOIN clients c ON o.{$schema['joinColumn']} = c.client_id" : "LEFT JOIN clients c ON 1=0";
                    $orderByColumn = $schema['orderByColumn'];
                    $stmt = $pdo->query("
                        SELECT o.order_id, o.order_number, {$customerNameExpr} AS customer_name, o.order_date, o.total_amount, {$paymentStatusExpr} AS payment_status, {$statusExpr} AS status
                        FROM orders o 
                        {$joinClause}
                        ORDER BY o.{$orderByColumn} DESC 
                        LIMIT 10
                    ");
                    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($recentOrders as &$order) {
                        normalizeOrderRecord($order);
                    }
                    unset($order);
                    
                    if ($recentOrders): ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 12px; text-align: left;">Order ID</th>
                                    <th style="padding: 12px; text-align: left;">Order #</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Date</th>
                                    <th style="padding: 12px; text-align: left;">Total</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: left;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td style="padding: 12px;"><?php echo $order['order_id']; ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td style="padding: 12px;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                            <?php echo strtoupper($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <a href="?order_id=<?php echo $order['order_id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.9em;">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif;
                } catch (Exception $e) {
                    echo "<p>Could not load recent orders</p>";
                }
                ?>
            </div>
            
        <?php elseif ($storeOrders && !$orderId): ?>
            <div class="store-results">
                <div class="results-header">
                    <h2>Orders for: <?php echo htmlspecialchars($storeName); ?></h2>
                    <p><?php echo count($storeOrders); ?> order(s) found</p>
                    <a href="<?php echo htmlspecialchars($backToSalesUrl); ?>" class="btn btn-secondary">← Back to Sales</a>
                </div>
                
                <div class="store-orders-table">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 12px; text-align: left;">Order ID</th>
                                <th style="padding: 12px; text-align: left;">Order #</th>
                                <th style="padding: 12px; text-align: left;">Business Name</th>
                                <th style="padding: 12px; text-align: left;">Customer Code</th>
                                <th style="padding: 12px; text-align: left;">Date</th>
                                <th style="padding: 12px; text-align: left;">Total</th>
                                <th style="padding: 12px; text-align: left;">Payment Status</th>
                                <th style="padding: 12px; text-align: left;">Location</th>
                                <th style="padding: 12px; text-align: left;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($storeOrders as $order): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px;"><?php echo $order['order_id']; ?></td>
                                <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($order['business_name'] ?: $order['customer_name']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_code'] ?: 'N/A'); ?></td>
                                <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td style="padding: 12px; font-weight: 600; color: #059669;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="padding: 12px;">
                                    <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo strtoupper($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; font-size: 0.9em; color: #6b7280;">
                                    <?php 
                                    $loc = [];
                                    if ($order['city']) $loc[] = $order['city'];
                                    if ($order['province']) $loc[] = $order['province'];
                                    echo htmlspecialchars(implode(', ', $loc) ?: 'N/A');
                                    ?>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="?order_id=<?php echo $order['order_id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9em;">View Order</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Store Summary -->
                <div class="store-summary" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h3>Store Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px;">
                        <div class="summary-card">
                            <div style="font-size: 0.9em; color: #6b7280;">Total Orders</div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #374151;"><?php echo count($storeOrders); ?></div>
                        </div>
                        <div class="summary-card">
                            <div style="font-size: 0.9em; color: #6b7280;">Total Sales</div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #059669;">
                                $<?php echo number_format(array_sum(array_column($storeOrders, 'total_amount')), 2); ?>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div style="font-size: 0.9em; color: #6b7280;">Average Order</div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #6366f1;">
                                $<?php echo number_format(array_sum(array_column($storeOrders, 'total_amount')) / count($storeOrders), 2); ?>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div style="font-size: 0.9em; color: #6b7280;">Latest Order</div>
                            <div style="font-size: 1.2em; font-weight: 600; color: #374151;">
                                <?php echo date('M j, Y', strtotime($storeOrders[0]['order_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($errorMessage): ?>
            <div class="error">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
                <a href="<?php echo htmlspecialchars($backToSalesUrl); ?>" class="btn btn-secondary" style="margin-top: 15px;">← Back to Sales</a>
            </div>
            
        <?php else: ?>
            <div class="order-header">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-row">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value"><?php echo $orderData['order_id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orderData['order_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($orderData['order_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="status-badge status-<?php echo strtolower($orderData['payment_status']); ?>">
                            <?php echo strtoupper($orderData['payment_status']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Terms:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orderData['terms']); ?></span>
                    </div>
                </div>
                
                <div class="customer-info">
                    <h2>Customer Information</h2>
                    <div class="info-row">
                        <span class="info-label">Customer Code:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orderData['customer_code'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Business Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orderData['business_name'] ?: $orderData['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orderData['phone'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value">
                            <?php 
                            $location = [];
                            if ($orderData['city']) $location[] = $orderData['city'];
                            if ($orderData['province']) $location[] = $orderData['province'];
                            if ($orderData['country']) $location[] = $orderData['country'];
                            echo htmlspecialchars(implode(', ', $location) ?: 'N/A');
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="line-items">
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Extended Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderData['line_items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['extended_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($orderData['subtotal'], 2); ?></span>
                </div>
                <?php if ($orderData['discount_amount'] > 0): ?>
                <div class="total-row">
                    <span>Discount (<?php echo $orderData['discount_percent']; ?>%):</span>
                    <span>-$<?php echo number_format($orderData['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($orderData['tax_amount'], 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span>$<?php echo number_format($orderData['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <div class="actions">
                <a href="<?php echo htmlspecialchars($backToSalesUrl); ?>" class="btn btn-secondary">← Back to Sales</a>
                
                <?php if ($orderData['payment_status'] === 'PENDING' && ($orderData['terms'] === 'COD' || $orderData['terms'] === 'CIA')): ?>
                <a href="order_payment.php?order_id=<?php echo $orderData['order_id']; ?>" class="btn btn-success">Process Payment</a>
                <?php endif; ?>
                
                <button onclick="generateInvoice()" class="btn btn-primary">Generate Invoice</button>
                <a href="orders.php" class="btn btn-secondary">New Order</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function generateInvoice() {
            const orderData = <?php echo json_encode($orderData); ?>;
            
            // Prepare invoice data
            const invoiceData = {
                customerName: orderData.business_name || orderData.customer_name,
                customerPhone: orderData.phone || '',
                customerLocation: [orderData.city, orderData.province, orderData.country].filter(x => x).join(', '),
                accountNumber: orderData.customer_code || 'N/A',
                salesRep: 'WEB',
                orderNumber: orderData.order_number,
                orderDate: orderData.order_date,
                terms: orderData.terms,
                items: orderData.line_items.map(item => ({
                    line: item.line_number,
                    itemCode: item.item_code,
                    description: item.description,
                    quantity: item.quantity,
                    price: item.unit_price,
                    engraving_requested: item.engraving_requested ?? false,
                    engraving_text: item.engraving_text ?? '',
                    engraving_cost: item.engraving_cost ?? 0,
                    line_note: item.line_note ?? item.notes ?? ''
                })),
                subtotal: parseFloat(orderData.subtotal),
                discount: parseFloat(orderData.discount_amount),
                tax: parseFloat(orderData.tax_amount),
                total: parseFloat(orderData.total_amount)
            };
            
            // Generate invoice
            fetch('generate_invoice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(invoiceData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.open(data.url, '_blank');
                } else {
                    alert('Error generating invoice: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error generating invoice: ' + error.message);
            });
        }
    </script>
</body>
</html>