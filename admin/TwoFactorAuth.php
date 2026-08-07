<?php
/**
 * Two-Factor Authentication (2FA) Library
 * Implements TOTP (Time-based One-Time Password) authentication
 */

class TwoFactorAuth {
    private const SECRET_LENGTH = 32;
    private const TIME_STEP = 30; // 30-second time steps
    private const WINDOW = 1; // Allow 1 time step before/after for clock drift
    
    /**
     * Generate a new secret key for 2FA
     */
    public static function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate QR code URL for setting up 2FA in authenticator apps
     */
    public static function getQRCodeUrl($secret, $username, $issuer = 'Cadman Manufacturing') {
        $label = urlencode($issuer . ':' . $username);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => self::TIME_STEP
        ]);
        
        return "otpauth://totp/{$label}?{$params}";
    }
    
    /**
     * Verify a TOTP code
     */
    public static function verifyCode($secret, $code, $time = null) {
        if ($time === null) {
            $time = time();
        }
        
        // Check current time window and adjacent windows for clock drift
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $testTime = $time + ($i * self::TIME_STEP);
            $expectedCode = self::generateCode($secret, $testTime);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for given time
     */
    private static function generateCode($secret, $time) {
        // Convert time to time step
        $timeStep = intval($time / self::TIME_STEP);
        
        // Convert secret from base32
        $binarySecret = self::base32Decode($secret);
        
        // Create time counter as 8-byte big-endian
        $timeBytes = pack('N*', 0, $timeStep);
        
        // Generate HMAC
        $hash = hash_hmac('sha1', $timeBytes, $binarySecret, true);
        
        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return sprintf('%06d', $code);
    }
    
    /**
     * Base32 decode function
     */
    private static function base32Decode($input) {
        $input = strtoupper($input);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $pos = strpos($alphabet, $char);
            
            if ($pos === false) {
                continue; // Skip invalid characters
            }
            
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }
        
        return $output;
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public static function isEnabled($username) {
        $secretFile = __DIR__ . '/2fa_secrets.json';
        if (!file_exists($secretFile)) {
            return false;
        }
        
        $secrets = json_decode(file_get_contents($secretFile), true);
        return isset($secrets[$username]) && !empty($secrets[$username]);
    }
    
    /**
     * Get user's 2FA secret
     */
    public static function getSecret($username) {
        $secretFile = __DIR__ . '/2fa_secrets.json';
        if (!file_exists($secretFile)) {
            return null;
        }
        
        $secrets = json_decode(file_get_contents($secretFile), true);
        return $secrets[$username] ?? null;
    }
    
    /**
     * Save user's 2FA secret
     */
    public static function saveSecret($username, $secret) {
        $secretFile = __DIR__ . '/2fa_secrets.json';
        $secrets = [];
        
        if (file_exists($secretFile)) {
            $secrets = json_decode(file_get_contents($secretFile), true) ?: [];
        }
        
        $secrets[$username] = $secret;
        
        $result = file_put_contents($secretFile, json_encode($secrets, JSON_PRETTY_PRINT));
        if ($result !== false) {
            chmod($secretFile, 0600); // Secure permissions
        }
        
        return $result !== false;
    }
    
    /**
     * Remove user's 2FA secret (disable 2FA)
     */
    public static function removeSecret($username) {
        $secretFile = __DIR__ . '/2fa_secrets.json';
        if (!file_exists($secretFile)) {
            return true;
        }
        
        $secrets = json_decode(file_get_contents($secretFile), true) ?: [];
        unset($secrets[$username]);
        
        return file_put_contents($secretFile, json_encode($secrets, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Generate backup codes for 2FA recovery
     */
    public static function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf('%04d-%04d', random_int(1000, 9999), random_int(1000, 9999));
        }
        return $codes;
    }
    
    /**
     * Save backup codes for user
     */
    public static function saveBackupCodes($username, $codes) {
        $backupFile = __DIR__ . '/2fa_backup_codes.json';
        $backups = [];
        
        if (file_exists($backupFile)) {
            $backups = json_decode(file_get_contents($backupFile), true) ?: [];
        }
        
        // Hash the backup codes for secure storage
        $hashedCodes = array_map(function($code) {
            return password_hash($code, PASSWORD_DEFAULT);
        }, $codes);
        
        $backups[$username] = $hashedCodes;
        
        $result = file_put_contents($backupFile, json_encode($backups, JSON_PRETTY_PRINT));
        if ($result !== false) {
            chmod($backupFile, 0600); // Secure permissions
        }
        
        return $result !== false;
    }
    
    /**
     * Verify and consume a backup code
     */
    public static function verifyBackupCode($username, $code) {
        $backupFile = __DIR__ . '/2fa_backup_codes.json';
        if (!file_exists($backupFile)) {
            return false;
        }
        
        $backups = json_decode(file_get_contents($backupFile), true) ?: [];
        if (!isset($backups[$username])) {
            return false;
        }
        
        $userCodes = $backups[$username];
        foreach ($userCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used backup code
                unset($backups[$username][$index]);
                $backups[$username] = array_values($backups[$username]); // Re-index array
                
                file_put_contents($backupFile, json_encode($backups, JSON_PRETTY_PRINT));
                return true;
            }
        }
        
        return false;
    }
}
?>