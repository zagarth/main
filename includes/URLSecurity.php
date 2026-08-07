<?php
/**
 * URL Security Enhancement Class
 * Provides comprehensive URL injection protection
 */

require_once __DIR__ . '/VerboseLogger.php';

class URLSecurity {
    
    /**
     * Validate and sanitize product ID input
     * @param string $productId
     * @return string|false
     */
    public static function validateProductId($productId) {
        VerboseLogger::debug(VerboseLogger::CATEGORY_SECURITY, "Validating product ID", ['input' => $productId]);
        
        // Remove any null bytes, control characters
        $productId = filter_var($productId, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        // Validate format: alphanumeric + allowed special chars only
        if (!preg_match('/^[A-Za-z0-9._-]{1,20}$/', $productId)) {
            VerboseLogger::security("Invalid product ID format rejected", ['input' => $productId, 'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }
        
        VerboseLogger::debug(VerboseLogger::CATEGORY_SECURITY, "Product ID validation passed", ['sanitized' => $productId]);
        return $productId;
    }
    
    /**
     * Validate search terms
     * @param string $term
     * @return string|false
     */
    public static function validateSearchTerm($term) {
        // Length check
        if (strlen($term) > 100) {
            return false;
        }
        
        // Remove script tags, null bytes
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $term = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $term);
        
        return trim($term);
    }
    
    /**
     * Validate file paths to prevent directory traversal
     * @param string $path
     * @return string|false
     */
    public static function validateFilePath($path) {
        // Block directory traversal attempts
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            return false;
        }
        
        // Only allow specific file extensions
        if (!preg_match('/\.(jpg|jpeg|png|gif|pdf|css|js)$/i', $path)) {
            return false;
        }
        
        return $path;
    }
    
    /**
     * Check for common injection patterns
     * @param string $input
     * @return bool
     */
    public static function containsInjection($input) {
        $patterns = [
            '/\b(union|select|insert|update|delete|drop|exec|script)\b/i',
            '/[<>"\']/',  // HTML/XML injection
            '/\${|\$\(/',  // Template injection
            '/javascript:/i',  // JavaScript injection
            '/vbscript:/i',   // VBScript injection
            '/data:/i',       // Data URI injection
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                VerboseLogger::security("Injection attempt detected", [
                    'input' => $input,
                    'pattern_matched' => $pattern,
                    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rate limiting for URL requests
     * @param string $clientIP
     * @param int $maxRequests
     * @param int $timeWindow
     * @return bool
     */
    public static function checkRateLimit($clientIP, $maxRequests = 100, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . '/url_rate_limit_' . md5($clientIP);
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && time() - $data['timestamp'] < $timeWindow) {
                if ($data['count'] >= $maxRequests) {
                    return false; // Rate limit exceeded
                }
                $data['count']++;
            } else {
                $data = ['timestamp' => time(), 'count' => 1];
            }
        } else {
            $data = ['timestamp' => time(), 'count' => 1];
        }
        
        file_put_contents($cacheFile, json_encode($data));
        
        // Log rate limit check for monitoring
        if ($data['count'] > $maxRequests * 0.8) {
            VerboseLogger::warning(VerboseLogger::CATEGORY_SECURITY, "High request rate detected", [
                'client_ip' => $clientIP,
                'request_count' => $data['count'],
                'max_requests' => $maxRequests
            ]);
        }
        
        return true;
    }
}
?>