#!/usr/bin/env php
<?php
/**
 * Batch Geocoding Script for AR Clients
 * 
 * This script geocodes all clients with missing coordinates using the Nominatim API.
 * It processes clients in batches with a 1-second delay between requests to respect rate limits.
 * 
 * Usage: php batch_geocode_clients.php
 */

// Set execution time limit (755 clients * 1 second = ~13 minutes)
set_time_limit(1800); // 30 minutes to be safe

// Database connection
require_once __DIR__ . '/../includes/db_config.php';

// Color codes for terminal output
class TerminalColors {
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const RED = "\033[31m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
}

// Statistics tracking
$stats = [
    'total' => 0,
    'success' => 0,
    'failed' => 0,
    'skipped' => 0,
    'start_time' => microtime(true)
];

echo TerminalColors::BOLD . TerminalColors::CYAN;
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          AR CLIENT BATCH GEOCODING TOOL                        ║\n";
echo "║          Uses OpenStreetMap Nominatim API                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo TerminalColors::RESET;
echo "\n";

// Get all clients with missing coordinates
echo TerminalColors::BLUE . "📊 Querying clients with missing coordinates..." . TerminalColors::RESET . "\n";

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT client_id, business_name, address, city, province, postal_code, country, latitude, longitude
        FROM clients
        WHERE status = 'Active'
        AND (latitude IS NULL OR latitude = 0 OR latitude = 50 OR longitude IS NULL OR longitude = 0)
        AND address IS NOT NULL
        AND city IS NOT NULL
        ORDER BY business_name
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['total'] = count($clients);
    
    echo TerminalColors::GREEN . "✓ Found " . $stats['total'] . " clients needing geocoding" . TerminalColors::RESET . "\n\n";
    
    if ($stats['total'] == 0) {
        echo TerminalColors::YELLOW . "✓ All clients already have coordinates!" . TerminalColors::RESET . "\n";
        exit(0);
    }
    
    // Estimate time
    $estimated_minutes = ceil($stats['total'] / 60);
    echo TerminalColors::YELLOW . "⏱️  Estimated time: ~{$estimated_minutes} minutes (1 request/second rate limit)" . TerminalColors::RESET . "\n";
    echo TerminalColors::YELLOW . "💡 Press Ctrl+C to cancel at any time" . TerminalColors::RESET . "\n\n";
    
    // Countdown
    echo "Starting in: ";
    for ($i = 3; $i > 0; $i--) {
        echo $i . "... ";
        sleep(1);
    }
    echo "\n\n";
    
    // Process each client
    $processed = 0;
    foreach ($clients as $client) {
        try {
            $processed++;
            
            echo TerminalColors::CYAN . "[{$processed}/{$stats['total']}] " . TerminalColors::RESET;
            echo TerminalColors::BOLD . $client['business_name'] . TerminalColors::RESET . "\n";
            flush(); // Force output immediately
            
            echo "   📍 " . $client['address'] . ", " . $client['city'] . ", " . $client['province'] . "\n";
            
            // Skip if no address
            if (empty($client['address']) || empty($client['city'])) {
                echo TerminalColors::YELLOW . "   ⚠️  Skipped (missing address data)" . TerminalColors::RESET . "\n\n";
                $stats['skipped']++;
                flush();
                continue;
            }
            
            // Geocode the address
            $result = geocodeAddress(
                $client['address'],
                $client['city'],
                $client['province'] ?? 'SK',
                $client['postal_code'] ?? '',
                $client['country'] ?? 'Canada'
            );
            
            if ($result['success']) {
                // Update database
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE clients 
                        SET latitude = :lat, longitude = :lng 
                        WHERE client_id = :id
                    ");
                    $updateStmt->execute([
                        'lat' => $result['lat'],
                        'lng' => $result['lng'],
                        'id' => $client['client_id']
                    ]);
                    
                    echo TerminalColors::GREEN . "   ✓ Success: " . number_format($result['lat'], 6) . ", " . number_format($result['lng'], 6) . TerminalColors::RESET . "\n";
                    if (!empty($result['display_name'])) {
                        echo TerminalColors::GREEN . "   📌 " . $result['display_name'] . TerminalColors::RESET . "\n";
                    }
                    $stats['success']++;
                    
                } catch (PDOException $e) {
                    echo TerminalColors::RED . "   ✗ Database error: " . $e->getMessage() . TerminalColors::RESET . "\n";
                    $stats['failed']++;
                }
            } else {
                // Geocoding failed - skip and continue
                echo TerminalColors::YELLOW . "   ⚠️  Skipped: " . ($result['error'] ?? 'Unknown error') . TerminalColors::RESET . "\n";
                $stats['skipped']++;
            }
            
            echo "\n";
            flush(); // Force output immediately
            
            // Progress bar
            $progress = ($processed / $stats['total']) * 100;
            $bar_length = 50;
            $filled = round(($progress / 100) * $bar_length);
            $bar = str_repeat('█', $filled) . str_repeat('░', $bar_length - $filled);
            echo "   Progress: [" . $bar . "] " . number_format($progress, 1) . "%\n\n";
            flush(); // Force output immediately
            
            // Rate limiting: 1 request per second (Nominatim requirement)
            if ($processed < $stats['total']) {
                sleep(1);
            }
            
        } catch (Exception $e) {
            echo TerminalColors::RED . "   ✗ CRITICAL ERROR: " . $e->getMessage() . TerminalColors::RESET . "\n";
            echo TerminalColors::RED . "   Stack trace: " . $e->getTraceAsString() . TerminalColors::RESET . "\n\n";
            $stats['failed']++;
            flush();
            // Continue with next client instead of crashing
            sleep(1);
        }
    }
    
    // Final statistics
    $elapsed = microtime(true) - $stats['start_time'];
    $minutes = floor($elapsed / 60);
    $seconds = $elapsed % 60;
    
    echo TerminalColors::BOLD . TerminalColors::CYAN;
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    GEOCODING COMPLETE                          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo TerminalColors::RESET;
    echo "\n";
    echo TerminalColors::GREEN . "✓ Successful: " . $stats['success'] . TerminalColors::RESET . "\n";
    echo TerminalColors::RED . "✗ Failed: " . $stats['failed'] . TerminalColors::RESET . "\n";
    echo TerminalColors::YELLOW . "⚠ Skipped: " . $stats['skipped'] . TerminalColors::RESET . "\n";
    echo TerminalColors::BLUE . "⏱️  Time elapsed: {$minutes}m " . number_format($seconds, 1) . "s" . TerminalColors::RESET . "\n";
    echo "\n";
    
    if ($stats['success'] > 0) {
        echo TerminalColors::GREEN . "✓ Updated {$stats['success']} client records with coordinates!" . TerminalColors::RESET . "\n";
        echo TerminalColors::CYAN . "💡 Refresh your retail map to see the new locations" . TerminalColors::RESET . "\n";
    }
    
    if ($stats['failed'] > 0) {
        echo TerminalColors::YELLOW . "\n⚠️  Some addresses could not be geocoded. You may need to:" . TerminalColors::RESET . "\n";
        echo "   • Check address formatting for failed clients\n";
        echo "   • Manually geocode problematic addresses\n";
        echo "   • Use the admin geocoding tool for review\n";
    }
    
} catch (PDOException $e) {
    echo TerminalColors::RED . "✗ Database error: " . $e->getMessage() . TerminalColors::RESET . "\n";
    exit(1);
}

