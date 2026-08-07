#!/usr/bin/env php
<?php
/**
 * Initialize Gold Price Database with Historical Data
 * Run this once to populate the database with your provided historical prices
 */

// Set timezone
date_default_timezone_set('America/New_York');

echo "=== Gold Price Database Initialization ===\n\n";

// Database configuration - use encrypted credentials
require_once __DIR__ . '/includes/db_config_encrypted.php';

// Your historical data (Monday-Friday from last week) - Realistic prices $6000-$6300
$historicalData = [
    ['date' => '2026-03-24 09:00:00', 'price' => 6125.70],
    ['date' => '2026-03-24 10:00:00', 'price' => 6132.30],
    ['date' => '2026-03-24 12:00:00', 'price' => 6139.45],
    
    ['date' => '2026-03-25 09:00:00', 'price' => 6199.94],
    ['date' => '2026-03-25 10:00:00', 'price' => 6195.20],
    ['date' => '2026-03-25 12:00:00', 'price' => 6188.50],
    
    ['date' => '2026-03-26 09:00:00', 'price' => 6183.79],
    ['date' => '2026-03-26 10:00:00', 'price' => 6178.65],
    ['date' => '2026-03-26 12:00:00', 'price' => 6169.30],
    
    ['date' => '2026-03-27 09:00:00', 'price' => 6076.18],
    ['date' => '2026-03-27 10:00:00', 'price' => 6085.50],
    ['date' => '2026-03-27 12:00:00', 'price' => 6092.85],
    
    ['date' => '2026-03-28 09:00:00', 'price' => 6143.65],
    ['date' => '2026-03-28 10:00:00', 'price' => 6151.20],
    ['date' => '2026-03-28 12:00:00', 'price' => 6147.45]
];

try {
    // Connect to database with write access
    $pdo = getAdminConnection();
    
    echo "✓ Database connected\n";
    
    // Create table
    $createTable = "CREATE TABLE IF NOT EXISTS gold_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        price DECIMAL(10,2) NOT NULL,
        recorded_at DATETIME NOT NULL,
        INDEX idx_recorded_at (recorded_at)
    )";
    $pdo->exec($createTable);
    
    echo "✓ Table created/verified\n\n";
    
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gold_prices");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing['count'] > 0) {
        echo "Database already contains {$existing['count']} records.\n";
        echo "Do you want to clear and reinitialize? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        
        if (strtolower($line) !== 'yes') {
            echo "Initialization cancelled.\n";
            exit(0);
        }
        
        $pdo->exec("TRUNCATE TABLE gold_prices");
        echo "✓ Cleared existing data\n\n";
    }
    
    // Insert historical data
    echo "Inserting historical data:\n";
    $stmt = $pdo->prepare("INSERT INTO gold_prices (price, recorded_at) VALUES (?, ?)");
    
    foreach ($historicalData as $record) {
        $stmt->execute([$record['price'], $record['date']]);
        echo "  • {$record['date']} - CA\${$record['price']}\n";
    }
    
    echo "\n✓ Inserted " . count($historicalData) . " historical records\n";
    
    // Display summary
    $stmt = $pdo->query("SELECT 
        MIN(price) as min_price, 
        MAX(price) as max_price, 
        AVG(price) as avg_price,
        MIN(recorded_at) as first_date,
        MAX(recorded_at) as last_date,
        COUNT(*) as total
        FROM gold_prices");
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== Summary ===\n";
    echo "Total records: {$summary['total']}\n";
    echo "Date range: {$summary['first_date']} to {$summary['last_date']}\n";
    echo "Price range: CA\${$summary['min_price']} - CA\${$summary['max_price']}\n";
    echo "Average price: CA\$" . number_format($summary['avg_price'], 2) . "\n";
    
    echo "\n✓ Initialization complete!\n\n";
    echo "Next steps:\n";
    echo "1. Set up cron job to run 3x daily:\n";
    echo "   0 8,10,12 * * * /usr/bin/php /var/www/html/homesite/cron_update_gold_price.php\n";
    echo "2. Test the API: https://yoursite.com/gold_price_api.php\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
