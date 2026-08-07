<?php
/**
 * Encrypted Configuration Manager
 * Handles loading configuration from encrypted directory
 * Cadman Manufacturing - Enhanced Security
 */

class EncryptedConfig extends Config {
    private $encryption_password = null;
    private $temp_decrypt_dir = '/tmp/cadman_config_temp';
    
    public function __construct($encryption_password = null) {
        // If password provided, decrypt temporarily
        if ($encryption_password) {
            $this->encryption_password = $encryption_password;
            $this->temporaryDecrypt();
        }
        
        // Call parent constructor
        parent::__construct();
    }
    
    /**
     * Temporarily decrypt config for loading
     */
    private function temporaryDecrypt() {
        // Check if config is encrypted
        if (!file_exists('/var/www/config_encrypted')) {
            return; // Not encrypted, use normal flow
        }
        
        if (file_exists('/var/www/config')) {
            return; // Already decrypted
        }
        
        // Run Python decryption script
        $command = sprintf(
            'cd /var/www/html/homesite && echo %s | python3 config_encrypt.py decrypt 2>&1',
            escapeshellarg($this->encryption_password)
        );
        
        $output = shell_exec($command);
        
        if (strpos($output, 'Decryption completed') === false) {
            throw new Exception("Failed to decrypt configuration: " . $output);
        }
    }
    
    /**
     * Re-encrypt config after use (for extra security)
     */
    public function reEncrypt() {
        if (!$this->encryption_password) {
            return false; // No password available
        }
        
        $command = sprintf(
            'cd /var/www/html/homesite && echo %s | python3 config_encrypt.py encrypt 2>&1',
            escapeshellarg($this->encryption_password)
        );
        
        $output = shell_exec($command);
        
        return strpos($output, 'Encryption completed') !== false;
    }
    
    /**
     * Check if config directory is encrypted
     */
    public static function isEncrypted() {
        return file_exists('/var/www/config_encrypted') && !file_exists('/var/www/config');
    }
    
    /**
     * Get encryption status
     */
    public static function getEncryptionStatus() {
        $config_exists = file_exists('/var/www/config');
        $encrypted_exists = file_exists('/var/www/config_encrypted');
        
        if ($config_exists && !$encrypted_exists) {
            return 'unencrypted';
        } elseif (!$config_exists && $encrypted_exists) {
            return 'encrypted';
        } elseif ($config_exists && $encrypted_exists) {
            return 'both_exist';
        } else {
            return 'neither_exists';
        }
    }
}

/**
 * Helper function for encrypted config
 */
function encrypted_config($password, $key = null, $default = null) {
    static $encrypted_config_instance = null;
    
    if ($encrypted_config_instance === null) {
        $encrypted_config_instance = new EncryptedConfig($password);
    }
    
    if ($key === null) {
        return $encrypted_config_instance;
    }
    
    return $encrypted_config_instance->get($key, $default);
}
?>