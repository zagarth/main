<?php
/**
 * Get order detail by order ID or order number, including the new orders table.
 */
require_once '/var/www/html/homesite/includes/db_config.php';
header('Content-Type: application/json');

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$invoiceNumber = trim((string)($_GET['invoice_number'] ?? ''));

if (!$orderId && $invoiceNumber === '') {
    echo json_encode(['success' => false, 'error' => 'Order ID or invoice number required']);
    exit;
}

try {
    $pdo = getDBConnection();
    $order = null;
    $lineItems = [];

    if ($orderId > 0) {
        $stmt = $pdo->prepare("
            SELECT
                o.order_id,
                o.order_number,
                o.order_date,
                o.status,
                o.total_amount,
                o.notes,
                o.client_id,
                o.items_json,
                c.business_name,
                c.phone,
                c.city,
                c.province,
                c.country,
                c.terms
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.client_id
            WHERE o.order_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $meta = [];
            if (!empty($row['notes'])) {
                $decodedNotes = json_decode((string)$row['notes'], true);
                if (is_array($decodedNotes)) {
                    $meta = $decodedNotes;
                }
            }

            $order = [
                'order_id' => (int)$row['order_id'],
                'order_number' => (string)$row['order_number'],
                'order_date' => $row['order_date'] ?? date('Y-m-d'),
                'total_amount' => (float)($row['total_amount'] ?? 0),
                'payment_status' => $row['status'] ?: 'PENDING',
                'customer_code' => (string)($meta['customer_code'] ?? ''),
                'business_name' => $row['business_name'] ?? ($meta['customer_name'] ?? ''),
                'customer_name' => $row['business_name'] ?? ($meta['customer_name'] ?? ''),
                'terms' => $row['terms'] ?? ($meta['terms'] ?? ''),
                'phone' => $row['phone'] ?? '',
                'city' => $row['city'] ?? '',
                'province' => $row['province'] ?? '',
                'country' => $row['country'] ?? '',
            ];

            $stmt = $pdo->prepare("
                SELECT
                    line_number,
                    item_code,
                    description,
                    quantity,
                    unit_price,
                    extended_price,
                    cost
                FROM order_lines
                WHERE order_id = :id
                ORDER BY line_number, line_id
            ");
            $stmt->execute([':id' => $orderId]);
            $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!$order && $invoiceNumber !== '') {
        $stmt = $pdo->prepare("
            SELECT
                o.order_id,
                o.order_number,
                o.order_date,
                o.status,
                o.total_amount,
                o.notes,
                o.client_id,
                o.items_json,
                c.business_name,
                c.phone,
                c.city,
                c.province,
                c.country,
                c.terms
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.client_id
            WHERE CAST(o.order_number AS CHAR) = :inv1
               OR CAST(o.order_number AS CHAR) = CONCAT('ORD-', :inv2)
               OR CAST(o.order_number AS CHAR) = CONCAT('', :inv3)
               OR CAST(o.order_number AS CHAR) = CAST(:inv4 AS CHAR)
            LIMIT 1
        ");
        $stmt->execute([':inv1' => $invoiceNumber, ':inv2' => $invoiceNumber, ':inv3' => $invoiceNumber, ':inv4' => $invoiceNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $meta = [];
            if (!empty($row['notes'])) {
                $decodedNotes = json_decode((string)$row['notes'], true);
                if (is_array($decodedNotes)) {
                    $meta = $decodedNotes;
                }
            }

            $order = [
                'order_id' => (int)$row['order_id'],
                'order_number' => (string)$row['order_number'],
                'order_date' => $row['order_date'] ?? date('Y-m-d'),
                'total_amount' => (float)($row['total_amount'] ?? 0),
                'payment_status' => $row['status'] ?: 'PENDING',
                'customer_code' => (string)($meta['customer_code'] ?? ''),
                'business_name' => $row['business_name'] ?? ($meta['customer_name'] ?? ''),
                'customer_name' => $row['business_name'] ?? ($meta['customer_name'] ?? ''),
                'terms' => $row['terms'] ?? ($meta['terms'] ?? ''),
                'phone' => $row['phone'] ?? '',
                'city' => $row['city'] ?? '',
                'province' => $row['province'] ?? '',
                'country' => $row['country'] ?? '',
            ];

            $stmt = $pdo->prepare("
                SELECT
                    line_number,
                    item_code,
                    description,
                    quantity,
                    unit_price,
                    extended_price,
                    cost
                FROM order_lines
                WHERE order_id = :id
                ORDER BY line_number, line_id
            ");
            $stmt->execute([':id' => $order['order_id']]);
            $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!$order) {
            $stmt = $pdo->prepare("
                SELECT
                    MIN(sh.sale_id)          AS order_id,
                    sh.invoice_number        AS order_number,
                    MIN(sh.transaction_date) AS order_date,
                    SUM(sh.amount)           AS total_amount,
                    'completed'              AS payment_status,
                    sh.customer_code,
                    MAX(c.business_name)     AS business_name,
                    MAX(c.phone)             AS phone,
                    MAX(c.city)              AS city,
                    MAX(c.province)          AS province,
                    MAX(c.country)           AS country,
                    MAX(c.terms)             AS terms
                FROM sales_history sh
                LEFT JOIN clients c ON sh.client_id = c.client_id
                WHERE sh.invoice_number = :inv
                GROUP BY sh.invoice_number, sh.customer_code
                LIMIT 1
            ");
            $stmt->execute([':inv' => $invoiceNumber]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
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
                $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    if (!$order) {
        // Fallback to the legacy sales_history table for older invoices.
        if ($invoiceNumber === '') {
            $stmt = $pdo->prepare("SELECT invoice_number FROM sales_history WHERE sale_id = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'Order not found']);
                exit;
            }

            $invoiceNumber = (string)$row['invoice_number'];
        } else {
            $stmt = $pdo->prepare("SELECT invoice_number FROM sales_history WHERE invoice_number = :inv LIMIT 1");
            $stmt->execute([':inv' => $invoiceNumber]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'Order not found']);
                exit;
            }

            $invoiceNumber = (string)$row['invoice_number'];
        }

        $stmt = $pdo->prepare("
            SELECT
                MIN(sh.sale_id)          AS order_id,
                sh.invoice_number        AS order_number,
                MIN(sh.transaction_date) AS order_date,
                SUM(sh.amount)           AS total_amount,
                'completed'              AS payment_status,
                sh.customer_code,
                MAX(c.business_name)     AS business_name,
                MAX(c.phone)             AS phone,
                MAX(c.city)              AS city,
                MAX(c.province)          AS province,
                MAX(c.country)           AS country,
                MAX(c.terms)             AS terms
            FROM sales_history sh
            LEFT JOIN clients c ON sh.client_id = c.client_id
            WHERE sh.invoice_number = :inv
            GROUP BY sh.invoice_number, sh.customer_code
        ");
        $stmt->execute([':inv' => $invoiceNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

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
        $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success'   => true,
        'order'     => $order,
        'lineItems' => $lineItems,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
