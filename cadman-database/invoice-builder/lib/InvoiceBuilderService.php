<?php

declare(strict_types=1);

/**
 * Shared invoice builder service for creating and regenerating invoice PDFs.
 */
class InvoiceBuilderService
{
    private PDO $pdo;
    private string $projectRoot;
    private string $apiRoot;
    private string $generateInvoiceEndpoint;

    public function __construct(PDO $pdo, string $projectRoot)
    {
        $this->pdo = $pdo;
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->apiRoot = $this->projectRoot . '/cadman-database';
        $this->generateInvoiceEndpoint = 'http://localhost/cadman-database/generate_invoice.php';
    }

    public function findOrderById(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT order_id, client_id, order_number, order_date, status, total_amount, currency, items_json, shipping_address, tracking_number, notes
             FROM orders
             WHERE order_id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findOrderByNumber(string $orderNumber): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT order_id, client_id, order_number, order_date, status, total_amount, currency, items_json, shipping_address, tracking_number, notes
             FROM orders
             WHERE order_number = ?
             LIMIT 1"
        );
        $stmt->execute([$orderNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createOrderFromPayload(array $payload): array
    {
        $clientId = (int)($payload['client_id'] ?? 1);
        $orderDate = $payload['order_date'] ?? date('Y-m-d H:i:s');
        $status = $payload['status'] ?? 'pending';
        $currency = strtoupper((string)($payload['currency'] ?? 'USD'));
        $items = $payload['items'] ?? [];
        $shippingAddress = $payload['shipping_address'] ?? '';
        $trackingNumber = $payload['tracking_number'] ?? '';
        $notesPayload = $payload['notes'] ?? [];

        if (!is_array($items) || count($items) === 0) {
            throw new InvalidArgumentException('Payload must include a non-empty items array.');
        }

        $orderNumber = $payload['order_number'] ?? $this->generateOrderNumber();
        $totalAmount = $this->calculateTotal($items, $payload['totals'] ?? null);

        $this->pdo->beginTransaction();

        try {
            $insert = $this->pdo->prepare(
                "INSERT INTO orders (
                    client_id,
                    order_number,
                    order_date,
                    status,
                    total_amount,
                    currency,
                    items_json,
                    shipping_address,
                    tracking_number,
                    notes
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $insert->execute([
                $clientId,
                $orderNumber,
                $orderDate,
                $status,
                $totalAmount,
                $currency,
                json_encode($items, JSON_UNESCAPED_SLASHES),
                is_array($shippingAddress) ? json_encode($shippingAddress, JSON_UNESCAPED_SLASHES) : (string)$shippingAddress,
                (string)$trackingNumber,
                json_encode($notesPayload, JSON_UNESCAPED_SLASHES)
            ]);

            $orderId = (int)$this->pdo->lastInsertId();
            $this->insertOrderLinesIfTableExists($orderId, $items);

            $this->pdo->commit();

            $order = $this->findOrderById($orderId);
            if (!$order) {
                throw new RuntimeException('Order was inserted but could not be reloaded.');
            }

            return $order;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function ensureInvoicePdf(array $order, bool $forceRegenerate = false): array
    {
        $orderNumber = (string)$order['order_number'];
        $existingPdfPath = $this->findExistingPdfPath($orderNumber);

        if (!$forceRegenerate && $existingPdfPath !== null) {
            return [
                'status' => 'existing',
                'pdf_path' => $existingPdfPath,
                'pdf_url' => $this->toPublicPdfUrl($existingPdfPath),
                'generated' => false
            ];
        }

        $payload = $this->buildInvoicePayload($order);
        $result = $this->callGenerator($payload);

        return [
            'status' => 'generated',
            'pdf_path' => $result['pdf_path'],
            'pdf_url' => $result['pdf_url'],
            'generated' => true,
            'generator_response' => $result
        ];
    }

    public function summarizeOrder(array $order): array
    {
        return [
            'order_id' => (int)$order['order_id'],
            'order_number' => (string)$order['order_number'],
            'client_id' => (int)$order['client_id'],
            'status' => (string)($order['status'] ?? 'pending'),
            'total_amount' => (float)($order['total_amount'] ?? 0),
            'currency' => (string)($order['currency'] ?? 'USD')
        ];
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        return $prefix . '-' . $random;
    }

    private function calculateTotal(array $items, $totals): float
    {
        if (is_array($totals) && isset($totals['grand_total'])) {
            return round((float)$totals['grand_total'], 2);
        }

        $sum = 0.0;
        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? ($item['price'] ?? 0));
            $line = isset($item['line_total']) ? (float)$item['line_total'] : ($qty * $price);
            $sum += $line;
        }

        return round($sum, 2);
    }

    private function insertOrderLinesIfTableExists(int $orderId, array $items): void
    {
        if (!$this->tableExists('order_lines')) {
            return;
        }

        $lineStmt = $this->pdo->prepare(
            "INSERT INTO order_lines (
                order_id,
                line_number,
                item_code,
                description,
                quantity,
                unit_price,
                extended_price,
                cost,
                margin_percent,
                notes
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $lineNumber = 1;
        foreach ($items as $item) {
            $qty = (int)max(1, (int)($item['quantity'] ?? 1));
            $unitPrice = (float)($item['unit_price'] ?? ($item['price'] ?? 0));
            $extended = isset($item['line_total']) ? (float)$item['line_total'] : ($qty * $unitPrice);
            $lineStmt->execute([
                $orderId,
                $lineNumber,
                (string)($item['item_code'] ?? ($item['sku'] ?? 'ITEM-' . $lineNumber)),
                (string)($item['description'] ?? ($item['name'] ?? 'Catalog Item')),
                $qty,
                round($unitPrice, 2),
                round($extended, 2),
                isset($item['cost']) ? round((float)$item['cost'], 2) : null,
                isset($item['margin_percent']) ? round((float)$item['margin_percent'], 2) : null,
                isset($item['notes']) ? json_encode($item['notes'], JSON_UNESCAPED_SLASHES) : null,
            ]);
            $lineNumber++;
        }
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private function buildInvoicePayload(array $order): array
    {
        $items = json_decode((string)($order['items_json'] ?? '[]'), true);
        if (!is_array($items)) {
            $items = [];
        }

        $decodedNotes = json_decode((string)($order['notes'] ?? ''), true);
        $customer = is_array($decodedNotes) && isset($decodedNotes['customer']) && is_array($decodedNotes['customer'])
            ? $decodedNotes['customer']
            : [];

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += isset($item['line_total'])
                ? (float)$item['line_total']
                : (float)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? ($item['price'] ?? 0));
        }
        $grandTotal = (float)($order['total_amount'] ?? 0);
        $discount = max(0.0, round($subtotal - $grandTotal, 2));

        return [
            'orderNumber' => (string)$order['order_number'],
            'orderDate' => date('Y-m-d', strtotime((string)($order['order_date'] ?? 'now'))),
            'customerName' => (string)($customer['name'] ?? 'Valued Customer'),
            'customerPhone' => (string)($customer['phone'] ?? ''),
            'customerLocation' => (string)($customer['location'] ?? ($customer['address'] ?? '')),
            'accountNumber' => (string)($customer['account_number'] ?? 'N/A'),
            'salesRep' => (string)($customer['sales_rep'] ?? 'WEB'),
            'terms' => (string)($customer['terms'] ?? 'NET30'),
            'items' => array_map(function (array $item): array {
                return [
                    'description' => (string)($item['description'] ?? ($item['name'] ?? 'Catalog Item')),
                    'quantity' => (float)($item['quantity'] ?? 1),
                    'price' => (float)($item['unit_price'] ?? ($item['price'] ?? 0)),
                    'itemCode' => (string)($item['item_code'] ?? ($item['sku'] ?? '')),
                ];
            }, $items),
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'total' => round($grandTotal, 2),
        ];
    }

    private function callGenerator(array $payload): array
    {
        $scriptResponse = $this->callGeneratorViaPhpProcess($payload);
        if ($scriptResponse !== null) {
            return $scriptResponse;
        }

        return $this->callGeneratorViaHttp($payload);
    }

    private function callGeneratorViaPhpProcess(array $payload): ?array
    {
        $scriptPath = $this->apiRoot . '/generate_invoice.php';
        if (!is_file($scriptPath)) {
            return null;
        }

        if (!function_exists('proc_open')) {
            return null;
        }

        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, $this->apiRoot);
        if (!is_resource($process)) {
            return null;
        }

        fwrite($pipes[0], json_encode($payload, JSON_UNESCAPED_SLASHES));
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 && trim($stdout) === '') {
            throw new RuntimeException('Invoice generator process failed: ' . trim($stderr));
        }

        return $this->normalizeGeneratorResponse($stdout);
    }

    private function callGeneratorViaHttp(array $payload): array
    {
        $ch = curl_init($this->generateInvoiceEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Invoice generator call failed: ' . $curlError);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);
            $error = is_array($decoded) ? ($decoded['error'] ?? 'unknown error') : 'HTTP ' . $httpCode;
            throw new RuntimeException('Invoice generator error: ' . $error);
        }

        return $this->normalizeGeneratorResponse($response);
    }

    private function normalizeGeneratorResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invoice generator returned invalid JSON.');
        }

        if (!isset($decoded['success']) || !$decoded['success']) {
            $error = $decoded['error'] ?? 'unknown generator failure';
            throw new RuntimeException('Invoice generator error: ' . $error);
        }

        $pdfPath = $decoded['filepath'] ?? null;
        $pdfUrl = isset($decoded['url']) ? '/homesite/cadman-database/' . ltrim((string)$decoded['url'], '/') : null;
        if (!$pdfPath || !$pdfUrl) {
            throw new RuntimeException('Invoice generator response missing file path or URL.');
        }

        return [
            'success' => true,
            'pdf_path' => (string)$pdfPath,
            'pdf_url' => (string)$pdfUrl,
            'raw' => $decoded
        ];
    }

    private function findExistingPdfPath(string $orderNumber): ?string
    {
        $candidates = [
            $this->apiRoot . '/temp_invoices/invoice_' . $orderNumber . '.pdf',
            $this->apiRoot . '/temp_invoices/' . $orderNumber . '.pdf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function toPublicPdfUrl(string $pdfPath): string
    {
        $filename = basename($pdfPath);
        return '/homesite/cadman-database/temp_invoices/' . $filename;
    }
}
