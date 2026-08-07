<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../includes/db_config.php';
require_once __DIR__ . '/../../lib/InvoiceBuilderService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
if (($rawBody === '' || $rawBody === false) && PHP_SAPI === 'cli') {
    $rawBody = file_get_contents('php://stdin');
}
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON payload.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    $projectRoot = dirname(__DIR__, 4);
    $service = new InvoiceBuilderService($pdo, $projectRoot);

    $forceRegenerate = (bool)($data['force_regenerate'] ?? false);

    $order = null;
    $created = false;

    if (isset($data['order_id'])) {
        $order = $service->findOrderById((int)$data['order_id']);
    } elseif (isset($data['order_number'])) {
        $order = $service->findOrderByNumber((string)$data['order_number']);
    }

    if (!$order && isset($data['new_order_payload']) && is_array($data['new_order_payload'])) {
        $order = $service->createOrderFromPayload($data['new_order_payload']);
        $created = true;
    }

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Order not found. Provide order_id/order_number or new_order_payload.'
        ]);
        exit;
    }

    $invoiceResult = $service->ensureInvoicePdf($order, $forceRegenerate);
    $orderSummary = $service->summarizeOrder($order);

    $statusCode = $created ? 201 : 200;
    http_response_code($statusCode);

    echo json_encode([
        'success' => true,
        'created_order' => $created,
        'invoice_status' => $invoiceResult['status'],
        'force_regenerate' => $forceRegenerate,
        'order' => $orderSummary,
        'invoice' => [
            'generated' => $invoiceResult['generated'],
            'pdf_path' => $invoiceResult['pdf_path'],
            'pdf_url' => $invoiceResult['pdf_url']
        ],
        'generator_response' => $invoiceResult['generator_response'] ?? null
    ], JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Invoice builder failed.',
        'details' => $e->getMessage()
    ]);
}
