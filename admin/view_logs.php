<?php
require_once 'auth.php';
requireAdmin();

$logFile = '/var/log/collections_watcher.log';
$adminLogFile = '/var/log/admin_actions.log';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #8B008B, #FF69B4, #FFD700);
            min-height: 100vh;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
        }
        
        .logs-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logs-header {
            background: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logs-header h1 {
            color: #8B008B;
            margin: 0;
        }
        
        .header-controls {
            display: flex;
            gap: 10px;
        }
        
        .control-button {
            background: #8B008B;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .control-button:hover {
            background: #FF69B4;
        }
        
        .logs-content {
            background: #1e1e1e;
            color: #00ff00;
            border-radius: 0 0 15px 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            min-height: 500px;
            max-height: 80vh;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .log-tabs {
            background: white;
            padding: 10px 20px;
            border-radius: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
        }
        
        .log-tab {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .log-tab.active {
            background: #8B008B;
            color: white;
            border-color: #8B008B;
        }
        
        .log-entry {
            margin-bottom: 3px;
            word-wrap: break-word;
        }
        
        .log-timestamp {
            color: #888;
        }
        
        .log-error { color: #ff6b6b; }
        .log-success { color: #51cf66; }
        .log-warning { color: #ffd43b; }
        .log-info { color: #74c0fc; }
        
        .filter-controls {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-input {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-family: inherit;
            font-size: 11px;
        }
        
        .filter-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .log-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-size: 11px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="logs-container">
        <div class="logs-header">
            <h1>📋 System Logs</h1>
            <div class="header-controls">
                <button class="control-button" onclick="refreshLogs()">🔄 Refresh</button>
                <button class="control-button" onclick="downloadLogs()">💾 Download</button>
                <button class="control-button" onclick="clearLogs()">🗑️ Clear</button>
                <a href="index.php" class="control-button" style="text-decoration: none;">🏠 Back</a>
            </div>
        </div>
        
        <div class="log-tabs">
            <div class="log-tab active" onclick="switchTab('watcher')">File Watcher</div>
            <div class="log-tab" onclick="switchTab('admin')">Admin Actions</div>
            <div class="log-tab" onclick="switchTab('system')">System</div>
        </div>
        
        <div class="logs-content">
            <div class="filter-controls">
                <input type="text" class="filter-input" id="filterText" placeholder="Filter logs..." onkeyup="filterLogs()">
                <select class="filter-input" id="filterLevel" onchange="filterLogs()">
                    <option value="">All Levels</option>
                    <option value="ERROR">Errors</option>
                    <option value="SUCCESS">Success</option>
                    <option value="WARNING">Warnings</option>
                    <option value="INFO">Info</option>
                </select>
                <input type="number" class="filter-input" id="maxLines" placeholder="Max lines" value="1000" onchange="filterLogs()">
            </div>
            
            <div class="log-stats" id="logStats">
                Loading logs...
            </div>
            
            <div id="logContent">
                <!-- Logs will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        let currentTab = 'watcher';
        let allLogs = {};
        
        // Load logs on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshLogs();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshLogs, 30000);
        });
        
        // Switch between log tabs
        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab appearance
            document.querySelectorAll('.log-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            // Display logs for selected tab
            displayLogs();
        }
        
        // Refresh all logs
        async function refreshLogs() {
            try {
                // Load file watcher logs
                const watcherResponse = await fetch('log_reader.php?file=watcher');
                allLogs.watcher = await watcherResponse.text();
                
                // Load admin logs
                const adminResponse = await fetch('log_reader.php?file=admin');
                allLogs.admin = await adminResponse.text();
                
                // Load system logs (simulated)
                allLogs.system = generateSystemLogs();
                
                displayLogs();
                
            } catch (error) {
                document.getElementById('logContent').innerHTML = 
                    '<div class="log-error">Error loading logs: ' + error.message + '</div>';
            }
        }
        
        // Display logs for current tab
        function displayLogs() {
            const content = allLogs[currentTab] || 'No logs available';
            const lines = content.split('\n').filter(line => line.trim());
            
            // Update stats
            updateLogStats(lines);
            
            // Apply filters
            filterLogs();
        }
        
        // Filter logs based on current filters
        function filterLogs() {
            const filterText = document.getElementById('filterText').value.toLowerCase();
            const filterLevel = document.getElementById('filterLevel').value;
            const maxLines = parseInt(document.getElementById('maxLines').value) || 1000;
            
            const content = allLogs[currentTab] || '';
            let lines = content.split('\n').filter(line => line.trim());
            
            // Apply text filter
            if (filterText) {
                lines = lines.filter(line => line.toLowerCase().includes(filterText));
            }
            
            // Apply level filter
            if (filterLevel) {
                lines = lines.filter(line => line.toUpperCase().includes(filterLevel));
            }
            
            // Limit number of lines
            lines = lines.slice(-maxLines);
            
            // Format and display
            const formattedLines = lines.map(line => formatLogLine(line)).join('\n');
            document.getElementById('logContent').innerHTML = formattedLines || '<div class="log-info">No matching log entries</div>';
            
            // Update stats
            updateLogStats(lines);
            
            // Scroll to bottom
            const logContent = document.getElementById('logContent');
            logContent.scrollTop = logContent.scrollHeight;
        }
        
        // Format individual log line
        function formatLogLine(line) {
            if (!line.trim()) return '';
            
            let className = 'log-entry';
            
            // Detect log level and apply styling
            if (line.includes('ERROR') || line.includes('Error') || line.includes('Failed')) {
                className += ' log-error';
            } else if (line.includes('SUCCESS') || line.includes('Success') || line.includes('completed')) {
                className += ' log-success';
            } else if (line.includes('WARNING') || line.includes('Warning')) {
                className += ' log-warning';
            } else if (line.includes('INFO') || line.includes('Starting') || line.includes('Loaded')) {
                className += ' log-info';
            }
            
            // Highlight timestamps
            line = line.replace(/\[([^\]]+)\]/g, '<span class="log-timestamp">[$1]</span>');
            
            return '<div class="' + className + '">' + escapeHtml(line) + '</div>';
        }
        
        // Update log statistics
        function updateLogStats(lines) {
            const total = lines.length;
            const errors = lines.filter(line => line.includes('ERROR') || line.includes('Error')).length;
            const warnings = lines.filter(line => line.includes('WARNING') || line.includes('Warning')).length;
            const success = lines.filter(line => line.includes('SUCCESS') || line.includes('Success')).length;
            
            document.getElementById('logStats').textContent = 
                `Total: ${total} | Errors: ${errors} | Warnings: ${warnings} | Success: ${success}`;
        }
        
        // Generate system logs (simulated)
        function generateSystemLogs() {
            const logs = [
                '[' + new Date().toISOString() + '] System initialized',
                '[' + new Date().toISOString() + '] PHP version: ' + '<?php echo PHP_VERSION; ?>',
                '[' + new Date().toISOString() + '] Collections processor: Active',
                '[' + new Date().toISOString() + '] File watcher: Monitoring',
                '[' + new Date().toISOString() + '] Admin portal: Secured'
            ];
            return logs.join('\n');
        }
        
        // Download logs
        function downloadLogs() {
            const content = allLogs[currentTab] || '';
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = currentTab + '-logs-' + new Date().toISOString().slice(0, 10) + '.txt';
            a.click();
            URL.revokeObjectURL(url);
        }
        
        // Clear logs (admin action)
        async function clearLogs() {
            if (confirm('Are you sure you want to clear the ' + currentTab + ' logs?')) {
                try {
                    const response = await fetch('log_reader.php?action=clear&file=' + currentTab, {method: 'POST'});
                    const result = await response.text();
                    alert(result);
                    refreshLogs();
                } catch (error) {
                    alert('Error clearing logs: ' + error.message);
                }
            }
        }
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
