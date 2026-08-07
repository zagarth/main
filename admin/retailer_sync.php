<?php
// Only require auth for web interface, not CLI
if (php_sapi_name() !== 'cli') {
    require_once 'auth.php';
    requireAdmin();
}

// Configuration
$retailersTxtFile = '../admin/retailers.txt';
$retailersJsonFile = '../retailers.json';
$retailersXmlFile = '../retailers.xml';
$logFile = '../admin/retailer_sync_log.txt';

// Initialize logging
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// Parse retailers.txt file
function parseRetailersTxt($filePath) {
    $content = file_get_contents($filePath);
    $retailers = [];
    $entries = preg_split('/\n\s*\n/', trim($content));
    
    foreach ($entries as $entry) {
        $lines = array_filter(array_map('trim', explode("\n", $entry)));
        if (count($lines) >= 3) {
            $retailer = [
                'name' => $lines[0],
                'street' => $lines[1],
                'city_province_postal' => $lines[2],
                'phone' => isset($lines[3]) ? $lines[3] : '',
                'raw_entry' => $entry
            ];
            
            // Parse city, province, postal code
            if (preg_match('/^(.+?),\s*([A-Z]{2}),?\s*([A-Z0-9\s]+)?/', $retailer['city_province_postal'], $matches)) {
                $retailer['city'] = trim($matches[1]);
                $retailer['province'] = trim($matches[2]);
                $retailer['postal_code'] = isset($matches[3]) ? trim($matches[3]) : '';
            }
            
            $retailers[] = $retailer;
        }
    }
    
    return $retailers;
}

// Load existing retailers from JSON
function loadExistingRetailers($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    return json_decode(file_get_contents($filePath), true) ?: [];
}

// Check if retailer already exists (improved matching)
function retailerExists($newRetailer, $existingRetailers) {
    $newName = strtolower(trim($newRetailer['name']));
    $newCity = strtolower(trim($newRetailer['city'] ?? ''));
    
    // Normalize name for better matching
    $newNameNormalized = preg_replace('/\b(ltd|limited|inc|incorporated|corp|corporation|co|company)\b\.?/i', '', $newName);
    $newNameNormalized = preg_replace('/[^a-z0-9\s]/', '', $newNameNormalized);
    $newNameNormalized = preg_replace('/\s+/', ' ', trim($newNameNormalized));
    
    foreach ($existingRetailers as $existing) {
        $existingName = strtolower(trim($existing['name']));
        $existingCity = strtolower(trim($existing['city'] ?? ''));
        
        // Exact name match
        if ($existingName === $newName) {
            return true;
        }
        
        // Normalize existing name for comparison
        $existingNameNormalized = preg_replace('/\b(ltd|limited|inc|incorporated|corp|corporation|co|company)\b\.?/i', '', $existingName);
        $existingNameNormalized = preg_replace('/[^a-z0-9\s]/', '', $existingNameNormalized);
        $existingNameNormalized = preg_replace('/\s+/', ' ', trim($existingNameNormalized));
        
        // Normalized name match
        if ($existingNameNormalized === $newNameNormalized && !empty($newNameNormalized)) {
            return true;
        }
        
        // Similar name match with same city (more strict)
        if ($newCity === $existingCity && !empty($newCity)) {
            similar_text($existingNameNormalized, $newNameNormalized, $similarity);
            if ($similarity > 90) {
                return true;
            }
        }
        
        // Very similar name match (less strict)
        similar_text($existingName, $newName, $similarity);
        if ($similarity > 95) {
            return true;
        }
    }
    
    return false;
}

