<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    http_response_code(403);
    exit;
}

if (isset($_GET['cleanup'])) {
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    $token = trim((string)($_GET['token'] ?? ''));
    if ($orderId > 0 && $token !== '') {
        $tempDir = __DIR__ . '/../cadman-database/temp_invoices';
        $filepath = $tempDir . '/invoice_order_' . $orderId . '_' . $token . '.pdf';
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$source = trim((string)($_GET['source'] ?? 'current'));
$orderNumber = trim((string)($_GET['order_number'] ?? ''));
if ($orderId <= 0) {
    http_response_code(400);
    exit;
}

$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    http_response_code(401);
    exit;
}

$clientId = (int)($user['client_id'] ?? 0);
$pdo = getDBConnection();

// Legacy orders come from sales_history grouped by invoice_number
if ($source === 'legacy') {
    // Get invoice_number from the sale_id (order_id is MIN(sale_id) from getClientOrders)
    $invStmt = $pdo->prepare("SELECT invoice_number, customer_code FROM sales_history WHERE sale_id = :sid AND (client_id = :cid OR customer_code = :code) LIMIT 1");
    $invStmt->execute([':sid' => $orderId, ':cid' => $clientId, ':code' => $user['customer_code'] ?? '']);
    $invRow = $invStmt->fetch(PDO::FETCH_ASSOC);

    if (!$invRow && $orderNumber !== '') {
        // Fallback: look up by invoice_number directly
        $invStmt2 = $pdo->prepare("SELECT invoice_number, customer_code FROM sales_history WHERE invoice_number = :inv AND (client_id = :cid OR customer_code = :code) LIMIT 1");
        $invStmt2->execute([':inv' => $orderNumber, ':cid' => $clientId, ':code' => $user['customer_code'] ?? '']);
        $invRow = $invStmt2->fetch(PDO::FETCH_ASSOC);
    }

    if (!$invRow) {
        http_response_code(404);
        exit;
    }

    $invoiceNumber = $invRow['invoice_number'];
    $lineStmt = $pdo->prepare("SELECT description, category_code, amount, transaction_date, salesman_code FROM sales_history WHERE invoice_number = :inv AND (client_id = :cid OR customer_code = :code) ORDER BY sale_id");
    $lineStmt->execute([':inv' => $invoiceNumber, ':cid' => $clientId, ':code' => $user['customer_code'] ?? '']);
    $lines = $lineStmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(fn($l) => [
        'description' => $l['description'],
        'sku'         => $l['category_code'],
        'quantity'    => 1,
        'price'       => (float)$l['amount'],
        'subtotal'    => (float)$l['amount'],
    ], $lines);

    $subtotal = array_sum(array_column($items, 'subtotal'));
    $province = trim((string)($user['province'] ?? ''));
    $breakdown = calculateOrderBreakdownFromProvince($subtotal, $province, 0);

    $payload = [
        'customerName'     => $user['business_name'] ?? '',
        'customerPhone'    => $user['phone'] ?? '',
        'customerLocation' => implode(', ', array_filter([$user['address'] ?? '', $user['city'] ?? '', $province, $user['postal_code'] ?? '', $user['country'] ?? ''])),
        'accountNumber'    => $user['customer_code'] ?? $invRow['customer_code'] ?? 'N/A',
        'salesRep'         => $lines[0]['salesman_code'] ?? 'WEB',
        'orderNumber'      => $invoiceNumber,
        'orderDate'        => $lines[0]['transaction_date'] ?? date('Y-m-d'),
        'terms'            => $user['terms'] ?? 'NET30',
        'items'            => $items,
        'subtotal'         => $breakdown['subtotal'],
        'discount'         => 0,
        'tax'              => $breakdown['tax_amount'],
        'total'            => $breakdown['total_amount'],
        'outputFilename'   => 'legacy_' . $invoiceNumber . '_' . (isset($_GET['token']) ? trim((string)$_GET['token']) : 'reprint'),
    ];
} else {
    $stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE order_id = :order_id
      AND client_id = :client_id
    LIMIT 1
");
$stmt->execute([':order_id' => $orderId, ':client_id' => $clientId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit;
}

$items = [];
if (!empty($order['items_json'])) {
    $decodedItems = json_decode((string)$order['items_json'], true);
    if (is_array($decodedItems)) {
        $items = $decodedItems;
    }
}

$province = trim((string)($order['province'] ?? $user['province'] ?? ''));
$discountAmount = (float)($order['discount_amount'] ?? 0.0);
$taxRate = getTaxRateForProvince($province);
$breakdown = calculateOrderBreakdownFromProvince((float)($order['total_amount'] ?? 0.0), $province, $discountAmount);

$clientAddress = trim((string)($user['address'] ?? $order['shipping_address'] ?? ''));
$clientCity = trim((string)($user['city'] ?? $order['city'] ?? ''));
$clientProvince = trim((string)($user['province'] ?? $order['province'] ?? ''));
$clientPostal = trim((string)($user['postal_code'] ?? $order['postal_code'] ?? ''));
$clientCountry = trim((string)($user['country'] ?? $order['country'] ?? ''));
$clientLocationParts = array_filter([$clientAddress, $clientCity, $clientProvince, $clientPostal, $clientCountry], static function ($value) {
    return $value !== '';
});
$clientLocation = implode(', ', $clientLocationParts);

$payload = [
    'customerName' => $order['customer_name'] ?? $user['business_name'] ?? '',
    'customerPhone' => $user['phone'] ?? $order['phone'] ?? '',
    'customerLocation' => $clientLocation,
    'accountNumber' => $order['customer_code'] ?? $user['customer_code'] ?? 'N/A',
    'salesRep' => $order['sales_rep'] ?? 'WEB',
    'orderNumber' => $order['order_number'] ?? '',
    'orderDate' => $order['order_date'] ?? date('Y-m-d'),
    'terms' => $order['terms'] ?? 'NET30',
    'items' => $items,
    'subtotal' => $breakdown['subtotal'],
    'discount' => $breakdown['discount_amount'],
    'tax' => $breakdown['tax_amount'],
    'total' => $breakdown['total_amount'],
    'outputFilename' => 'order_' . $orderId . '_' . (isset($_GET['token']) ? trim((string)$_GET['token']) : 'reprint'),
];
} // end else (current order)

$generator = __DIR__ . '/../cadman-database/generate_invoice.php';
$cmd = 'php ' . escapeshellarg($generator);
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($process)) {
    http_response_code(500);
    exit;
}

fwrite($pipes[0], json_encode($payload));
 fclose($pipes[0]);

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
 fclose($pipes[1]);
 fclose($pipes[2]);

$exitCode = proc_close($process);
if ($exitCode === 0) {
    $result = json_decode($stdout, true);
    if (!empty($result['success']) && !empty($result['filepath'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($result['filepath']) . '"');
        readfile($result['filepath']);
        exit;
    }
}

http_response_code(500);
echo json_encode(['success' => false, 'error' => 'Invoice generation failed']);
