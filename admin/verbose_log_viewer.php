<?php
// VerboseLogger Log Viewer
require_once __DIR__ . '/auth.php';
requireAdmin();

// Log directory from VerboseLogger
$log_directory = '/media/user0/backup/cadman_logs';

// Available log categories
$categories = [
    'DATABASE' => 'Database Operations',
    'SECURITY' => 'Security Events', 
    'SYSTEM' => 'System Operations',
    'ERROR' => 'Error Logs',
    'USER' => 'User Actions',
    'API' => 'API Requests',
    'PERFORMANCE' => 'Performance Metrics',
    'DEBUG' => 'Debug Information'
];

$selected_category = $_GET['category'] ?? 'SYSTEM';
$date_filter = $_GET['date'] ?? date('Y-m-d');

function getLogFiles($directory, $category, $date) {
    $files = [];
    if (is_dir($directory)) {
        $pattern = $directory . '/' . $category . '_' . str_replace('-', '', $date) . '*.log';
        $files = glob($pattern);
        // Also check for general date pattern
        $pattern2 = $directory . '/' . $category . '_*.log';
        $all_files = glob($pattern2);
        
        // Filter files by date in filename
        foreach ($all_files as $file) {
            if (strpos(basename($file), str_replace('-', '', $date)) !== false && !in_array($file, $files)) {
                $files[] = $file;
            }
        }
    }
    return $files;
}

function readLogFile($filepath, $lines = 100) {
    if (!file_exists($filepath)) {
        return "Log file not found: " . basename($filepath);
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        return "Unable to read log file: " . basename($filepath);
    }
    
    $lines_array = explode("\n", $content);
    $total_lines = count($lines_array);
    
    // Get last N lines if file is large
    if ($total_lines > $lines) {
        $lines_array = array_slice($lines_array, -$lines);
    }
    
    return [
        'content' => implode("\n", $lines_array),
        'total_lines' => $total_lines,
        'showing_lines' => count($lines_array)
    ];
}

$log_files = getLogFiles($log_directory, $selected_category, $date_filter);
$log_content = '';
$total_lines = 0;

if (!empty($log_files)) {
    foreach ($log_files as $file) {
        $result = readLogFile($file);
        if (is_array($result)) {
            $log_content .= "=== " . basename($file) . " (showing {$result['showing_lines']} of {$result['total_lines']} lines) ===\n";
            $log_content .= $result['content'] . "\n\n";
            $total_lines += $result['total_lines'];
        } else {
            $log_content .= $result . "\n\n";
        }
    }
} else {
    $log_content = "No log files found for category '$selected_category' on date '$date_filter'";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerboseLogger System Logs</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .log-viewer {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .log-controls {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #FFD700;
        }
        .log-display {
            background: #1e1e1e;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 600px;
            overflow-y: auto;
            border: 2px solid #333;
        }
        .log-stats {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .control-group {
            margin: 10px 0;
        }
        .control-group label {
            font-weight: bold;
            margin-right: 10px;
        }
        select, input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
        }
        .refresh-btn {
            background: #FFD700;
            color: #333;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .refresh-btn:hover {
            background: #e6c200;
        }
    </style>
</head>
<body>
    <div class="log-viewer">
        <div class="admin-header">
            <h1>🗂️ VerboseLogger System Logs</h1>
            <p>Real-time system monitoring and debugging logs from 8TB drive</p>
        </div>
        
        <div class="log-controls">
            <form method="GET">
                <div class="control-group">
                    <label for="category">Log Category:</label>
                    <select name="category" id="category">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $selected_category === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" value="<?= htmlspecialchars($date_filter) ?>">
                    
                    <button type="submit" class="refresh-btn">🔄 Refresh Logs</button>
                </div>
            </form>
            
            <div class="log-stats">
                📊 <strong>Log Statistics:</strong>
                Found <?= count($log_files) ?> log file(s) | 
                Total lines: <?= number_format($total_lines) ?> |
                Category: <?= htmlspecialchars($categories[$selected_category] ?? $selected_category) ?> |
                Date: <?= htmlspecialchars($date_filter) ?>
            </div>
        </div>
        
        <div class="log-display">
<?= htmlspecialchars($log_content) ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button onclick="window.close()" class="refresh-btn">🚪 Close Window</button>
            <button onclick="location.reload()" class="refresh-btn">🔄 Refresh</button>
            <button onclick="window.print()" class="refresh-btn">🖨️ Print Logs</button>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of logs
        const logDisplay = document.querySelector('.log-display');
        logDisplay.scrollTop = logDisplay.scrollHeight;
        
        // Auto-refresh every 30 seconds for real-time monitoring
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>