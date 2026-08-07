<?php
require_once '../auth.php';
requireLogin();

// Increase execution time for large file operations
set_time_limit(60);

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['retailer_id', 'latitude', 'longitude', 'name', 'address', 'city', 'province'];
foreach ($required as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Use absolute path to data directory outside web root
    $dataPath = '/var/www/data/retailers.json';
    
    // Create data directory if it doesn't exist
    $dataDir = dirname($dataPath);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0750, true);
        chown($dataDir, 'www-data');
        chgrp($dataDir, 'www-data');
    }
    
    // If retailers.json doesn't exist in secure location, copy it there
    if (!file_exists($dataPath)) {
        $oldPath = '../../retailers.json';
        if (file_exists($oldPath)) {
            copy($oldPath, $dataPath);
            chmod($dataPath, 0640);
            chown($dataPath, 'www-data');
            chgrp($dataPath, 'www-data');
        } else {
            throw new Exception('Retailers data file not found');
        }
    }
    
    // Lock file for atomic operations
    $lockFile = $dataPath . '.lock';
    $lock = fopen($lockFile, 'w');
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        // Non-blocking lock - if can't get lock immediately, return error
        fclose($lock);
        throw new Exception('Another update is in progress, please try again');
    }
    
    // Load current data
    $retailersData = json_decode(file_get_contents($dataPath), true);
    if ($retailersData === null) {
        throw new Exception('Invalid JSON in retailers data file');
    }
    
    // Find and update retailer
    $updated = false;
    $retailerName = '';
    
    foreach ($retailersData as &$retailer) {
        if ($retailer['ID'] === $input['retailer_id']) {
            $retailer['lat'] = floatval($input['latitude']);
            $retailer['lng'] = floatval($input['longitude']);
            $retailer['name'] = trim($input['name']);
            $retailer['address'] = trim($input['address']);
            $retailer['city'] = trim($input['city']);
            $retailer['province'] = trim($input['province']);
            $retailer['postal_code'] = trim($input['postal_code'] ?? '');
            $retailer['phone'] = trim($input['phone'] ?? '');
            $retailerName = $retailer['name'];
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        throw new Exception('Retailer not found');
    }
    
    // Write updated data atomically
    $tempFile = $dataPath . '.tmp';
    if (file_put_contents($tempFile, json_encode($retailersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        throw new Exception('Failed to write temporary file');
    }
    
    if (!rename($tempFile, $dataPath)) {
        unlink($tempFile);
        throw new Exception('Failed to update retailers file');
    }
    
    // Release lock
    flock($lock, LOCK_UN);
    fclose($lock);
    unlink($lockFile);
    
    // Also update the public retailers.json for the map (with proper permissions)
    $publicPath = '../../retailers.json';
    if (file_exists($publicPath)) {
        // Use a more efficient method for large files
        if (copy($dataPath, $publicPath)) {
            chmod($publicPath, 0644);
        }
    }
    
    // Log the action
    logAdminAction('RETAILER_UPDATE', "Updated retailer coordinates: $retailerName");
    
    echo json_encode([
        'success' => true,
        'message' => 'Retailer updated successfully',
        'retailer_name' => $retailerName
    ]);
    
} catch (Exception $e) {
    // Release lock if we have it
    if (isset($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
    
    // Clean up temp file if it exists
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>