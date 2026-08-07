<?php
/**
 * Get all orders from the new orders table and legacy sales history, grouped by invoice/order number.
 */
require_once '/var/www/html/homesite/includes/db_config.php';
header('Content-Type: application/json');

$limit = min((int)($_GET['limit'] ?? 500), 2000);

try {
    $pdo = getDBConnection();
    $orders = [];

    $stmt = $pdo->prepare("
        SELECT
            o.order_id,
            o.order_number,
            o.order_date,
            o.total_amount,
            COALESCE(o.status, 'PENDING') AS payment_status,
            '' AS customer_code,
            COALESCE(c.business_name, 'Unknown Customer') AS business_name,
            COALESCE(c.phone, '') AS phone,
            COALESCE(c.city, '') AS city,
            COALESCE(c.province, '') AS province,
            COALESCE(c.country, '') AS country,
            COALESCE(c.terms, '') AS terms
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.client_id
        ORDER BY o.order_date DESC, o.order_id DESC
    ");
    $stmt->execute();
    $orders = array_merge($orders, $stmt->fetchAll(PDO::FETCH_ASSOC));

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
        GROUP BY sh.invoice_number, sh.customer_code
        ORDER BY MIN(sh.transaction_date) DESC
    ");
    $stmt->execute();
    $orders = array_merge($orders, $stmt->fetchAll(PDO::FETCH_ASSOC));

    usort($orders, static function ($a, $b) {
        $dateA = (string)($a['order_date'] ?? '');
        $dateB = (string)($b['order_date'] ?? '');
        return strcmp($dateB, $dateA);
    });

    $totalCount = count($orders);
    $orders = array_slice($orders, 0, $limit);

    echo json_encode([
        'success'    => true,
        'orders'     => $orders,
        'count'      => count($orders),
        'totalCount' => $totalCount,
        'displaying' => count($orders) < $totalCount
            ? "Showing " . count($orders) . " of {$totalCount} total orders"
            : "All orders displayed",
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
