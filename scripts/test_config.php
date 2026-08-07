<?php
/**
 * Test Configuration Loading
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          CONFIGURATION TEST                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/includes/config_loader.php';

echo "🔐 Admin Credentials:\n";
echo "   Username: " . ($_ENV['ADMIN_USERNAME'] ?? '❌ NOT LOADED') . "\n";
echo "   Password Hash: " . (isset($_ENV['ADMIN_PASSWORD_HASH']) ? '✅ LOADED' : '❌ NOT LOADED') . "\n\n";

echo "📊 Client Database:\n";
echo "   Host: " . ($_ENV['CLIENT_DB_HOST'] ?? '❌ NOT LOADED') . "\n";
echo "   Name: " . ($_ENV['CLIENT_DB_NAME'] ?? '❌ NOT LOADED') . "\n";
echo "   User: " . ($_ENV['CLIENT_DB_USER'] ?? '❌ NOT LOADED') . "\n";
echo "   Pass: " . (isset($_ENV['CLIENT_DB_PASS']) ? '✅ LOADED' : '❌ NOT LOADED') . "\n\n";

$loaded = 0;
if (isset($_ENV['ADMIN_USERNAME'])) $loaded++;
if (isset($_ENV['ADMIN_PASSWORD_HASH'])) $loaded++;
if (isset($_ENV['CLIENT_DB_USER'])) $loaded++;
if (isset($_ENV['CLIENT_DB_PASS'])) $loaded++;

echo "📊 Result: $loaded/4 credentials loaded\n\n";

if ($loaded === 4) {
    echo "✅ SUCCESS!\n\n";
    echo "DATABASE CREDENTIALS:\n";
    echo "  Database: CadmanClients\n";
    echo "  User: cadman_admin\n";
    echo "  Pass: Admin2025!Cadman\n\n";
}
?>
