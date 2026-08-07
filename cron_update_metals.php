#!/usr/bin/env php
<?php
/**
 * Cron Job Script - Update Gold and Silver Prices
 * Run this script 2 times per day at 8 AM and 10 AM EST
 * 
 * Gold: 8 AM, 10 AM (2x/day = 60/month)
 * Silver: 8 AM only (1x/day = 30/month)
 * Total: 90 API requests/month (within free tier)
 * 
 * Crontab entry (EST timezone):
 * 0 8,10 * * * /usr/bin/php /var/www/html/homesite/cron_update_metals.php >>  /var/www/html/homesite/logs/metals_cron.log 2>&1
 */

// Set timezone
date_default_timezone_set('America/New_York');

echo "[" . date('Y-m-d H:i:s') . "] Starting precious metals price update...\n";

// Database configuration - use encrypted credentials
require_once __DIR__ . '/includes/db_config_encrypted.php';

// API Configuration
$apiKey = 'goldapi-dag1usmnc2ner5-io'; // Your GoldAPI.io APIkey
$currentHour = intval(date('G'));

try {
    // Connect to database with write access
    $pdo = getAdminConnection();
    
    echo "Database connected successfully\n";
    
    // Create both tables if they don't exist
    $createGoldTable = "CREATE TABLE IF NOT EXISTS gold_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        price DECIMAL(10,2) NOT NULL,
        recorded_at DATETIME NOT NULL,
        INDEX idx_recorded_at (recorded_at)
    )";
    $pdo->exec($createGoldTable);
    
    $createSilverTable = "CREATE TABLE IF NOT EXISTS silver_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        price DECIMAL(10,2) NOT NULL,
        recorded_at DATETIME NOT NULL,
        INDEX idx_recorded_at (recorded_at)
    )";
    $pdo->exec($createSilverTable);
    
    echo "Database tables verified\n";
    
    // ===== UPDATE GOLD PRICE (Always: 8 AM, 10 AM) =====
    echo "\n--- Updating GOLD price ---\n";
    
    // Fetch live gold price
    $url = "https://www.goldapi.io/api/XAU/CAD";
    
    echo "Fetching GOLD price from: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-access-token: ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Gold API returned HTTP $httpCode. Error: $curlError");
    }
    
    if (!$result) {
        throw new Exception("Empty response from Gold API");
    }
    
    echo "Gold API Response (HTTP $httpCode)\n";
    
    $data = json_decode($result, true);
    
    if (!isset($data['price'])) {
        throw new Exception("Invalid Gold API response - no price field");
    }
    
    $goldPrice = floatval($data['price']);
    echo "New GOLD price: CA\$$goldPrice\n";
    
    // Insert gold price
    $stmt = $pdo->prepare("INSERT INTO gold_prices (price, recorded_at) VALUES (?, NOW())");
    $stmt->execute([$goldPrice]);
    echo "Gold price saved (ID: " . $pdo->lastInsertId() . ")\n";
    
    // Clean up old gold data
    $deleted = $pdo->exec("DELETE FROM gold_prices WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    echo "Cleaned up $deleted old gold records\n";
    
    
    // ===== UPDATE SILVER PRICE (Only at 8 AM) =====
    if ($currentHour == 8) {
        echo "\n--- Updating SILVER price (morning update) ---\n";
        
        // Fetch live silver price
        $url = "https://www.goldapi.io/api/XAG/CAD";
        
        echo "Fetching SILVER price from: $url\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-access-token: ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "[WARNING] Silver API returned HTTP $httpCode. Error: $curlError\n";
        } elseif (!$result) {
            echo "[WARNING] Empty response from Silver API\n";
        } else {
            echo "Silver API Response (HTTP $httpCode)\n";
            
            $data = json_decode($result, true);
            
            if (!isset($data['price'])) {
                echo "[WARNING] Invalid Silver API response - no price field\n";
            } else {
                $silverPrice = floatval($data['price']);
                echo "New SILVER price: CA\$$silverPrice\n";
                
                // Insert silver price
                $stmt = $pdo->prepare("INSERT INTO silver_prices (price, recorded_at) VALUES (?, NOW())");
                $stmt->execute([$silverPrice]);
                echo "Silver price saved (ID: " . $pdo->lastInsertId() . ")\n";
                
                // Clean up old silver data
                $deleted = $pdo->exec("DELETE FROM silver_prices WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                echo "Cleaned up $deleted old silver records\n";
            }
        }
    } else {
        echo "\n--- Skipping SILVER update (only at 8 AM, current hour: {$currentHour}) ---\n";
    }
    
    
    echo "\n=== Update Summary ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gold_prices");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total GOLD records: " . $count['count'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM silver_prices");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total SILVER records: " . $count['count'] . "\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] Precious metals update completed successfully\n";
    
    // Write sync status for admin dashboard
    $status = [
        'last_run'    => date('Y-m-d H:i:s'),
        'success'     => true,
        'gold_price'  => $goldPrice ?? null,
        'silver_price'=> $silverPrice ?? null,
        'error'       => null,
    ];
    file_put_contents(__DIR__ . '/cache/metals_sync_status.json', json_encode($status));
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Update failed\n";
    
    // Write failure status for admin dashboard
    $status = [
        'last_run' => date('Y-m-d H:i:s'),
        'success'  => false,
        'error'    => $e->getMessage(),
    ];
    file_put_contents(__DIR__ . '/cache/metals_sync_status.json', json_encode($status));
    exit(1);
}

exit(0);
