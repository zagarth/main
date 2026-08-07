<?php
// Enhanced Native Browser PDF Catalog Viewer
// Backup of PDF.js version: catalog.php.backup_pdfjs_[timestamp]

// Get current section from URL parameter
$currentSection = $_GET['section'] ?? 'main';

// Define catalog sections in logical order
function getCatalogSections() {
    return [
        // Title/Main pages
        'main' => ['file' => 'index_main.pdf', 'title' => 'Main Catalog', 'category' => 'Index'],
        'title' => ['file' => 'Title_page.pdf', 'title' => 'Title Page', 'category' => 'Index'],
        'title2' => ['file' => 'Title_page-02.pdf', 'title' => 'Title Page 2', 'category' => 'Index'],
        'wedding-title' => ['file' => 'title-wedding.pdf', 'title' => 'Wedding Collection', 'category' => 'Index'],
        
        // Content Pages (ordered logically)
        '01a' => ['file' => 'page_01a.pdf', 'title' => 'Page 1A', 'category' => 'Main Catalog'],
        '01b' => ['file' => 'page_01b.pdf', 'title' => 'Page 1B', 'category' => 'Main Catalog'],
        '02a' => ['file' => 'page_02a.pdf', 'title' => 'Page 2A', 'category' => 'Main Catalog'],
        '02b' => ['file' => 'page_02b.pdf', 'title' => 'Page 2B', 'category' => 'Main Catalog'],
        '02c' => ['file' => 'page_02c.pdf', 'title' => 'Page 2C', 'category' => 'Main Catalog'],
        '02d' => ['file' => 'page_02d.pdf', 'title' => 'Page 2D', 'category' => 'Main Catalog'],
        
        // Page 3 Series
        '03' => ['file' => 'page_03.pdf', 'title' => 'Page 3', 'category' => 'Series 3'],
        '03a' => ['file' => 'page_03a.pdf', 'title' => 'Page 3A', 'category' => 'Series 3'],
        '03b' => ['file' => 'page_03b.pdf', 'title' => 'Page 3B', 'category' => 'Series 3'],
        '03c' => ['file' => 'page_03c.pdf', 'title' => 'Page 3C', 'category' => 'Series 3'],
        '03d' => ['file' => 'page_03d.pdf', 'title' => 'Page 3D', 'category' => 'Series 3'],
        '03e' => ['file' => 'page_03e.pdf', 'title' => 'Page 3E', 'category' => 'Series 3'],
        '03ee' => ['file' => 'page_03ee.pdf', 'title' => 'Page 3EE', 'category' => 'Series 3'],
        '03eee' => ['file' => 'page_03eee.pdf', 'title' => 'Page 3EEE', 'category' => 'Series 3'],
        '03g' => ['file' => 'page_03g.pdf', 'title' => 'Page 3G', 'category' => 'Series 3'],
        '03h' => ['file' => 'page_03h.pdf', 'title' => 'Page 3H', 'category' => 'Series 3'],
        '03i' => ['file' => 'page_03i.pdf', 'title' => 'Page 3I', 'category' => 'Series 3'],
        '03j' => ['file' => 'page_03j.pdf', 'title' => 'Page 3J', 'category' => 'Series 3'],
        
        // Continue with other series...
        '04a' => ['file' => 'page_04a.pdf', 'title' => 'Page 4A', 'category' => 'Series 4'],
        '04b' => ['file' => 'page_04b.pdf', 'title' => 'Page 4B', 'category' => 'Series 4'],
        '04c' => ['file' => 'page_04c.pdf', 'title' => 'Page 4C', 'category' => 'Series 4'],
        
        '05a' => ['file' => 'page_05a.pdf', 'title' => 'Page 5A', 'category' => 'Series 5'],
        '05b' => ['file' => 'page_05b.pdf', 'title' => 'Page 5B', 'category' => 'Series 5'],
        
        // Page 6 Series
        '06a' => ['file' => 'page_06a.pdf', 'title' => 'Page 6A', 'category' => 'Series 6'],
        '06b' => ['file' => 'page_06b.pdf', 'title' => 'Page 6B', 'category' => 'Series 6'],
        '06c' => ['file' => 'page_06c.pdf', 'title' => 'Page 6C', 'category' => 'Series 6'],
        '06d' => ['file' => 'page_06d.pdf', 'title' => 'Page 6D', 'category' => 'Series 6'],
        '06e' => ['file' => 'page_06e.pdf', 'title' => 'Page 6E', 'category' => 'Series 6'],
        '06f' => ['file' => 'page_06f.pdf', 'title' => 'Page 6F', 'category' => 'Series 6'],
        '06g' => ['file' => 'page_06g.pdf', 'title' => 'Page 6G', 'category' => 'Series 6'],
        '06h' => ['file' => 'page_06h.pdf', 'title' => 'Page 6H', 'category' => 'Series 6'],
        '06i' => ['file' => 'page_06i.pdf', 'title' => 'Page 6I', 'category' => 'Series 6'],
        '06j' => ['file' => 'page_06j.pdf', 'title' => 'Page 6J', 'category' => 'Series 6'],
        '06k' => ['file' => 'page_06k.pdf', 'title' => 'Page 6K', 'category' => 'Series 6'],
        '06L' => ['file' => 'page_06L.pdf', 'title' => 'Page 6L', 'category' => 'Series 6'],
        
        // Series 7
        '07a' => ['file' => 'page_07a.pdf', 'title' => 'Page 7A', 'category' => 'Series 7'],
        '07b' => ['file' => 'page_07b.pdf', 'title' => 'Page 7B', 'category' => 'Series 7'],
        '07c' => ['file' => 'page_07c.pdf', 'title' => 'Page 7C', 'category' => 'Series 7'],
        '07d' => ['file' => 'page_07d.pdf', 'title' => 'Page 7D', 'category' => 'Series 7'],
        '07e' => ['file' => 'page_07e.pdf', 'title' => 'Page 7E', 'category' => 'Series 7'],
        '07f' => ['file' => 'page_07f.pdf', 'title' => 'Page 7F', 'category' => 'Series 7'],
        
        // Series 8
        '08a' => ['file' => 'page_08a.pdf', 'title' => 'Page 8A', 'category' => 'Series 8'],
        '08b' => ['file' => 'page_08b.pdf', 'title' => 'Page 8B', 'category' => 'Series 8'],
        '08c' => ['file' => 'page_08c.pdf', 'title' => 'Page 8C', 'category' => 'Series 8'],
        '08d' => ['file' => 'page_08d.pdf', 'title' => 'Page 8D', 'category' => 'Series 8'],
        
        // Series 9
        '09a' => ['file' => 'page_09a.pdf', 'title' => 'Page 9A', 'category' => 'Series 9'],
        '09aa' => ['file' => 'page_09aa.pdf', 'title' => 'Page 9AA', 'category' => 'Series 9'],
        '09b' => ['file' => 'page_09b.pdf', 'title' => 'Page 9B', 'category' => 'Series 9'],
        '09c' => ['file' => 'page_09c.pdf', 'title' => 'Page 9C', 'category' => 'Series 9'],
        
        // Series 10
        '10' => ['file' => 'page_10.pdf', 'title' => 'Page 10', 'category' => 'Series 10'],
        '10a' => ['file' => 'page_10a.pdf', 'title' => 'Page 10A', 'category' => 'Series 10'],
        '10b' => ['file' => 'page_10b.pdf', 'title' => 'Page 10B', 'category' => 'Series 10'],
        '10c' => ['file' => 'page_10c.pdf', 'title' => 'Page 10C', 'category' => 'Series 10'],
        '10d' => ['file' => 'PAGE_10D.pdf', 'title' => 'Page 10D', 'category' => 'Series 10'],
        
        // Series 11
        '11a' => ['file' => 'page_11a.pdf', 'title' => 'Page 11A', 'category' => 'Series 11'],
        '11b' => ['file' => 'page_11b.pdf', 'title' => 'Page 11B', 'category' => 'Series 11'],
        '11c' => ['file' => 'page_11c.pdf', 'title' => 'Page 11C', 'category' => 'Series 11'],
        '11d' => ['file' => 'page_11d.pdf', 'title' => 'Page 11D', 'category' => 'Series 11'],
        '11e' => ['file' => 'page_11e.pdf', 'title' => 'Page 11E', 'category' => 'Series 11'],
        '11g' => ['file' => 'page_11g.pdf', 'title' => 'Page 11G', 'category' => 'Series 11'],
        '11r' => ['file' => 'page_11r.pdf', 'title' => 'Page 11R', 'category' => 'Series 11'],
        
        // Series 12
        '12a' => ['file' => 'page_12a.pdf', 'title' => 'Page 12A', 'category' => 'Series 12'],
        '12r' => ['file' => 'page_12r.pdf', 'title' => 'Page 12R', 'category' => 'Series 12'],
        
        // Special pages
        '15g' => ['file' => 'page_15g.pdf', 'title' => 'Page 15G', 'category' => 'Special'],
        '20a' => ['file' => 'page_20a.pdf', 'title' => 'Page 20A', 'category' => 'Special'],
        
        // Series 21+
        '21a' => ['file' => 'page_21a.pdf', 'title' => 'Page 21A', 'category' => 'Series 21+'],
        '21b' => ['file' => 'page_21B.pdf', 'title' => 'Page 21B', 'category' => 'Series 21+'],
        '21c' => ['file' => 'page_21c.pdf', 'title' => 'Page 21C', 'category' => 'Series 21+'],
        '21d' => ['file' => 'page_21d.pdf', 'title' => 'Page 21D', 'category' => 'Series 21+'],
        '22' => ['file' => 'page_22.pdf', 'title' => 'Page 22', 'category' => 'Series 21+'],
        '22a' => ['file' => 'page_22a.pdf', 'title' => 'Page 22A', 'category' => 'Series 21+'],
        '22b' => ['file' => 'page_22b.pdf', 'title' => 'Page 22B', 'category' => 'Series 21+'],
        '22c' => ['file' => 'page_22c.pdf', 'title' => 'Page 22C', 'category' => 'Series 21+'],
        '23a' => ['file' => 'page_23A.pdf', 'title' => 'Page 23A', 'category' => 'Series 21+'],
        '23b' => ['file' => 'page_23B.pdf', 'title' => 'Page 23B', 'category' => 'Series 21+'],
        '24a' => ['file' => 'page_24A.pdf', 'title' => 'Page 24A', 'category' => 'Series 21+'],
        '24b' => ['file' => 'page_24B.pdf', 'title' => 'Page 24B', 'category' => 'Series 21+'],
        '25a' => ['file' => 'page_25a.pdf', 'title' => 'Page 25A', 'category' => 'Series 21+'],
        '26a' => ['file' => 'page_26A.pdf', 'title' => 'Page 26A', 'category' => 'Series 21+'],
        '27a' => ['file' => 'page_27A.pdf', 'title' => 'Page 27A', 'category' => 'Series 21+'],
        '27b' => ['file' => 'page_27B.pdf', 'title' => 'Page 27B', 'category' => 'Series 21+'],
        '33g' => ['file' => 'page_33G.pdf', 'title' => 'Page 33G', 'category' => 'Series 30+'],
        '34n' => ['file' => 'page_34N.pdf', 'title' => 'Page 34N', 'category' => 'Series 30+'],
        '35n' => ['file' => 'page_35N.pdf', 'title' => 'Page 35N', 'category' => 'Series 30+'],
        
        // Category Index Pages
        'crosses' => ['file' => 'index_page_10k-crosses_01.pdf', 'title' => '10K Crosses', 'category' => 'Categories'],
        'lockets' => ['file' => 'index_page_10K-LOCKETS_01.pdf', 'title' => '10K Lockets', 'category' => 'Categories'],
        'bracelets' => ['file' => 'index_page_bracelets_01.pdf', 'title' => 'Bracelets', 'category' => 'Categories'],
        'emblematic' => ['file' => 'index_page_EMBLEMATIC_01.pdf', 'title' => 'Emblematic', 'category' => 'Categories'],
        'engagement' => ['file' => 'index_page_engagementsets_01.pdf', 'title' => 'Engagement Sets', 'category' => 'Categories'],
        'gents-rings' => ['file' => 'index_page_gents-rings_01.pdf', 'title' => 'Gents Rings', 'category' => 'Categories'],
        'ladies-stone-1' => ['file' => 'index_page_ladiesstone-001.pdf', 'title' => 'Ladies Stone 1', 'category' => 'Categories'],
        'ladies-stone-2' => ['file' => 'index_page_ladiesstone-002.pdf', 'title' => 'Ladies Stone 2', 'category' => 'Categories'],
        'medical' => ['file' => 'index_page_medical_01.pdf', 'title' => 'Medical', 'category' => 'Categories'],
        'mens-jewelry' => ['file' => 'index_page_mens-jewellry_01.pdf', 'title' => 'Mens Jewelry', 'category' => 'Categories'],
        'mother-1' => ['file' => 'index_page_mother-001.pdf', 'title' => 'Mother 1', 'category' => 'Categories'],
        'pendants-earrings' => ['file' => 'index_page_pendants-earrings_01.pdf', 'title' => 'Pendants & Earrings', 'category' => 'Categories'],
        'signets' => ['file' => 'index_page_signets_01.pdf', 'title' => 'Signets', 'category' => 'Categories'],
        'ster-crosses' => ['file' => 'index_page_ster-crosses_01.pdf', 'title' => 'Sterling Crosses', 'category' => 'Categories'],
        'ster-lockets' => ['file' => 'index_page_STER-LOCKETS_01.pdf', 'title' => 'Sterling Lockets', 'category' => 'Categories'],
        'wedding1' => ['file' => 'index_page_wedding_01.pdf', 'title' => 'Wedding Bands 1', 'category' => 'Categories'],
        'wedding2' => ['file' => 'index_page_wedding_02.pdf', 'title' => 'Wedding Bands 2', 'category' => 'Categories'],
        'wedding3' => ['file' => 'index_page_wedding_03.pdf', 'title' => 'Wedding Bands 3', 'category' => 'Categories'],
    ];
}

