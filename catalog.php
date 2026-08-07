<?php
/**
 * PDF.js Fallback Catalog for browsers without native PDF support
 * Handle AJAX requests using modern CatalogSearch class
 */

// Load modern search and analytics
require_once __DIR__ . '/classes/CatalogSearch.php';
require_once __DIR__ . '/catalog_analytics.php';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'search') {
        $catalogSearch = new CatalogSearch();
        $catalogSearch->handleAjaxRequest();
        exit;
    } 
    elseif ($_POST['action'] === 'get_catalog_structure') {
        header('Content-Type: application/json');
        
        // Include catalog functions with enhanced metadata
        function getCatalogStructureData() {
            $catalogDir = './Cadman_catalog/';
            $files = scandir($catalogDir);
            $structure = [
                'indexes' => [],
                'content_pages' => [],
                'other' => []
            ];
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    $filePath = $catalogDir . $file;
                    
                    // Get file metadata
                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                    $fileSizeFormatted = formatFileSize($fileSize);
                    
                    $fileData = [
                        'filename' => $file,
                        'size' => $fileSize,
                        'sizeFormatted' => $fileSizeFormatted,
                        'modified' => file_exists($filePath) ? filemtime($filePath) : 0
                    ];
                    
                    // Check if it's an index file or celtic.pdf
                    if (strpos($fileName, 'index') !== false) {
                        $categoryName = str_replace(['index_page_', 'index_'], [' ', ' '], $fileName);
                        $categoryName = str_replace('_', ' ', $categoryName);
                        $categoryName = trim(ucwords($categoryName));
                        $structure['indexes'][$categoryName] = $fileData;
                    }
                    // Include celtic.pdf as a special case
                    elseif ($fileName === 'celtic') {
                        $structure['indexes']['Celtic'] = $fileData;
                    }
                    // Check if it's a numbered page (content)
                    elseif (preg_match('/page_?(\d+[a-z]*)/', $fileName, $matches)) {
                        $pageNum = $matches[1];
                        $pageGroup = "Page " . strtoupper($pageNum);
                        $structure['content_pages'][$pageGroup][] = $fileData;
                    }
                    // Everything else
                    else {
                        $structure['other'][$fileName] = $fileData;
                    }
                }
            }
            
            // Sort content pages numerically
            uksort($structure['content_pages'], function($a, $b) {
                $numA = intval(preg_replace('/Page (\d+).*/', '$1', $a));
                $numB = intval(preg_replace('/Page (\d+).*/', '$1', $b));
                return $numA - $numB;
            });
            
            return $structure;
        }
        
        // Helper function to format file sizes
        function formatFileSize($bytes) {
            if ($bytes === 0) return '0 B';
            
            $sizes = ['B', 'KB', 'MB', 'GB'];
            $factor = floor((strlen($bytes) - 1) / 3);
            
            return sprintf("%.1f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
        }
        
        $structure = getCatalogStructureData();
        echo json_encode($structure);
        exit;
    }
    elseif ($_POST['action'] === 'get_welcome_content') {
        // Include the main catalog structure function
        function getCatalogStructure() {
            $catalogDir = './Cadman_catalog/';
            $files = scandir($catalogDir);
            $structure = [
                'indexes' => [],
                'content_pages' => [],
                'other' => []
            ];
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    
                    // Check if it's an index file
                    if (strpos($fileName, 'index') !== false) {
                        $categoryName = str_replace(['index_page_', 'index_', '_'], [' ', ' ', ' '], $fileName);
                        $categoryName = ucwords($categoryName);
                        $structure['indexes'][$categoryName] = $file;
                    }
                    // Check if it's a numbered page (content)
                    elseif (preg_match('/page_?(\d+[a-z]*)/', $fileName, $matches)) {
                        $pageNum = $matches[1];
                        $pageGroup = "Page " . strtoupper($pageNum);
                        $structure['content_pages'][$pageGroup][] = $file;
                    }
                    // Everything else
                    else {
                        $structure['other'][$fileName] = $file;
                    }
                }
            }
            
            // Sort content pages numerically
            uksort($structure['content_pages'], function($a, $b) {
                $numA = intval(preg_replace('/Page (\d+).*/', '$1', $a));
                $numB = intval(preg_replace('/Page (\d+).*/', '$1', $b));
                return $numA - $numB;
            });
            
            return $structure;
        }
        
        function getPageDescription($pageNum) {
            $descriptions = [
                3 => "Wedding & Engagement Collection",
                6 => "Classic Ring Styles",
                7 => "Modern Band Designs", 
                8 => "Vintage Inspired Pieces",
                9 => "Custom Design Options",
                10 => "Precious Stone Settings",
                11 => "Alternative Metal Options",
                12 => "Specialty Finishes"
            ];
            
            return isset($descriptions[$pageNum]) ? $descriptions[$pageNum] : "Product Showcase";
        }
        
        $catalogStructure = getCatalogStructure();
        
        // Generate welcome content
        echo '<div class="catalog-welcome">
                <h2>Welcome to Our Catalog</h2>
                <p>Select a category above to start browsing our jewelry collection.</p>
                
                <!-- Quick Access Categories and Pages -->
                <div class="category-grid">';
        
        // Category Indexes FIRST
        if (isset($catalogStructure['indexes'])) {
            foreach ($catalogStructure['indexes'] as $category => $file) {
                echo '<div class="category-card" onclick="loadPDF(\'' . $file . '\')">
                        <h3>📑 ' . $category . '</h3>
                        <p>Category Index</p>
                      </div>';
            }
        }
        
        // Featured Product Pages AFTER categories
        if (isset($catalogStructure['content_pages'])) {
            $featuredPages = [];
            foreach ($catalogStructure['content_pages'] as $pageGroup => $pageFiles) {
                $pageNum = intval(preg_replace('/Page (\d+)/', '$1', $pageGroup));
                if (in_array($pageNum, [3, 6, 7, 8, 9, 10])) { // Popular pages
                    $featuredPages[$pageGroup] = [
                        'file' => $pageFiles[0],
                        'description' => getPageDescription($pageNum)
                    ];
                }
            }
            
            foreach (array_slice($featuredPages, 0, 6) as $pageGroup => $pageInfo) {
                echo '<div class="category-card" onclick="loadPDF(\'' . $pageInfo['file'] . '\')">
                        <h3>🎯 ' . $pageGroup . '</h3>
                        <p>' . $pageInfo['description'] . '</p>
                      </div>';
            }
        }
        
        echo '</div>
                
                <!-- Quick Search Tips -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                    <h3 style="color: #8B4513; margin-bottom: 15px;">💡 Quick Search Tips</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #6c757d;">
                        <div><strong>"page 3"</strong> - Find Page 3 content</div>
                        <div><strong>"wedding"</strong> - Find wedding-related pages</div>
                        <div><strong>"rings"</strong> - Find ring collections</div>
                        <div><strong>"engagement"</strong> - Find engagement items</div>
                    </div>
                </div>
            </div>';
        
        exit;
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link rel="stylesheet" href="/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes, maximum-scale=10.0, minimum-scale=0.25, viewport-fit=cover">
    <meta name="theme-color" content="#FFD700" />
    <meta name="keywords" content="Cadman Manufacturing, jewelry catalog, wedding bands, engagement rings, catalog" />
    <link rel="icon" sizes="" href="/favicon.ico">
    <title>Product Catalog - Cadman Manufacturing</title>
    
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"></script>
    
    <style>
        /* Global reset and body styles */
        * {
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .catalog-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: calc(100vh - 40px); /* Full height minus padding */
        }
        
        .catalog-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #FFD700;
            padding-bottom: 20px;
        }
        
        .catalog-header h1 {
            color: #8B4513;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .catalog-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            align-items: center;
        }
        
        .control-group {
            display: flex;
            flex-direction: row;
            gap: 10px;
            align-items: center;
            flex: 1;
        }
        
        .control-group label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .catalog-select, .search-input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 200px;
            flex: 1;
        }
        
        .control-group .search-input {
            flex: 1;
        }
        
        #searchBtn {
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .catalog-select:focus, .search-input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #8B4513;
        }
        
        .btn-primary:hover {
            background: linear-gradient(145deg, #FFA500, #FFD700);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .pdf-viewer-container {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
            min-height: 600px;
            overflow: auto; /* Enable scrolling */
            width: 100%;
            /* Ensure smooth scrolling when zoomed */
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            text-align: center;
        }
        
        .pdf-canvas-wrapper {
            display: inline-block;
            margin: 20px auto;
            /* This wrapper will expand when canvas is scaled */
            transition: all 0.1s ease-out;
        }
        
        .pdf-loading {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 600px;
            font-size: 18px;
            color: #6c757d;
            gap: 20px;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8B4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .loading-text {
            font-weight: 500;
            animation: pulse 2s ease-in-out infinite alternate;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }
        
        .pdf-canvas {
            display: block;
            margin: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            background: white;
            /* Ensure canvas can be larger than container when zoomed */
            min-width: 0;
            max-width: none;
            /* Smooth transform transitions */
            transition: transform 0.1s ease-out;
            transform-origin: center center;
        }
            margin: 0 auto;
            min-width: 600px; /* Minimum width for quality */
        }
        
        .pdf-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            padding: 15px 25px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        
        /* Desktop navigation row layout - all on one line */
        .pdf-nav-row {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-left: 30px;
            border-left: 2px solid #e9ecef;
        }
        
        /* Desktop button styling */
        .nav-btn {
            padding: 12px 20px;
            border: 1px solid #ced4da;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s ease;
            white-space: nowrap;
            color: #495057;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 80px;
            text-align: center;
            /* Ensure buttons are clickable */
            pointer-events: auto;
            position: relative;
            z-index: 10;
        }
        
        .nav-btn:hover {
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-color: #adb5bd;
            color: #343a40;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        
        .nav-btn:active {
            background: linear-gradient(145deg, #e9ecef, #dee2e6);
            border-color: #6c757d;
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .nav-btn:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            background: #f8f9fa !important;
            color: #6c757d !important;
            transform: none !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
            pointer-events: none !important;
        }
        
        /* Special styling for zoom buttons */
        #zoomOutBtn, #zoomInBtn {
            font-size: 18px;
            font-weight: 700;
            min-width: 50px;
            padding: 10px 16px;
        }
        
        #fitWidthBtn {
            font-size: 13px;
            font-weight: 600;
            min-width: 60px;
        }
        
        .page-info {
            font-weight: 700;
            color: #495057;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            min-width: 160px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 15px;
        }
        
        .zoom-info {
            font-weight: 700;
            color: #495057;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 10px 16px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            min-width: 70px;
            text-align: center;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
            color: #495057;
            min-width: 45px;
            text-align: center;
        }
        
        .page-info {
            font-weight: 600;
            color: #495057;
        }
        
        .nav-btn {
            padding: 8px 12px;
            background: #FFD700;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            color: #8B4513;
        }
        
        .nav-btn:hover:not(:disabled) {
            background: #FFA500;
        }
        
        .nav-btn:disabled {
            background: #ddd;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .catalog-welcome {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .catalog-welcome h2 {
            color: #8B4513;
            margin-bottom: 15px;
        }
        
        .pdf-preview {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .pdf-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .category-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-card:hover {
            border-color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .category-card h3 {
            color: #8B4513;
            margin-bottom: 10px;
        }
        
        .back-to-site {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        .back-to-site .btn {
            background: rgba(139, 69, 19, 0.9);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .back-to-site .btn:hover {
            background: rgba(139, 69, 19, 1);
        }
        
        @media (max-width: 768px) {
            .catalog-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-group {
                margin-left: 0;
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
                width: 100%;
                padding: 10px 0;
            }
            
            /* Make buttons responsive and touch-friendly on mobile */
            .btn-group .btn {
                flex: 1;
                min-width: 0;
                max-width: calc(50% - 4px); /* Two buttons per row max */
                padding: 10px 8px;
                font-size: 12px;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-height: 44px; /* Touch target size */
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1.2;
            }
            
            /* Ensure buttons wrap to new rows when needed */
            .btn-group .btn:nth-child(3) {
                flex-basis: 100%; /* Fullscreen button gets its own row */
                max-width: 100%;
            }
            
            .btn-group .btn:nth-child(4),
            .btn-group .btn:nth-child(5) {
                flex-basis: calc(50% - 4px); /* Download buttons share bottom row */
                max-width: calc(50% - 4px);
            }
            
            /* Compact text for mobile */
            .btn-group .btn {
                font-size: 11px;
                padding: 8px 6px;
            }
            
            .control-group {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .control-group label {
                font-size: 13px;
            }
            
            #searchBtn {
                flex-shrink: 0;
                padding: 10px 16px;
                font-size: 13px;
                width: 100%;
            }
            
            .catalog-select, .search-input {
                min-width: auto;
                width: 100%;
            }
            
            /* Mobile PDF viewer improvements */
            .pdf-viewer-container {
                -webkit-overflow-scrolling: touch; /* Smooth iOS scrolling */
                overflow: auto; /* Enable scrolling in all directions */
                min-height: 400px; /* Smaller on mobile */
                /* Prevent ALL browser touch gestures */
                touch-action: none !important;
                -ms-touch-action: none !important;
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                user-select: none !important;
                /* Force browser to allow zoom */
                zoom: 1;
                -webkit-transform: scale(1);
                transform: scale(1);
                transform-origin: 0 0;
                position: relative;
            }
            
            .pdf-canvas, 
            canvas {
                /* Allow manual touch handling for custom gestures */
                touch-action: none !important;
                -ms-touch-action: none !important;
                pointer-events: auto !important;
                max-width: none !important;
                width: auto !important;
                height: auto !important;
                display: block;
                margin: 0 auto;
                /* Force browser zoom capability */
                image-rendering: auto;
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
                will-change: transform;
            }
            
            /* Remove any restrictions on touch for manual handling */
            .pdf-viewer-container,
            .pdf-viewer-container *,
            .pdf-canvas-wrapper,
            .pdf-canvas-wrapper * {
                touch-action: none !important;
                -ms-touch-action: none !important;
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                user-select: none !important;
            }
            
            /* Hide zoom controls on mobile - use gestures instead */
            .zoom-controls {
                display: none !important;
            }
            
            /* Keep only navigation controls on mobile */
            .pdf-navigation {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
                gap: 10px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin: 10px auto;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            /* Navigation controls row - Previous, Page Info, Next inline */
            .pdf-nav-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }
            
            .pdf-navigation #prevPageBtn,
            .pdf-navigation #nextPageBtn {
                flex: 0 0 auto;
                min-width: 80px;
                padding: 12px 16px;
                font-size: 14px;
                border: 1px solid #ddd;
                background: white;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
                touch-action: manipulation;
                font-weight: 500;
            }
            
            .pdf-navigation #pageInfo {
                flex: 1;
                text-align: center;
                font-weight: 600;
                color: #495057;
                background: #f8f9fa;
                border-radius: 6px;
                padding: 12px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e9ecef;
                margin: 0 10px;
            }
            
            .pdf-navigation button:hover,
            .pdf-navigation button:active {
                background: #f8f9fa;
                border-color: #6c757d;
                color: #495057;
            }
            
            .pdf-navigation button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background: #f8f9fa;
                color: #6c757d;
            }
        }
        
        /* Portrait orientation specific styles */
        @media (max-width: 768px) and (orientation: portrait) {
            html, body {
                height: 100%;
                min-height: 100vh;
                overflow-x: hidden;
            }
            
            .catalog-container {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                padding-bottom: 20px;
            }
            
            .pdf-viewer-container {
                flex: 1;
                min-height: 60vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Ensure all elements are properly sized */
            .catalog-controls,
            .pdf-navigation {
                flex-shrink: 0;
            }
            
            .pdf-canvas {
                width: 100% !important;
                height: auto !important;
                max-width: none !important;
            }
        }
        
        /* Landscape orientation for mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .pdf-viewer-container {
                min-height: 50vh;
                max-height: 70vh;
            }
        }
    </style>
</head>
<body>
    <!-- Back to Main Site Button -->
    <div class="back-to-site">
        <a href="index.php" class="btn">
            ← Back to Main Site
        </a>
    </div>

    <?php
    /**
 * Get catalog structure organized by type
 */
function getCatalogStructure() {
    $catalogDir = './Cadman_catalog/';
    $files = scandir($catalogDir);
    $structure = [
        'indexes' => [],
        'content_pages' => [],
        'other' => []
    ];
    
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            
            // Check if it's an index file
            if (strpos($fileName, 'index') !== false) {
                $categoryName = str_replace(['index_page_', 'index_', '_'], [' ', ' ', ' '], $fileName);
                $categoryName = ucwords($categoryName);
                $structure['indexes'][$categoryName] = $file;
            }
            // Check if it's a numbered page (content)
            elseif (preg_match('/page_?(\d+[a-z]*)/', $fileName, $matches)) {
                $pageNum = $matches[1];
                $pageGroup = "Page " . strtoupper($pageNum);
                $structure['content_pages'][$pageGroup][] = $file;
            }
            // Everything else
            else {
                $structure['other'][$fileName] = $file;
            }
        }
    }
    
    // Sort content pages numerically
    uksort($structure['content_pages'], function($a, $b) {
        $numA = intval(preg_replace('/Page (\d+).*/', '$1', $a));
        $numB = intval(preg_replace('/Page (\d+).*/', '$1', $b));
        return $numA - $numB;
    });
    
    return $structure;
}

/**
 * Create searchable catalog index from index PDFs
 */

/**
 * Get descriptive text for page numbers
 */
function getPageDescription($pageNum) {
    $descriptions = [
        3 => "Wedding & Engagement Collection",
        6 => "Classic Ring Styles",
        7 => "Modern Band Designs", 
        8 => "Vintage Inspired Pieces",
        9 => "Custom Design Options",
        10 => "Precious Stone Settings",
        11 => "Alternative Metal Options",
        12 => "Specialty Finishes"
    ];
    
    return isset($descriptions[$pageNum]) ? $descriptions[$pageNum] : "Product Showcase";
}

$catalogStructure = getCatalogStructure();
?>
    
    <div class="catalog-container">
        <div class="catalog-header">
            <h1>📖 Cadman Manufacturing Catalog</h1>
            <p>Browse our complete jewelry collection</p>
        </div>
        
        <div class="catalog-controls">
            <div class="control-group">
                <label for="searchInput">Search Catalog:</label>
                <input type="text" id="searchInput" class="search-input" placeholder="Search: page 3, wedding, rings, etc.">
                <button id="searchBtn" class="btn btn-primary">🔍 Search</button>
            </div>
            
            <div class="btn-group">
                <button id="showAllPagesBtn" class="btn btn-secondary">📄 All Pages</button>
                <button id="showByCategoryBtn" class="btn btn-secondary">📂 Category</button>
                <button id="fullscreenBtn" class="btn btn-primary">⛶ Fullscreen</button>
                <button id="downloadBtn" class="btn btn-secondary" style="display: none;">⬇️ Download</button>
                <button id="zipDownloadBtn" class="btn btn-secondary" onclick="showZipComingSoon()" title="Complete catalog download - Coming Soon!">📦 Download All</button>
            </div>
        </div>
        
        <!-- PDF Navigation Controls (positioned above PDF viewer) -->
        <div class="pdf-navigation" id="pdfNavigation" style="display: none;">
            <!-- Navigation controls row -->
            <div class="pdf-nav-row">
                <button id="prevPageBtn" class="nav-btn">‹ Prev</button>
                <span class="page-info" id="pageInfo">Page 1 of 1</span>
                <button id="nextPageBtn" class="nav-btn">Next ›</button>
            </div>
            
            <!-- Zoom controls -->
            <div class="zoom-controls">
                <button id="zoomOutBtn" class="nav-btn" title="Zoom Out">−</button>
                <span class="zoom-info" id="zoomInfo">200%</span>
                <button id="zoomInBtn" class="nav-btn" title="Zoom In">+</button>
                <button id="fitWidthBtn" class="nav-btn" title="Fit to Width">Fit</button>
            </div>
        </div>
        
        <div class="pdf-viewer-container" id="pdfContainer">
            <div class="catalog-welcome">
                <h2>Welcome to Our Catalog</h2>
                <p>Select a category above to start browsing our jewelry collection.</p>
                
                <!-- Quick Access Categories and Pages -->
                <div class="category-grid">
                    <!-- Category Indexes FIRST -->
                    <?php if (isset($catalogStructure['indexes'])): ?>
                        <?php foreach ($catalogStructure['indexes'] as $category => $file): ?>
                            <div class="category-card" onclick="loadPDF('<?php echo $file; ?>')">
                                <h3>📑 <?php echo $category; ?></h3>
                                <p>Category Index</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Featured Product Pages AFTER categories -->
                    <?php if (isset($catalogStructure['content_pages'])): ?>
                        <?php 
                        $featuredPages = [];
                        foreach ($catalogStructure['content_pages'] as $pageGroup => $pageFiles) {
                            $pageNum = intval(preg_replace('/Page (\d+)/', '$1', $pageGroup));
                            if (in_array($pageNum, [3, 6, 7, 8, 9, 10])) { // Popular pages
                                $featuredPages[$pageGroup] = [
                                    'file' => $pageFiles[0],
                                    'description' => getPageDescription($pageNum)
                                ];
                            }
                        }
                        ?>
                        
                        <?php foreach (array_slice($featuredPages, 0, 6) as $pageGroup => $pageInfo): ?>
                            <div class="category-card" onclick="loadPDF('<?php echo $pageInfo['file']; ?>')">
                                <h3>🎯 <?php echo $pageGroup; ?></h3>
                                <p><?php echo $pageInfo['description']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Search Tips -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                    <h3 style="color: #8B4513; margin-bottom: 15px;">💡 Quick Search Tips</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #6c757d;">
                        <div><strong>"page 3"</strong> - Find Page 3 content</div>
                        <div><strong>"wedding"</strong> - Find wedding-related pages</div>
                        <div><strong>"rings"</strong> - Find ring collections</div>
                        <div><strong>"engagement"</strong> - Find engagement items</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 2.0; // Base rendering scale
    let displayScale = 1.0; // Visual zoom level (CSS transform scale)
    let currentPdfFile = null;
    let catalogFiles = []; // Array of all catalog PDF files in order
    
    // Touch pan variables (global scope for mobile transform)
    let touchPanX = 0, touchPanY = 0;
    
    // Prevent browser zoom on mobile devices globally
    if (typeof window !== 'undefined' && window.innerWidth <= 768) {
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                console.log('🚫 Preventing global browser pinch zoom');
                e.preventDefault();
            }
        }, { passive: false, capture: true });
        
        document.addEventListener('touchmove', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false, capture: true });
        
        // Prevent double-tap zoom on mobile
        document.addEventListener('touchend', function(e) {
            const now = new Date().getTime();
            if (now - window.lastTouchEnd <= 300) {
                console.log('🚫 Preventing double-tap browser zoom');
                e.preventDefault();
            }
            window.lastTouchEnd = now;
        }, { passive: false, capture: true });
    }
    
    // Initialize catalog files array
    function initializeCatalogFiles() {
        // Get all PDF files in catalog directory
        fetch('catalog.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=get_catalog_structure'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Catalog structure loaded:', data);
            
            // Build ordered array of catalog files
            catalogFiles = [];
            
            // Add index files first
            Object.values(data.indexes || {}).forEach(file => {
                catalogFiles.push(typeof file === 'object' ? file.filename : file);
            });
            
            // Add content pages in order
            Object.values(data.content_pages || {}).forEach(pageGroup => {
                if (Array.isArray(pageGroup)) {
                    pageGroup.forEach(file => catalogFiles.push(typeof file === 'object' ? file.filename : file));
                } else {
                    catalogFiles.push(typeof pageGroup === 'object' ? pageGroup.filename : pageGroup);
                }
            });
            
            // Add other files
            Object.values(data.other || {}).forEach(file => {
                catalogFiles.push(typeof file === 'object' ? file.filename : file);
            });
            
            console.log('Catalog files initialized:', catalogFiles);
            updateNavigationState(); // Update navigation once files are loaded
        })
        .catch(error => {
            console.error('Error loading catalog structure:', error);
            catalogFiles = []; // Fallback to empty array
        });
    }
    
    // Function to detect mobile devices
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Universal function to calculate optimal scale for any screen size
    function calculateOptimalScale(page) {
        const viewport = page.getViewport({scale: 1.0});
        const containerWidth = pdfContainer.clientWidth - 20; // Account for padding
        
        // Calculate available height (accounting for UI elements)
        let availableHeight;
        if (isMobile()) {
            availableHeight = window.innerHeight * 0.6; // 60% on mobile
        } else {
            // Desktop: Use container height or fallback to window calculation
            const containerHeight = pdfContainer.clientHeight || (window.innerHeight * 0.7);
            availableHeight = Math.max(400, containerHeight - 100); // Minimum 400px, subtract for UI
        }
        
        const scaleWidth = containerWidth / viewport.width;
        const scaleHeight = availableHeight / viewport.height;
        
        // Use the smaller scale to ensure PDF fits in both dimensions
        const calculatedScale = Math.min(scaleWidth, scaleHeight);
        
        // Set bounds: minimum for readability, maximum for performance
        const minScale = isMobile() ? 0.8 : 0.5; // Mobile needs higher minimum
        const maxScale = isMobile() ? 3.0 : 4.0;  // Desktop can handle higher max
        
        return Math.max(minScale, Math.min(calculatedScale, maxScale));
    }
    
    // Function to apply visual zoom using CSS transforms (desktop only)
    function applyVisualZoom(zoomScale) {
        if (canvas && !isMobile()) {
            // Apply the transform to the canvas
            canvas.style.transform = `scale(${zoomScale})`;
            canvas.style.transformOrigin = 'center center';
            
            // Adjust the wrapper to provide proper scrollable area
            const wrapper = canvas.parentElement;
            if (wrapper && wrapper.className === 'pdf-canvas-wrapper') {
                // When zoomed in, we need extra padding for scrolling
                const extraSpace = zoomScale > 1 ? (zoomScale - 1) * 200 : 0;
                wrapper.style.padding = `${20 + extraSpace}px`;
            }
            
            displayScale = zoomScale;
        }
    }
    
    // Function to apply mobile touch transform (zoom + pan)
    function applyMobileTransform() {
        if (canvas && isMobile()) {
            const transform = `scale(${displayScale}) translate(${touchPanX}px, ${touchPanY}px)`;
            canvas.style.transform = transform;
            canvas.style.transformOrigin = '0 0';
            console.log('Applied mobile transform:', transform);
        }
    }
    
    // Legacy function for backward compatibility
    function calculateMobileScale(page) {
        return calculateOptimalScale(page);
    }
    let canvas = null;
    let ctx = null;
    
    // Initialize PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    // DOM elements
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const pdfContainer = document.getElementById('pdfContainer');
    const pdfNavigation = document.getElementById('pdfNavigation');
    const pageInfo = document.getElementById('pageInfo');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const downloadBtn = document.getElementById('downloadBtn');
    const showAllPagesBtn = document.getElementById('showAllPagesBtn');
    const showByCategoryBtn = document.getElementById('showByCategoryBtn');
    
    // Zoom control elements
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const fitWidthBtn = document.getElementById('fitWidthBtn');
    const zoomInfo = document.getElementById('zoomInfo');
    
    // Debug: Check if all elements are found
    console.log('PDF Navigation Elements Found:', {
        prevPageBtn: !!prevPageBtn,
        nextPageBtn: !!nextPageBtn,
        pageInfo: !!pageInfo,
        zoomOutBtn: !!zoomOutBtn,
        zoomInBtn: !!zoomInBtn,
        fitWidthBtn: !!fitWidthBtn,
        zoomInfo: !!zoomInfo
    });
    
    // Event listeners
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchCatalog(this.value);
        }
    });
    
    searchBtn.addEventListener('click', function() {
        searchCatalog(searchInput.value);
    });
    
    // Button event listeners
    showAllPagesBtn.addEventListener('click', showAllPages);
    showByCategoryBtn.addEventListener('click', showByCategory);
    
    prevPageBtn.addEventListener('click', () => {
        console.log('Prev button clicked, current file:', currentPdfFile);
        if (!catalogFiles || !Array.isArray(catalogFiles) || catalogFiles.length === 0) {
            console.log('No catalog files available');
            return;
        }
        
        const currentIndex = catalogFiles.findIndex(file => file === currentPdfFile);
        console.log('Current index:', currentIndex);
        
        if (currentIndex <= 0) {
            console.log('Already on first catalog section');
            return;
        }
        
        const prevFile = catalogFiles[currentIndex - 1];
        console.log('Loading previous catalog section:', prevFile);
        loadPDF(prevFile);
    });

    nextPageBtn.addEventListener('click', () => {
        console.log('Next button clicked, current file:', currentPdfFile);
        if (!catalogFiles || !Array.isArray(catalogFiles) || catalogFiles.length === 0) {
            console.log('No catalog files available');
            return;
        }
        
        const currentIndex = catalogFiles.findIndex(file => file === currentPdfFile);
        console.log('Current index:', currentIndex, 'Total files:', catalogFiles.length);
        
        if (currentIndex >= catalogFiles.length - 1) {
            console.log('Already on last catalog section');
            return;
        }
        
        const nextFile = catalogFiles[currentIndex + 1];
        console.log('Loading next catalog section:', nextFile);
        loadPDF(nextFile);
    });
    
    // Zoom control event listeners (desktop only)
    zoomOutBtn.addEventListener('click', () => {
        if (isMobile()) {
            // Mobile: still use re-rendering for compatibility with gestures
            scale = Math.max(scale - 0.25, 0.5);
            displayScale = Math.max(displayScale - 0.25, 0.25);
            if (displayScale <= 1.0) {
                touchPanX = 0;
                touchPanY = 0;
            }
            updateZoomInfo();
            if (pdfDoc) {
                queueRenderPage(pageNum);
            }
        } else {
            // Desktop: use CSS transform for instant zoom
            displayScale = Math.max(displayScale - 0.25, 0.25); // Min 25% zoom
            applyVisualZoom(displayScale);
            updateZoomInfo();
        }
    });
    
    zoomInBtn.addEventListener('click', () => {
        if (isMobile()) {
            // Mobile: still use re-rendering for compatibility with gestures
            scale = Math.min(scale + 0.25, 4.0);
            displayScale = Math.min(displayScale + 0.25, 3.0);
            updateZoomInfo();
            if (pdfDoc) {
                queueRenderPage(pageNum);
            }
        } else {
            // Desktop: use CSS transform for instant zoom
            displayScale = Math.min(displayScale + 0.25, 3.0); // Max 300% zoom
            applyVisualZoom(displayScale);
            updateZoomInfo();
        }
    });
    
    fitWidthBtn.addEventListener('click', () => {
        if (pdfDoc && canvas) {
            pdfDoc.getPage(pageNum).then(function(page) {
                if (isMobile()) {
                    // Mobile: re-render at optimal scale
                    scale = calculateOptimalScale(page);
                    displayScale = 1.0;
                    touchPanX = 0;
                    touchPanY = 0;
                    updateZoomInfo();
                    queueRenderPage(pageNum);
                } else {
                    // Desktop: reset to optimal scale without re-rendering
                    const optimalScale = calculateOptimalScale(page);
                    displayScale = optimalScale / scale; // Calculate ratio
                    applyVisualZoom(displayScale);
                    updateZoomInfo();
                }
            });
        }
    });
    
    fullscreenBtn.addEventListener('click', toggleFullscreen);
    
    // Show all available pages function
    function showAllPages() {
        // Get catalog structure data
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_catalog_structure'
        })
        .then(response => response.json())
        .then(data => {
            showWelcomeWithData(data, 'all');
        })
        .catch(error => {
            console.error('Error loading catalog data:', error);
            showWelcomeDefault();
        });
    }
    
    // Show by category function  
    function showByCategory() {
        // Get catalog structure data
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_catalog_structure'
        })
        .then(response => response.json())
        .then(data => {
            showWelcomeWithData(data, 'category');
        })
        .catch(error => {
            console.error('Error loading catalog data:', error);
            showWelcomeDefault();
        });
    }
    
    // Show welcome screen with data
    function showWelcomeWithData(catalogData, displayType) {
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        currentPdfFile = null;
        
        let content = '<div class="catalog-welcome">';
        
        if (displayType === 'all') {
            content += '<h2>📄 All Available Pages</h2>';
            content += '<p>Click on any page to load it directly:</p>';
            content += '<div class="category-grid">';
            
            // Show all content pages
            if (catalogData.content_pages) {
                for (const [pageGroup, files] of Object.entries(catalogData.content_pages)) {
                    files.forEach(file => {
                        const filename = typeof file === 'object' ? file.filename : file;
                        const pageLabel = filename.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
                        content += `<div class="category-card" onclick="loadPDF('${filename}')">`;
                        content += `<h3>📄 ${pageLabel}</h3>`;
                        content += `<p>Click to view</p>`;
                        content += `</div>`;
                    });
                }
            }
        } else if (displayType === 'category') {
            content += '<h2>📂 Browse by Category</h2>';
            content += '<p>Select a category to explore:</p>';
            content += '<div class="category-grid">';
            
            // Show categories with their content
            if (catalogData.indexes) {
                for (const [category, file] of Object.entries(catalogData.indexes)) {
                    const filename = typeof file === 'object' ? file.filename : file;
                    content += `<div class="category-card" onclick="loadPDF('${filename}')">`;
                    content += `<h3>📑 ${category}</h3>`;
                    content += `<p>Category Index</p>`;
                    content += `</div>`;
                }
            }
        }
        
        content += '</div>';
        content += '<div style="margin-top: 30px; text-align: center;">';
        content += '<button onclick="showWelcomeDefault()" class="btn btn-secondary">🏠 Back to Welcome</button>';
        content += '</div>';
        content += '</div>';
        
        pdfContainer.innerHTML = content;
    }
    
    // Show default welcome screen
    function showWelcomeDefault() {
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        currentPdfFile = null;
        
        // Get the original welcome content via AJAX
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_welcome_content'
        })
        .then(response => response.text())
        .then(content => {
            pdfContainer.innerHTML = content;
        })
        .catch(error => {
            console.error('Error loading welcome content:', error);
            // Fallback welcome content
            pdfContainer.innerHTML = `
                <div class="catalog-welcome">
                    <h2>Welcome to Our Catalog</h2>
                    <p>Select a category above to start browsing our jewelry collection.</p>
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                        <h3 style="color: #8B4513; margin-bottom: 15px;">💡 Quick Search Tips</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #6c757d;">
                            <div><strong>"page 3"</strong> - Find Page 3 content</div>
                            <div><strong>"wedding"</strong> - Find wedding-related pages</div>
                            <div><strong>"rings"</strong> - Find ring collections</div>
                            <div><strong>"engagement"</strong> - Find engagement items</div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    fullscreenBtn.addEventListener('click', toggleFullscreen);
    
    downloadBtn.addEventListener('click', function() {
        if (currentPdfFile) {
            const link = document.createElement('a');
            link.href = `Cadman_catalog/${currentPdfFile}`;
            link.download = currentPdfFile;
            link.click();
        }
    });
    
    // Load PDF function
    function loadPDF(filename) {
        currentPdfFile = filename;
        const url = `Cadman_catalog/${filename}`;
        
        // Show animated loading
        pdfContainer.innerHTML = `
            <div class="pdf-loading">
                <div class="loading-spinner"></div>
                <div class="loading-text">📄 Loading catalog page...</div>
            </div>
        `;
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        
        // Load PDF
        pdfjsLib.getDocument(url).promise.then(function(pdf) {
            pdfDoc = pdf;
            pageNum = 1;
            
            // Create canvas for rendering
            canvas = document.createElement('canvas');
            canvas.className = 'pdf-canvas';
            
            // Create a wrapper div for better zoom/scroll handling
            const canvasWrapper = document.createElement('div');
            canvasWrapper.className = 'pdf-canvas-wrapper';
            canvasWrapper.style.display = 'inline-block';
            canvasWrapper.style.margin = '20px auto';
            canvasWrapper.style.textAlign = 'center';
            canvasWrapper.appendChild(canvas);
            
            // Force manual touch handling for custom gestures
            canvas.style.touchAction = 'none';
            canvas.style.msTouchAction = 'none';
            canvas.style.position = 'relative';
            canvas.style.zIndex = '10'; // Ensure canvas is on top
            canvas.style.pointerEvents = 'auto'; // Ensure events reach canvas
            
            // Also apply to canvas wrapper to ensure no browser zoom
            canvasWrapper.style.touchAction = 'none';
            canvasWrapper.style.msTouchAction = 'none';
            canvasWrapper.style.webkitTouchCallout = 'none';
            canvasWrapper.style.webkitUserSelect = 'none';
            canvasWrapper.style.userSelect = 'none';
            
            // Enable touch debugging - set window.touchDebug = true in console
            window.touchDebug = false;
            
            // Test if canvas is receiving ANY events
            canvas.addEventListener('click', function(e) {
                console.log('🖱️ Canvas click detected');
                if (window.touchDebug) alert('Canvas click works!');
            });
            
            canvas.addEventListener('mousedown', function(e) {
                console.log('🖱️ Canvas mousedown detected');
                if (window.touchDebug) alert('Canvas mousedown works!');
            });
            canvas.style.webkitTouchCallout = 'default';
            canvas.style.webkitUserSelect = 'auto';
            canvas.style.userSelect = 'auto';
            canvas.style.display = 'block';
            canvas.style.margin = '0';
            canvas.style.transformOrigin = '0 0';
            canvas.style.willChange = 'transform';
            
            // Touch variables for panning and zooming
            let touchStartX = 0, touchStartY = 0;
            let isTouchDragging = false;
            let initialPinchDistance = 0;
            let isPinching = false;
            let lastTouchScale = 1;
            let lastTap = 0; // For double-tap detection
            
            // Combined touch event listeners for pan, zoom, and double-tap
            canvas.addEventListener('touchstart', function(e) {
                console.log('🔥 TOUCH START DETECTED:', e.touches.length, 'touches');
                
                // Add a visible indicator that touch was detected
                if (window.touchDebug) {
                    alert('Touch detected on canvas!');
                }
                
                if (e.touches.length === 1) {
                    // Single touch - check for double-tap first
                    const currentTime = new Date().getTime();
                    const tapLength = currentTime - lastTap;
                    
                    if (tapLength < 300 && tapLength > 0) {
                        // Double tap detected - toggle zoom
                        e.preventDefault();
                        console.log('Double tap detected, current scale:', displayScale);
                        if (displayScale <= 1.0) {
                            // Zoom in to 200%
                            displayScale = 2.0;
                            touchPanX = 0;
                            touchPanY = 0;
                            console.log('Zooming in to 200%');
                        } else {
                            // Reset to fit width
                            displayScale = 1.0;
                            touchPanX = 0;
                            touchPanY = 0;
                            console.log('Resetting to fit width');
                        }
                        applyMobileTransform();
                        updateZoomInfo();
                        lastTap = 0; // Reset to prevent triple-tap issues
                        return; // Don't process as drag start
                    }
                    
                    lastTap = currentTime;
                    
                    // Single touch - prepare for panning
                    isTouchDragging = true;
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    console.log('Single touch start for panning');
                } else if (e.touches.length === 2) {
                    // Two finger touch - prepare for pinch zoom
                    isPinching = true;
                    isTouchDragging = false;
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    initialPinchDistance = Math.sqrt(
                        Math.pow(touch2.clientX - touch1.clientX, 2) +
                        Math.pow(touch2.clientY - touch1.clientY, 2)
                    );
                    lastTouchScale = displayScale;
                    console.log('Pinch start, distance:', initialPinchDistance, 'current scale:', displayScale);
                }
                e.preventDefault();
            }, { passive: false });
            
            canvas.addEventListener('touchmove', function(e) {
                if (isPinching && e.touches.length === 2) {
                    // Handle pinch zoom
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    const currentDistance = Math.sqrt(
                        Math.pow(touch2.clientX - touch1.clientX, 2) +
                        Math.pow(touch2.clientY - touch1.clientY, 2)
                    );
                    
                    const scaleChange = currentDistance / initialPinchDistance;
                    const newScale = lastTouchScale * scaleChange;
                    
                    console.log('Pinch zoom:', scaleChange.toFixed(2), 'new scale:', newScale.toFixed(2));
                    
                    // Apply zoom limits
                    if (newScale >= 0.25 && newScale <= 3.0) {
                        displayScale = newScale;
                        // Reset pan if zoomed out to normal size
                        if (displayScale <= 1.0) {
                            touchPanX = 0;
                            touchPanY = 0;
                        }
                        applyMobileTransform();
                        updateZoomInfo();
                    }
                } else if (isTouchDragging && e.touches.length === 1) {
                    // Handle single finger panning (removed zoom restriction for debugging)
                    const deltaX = e.touches[0].clientX - touchStartX;
                    const deltaY = e.touches[0].clientY - touchStartY;
                    
                    console.log('🔄 Panning (any zoom), delta:', deltaX, deltaY, 'scale:', displayScale);
                    
                    // Apply panning with CSS transform
                    touchPanX += deltaX * 0.5;
                    touchPanY += deltaY * 0.5;
                    
                    // Update transform to include both zoom and pan
                    applyMobileTransform();
                    
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                }
                e.preventDefault();
            }, { passive: false });
            
            canvas.addEventListener('touchend', function(e) {
                if (e.touches.length === 0) {
                    isTouchDragging = false;
                    isPinching = false;
                }
                e.preventDefault();
            }, { passive: false });
            
            // Add aggressive touch event prevention to containers to stop browser zoom
            pdfContainer.addEventListener('touchstart', function(e) {
                console.log('🛡️ PDF Container preventing browser zoom');
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            pdfContainer.addEventListener('touchmove', function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            pdfContainer.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            // Also prevent on canvas wrapper
            canvasWrapper.addEventListener('touchstart', function(e) {
                console.log('🛡️ Canvas wrapper preventing browser zoom');
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            canvasWrapper.addEventListener('touchmove', function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            canvasWrapper.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, { passive: false, capture: true });
            
            ctx = canvas.getContext('2d');
            
            pdfContainer.innerHTML = '';
            pdfContainer.appendChild(canvasWrapper);
            
            // Set optimal scale and render
            pdfDoc.getPage(1).then(function(firstPage) {
                scale = calculateOptimalScale(firstPage);
                displayScale = 1.0; // Start at 1x display scale
                
                renderPage(pageNum);
                updateNavigationState();
                updateZoomInfo(); // Initialize zoom display
                updatePageInfo(); // Initialize page info display
                
                // Track PDF view in analytics
                if (window.analyticsTracker) {
                    window.analyticsTracker.trackPDFView(filename, pageNum);
                }
            });
            
            pdfNavigation.style.display = 'flex';
            downloadBtn.style.display = 'inline-flex';
            
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
            pdfContainer.innerHTML = `
                <div class="pdf-loading">
                    <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                    <div class="loading-text">Error loading catalog page. Please try again.</div>
                </div>
            `;
        });
    }
    
    // Render page function with performance optimizations
    function renderPage(num) {
        pageRendering = true;
        
        pdfDoc.getPage(num).then(function(page) {
            // Performance optimization: Use devicePixelRatio for crisp rendering
            const devicePixelRatio = window.devicePixelRatio || 1;
            
            // Optimize scale based on device and screen size
            let optimizedScale = scale;
            if (isMobile()) {
                optimizedScale = Math.min(scale, 2.0); // Cap mobile scale for performance
            }
            
            const viewport = page.getViewport({scale: optimizedScale});
            
            // Set actual canvas size for crisp rendering
            canvas.height = viewport.height * devicePixelRatio;
            canvas.width = viewport.width * devicePixelRatio;
            
            // Scale the drawing context for high DPI displays
            ctx.scale(devicePixelRatio, devicePixelRatio);
            
            // Set CSS size to maintain proper display size
            canvas.style.height = viewport.height + 'px';
            canvas.style.width = viewport.width + 'px';
            
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport,
                // Performance improvement: Use 'print' intent for better quality
                intent: 'display'
            };
            
            // Performance: Cancel any existing render task
            if (window.currentRenderTask) {
                window.currentRenderTask.cancel();
            }
            
            const renderTask = page.render(renderContext);
            window.currentRenderTask = renderTask;
            
            renderTask.promise.then(function() {
                pageRendering = false;
                window.currentRenderTask = null;
                
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            }).catch(function(error) {
                pageRendering = false;
                window.currentRenderTask = null;
                
                // Handle cancellation gracefully
                if (error.name !== 'RenderingCancelledException') {
                    console.error('Render error:', error);
                }
            });
        });
        
        updatePageInfo();
        updateZoomInfo();
        updateNavigationState();
    }
    
    // Queue render page
    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }
    
    // Update page info
    function updatePageInfo() {
        if (pdfDoc && pdfDoc.numPages) {
            // Extract catalog section from filename (e.g., "Cadman_catalog_1e.pdf" -> "1e")
            let sectionDisplay = "Page";
            if (currentPdfFile) {
                // Try multiple patterns to extract section
                let match = currentPdfFile.match(/Cadman_catalog_([^.]+)\.pdf$/i) || 
                           currentPdfFile.match(/catalog_([^.]+)\.pdf$/i) || 
                           currentPdfFile.match(/([^/_]+)\.pdf$/i);
                if (match && match[1]) {
                    sectionDisplay = match[1].toUpperCase(); // Extract and uppercase the section
                }
            }
            
            if (pdfDoc.numPages > 1) {
                pageInfo.textContent = `${sectionDisplay} - Page ${pageNum} of ${pdfDoc.numPages}`;
            } else {
                pageInfo.textContent = `Section ${sectionDisplay}`;
            }
        } else {
            pageInfo.textContent = "Loading...";
        }
    }
    
    // Update zoom info display
    function updateZoomInfo() {
        if (isMobile()) {
            // Mobile: show actual rendering scale
            const zoomPercent = Math.round(scale * 100);
            zoomInfo.textContent = `${zoomPercent}%`;
        } else {
            // Desktop: show combined scale (base scale * display scale)
            const zoomPercent = Math.round(scale * displayScale * 100);
            zoomInfo.textContent = `${zoomPercent}%`;
        }
    }
    
    // Update navigation buttons state - handles catalog navigation
    function updateNavigationState() {
        if (!pdfDoc || !catalogFiles || !Array.isArray(catalogFiles)) {
            console.log('updateNavigationState: No PDF or catalog data loaded, disabling buttons');
            prevPageBtn.disabled = true;
            nextPageBtn.disabled = true;
            return;
        }
        
        // Find current catalog file index
        const currentFileIndex = catalogFiles.findIndex(file => file === currentPdfFile);
        console.log('Current file:', currentPdfFile, 'Index:', currentFileIndex, 'Total files:', catalogFiles.length);
        
        // Enable/disable based on catalog position, not individual PDF pages
        const prevShouldDisable = currentFileIndex <= 0;
        const nextShouldDisable = currentFileIndex >= catalogFiles.length - 1;
        
        prevPageBtn.disabled = prevShouldDisable;
        nextPageBtn.disabled = nextShouldDisable;
        
        // Debug: log button states
        console.log(`updateNavigationState: Catalog ${currentFileIndex + 1} of ${catalogFiles.length}`, {
            currentFile: currentPdfFile,
            prevDisabled: prevShouldDisable,
            nextDisabled: nextShouldDisable,
            prevButton: prevPageBtn.disabled,
            nextButton: nextPageBtn.disabled
        });
    }
    
    // Show error message in-page
    function showError(message, suggestions = null) {
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        currentPdfFile = null;
        
        let content = `
            <div class="catalog-welcome">
                <div class="pdf-preview">
                    <div class="pdf-icon">⚠️</div>
                    <h2>No Results Found</h2>
                    <p style="color: #666; margin-top: 10px;">${message}</p>
        `;
        
        if (suggestions) {
            content += `
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                    <h3 style="color: #8B4513; margin-bottom: 15px;">💡 Try searching for:</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #6c757d;">
                        ${suggestions.map(s => `<div><strong>"${s.term}"</strong> - ${s.description}</div>`).join('')}
                    </div>
                </div>
            `;
        }
        
        content += `
                    <button onclick="showWelcomeDefault()" class="btn btn-secondary" style="margin-top: 20px;">🏠 Back to Welcome</button>
                </div>
            </div>
        `;
        
        pdfContainer.innerHTML = content;
    }
    
    // Show search results as page previews
    function showPagePreviews(files, searchTerm) {
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        currentPdfFile = null;
        
        let content = '<div class="catalog-welcome">';
        content += `<h2>🔍 Search Results for "${searchTerm}"</h2>`;
        content += `<p>Found ${files.length} catalog page${files.length > 1 ? 's' : ''}. Click any page to view:</p>`;
        content += '<div class="category-grid">';
        
        files.forEach(file => {
            const filename = typeof file === 'object' ? file.filename : file;
            const pageLabel = filename.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
            const size = (file.sizeFormatted) ? file.sizeFormatted : '';
            
            content += `<div class="category-card" onclick="loadPDF('${filename}')">`;
            content += `<h3>📄 ${pageLabel}</h3>`;
            content += `<p>Click to view</p>`;
            if (size) {
                content += `<p style="font-size: 12px; color: #999;">${size}</p>`;
            }
            content += `</div>`;
        });
        
        content += '</div>';
        content += '<div style="margin-top: 30px; text-align: center;">';
        content += '<button onclick="showWelcomeDefault()" class="btn btn-secondary">🏠 Back to Welcome</button>';
        content += '</div>';
        content += '</div>';
        
        pdfContainer.innerHTML = content;
    }
    
    // Show category results
    function showCategoryResults(results, searchTerm) {
        pdfNavigation.style.display = 'none';
        downloadBtn.style.display = 'none';
        currentPdfFile = null;
        
        let content = '<div class="catalog-welcome">';
        content += `<h2>🔍 Search Results for "${searchTerm}"</h2>`;
        content += `<p>Found ${results.length} matching categor${results.length > 1 ? 'ies' : 'y'}:</p>`;
        content += '<div class="category-grid">';
        
        results.forEach(result => {
            const categoryData = result.data;
            const relevance = Math.round(result.relevance || 100);
            
            content += `<div class="category-card">`;
            content += `<h3>📂 ${result.category}</h3>`;
            content += `<p>${result.description || 'Product category'}</p>`;
            content += `<p style="font-size: 12px; color: #28a745; font-weight: bold;">${relevance}% match</p>`;
            
            if (categoryData.indexes && categoryData.indexes.length > 0) {
                const firstIndex = typeof categoryData.indexes[0] === 'object' ? categoryData.indexes[0].filename : categoryData.indexes[0];
                content += `<button onclick="loadPDF('${firstIndex}')" class="btn btn-primary" style="margin-top: 10px;">View Index</button>`;
            }
            
            if (categoryData.content_pages && categoryData.content_pages.length > 0) {
                const firstPage = typeof categoryData.content_pages[0] === 'object' ? categoryData.content_pages[0].filename : categoryData.content_pages[0];
                content += `<button onclick="loadPDF('${firstPage}')" class="btn btn-secondary" style="margin-top: 5px;">View Pages</button>`;
            }
            
            content += `</div>`;
        });
        
        content += '</div>';
        content += '<div style="margin-top: 30px; text-align: center;">';
        content += '<button onclick="showWelcomeDefault()" class="btn btn-secondary">🏠 Back to Welcome</button>';
        content += '</div>';
        content += '</div>';
        
        pdfContainer.innerHTML = content;
    }
    
    // Enhanced intelligent search function
    function searchCatalog(query) {
        if (!query.trim()) return;
        
        const searchTerm = query.toLowerCase().trim();
        
        // Use PHP intelligent search via AJAX
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=search&term=' + encodeURIComponent(searchTerm)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Track search in analytics
            if (window.analyticsTracker) {
                const resultsCount = data.type === 'direct_page' ? data.files.length : 
                                   (Array.isArray(data) ? data.length : 0);
                window.analyticsTracker.trackSearch(searchTerm, resultsCount);
            }
            
            if (data.type === 'direct_page') {
                // Show page previews instead of auto-loading
                if (data.files.length > 1) {
                    showPagePreviews(data.files, data.description || searchTerm);
                } else {
                    // Single file - load it directly
                    loadPDF(data.files[0]);
                }
            } else if (data.type === 'database_match') {
                // Database product match - show results
                if (data.catalog_details && data.catalog_details.files && data.catalog_details.files.length > 0) {
                    showPagePreviews(data.catalog_details.files, searchTerm);
                } else {
                    showError(`Product "${searchTerm}" found in database, but no catalog page available.`, [
                        {term: 'page 3', description: 'Find Page 3 content'},
                        {term: 'wedding', description: 'Wedding rings'},
                        {term: 'engagement', description: 'Engagement rings'}
                    ]);
                }
            } else if (Array.isArray(data) && data.length > 0) {
                // Category results - show them visually
                showCategoryResults(data, searchTerm);
            } else {
                // No results - show error with suggestions
                showError(`No results found for "${searchTerm}"`, [
                    {term: 'page 3', description: 'Page references'},
                    {term: 'wedding', description: 'Wedding category'},
                    {term: 'rings', description: 'Ring collections'},
                    {term: 'engagement', description: 'Engagement items'},
                    {term: 'cross', description: 'Religious items'},
                    {term: 'locket', description: 'Lockets & pendants'}
                ]);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showError('Search failed. Please try again.', [
                {term: 'page 3', description: 'Try page numbers'},
                {term: 'wedding', description: 'Try categories'}
            ]);
        });
    }
    
    // Fallback search function (no longer used, but kept for compatibility)
    function fallbackSearch(searchTerm) {
        showError(`No results found for "${searchTerm}"`, [
            {term: '21B', description: 'Page references'},
            {term: 'wedding', description: 'Categories'},
            {term: '5666M', description: 'Product codes'},
            {term: 'cross', description: 'Keywords'}
        ]);
    }
    
    // Test if file exists before loading
    function testFileAndLoad(filename, displayName) {
        fetch(`./Cadman_catalog/${filename}`, { method: 'HEAD' })
            .then(response => {
                if (response.ok) {
                    loadPDF(filename);
                } else {
                    showError(`${displayName || filename} not found.`, [
                        {term: 'page 3', description: 'Try page numbers'},
                        {term: 'wedding', description: 'Try categories'}
                    ]);
                }
            })
            .catch(error => {
                showError(`${displayName || filename} not found.`, [
                    {term: 'page 3', description: 'Try page numbers'},
                    {term: 'wedding', description: 'Try categories'}
                ]);
            });
    }
    
    // Fullscreen toggle
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            pdfContainer.requestFullscreen().catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (pdfDoc && canvas) {
            queueRenderPage(pageNum);
        }
    });
    
    // Handle fullscreen changes
    document.addEventListener('fullscreenchange', function() {
        if (document.fullscreenElement) {
            fullscreenBtn.textContent = '⛶ Exit Fullscreen';
        } else {
            fullscreenBtn.textContent = '⛶ Fullscreen';
        }
        
        // Re-render to adjust to new size
        if (pdfDoc && canvas) {
            setTimeout(() => queueRenderPage(pageNum), 100);
        }
    });
    
    // Download complete catalog ZIP
    function downloadCompleteCatalog() {
        window.location.href = 'download_catalog_zip.php';
    }
    
    // Legacy function for backward compatibility
    function showZipComingSoon() {
        downloadCompleteCatalog();
    }
    
    // Analytics Tracking System
    class CatalogAnalyticsTracker {
        trackSearch(searchTerm, resultsCount) {
            this.track({
                event_type: 'search',
                search_term: searchTerm,
                results_count: resultsCount,
                viewer_type: 'pdfjs_fallback'
            });
        }
        
        trackPDFView(pdfFile, pageNumber) {
            this.track({
                event_type: 'pdf_view',
                pdf_file: pdfFile,
                page_number: pageNumber,
                viewer_type: 'pdfjs_fallback'
            });
        }
        
        track(data) {
            try {
                const payload = {
                    ...data,
                    timestamp: new Date().toISOString(),
                    user_agent: navigator.userAgent,
                    screen_width: window.innerWidth,
                    screen_height: window.innerHeight,
                    action: 'log_analytics',
                    viewer: 'pdf.js'
                };
                
                // Send analytics via AJAX
                fetch('catalog_analytics.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                }).catch(e => console.log('Analytics tracking failed:', e));
            } catch(e) {
                console.log('Analytics error:', e);
            }
        }
    }
    
    // Initialize analytics tracker
    window.analyticsTracker = new CatalogAnalyticsTracker();
    
    // Initialize catalog files when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCatalogFiles();
    });
    </script>
</body>
</html>