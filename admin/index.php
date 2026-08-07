<?php
// Security headers for admin pages
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Content-Type: text/html; charset=UTF-8');

require_once 'auth.php';
require_once __DIR__ . '/../includes/SessionManager.php';
requireAdmin();

$sessionManager = SessionManager::getInstance();

// Handle session extension
if ($_POST['extend_session'] ?? false) {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    $_SESSION['session_start'] = time(); // Reset session start time
    logAdminAction('SESSION_EXTENDED');
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Log admin access
logAdminAction('ADMIN_PORTAL_ACCESS');

// Read metals cron sync status
$metalsSyncStatus = null;
$syncStatusFile = __DIR__ . '/../cache/metals_sync_status.json';
if (file_exists($syncStatusFile)) {
    $metalsSyncStatus = json_decode(file_get_contents($syncStatusFile), true);
}

function getClientHistoryFeed($clientId, $limit = 50) {
    $pdo = getViewerConnection();

    $clientInfoStmt = $pdo->prepare(<<<SQL
        SELECT customer_code
        FROM clients
        WHERE client_id = :client_id
        LIMIT 1
SQL);
    $clientInfoStmt->execute([':client_id' => (int) $clientId]);
    $clientInfo = $clientInfoStmt->fetch();

    $customerCode = trim((string) ($clientInfo['customer_code'] ?? ''));
    $timeline = [];

    $liveStmt = $pdo->prepare(<<<SQL
        SELECT
            o.order_id AS record_id,
            o.order_number AS reference,
            o.order_date AS posted_at,
            COALESCE(o.status, 'pending') AS state,
            o.total_amount AS amount,
            COALESCE(o.currency, 'CAD') AS currency,
            COALESCE(o.tracking_number, '') AS tracking_ref,
            'current' AS bucket
        FROM orders o
        WHERE o.client_id = :client_id
        ORDER BY o.order_date DESC, o.order_id DESC
SQL);
    $liveStmt->execute([':client_id' => (int) $clientId]);
    foreach ($liveStmt->fetchAll() as $row) {
        $timeline[] = $row;
    }

    if ($customerCode !== '') {
        $legacyStmt = $pdo->prepare(<<<SQL
            SELECT
                MIN(sh.sale_id) AS record_id,
                sh.invoice_number AS reference,
                MIN(sh.transaction_date) AS posted_at,
                'completed' AS state,
                SUM(sh.amount) AS amount,
                'CAD' AS currency,
                '' AS tracking_ref,
                'legacy' AS bucket
            FROM sales_history sh
            WHERE sh.client_id = :client_id
               OR (sh.client_id IS NULL AND sh.customer_code = :customer_code)
            GROUP BY sh.invoice_number, sh.customer_code
            ORDER BY MIN(sh.transaction_date) DESC, MIN(sh.sale_id) DESC
SQL);
        $legacyStmt->execute([
            ':client_id' => (int) $clientId,
            ':customer_code' => $customerCode,
        ]);

        foreach ($legacyStmt->fetchAll() as $row) {
            $timeline[] = $row;
        }
    }

    usort($timeline, function ($a, $b) {
        $dateA = (string) ($a['posted_at'] ?? '');
        $dateB = (string) ($b['posted_at'] ?? '');

        if ($dateA !== $dateB) {
            return strcmp($dateB, $dateA);
        }

        return ((int) ($b['record_id'] ?? 0)) <=> ((int) ($a['record_id'] ?? 0));
    });

    return array_slice($timeline, 0, (int) $limit);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Cadman Manufacturing</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <script src="session-manager.js"></script>
    <script src="carousel-manager.js"></script>
    <?php echo renderSessionScript(); ?>
    <style>
        /* Additional page-specific styles can go here if needed */
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-nav">
                <div class="admin-user-info">
                    <span>🛡️ Admin Portal</span>
                    <span>👤 <?php echo htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['username'] ?? 'Unknown Admin'); ?></span>
                    <span>🕒 <?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                <div class="admin-controls">
                    <a href="../index.php">🏠 Main Site</a>
                    <a href="?logout=1">🚪 Logout</a>
                </div>
            </div>
            
            <h1 style="color: #333; margin: 0;">Collections Management System</h1>
            <p style="color: #666; margin: 10px 0 0 0;">Administrative controls for jewelry collections</p>
        </div>
        
        <!-- Precious Metals Price Dashboard -->
        <div class="admin-card" style="margin-bottom: 20px; padding: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #333;">💎 Precious Metals Pricing</h3>
            <div style="display: flex; gap: 30px; justify-content: center; align-items: flex-start; flex-wrap: wrap;">
                <!-- Gold Price Widget (Your Custom API) -->
                <div style="border: 2px solid #FFD700; width: 280px; padding: 15px; border-radius: 10px; background: linear-gradient(135deg, #fff9e6, #ffffff);">
                    <div style="text-align: center; font-weight: bold; font-size: 18px; color: #333; margin-bottom: 10px; padding: 8px; background: #FFD700; border-radius: 5px;">
                        🥇 Gold Price (CAD)
                    </div>
                    <div id="admin-gold-price" style="text-align: center; font-size: 32px; font-weight: bold; color: #FFD700; margin: 15px 0;">
                        Loading...
                    </div>
                    <div id="admin-gold-change" style="text-align: center; font-size: 14px; margin-bottom: 10px;">
                        <span id="admin-gold-change-value" style="font-weight: bold;"></span>
                        <span id="admin-gold-change-percent"></span>
                    </div>
                    <div style="font-size: 11px; color: #888; text-align: center; margin-top: 10px;">
                        <div id="admin-gold-updated"></div>
                        <div>Per troy oz • Updates 3x daily</div>
                    </div>
                </div>
                
                <!-- Silver Price Widget (GoldAPI.io) -->
                <div style="border: 2px solid #C0C0C0; width: 280px; padding: 15px; border-radius: 10px; background: linear-gradient(135deg, #f5f5f5, #ffffff);">
                    <div style="text-align: center; font-weight: bold; font-size: 18px; color: #333; margin-bottom: 10px; padding: 8px; background: #C0C0C0; border-radius: 5px;">
                        🥈 Silver Price (CAD)
                    </div>
                    <div id="admin-silver-price" style="text-align: center; font-size: 32px; font-weight: bold; color: #666; margin: 15px 0;">
                        Loading...
                    </div>
                    <div id="admin-silver-change" style="text-align: center; font-size: 14px; margin-bottom: 10px;">
                        <span id="admin-silver-change-value" style="font-weight: bold;"></span>
                        <span id="admin-silver-change-percent"></span>
                    </div>
                    <div style="font-size: 11px; color: #888; text-align: center; margin-top: 10px;">
                        <div id="admin-silver-updated"></div>
                        <div>Per troy oz • Live market data</div>
                    </div>
                </div>
            </div>
            <div style="margin-top: 15px; font-size: 12px; color: #888; text-align: center;">
                <em>Real-time precious metal prices for jewelry manufacturing cost estimates</em>
            </div>
            <!-- Cron sync status -->
            <div style="margin-top: 12px; text-align: center; font-size: 12px;">
                <?php if ($metalsSyncStatus === null): ?>
                    <span style="color: #999;">⚠ No sync record found — cron has not run yet or status file is missing.</span>
                <?php else:
                    $lastRun = $metalsSyncStatus['last_run'];
                    $success = !empty($metalsSyncStatus['success']);
                    $runTs   = strtotime($lastRun);
                    $hoursAgo = round((time() - $runTs) / 3600, 1);
                    // Flag as stale if last run was over 26 hours ago (missed a day)
                    $stale = ($hoursAgo > 26);
                    if ($success && !$stale): ?>
                        <span style="color: #28a745; font-weight: bold;">✔ Last sync: <?= htmlspecialchars($lastRun) ?> (<?= $hoursAgo ?>h ago)</span>
                    <?php elseif ($success && $stale): ?>
                        <span style="color: #fd7e14; font-weight: bold;">⚠ Last sync: <?= htmlspecialchars($lastRun) ?> (<?= $hoursAgo ?>h ago — possibly missed)</span>
                    <?php else: ?>
                        <span style="color: #dc3545; font-weight: bold;">✘ Last sync FAILED: <?= htmlspecialchars($lastRun) ?></span><br>
                        <span style="color: #dc3545;">Error: <?= htmlspecialchars($metalsSyncStatus['error'] ?? 'Unknown error') ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        // Fetch gold and silver prices for admin dashboard
        async function fetchAdminMetalPrices() {
            try {
                // Fetch Canadian gold price from your API
                const goldResponse = await fetch('../gold_price_api.php');
                const goldData = await goldResponse.json();
                
                if (goldData.success) {
                    const price = parseFloat(goldData.current_price.replace(/,/g, ''));
                    document.getElementById('admin-gold-price').textContent = `CA$${price.toLocaleString('en-CA', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    
                    const changeAmount = parseFloat(goldData.change_amount.replace(/,/g, ''));
                    const changePercent = parseFloat(goldData.change_percent.replace(/,/g, ''));
                    const isPositive = changeAmount >= 0;
                    
                    const changeColor = isPositive ? '#28a745' : '#dc3545';
                    const changeSymbol = isPositive ? '▲' : '▼';
                    
                    document.getElementById('admin-gold-change-value').textContent = `${changeSymbol} CA$${Math.abs(changeAmount).toFixed(2)}`;
                    document.getElementById('admin-gold-change-value').style.color = changeColor;
                    document.getElementById('admin-gold-change-percent').textContent = `(${isPositive ? '+' : ''}${changePercent.toFixed(2)}%)`;
                    document.getElementById('admin-gold-change-percent').style.color = changeColor;
                    
                    document.getElementById('admin-gold-updated').textContent = `Updated: ${new Date().toLocaleTimeString()}`;
                }
                
                // Fetch Canadian silver price from your API
                const silverResponse = await fetch('../silver_price_api.php');
                const silverData = await silverResponse.json();
                
                if (silverData.success) {
                    const price = parseFloat(silverData.current_price.replace(/,/g, ''));
                    document.getElementById('admin-silver-price').textContent = `CA$${price.toLocaleString('en-CA', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    
                    const changeAmount = parseFloat(silverData.change_amount.replace(/,/g, ''));
                    const changePercent = parseFloat(silverData.change_percent.replace(/,/g, ''));
                    const isPositive = changeAmount >= 0;
                    
                    const changeColor = isPositive ? '#28a745' : '#dc3545';
                    const changeSymbol = isPositive ? '▲' : '▼';
                    
                    document.getElementById('admin-silver-change-value').textContent = `${changeSymbol} CA$${Math.abs(changeAmount).toFixed(2)}`;
                    document.getElementById('admin-silver-change-value').style.color = changeColor;
                    document.getElementById('admin-silver-change-percent').textContent = `(${isPositive ? '+' : ''}${changePercent.toFixed(2)}%)`;
                    document.getElementById('admin-silver-change-percent').style.color = changeColor;
                    
                    document.getElementById('admin-silver-updated').textContent = `Updated: ${new Date().toLocaleTimeString()}`;
                }
                
            } catch (error) {
                console.error('Error fetching metal prices:', error);
                document.getElementById('admin-gold-price').textContent = 'Error';
                document.getElementById('admin-silver-price').textContent = 'Error';
            }
        }
        
        // Load prices on page load
        document.addEventListener('DOMContentLoaded', fetchAdminMetalPrices);
        </script>
        
        <div class="admin-grid">
            <div class="admin-card">
                <div class="card-icon">💰</div>
                <div class="card-title">Product Database Management</div>
                <div class="card-description">
                    Manage product pricing, labor costs, stone costs, and metal specifications. 
                    Update calculations and pricing for all product variants.
                </div>
                <div class="card-actions">
                    <a href="manage_pricing.php" class="action-button">🚀 Manage Pricing</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">🔧</div>
                <div class="card-title">System Management</div>
                <div class="card-description">
                    Monitor web server health, active users, system load percentage, 
                    disk usage, and backup status. View comprehensive system logs.
                </div>
                
                <!-- Server Status Display -->
                <div class="system-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; font-size: 12px;">
                    <div class="status-item">
                        <div style="font-weight: bold; margin-bottom: 5px;">🌐 Web Server</div>
                        <div id="serverStatus" style="color: #666;">Checking...</div>
                        <div id="activeUsers" style="color: #888; font-size: 11px;">-</div>
                    </div>
                    <div class="status-item">
                        <div style="font-weight: bold; margin-bottom: 5px;">💾 Disk Usage</div>
                        <div id="diskUsage" style="color: #666;">Loading...</div>
                        <div id="diskPercentage" style="color: #888; font-size: 11px;">-</div>
                    </div>
                    <div class="status-item">
                        <div style="font-weight: bold; margin-bottom: 5px;">⚡ System Load</div>
                        <div id="systemLoad" style="color: #666;">Loading...</div>
                        <div id="systemUptime" style="color: #888; font-size: 11px;">-</div>
                    </div>
                    <div class="status-item">
                        <div style="font-weight: bold; margin-bottom: 5px;">💾 Last Backup</div>
                        <div id="lastBackup" style="color: #666;">Loading...</div>
                        <div id="backupDetails" style="color: #888; font-size: 11px;">-</div>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="#" onclick="viewVerboseLogs()" class="action-button">📋 View System Logs</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">📁</div>
                <div class="card-title">File Management</div>
                <div class="card-description">
                    Upload images to collections, organize files, and manage 
                    directory structures across all collections.
                </div>
                <div class="card-actions">
                    <a href="file_upload.php" class="action-button">📤 Upload Files</a>
                    <a href="#" onclick="browseCollections()" class="action-button secondary">🔍 Browse Files</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">📊</div>
                <div class="card-title">Reports & Analytics</div>
                <div class="card-description">
                    View detailed reports on collection status, processing history, 
                    and system performance metrics.
                </div>
                <div class="card-actions">
                    <a href="#" onclick="generateReport()" class="action-button">📈 Generate Report</a>
                    <a href="#" onclick="exportData()" class="action-button secondary">💾 Export Data</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">🗺️</div>
                <div class="card-title">Retailer Geocoding</div>
                <div class="card-description">
                    Manage retailer locations: add new retailers to the database and review/update existing retailer 
                    coordinates as needed. All current retailers appear to have valid geocoding.
                </div>
                <div class="card-actions">
                    <a href="retailer_geocoding.php" class="action-button">🗺️ Start Geocoding</a>
                    <a href="add_retailer.php" class="action-button secondary">➕ Add Retailer</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">🏪</div>
                <div class="card-title">Retailer Management</div>
                <div class="card-description">
                    Complete retailer management system: add, edit, delete retailers and manage 
                    all retailer information. View all retailers with comprehensive search and filtering.
                </div>
                <div class="card-actions">
                    <a href="retailer_management.php" class="action-button">🏪 Manage All Retailers</a>
                    <a href="retailer_management.php" class="action-button secondary">🗑️ Delete Retailers</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">🛡️</div>
                <div class="card-title">Security Dashboard</div>
                <div class="card-description">
                    Monitor authentication security, SSL/HTTPS encryption, two-factor authentication, and system security status. 
                    Enterprise-level security with encrypted admin access and comprehensive protection.
                </div>
                <div class="card-actions">
                    <a href="security_status.php" class="action-button">🔒 Security Status</a>
                    <a href="encryption_status.php" class="action-button">🛡️ Encryption Status</a>
                    <a href="2fa_setup.php" class="action-button">🔐 Two-Factor Auth</a>
                    <a href="view_logs.php" class="action-button secondary">📋 Security Logs</a>
                    <a href="password_reset.php" class="action-button secondary">🔑 Reset Password</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">🎠</div>
                <div class="card-title">Carousel Management</div>
                <div class="card-description">
                    Configure the main site carousel to display products from specific categories. 
                    Select from available product collections and set active carousel content.
                </div>
                <div class="card-actions">
                    <button class="action-button" onclick="openCarouselManager()">🎠 Manage Carousel</button>
                    <button class="action-button secondary" onclick="previewCarousel()">👁️ Preview Current</button>
                </div>
                
                <!-- Carousel Management Modal -->
                <div id="carouselModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h2>🎠 Carousel Management</h2>
                            <span class="close" onclick="closeCarouselManager()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="carouselCategorySelect"><strong>Select Product Category:</strong></label>
                                <select id="carouselCategorySelect" onchange="loadCarouselPreview()">
                                    <option value="">Loading categories...</option>
                                </select>
                            </div>
                            
                            <div id="carouselPreview" style="display: none;">
                                <h3>Preview: <span id="previewTitle"></span></h3>
                                <div id="previewCount" style="margin-bottom: 15px; color: #666;"></div>
                                <div id="previewGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                                    <!-- Preview items will load here -->
                                </div>
                            </div>
                            
                            <div class="form-actions" style="margin-top: 20px;">
                                <button id="setCarouselBtn" class="action-button success" onclick="setCarouselCategory()" disabled>
                                    🎠 Set as Active Carousel
                                </button>
                                <button class="action-button secondary" onclick="clearCarousel()">
                                    🧹 Clear Carousel
                                </button>
                                <button class="action-button" onclick="closeCarouselManager()">
                                    ❌ Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">📧</div>
                <div class="card-title">Mass Email System</div>
                <div class="card-description">
                    Token-certified client communication system with professional HTML templates, 
                    validation, and comprehensive logging. Send newsletters, promotions, and announcements.
                </div>
                <div class="card-actions">
                    <a href="mass_email.php" class="action-button">📧 Compose Email</a>
                    <a href="mass_email.php" class="action-button secondary">📊 Email Analytics</a>
                </div>
                <div class="security-badge" style="background: #d4edda; color: #155724; padding: 8px; border-radius: 4px; margin-top: 10px; font-size: 12px;">
                    🔒 <strong>Token-Certified:</strong> CSRF protection, rate limiting (500/hour), and audit logging
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-icon">💰</div>
                <div class="card-title">AR12 Pricing Database</div>
                <div class="card-description">
                    Access the complete product pricing database with 15,234+ metal variants. 
                    Search items, view pricing calculations, and manage product costs with live gold price updates.
                </div>
                <div class="card-actions">
                    <a href="../cadman-database/" class="action-button">💰 Open Pricing Database</a>
                </div>
            </div>
        </div>
        
        <div class="admin-card" style="margin-top: 20px;">
            <div class="card-title">System Overview</div>
            <div id="systemStats" class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number" id="totalCollections">-</div>
                    <div class="stat-label">Collections</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="totalItems">-</div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="readyItems">-</div>
                    <div class="stat-label">Ready Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="completionRate">-</div>
                    <div class="stat-label">Completion %</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Load system statistics
        async function loadSystemStats() {
            try {
                const response = await fetch('universal_collection_processor.php?action=status');
                const data = await response.json();
                
                let totalCollections = Object.keys(data).length;
                let totalItems = 0;
                let readyItems = 0;
                
                Object.values(data).forEach(collection => {
                    totalItems += collection.totals.items;
                    readyItems += collection.totals.ready;
                });
                
                const completionRate = totalItems > 0 ? Math.round((readyItems / totalItems) * 100) : 0;
                
                document.getElementById('totalCollections').textContent = totalCollections;
                document.getElementById('totalItems').textContent = totalItems;
                document.getElementById('readyItems').textContent = readyItems;
                document.getElementById('completionRate').textContent = completionRate + '%';
                
            } catch (error) {
                console.error('Error loading system stats:', error);
            }
        }        
        function debugSystemStatus() {
            console.log('=== DEBUG: System Status ===');
            console.log('serverStatus element:', document.getElementById('serverStatus'));
            console.log('diskUsage element:', document.getElementById('diskUsage'));
            console.log('systemLoad element:', document.getElementById('systemLoad'));
            console.log('watcherStatus element:', document.getElementById('watcherStatus'));
            
            // Test API directly
            fetch('server_status_api.php?action=connections')
                .then(response => {
                    console.log('API Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('API Response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('API Response JSON:', data);
                    } catch (e) {
                        console.error('JSON Parse error:', e);
                    }
                })
                .catch(error => console.error('API Fetch error:', error));
        }        
        // System management functions
        async function checkWatcherStatus() {
            try {
                const response = await fetch('../manage_watcher_api.php?action=status');
                const result = await response.text();
                
                // Update watcher status display
                const statusElement = document.getElementById('watcherStatus');
                const detailsElement = document.getElementById('watcherDetails');
                
                if (result.includes('Active: active') || result.includes('running')) {
                    statusElement.textContent = '✅ Running';
                    statusElement.style.color = '#28a745';
                    detailsElement.textContent = 'Service active';
                } else if (result.includes('inactive') || result.includes('stopped')) {
                    statusElement.textContent = '⏹️ Stopped';
                    statusElement.style.color = '#dc3545';
                    detailsElement.textContent = 'Service inactive';
                } else {
                    statusElement.textContent = '❓ Unknown';
                    statusElement.style.color = '#ffc107';
                    detailsElement.textContent = 'Status unclear';
                }
                
                alert('Watcher Status:\n' + result);
            } catch (error) {
                document.getElementById('watcherStatus').textContent = '❌ Error';
                document.getElementById('watcherStatus').style.color = '#dc3545';
                document.getElementById('watcherDetails').textContent = 'Check failed';
                alert('Error checking watcher status: ' + error.message);
            }
        }
        
        async function checkServerConnections() {
            try {
                const response = await fetch('server_status_api.php?action=connections');
                const data = await response.json();
                
                let statusText = `Web Server Connections Report:\n\n`;
                statusText += `Overall Status: ${data.status.toUpperCase()}\n`;
                statusText += `Total Connections: ${data.total_connections}\n`;
                statusText += `Port 80 (HTTP): ${data.port_80} connections\n`;
                statusText += `Port 443 (HTTPS): ${data.port_443} connections\n\n`;
                
                if (data.listening_ports.length > 0) {
                    statusText += `Listening Ports:\n`;
                    data.listening_ports.forEach(port => {
                        statusText += `  ${port.address} (${port.state})\n`;
                    });
                    statusText += '\n';
                }
                
                if (data.established_connections.length > 0) {
                    statusText += `Active Connections:\n`;
                    data.established_connections.slice(0, 10).forEach(conn => {
                        statusText += `  ${conn.local} → ${conn.remote}\n`;
                    });
                    if (data.established_connections.length > 10) {
                        statusText += `  ... and ${data.established_connections.length - 10} more\n`;
                    }
                }
                
                alert(statusText);
                
            } catch (error) {
                alert('Error checking server connections: ' + error.message);
            }
        }
        
        async function refreshSystemStatus() {
            try {
                // Set loading indicators
                document.getElementById('serverStatus').textContent = 'Loading...';
                document.getElementById('diskUsage').textContent = 'Loading...';
                document.getElementById('systemLoad').textContent = 'Loading...';
                document.getElementById('lastBackup').textContent = 'Loading...';
                
                // Update server connections
                const connResponse = await fetch('test_status_api.php?action=connections');
                if (!connResponse.ok) {
                    throw new Error(`HTTP ${connResponse.status}: ${connResponse.statusText}`);
                }
                const connData = await connResponse.json();
                
                // Update system metrics
                const metricsResponse = await fetch('test_status_api.php?action=metrics');
                if (!metricsResponse.ok) {
                    console.warn('Metrics API failed, continuing without metrics');
                }
                const metricsData = metricsResponse.ok ? await metricsResponse.json() : {};
                
                // Update server status display
                const serverStatus = document.getElementById('serverStatus');
                const activeUsers = document.getElementById('activeUsers');
                
                switch (connData.status) {
                    case 'healthy':
                        serverStatus.textContent = '✅ Healthy';
                        serverStatus.style.color = '#28a745';
                        break;
                    case 'partial':
                        serverStatus.textContent = '⚠️ Partial';
                        serverStatus.style.color = '#ffc107';
                        break;
                    case 'down':
                        serverStatus.textContent = '❌ Down';
                        serverStatus.style.color = '#dc3545';
                        break;
                    default:
                        serverStatus.textContent = '❓ Unknown';
                        serverStatus.style.color = '#6c757d';
                }
                
                // Show active users
                if (metricsData.active_users !== undefined) {
                    const userCount = metricsData.active_users;
                    activeUsers.textContent = `${userCount} active user${userCount !== 1 ? 's' : ''}`;
                } else {
                    activeUsers.textContent = 'User count unavailable';
                }
                
                // Update disk usage
                const diskUsage = document.getElementById('diskUsage');
                const diskPercentage = document.getElementById('diskPercentage');
                
                if (metricsData.disk_usage) {
                    diskUsage.textContent = `${metricsData.disk_usage.used} / ${metricsData.disk_usage.total}`;
                    const percentage = parseInt(metricsData.disk_usage.percentage);
                    diskPercentage.textContent = `${percentage}% used`;
                    
                    if (percentage > 90) {
                        diskUsage.style.color = '#dc3545';
                    } else if (percentage > 75) {
                        diskUsage.style.color = '#ffc107';
                    } else {
                        diskUsage.style.color = '#28a745';
                    }
                } else {
                    diskUsage.textContent = 'Unavailable';
                    diskUsage.style.color = '#6c757d';
                    diskPercentage.textContent = 'No data';
                }
                
                // Update system load as percentage
                const systemLoad = document.getElementById('systemLoad');
                const systemUptime = document.getElementById('systemUptime');
                
                if (connData.server_load && metricsData.cpu_cores) {
                    const load = connData.server_load[0];
                    const cores = metricsData.cpu_cores;
                    const loadPercent = ((load / cores) * 100).toFixed(1);
                    systemLoad.textContent = `${loadPercent}%`;
                    
                    if (loadPercent > 90) {
                        systemLoad.style.color = '#dc3545';
                    } else if (loadPercent > 70) {
                        systemLoad.style.color = '#ffc107';
                    } else {
                        systemLoad.style.color = '#28a745';
                    }
                } else {
                    systemLoad.textContent = 'Unknown';
                    systemLoad.style.color = '#6c757d';
                }
                
                if (metricsData.uptime) {
                    systemUptime.textContent = metricsData.uptime;
                } else {
                    systemUptime.textContent = 'Uptime: Unknown';
                }
                
                // Update last backup info
                const lastBackup = document.getElementById('lastBackup');
                const backupDetails = document.getElementById('backupDetails');
                
                if (metricsData.last_backup) {
                    lastBackup.textContent = metricsData.last_backup.time_ago;
                    backupDetails.textContent = metricsData.last_backup.date;
                    
                    // Color code based on age
                    const hours = (Date.now() / 1000 - metricsData.last_backup.timestamp) / 3600;
                    if (hours < 30) {
                        lastBackup.style.color = '#28a745'; // < 30 hours = green
                    } else if (hours < 48) {
                        lastBackup.style.color = '#ffc107'; // < 48 hours = yellow
                    } else {
                        lastBackup.style.color = '#dc3545'; // > 48 hours = red
                    }
                } else {
                    lastBackup.textContent = 'No backup found';
                    lastBackup.style.color = '#dc3545';
                    backupDetails.textContent = 'Drive may be unmounted';
                }
                
                console.log('System status refreshed successfully');
                
            } catch (error) {
                console.error('Error refreshing system status:', error);
                
                // Set error states
                document.getElementById('serverStatus').textContent = '❌ Error';
                document.getElementById('serverStatus').style.color = '#dc3545';
                document.getElementById('activeUsers').textContent = 'Failed to load';
                document.getElementById('diskUsage').textContent = 'Error';
                document.getElementById('diskUsage').style.color = '#dc3545';
                document.getElementById('systemLoad').textContent = 'Error';
                document.getElementById('systemLoad').style.color = '#dc3545';
                document.getElementById('lastBackup').textContent = 'Error';
                document.getElementById('lastBackup').style.color = '#dc3545';
            }
        }
        
        async function startWatcher() {
            if (confirm('Start the file watcher service?')) {
                try {
                    const response = await fetch('../manage_watcher_api.php?action=start', {method: 'POST'});
                    const result = await response.text();
                    alert('Start Result:\n' + result);
                } catch (error) {
                    alert('Error starting watcher: ' + error.message);
                }
            }
        }
        
        async function stopWatcher() {
            if (confirm('Stop the file watcher service?')) {
                try {
                    const response = await fetch('../manage_watcher_api.php?action=stop', {method: 'POST'});
                    const result = await response.text();
                    alert('Stop Result:\n' + result);
                } catch (error) {
                    alert('Error stopping watcher: ' + error.message);
                }
            }
        }
        
        async function restartWatcher() {
            if (confirm('Restart the file watcher service?')) {
                try {
                    const response = await fetch('../manage_watcher_api.php?action=restart', {method: 'POST'});
                    const result = await response.text();
                    alert('Restart Result:\n' + result);
                } catch (error) {
                    alert('Error restarting watcher: ' + error.message);
                }
            }
        }
        
        function viewVerboseLogs() {
            // Open VerboseLogger log viewer
            window.open('verbose_log_viewer.php', '_blank', 'width=1200,height=800');
        }
        
        function browseCollections() {
            alert('File browser will be implemented in the next version.');
        }
        
        function generateReport() {
            alert('Report generation will be implemented in the next version.');
        }
        
        function exportData() {
            alert('Data export will be implemented in the next version.');
        }
        
        // Load stats when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadSystemStats();
            refreshSystemStatus();
        });
        
        // Refresh stats every 30 seconds
        setInterval(loadSystemStats, 30000);
        
        // Refresh system status every 60 seconds
        setInterval(refreshSystemStatus, 60000);
    </script>
</body>
</html>
