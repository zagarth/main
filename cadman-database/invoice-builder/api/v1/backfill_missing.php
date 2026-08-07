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
    $data = [];
}

$dryRun = (bool)($data['dry_run'] ?? true);
$limit = max(1, min(500, (int)($data['limit'] ?? 50)));

try {
    $pdo = getDBConnection();
    $projectRoot = dirname(__DIR__, 4);
    $service = new InvoiceBuilderService($pdo, $projectRoot);

    $stmt = $pdo->prepare(
        "SELECT order_id, client_id, order_number, order_date, status, total_amount, currency, items_json, shipping_address, tracking_number, notes
         FROM orders
         ORDER BY order_id DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    $generatedCount = 0;
    $existingCount = 0;
    $errorCount = 0;

    foreach ($orders as $order) {
        $orderNumber = (string)$order['order_number'];
        $candidatePaths = [
            $projectRoot . '/cadman-database/temp_invoices/invoice_' . $orderNumber . '.pdf',
            $projectRoot . '/cadman-database/temp_invoices/' . $orderNumber . '.pdf'
        ];
        $existingPdf = null;
        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath)) {
                $existingPdf = $candidatePath;
                break;
            }
        }

        if ($existingPdf !== null) {
            $existingCount++;
            $results[] = [
                'order_id' => (int)$order['order_id'],
                'order_number' => $orderNumber,
                'status' => 'existing',
                'pdf_path' => $existingPdf
            ];
            continue;
        }

        if ($dryRun) {
            $results[] = [
                'order_id' => (int)$order['order_id'],
                'order_number' => $orderNumber,
                'status' => 'would_generate',
                'pdf_path' => $candidatePaths[0]
            ];
            continue;
        }

        try {
            $invoice = $service->ensureInvoicePdf($order, true);
            $generatedCount++;
            $results[] = [
                'order_id' => (int)$order['order_id'],
                'order_number' => $orderNumber,
                'status' => $invoice['status'],
                'pdf_path' => $invoice['pdf_path'],
                'pdf_url' => $invoice['pdf_url']
            ];
        } catch (Throwable $e) {
            $errorCount++;
            $results[] = [
                'order_id' => (int)$order['order_id'],
                'order_number' => $orderNumber,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'dry_run' => $dryRun,
        'scanned' => count($orders),
        'existing' => $existingCount,
        'generated' => $generatedCount,
        'errors' => $errorCount,
        'results' => $results
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Backfill failed.',
        'details' => $e->getMessage()
    ]);
}
