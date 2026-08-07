<?php
/**
 * Search orders by business name or customer code across the new orders table and legacy sales history.
 */
require_once '/var/www/html/homesite/includes/db_config.php';
header('Content-Type: application/json');

$storeName = trim($_GET['store_name'] ?? '');
if (strlen($storeName) < 2) {
    echo json_encode(['success' => false, 'error' => 'Store name must be at least 2 characters']);
    exit;
}

$searchPattern = '%' . $storeName . '%';

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
        WHERE (
            c.business_name LIKE :search1
            OR o.order_number LIKE :search2
            OR o.notes LIKE :search3
            OR o.order_number LIKE CONCAT('%', :search4, '%')
            OR CAST(o.order_number AS CHAR) LIKE :search5
        )
        ORDER BY o.order_date DESC, o.order_id DESC
        LIMIT 100
    ");
    $stmt->execute([':search1' => $searchPattern, ':search2' => $searchPattern, ':search3' => $searchPattern, ':search4' => $storeName, ':search5' => $searchPattern]);
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
        WHERE (c.business_name LIKE :search1 OR sh.customer_code LIKE :search2 OR sh.description LIKE :search3)
        GROUP BY sh.invoice_number, sh.customer_code
        ORDER BY MIN(sh.transaction_date) DESC
        LIMIT 100
    ");
    $stmt->execute([':search1' => $searchPattern, ':search2' => $searchPattern, ':search3' => $searchPattern]);
    $orders = array_merge($orders, $stmt->fetchAll(PDO::FETCH_ASSOC));

    usort($orders, static function ($a, $b) {
        $dateA = (string)($a['order_date'] ?? '');
        $dateB = (string)($b['order_date'] ?? '');
        return strcmp($dateB, $dateA);
    });

    $orders = array_slice($orders, 0, 100);

    echo json_encode([
        'success'    => true,
        'orders'     => $orders,
        'searchTerm' => $storeName,
        'count'      => count($orders),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
