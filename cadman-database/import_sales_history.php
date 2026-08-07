<?php
/**
 * Import SA-EXP01.csv into sales_history table
 * Maps customer_code → client_id via clients table
 * Run once from CLI or browser (admin-protected)
 */

session_start();
if (php_sapi_name() !== 'cli' && !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

require_once '/var/www/html/homesite/includes/db_config.php';

$csvFile = __DIR__ . '/SA-EXP01.csv';

if (!file_exists($csvFile)) {
    die("ERROR: SA-EXP01.csv not found at $csvFile\n");
}

$pdo = getDBConnection();

// Clear existing SA imports so re-running is safe
$pdo->exec("DELETE FROM sales_history");
echo "Cleared existing sales_history rows.\n";

// Build customer_code → client_id lookup map
$stmt = $pdo->query("SELECT client_id, customer_code FROM clients WHERE customer_code IS NOT NULL");
$clientMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $clientMap[trim($row['customer_code'])] = (int)$row['client_id'];
}
echo "Loaded " . count($clientMap) . " client mappings.\n";

$insert = $pdo->prepare("
    INSERT INTO sales_history
        (customer_code, client_id, category_code, period_date, invoice_number,
         description, transaction_date, amount, salesman_code, cost, order_method)
    VALUES
        (:customer_code, :client_id, :category_code, :period_date, :invoice_number,
         :description, :transaction_date, :amount, :salesman_code, :cost, :order_method)
");

$handle = fopen($csvFile, 'r');
$imported  = 0;
$skipped   = 0;
$unmatched = 0;

/**
 * Parse a YYYYMMDD string into SQL DATE or NULL
 */
function parseDate(string $raw): ?string {
    $d = trim($raw);
    if (strlen($d) === 8 && ctype_digit($d)) {
        $y = substr($d, 0, 4);
        $m = substr($d, 4, 2);
        $day = substr($d, 6, 2);
        if (checkdate((int)$m, (int)$day, (int)$y)) {
            return "$y-$m-$day";
        }
    }
    return null;
}

$pdo->beginTransaction();

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === '') continue;

    $fields = explode('|', $line);
    if (count($fields) < 9) {
        $skipped++;
        continue;
    }

    $customerCode   = trim($fields[0]);
    $categoryCode   = trim($fields[1]);
    $periodRaw      = trim($fields[2]);
    $invoiceNumber  = trim($fields[3]);
    $description    = trim($fields[4]);
    $transDateRaw   = trim($fields[5]);
    $amount         = (float) str_replace(',', '', trim($fields[6]));
    $salesmanCode   = trim($fields[7]);
    $cost           = (float) str_replace(',', '', trim($fields[8]));
    $orderMethod    = isset($fields[9]) ? trim($fields[9]) : null;

    if (empty($customerCode) || empty($invoiceNumber)) {
        $skipped++;
        continue;
    }

    $clientId  = $clientMap[$customerCode] ?? null;
    if ($clientId === null) {
        $unmatched++;
    }

    $periodDate  = parseDate($periodRaw);
    $transDate   = parseDate($transDateRaw);

    $insert->execute([
        ':customer_code'   => $customerCode,
        ':client_id'       => $clientId,
        ':category_code'   => $categoryCode,
        ':period_date'     => $periodDate,
        ':invoice_number'  => $invoiceNumber,
        ':description'     => $description,
        ':transaction_date'=> $transDate,
        ':amount'          => $amount,
        ':salesman_code'   => $salesmanCode,
        ':cost'            => $cost,
        ':order_method'    => $orderMethod,
    ]);

    $imported++;

    if ($imported % 500 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        echo "  ... $imported rows committed\n";
    }
}

$pdo->commit();
fclose($handle);

echo "\n=== Import Complete ===\n";
echo "Imported : $imported\n";
echo "Skipped  : $skipped\n";
echo "Unmatched: $unmatched (customer code not in clients table)\n";

// Show summary
$stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT customer_code) as unique_customers, COUNT(DISTINCT invoice_number) as unique_invoices FROM sales_history");
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nDB Summary:\n";
echo "  Total rows    : {$summary['total']}\n";
echo "  Unique customers : {$summary['unique_customers']}\n";
echo "  Unique invoices  : {$summary['unique_invoices']}\n";
