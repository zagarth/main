<?php
/**
 * Gold Price API - Canadian Gold Prices with Database Storage
 * Fetches live prices from GoldAPI.io 2x per day and stores rolling 7-day history
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=1800'); // prices update 2x/day via cron
header('Vary: Accept-Encoding');

// Database configuration - use encrypted credentials
require_once __DIR__ . '/includes/db_config_encrypted.php';

// Scheduled update times (EST) - for next update display
$updateHoursEST = [8, 10]; // 8 AM, 10 AM EST (updated by cron_update_metals.php)
date_default_timezone_set('America/New_York');

try {
    // Read-only connection — writes handled by cron_update_metals.php
    $pdo = getViewerConnection();

    $currentHour = intval(date('G')); // used for next update display

    // Get current price (most recent)
    $stmt = $pdo->query("SELECT price, recorded_at FROM gold_prices ORDER BY recorded_at DESC LIMIT 1");
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentPrice = $currentData ? floatval($currentData['price']) : 6304.25;
    
    // Get previous price for change calculation (previous record)
    $stmt = $pdo->query("SELECT price FROM gold_prices ORDER BY recorded_at DESC LIMIT 1 OFFSET 1");
    $previousData = $stmt->fetch(PDO::FETCH_ASSOC);
    $previousPrice = $previousData ? floatval($previousData['price']) : $currentPrice;
    
    // Calculate change
    $changeAmount = $currentPrice - $previousPrice;
    $changePercent = ($previousPrice > 0) ? ($changeAmount / $previousPrice) * 100 : 0;
    
    // Get 5-day rolling history (last 10 data points at 2x per day)
    $stmt = $pdo->query("SELECT price, recorded_at, 
                         DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i:%s') as date,
                         DATE_FORMAT(recorded_at, '%W') as day
                         FROM gold_prices 
                         ORDER BY recorded_at ASC 
                         LIMIT 10");
    $priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no history yet, use placeholder data
    if (empty($priceHistory)) {
        $priceHistory = [
            ['date' => '2026-03-24 09:00:00', 'price' => 6125.70, 'day' => 'Monday'],
            ['date' => '2026-03-25 09:00:00', 'price' => 6199.94, 'day' => 'Tuesday'],
            ['date' => '2026-03-26 09:00:00', 'price' => 6183.79, 'day' => 'Wednesday'],
            ['date' => '2026-03-27 09:00:00', 'price' => 6076.18, 'day' => 'Thursday'],
            ['date' => '2026-03-28 09:00:00', 'price' => 6143.65, 'day' => 'Friday']
        ];
    }
    
    // Calculate next update time
    $nextUpdateHour = null;
    foreach ($updateHoursEST as $hour) {
        if ($currentHour < $hour) {
            $nextUpdateHour = $hour;
            break;
        }
    }
    if ($nextUpdateHour === null) {
        $nextUpdateHour = $updateHoursEST[0];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'current_price' => number_format($currentPrice, 2),
        'change_amount' => number_format($changeAmount, 2),
        'change_percent' => number_format($changePercent, 2),
        'price_history' => $priceHistory,
        'next_update' => date('g:i A', strtotime("today {$nextUpdateHour}:00:00")),
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
        'current_price' => '6244.49',
        'change_amount' => '100.84',
        'change_percent' => '1.64',
        'price_history' => [
            ['date' => '2026-03-24 09:00:00', 'price' => 6125.70, 'day' => 'Monday'],
            ['date' => '2026-03-25 09:00:00', 'price' => 6199.94, 'day' => 'Tuesday'],
            ['date' => '2026-03-26 09:00:00', 'price' => 6183.79, 'day' => 'Wednesday'],
            ['date' => '2026-03-27 09:00:00', 'price' => 6076.18, 'day' => 'Thursday'],
            ['date' => '2026-03-28 09:00:00', 'price' => 6143.65, 'day' => 'Friday']
        ],
        'next_update' => '8:00 AM',
        'last_updated' => date('Y-m-d H:i:s'),
        'currency' => 'CAD',
        'unit' => 'per troy ounce',
        'error_fallback' => true,
        'error' => $e->getMessage()
    ]);
    exit;
}

/* OLD CODE - DISABLED
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// API Configuration - GoldAPI.io (Free: 100 requests/month)
// Sign up at: https://www.goldapi.io/
$apiKey = 'goldapi-dag1usmnc2ner5-io'; // Your API key

// Scheduled update times (EST) - 3 updates/day = ~90 requests/month
$updateHoursEST = [8, 10, 12]; // 8 AM, 10 AM, 12 PM EST

// Cache settings
$cacheFile = __DIR__ . '/cache/gold_price_cache.json';
$historyFile = __DIR__ . '/cache/gold_price_history.json';
$maxHistoryPoints = 24; // Keep last 24 data points (8 days @ 3 updates/day)

// Convert EST to server time (EST is UTC-5, or UTC-4 during DST)
date_default_timezone_set('America/New_York'); // EST/EDT

// Check if we need to update based on schedule
$currentHour = intval(date('G')); // 0-23 hour format
$lastUpdateTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
$lastUpdateHour = $lastUpdateTime > 0 ? intval(date('G', $lastUpdateTime)) : -1;

// Determine if we should fetch new data
$shouldUpdate = false;

// Check if we've passed any scheduled update time since last update
foreach ($updateHoursEST as $updateHour) {
    // If current hour >= update hour AND last update was before this hour today
    if ($currentHour >= $updateHour) {
        $todayUpdateTime = strtotime("today {$updateHour}:00:00");
        if ($lastUpdateTime < $todayUpdateTime) {
            $shouldUpdate = true;
            break;
        }
    }
}

// Return cached data if not time for update yet
if (!$shouldUpdate && file_exists($cacheFile)) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    
    // Load price history
    if (file_exists($historyFile)) {
        $history = json_decode(file_get_contents($historyFile), true);
        $cachedData['price_history'] = $history ?? [];
    } else {
        $cachedData['price_history'] = [];
    }
    
    // Add next update time info
    $nextUpdateHour = null;
    foreach ($updateHoursEST as $hour) {
        if ($currentHour < $hour) {
            $nextUpdateHour = $hour;
            break;
        }
    }
    if ($nextUpdateHour === null) {
        $nextUpdateHour = $updateHoursEST[0]; // Next day's first update
    }
    
    $cachedData['next_update'] = date('g:i A', strtotime("today {$nextUpdateHour}:00:00"));
    echo json_encode($cachedData);
    exit;
}

try {
    // Ensure cache directory exists
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Fetch live gold price from GoldAPI.io
    $url = "https://www.goldapi.io/api/XAU/CAD";
    
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
    curl_close($ch);
    
    if ($httpCode !== 200 || !$result) {
        throw new Exception("API returned HTTP $httpCode");
    }
    
    $data = json_decode($result, true);
    
    if (!isset($data['price'])) {
        throw new Exception("Invalid API response format");
    }
    
    // Get current price (per troy ounce)
    $currentPrice = floatval($data['price']);
    
    // Load previous price for change calculation
    $previousPrice = $currentPrice;
    if (file_exists($cacheFile)) {
        $previousData = json_decode(file_get_contents($cacheFile), true);
        if (isset($previousData['current_price'])) {
            $previousPrice = floatval($previousData['current_price']);
        }
    }
    
    $changeAmount = $currentPrice - $previousPrice;
    $changePercent = ($previousPrice > 0) ? ($changeAmount / $previousPrice) * 100 : 0;
    
    // Load and update price history
    $priceHistory = [];
    if (file_exists($historyFile)) {
        $priceHistory = json_decode(file_get_contents($historyFile), true) ?? [];
    }
    
    // Add new price point to history
    $priceHistory[] = [
        'price' => floatval($currentPrice),
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ];
    
    // Keep only last N points (trim old data)
    if (count($priceHistory) > $maxHistoryPoints) {
        $priceHistory = array_slice($priceHistory, -$maxHistoryPoints);
    }
    
    // Save history
    file_put_contents($historyFile, json_encode($priceHistory, JSON_PRETTY_PRINT));
    
    // Calculate next update time
    $nextUpdateHour = null;
    foreach ($updateHoursEST as $hour) {
        if ($currentHour < $hour) {
            $nextUpdateHour = $hour;
            break;
        }
    }
    if ($nextUpdateHour === null) {
        $nextUpdateHour = $updateHoursEST[0]; // Tomorrow's first update
    }
    
    $response = [
        'success' => true,
        'current_price' => number_format($currentPrice, 2, '.', ''),
        'change_amount' => number_format($changeAmount, 2, '.', ''),
        'change_percent' => number_format($changePercent, 2, '.', ''),
        'currency' => 'USD',
        'unit' => 'troy_ounce',
        'timestamp' => time(),
        'source' => 'goldapi.io',
        'high_24h' => number_format(floatval($data['high_price'] ?? $currentPrice), 2, '.', ''),
        'low_24h' => number_format(floatval($data['low_price'] ?? $currentPrice), 2, '.', ''),
        'update_schedule' => implode(', ', array_map(fn($h) => date('g A', strtotime("today {$h}:00")), $updateHoursEST)) . ' EST',
        'next_update' => date('g:i A', strtotime("today {$nextUpdateHour}:00:00")),
        'price_history' => $priceHistory
    ];
    
    // Save to cache
    file_put_contents($cacheFile, json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return error response with cached data fallback
    error_log("Gold Price API Error: " . $e->getMessage());
    
    // Try to return cached data even if expired
    if (file_exists($cacheFile)) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        $cachedData['cached'] = true;
        $cachedData['cache_age'] = time() - filemtime($cacheFile);
        echo json_encode($cachedData);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch gold price',
            'message' => $e->getMessage()
        ]);
    }
}

/*
 * SETUP INSTRUCTIONS:
 * 
 * Update Schedule: 8 AM, 10 AM, 12 PM EST daily
 * Total requests: ~90/month (well under 100 limit)
 * 
 * To change update times, edit $updateHoursEST array above
 * Example: [6, 12, 18] = 6 AM, 12 PM, 6 PM
 * 
 * Gold prices are cached between scheduled updates
 */
