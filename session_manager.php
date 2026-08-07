<?php
/**
 * Session Management for Cadman Manufacturing
 * COMPATIBILITY SHIM - Uses unified SessionManager class
 * This file maintains backward compatibility with code that includes session_manager.php
 */

// Use the unified SessionManager class for all session handling
require_once __DIR__ . '/includes/SessionManager.php';

// Initialize session via SessionManager singleton
SessionManager::getInstance();

// Initialize session tracking for backward compatibility
if (!isset($_SESSION['session_created'])) {
    $_SESSION['session_created'] = time();
    $_SESSION['session_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['session_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

// Set cache control headers to prevent page caching
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}
