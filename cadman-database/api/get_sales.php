<?php
header('Content-Type: application/json');
require_once '/var/www/html/homesite/includes/db_config.php';

try {
    $pdo = getDBConnection();

    $limit        = isset($_GET['limit'])    ? min((int)$_GET['limit'], 50000) : 2779;
    $customerCode = $_GET['customer']        ?? null;
    $search       = $_GET['search']          ?? null;

    $sql = "
        SELECT
            sh.customer_code,
            sh.category_code          AS item_code,
            sh.transaction_date       AS invoice_date,
            sh.invoice_number,
            sh.description,
            sh.transaction_date       AS ship_date,
            sh.amount                 AS selling_price,
            1                         AS quantity,
            sh.cost,
            sh.salesman_code          AS sales_rep,
            sh.order_method,
            sh.period_date,
            c.business_name
        FROM sales_history sh
        LEFT JOIN clients c ON sh.client_id = c.client_id
        WHERE 1=1
    ";

    $params = [];

    if ($customerCode) {
        $sql .= " AND sh.customer_code = :customer";
        $params[':customer'] = $customerCode;
    }

    if ($search) {
        $sql .= " AND (sh.customer_code LIKE :search
                       OR sh.invoice_number LIKE :search
                       OR sh.description LIKE :search
                       OR c.business_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY sh.transaction_date DESC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => count($data),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
