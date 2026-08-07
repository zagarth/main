<?php
require_once 'auth.php';
require_once __DIR__ . '/../includes/SessionManager.php';
requireAdmin();

$sessionManager = SessionManager::getInstance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections Management Dashboard - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .dashboard-container {
                           <div class="admin-user-info">
                    <span>👤 Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <span>🕒 <?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                <div>
                    <a href="index.php" class="logout-button">🛡️ Admin Portal</a>
                    <a href="../index.php" class="logout-button">🏠 Main Site</a>
                    <a href="?logout=1" class="logout-button">🚪 Logout</a>
                </div>
            </div>
            <h1>🏺 Collections Management Dashboard</h1>      <div>
                    <a href="index.php" class="logout-button">🛡️ Admin Portal</a>
                    <a href="../index.php" class="logout-button">🏠 Main Site</a>
                    <a href="?logout=1" class="logout-button">🚪 Logout</a>
                </div>        <div>
                    <a href="index.php" class="logout-button">🛡️ Admin Portal</a>
                    <a href="../index.php" class="logout-button">🏠 Main Site</a>
                    <a href="?logout=1" class="logout-button">🚪 Logout</a>
                </div>        <div>
                    <a href="index.php" class="logout-button">🛡️ Admin Portal</a>
                    <a href="../index.php" class="logout-button">🏠 Main Site</a>
                    <a href="?logout=1" class="logout-button">🚪 Logout</a>
                </div>     max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
                .dashboard-header {
            background: linear-gradient(135deg, #333, #666, #FFD700);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            border: 2px solid #FFD700;
        }
        
        .admin-header-bar {
            background: rgba(0, 0, 0, 0.2);
            padding: 10px 20px;
            margin: -30px -30px 20px -30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .logout-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .dashboard-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: bold;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
                .card-header {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #FFD700;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .collection-card {
            border-left: 4px solid #FFD700;
            transition: all 0.3s ease;
        }
        
        .collection-card:hover {
            border-left-color: #FFA500;
        }
        
        .collection-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
        }
        
        .progress-circle {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 4;
        }
        
        .progress-bar-ring {
            fill: none;
            stroke: #FFD700;
            stroke-width: 4;
            stroke-dasharray: 157;
            stroke-dashoffset: 157;
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .action-button {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: black;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #FFD700;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            background: linear-gradient(145deg, #FFA500, #FFD700);
        }
        
        .action-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .action-button.secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        
        .action-button.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .action-button.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-ready { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-missing { background: #f8d7da; color: #721c24; }
        .status-processing { background: #cce7ff; color: #004085; }
        
        .log-container {
            background: #1e1e1e;
            color: #00ff00;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .log-entry {
            margin-bottom: 5px;
        }
        
        .log-timestamp {
            color: #888;
        }
        
        .log-error { color: #ff6b6b; }
        .log-success { color: #51cf66; }
        .log-warning { color: #ffd43b; }
        
        .overview-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .overview-card {
            background: linear-gradient(135deg, #333, #FFD700);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: 2px solid #FFD700;
        }
        
        .overview-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .overview-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .collection-selector {
            background: #f8f9fa;
            border: 2px solid #FFD700;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .collection-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .processing-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .processing-modal {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FFD700;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #FFD700;
            border-radius: 20px;
            background: white;
            color: #333;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active, .filter-tab:hover {
            background: #FFD700;
            color: black;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="admin-header-bar">
                <div class="admin-user-info">
                    <span>👤 Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <span>🕒 <?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                <div>
                    <a href="index.php" class="logout-button">🛡️ Admin Portal</a>
                    <a href="../index.php" class="logout-button">🏠 Main Site</a>
                    <a href="?logout=1" class="logout-button">🚪 Logout</a>
                </div>
            </div>
            <h1>🏺 Collections Management Dashboard</h1>
            <p>Automated content management for all jewelry collections</p>
        </div>
        
        <div class="overview-stats" id="overviewStats">
            <div class="overview-card">
                <div class="overview-number" id="totalCollections">-</div>
                <div class="overview-label">Collections</div>
            </div>
            <div class="overview-card">
                <div class="overview-number" id="totalItems">-</div>
                <div class="overview-label">Total Items</div>
            </div>
            <div class="overview-card">
                <div class="overview-number" id="readyItems">-</div>
                <div class="overview-label">Ready Items</div>
            </div>
            <div class="overview-card">
                <div class="overview-number" id="readyPercent">-</div>
                <div class="overview-label">Completion %</div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">🎛️</span>
                    Global Controls
                </div>
                
                <div class="collection-selector" style="display: none;">
                    <!-- Collection selector removed - using direct database categories -->
                </div>
                
                <div style="text-align: center;">
                    <button class="action-button" onclick="processCollections()">
                        🔄 Process Selected
                    </button>
                    <button class="action-button secondary" onclick="refreshAllStatus()">
                        📊 Refresh Status
                    </button>
                    <button class="action-button success" onclick="processAllCollections()">
                        ⚡ Process All
                    </button>
                    <button class="action-button warning" onclick="generateAllThumbnails()">
                        🖼️ Generate Thumbnails
                    </button>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">🎠</span>
                    Carousel Filter Manager
                </div>
                
                <div class="collection-selector">
                    <label for="carouselCollectionSelect"><strong>Select Collection:</strong></label>
                    <select id="carouselCollectionSelect" onchange="loadCollectionFilters()">
                        <option value="">Choose a collection...</option>
                        <?php
                        // Load actual categories from catalog_products table
                        // Note: DB connection already available via auth.php -> db_config_encrypted.php
                        try {
                            $pdo = getDBConnection();
                            $stmt = $pdo->query("
                                SELECT category, COUNT(*) as item_count 
                                FROM catalog_products 
                                WHERE has_images = 1 AND category IS NOT NULL 
                                AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
                                AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
                                GROUP BY category 
                                ORDER BY item_count DESC, category
                            ");
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            $categoryNames = [
                                'celtic_bands' => 'Celtic Bands',
                                'plain_bands' => 'Plain Bands', 
                                'fancy_bands' => 'Fancy Bands',
                                'family' => 'Family Collection',
                                'engagement' => 'Engagement Rings',
                                'school' => 'School Collection',
                                'corporate' => 'Corporate Collection',
                                'professional' => 'Professional Collection',
                                'crosses' => 'Crosses',
                                'lockets' => 'Lockets',
                                'signets' => 'Signet Rings',
                                'gents_rings' => 'Gents Rings',
                                'ladies_jewelry' => 'Ladies Jewelry'
                            ];
                            
                            foreach ($categories as $cat) {
                                $displayName = $categoryNames[$cat['category']] ?? ucfirst(str_replace('_', ' ', $cat['category']));
                                echo '<option value="' . htmlspecialchars($cat['category']) . '">' . 
                                     htmlspecialchars($displayName) . ' (' . $cat['item_count'] . ' items)</option>';
                            }
                        } catch (Exception $e) {
                            echo '<option value="">Database error - contact admin</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div id="filterPreview" style="margin-top: 15px; display: none;">
                    <div style="font-weight: bold; margin-bottom: 10px;">
                        <span id="previewTitle">Preview:</span>
                        <span id="previewCount" style="color: #8B008B; margin-left: 10px;"></span>
                    </div>
                        <div id="previewGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px; background: #f9f9f9;">
                            <!-- Preview items will be loaded here -->
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;" id="carouselActions">
                        <button class="action-button success" onclick="setCarouselFilter()" id="setCarouselBtn" disabled>
                            🎠 Set as Carousel Items
                        </button>
                        <button class="action-button secondary" onclick="clearCarouselFilter()">
                            🧹 Clear Carousel
                        </button>
                        <button class="action-button" onclick="exportFilterData()">
                            📤 Export Data
                        </button>
                        <button class="action-button" onclick="window.open('../index.php', '_blank')" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                            🔗 View Main Site
                        </button>
                    </div>
                </div>
                
                <div id="currentCarouselStatus" style="margin-top: 15px; padding: 10px; background: rgba(255, 215, 0, 0.1); border-radius: 5px; border-left: 4px solid #FFD700;">
                    <strong>Current Carousel:</strong> <span id="carouselStatus">No filter selected</span>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">📊</span>
                    System Overview
                </div>
                <div id="systemOverview">Loading system status...</div>
            </div>
        </div>
        
        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterCollections('all')">All Collections</div>
            <div class="filter-tab" onclick="filterCollections('ready')">Ready</div>
            <div class="filter-tab" onclick="filterCollections('partial')">Needs Work</div>
            <div class="filter-tab" onclick="filterCollections('missing')">Not Ready</div>
        </div>
        
        <div class="dashboard-grid" id="collectionsGrid">
            <!-- Collections will be populated here -->
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <span class="card-icon">📋</span>
                Processing Log
            </div>
            <div class="log-container" id="processingLog">
                <div class="log-entry">
                    <span class="log-timestamp">[System Ready]</span> 
                    Universal Collections Dashboard initialized
                </div>
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <button class="action-button secondary" onclick="clearLog()">Clear Log</button>
                <button class="action-button secondary" onclick="exportLog()">Export Log</button>
            </div>
        </div>
    </div>
    
    <!-- Processing Overlay -->
    <div class="processing-overlay" id="processingOverlay">
        <div class="processing-modal">
            <div class="spinner"></div>
            <h3>Processing Collections...</h3>
            <p id="processingStatus">Initializing...</p>
        </div>
    </div>

    <script>
        let collectionsData = {};
        let availableCollections = []; // Removed problematic processor dependency
        let currentCarouselFilter = null;
        let collectionFilters = {
            'bands': [
                { value: 'celtic', label: 'Celtic Bands', icon: '🍀' },
                { value: 'cultural', label: 'Cultural Bands', icon: '🌍' },
                { value: 'fancy', label: 'Fancy Bands', icon: '✨' },
                { value: 'plain', label: 'Classic Bands', icon: '⭕' }
            ],
            'family': [
                { value: 'mother', label: 'Mother\'s Collection', icon: '💐' },
                { value: 'father', label: 'Father\'s Collection', icon: '👔' },
                { value: 'daughter', label: 'Daughter\'s Collection', icon: '🌸' }
            ],
            'engagement': [
                { value: 'MK_series', label: 'MK Collection', icon: '�' },
                { value: 'MM_series', label: 'MM Collection', icon: '💎' },
                { value: 'WM_series', label: 'Wedding Sets', icon: '�' }
            ],
            'accessories': [
                { value: 'crosses', label: 'Crosses & Lockets', icon: '✝️' },
                { value: 'idents', label: 'ID Tags', icon: '🏷️' },
                { value: 'pendant_earrings', label: 'Pendant Earrings', icon: '💎' }
            ],
            'corp': [
                { value: 'awards', label: 'Corporate Awards', icon: '🏆' },
                { value: 'executive', label: 'Executive Collection', icon: '💼' }
            ],
            'signet': [
                { value: 'crest_top', label: 'Crest Top', icon: '🏛️' },
                { value: 'jewel_top', label: 'Jewel Top', icon: '💎' }
            ],
            'frontline_workers': [
                { value: 'firefighter', label: 'Firefighter', icon: '🚒' },
                { value: 'clinical_services', label: 'Clinical Services', icon: '⚕️' }
            ],
            'ladys_stoneset': [
                { value: 'gems', label: 'Gemstone Settings', icon: '💎' },
                { value: 'pearls', label: 'Pearl Settings', icon: '🤍' }
            ],
            'school': [
                { value: 'bands', label: 'School Bands', icon: '🎓' },
                { value: 'crest_tops', label: 'School Crests', icon: '🛡️' },
                { value: 'shoulders', label: 'School Shoulders', icon: '🎖️' }
            ]
        };
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshAllStatus();
            loadSavedCarouselFilter();
            
            // Auto-refresh every 60 seconds
            setInterval(refreshAllStatus, 60000);
        });
        
        // Collections are now loaded server-side for security
        
        // Refresh all collection status
        async function refreshAllStatus() {
            try {
                logMessage('Refreshing all collection status...');
                
                const response = await fetch('universal_collection_processor.php?action=status');
                collectionsData = await response.json();
                
                updateOverviewStats();
                updateSystemOverview();
                updateCollectionsGrid();
                
                logMessage('Status refresh completed successfully', 'success');
                
            } catch (error) {
                logMessage('Error refreshing status: ' + error.message, 'error');
            }
        }
        
        // Process collections
        async function processCollections() {
            const selectedCollection = document.getElementById('collectionSelect').value;
            
            try {
                showProcessingOverlay('Processing ' + (selectedCollection || 'all collections') + '...');
                logMessage('Starting processing for: ' + (selectedCollection || 'all collections'));
                
                let url = 'universal_collection_processor.php?action=process';
                if (selectedCollection) {
                    url += '&collection=' + selectedCollection;
                }
                
                const response = await fetch(url);
                const results = await response.json();
                
                hideProcessingOverlay();
                
                logMessage('Processing completed:', 'success');
                logMessage('- Collections processed: ' + results.collections_processed, 'success');
                logMessage('- Thumbnails created: ' + results.thumbnails_created, 'success');
                logMessage('- Detail pages created: ' + results.detail_pages_created, 'success');
                logMessage('- Errors: ' + results.errors, results.errors > 0 ? 'error' : 'success');
                
                // Refresh status after processing
                setTimeout(refreshAllStatus, 2000);
                
            } catch (error) {
                hideProcessingOverlay();
                logMessage('Error processing collections: ' + error.message, 'error');
            }
        }
        
        // Process all collections
        async function processAllCollections() {
            document.getElementById('collectionSelect').value = '';
            await processCollections();
        }
        
        // Generate all thumbnails using the existing script
        async function generateAllThumbnails() {
            try {
                showProcessingOverlay('Generating thumbnails for all collections...');
                logMessage('Starting thumbnail generation...');
                
                const response = await fetch('generate_thumbnails.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=generate'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.text();
                
                hideProcessingOverlay();
                logMessage('Thumbnail generation completed successfully', 'success');
                console.log('Thumbnail generation result:', result);
                
                // Refresh status after processing
                setTimeout(refreshAllStatus, 2000);
                
            } catch (error) {
                hideProcessingOverlay();
                logMessage('Error generating thumbnails: ' + error.message, 'error');
                console.error('Thumbnail generation error:', error);
            }
        }
        
        // Update overview statistics
        function updateOverviewStats() {
            let totalCollections = Object.keys(collectionsData).length;
            let totalItems = 0;
            let readyItems = 0;
            
            Object.values(collectionsData).forEach(collection => {
                totalItems += collection.totals.items;
                readyItems += collection.totals.ready;
            });
            
            const readyPercent = totalItems > 0 ? Math.round((readyItems / totalItems) * 100) : 0;
            
            document.getElementById('totalCollections').textContent = totalCollections;
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('readyItems').textContent = readyItems;
            document.getElementById('readyPercent').textContent = readyPercent + '%';
        }
        
        // Update system overview
        function updateSystemOverview() {
            const container = document.getElementById('systemOverview');
            
            if (Object.keys(collectionsData).length === 0) {
                container.innerHTML = '<div style="color: #666; font-style: italic;">No collections found</div>';
                return;
            }
            
            let totalMissingThumbs = 0;
            let totalMissingDetails = 0;
            
            Object.values(collectionsData).forEach(collection => {
                totalMissingThumbs += collection.totals.missing_thumbs;
                totalMissingDetails += collection.totals.missing_details;
            });
            
            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="stat-item">
                        <div class="stat-number">${totalMissingThumbs}</div>
                        <div class="stat-label">Missing Thumbnails</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">${totalMissingDetails}</div>
                        <div class="stat-label">Missing Detail Pages</div>
                    </div>
                </div>
            `;
        }
        
        // Update collections grid
        function updateCollectionsGrid() {
            const container = document.getElementById('collectionsGrid');
            
            if (Object.keys(collectionsData).length === 0) {
                container.innerHTML = '<div style="color: #666; font-style: italic;">No collections found</div>';
                return;
            }
            
            let html = '';
            Object.entries(collectionsData).forEach(([key, collection]) => {
                const readyPercent = collection.totals.items > 0 ? 
                    Math.round((collection.totals.ready / collection.totals.items) * 100) : 0;
                
                const status = getCollectionStatus(collection);
                const strokeDashoffset = 157 - (readyPercent / 100) * 157;
                
                html += `
                    <div class="dashboard-card collection-card" data-collection="${key}" data-status="${status.type}">
                        <div class="card-header">
                            <span class="card-icon">${getCollectionIcon(key)}</span>
                            ${collection.name}
                            <span class="status-badge ${status.class}" style="margin-left: auto;">${status.text}</span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
                            <svg class="progress-ring" viewBox="0 0 60 60">
                                <circle class="progress-circle" cx="30" cy="30" r="25"></circle>
                                <circle class="progress-bar-ring" cx="30" cy="30" r="25" 
                                        style="stroke-dashoffset: ${strokeDashoffset}"></circle>
                            </svg>
                            <div style="flex: 1;">
                                <div style="font-size: 24px; font-weight: bold; color: #333;">${readyPercent}%</div>
                                <div style="font-size: 12px; color: #666;">Complete</div>
                            </div>
                        </div>
                        
                        <div class="collection-stats">
                            <div class="stat-item">
                                <div class="stat-number">${collection.totals.items}</div>
                                <div class="stat-label">Total Items</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">${collection.totals.ready}</div>
                                <div class="stat-label">Ready</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">${collection.totals.missing_thumbs}</div>
                                <div class="stat-label">No Thumbs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">${collection.totals.missing_details}</div>
                                <div class="stat-label">No Details</div>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <button class="action-button" onclick="processSpecificCollection('${key}')">
                                🔄 Process
                            </button>
                            <button class="action-button secondary" onclick="viewCollectionDetails('${key}')">
                                👁️ Details
                            </button>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Get collection status
        function getCollectionStatus(collection) {
            const readyPercent = collection.totals.items > 0 ? 
                (collection.totals.ready / collection.totals.items) * 100 : 0;
            
            if (readyPercent === 100) {
                return { type: 'ready', class: 'status-ready', text: 'Ready' };
            } else if (readyPercent >= 50) {
                return { type: 'partial', class: 'status-partial', text: 'Partial' };
            } else {
                return { type: 'missing', class: 'status-missing', text: 'Needs Work' };
            }
        }
        
        // Get collection icon
        function getCollectionIcon(key) {
            const icons = {
                'accessories': '🔗',
                'bands': '💍',
                'corp': '🏢',
                'engagement': '💎',
                'family': '👨‍👩‍👧‍👦',
                'ladys_stoneset': '👑',
                'school': '🎓',
                'signet': '🏛️'
            };
            return icons[key] || '💍';
        }
        
        // Process specific collection
        async function processSpecificCollection(collectionKey) {
            document.getElementById('collectionSelect').value = collectionKey;
            await processCollections();
        }
        
        // View collection details
        function viewCollectionDetails(collectionKey) {
            const collection = collectionsData[collectionKey];
            if (!collection) return;
            
            let details = `Collection: ${collection.name}\n`;
            details += `Total Items: ${collection.totals.items}\n`;
            details += `Ready Items: ${collection.totals.ready}\n`;
            details += `Missing Thumbnails: ${collection.totals.missing_thumbs}\n`;
            details += `Missing Detail Pages: ${collection.totals.missing_details}\n\n`;
            
            details += 'Categories:\n';
            Object.entries(collection.categories).forEach(([path, category]) => {
                const itemCount = Object.keys(category.items).length;
                details += `- ${category.display_name}: ${itemCount} items\n`;
            });
            
            alert(details);
        }
        
        // Filter collections
        function filterCollections(filter) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter collection cards
            document.querySelectorAll('.collection-card').forEach(card => {
                const status = card.dataset.status;
                const show = filter === 'all' || status === filter;
                card.style.display = show ? 'block' : 'none';
            });
        }
        
        // Show processing overlay
        function showProcessingOverlay(message) {
            document.getElementById('processingStatus').textContent = message;
            document.getElementById('processingOverlay').style.display = 'flex';
        }
        
        // Hide processing overlay
        function hideProcessingOverlay() {
            document.getElementById('processingOverlay').style.display = 'none';
        }
        
        // Log message
        function logMessage(message, type = 'info') {
            const logContainer = document.getElementById('processingLog');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            
            let cssClass = '';
            switch (type) {
                case 'error': cssClass = 'log-error'; break;
                case 'success': cssClass = 'log-success'; break;
                case 'warning': cssClass = 'log-warning'; break;
            }
            
            logEntry.innerHTML = `
                <span class="log-timestamp">[${timestamp}]</span> 
                <span class="${cssClass}">${message}</span>
            `;
            
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        // Clear log
        function clearLog() {
            document.getElementById('processingLog').innerHTML = `
                <div class="log-entry">
                    <span class="log-timestamp">[${new Date().toLocaleTimeString()}]</span> 
                    Log cleared
                </div>
            `;
        }
        
        // Export log
        function exportLog() {
            const logContent = document.getElementById('processingLog').textContent;
            const blob = new Blob([logContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'collections-log-' + new Date().toISOString().slice(0, 10) + '.txt';
            a.click();
            URL.revokeObjectURL(url);
        }
        
        // ===== CAROUSEL FILTER MANAGEMENT FUNCTIONS =====
        
        // Load collection filters when collection is selected
        function loadCollectionFilters() {
            const selectedCollection = document.getElementById('carouselCollectionSelect').value;
            const filterContainer = document.getElementById('filterOptionsContainer');
            
            if (!selectedCollection) {
                filterContainer.style.display = 'none';
                document.getElementById('setCarouselBtn').disabled = true;
                return;
            }
            
            // Since we're now using direct categories, no sub-filters needed
            // Just enable the preview and set button
            filterContainer.style.display = 'none'; // Hide filter section since we don't need it
            document.getElementById('setCarouselBtn').disabled = false;
            
            // Show simple preview message instead of async loading
            const previewContainer = document.getElementById('filterPreview');
            const previewCount = document.getElementById('previewCount');
            previewContainer.style.display = 'block';
            previewCount.textContent = 'Loading...';
            
            logMessage(`Selected category: ${selectedCollection}`);
        }
        
        // Simple set carousel function
        function setCarouselFilter() {
            const selectedCategory = document.getElementById('carouselCollectionSelect').value;
            
            if (!selectedCategory) {
                logMessage('Please select a category', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'set');
            formData.append('collection', 'catalog_products');
            formData.append('filter', selectedCategory);
            
            fetch('carousel_filter_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    logMessage(`Carousel set to ${selectedCategory} successfully!`, 'success');
                } else {
                    logMessage('Failed to set carousel: ' + (result.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                logMessage('Error: ' + error.message, 'error');
            });
        }
            const selectedCategory = document.getElementById('carouselCollectionSelect').value;
            const previewContainer = document.getElementById('filterPreview');
            const previewGrid = document.getElementById('previewGrid');
            const previewTitle = document.getElementById('previewTitle');
            const previewCount = document.getElementById('previewCount');
            
            if (!selectedCategory) {
                previewContainer.style.display = 'none';
                return;
            }
            
            try {
                logMessage(`Loading preview for category: ${selectedCategory}...`);
                
                // Get items for this category from database
                const response = await fetch(`carousel_filter_manager.php?action=items&collection=catalog&filter=${selectedCategory}`);
                const rawData = await response.json();
                
                if (rawData.error) {
                    throw new Error(rawData.error);
                }
                
                const items = rawData.items || [];
                
                // Update preview display
                previewTitle.textContent = `${selectedCategory.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())} Collection`;
                previewCount.textContent = `${items.length} items available`;
                
                // Create preview grid
                previewGrid.innerHTML = '';
                const maxPreview = Math.min(items.length, 12); // Show max 12 items in preview
                
                for (let i = 0; i < maxPreview; i++) {
                    const item = items[i];
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.innerHTML = `
                        <img src="../${item.admin_relative_path || item.relative_path}" 
                             alt="${item.name}" 
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+'" />
                        <div class="preview-item-name">${item.name}</div>
                        <div class="preview-item-id">${item.product_id || 'N/A'}</div>
                    `;
                    previewGrid.appendChild(previewItem);
                }
                
                if (items.length > maxPreview) {
                    const moreItem = document.createElement('div');
                    moreItem.className = 'preview-item more-items';
                    moreItem.innerHTML = `<div class="more-count">+${items.length - maxPreview} more...</div>`;
                    previewGrid.appendChild(moreItem);
                }
                
                previewContainer.style.display = 'block';
                logMessage(`Preview loaded: ${items.length} items found in ${selectedCategory}`);
                
            } catch (error) {
                logMessage(`Error loading preview: ${error.message}`, 'error');
                previewContainer.style.display = 'none';
            }
        }
                formData.append('collection', selectedCollection);
                formData.append('filter', selectedFilter);
                
                const testResponse = await fetch('carousel_filter_manager.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Now get the carousel format
                const carouselResponse = await fetch('carousel_filter_manager.php?action=carousel');
                const carouselData = await carouselResponse.json();
                
                const items = carouselData.items || [];
                
                previewTitle.textContent = `Preview: ${selectedFilter.charAt(0).toUpperCase() + selectedFilter.slice(1)}`;
                previewCount.textContent = `(${items.length} grouped items)`;
                previewCount.style.color = '#FFD700';
                previewCount.style.fontWeight = 'bold';
                
                // Generate preview grid with grouped carousel items
                let previewHTML = '';
                items.slice(0, 12).forEach(item => { // Show max 12 preview items
                    const thumbSrc = item.src;
                    const variantText = item.variants > 1 ? ` (${item.variants} variants)` : '';
                    
                    previewHTML += `
                        <div style="text-align: center; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: white;">
                            <div style="width: 80px; height: 60px; border-radius: 3px; margin: 0 auto 5px; overflow: hidden; border: 1px solid #eee;">
                                <img src="${thumbSrc}" alt="${item.name}" 
                                     style="width: 100%; height: 100%; object-fit: cover; background: #f0f0f0;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="width: 100%; height: 100%; background: #f0f0f0; display: none; align-items: center; justify-content: center; font-size: 20px;">
                                    ${getFilterIcon(selectedFilter)}
                                </div>
                            </div>
                            <div style="font-size: 10px; font-weight: bold; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${item.base_name}${variantText}">
                                ${item.base_name}${variantText}
                            </div>
                        </div>
                    `;
                });
                
                if (items.length > 12) {
                    previewHTML += `
                        <div style="text-align: center; padding: 8px; border: 2px dashed #ccc; border-radius: 4px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; color: #666;">
                            +${items.length - 12} more
                        </div>
                    `;
                }
                
                previewGrid.innerHTML = previewHTML;
                previewContainer.style.display = 'block';
                setCarouselBtn.disabled = false;
                
                logMessage(`Preview loaded: ${items.length} items found`, 'success');
                
            } catch (error) {
                logMessage(`Error loading preview: ${error.message}`, 'error');
                previewContainer.style.display = 'none';
                setCarouselBtn.disabled = true;
            }
        }
        
        // Load collection items (now connects to actual backend)
        async function loadCollectionItems(collection, filter) {
            try {
                const response = await fetch(`carousel_filter_manager.php?action=items&collection=${collection}&filter=${filter}`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                return data.items || [];
            } catch (error) {
                console.error('Error loading collection items:', error);
                throw error;
            }
        }
        
        // Get icon for filter type
        function getFilterIcon(filterType) {
            const icons = {
                'celtic': '🍀', 'cultural': '🌍', 'fancy': '✨', 'plain': '⭕',
                'mother': '💐', 'father': '👔', 'daughter': '🌸',
                'MK_series': '💍', 'MM_series': '💎', 'WM_series': '💕',
                'solitaire': '💎', 'halo': '🌟', 'vintage': '🏛️', 'modern': '🔷',
                'cufflinks': '🔗', 'tieclips': '📎', 'chains': '⛓️',
                'awards': '🏆', 'emblems': '🛡️', 'badges': '🎖️',
                'classic': '🏛️', 'custom': '⚡'
            };
            return icons[filterType] || '💍';
        }
        
        // Set the selected category as carousel items
        async function setCarouselFilter() {
            const selectedCategory = document.getElementById('carouselCollectionSelect').value;
            const carouselStatus = document.getElementById('carouselStatus');
            
            if (!selectedCategory) {
                logMessage('Please select a category', 'warning');
                return;
            }
            
            try {
                logMessage(`Setting carousel to category: ${selectedCategory}...`);
                
                // Send to backend API
                const formData = new FormData();
                formData.append('action', 'set');
                formData.append('collection', 'catalog_products'); // Use a generic collection name
                formData.append('filter', selectedCategory); // The category is our filter
                
                console.log('Sending request to carousel_filter_manager.php with:', {
                    action: 'set',
                    collection: 'catalog_products',
                    filter: selectedCategory
                });
                
                const response = await fetch('carousel_filter_manager.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response: ' + responseText);
                }
                
                console.log('Parsed result:', result);
                
                if (result.success) {
                    // Store locally as well for immediate feedback
                    currentCarouselFilter = result.data;
                    
                    // Update status display
                    const filterLabel = collectionFilters[selectedCollection].find(f => f.value === selectedFilter)?.label || selectedFilter;
                    carouselStatus.innerHTML = `
                        <strong style="color: #333;">${selectedCollection.charAt(0).toUpperCase() + selectedCollection.slice(1)}</strong> - 
                        <span style="color: #333;">${filterLabel}</span>
                        <span style="color: #666; font-size: 12px;">(Set: ${new Date().toLocaleString()})</span>
                    `;
                    
                    // Refresh the preview to show the newly set carousel items
                    setTimeout(() => {
                        previewFilterItems();
                    }, 500);
                    
                    logMessage(`Carousel filter set successfully: ${selectedCollection} -> ${filterLabel}`, 'success');
                    logMessage('✅ Carousel updated! The main site homepage will now show these items.', 'success');
                } else {
                    throw new Error(result.message || 'Failed to set carousel filter');
                }
                
            } catch (error) {
                logMessage(`Error setting carousel filter: ${error.message}`, 'error');
            }
        }
        
        // Clear carousel filter
        async function clearCarouselFilter() {
            try {
                const response = await fetch('carousel_filter_manager.php?action=clear');
                const result = await response.json();
                
                if (result.success) {
                    currentCarouselFilter = null;
                    
                    const carouselStatus = document.getElementById('carouselStatus');
                    carouselStatus.innerHTML = '<span style="color: #999;">No filter selected - using default carousel</span>';
                    
                    // Clear the form selections
                    document.getElementById('carouselCollectionSelect').value = '';
                    document.getElementById('filterSelect').innerHTML = '<option value="">Choose a filter...</option>';
                    document.getElementById('filterOptionsContainer').style.display = 'none';
                    
                    logMessage('✅ Carousel filter cleared - main site will use default items', 'success');
                } else {
                    throw new Error(result.message || 'Failed to clear carousel filter');
                }
            } catch (error) {
                logMessage(`Error clearing carousel filter: ${error.message}`, 'error');
            }
        }
        
        // Export filter data
        function exportFilterData() {
            const selectedCollection = document.getElementById('carouselCollectionSelect').value;
            const selectedFilter = document.getElementById('filterSelect').value;
            
            if (!selectedCollection || !selectedFilter) {
                logMessage('Please select both collection and filter to export', 'warning');
                return;
            }
            
            const exportData = {
                collection: selectedCollection,
                filter: selectedFilter,
                timestamp: new Date().toISOString(),
                filterConfig: collectionFilters[selectedCollection],
                currentCarouselFilter: currentCarouselFilter
            };
            
            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `carousel-filter-${selectedCollection}-${selectedFilter}-${new Date().toISOString().slice(0, 10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            logMessage(`Filter data exported: ${selectedCollection} -> ${selectedFilter}`, 'success');
        }
        
        // Load saved carousel filter on page load
        async function loadSavedCarouselFilter() {
            try {
                const response = await fetch('carousel_filter_manager.php?action=get');
                const data = await response.json();
                
                if (data.active && data.collection && data.filter) {
                    currentCarouselFilter = data;
                    const carouselStatus = document.getElementById('carouselStatus');
                    const filterLabel = collectionFilters[data.collection]?.find(f => f.value === data.filter)?.label || data.filter;
                    
                    carouselStatus.innerHTML = `
                        <strong style="color: #333;">${data.collection.charAt(0).toUpperCase() + data.collection.slice(1)}</strong> - 
                        <span style="color: #333;">${filterLabel}</span>
                        <span style="color: #666; font-size: 12px;">(Set: ${new Date(data.timestamp).toLocaleString()})</span>
                    `;
                    
                    // Also pre-select the collection and filter in the dropdowns
                    const collectionSelect = document.getElementById('carouselCollectionSelect');
                    const filterSelect = document.getElementById('filterSelect');
                    
                    if (collectionSelect && filterSelect) {
                        collectionSelect.value = data.collection;
                        loadCollectionFilters(); // Load the filter options
                        
                        // Wait a moment for filters to load, then set the filter
                        setTimeout(() => {
                            filterSelect.value = data.filter;
                            previewFilterItems(); // Show the current preview
                        }, 100);
                    }
                    
                    logMessage('✅ Loaded saved carousel filter: ' + data.collection + ' -> ' + filterLabel, 'success');
                } else {
                    const carouselStatus = document.getElementById('carouselStatus');
                    carouselStatus.innerHTML = '<span style="color: #999;">No filter selected - using default carousel</span>';
                    logMessage('No saved carousel filter found, using default items', 'info');
                }
            } catch (error) {
                logMessage('Error loading saved carousel filter: ' + error.message, 'warning');
                const carouselStatus = document.getElementById('carouselStatus');
                carouselStatus.innerHTML = '<span style="color: #c00;">Error loading filter status</span>';
            }
        }
    </script>
</body>
</html>
