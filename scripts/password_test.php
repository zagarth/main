<?php
/**
 * Simple Password Reset Test
 * This will test the password reset without sessions
 */

require_once 'admin/auth.php';

echo "<h2>Direct Password Reset Test</h2>\n";

// Test 1: Current password verification
$currentPassword = 'cadman123';
$currentHash = ADMIN_PASSWORD_HASH;

echo "<h3>Test 1: Current Password Verification</h3>\n";
echo "Testing password: $currentPassword<br>\n";
echo "Against hash: $currentHash<br>\n";
echo "Verification result: " . (password_verify($currentPassword, $currentHash) ? '✅ VALID' : '❌ INVALID') . "<br>\n";

// Test 2: Generate new password hash
$newPassword = 'NewPassword123!';
echo "<h3>Test 2: Generate New Hash</h3>\n";
echo "New password: $newPassword<br>\n";

$newHashDefault = password_hash($newPassword, PASSWORD_DEFAULT);
$newHashArgon = generatePasswordHash($newPassword);

echo "PASSWORD_DEFAULT hash: $newHashDefault<br>\n";
echo "generatePasswordHash: $newHashArgon<br>\n";

// Test 3: Verify new hashes work
echo "<h3>Test 3: Verify New Hashes</h3>\n";
echo "PASSWORD_DEFAULT verify: " . (password_verify($newPassword, $newHashDefault) ? '✅ VALID' : '❌ INVALID') . "<br>\n";
echo "generatePasswordHash verify: " . (password_verify($newPassword, $newHashArgon) ? '✅ VALID' : '❌ INVALID') . "<br>\n";

// Test 4: Simulate .env update
echo "<h3>Test 4: Simulate .env Update</h3>\n";
$envFile = __DIR__ . '/admin/.env';
$envBackup = $envFile . '.backup';

// Backup current .env
copy($envFile, $envBackup);

$envContent = file_get_contents($envFile);
echo "Original .env contains: " . (strpos($envContent, ADMIN_PASSWORD_HASH) !== false ? '✅ Current hash found' : '❌ Current hash not found') . "<br>\n";

// Try the update
$newEnvContent = preg_replace('/^ADMIN_PASSWORD_HASH=.*$/m', 'ADMIN_PASSWORD_HASH=' . $newHashArgon, $envContent);
$updateSuccess = file_put_contents($envFile, $newEnvContent);

echo "Update result: " . ($updateSuccess ? '✅ SUCCESS' : '❌ FAILED') . "<br>\n";

// Test 5: Reload and verify
echo "<h3>Test 5: Reload and Verify</h3>\n";

// Clear any opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPCache reset: ✅ DONE<br>\n";
}

// Manually reload the .env
unset($_ENV);
loadEnv($envFile);

$reloadedHash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';
echo "Reloaded hash: $reloadedHash<br>\n";
echo "Hash changed: " . ($reloadedHash !== $currentHash ? '✅ YES' : '❌ NO') . "<br>\n";
echo "New password works with reloaded hash: " . (password_verify($newPassword, $reloadedHash) ? '✅ VALID' : '❌ INVALID') . "<br>\n";
echo "Old password works with reloaded hash: " . (password_verify($currentPassword, $reloadedHash) ? '❌ STILL VALID (BAD)' : '✅ INVALID (GOOD)') . "<br>\n";

// Test 6: Restore original
echo "<h3>Test 6: Restore Original</h3>\n";
copy($envBackup, $envFile);
unlink($envBackup);
echo "Original .env restored<br>\n";

// Clear cache again
if (function_exists('opcache_reset')) {
    opcache_reset();
}

echo "<h3>🎯 Conclusion:</h3>\n";
echo "The password reset mechanism should work. The issue might be:<br>\n";
echo "1. CSRF token validation failing<br>\n";
echo "2. Session not persisting properly<br>\n";
echo "3. Environment not reloading in the same request<br>\n";

?>