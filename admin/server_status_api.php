<?php
// Web server connection monitoring API - Secured version
require_once __DIR__ . '/auth.php';
requireAdmin();

// Additional security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Security logging
error_log("Admin server status check by user: " . ($_SESSION['username'] ?? 'unknown') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Whitelist of allowed commands for security
const ALLOWED_COMMANDS = [
    'netstat_listen' => 'netstat -tln 2>/dev/null | grep -E ":(80|443) "',
    'disk_usage' => 'df -h / 2>/dev/null',
    'system_uptime' => 'uptime -p 2>/dev/null',
    'process_count' => 'ps ax 2>/dev/null | wc -l'
];

function secureShellExec($command_key) {
    if (!isset(ALLOWED_COMMANDS[$command_key])) {
        error_log("Unauthorized command attempt: $command_key");
        return false;
    }
    
    $command = ALLOWED_COMMANDS[$command_key];
    return shell_exec($command);
}

function getWebServerConnections() {
    $connections = [
        'listening_ports' => [],
        'established_connections' => [],
        'total_connections' => 0,
        'port_80' => 0,
        'port_443' => 0,
        'status' => 'unknown'
    ];
    
    try {
        // Get listening ports only (more secure than full netstat)
        $listening_output = secureShellExec('netstat_listen');
        
        // Process listening ports
        if ($listening_output) {
            $lines = explode("\n", trim($listening_output));
            foreach ($lines as $line) {
                if (empty($line)) continue;
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 4) {
                    $local_address = $parts[3];
                    
                    if (strpos($local_address, ':80') !== false) {
                        $connections['port_80']++;
                        $connections['listening_ports'][] = [
                            'port' => 80,
                            'state' => 'LISTEN',
                            'protocol' => (strpos($local_address, ':::') === 0) ? 'IPv6' : 'IPv4'
                        ];
                    }
                    
                    if (strpos($local_address, ':443') !== false) {
                        $connections['port_443']++;
                        $connections['listening_ports'][] = [
                            'port' => 443,
                            'state' => 'LISTEN', 
                            'protocol' => (strpos($local_address, ':::') === 0) ? 'IPv6' : 'IPv4'
                        ];
                    }
                }
            }
        }
        
        $connections['total_connections'] = count($connections['listening_ports']);
        
        // Determine status (only based on listening ports for security)
        $listening_80 = false;
        $listening_443 = false;
        
        foreach ($connections['listening_ports'] as $port) {
            if ($port['port'] == 80) $listening_80 = true;
            if ($port['port'] == 443) $listening_443 = true;
        }
        
        if ($listening_80 && $listening_443) {
            $connections['status'] = 'healthy';
        } else if ($listening_80 || $listening_443) {
            $connections['status'] = 'partial';
        } else {
            $connections['status'] = 'down';
        }
        
        // Limited server info (avoid exposing too much)
        $load = sys_getloadavg();
        $connections['server_load'] = [$load[0]]; // Only 1-minute average
        $connections['timestamp'] = date('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        $connections['error'] = $e->getMessage();
        $connections['status'] = 'error';
    }
    
    return $connections;
}

function getSystemMetrics() {
    $metrics = [
        'disk_usage' => [],
        'uptime' => '',
        'processes' => 0
    ];
    
    try {
        // Get disk usage for main partition
        $disk_output = secureShellExec('disk_usage');
        if ($disk_output) {
            $lines = explode("\n", trim($disk_output));
            if (count($lines) >= 2) {
                $data = preg_split('/\s+/', $lines[1]);
                $metrics['disk_usage'] = [
                    'total' => $data[1] ?? 'unknown',
                    'used' => $data[2] ?? 'unknown',
                    'available' => $data[3] ?? 'unknown',
                    'percentage' => rtrim($data[4] ?? '0%', '%')
                ];
            }
        }
        
        // Get system uptime
        $uptime_output = secureShellExec('system_uptime');
        $metrics['uptime'] = trim($uptime_output ?: 'unknown');
        
        // Count running processes (limited info)
        $process_output = secureShellExec('process_count');
        $metrics['processes'] = intval($process_output) - 1; // Subtract header line
        
    } catch (Exception $e) {
        $metrics['error'] = $e->getMessage();
    }
    
    return $metrics;
}

$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'connections':
        echo json_encode(getWebServerConnections());
        break;
        
    case 'metrics':
        echo json_encode(getSystemMetrics());
        break;
        
    case 'full':
        echo json_encode([
            'connections' => getWebServerConnections(),
            'metrics' => getSystemMetrics()
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>