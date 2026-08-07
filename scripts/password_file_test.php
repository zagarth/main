<?php
/**
 * Password File Write Test
 */

echo "<h2>🔧 Password File Write Test</h2>\n";

$envFile = '/var/www/html/homesite/admin/.env';

echo "<h3>File Information:</h3>\n";
echo "File path: $envFile<br>\n";
echo "File exists: " . (file_exists($envFile) ? '✅ YES' : '❌ NO') . "<br>\n";
echo "File readable: " . (is_readable($envFile) ? '✅ YES' : '❌ NO') . "<br>\n";
echo "File writable: " . (is_writable($envFile) ? '✅ YES' : '❌ NO') . "<br>\n";

// Check permissions
$perms = fileperms($envFile);
echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "<br>\n";
echo "File owner: " . fileowner($envFile) . "<br>\n";
echo "File group: " . filegroup($envFile) . "<br>\n";

// Check current user/group
echo "Current process user: " . getmyuid() . "<br>\n";
echo "Current process group: " . getmygid() . "<br>\n";

// Test read
echo "<h3>Read Test:</h3>\n";
$content = file_get_contents($envFile);
if ($content !== false) {
    echo "✅ File read successful (" . strlen($content) . " bytes)<br>\n";
    echo "Content preview: " . substr($content, 0, 100) . "...<br>\n";
} else {
    echo "❌ File read failed<br>\n";
}

// Test write (backup first)
echo "<h3>Write Test:</h3>\n";
$backupFile = $envFile . '.test-backup';
copy($envFile, $backupFile);

// Add a test comment
$testContent = $content . "\n# Test write at " . date('Y-m-d H:i:s') . "\n";
$writeResult = file_put_contents($envFile, $testContent);

if ($writeResult !== false) {
    echo "✅ File write successful ($writeResult bytes written)<br>\n";
    
    // Restore original
    copy($backupFile, $envFile);
    unlink($backupFile);
    echo "✅ Original file restored<br>\n";
} else {
    echo "❌ File write failed<br>\n";
    echo "Last error: " . error_get_last()['message'] . "<br>\n";
}

// Test password hash update simulation
echo "<h3>Password Hash Update Test:</h3>\n";
require_once '/var/www/html/homesite/admin/auth.php';

$currentHash = ADMIN_PASSWORD_HASH;
echo "Current hash: " . substr($currentHash, 0, 20) . "...<br>\n";

$testPassword = 'TestPassword123!';
$newHash = generatePasswordHash($testPassword);
echo "New hash generated: " . substr($newHash, 0, 20) . "...<br>\n";

// Test the regex replacement
$envContent = file_get_contents($envFile);
$newEnvContent = preg_replace('/^ADMIN_PASSWORD_HASH=.*$/m', 'ADMIN_PASSWORD_HASH=' . $newHash, $envContent);

if ($newEnvContent !== $envContent) {
    echo "✅ Regex replacement successful<br>\n";
    echo "New content preview: " . substr($newEnvContent, strpos($newEnvContent, 'ADMIN_PASSWORD_HASH'), 60) . "...<br>\n";
} else {
    echo "❌ Regex replacement failed<br>\n";
}

?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
h2, h3 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
</style>