/**
 * Geocode an address using OpenStreetMap Nominatim API
 * 
 * @param string $address Street address
 * @param string $city City name
 * @param string $province Province code (SK, AB, etc.)
 * @param string $postal_code Postal code
 * @param string $country Country name
 * @return array Result with success, lat, lng, display_name, or error
 */
function geocodeAddress($address, $city, $province, $postal_code = '', $country = 'Canada') {
    // Build the full address string
    $addressParts = array_filter([
        $address,
        $city,
        $province,
        $postal_code,
        $country
    ]);
    $fullAddress = implode(', ', $addressParts);
    
    // Nominatim API endpoint
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'q' => $fullAddress,
        'countrycodes' => 'ca',
        'limit' => 1,
        'addressdetails' => 1
    ]);
    
    // Make API request
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'CadmanMfg-BatchGeocoder/1.0 (Admin Tool)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for errors
    if ($error) {
        return [
            'success' => false,
            'error' => 'CURL error: ' . $error
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'HTTP error: ' . $httpCode
        ];
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    if (!$data || !is_array($data) || count($data) === 0) {
        // Try fallback with just city and province
        return geocodeFallback($city, $province, $country);
    }
    
    // Extract coordinates
    $result = $data[0];
    
    return [
        'success' => true,
        'lat' => floatval($result['lat']),
        'lng' => floatval($result['lon']),
        'display_name' => $result['display_name'] ?? '',
        'strategy' => 'full_address'
    ];
}

/**
 * Fallback geocoding using just city and province
 * 
 * @param string $city City name
 * @param string $province Province code
 * @param string $country Country name
 * @return array Result
 */
function geocodeFallback($city, $province, $country = 'Canada') {
    $fallbackAddress = implode(', ', array_filter([$city, $province, $country]));
    
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'q' => $fallbackAddress,
        'countrycodes' => 'ca',
        'limit' => 1
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'CadmanMfg-BatchGeocoder/1.0 (Admin Tool)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (!$data || !is_array($data) || count($data) === 0) {
        return [
            'success' => false,
            'error' => 'Address not found (tried fallback: city + province)'
        ];
    }
    
    $result = $data[0];
    
    return [
        'success' => true,
        'lat' => floatval($result['lat']),
        'lng' => floatval($result['lon']),
        'display_name' => $result['display_name'] ?? '',
        'strategy' => 'city_province_fallback'
    ];
}
