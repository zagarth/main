<?php
/**
 * Configuration Loader
 * Loads configuration from encrypted /var/www/config directory
 * Falls back to local .env if encrypted config not available
 */

// Check if we already loaded config
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);
    
    // First try to load from decrypted /var/www/config
    $config_file = '/var/www/config/.env.development';
    
    if (file_exists($config_file) && is_readable($config_file)) {
        loadEnvFile($config_file);
    } else {
        error_log("config_loader: cannot read $config_file (exists=" . (int)file_exists($config_file) . " readable=" . (int)is_readable($config_file) . ")");
        // Fallback to local .env if it exists
        $local_env = __DIR__ . '/../.env';
        if (file_exists($local_env)) {
            loadEnvFile($local_env);
        } else {
            error_log("WARNING: No configuration file found. Database credentials not loaded.");
        }
    }
}

/**
 * Load environment variables from file
 */
function loadEnvFile($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set in $_ENV superglobal
            $_ENV[$key] = $value;
            
            // Also set in environment
            putenv("$key=$value");
        }
    }
    
    return true;
}
?>
