<?php
/**
 * Catalog ZIP Download Endpoint
 * Serves the complete catalog ZIP file
 */

// Set error handling
error_reporting(0); // Don't output errors during download
ini_set('display_errors', 0);

// Look for the most recent catalog ZIP file
$zipPattern = __DIR__ . '/Cadman_Complete_Catalog_*.zip';
$zipFiles = glob($zipPattern);

if (empty($zipFiles)) {
    http_response_code(404);
    die("Catalog ZIP file not found. Please contact support.");
}

// Get the most recent ZIP file
usort($zipFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$zipFile = $zipFiles[0];
$fileName = basename($zipFile);

// Verify file exists and is readable
if (!file_exists($zipFile) || !is_readable($zipFile)) {
    http_response_code(404);
    die("Catalog file not accessible. Please contact support.");
}

// Get file size
$fileSize = filesize($zipFile);

// Set headers for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Read and output file in chunks to handle large files
$chunkSize = 8192; // 8KB chunks
$handle = fopen($zipFile, 'rb');

if ($handle === false) {
    http_response_code(500);
    die("Error reading catalog file.");
}

while (!feof($handle)) {
    $buffer = fread($handle, $chunkSize);
    echo $buffer;
    flush();
}

fclose($handle);
exit;
?>
