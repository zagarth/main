<?php
/**
 * Admin Thumbnail Generator
 * Web interface for the thumbnail generation system
 */

require_once 'auth.php';
requireAdmin();

// Include the main thumbnail generator
require_once '../generate_thumbnails.php';

// Set proper headers for web response
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    echo "=== ADMIN THUMBNAIL GENERATOR ===\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    echo "User: " . ($_SESSION['admin_username'] ?? 'unknown') . "\n";
    echo "==========================================\n\n";
    
    // Flush output immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    ob_start();
    
    // Call the main thumbnail generation function
    generateThumbnails();
    
    echo "\n==========================================\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "=== THUMBNAIL GENERATION COMPLETE ===\n";
    
    // Final flush
    if (ob_get_level()) {
        ob_end_flush();
    }
} else {
    // Invalid request
    http_response_code(400);
    echo "Invalid request. This endpoint requires POST with action=generate.\n";
}
?>