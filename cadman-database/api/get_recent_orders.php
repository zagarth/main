<?php
/**
 * Get recent orders from the new orders table and legacy sales history.
 */
require_once '/var/www/html/homesite/includes/db_config.php';
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $orders = [];

    $stmt = $pdo->prepare("
        SELECT
            order_id,
            order_number,
            order_date,
            total_amount,
            COALESCE(payment_status, status, 'PENDING') AS payment_status,
            customer_code,
            customer_name AS business_name,
            '' AS phone,
            '' AS city,
            '' AS province,
            '' AS country,
            terms
        FROM orders
        ORDER BY order_date DESC, order_id DESC
        LIMIT 50
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
        LIMIT 50
    ");
    $stmt->execute();
    $orders = array_merge($orders, $stmt->fetchAll(PDO::FETCH_ASSOC));

    usort($orders, static function ($a, $b) {
        $dateA = (string)($a['order_date'] ?? '');
        $dateB = (string)($b['order_date'] ?? '');
        return strcmp($dateB, $dateA);
    });

    $orders = array_slice($orders, 0, 50);

    echo json_encode(['success' => true, 'orders' => $orders, 'count' => count($orders)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
