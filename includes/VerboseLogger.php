<?php
/**
 * Comprehensive Logging System for Cadman Website
 * Saves verbose logs to 8TB drive with rotation and categorization
 */

class VerboseLogger {
    private static $logBasePath = '/media/user0/backup/cadman_logs';
    private static $maxFileSize = 100 * 1024 * 1024; // 100MB per log file
    private static $maxFiles = 50; // Keep 50 rotated files per category
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const SECURITY = 'SECURITY';
    const PERFORMANCE = 'PERFORMANCE';
    
    // Log categories
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_DATABASE = 'database';
    const CATEGORY_MODAL = 'modal';
    const CATEGORY_SEARCH = 'search';
    const CATEGORY_ADMIN = 'admin';
    const CATEGORY_API = 'api';
    const CATEGORY_PERFORMANCE = 'performance';
    
    /**
     * Initialize logging directory structure
     */
    public static function init() {
        $basePath = self::$logBasePath;
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        $categories = [
            self::CATEGORY_GENERAL,
            self::CATEGORY_SECURITY, 
            self::CATEGORY_DATABASE,
            self::CATEGORY_MODAL,
            self::CATEGORY_SEARCH,
            self::CATEGORY_ADMIN,
            self::CATEGORY_API,
            self::CATEGORY_PERFORMANCE
        ];
        
        foreach ($categories as $category) {
            $categoryPath = $basePath . '/' . $category;
            if (!is_dir($categoryPath)) {
                mkdir($categoryPath, 0755, true);
            }
        }
        
        // Create index file to prevent directory browsing
        file_put_contents($basePath . '/index.html', '<!-- Access denied -->');
    }
    
    /**
     * Log a message with full context
     */
    public static function log($level, $category, $message, $context = []) {
        try {
            self::init();
            
            $timestamp = date('Y-m-d H:i:s.u');
            $microtime = microtime(true);
            $requestId = self::getRequestId();
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
            $memoryUsage = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);
            
            // Build comprehensive log entry
            $logEntry = [
                'timestamp' => $timestamp,
                'microtime' => $microtime,
                'level' => $level,
                'category' => $category,
                'request_id' => $requestId,
                'client_ip' => $clientIP,
                'user_agent' => $userAgent,
                'request_method' => $requestMethod,
                'request_uri' => $requestUri,
                'memory_usage' => $memoryUsage,
                'peak_memory' => $peakMemory,
                'message' => $message,
                'context' => $context,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ];
            
            // Format for human readability
            $formatted = sprintf(
                "[%s] [%s] [%s] [IP: %s] [REQ: %s] [MEM: %s] %s\n",
                $timestamp,
                $level,
                $category,
                $clientIP,
                $requestId,
                self::formatBytes($memoryUsage),
                $message
            );
            
            // Add context if provided
            if (!empty($context)) {
                $formatted .= "Context: " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
            
            // Write to category-specific log file
            self::writeToFile($category, $formatted);
            
            // Also write JSON format for machine parsing
            self::writeToFile($category . '_json', json_encode($logEntry) . "\n");
            
            // Critical logs also go to main log
            if (in_array($level, [self::ERROR, self::SECURITY])) {
                self::writeToFile('critical', $formatted);
            }
            
        } catch (Exception $e) {
            // Fallback logging to system log if our logging fails
            error_log("VerboseLogger failed: " . $e->getMessage());
        }
    }
    
    /**
     * Write log entry to file with rotation
     */
    private static function writeToFile($category, $content) {
        $logDir = self::$logBasePath . '/' . $category;
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        
        // Ensure directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Check if rotation is needed
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            self::rotateLogFile($logFile);
        }
        
        // Write to log file
        file_put_contents($logFile, $content, FILE_APPEND | LOCK_EX);
        
        // Set proper permissions
        chmod($logFile, 0644);
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private static function rotateLogFile($logFile) {
        $timestamp = date('H-i-s');
        $rotatedFile = $logFile . '.' . $timestamp;
        
        if (rename($logFile, $rotatedFile)) {
            // Compress the rotated file
            if (function_exists('gzopen')) {
                $gz = gzopen($rotatedFile . '.gz', 'wb9');
                if ($gz) {
                    gzwrite($gz, file_get_contents($rotatedFile));
                    gzclose($gz);
                    unlink($rotatedFile);
                }
            }
            
            // Clean up old files
            self::cleanupOldLogs(dirname($logFile));
        }
    }
    
    /**
     * Clean up old log files
     */
    private static function cleanupOldLogs($logDir) {
        $files = glob($logDir . '/*.gz');
        if (count($files) > self::$maxFiles) {
            // Sort by modification time, oldest first
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToDelete = array_slice($files, 0, count($files) - self::$maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Generate unique request ID for tracking
     */
    private static function getRequestId() {
        static $requestId = null;
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        return $requestId;
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Convenience methods for different log levels
     */
    public static function debug($category, $message, $context = []) {
        self::log(self::DEBUG, $category, $message, $context);
    }
    
    public static function info($category, $message, $context = []) {
        self::log(self::INFO, $category, $message, $context);
    }
    
    public static function warning($category, $message, $context = []) {
        self::log(self::WARNING, $category, $message, $context);
    }
    
    public static function error($category, $message, $context = []) {
        self::log(self::ERROR, $category, $message, $context);
    }
    
    public static function security($message, $context = []) {
        self::log(self::SECURITY, self::CATEGORY_SECURITY, $message, $context);
    }
    
    public static function performance($message, $context = []) {
        self::log(self::PERFORMANCE, self::CATEGORY_PERFORMANCE, $message, $context);
    }
    
    /**
     * Log database query performance
     */
    public static function logQuery($query, $params, $executionTime, $rowCount = null) {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'row_count' => $rowCount
        ];
        
        $level = $executionTime > 1.0 ? self::WARNING : self::INFO;
        self::log($level, self::CATEGORY_DATABASE, "Database query executed", $context);
    }
    
    /**
     * Log API endpoint access
     */
    public static function logApiAccess($endpoint, $responseTime, $responseSize = null) {
        $context = [
            'endpoint' => $endpoint,
            'response_time_ms' => round($responseTime * 1000, 2),
            'response_size_bytes' => $responseSize,
            'post_data' => $_POST,
            'get_data' => $_GET
        ];
        
        self::log(self::INFO, self::CATEGORY_API, "API endpoint accessed", $context);
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $severity, $details = []) {
        $context = array_merge($details, [
            'event_type' => $event,
            'severity' => $severity,
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);
        
        self::security("Security event: $event", $context);
    }
}
?>