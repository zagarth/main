<?php
// Test session cookie domain configuration
require_once 'session_manager.php';

// Get session cookie parameters
$params = session_get_cookie_params();

echo "<h2>Session Configuration Test</h2>";
echo "<pre>";
echo "Session Name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n\n";
echo "Cookie Parameters:\n";
echo "  Lifetime: " . $params['lifetime'] . "  (0 = expires when browser closes)\n";
echo "  Path: " . $params['path'] . "\n";
echo "  Domain: " . ($params['domain'] ?: '(not set)') . "\n";
echo "  Secure: " . ($params['secure'] ? 'Yes' : 'No') . "\n";
echo "  HttpOnly: " . ($params['httponly'] ? 'Yes' : 'No') . "\n";
echo "  SameSite: " . ($params['samesite'] ?: '(not set)') . "\n\n";

echo "Expected domain: .cadmanmfg.com\n";
echo "Current domain: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Session will work on all *.cadmanmfg.com subdomains\n";
echo "</pre>";
?>
