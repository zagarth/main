#!/usr/bin/env php
<?php
/**
 * Cron Job Script - Update Gold Prices
 * Run this script 3 times per day at 8 AM, 10 AM, and 12 PM EST
 * 
 * Crontab entry (EST timezone):
 * 0 8,10,12 * * * /usr/bin/php /var/www/html/homesite/cron_update_gold_price.php >> /var/www/html/homesite/logs/gold_cron.log 2>&1
 */

// Set timezone
date_default_timezone_set('America/New_York');

echo "[" . date('Y-m-d H:i:s') . "] Starting gold price update...\n";

// Database configuration - use encrypted credentials
require_once __DIR__ . '/includes/db_config_encrypted.php';

// API Configuration
$apiKey = 'goldapi-dag1usmnc2ner5-io'; // Your GoldAPI.io API key

try {
    // Connect to database with write access
    $pdo = getAdminConnection();
    
    echo "Database connected successfully\n";
    
    // Create table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS gold_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        price DECIMAL(10,2) NOT NULL,
        recorded_at DATETIME NOT NULL,
        INDEX idx_recorded_at (recorded_at)
    )";
    $pdo->exec($createTable);
    
    // Fetch live gold price from GoldAPI.io
    $url = "https://www.goldapi.io/api/XAU/CAD";
    
    echo "Fetching price from: $url\n";
    
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
        throw new Exception("API returned HTTP $httpCode. Error: $curlError");
    }
    
    if (!$result) {
        throw new Exception("Empty response from API");
    }
    
    echo "API Response (HTTP $httpCode): $result\n";
    
    $data = json_decode($result, true);
    
    if (!isset($data['price'])) {
        throw new Exception("Invalid API response format - no price field");
    }
    
    $newPrice = floatval($data['price']);
    
    echo "New price: CA\$$newPrice\n";
    
    // Insert new price into database
    $stmt = $pdo->prepare("INSERT INTO gold_prices (price, recorded_at) VALUES (?, NOW())");
    $stmt->execute([$newPrice]);
    
    echo "Price saved to database (ID: " . $pdo->lastInsertId() . ")\n";
    
    // Clean up old data - keep only last 7 days
    $stmt = $pdo->exec("DELETE FROM gold_prices WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    
    echo "Cleaned up old records. Deleted: $stmt rows\n";
    
    // Get current count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gold_prices");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total records in database: " . $count['count'] . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Gold price update completed successfully\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Gold price update failed\n";
    exit(1);
}

exit(0);