// Get navigation data
$catalogSections = getCatalogSections();
$sectionKeys = array_keys($catalogSections);
$currentIndex = array_search($currentSection, $sectionKeys);

// Validate current section
if ($currentIndex === false) {
    $currentSection = 'main';
    $currentIndex = 0;
}

$currentData = $catalogSections[$currentSection];
$currentFile = $currentData['file'];

// Calculate navigation
$prevSection = $currentIndex > 0 ? $sectionKeys[$currentIndex - 1] : null;
$nextSection = $currentIndex < count($sectionKeys) - 1 ? $sectionKeys[$currentIndex + 1] : null;

// Get adjacent section data for preloading
$prevData = $prevSection ? $catalogSections[$prevSection] : null;
$nextData = $nextSection ? $catalogSections[$nextSection] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadman Manufacturing Catalog - <?= htmlspecialchars($currentData['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Preload adjacent PDF files for instant navigation -->
    <?php if ($prevData): ?>
    <link rel="prefetch" href="Cadman_catalog/<?= htmlspecialchars($prevData['file']) ?>">
    <?php endif; ?>
    <?php if ($nextData): ?>
    <link rel="prefetch" href="Cadman_catalog/<?= htmlspecialchars($nextData['file']) ?>">
    <?php endif; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header .subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .catalog-nav {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-bottom: 3px solid #8B4513;
        }
        
        .nav-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
            border: none;
            cursor: pointer;
            min-width: 140px;
            text-align: center;
        }
        
        .nav-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #A0522D 0%, #8B4513 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
        }
        
        .nav-btn:disabled {
            background: #ddd;
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .nav-btn small {
            font-size: 0.7em;
            opacity: 0.8;
        }
        
        .section-info {
            text-align: center;
            color: #8B4513;
            font-weight: 600;
            flex-grow: 1;
            margin: 0 20px;
        }
        
        .section-title {
            font-size: 1.4em;
            margin-bottom: 3px;
        }
        
        .section-category {
            font-size: 0.9em;
            opacity: 0.7;
        }
        
        .section-counter {
            font-size: 0.8em;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .pdf-container {
            width: 100%;
            height: calc(100vh - 160px); /* Account for header and nav */
            border: none;
            background: white;
        }
        
        .quick-nav {
            background: #f8f9fa;
            padding: 10px 20px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        
        .quick-nav a {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8em;
            transition: background 0.2s;
        }
        
        .quick-nav a:hover {
            background: #A0522D;
        }
        
        .quick-nav a.current {
            background: #6A3A0F;
            font-weight: bold;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .catalog-nav {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }
            
            .nav-btn {
                width: 100%;
                justify-content: center;
                max-width: 200px;
                padding: 10px 15px;
            }
            
            .section-info {
                margin: 0;
                order: -1;
            }
            
            .pdf-container {
                height: calc(100vh - 200px);
            }
            
            .quick-nav {
                display: none; /* Hide on mobile for space */
            }
        }
        
        /* Loading animation for PDF changes */
        .pdf-loading {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 18px;
            color: #8B4513;
            gap: 20px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8B4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🏭 Cadman Manufacturing</h1>
        <div class="subtitle">Premium Jewelry Catalog</div>
    </header>
    
    <nav class="catalog-nav">
        <?php if ($prevData): ?>
            <a href="?section=<?= urlencode($prevSection) ?>" class="nav-btn">
                ← Previous<br><small><?= htmlspecialchars($prevData['title']) ?></small>
            </a>
        <?php else: ?>
            <button class="nav-btn" disabled>← Previous</button>
        <?php endif; ?>
        
        <div class="section-info">
            <div class="section-title"><?= htmlspecialchars($currentData['title']) ?></div>
            <div class="section-category"><?= htmlspecialchars($currentData['category']) ?></div>
            <div class="section-counter">Section <?= $currentIndex + 1 ?> of <?= count($sectionKeys) ?></div>
        </div>
        
        <?php if ($nextData): ?>
            <a href="?section=<?= urlencode($nextSection) ?>" class="nav-btn">
                Next →<br><small><?= htmlspecialchars($nextData['title']) ?></small>
            </a>
        <?php else: ?>
            <button class="nav-btn" disabled>Next →</button>
        <?php endif; ?>
    </nav>
    
    <!-- Quick navigation (desktop only) -->
    <div class="quick-nav">
        <strong>Quick Jump:</strong>
        <?php
        // Show a selection of key sections for quick navigation
        $quickSections = ['main', '01a', '03e', '06a', '10a', 'crosses', 'engagement', 'wedding1'];
        foreach ($quickSections as $section) {
            if (isset($catalogSections[$section])) {
                $class = $section === $currentSection ? 'current' : '';
                echo "<a href=\"?section=$section\" class=\"$class\">{$catalogSections[$section]['title']}</a>";
            }
        }
        ?>
        <a href="?section=main">📋 Full Index</a>
    </div>
    
    <!-- Native browser PDF viewer with fallbacks -->
    <?php
    $pdfPath = "Cadman_catalog/" . $currentFile;
    if (file_exists($pdfPath)):
    ?>
        <!-- Method 1: Object tag (most compatible when iframes disabled) -->
        <object 
            data="<?= htmlspecialchars($pdfPath) ?>" 
            type="application/pdf" 
            class="pdf-container"
            title="<?= htmlspecialchars($currentData['title']) ?>">
            
            <!-- Method 2: Embed tag (backup for object) -->
            <embed 
                src="<?= htmlspecialchars($pdfPath) ?>" 
                type="application/pdf" 
                class="pdf-container"
                title="<?= htmlspecialchars($currentData['title']) ?>">
            
            <!-- Method 3: Direct download fallback -->
            <div class="pdf-loading">
                <div class="error-message">
                    <div style="font-size: 48px; margin-bottom: 20px;">📄</div>
                    <div style="font-size: 24px; margin-bottom: 10px;">PDF Viewer Unavailable</div>
                    <div style="opacity: 0.7; margin-bottom: 20px;">
                        Your browser settings prevent PDF viewing.<br>
                        Please download the file to view it.
                    </div>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= htmlspecialchars($pdfPath) ?>" 
                           class="nav-btn" 
                           download="<?= htmlspecialchars($currentData['title']) ?>.pdf">
                           📥 Download PDF
                        </a>
                        <a href="<?= htmlspecialchars($pdfPath) ?>" 
                           class="nav-btn" 
                           target="_blank">
                           🔗 Open in New Tab
                        </a>
                        <?php if ($prevData): ?>
                        <a href="?section=<?= urlencode($prevSection) ?>" class="nav-btn">
                            ← Try Previous
                        </a>
                        <?php endif; ?>
                        <?php if ($nextData): ?>
                        <a href="?section=<?= urlencode($nextSection) ?>" class="nav-btn">
                            Try Next →
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </embed>
        </object>
    <?php else: ?>
        <div class="pdf-loading">
            <div class="error-message">
                <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 24px; margin-bottom: 10px;">PDF file not found</div>
                <div style="opacity: 0.7; margin-bottom: 20px;">File: <?= htmlspecialchars($currentFile) ?></div>
                <a href="?section=main" class="nav-btn">Return to Main Catalog</a>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                // Only if not focused on form elements
                if (!['input', 'textarea', 'select'].includes(e.target.tagName.toLowerCase())) {
                    e.preventDefault();
                    
                    if (e.key === 'ArrowLeft') {
                        <?php if ($prevSection): ?>
                        window.location.href = '?section=<?= urlencode($prevSection) ?>';
                        <?php endif; ?>
                    } else {
                        <?php if ($nextSection): ?>
                        window.location.href = '?section=<?= urlencode($nextSection) ?>';
                        <?php endif; ?>
                    }
                }
            }
        });
        
        // Show loading indicator during navigation
        function showLoading() {
            const pdfContainer = document.querySelector('.pdf-container') || 
                                document.querySelector('object') || 
                                document.querySelector('embed');
            if (pdfContainer) {
                pdfContainer.style.display = 'none';
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'pdf-loading';
                loadingDiv.innerHTML = `
                    <div class="loading-spinner"></div>
                    <div>Loading catalog page...</div>
                `;
                pdfContainer.parentNode.insertBefore(loadingDiv, pdfContainer);
            }
        }
        
        // Add loading to navigation links
        document.querySelectorAll('a[href*="section="]').forEach(link => {
            // Only add loading to navigation links, not download/open links
            if (!link.hasAttribute('download') && !link.hasAttribute('target')) {
                link.addEventListener('click', showLoading);
            }
        });
        
        // Detect PDF viewing capability and provide helpful feedback
        function detectPdfSupport() {
            const pdfContainer = document.querySelector('object[type="application/pdf"]');
            if (pdfContainer) {
                // Check if PDF is actually loading
                setTimeout(() => {
                    if (pdfContainer.offsetHeight < 200) {
                        console.log('PDF viewing may be blocked - showing fallback options');
                        // The fallback content will automatically show
                    }
                }, 2000);
            }
        }
        
        // Performance: Preload next/prev sections
        function preloadSection(section) {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = `Cadman_catalog/${section}`;
            document.head.appendChild(link);
        }
        
        console.log('🏭 Cadman Native PDF Catalog loaded - Section: <?= $currentSection ?>');
        
        // Initialize PDF support detection when page loads
        document.addEventListener('DOMContentLoaded', detectPdfSupport);
    </script>
</body>
</html>