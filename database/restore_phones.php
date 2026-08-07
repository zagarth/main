<?php
// Cross-reference Feb 2026 backup phones against live clients, matched on postal_code
require_once __DIR__ . '/../includes/db_config_encrypted.php';

$backupFile = __DIR__ . '/../database_backups/clients_backup_before_admin_update_20260225_184855.sql';
$sql = file_get_contents($backupFile);

// postal_code field is 8th value, phone is 10th value in the INSERT column order
preg_match_all(
    "/VALUES\s*\(\d+,\s*'[^']*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'[^']*',\s*'([^']*)',\s*'[^']*',\s*'([^']*)'/",
    $sql, $matches, PREG_SET_ORDER
);

$backupMap = []; // normalised postal_code => full phone
foreach ($matches as $m) {
    $postal = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $m[1]));
    $phone  = trim($m[2]);
    if ($postal === '' || $phone === '') continue;
    if (strlen(preg_replace('/[^0-9]/', '', $phone)) >= 10) {
        $backupMap[$postal] = $phone;
    }
}

echo 'Backup records with full phones: ' . count($backupMap) . PHP_EOL;

$pdo = getAdminConnection();

$rows = $pdo->query(
    "SELECT client_id, business_name, postal_code, phone FROM clients WHERE phone IS NOT NULL AND TRIM(phone) <> ''"
)->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$alreadyFull = 0;
$noMatch = 0;

foreach ($rows as $row) {
    $currentDigits = preg_replace('/[^0-9]/', '', (string)($row['phone'] ?? ''));
    if (strlen($currentDigits) >= 10) {
        $alreadyFull++;
        continue;
    }

    $livePostal = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($row['postal_code'] ?? '')));
    if ($livePostal === '' || !isset($backupMap[$livePostal])) {
        $noMatch++;
        continue;
    }

    $newPhone = $backupMap[$livePostal];
    $pdo->prepare("UPDATE clients SET phone = ? WHERE client_id = ?")->execute([$newPhone, (int)$row['client_id']]);
    echo sprintf("  FIXED [%d] %-45s  '%s' -> '%s'\n", $row['client_id'], $row['business_name'], $row['phone'], $newPhone);
    $updated++;
}

echo PHP_EOL;
echo "Updated  : $updated" . PHP_EOL;
echo "Already full: $alreadyFull" . PHP_EOL;
echo "No match : $noMatch" . PHP_EOL;
