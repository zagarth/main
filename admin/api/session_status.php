<?php
require_once dirname(__FILE__) . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extend the session
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session extended',
        'expires_at' => time() + ini_get('session.gc_maxlifetime')
    ]);
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check session status
    $maxLifetime = ini_get('session.gc_maxlifetime');
    $lastActivity = $_SESSION['last_activity'] ?? time();
    $timeRemaining = $maxLifetime - (time() - $lastActivity);
    
    echo json_encode([
        'success' => true,
        'time_remaining' => max(0, $timeRemaining),
        'max_lifetime' => $maxLifetime,
        'expires_at' => $lastActivity + $maxLifetime
    ]);
}
?>