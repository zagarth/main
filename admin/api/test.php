<?php
header('Content-Type: application/json');

// Simple test endpoint
echo json_encode([
    'success' => true,
    'message' => 'API endpoint is working',
    'timestamp' => time(),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);
?>