<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verbose Logs Dashboard</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        select, input, button {
            padding: 8px 12px;
            border: 1px solid #444;
            background: #333;
            color: #fff;
            border-radius: 4px;
        }
        button {
            background: #0066cc;
            cursor: pointer;
        }
        button:hover {
            background: #0052a3;
        }
        .log-container {
            background: #000;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.4;
        }
        .log-line {
            margin-bottom: 5px;
            padding: 5px;
            border-left: 3px solid transparent;
        }
        .log-line.ERROR {
            border-color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
        }
        .log-line.SECURITY {
            border-color: #ff8800;
            background: rgba(255, 136, 0, 0.1);
        }
        .log-line.WARNING {
            border-color: #ffff44;
            background: rgba(255, 255, 68, 0.1);
        }
        .log-line.INFO {
            border-color: #44ff44;
            background: rgba(68, 255, 68, 0.05);
        }
        .log-line.DEBUG {
            border-color: #4444ff;
            background: rgba(68, 68, 255, 0.05);
        }
        .timestamp {
            color: #888;
        }
        .level {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            margin: 0 5px;
        }
        .level.ERROR {
            background: #ff4444;
            color: #fff;
        }
        .level.SECURITY {
            background: #ff8800;
            color: #fff;
        }
        .level.WARNING {
            background: #ffff44;
            color: #000;
        }
        .level.INFO {
            background: #44ff44;
            color: #000;
        }
        .level.DEBUG {
            background: #4444ff;
            color: #fff;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
        }
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Cadman Verbose Logs Dashboard</h1>
        <p>Comprehensive logging system - Data stored on 8TB drive</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-value" id="total-logs">-</div>
            <div>Total Logs Today</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" id="error-count">-</div>
            <div>Errors Today</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" id="security-events">-</div>
            <div>Security Events</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" id="disk-usage">-</div>
            <div>8TB Drive Usage</div>
        </div>
    </div>

    <div class="controls">
        <div class="control-group">
            <label>Category</label>
            <select id="category">
                <option value="">All Categories</option>
                <option value="general">General</option>
                <option value="security">Security</option>
                <option value="database">Database</option>
                <option value="modal">Modal</option>
                <option value="search">Search</option>
                <option value="admin">Admin</option>
                <option value="api">API</option>
                <option value="performance">Performance</option>
            </select>
        </div>
        
        <div class="control-group">
            <label>Level</label>
            <select id="level">
                <option value="">All Levels</option>
                <option value="DEBUG">Debug</option>
                <option value="INFO">Info</option>
                <option value="WARNING">Warning</option>
                <option value="ERROR">Error</option>
                <option value="SECURITY">Security</option>
            </select>
        </div>
        
        <div class="control-group">
            <label>Date</label>
            <input type="date" id="date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        
        <div class="control-group">
            <label>Lines</label>
            <select id="lines">
                <option value="100">Last 100</option>
                <option value="500">Last 500</option>
                <option value="1000">Last 1000</option>
                <option value="5000">Last 5000</option>
            </select>
        </div>
        
        <div class="control-group">
            <label>Search</label>
            <input type="text" id="search" placeholder="Search logs...">
        </div>
        
        <div class="control-group">
            <label>&nbsp;</label>
            <button onclick="loadLogs()">🔄 Refresh</button>
        </div>
        
        <div class="auto-refresh">
            <input type="checkbox" id="auto-refresh">
            <label>Auto-refresh (30s)</label>
        </div>
    </div>

    <div class="log-container" id="log-container">
        <p>Loading logs...</p>
    </div>

    <script>
        let autoRefreshInterval = null;
        
        function loadLogs() {
            const category = document.getElementById('category').value;
            const level = document.getElementById('level').value;
            const date = document.getElementById('date').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            
            const params = new URLSearchParams({
                category: category,
                level: level,
                date: date,
                lines: lines,
                search: search
            });
            
            fetch(`log_viewer_api.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    displayLogs(data.logs);
                    updateStats(data.stats);
                })
                .catch(error => {
                    document.getElementById('log-container').innerHTML = 
                        '<p style="color: #ff4444;">Error loading logs: ' + error + '</p>';
                });
        }
        
        function displayLogs(logs) {
            const container = document.getElementById('log-container');
            
            if (logs.length === 0) {
                container.innerHTML = '<p>No logs found for the selected criteria.</p>';
                return;
            }
            
            const html = logs.map(log => {
                const level = extractLevel(log);
                return `<div class="log-line ${level}">${escapeHtml(log)}</div>`;
            }).join('');
            
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight; // Auto-scroll to bottom
        }
        
        function extractLevel(logLine) {
            const levelMatch = logLine.match(/\\[(DEBUG|INFO|WARNING|ERROR|SECURITY)\\]/);
            return levelMatch ? levelMatch[1] : 'INFO';
        }
        
        function updateStats(stats) {
            document.getElementById('total-logs').textContent = stats.total_logs || '-';
            document.getElementById('error-count').textContent = stats.error_count || '-';
            document.getElementById('security-events').textContent = stats.security_events || '-';
            document.getElementById('disk-usage').textContent = stats.disk_usage || '-';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('auto-refresh');
            
            if (checkbox.checked) {
                autoRefreshInterval = setInterval(loadLogs, 30000); // 30 seconds
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
            }
        }
        
        // Event listeners
        document.getElementById('auto-refresh').addEventListener('change', toggleAutoRefresh);
        document.getElementById('search').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                loadLogs();
            }
        });
        
        // Load logs on page load
        loadLogs();
    </script>
</body>
</html>