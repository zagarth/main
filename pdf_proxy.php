<?php
// PDF Proxy for PDF.js viewer
// Serves PDF files with proper headers to avoid CORS issues

$file = $_GET['file'] ?? '';

// Validate and sanitize the file parameter
if (empty($file)) {
    http_response_code(400);
    die('No file specified');
}

// Remove any path traversal attempts
$file = str_replace(['../', '..\\', '\\'], '', $file);

// Ensure it's a PDF file
if (!preg_match('/\.pdf$/i', $file)) {
    http_response_code(400);
    die('Invalid file type');
}

// Build the full path
$pdfPath = __DIR__ . '/' . $file;

// Check if file exists and is readable
if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
    http_response_code(404);
    die('File not found');
}

// Set proper headers for PDF serving
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfPath));
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Serve the file
readfile($pdfPath);
?>