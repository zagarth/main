<?php
/**
 * Password Reset Testing Script
 * This script will test the password reset functionality
 */

require_once 'admin/auth.php';

echo "<h2>Password Reset Debug Tool</h2>\n";

// Test current password
echo "<h3>Current System Status:</h3>\n";
echo "ADMIN_USERNAME: " . ADMIN_USERNAME . "<br>\n";
echo "Current password hash: " . ADMIN_PASSWORD_HASH . "<br>\n";
echo "Hash algorithm: " . password_get_info(ADMIN_PASSWORD_HASH)['algoName'] . "<br>\n";

// Test original password
$originalPassword = 'cadman123';
$originalValid = password_verify($originalPassword, ADMIN_PASSWORD_HASH);
echo "Original password '$originalPassword' works: " . ($originalValid ? '✅ YES' : '❌ NO') . "<br>\n";

// Test generating a new hash
$testPassword = 'NewTestPassword123!';
echo "<h3>Testing Password Generation:</h3>\n";

$hashDefault = password_hash($testPassword, PASSWORD_DEFAULT);
$hashArgon = generatePasswordHash($testPassword);

echo "PASSWORD_DEFAULT hash: $hashDefault<br>\n";
echo "generatePasswordHash: $hashArgon<br>\n";

echo "PASSWORD_DEFAULT verify: " . (password_verify($testPassword, $hashDefault) ? '✅ YES' : '❌ NO') . "<br>\n";
echo "generatePasswordHash verify: " . (password_verify($testPassword, $hashArgon) ? '✅ YES' : '❌ NO') . "<br>\n";

// Check what happens if we update .env with argon hash
echo "<h3>Environment File Test:</h3>\n";
$envFile = __DIR__ . '/admin/.env';
$envContent = file_get_contents($envFile);
echo "Current .env readable: " . (file_exists($envFile) && is_readable($envFile) ? '✅ YES' : '❌ NO') . "<br>\n";
echo "Current .env writable: " . (file_exists($envFile) && is_writable($envFile) ? '✅ YES' : '❌ NO') . "<br>\n";

// Test login with current credentials  
echo "<h3>Login Test:</h3>\n";
session_start();
$username = ADMIN_USERNAME;
$password = $originalPassword;

$usernameValid = hash_equals(ADMIN_USERNAME, $username);
$passwordValid = password_verify($password, ADMIN_PASSWORD_HASH);

echo "Username '$username' validation: " . ($usernameValid ? '✅ VALID' : '❌ INVALID') . "<br>\n";
echo "Password '$password' validation: " . ($passwordValid ? '✅ VALID' : '❌ INVALID') . "<br>\n";
echo "Login would succeed: " . (($usernameValid && $passwordValid) ? '✅ YES' : '❌ NO') . "<br>\n";

?>