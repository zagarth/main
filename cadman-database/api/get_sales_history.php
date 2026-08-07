<?php
/**
 * api/get_sales_history.php
 * Returns sales_history rows for a given customer.
 *
 * Query params (at least one required):
 *   ?customer_code=BOG011
 *   ?client_id=42
 * Optional:
 *   ?invoice_number=620513
 *   ?from=2025-01-01  ?to=2025-12-31
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '/var/www/html/homesite/includes/db_config.php';

function jsonError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$customerCode  = isset($_GET['customer_code'])  ? trim($_GET['customer_code'])  : null;
$clientId      = isset($_GET['client_id'])       ? (int)$_GET['client_id']      : null;
$invoiceFilter = isset($_GET['invoice_number'])  ? trim($_GET['invoice_number']) : null;
$from          = isset($_GET['from'])            ? trim($_GET['from'])           : null;
$to            = isset($_GET['to'])              ? trim($_GET['to'])             : null;

if (!$customerCode && !$clientId) {
    jsonError('Provide customer_code or client_id');
}

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if ($from && !preg_match($datePattern, $from)) jsonError('Invalid from date (YYYY-MM-DD)');
if ($to   && !preg_match($datePattern, $to))   jsonError('Invalid to date (YYYY-MM-DD)');

try {
    $pdo    = getDBConnection();
    $where  = [];
    $params = [];

    if ($customerCode) {
        $where[] = 'sh.customer_code = :customer_code';
        $params[':customer_code'] = $customerCode;
    }
    if ($clientId) {
        $where[] = 'sh.client_id = :client_id';
        $params[':client_id'] = $clientId;
    }
    if ($invoiceFilter) {
        $where[] = 'sh.invoice_number = :invoice';
        $params[':invoice'] = $invoiceFilter;
    }
    if ($from) {
        $where[] = 'sh.transaction_date >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $where[] = 'sh.transaction_date <= :to';
        $params[':to'] = $to;
    }

    $sql = "SELECT sh.sale_id, sh.customer_code, c.business_name,
                   sh.category_code, sh.invoice_number, sh.description,
                   sh.transaction_date, sh.period_date,
                   sh.amount, sh.cost, sh.salesman_code, sh.order_method
            FROM sales_history sh
            LEFT JOIN clients c ON sh.client_id = c.client_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sh.transaction_date DESC
            LIMIT 1000";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'count'        => count($rows),
        'total_amount' => round(array_sum(array_column($rows, 'amount')), 2),
        'total_cost'   => round(array_sum(array_column($rows, 'cost')), 2),
        'rows'         => $rows,
    ]);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