// Geocode address using the same method as add_retailer.php
function geocodeAddress($address, $city, $province) {
    $fullAddress = "$address, $city, $province, Canada";
    
    try {
        // Use OpenStreetMap Nominatim API with better error handling (same as add_retailer.php)
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($fullAddress) . "&limit=1&countrycodes=ca";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'CadmanMfg-RetailerSync/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to connect to geocoding service");
        }
        
        $data = json_decode($response, true);
        
        if ($data && count($data) > 0 && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $lat = floatval($data[0]['lat']);
            $lng = floatval($data[0]['lon']);
            
            // Validate coordinates are reasonable for Canada (same validation as add_retailer.php)
            if ($lat >= 41.6 && $lat <= 83.3 && $lng >= -141.0 && $lng <= -52.6) {
                return [
                    'lat' => round($lat, 6), 
                    'lng' => round($lng, 6), 
                    'method' => 'full_address',
                    'display_name' => $data[0]['display_name'] ?? 'Address found'
                ];
            }
        }
        
        // Fallback to simplified address format (same as add_retailer.php)
        $simplifiedAddress = "$city, $province, Canada";
        $url2 = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($simplifiedAddress) . "&limit=1&countrycodes=ca";
        
        $response2 = @file_get_contents($url2, false, $context);
        
        if ($response2 !== false) {
            $data2 = json_decode($response2, true);
            
            if ($data2 && count($data2) > 0 && isset($data2[0]['lat']) && isset($data2[0]['lon'])) {
                $lat = floatval($data2[0]['lat']);
                $lng = floatval($data2[0]['lon']);
                
                // Validate coordinates are in Canada bounds
                if ($lat >= 41.6 && $lat <= 83.3 && $lng >= -141.0 && $lng <= -52.6) {
                    return [
                        'lat' => round($lat, 6), 
                        'lng' => round($lng, 6), 
                        'method' => 'city_only',
                        'display_name' => $data2[0]['display_name'] ?? "$city, $province"
                    ];
                }
            }
        }
        
        throw new Exception("No valid coordinates found for this location");
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Add retailer to JSON database
function addRetailerToDatabase($retailer, $coordinates, $existingRetailers, $filePath) {
    // Generate new ID
    $maxId = 0;
    foreach ($existingRetailers as $existing) {
        if (isset($existing['ID'])) {
            $numericPart = (int) str_replace('ret_', '', $existing['ID']);
            if ($numericPart > $maxId) {
                $maxId = $numericPart;
            }
        }
    }
    $newId = 'ret_' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
    
    // Create full address
    $fullAddress = $retailer['street'];
    if (!empty($retailer['city'])) {
        $fullAddress .= ', ' . $retailer['city'];
    }
    if (!empty($retailer['province'])) {
        $fullAddress .= ', ' . $retailer['province'];
    }
    if (!empty($retailer['postal_code'])) {
        $fullAddress .= ', ' . $retailer['postal_code'];
    }
    $fullAddress .= ', Canada';
    
    // Create new retailer record
    $newRetailer = [
        'ID' => $newId,
        'name' => $retailer['name'],
        'address' => $fullAddress,
        'street' => $retailer['street'],
        'city' => $retailer['city'] ?? '',
        'state' => $retailer['province'] ?? '',
        'province' => $retailer['province'] ?? '',
        'postal_code' => $retailer['postal_code'] ?? '',
        'country' => 'Canada',
        'phone' => $retailer['phone'],
        'email' => '',
        'website' => '',
        'lat' => $coordinates['lat'],
        'lng' => $coordinates['lng'],
        'longitude' => $coordinates['lng'],
        'latitude' => $coordinates['lat'],
        'geocode_method' => $coordinates['method'],
        'created_at' => date('Y-m-d H:i:s'),
        'source' => 'retailers.txt_import'
    ];
    
    // Add to array
    $existingRetailers[] = $newRetailer;
    
    // Save to file
    $result = file_put_contents($filePath, json_encode($existingRetailers, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        throw new Exception("Failed to save to database");
    }
    
    return $newId;
}

// Main processing function
function processRetailers() {
    global $retailersTxtFile, $retailersJsonFile, $logFile;
    
    // Clear log file
    file_put_contents($logFile, "=== RETAILER SYNC STARTED ===\n");
    
    logMessage("Starting retailer sync process...");
    
    // Load data
    logMessage("Loading retailers.txt file...");
    $txtRetailers = parseRetailersTxt($retailersTxtFile);
    logMessage("Found " . count($txtRetailers) . " retailers in txt file");
    
    logMessage("Loading existing JSON database...");
    $existingRetailers = loadExistingRetailers($retailersJsonFile);
    logMessage("Found " . count($existingRetailers) . " existing retailers in database");
    
    // Track results
    $stats = [
        'total_txt' => count($txtRetailers),
        'existing_count' => count($existingRetailers),
        'already_exists' => 0,
        'geocode_success' => 0,
        'geocode_failed' => 0,
        'save_failed' => 0,
        'added_successfully' => 0
    ];
    
    $failedRetailers = [];
    $addedRetailers = [];
    
    foreach ($txtRetailers as $index => $retailer) {
        logMessage("Processing retailer " . ($index + 1) . "/" . count($txtRetailers) . ": " . $retailer['name']);
        
        // Check if already exists
        if (retailerExists($retailer, $existingRetailers)) {
            logMessage("  - Already exists, skipping");
            $stats['already_exists']++;
            continue;
        }
        
        // Validate required data
        if (empty($retailer['name']) || empty($retailer['city']) || empty($retailer['province'])) {
            logMessage("  - Missing required data (name/city/province), skipping");
            $failedRetailers[] = [
                'retailer' => $retailer,
                'reason' => 'Missing required data'
            ];
            continue;
        }
        
        // Geocode the address
        logMessage("  - Geocoding: " . $retailer['street'] . ", " . $retailer['city'] . ", " . $retailer['province']);
        $coordinates = geocodeAddress($retailer['street'], $retailer['city'], $retailer['province']);
        
        if (isset($coordinates['error'])) {
            logMessage("  - Geocoding failed: " . $coordinates['error']);
            $stats['geocode_failed']++;
            $failedRetailers[] = [
                'retailer' => $retailer,
                'reason' => 'Geocoding failed: ' . $coordinates['error']
            ];
            continue;
        }
        
        $methodDescription = $coordinates['method'] === 'full_address' ? 'exact address' : 'city center';
        logMessage("  - Geocoded successfully: " . $coordinates['lat'] . ", " . $coordinates['lng'] . " ($methodDescription)");
        if (isset($coordinates['display_name'])) {
            logMessage("    Location: " . $coordinates['display_name']);
        }
        $stats['geocode_success']++;
        
        // Add to database
        try {
            $newId = addRetailerToDatabase($retailer, $coordinates, $existingRetailers, $retailersJsonFile);
            logMessage("  - Added to database with ID: $newId");
            $stats['added_successfully']++;
            $addedRetailers[] = [
                'id' => $newId,
                'name' => $retailer['name'],
                'coordinates' => $coordinates
            ];
            
            // Update our local copy for next iteration
            $existingRetailers = loadExistingRetailers($retailersJsonFile);
            
        } catch (Exception $e) {
            logMessage("  - Failed to save: " . $e->getMessage());
            $stats['save_failed']++;
            $failedRetailers[] = [
                'retailer' => $retailer,
                'reason' => 'Save failed: ' . $e->getMessage()
            ];
        }
        
        // Rate limiting - pause between requests (be respectful to the API)
        logMessage("  - Waiting 1 second before next request...");
        sleep(1); // 1 second delay between geocoding requests
    }
    
    // Log final results
    logMessage("\n=== SYNC COMPLETE ===");
    logMessage("Total retailers in txt file: " . $stats['total_txt']);
    logMessage("Already existed: " . $stats['already_exists']);
    logMessage("Geocoding successful: " . $stats['geocode_success']);
    logMessage("Geocoding failed: " . $stats['geocode_failed']);
    logMessage("Save failed: " . $stats['save_failed']);
    logMessage("Successfully added: " . $stats['added_successfully']);
    
    return [
        'stats' => $stats,
        'failed' => $failedRetailers,
        'added' => $addedRetailers
    ];
}

// If running from command line or direct access
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'sync')) {
    $results = processRetailers();
    
    echo "\n=== FINAL SUMMARY ===\n";
    echo "Added: " . $results['stats']['added_successfully'] . "\n";
    echo "Failed: " . count($results['failed']) . "\n";
    
    if (!empty($results['failed'])) {
        echo "\nFailed retailers:\n";
        foreach ($results['failed'] as $failed) {
            echo "- " . $failed['retailer']['name'] . ": " . $failed['reason'] . "\n";
        }
    }
    
    if (!empty($results['added'])) {
        echo "\nSuccessfully added:\n";
        foreach ($results['added'] as $added) {
            echo "- " . $added['name'] . " (ID: " . $added['id'] . ")\n";
        }
    }
    
    exit;
}

