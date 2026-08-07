<?php
/**
 * Silver Price API - Canadian Silver Prices with Database Storage
 * Fetches live prices from GoldAPI.io 1x per day at 12 PM EST
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=1800'); // prices update 1x/day via cron
header('Vary: Accept-Encoding');

// Database configuration
require_once __DIR__ . '/includes/db_config_encrypted.php';

// Scheduled update time (EST) - 1 update/day at 8 AM (updated by cron_update_metals.php)
$updateHourEST = 8;
date_default_timezone_set('America/New_York');

try {
    // Read-only connection — writes handled by cron_update_metals.php
    $pdo = getViewerConnection();

    // Get current price (most recent)
    $stmt = $pdo->query("SELECT price, recorded_at FROM silver_prices ORDER BY recorded_at DESC LIMIT 1");
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentPrice = $currentData ? floatval($currentData['price']) : 39.85;
    
    // Get previous price for change calculation (previous record)
    $stmt = $pdo->query("SELECT price FROM silver_prices ORDER BY recorded_at DESC LIMIT 1 OFFSET 1");
    $previousData = $stmt->fetch(PDO::FETCH_ASSOC);
    $previousPrice = $previousData ? floatval($previousData['price']) : $currentPrice;
    
    // Calculate change
    $changeAmount = $currentPrice - $previousPrice;
    $changePercent = ($previousPrice > 0) ? ($changeAmount / $previousPrice) * 100 : 0;
    
    // Get 5-day rolling history (last 5 data points at 1x per day)
    $stmt = $pdo->query("SELECT price, recorded_at, 
                         DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i:%s') as date,
                         DATE_FORMAT(recorded_at, '%W') as day
                         FROM silver_prices 
                         ORDER BY recorded_at ASC 
                         LIMIT 5");
    $priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no history yet, use placeholder data
    if (empty($priceHistory)) {
        $priceHistory = [
            ['date' => '2026-03-24 08:00:00', 'price' => 39.25, 'day' => 'Monday'],
            ['date' => '2026-03-25 08:00:00', 'price' => 39.48, 'day' => 'Tuesday'],
            ['date' => '2026-03-26 08:00:00', 'price' => 39.62, 'day' => 'Wednesday'],
            ['date' => '2026-03-27 08:00:00', 'price' => 39.15, 'day' => 'Thursday'],
            ['date' => '2026-03-28 08:00:00', 'price' => 39.85, 'day' => 'Friday']
        ];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'current_price' => number_format($currentPrice, 2),
        'change_amount' => number_format($changeAmount, 2),
        'change_percent' => number_format($changePercent, 2),
        'price_history' => $priceHistory,
        'next_update' => '8:00 AM',
        'last_updated' => $currentData['recorded_at'] ?? date('Y-m-d H:i:s'),
        'currency' => 'CAD',
        'unit' => 'per troy ounce',
        'data_points' => count($priceHistory)
    ]);
    exit;
    
} catch (Exception $e) {
    // Fallback with static data on error
    echo json_encode([
        'success' => true,
        'current_price' => '39.85',
        'change_amount' => '0.70',
        'change_percent' => '1.79',
        'price_history' => [
            ['date' => '2026-03-24 12:00:00', 'price' => 39.25, 'day' => 'Monday'],
            ['date' => '2026-03-25 12:00:00', 'price' => 39.48, 'day' => 'Tuesday'],
            ['date' => '2026-03-26 12:00:00', 'price' => 39.62, 'day' => 'Wednesday'],
            ['date' => '2026-03-27 12:00:00', 'price' => 39.15, 'day' => 'Thursday'],
            ['date' => '2026-03-28 12:00:00', 'price' => 39.85, 'day' => 'Friday']
        ],
        'next_update' => '12:00 PM',
        'last_updated' => date('Y-m-d H:i:s'),
        'currency' => 'CAD',
        'unit' => 'per troy ounce',
        'error_fallback' => true,
        'error' => $e->getMessage()
    ]);
    exit;
}
