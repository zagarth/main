<?php
/**
 * Global Site Configuration
 * Cadman Manufacturing
 * 
 * Centralized configuration for site-wide features
 */

// ===== PRICING VISIBILITY =====
// Use authenticated session role when a session is already active.
// Do not start a session here; many pages include this mid-render.
if (!defined('SHOW_PRICING')) {
    $canShowPricing = false;
    if (session_status() === PHP_SESSION_ACTIVE) {
        $canShowPricing = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
            && isset($_SESSION['role'])
            && in_array($_SESSION['role'], ['admin', 'business'], true);
    }
    define('SHOW_PRICING', $canShowPricing);
}

// ===== END CONFIGURATION =====
?>