// Web interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Sync Tool - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .sync-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .status-section {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .sync-button {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 0;
        }
        
        .sync-button:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
        }
        
        .log-output {
            background: #1a1a1a;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sync-container">
        <div class="admin-card">
            <div class="back-nav">
                <a href="index.php">← Back to Admin Portal</a>
                <a href="retailer_geocoding.php">← Geocoding Tool</a>
            </div>
            <h1 style="color: #1e3c72; margin: 0; text-align: center;">🔄 Retailer Sync Tool</h1>
            <p style="text-align: center; color: #666; margin: 10px 0 0 0;">Compare retailers.txt with database and add missing retailers</p>
        </div>

        <div class="status-section">
            <h3>Sync Process</h3>
            <p>This tool will:</p>
            <ul>
                <li>Parse the retailers.txt file</li>
                <li>Compare with existing database</li>
                <li>Geocode missing retailers</li>
                <li>Add them to the database</li>
                <li>Log any failures</li>
            </ul>
            
            <button class="sync-button" onclick="startSync()">🚀 Start Sync Process</button>
            
            <div id="progress" style="display: none;">
                <h4>Sync in progress...</h4>
                <div style="background: #ddd; border-radius: 10px; height: 20px;">
                    <div id="progressBar" style="background: #28a745; height: 20px; border-radius: 10px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p id="progressText">Starting...</p>
            </div>
        </div>

        <div class="log-output" id="logOutput" style="display: none;">
            <div id="logContent"></div>
        </div>
    </div>

    <script>
        function startSync() {
            document.getElementById('progress').style.display = 'block';
            document.getElementById('logOutput').style.display = 'block';
            
            // Start the sync process
            fetch('?run=sync')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('logContent').innerHTML = '<pre>' + data + '</pre>';
                    document.getElementById('progressText').textContent = 'Sync completed!';
                    document.getElementById('progressBar').style.width = '100%';
                })
                .catch(error => {
                    document.getElementById('logContent').innerHTML = '<pre>Error: ' + error + '</pre>';
                });
        }
    </script>
</body>
</html>
