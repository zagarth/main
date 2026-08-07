<?php
require_once 'auth.php';
requireAdmin();

// Log file paths
$logFiles = [
    'watcher' => '/var/log/collections_watcher.log',
    'admin' => '/var/log/admin_actions.log'
];

$action = $_GET['action'] ?? 'read';
$file = $_GET['file'] ?? 'watcher';

// Validate file parameter
if (!isset($logFiles[$file])) {
    http_response_code(400);
    echo "Invalid log file specified";
    exit;
}

$logFile = $logFiles[$file];

switch ($action) {
    case 'read':
        readLogFile($logFile);
        break;
        
    case 'clear':
        clearLogFile($logFile, $file);
        break;
        
    default:
        http_response_code(400);
        echo "Invalid action specified";
        break;
}

function readLogFile($logFile) {
    header('Content-Type: text/plain');
    
    if (!file_exists($logFile)) {
        echo "Log file not found: " . basename($logFile);
        return;
    }
    
    if (!is_readable($logFile)) {
        echo "Cannot read log file: " . basename($logFile);
        return;
    }
    
    // Read last 5000 lines to prevent memory issues
    $lines = [];
    $handle = fopen($logFile, 'r');
    
    if ($handle) {
        // Read file line by line from the end
        $filesize = filesize($logFile);
        if ($filesize > 0) {
            fseek($handle, $filesize);
            $line = '';
            $cursor = $filesize;
            
            while ($cursor >= 0 && count($lines) < 5000) {
                fseek($handle, $cursor);
                $char = fgetc($handle);
                if ($char === "\n" || $cursor === 0) {
                    if ($line !== '') {
                        array_unshift($lines, $line);
                        $line = '';
                    }
                } else {
                    $line = $char . $line;
                }
                $cursor--;
            }
        }
        fclose($handle);
        
        echo implode("\n", $lines);
    } else {
        echo "Error opening log file";
    }
}

function clearLogFile($logFile, $fileType) {
    header('Content-Type: text/plain');
    
    // Log the clear action
    logAdminAction('LOG_CLEAR', "Cleared $fileType logs");
    
    if (file_exists($logFile)) {
        if (is_writable($logFile)) {
            $timestamp = date('Y-m-d H:i:s');
            $clearMessage = "[$timestamp] Log cleared by admin: " . $_SESSION['admin_username'] . "\n";
            
            if (file_put_contents($logFile, $clearMessage) !== false) {
                echo "Log file cleared successfully";
            } else {
                echo "Error clearing log file";
            }
        } else {
            echo "Log file is not writable";
        }
    } else {
        echo "Log file does not exist";
    }
}
?>
