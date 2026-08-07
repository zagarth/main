<?php
/**
 * Log Viewer API
 * Provides data for the verbose logs dashboard
 */

// Security check - admin only
session_start();
require_once '../includes/VerboseLogger.php';

header('Content-Type: application/json');

// Basic admin authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$logBasePath = '/media/user0/backup/cadman_logs';
$category = $_GET['category'] ?? '';
$level = $_GET['level'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$lines = (int)($_GET['lines'] ?? 100);
$search = $_GET['search'] ?? '';

try {
    // Determine which log file(s) to read
    $logFiles = [];
    
    if ($category) {
        $categoryPath = $logBasePath . '/' . $category;
        if (is_dir($categoryPath)) {
            $logFile = $categoryPath . '/' . $date . '.log';
            if (file_exists($logFile)) {
                $logFiles[] = $logFile;
            }
        }
    } else {
        // Read from all categories
        $categories = ['general', 'security', 'database', 'modal', 'search', 'admin', 'api', 'performance'];
        foreach ($categories as $cat) {
            $logFile = $logBasePath . '/' . $cat . '/' . $date . '.log';
            if (file_exists($logFile)) {
                $logFiles[] = $logFile;
            }
        }
    }
    
    // Read and combine logs
    $allLogs = [];
    foreach ($logFiles as $logFile) {
        $fileContent = file_get_contents($logFile);
        if ($fileContent) {
            $fileLogs = explode("\n", trim($fileContent));
            $allLogs = array_merge($allLogs, $fileLogs);
        }
    }
    
    // Filter by level if specified
    if ($level) {
        $allLogs = array_filter($allLogs, function($log) use ($level) {
            return strpos($log, "[$level]") !== false;
        });
    }
    
    // Filter by search term if specified
    if ($search) {
        $allLogs = array_filter($allLogs, function($log) use ($search) {
            return stripos($log, $search) !== false;
        });
    }
    
    // Sort by timestamp (most recent first)
    usort($allLogs, function($a, $b) {
        // Extract timestamps
        preg_match('/\[([^\]]+)\]/', $a, $matchA);
        preg_match('/\[([^\]]+)\]/', $b, $matchB);
        
        if (!isset($matchA[1]) || !isset($matchB[1])) {
            return 0;
        }
        
        $timeA = strtotime($matchA[1]);
        $timeB = strtotime($matchB[1]);
        
        return $timeB - $timeA; // Descending order
    });
    
    // Limit number of lines
    $allLogs = array_slice($allLogs, 0, $lines);
    
    // Calculate statistics
    $stats = calculateStats($logBasePath, $date);
    
    echo json_encode([
        'success' => true,
        'logs' => $allLogs,
        'stats' => $stats,
        'total_lines' => count($allLogs),
        'date' => $date
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load logs: ' . $e->getMessage()
    ]);
}

/**
 * Calculate log statistics
 */
function calculateStats($logBasePath, $date) {
    $stats = [
        'total_logs' => 0,
        'error_count' => 0,
        'security_events' => 0,
        'disk_usage' => 'Unknown'
    ];
    
    try {
        // Count logs by category for today
        $categories = ['general', 'security', 'database', 'modal', 'search', 'admin', 'api', 'performance'];
        
        foreach ($categories as $category) {
            $logFile = $logBasePath . '/' . $category . '/' . $date . '.log';
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $lines = substr_count($content, "\n");
                $stats['total_logs'] += $lines;
                
                // Count errors and security events
                $stats['error_count'] += substr_count($content, '[ERROR]');
                $stats['security_events'] += substr_count($content, '[SECURITY]');
            }
        }
        
        // Calculate disk usage of log directory
        $dirSize = getDirSize($logBasePath);
        $stats['disk_usage'] = formatBytes($dirSize);
        
    } catch (Exception $e) {
        // Ignore stats calculation errors
    }
    
    return $stats;
}

/**
 * Get directory size recursively
 */
function getDirSize($directory) {
    $size = 0;
    if (!is_dir($directory)) {
        return $size;
    }
    
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>