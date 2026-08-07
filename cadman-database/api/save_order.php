<?php
/**
 * api/save_order.php
 * Accepts a JSON POST from orders.php and inserts a row into the orders table.
 *
 * Expected JSON fields:
 *   customer_id, customer_code, customer_name, order_date, po_number,
 *   terms, discount_percent, subtotal, discount, tax, total, items[],
 *   payment_status, payment_method, notes
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '/var/www/html/homesite/includes/db_config.php';

function jsonError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('POST required', 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    jsonError('Invalid JSON body');
}

// Required fields
$clientId = isset($data['customer_id']) ? (int)$data['customer_id'] : 0;
if ($clientId <= 0) {
    jsonError('customer_id is required');
}

$total     = isset($data['total'])      ? (float)$data['total']      : 0.00;
$items     = isset($data['items'])      ? $data['items']             : [];
$currency  = 'CAD';

// Build order number: ORD-YYYYMMDD-random4
$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

// Sanitize order_date (accept YYYY-MM-DD or timestamp)
$orderDateRaw = isset($data['order_date']) ? trim($data['order_date']) : date('Y-m-d');
$orderDate = date('Y-m-d', strtotime($orderDateRaw)) ?: date('Y-m-d');

// Pack extra financial/admin info into notes so nothing is lost
$extras = [
    'po_number'       => $data['po_number']       ?? '',
    'terms'           => $data['terms']            ?? '',
    'discount_percent'=> $data['discount_percent'] ?? 0,
    'subtotal'        => $data['subtotal']         ?? 0,
    'discount'        => $data['discount']         ?? 0,
    'tax'             => $data['tax']              ?? 0,
    'payment_status'  => $data['payment_status']   ?? 'PENDING',
    'payment_method'  => $data['payment_method']   ?? '',
    'customer_name'   => $data['customer_name']    ?? '',
    'customer_code'   => $data['customer_code']    ?? '',
];
$userNotes  = $data['notes'] ?? '';
$notesJson  = json_encode($extras);
$notes      = $userNotes ? $userNotes . "\n" . $notesJson : $notesJson;

// Map order status from payment_status
$status = 'pending';

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        INSERT INTO orders
            (client_id, order_number, order_date, status,
             total_amount, currency, items_json, notes)
        VALUES
            (:client_id, :order_number, :order_date, :status,
             :total_amount, :currency, :items_json, :notes)
    ");

    $stmt->execute([
        ':client_id'    => $clientId,
        ':order_number' => $orderNumber,
        ':order_date'   => $orderDate,
        ':status'       => $status,
        ':total_amount' => $total,
        ':currency'     => $currency,
        ':items_json'   => json_encode($items),
        ':notes'        => $notes,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'      => true,
        'order_id'     => $orderId,
        'order_number' => $orderNumber,
        'total'        => $total,
    ]);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
