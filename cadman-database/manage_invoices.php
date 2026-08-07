<!DOCTYPE html>
<html>
<head>
    <title>Invoice Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        #fileList {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .file-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .file-item:hover {
            background: #f0f0f0;
        }
        
        .file-name {
            flex: 1;
            font-family: monospace;
        }
        
        .file-age {
            color: #666;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .file-link {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .file-link:hover {
            text-decoration: underline;
        }
        
        #status {
            margin-top: 15px;
            padding: 12px;
            border-radius: 5px;
            display: none;
        }
        
        #status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        #status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
    </style>
</head>
<body>
    <h1>📄 Invoice Management</h1>
    
    <div class="section">
        <h2>Temporary Invoice Files</h2>
        <p>These files are generated when you create invoices and stored temporarily.</p>
        
        <div>
            <button class="btn btn-primary" onclick="listFiles()">🔄 Refresh List</button>
            <button class="btn btn-warning" onclick="cleanupOld()">🗑️ Delete Old (24h+)</button>
            <button class="btn btn-danger" onclick="cleanupAll()">⚠️ Delete All</button>
        </div>
        
        <div id="status"></div>
        
        <div id="fileList">
            <p style="color: #999; text-align: center;">Click "Refresh List" to see files</p>
        </div>
    </div>
    
    <script>
        function showStatus(message, type = 'success') {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = type;
            
            setTimeout(() => {
                status.style.display = 'none';
            }, 5000);
        }
        
        function listFiles() {
            fetch('list_invoices.php')
                .then(response => response.json())
                .then(data => {
                    const fileList = document.getElementById('fileList');
                    
                    if (data.files && data.files.length > 0) {
                        fileList.innerHTML = data.files.map(file => `
                            <div class="file-item">
                                <span class="file-name">${file.name}</span>
                                <span class="file-age">${file.age}</span>
                                <a href="temp_invoices/${file.name}" target="_blank" class="file-link">View</a>
                            </div>
                        `).join('');
                        
                        showStatus(`Found ${data.files.length} invoice file(s) (${data.totalSize})`, 'success');
                    } else {
                        fileList.innerHTML = '<p style="color: #999; text-align: center;">No invoice files found</p>';
                        showStatus('No files found', 'success');
                    }
                })
                .catch(error => {
                    showStatus('Error loading files: ' + error.message, 'error');
                });
        }
        
        function cleanupOld() {
            if (!confirm('Delete all invoice files older than 24 hours?')) {
                return;
            }
            
            fetch('cleanup_invoices.php?hours=24')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatus(`Deleted ${data.deleted} old file(s). ${data.remaining} remaining.`, 'success');
                        listFiles();
                    } else {
                        showStatus('Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showStatus('Error: ' + error.message, 'error');
                });
        }
        
        function cleanupAll() {
            if (!confirm('⚠️ DELETE ALL INVOICE FILES? This cannot be undone!')) {
                return;
            }
            
            fetch('cleanup_invoices.php?all=true')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatus(`Deleted all ${data.deleted} file(s).`, 'success');
                        listFiles();
                    } else {
                        showStatus('Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showStatus('Error: ' + error.message, 'error');
                });
        }
        
        // Auto-load on page load
        listFiles();
    </script>
</body>
</html>
