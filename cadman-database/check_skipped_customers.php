<?php
/**
 * Check which customers from AR-EXP01.csv were not imported
 */

require_once '../includes/db_config.php';

$csvFile = __DIR__ . '/AR-EXP01.csv';

if (!file_exists($csvFile)) {
    die("Error: AR-EXP01.csv not found\n");
}

echo "Checking for skipped customers...\n\n";

try {
    $pdo = getDBConnection();
    
    // Get all imported customer codes
    $stmt = $pdo->query("SELECT customer_code FROM clients");
    $importedCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $importedSet = array_flip($importedCodes);
    
    // Parse CSV and find missing ones
    $handle = fopen($csvFile, 'r');
    $header = fgetcsv($handle, 0, '|'); // Skip header
    
    $skipped = [];
    $lineNum = 1;
    
    while (($row = fgetcsv($handle, 0, '|')) !== false) {
        $lineNum++;
        
        if (count($row) < 13) {
            $skipped[] = [
                'line' => $lineNum,
                'reason' => 'Invalid row (too few columns)',
                'code' => isset($row[0]) ? trim($row[0]) : 'N/A',
                'name' => isset($row[1]) ? trim($row[1]) : 'N/A',
                'data' => implode('|', $row)
            ];
            continue;
        }
        
        $customerCode = trim($row[0]);
        $name = trim($row[1]);
        $addr2 = trim($row[2]);
        $addr3 = trim($row[3]);
        $cityProv = trim($row[4]);
        
        // Check if this customer code is NOT in the database
        if (!isset($importedSet[$customerCode])) {
            $reason = [];
            if (empty($name) && empty($addr2) && empty($cityProv)) {
                $reason[] = 'Empty name, address, and city';
            } elseif (empty($name)) {
                $reason[] = 'Empty name';
            }
            if (empty($addr2)) {
                $reason[] = 'Empty address';
            }
            if (empty($cityProv)) {
                $reason[] = 'Empty city/province';
            }
            
            $skipped[] = [
                'line' => $lineNum,
                'code' => $customerCode,
                'name' => $name ?: '(empty)',
                'address' => $addr2,
                'city_prov' => $cityProv,
                'reason' => !empty($reason) ? implode(', ', $reason) : 'Unknown'
            ];
        }
    }
    
    fclose($handle);
    
    echo "Found " . count($skipped) . " skipped customers:\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($skipped as $i => $skip) {
        echo ($i + 1) . ". Line {$skip['line']}: {$skip['code']} - {$skip['name']}\n";
        if (isset($skip['address'])) {
            echo "   Address: {$skip['address']}\n";
            echo "   City/Prov: {$skip['city_prov']}\n";
        }
        echo "   Reason: {$skip['reason']}\n";
        if (isset($skip['data'])) {
            echo "   Raw: " . substr($skip['data'], 0, 100) . "...\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
