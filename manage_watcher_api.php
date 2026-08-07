<?php
/**
 * Watcher Management API
 * Provides web interface to control the collections file watcher service
 */

require_once 'auth.php';
requireLogin();

// Set JSON response header
header('Content-Type: application/json');

// Get the action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Service configuration
$serviceName = 'collections-watcher';
$watcherScript = __DIR__ . '/universal_collections_watcher.sh';
$manageScript = __DIR__ . '/manage_watcher.sh';
$logFile = '/var/log/collections_watcher.log';

/**
 * Execute system command safely
 */
function executeCommand($command) {
    $output = [];
    $returnVar = 0;
    exec($command . ' 2>&1', $output, $returnVar);
    return [
        'success' => $returnVar === 0,
        'output' => implode("\n", $output),
        'exit_code' => $returnVar
    ];
}

/**
 * Get service status
 */
function getServiceStatus() {
    global $serviceName, $logFile;
    
    $result = executeCommand("systemctl is-active $serviceName");
    $isActive = $result['success'] && trim($result['output']) === 'active';
    
    $enabledResult = executeCommand("systemctl is-enabled $serviceName");
    $isEnabled = $enabledResult['success'] && trim($enabledResult['output']) === 'enabled';
    
    $status = [
        'active' => $isActive,
        'enabled' => $isEnabled,
        'status' => $isActive ? 'running' : 'stopped'
    ];
    
    // Get additional info if running
    if ($isActive) {
        $pidResult = executeCommand("systemctl show --property MainPID --value $serviceName");
        $memResult = executeCommand("systemctl show --property MemoryCurrent --value $serviceName");
        $startTimeResult = executeCommand("systemctl show --property ActiveEnterTimestamp --value $serviceName");
        
        $status['pid'] = trim($pidResult['output']);
        $status['memory'] = trim($memResult['output']);
        $status['started'] = trim($startTimeResult['output']);
    }
    
    // Get recent log entries
    if (file_exists($logFile)) {
        $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $status['recent_logs'] = array_slice($logLines, -10);
        $status['log_file_size'] = filesize($logFile);
    } else {
        $status['recent_logs'] = [];
        $status['log_file_size'] = 0;
    }
    
    return $status;
}

/**
 * Start the watcher service
 */
function startService() {
    global $serviceName;
    
    // First check if service file exists
    if (!file_exists("/etc/systemd/system/$serviceName.service")) {
        return [
            'success' => false,
            'message' => 'Service not installed. Please install the service first.',
            'output' => ''
        ];
    }
    
    logAdminAction('WATCHER_START_ATTEMPT');
    
    $result = executeCommand("sudo systemctl start $serviceName");
    
    if ($result['success']) {
        logAdminAction('WATCHER_STARTED');
        return [
            'success' => true,
            'message' => 'File watcher service started successfully',
            'output' => $result['output']
        ];
    } else {
        logAdminAction('WATCHER_START_FAILED', $result['output']);
        return [
            'success' => false,
            'message' => 'Failed to start watcher service',
            'output' => $result['output']
        ];
    }
}

/**
 * Stop the watcher service
 */
function stopService() {
    global $serviceName;
    
    logAdminAction('WATCHER_STOP_ATTEMPT');
    
    $result = executeCommand("sudo systemctl stop $serviceName");
    
    if ($result['success']) {
        logAdminAction('WATCHER_STOPPED');
        return [
            'success' => true,
            'message' => 'File watcher service stopped successfully',
            'output' => $result['output']
        ];
    } else {
        logAdminAction('WATCHER_STOP_FAILED', $result['output']);
        return [
            'success' => false,
            'message' => 'Failed to stop watcher service',
            'output' => $result['output']
        ];
    }
}

/**
 * Restart the watcher service
 */
function restartService() {
    global $serviceName;
    
    logAdminAction('WATCHER_RESTART_ATTEMPT');
    
    $result = executeCommand("sudo systemctl restart $serviceName");
    
    if ($result['success']) {
        logAdminAction('WATCHER_RESTARTED');
        return [
            'success' => true,
            'message' => 'File watcher service restarted successfully',
            'output' => $result['output']
        ];
    } else {
        logAdminAction('WATCHER_RESTART_FAILED', $result['output']);
        return [
            'success' => false,
            'message' => 'Failed to restart watcher service',
            'output' => $result['output']
        ];
    }
}

/**
 * Install the watcher service
 */
function installService() {
    global $serviceName;
    
    $serviceFile = __DIR__ . "/collections-watcher.service";
    $systemServiceFile = "/etc/systemd/system/$serviceName.service";
    
    if (!file_exists($serviceFile)) {
        return [
            'success' => false,
            'message' => 'Service file not found',
            'output' => "Service file not found: $serviceFile"
        ];
    }
    
    logAdminAction('WATCHER_INSTALL_ATTEMPT');
    
    // Copy service file
    $copyResult = executeCommand("sudo cp '$serviceFile' '$systemServiceFile'");
    if (!$copyResult['success']) {
        return [
            'success' => false,
            'message' => 'Failed to copy service file',
            'output' => $copyResult['output']
        ];
    }
    
    // Reload systemd
    $reloadResult = executeCommand("sudo systemctl daemon-reload");
    if (!$reloadResult['success']) {
        return [
            'success' => false,
            'message' => 'Failed to reload systemd',
            'output' => $reloadResult['output']
        ];
    }
    
    // Enable service
    $enableResult = executeCommand("sudo systemctl enable $serviceName");
    
    logAdminAction('WATCHER_INSTALLED');
    
    return [
        'success' => true,
        'message' => 'Watcher service installed and enabled',
        'output' => $enableResult['output']
    ];
}

// Handle the requested action
try {
    switch ($action) {
        case 'status':
            $response = getServiceStatus();
            break;
            
        case 'start':
            $response = startService();
            break;
            
        case 'stop':
            $response = stopService();
            break;
            
        case 'restart':
            $response = restartService();
            break;
            
        case 'install':
            $response = installService();
            break;
            
        case 'logs':
            global $logFile;
            if (file_exists($logFile)) {
                $lines = $_GET['lines'] ?? 50;
                $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $response = [
                    'success' => true,
                    'logs' => array_slice($logs, -$lines),
                    'total_lines' => count($logs)
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Log file not found',
                    'logs' => []
                ];
            }
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Invalid action specified',
                'available_actions' => ['status', 'start', 'stop', 'restart', 'install', 'logs']
            ];
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'action' => $action
    ]);
}
?>