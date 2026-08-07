<?php
// Simple server status test - no authentication needed
header('Content-Type: application/json');

function getWebServerConnections() {
    $connections = [
        'listening_ports' => [],
        'established_connections' => [],
        'total_connections' => 0,
        'port_80' => 0,
        'port_443' => 0,
        'status' => 'unknown',
        'server_load' => sys_getloadavg(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Get listening ports (without -p flag to avoid needing sudo)
        $listening_output = shell_exec("netstat -tln 2>/dev/null | grep -E ':(80|443) '");
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
                            'address' => $local_address,
                            'state' => 'LISTEN',
                            'protocol' => (strpos($local_address, ':::') === 0) ? 'IPv6' : 'IPv4'
                        ];
                    }
                    
                    if (strpos($local_address, ':443') !== false) {
                        $connections['port_443']++;
                        $connections['listening_ports'][] = [
                            'port' => 443,
                            'address' => $local_address,
                            'state' => 'LISTEN',
                            'protocol' => (strpos($local_address, ':::') === 0) ? 'IPv6' : 'IPv4'
                        ];
                    }
                }
            }
        }
        
        $connections['total_connections'] = count($connections['listening_ports']);
        
        // Determine status
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
        'processes' => 0,
        'cpu_cores' => 1,
        'active_users' => 0,
        'last_backup' => null
    ];
    
    try {
        // Get CPU core count
        $cpu_info = shell_exec('nproc 2>/dev/null');
        $metrics['cpu_cores'] = intval(trim($cpu_info)) ?: 1;
        
        // Get disk usage for main partition
        $disk_output = shell_exec('df -h / 2>/dev/null');
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
        $uptime_output = shell_exec('uptime -p 2>/dev/null');
        $metrics['uptime'] = trim($uptime_output ?: 'unknown');
        
        // Get active users (current established connections to web server)
        $active_conn_cmd = "netstat -tn 2>/dev/null | grep -E ':(80|443) ' | grep ESTABLISHED | awk '{print \$5}' | cut -d: -f1 | sort -u | wc -l";
        $active_users_output = shell_exec($active_conn_cmd);
        $metrics['active_users'] = intval(trim($active_users_output)) ?: 0;
        
        // Get last system backup timestamp from local file (backup drive is unmounted)
        $timestamp_file = '/var/log/last_system_backup.json';
        if (file_exists($timestamp_file)) {
            $backup_data = json_decode(file_get_contents($timestamp_file), true);
            if ($backup_data && isset($backup_data['timestamp'])) {
                $backup_time = intval($backup_data['timestamp']);
                $now = time();
                $diff = $now - $backup_time;
                
                if ($diff < 3600) {
                    $time_ago = round($diff / 60) . ' minutes ago';
                } elseif ($diff < 86400) {
                    $time_ago = round($diff / 3600) . ' hours ago';
                } else {
                    $days = floor($diff / 86400);
                    $time_ago = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                }
                
                $metrics['last_backup'] = [
                    'timestamp' => $backup_time,
                    'date' => $backup_data['date'],
                    'time_ago' => $time_ago,
                    'size' => $backup_data['backup_size'] ?? 'unknown'
                ];
            }
        }
        
    } catch (Exception $e) {
        $metrics['error'] = $e->getMessage();
    }
    
    return $metrics;
}

$action = $_GET['action'] ?? 'connections';

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