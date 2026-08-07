<?php
// Debug script for Engagement collection
echo "Debugging Engagement Collection\n";

// Test directory existence
$dir = 'Engagement_php/images/Bridal';
echo "Directory exists: " . (is_dir($dir) ? "YES" : "NO") . "\n";
echo "Full path: " . realpath($dir) . "\n";

if (is_dir($dir)) {
    $files = scandir($dir);
    echo "Files found: " . count($files) . "\n";
    foreach ($files as $file) {
        if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
            echo "Image file: $file\n";
        }
    }
}
?>
