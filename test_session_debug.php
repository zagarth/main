<?php
require_once __DIR__ . '/session_manager.php';

echo "<h2>Session Debug</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NONE') . "</p>";

echo "<h3>Session Configuration:</h3>";
echo "<pre>";
echo "cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "</pre>";

echo "<h3>Session Cookie Name:</h3>";
echo "<p>" . session_name() . "</p>";

echo "<h3>Current Domain/Host:</h3>";
echo "<p>HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "</p>";
echo "<p>SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "</p>";
echo "<p>HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "</p>";

$session = SessionManager::getInstance();
$token = $session->generateContactCSRFToken();

echo "<h3>CSRF Token Generated:</h3>";
echo "<p>" . htmlspecialchars($token) . "</p>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookies Sent to Browser:</h3>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";
?>
