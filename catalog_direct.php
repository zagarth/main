<?php
// Direct PDF Navigation System with Search & Index Functionality
// Enhanced version with full catalog.php feature parity

// Page-specific CSP to allow PDF embedding and iframe functionality
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https://code.jquery.com https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdnjs.cloudflare.com; style-src-elem \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https: https://*.basemaps.cartocdn.com https://cdnjs.cloudflare.com; connect-src \'self\' https://*.basemaps.cartocdn.com; frame-ancestors \'self\' https://www.cadmanmfg.com https://www.hddoc.ca; frame-src \'self\'; object-src \'self\' data:; base-uri \'self\'; form-action \'self\';');

// Add cache-busting headers for development
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

// Handle PDF support detection
if (isset($_POST['action']) && $_POST['action'] === 'pdf_support_test') {
    $supported = $_POST['supported'] ?? null;
    
    if ($supported === 'true') {
        // Browser supports PDFs natively, continue with catalog_direct
        header('Content-Type: application/json');
        echo json_encode(['status' => 'supported', 'continue' => true]);
        exit;
    } elseif ($supported === 'false') {
        // Browser doesn't support PDFs, redirect to catalog.php
        header('Content-Type: application/json');
        echo json_encode(['status' => 'unsupported', 'redirect' => 'catalog.php']);
        exit;
    } else {
        // No response or invalid response - should not happen from user choice
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid response']);
        exit;
    }
}

// Handle AJAX requests using the new CatalogSearch class
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
        
        // Include catalog functions
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
                        $categoryName = trim(ucwords($categoryName)); // Add trim to remove leading/trailing spaces
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
}

// Get current section and view from URL parameters
$currentSection = $_GET['section'] ?? 'main';
$viewType = $_GET['view'] ?? null; // 'all', 'categories', or null for normal page view
$fromSearch = isset($_GET['from_search']) && $_GET['from_search'] === '1';

// Add script to detect when search results are displayed
$hideNavigation = false;

// Define catalog sections in logical order
function getCatalogSections() {
    return [
        // Title/Main pages
        'main' => ['file' => 'index_main.pdf', 'title' => 'Main Catalog', 'category' => 'Index'],
        'wedding-title' => ['file' => 'title-wedding.pdf', 'title' => 'Wedding Collection', 'category' => 'Index'],
        
        // Special Collections
        'celtic' => ['file' => 'celtic.pdf', 'title' => 'Celtic Collection', 'category' => 'Celtic Bands'],
        
        // Product ID Index Pages (logical to show these before content pages)
        'crosses' => ['file' => 'index_page_10k-crosses_01.pdf', 'title' => '10K Crosses', 'category' => 'Categories'],
        'lockets' => ['file' => 'index_page_10K-LOCKETS_01.pdf', 'title' => '10K Lockets', 'category' => 'Categories'],
        'engagement' => ['file' => 'index_page_engagementsets_01.pdf', 'title' => 'Engagement Sets', 'category' => 'Categories'],
        'wedding1' => ['file' => 'index_page_wedding_01.pdf', 'title' => 'Wedding Bands 1', 'category' => 'Categories'],
        'wedding2' => ['file' => 'index_page_wedding_02.pdf', 'title' => 'Wedding Bands 2', 'category' => 'Categories'],
        'wedding3' => ['file' => 'index_page_wedding_03.pdf', 'title' => 'Wedding Bands 3', 'category' => 'Categories'],
        'bracelets' => ['file' => 'index_page_bracelets_01.pdf', 'title' => 'Bracelets', 'category' => 'Categories'],
        'emblematic' => ['file' => 'index_page_EMBLEMATIC_01.pdf', 'title' => 'Emblematic', 'category' => 'Categories'],
        'gents-rings' => ['file' => 'index_page_gents-rings_01.pdf', 'title' => 'Gents Rings', 'category' => 'Categories'],
        'ladiesstone-001' => ['file' => 'index_page_ladiesstone-001.pdf', 'title' => 'Ladies Stone Set 1', 'category' => 'Categories'],
        'ladiesstone-002' => ['file' => 'index_page_ladiesstone-002.pdf', 'title' => 'Ladies Stone Set 2', 'category' => 'Categories'],
        'medical' => ['file' => 'index_page_medical_01.pdf', 'title' => 'Medical ID', 'category' => 'Categories'],
        'mens-jewellry' => ['file' => 'index_page_mens-jewellry_01.pdf', 'title' => 'Mens Jewellery', 'category' => 'Categories'],
        'mother-001' => ['file' => 'index_page_mother-001.pdf', 'title' => 'Mother Collection', 'category' => 'Categories'],
        'pendants-earrings' => ['file' => 'index_page_pendants-earrings_01.pdf', 'title' => 'Pendants & Earrings', 'category' => 'Categories'],
        'signets' => ['file' => 'index_page_signets_01.pdf', 'title' => 'Signets', 'category' => 'Categories'],
        'ster-crosses' => ['file' => 'index_page_ster-crosses_01.pdf', 'title' => 'Sterling Crosses', 'category' => 'Categories'],
        'ster-lockets' => ['file' => 'index_page_STER-LOCKETS_01.pdf', 'title' => 'Sterling Lockets', 'category' => 'Categories'],
        'corp' => ['file' => 'index_corp.pdf', 'title' => 'Corp Collection', 'category' => 'Categories'],
        
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
        '06l' => ['file' => 'page_06l.pdf', 'title' => 'Page 6L', 'category' => 'Series 6'],
        
        // Page 7 Series 
        '07a' => ['file' => 'page_07a.pdf', 'title' => 'Page 7A', 'category' => 'Series 7'],
        '07b' => ['file' => 'page_07b.pdf', 'title' => 'Page 7B', 'category' => 'Series 7'],
        '07c' => ['file' => 'page_07c.pdf', 'title' => 'Page 7C', 'category' => 'Series 7'],
        '07d' => ['file' => 'page_07d.pdf', 'title' => 'Page 7D', 'category' => 'Series 7'],
        '07e' => ['file' => 'page_07e.pdf', 'title' => 'Page 7E', 'category' => 'Series 7'],
        '07f' => ['file' => 'page_07f.pdf', 'title' => 'Page 7F', 'category' => 'Series 7'],
        
        // Page 8 Series
        '08a' => ['file' => 'page_08a.pdf', 'title' => 'Page 8A', 'category' => 'Series 8'],
        '08b' => ['file' => 'page_08b.pdf', 'title' => 'Page 8B', 'category' => 'Series 8'],
        '08c' => ['file' => 'page_08c.pdf', 'title' => 'Page 8C', 'category' => 'Series 8'],
        '08d' => ['file' => 'page_08d.pdf', 'title' => 'Page 8D', 'category' => 'Series 8'],
        
        // Page 9 Series
        '09a' => ['file' => 'page_09a.pdf', 'title' => 'Page 9A', 'category' => 'Series 9'],
        '09aa' => ['file' => 'page_09aa.pdf', 'title' => 'Page 9AA', 'category' => 'Series 9'],
        '09b' => ['file' => 'page_09b.pdf', 'title' => 'Page 9B', 'category' => 'Series 9'],
        '09c' => ['file' => 'page_09c.pdf', 'title' => 'Page 9C', 'category' => 'Series 9'],
        '09d' => ['file' => 'page_09d.pdf', 'title' => 'Page 9D', 'category' => 'Series 9'],
        
        // Page 10 Series
        '10' => ['file' => 'page_10.pdf', 'title' => 'Page 10', 'category' => 'Series 10'],
        '10a' => ['file' => 'page_10a.pdf', 'title' => 'Page 10A', 'category' => 'Series 10'],
        '10b' => ['file' => 'page_10b.pdf', 'title' => 'Page 10B', 'category' => 'Series 10'],
        '10c' => ['file' => 'page_10c.pdf', 'title' => 'Page 10C', 'category' => 'Series 10'],
        
        // Page 11 Series
        '11a' => ['file' => 'page_11a.pdf', 'title' => 'Page 11A', 'category' => 'Series 11'],
        '11b' => ['file' => 'page_11b.pdf', 'title' => 'Page 11B', 'category' => 'Series 11'],
        '11c' => ['file' => 'page_11c.pdf', 'title' => 'Page 11C', 'category' => 'Series 11'],
        '11d' => ['file' => 'page_11d.pdf', 'title' => 'Page 11D', 'category' => 'Series 11'],
        '11e' => ['file' => 'page_11e.pdf', 'title' => 'Page 11E', 'category' => 'Series 11'],
        '11g' => ['file' => 'page_11g.pdf', 'title' => 'Page 11G', 'category' => 'Series 11'],
        '11r' => ['file' => 'page_11r.pdf', 'title' => 'Page 11R', 'category' => 'Series 11'],
        
        // Page 12 Series
        '12a' => ['file' => 'page_12a.pdf', 'title' => 'Page 12A', 'category' => 'Series 12'],
        '12r' => ['file' => 'page_12r.pdf', 'title' => 'Page 12R', 'category' => 'Series 12'],
        
        // Page 15 Series
        '15g' => ['file' => 'page_15g.pdf', 'title' => 'Page 15G', 'category' => 'Series 15'],
        
        // Page 20 Series
        '20a' => ['file' => 'page_20a.pdf', 'title' => 'Page 20A', 'category' => 'Series 20'],
        
        // Page 21 Series
        '21a' => ['file' => 'page_21a.pdf', 'title' => 'Page 21A', 'category' => 'Series 21'],
        '21b' => ['file' => 'page_21b.pdf', 'title' => 'Page 21B', 'category' => 'Series 21'],
        '21c' => ['file' => 'page_21c.pdf', 'title' => 'Page 21C', 'category' => 'Series 21'],
        '21d' => ['file' => 'page_21d.pdf', 'title' => 'Page 21D', 'category' => 'Series 21'],
        
        // Page 22 Series
        '22' => ['file' => 'page_22.pdf', 'title' => 'Page 22', 'category' => 'Series 22'],
        '22a' => ['file' => 'page_22a.pdf', 'title' => 'Page 22A', 'category' => 'Series 22'],
        '22b' => ['file' => 'page_22b.pdf', 'title' => 'Page 22B', 'category' => 'Series 22'],
        '22c' => ['file' => 'page_22c.pdf', 'title' => 'Page 22C', 'category' => 'Series 22'],
        
        // Page 23 Series
        '23a' => ['file' => 'page_23a.pdf', 'title' => 'Page 23A', 'category' => 'Series 23'],
        '23b' => ['file' => 'page_23b.pdf', 'title' => 'Page 23B', 'category' => 'Series 23'],
        
        // Page 24 Series
        '24a' => ['file' => 'page_24A.pdf', 'title' => 'Page 24A', 'category' => 'Series 24'],
        '24b' => ['file' => 'page_24B.pdf', 'title' => 'Page 24B', 'category' => 'Series 24'],
        
        // Page 25 Series
        '25a' => ['file' => 'page_25a.pdf', 'title' => 'Page 25A', 'category' => 'Series 25'],
        
        // Page 26 Series
        '26a' => ['file' => 'page_26a.pdf', 'title' => 'Page 26A', 'category' => 'Series 26'],
        
        // Page 27 Series
        '27a' => ['file' => 'page_27a.pdf', 'title' => 'Page 27A', 'category' => 'Series 27'],
        '27b' => ['file' => 'page_27b.pdf', 'title' => 'Page 27B', 'category' => 'Series 27'],
        
        // Page 33 Series
        '33g' => ['file' => 'page_33G.pdf', 'title' => 'Page 33G', 'category' => 'Series 33'],
        
        // Page 34 Series
        '34n' => ['file' => 'page_34N.pdf', 'title' => 'Page 34N', 'category' => 'Series 34'],
        
        // Page 35 Series
        '35n' => ['file' => 'page_35N.pdf', 'title' => 'Page 35N', 'category' => 'Series 35'],
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

// Simple navigation using main array position - works for all page types
if ($fromSearch) {
    // Disable navigation arrows when viewing search results
    $prevSection = null;
    $nextSection = null;
} else {
    // Use the actual position in main catalog sections array
    $prevSection = $currentIndex > 0 ? $sectionKeys[$currentIndex - 1] : null;
    $nextSection = $currentIndex < count($sectionKeys) - 1 ? $sectionKeys[$currentIndex + 1] : null;
}

// Get adjacent section data for preloading
$prevData = $prevSection ? $catalogSections[$prevSection] : null;
$nextData = $nextSection ? $catalogSections[$nextSection] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadman Manufacturing Catalog<?php if($viewType === 'all'): ?> - All Pages<?php elseif($viewType === 'categories'): ?> - Product ID Indexes<?php else: ?> - <?= htmlspecialchars($currentData['title']) ?><?php endif; ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Smart PDF Preloader Scripts -->
    <script src="/smart_pdf_preloader.js" onload="console.log('✅ smart_pdf_preloader.js loaded')" onerror="console.error('❌ Failed to load smart_pdf_preloader.js')"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #f8f9fa;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            color: #f8f9fa;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            border-bottom: 3px solid #FFD700;
        }
        
        .header h1 {
            margin: 0;
            color: #FFD700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-size: 2.5em;
        }
        
        .subtitle {
            color: #f8f9fa;
            margin-top: 10px;
            font-size: 1.1em;
        }
        
        /* Navigation Styles */
        .catalog-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            padding: 20px;
            margin: 0;
            border-bottom: 2px solid #FFD700;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .nav-btn {
            padding: 12px 20px;
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
            border: 2px solid #FFD700;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 120px;
            cursor: pointer;
        }
        
        .nav-btn:hover {
            background: linear-gradient(145deg, #FFA500, #FF8C00);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255,215,0,0.4);
            color: #000;
        }
        
        .nav-btn:disabled {
            background: #666;
            color: #999;
            border-color: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .nav-btn small {
            display: block;
            font-size: 0.8em;
            margin-top: 4px;
            opacity: 0.8;
        }
        
        .section-info {
            text-align: center;
            color: #f8f9fa;
            flex: 1;
            margin: 0 20px;
        }
        
        .section-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }
        
        .section-category {
            font-size: 0.9em;
            color: #f8f9fa;
            margin-bottom: 5px;
        }
        
        .section-counter {
            font-size: 0.8em;
            color: #aaa;
        }
        
        /* Search Controls */
        .search-controls {
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            padding: 20px;
            border-bottom: 1px solid #444;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            justify-content: space-between;
        }
        
        .search-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-group label {
            color: #FFD700;
            font-weight: bold;
        }
        
        .search-input {
            padding: 10px 15px;
            border: 2px solid #FFD700;
            border-radius: 8px;
            background: #1a1a1a;
            color: #f8f9fa;
            font-size: 14px;
            width: 300px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
            border-color: #FFA500;
        }
        
        .search-input::placeholder {
            color: #aaa;
        }

        .catalog-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            max-width: min(90vw, 420px);
        }

        .catalog-toast {
            pointer-events: auto;
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            color: #f8f9fa;
            border: 1px solid #555;
            border-left: 4px solid #FFD700;
            border-radius: 8px;
            padding: 12px 14px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.35);
            font-size: 0.95rem;
            line-height: 1.35;
            transform: translateY(-8px);
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .catalog-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .catalog-toast.error {
            border-left-color: #dc3545;
        }

        .catalog-toast.success {
            border-left-color: #28a745;
        }
        
        /* Main Content Container */
        #mainContainer {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            margin: 20px;
            border-radius: 15px;
            border: 2px solid #FFD700;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            min-height: 500px;
        }
        
        .pdf-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .secondary-btn {
            padding: 12px 24px;
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            color: #FFD700;
            border: 2px solid #FFD700;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .secondary-btn:hover {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.4);
        }
        
        .pdf-icon {
            font-size: 4em;
            margin-bottom: 20px;
            color: #FFD700;
        }
        
        .search-container {
            margin: 30px auto;
            max-width: 600px;
            position: relative;
        }
        
        .search-box {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            border: 2px solid #FFD700;
            border-radius: 25px;
            outline: none;
            background: #2d2d2d;
            color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            border-color: #FFA500;
        }
        
        .search-box::placeholder {
            color: #aaa;
        }
        
        .search-results {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border: 2px solid #FFD700;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .search-results h3 {
            color: #FFD700;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .control-btn {
            padding: 12px 24px;
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
            border: 2px solid #FFD700;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .control-btn:hover {
            background: linear-gradient(145deg, #FFA500, #FF8C00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.4);
            color: #000;
        }
        
        .control-btn.secondary {
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            color: #FFD700;
            border: 2px solid #FFD700;
        }
        
        .control-btn.secondary:hover {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
        }
        
        .primary-btn {
            padding: 15px 30px;
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
            border: 2px solid #FFD700;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .primary-btn:hover {
            background: linear-gradient(145deg, #FFA500, #FF8C00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.4);
            color: #000;
        }
        
        .catalog-welcome {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: 2px solid #FFD700;
        }
        
        .catalog-welcome h2 {
            color: #FFD700;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            font-size: 2.2em;
        }
        
        .catalog-welcome p {
            color: #333;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .category-card {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #FFD700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255,215,0,0.4);
            border-color: #FFA500;
        }
        
        .category-card h3 {
            color: #FFD700;
            margin-bottom: 10px;
            font-size: 1.3em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .category-card p {
            color: #333;
            margin-bottom: 0;
        }
        
        /* Page Preview Grid Styles */
        .page-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin: 30px 0;
            max-width: 1200px;
        }
        
        .page-preview-card {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid #FFD700;
        }
        
        .page-preview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255,215,0,0.4);
            border-color: #FFA500;
        }
        
        .preview-header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 20px;
            text-align: center;
        }
        
        .preview-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .preview-header h3 {
            margin: 0;
            font-size: 1.4em;
            color: #000;
            text-shadow: none;
        }
        
        .preview-info {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .file-name {
            font-family: monospace;
            font-size: 0.9em;
            color: #666;
            margin: 0 0 8px 0;
            word-break: break-all;
        }
        
        .file-size {
            font-size: 0.8em;
            color: #888;
            margin: 0;
        }
        
        .preview-actions {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .preview-btn {
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9em;
        }
        
        .preview-btn.primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            border: 2px solid #FFD700;
        }
        
        .preview-btn.primary:hover {
            background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255,215,0,0.3);
        }
        
        .preview-btn.secondary {
            background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
            color: #FFD700;
            border: 2px solid #FFD700;
        }
        
        .preview-btn.secondary:hover {
            background: linear-gradient(145deg, #FFD700, #FFA500);
            color: #000;
        }
        
        .pdf-viewer-container {
            width: 100%;
            height: calc(100vh - 160px);
            max-height: calc(100vh - 160px);
            background: #f8f9fa;
            border: 2px solid #FFD700;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            padding: 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        /* Mobile portrait adjustments */
        @media (max-width: 768px) and (orientation: portrait) {
            .pdf-viewer-container {
                height: calc(100vh - 120px);
                max-height: calc(100vh - 120px);
            }
            
            .pdf-viewer-wrapper {
                min-height: auto;
                height: 100%;
            }
        }
        
        .pdf-header {
            padding: 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            border-bottom: 2px solid #3498db;
        }
        
        .pdf-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.3em;
        }
        
        .pdf-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .pdf-controls {
            display: flex;
            gap: 10px;
        }
        
        .pdf-controls .control-btn {
            font-size: 0.8em;
            padding: 5px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
        }
        
        .pdf-controls .control-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .pdf-viewer-wrapper {
            flex: 1;
            position: relative;
            min-height: 400px;
            overflow: auto;
        }
        
        .pdf-iframe {
            width: 100%;
            height: 100%;
            min-height: 600px;
            border: none;
            background: white;
        }
        
        .pdf-loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #f8f9fa;
            z-index: 10;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e9ecef;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pdf-loading p {
            color: #666;
            margin: 0;
            font-size: 1.1em;
        }
        
        #pdf-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #f8f9fa;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .pdf-controls-bar {
            background: #2c3e50;
            color: white;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .pdf-controls-bar button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .pdf-controls-bar button:hover {
            background: #2980b9;
        }
        
        .pdf-controls-bar button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        #page-info {
            color: #ecf0f1;
            font-weight: bold;
        }
        
        #pdf-canvas {
            display: block;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background: white;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        #zoom-display {
            color: #ecf0f1;
            font-weight: bold;
            min-width: 50px;
            text-align: center;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            /* Reorder layout: search-controls before catalog-nav */
            
            .catalog-nav {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding: 10px;
            }
            
            .nav-btn {
                min-width: auto;
                padding: 8px 12px;
                font-size: 0.8em;
                flex: 0 0 auto;
            }
            
            .section-info {
                margin: 0;
                order: -1;
            }
            
            .section-title {
                font-size: 1.2em;
            }
            
            .search-controls {
                flex-direction: column;
                gap: 10px;
                text-align: left;
                margin-top: 10px;
            }
            
            .search-input {
                width: 100%;
                max-width: 300px;
            }
            
            .search-group {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .search-group:last-child {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 8px;
                justify-content: start;
            }
            
            .search-group:last-child .control-btn {
                margin: 0;
                min-height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                white-space: nowrap;
                font-size: 0.85em;
            }
            
            #mainContainer {
                margin: 10px;
            }
            
            .controls {
                flex-direction: column;
                align-items: center;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
                gap: 10px;
                margin: 15px 0;
            }
            
            .category-card {
                padding: 15px;
                margin: 5px 0;
            }
            
            .page-preview-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .preview-actions {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .pdf-actions {
                flex-direction: column;
                align-items: center;
            }
            
            /* Prevent browser zoom on mobile PDF viewers */
            .pdf-preview-container,
            .pdf-viewer,
            .pdf-canvas,
            canvas {
                touch-action: none !important;
                -ms-touch-action: none !important;
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                user-select: none !important;
                pointer-events: auto !important;
            }
        }
        
        @media (max-width: 540px) {
            .search-group:last-child {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🏭 Cadman Manufacturing</h1>
        <div class="subtitle">Premium Jewelry Catalog</div>
    </header>
    
    <!-- Search and Index Controls -->
    <div class="search-controls">
        <div class="search-group">
            <label for="searchInput">Search:</label>
            <input type="text" id="searchInput" class="search-input" placeholder="Search: page 3, wedding, rings, etc.">
        </div>
        <div class="search-group">
            <button id="showAllPagesBtn" class="control-btn secondary">📄 All Pages</button>
            <button id="showByCategoryBtn" class="control-btn secondary">🔍 Product IDs</button>
            <button id="showCurrentBtn" class="control-btn secondary">📖 Current Page</button>
            <button id="backToMainBtn" class="control-btn secondary">🏠 Back to Main</button>
            <button id="backToHomeBtn" class="control-btn secondary">🏡 Home</button>
            <button id="resetCacheBtn" class="control-btn secondary" onclick="localStorage.removeItem('pdf_cache_consent'); location.reload();" style="display:none;">🔄 Reset Cache</button>
        </div>
    </div>
    
    <nav class="catalog-nav">
        <?php if ($prevData): ?>
            <a href="?section=<?= urlencode($prevSection) ?><?= $viewType ? '&view=' . urlencode($viewType) : '' ?>" class="nav-btn">
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
            <a href="?section=<?= urlencode($nextSection) ?><?= $viewType ? '&view=' . urlencode($viewType) : '' ?>" class="nav-btn">
                Next →<br><small><?= htmlspecialchars($nextData['title']) ?></small>
            </a>
        <?php else: ?>
            <button class="nav-btn" disabled>Next →</button>
        <?php endif; ?>
    </nav>
    
    
    <!-- Main Content Container -->
    <div id="mainContainer">
        <!-- Content will be loaded dynamically here -->
        <!-- Initial load shows current PDF if section specified -->
        <?php if ($currentSection && isset($currentFile)): ?>
        <div class="pdf-viewer-container">
            <?php
            // Case-insensitive file finder function
            function findCaseInsensitiveFile($directory, $filename) {
                $exactPath = $directory . '/' . $filename;
                
                // Try exact match first
                if (file_exists($exactPath)) {
                    return $exactPath;
                }
                
                // If exact match fails, try case-insensitive search
                $files = scandir($directory);
                $targetFilename = strtolower($filename);
                
                foreach ($files as $file) {
                    if (strtolower($file) === $targetFilename) {
                        return $directory . '/' . $file;
                    }
                }
                
                return null; // File not found
            }
            
            $pdfPath = findCaseInsensitiveFile("Cadman_catalog", $currentFile);
            if ($pdfPath && file_exists($pdfPath)):
            ?>
                <!-- PDF.js Embedded Viewer -->
                <div class="pdf-header">
                    <h2><?= htmlspecialchars($currentData['title']) ?></h2>
                    <div class="pdf-meta">
                        <?= htmlspecialchars($currentData['category']) ?> • 
                        <?= round(filesize($pdfPath) / 1024 / 1024, 1) ?>MB
                        <span class="pdf-controls">
                            <button onclick="downloadCurrentPdf()" class="control-btn secondary" title="Download PDF">
                                📥 Download
                            </button>
                            <button onclick="openPdfInNewTab()" class="control-btn secondary" title="Open in New Tab">
                                🔗 Open
                            </button>
                        </span>
                    </div>
                </div>
                
                <div class="pdf-viewer-wrapper">
                    <div id="pdf-loading" class="pdf-loading">
                        <div class="loading-spinner"></div>
                        <p>Loading PDF...</p>
                    </div>
                    
                    <!-- Direct PDF.js implementation without iframe -->
                    <div id="pdf-container" style="display: none;">
                        <div class="pdf-controls-bar">
                            <button id="zoom-out">Zoom Out</button>
                            <span id="zoom-display">100%</span>
                            <button id="zoom-in">Zoom In</button>
                            <button id="fit-width">Fit Width</button>
                        </div>
                        <canvas id="pdf-canvas" style="border: 1px solid #ccc; max-width: 100%;"></canvas>
                    </div>
                    
                    <script type="module">
                        import { getDocument, GlobalWorkerOptions } from './assets/pdfjs/build/pdf.mjs';
                        
                        // Set PDF.js worker
                        GlobalWorkerOptions.workerSrc = './assets/pdfjs/build/pdf.worker.mjs';
                        
                        let pdfDoc = null;
                        let pageNum = 1;
                        let pageRendering = false;
                        let pageNumPending = null;
                        let scale = 1.0;
                        let fitScale = 1.0; // Scale that fits container width
                        let panX = 0, panY = 0; // Pan offset for high zoom
                        let isDragging = false;
                        let dragStartX = 0, dragStartY = 0;
                        let dragStartPanX = 0, dragStartPanY = 0;
                        
                        const canvas = document.getElementById('pdf-canvas');
                        if (!canvas) {
                            console.warn('pdf-canvas element missing; skipping PDF render');
                        } else {
                        const ctx = canvas.getContext('2d');
                        const pdfPath = '<?= htmlspecialchars($pdfPath) ?>';
                        
                        console.log('Loading PDF:', pdfPath);
                        
                        // Load PDF
                        getDocument(pdfPath).promise.then(function(pdf) {
                            console.log('PDF loaded successfully, pages:', pdf.numPages);
                            pdfDoc = pdf;
                            // Single page PDFs, no need for page counter
                            
                            // Calculate initial scale to fit width - with retry for container dimensions
                            function calculateAndRenderInitial(retryCount = 0) {
                                pdf.getPage(1).then(function(firstPage) {
                                    const container = document.querySelector('.pdf-viewer-wrapper');
                                    
                                    // Force browser to recalculate layout
                                    void container.offsetHeight; // Trigger reflow
                                    
                                    let containerWidth = container.clientWidth - 40;
                                    let containerHeight = container.clientHeight - 80;
                                    const viewport = firstPage.getViewport({ scale: 1.0 });
                                    
                                    console.log('📏 Initial - Container:', containerWidth, 'x', containerHeight, 'PDF:', viewport.width, 'x', viewport.height);
                                    console.log('📏 Window:', window.innerWidth, 'x', window.innerHeight);
                                    
                                    // Use window dimensions as fallback on mobile or if container is invalid
                                    const isMobile = window.innerWidth <= 768;
                                    if (containerWidth <= 0 || containerHeight <= 0 || isMobile) {
                                        console.log('📱 Using window-based dimensions (mobile or invalid container)');
                                        containerWidth = window.innerWidth - 80;
                                        containerHeight = window.innerHeight - 200;
                                        console.log('📱 Fallback dimensions:', containerWidth, 'x', containerHeight);
                                    }
                                    
                                    // Validate final dimensions
                                    if (containerWidth <= 0 || containerHeight <= 0) {
                                        console.warn(`⚠️ Invalid dimensions: ${containerWidth}x${containerHeight}`);
                                        
                                        if (retryCount < 5) {
                                            console.log(`Retrying in 300ms... (attempt ${retryCount + 1}/5)`);
                                            setTimeout(() => calculateAndRenderInitial(retryCount + 1), 300);
                                            return;
                                        } else {
                                            console.error('❌ Failed after 5 retries, using absolute fallback');
                                            containerWidth = Math.max(300, viewport.width * 0.5);
                                            containerHeight = Math.max(400, viewport.height * 0.5);
                                        }
                                    }
                                    
                                    // Calculate fit scale (85% of container to leave margins)
                                    fitScale = Math.min(
                                        (containerWidth * 0.85) / viewport.width,
                                        (containerHeight * 0.85) / viewport.height
                                    );
                                    
                                    // Validate fitScale
                                    if (fitScale <= 0 || !isFinite(fitScale)) {
                                        console.error('❌ Invalid fitScale calculated:', fitScale, '- using fallback 0.5');
                                        fitScale = 0.5;
                                    }
                                    
                                    scale = fitScale;
                                    
                                    console.log('✅ Fit scale calculated:', fitScale, 'initial scale:', scale);
                                    
                                    // Hide loading, show PDF
                                    document.getElementById('pdf-loading').style.display = 'none';
                                    document.getElementById('pdf-container').style.display = 'block';
                                    
                                    // Update zoom display to show actual initial scale
                                    updateZoom();
                                    
                                    renderPage(pageNum);
                                });
                            }
                            
                            // Start the initial calculation
                            calculateAndRenderInitial();
                        }).catch(function(error) {
                            console.error('Error loading PDF:', error);
                            document.getElementById('pdf-loading').innerHTML = 
                                '<div style="text-align: center; color: #666;">' +
                                '<h3>Error Loading PDF</h3>' +
                                '<p>Could not load: ' + pdfPath + '</p>' +
                                '<p><a href="' + pdfPath + '" target="_blank">Click here to open PDF directly</a></p>' +
                                '</div>';
                        });
                        
                        function renderPage(num) {
                            console.log('🎨 Rendering page:', num, 'with scale:', scale, 'fitScale:', fitScale);
                            
                            // Validate scale before rendering
                            if (scale <= 0 || !isFinite(scale)) {
                                console.error('❌ Invalid scale:', scale, '- resetting to fitScale');
                                scale = fitScale > 0 ? fitScale : 0.5;
                            }
                            
                            if (pageRendering) {
                                pageNumPending = num;
                                console.log('⏳ Page render in progress, queuing page:', num);
                                return;
                            }
                            
                            pageRendering = true;
                            console.log('🔒 Page rendering locked');
                            
                            pdfDoc.getPage(num).then(function(page) {
                                const viewport = page.getViewport({ scale: scale });
                                
                                console.log('📐 Viewport calculated:', viewport.width, 'x', viewport.height, 'at scale:', scale);
                                
                                // Validate viewport dimensions
                                if (viewport.width <= 0 || viewport.height <= 0 || !isFinite(viewport.width) || !isFinite(viewport.height)) {
                                    console.error('❌ Invalid viewport dimensions:', viewport.width, 'x', viewport.height);
                                    pageRendering = false;
                                    return;
                                }
                                
                                // Determine if we should show full page or crop it
                                const isZoomedIn = scale > fitScale * 1.1; // 10% tolerance
                                
                                if (isZoomedIn) {
                                    // High zoom: show cropped view at container size
                                    const container = document.querySelector('.pdf-viewer-wrapper');
                                    const maxWidth = container.clientWidth - 40;
                                    const maxHeight = container.clientHeight - 40;
                                    
                                    canvas.width = Math.max(100, Math.min(viewport.width, maxWidth));
                                    canvas.height = Math.max(100, Math.min(viewport.height, maxHeight));
                                    
                                    console.log('🔍 Cropped view - Canvas:', canvas.width, 'x', canvas.height, 'Viewport:', viewport.width, 'x', viewport.height);
                                    
                                    // Clear canvas completely
                                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                                    
                                    // Create transform for cropping
                                    ctx.save();
                                    ctx.translate(-panX, -panY);
                                    
                                    const renderContext = {
                                        canvasContext: ctx,
                                        viewport: viewport
                                    };
                                    
                                    const renderTask = page.render(renderContext);
                                    renderTask.promise.then(function() {
                                        ctx.restore();
                                        pageRendering = false;
                                        if (pageNumPending !== null) {
                                            renderPage(pageNumPending);
                                            pageNumPending = null;
                                        }
                                        console.log('✅ Cropped page rendered');
                                    }).catch(function(err) {
                                        console.error('❌ Render error (cropped):', err);
                                        pageRendering = false;
                                    });
                                    
                                } else {
                                    // Normal zoom: show full page
                                    canvas.width = Math.max(100, viewport.width);
                                    canvas.height = Math.max(100, viewport.height);
                                    
                                    console.log('📄 Full view - Canvas size:', canvas.width, 'x', canvas.height);
                                    
                                    // Validate canvas dimensions before rendering
                                    if (canvas.width <= 0 || canvas.height <= 0) {
                                        console.error('❌ Invalid canvas dimensions:', canvas.width, 'x', canvas.height);
                                        pageRendering = false;
                                        return;
                                    }
                                    
                                    // Clear canvas completely
                                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                                    
                                    // Remove problematic inline styles
                                    canvas.style.maxWidth = 'none';
                                    canvas.style.height = 'auto';
                                    
                                    const renderContext = {
                                        canvasContext: ctx,
                                        viewport: viewport
                                    };
                                    
                                    const renderTask = page.render(renderContext);
                                    renderTask.promise.then(function() {
                                        pageRendering = false;
                                        if (pageNumPending !== null) {
                                            renderPage(pageNumPending);
                                            pageNumPending = null;
                                        }
                                        console.log('✅ Full page rendered');
                                    }).catch(function(err) {
                                        console.error('❌ Render error (full):', err);
                                        pageRendering = false;
                                    });
                                }
                            });
                            
                            // Single page PDFs, no page navigation needed
                        }
                        
                        function updateZoom() {
                            console.log('Updating zoom to:', scale);
                            // Update zoom display
                            const zoomPercent = Math.round(scale * 100);
                            const zoomDisplay = document.getElementById('zoom-display');
                            if (zoomDisplay) {
                                zoomDisplay.textContent = zoomPercent + '%';
                            }
                            
                            // Update cursor based on zoom level
                            updateCursor();
                            
                            // Re-render current page with new scale
                            if (pdfDoc) {
                                renderPage(pageNum);
                            }
                        }
                        
                        // Event listeners (removed page navigation for single-page PDFs)
                        document.getElementById('zoom-out').addEventListener('click', function() {
                            console.log('Zoom out clicked, current scale:', scale);
                            if (scale <= 0.1) return;
                            scale = scale * 0.9; // Smoother: 10% reduction
                            
                            // Reset pan when zooming out below fit scale
                            if (scale <= fitScale) {
                                panX = 0;
                                panY = 0;
                            }
                            
                            updateZoom();
                        });
                        
                        document.getElementById('zoom-in').addEventListener('click', function() {
                            console.log('Zoom in clicked, current scale:', scale);
                            if (scale >= 8) return; // Higher max zoom for detail viewing
                            scale = scale * 1.1; // Smoother: 10% increase
                            updateZoom();
                        });
                        
                        document.getElementById('fit-width').addEventListener('click', function() {
                            console.log('Fit width clicked');
                            if (!pdfDoc) return;
                            
                            // Calculate scale to fit width
                            const container = document.querySelector('.pdf-viewer-wrapper');
                            const containerWidth = container.clientWidth - 40; // Account for padding
                            
                            pdfDoc.getPage(pageNum).then(function(page) {
                                const viewport = page.getViewport({ scale: 1.0 });
                                scale = (containerWidth * 0.9) / viewport.width;
                                console.log('Fit width scale calculated:', scale);
                                if (scale > 5) scale = 5; // Max zoom limit
                                if (scale < 0.3) scale = 0.3; // Min zoom limit
                                
                                // Reset pan when fitting width
                                panX = 0;
                                panY = 0;
                                
                                updateZoom();
                            });
                        });

                        // Mouse panning functionality
                        canvas.addEventListener('mousedown', function(e) {
                            // Only enable panning when zoomed beyond fit scale
                            if (scale <= fitScale * 1.1) return;
                            
                            isDragging = true;
                            dragStartX = e.clientX;
                            dragStartY = e.clientY;
                            dragStartPanX = panX;
                            dragStartPanY = panY;
                            
                            canvas.style.cursor = 'grabbing';
                            e.preventDefault();
                        });

                        document.addEventListener('mousemove', function(e) {
                            if (!isDragging) return;
                            
                            const deltaX = e.clientX - dragStartX;
                            const deltaY = e.clientY - dragStartY;
                            
                            panX = dragStartPanX - deltaX;
                            panY = dragStartPanY - deltaY;
                            
                            // Limit panning to keep content visible
                            if (pdfDoc) {
                                pdfDoc.getPage(pageNum).then(function(page) {
                                    const viewport = page.getViewport({ scale: scale });
                                    const container = document.querySelector('.pdf-viewer-wrapper');
                                    const maxWidth = container.clientWidth - 40;
                                    const maxHeight = container.clientHeight - 40;
                                    
                                    // Limit pan to prevent showing empty space
                                    const maxPanX = Math.max(0, viewport.width - maxWidth);
                                    const maxPanY = Math.max(0, viewport.height - maxHeight);
                                    
                                    panX = Math.max(0, Math.min(panX, maxPanX));
                                    panY = Math.max(0, Math.min(panY, maxPanY));
                                    
                                    renderPage(pageNum);
                                });
                            }
                        });

                        document.addEventListener('mouseup', function(e) {
                            if (!isDragging) return;
                            
                            isDragging = false;
                            canvas.style.cursor = scale > fitScale * 1.1 ? 'grab' : 'default';
                        });

                        // Mouse wheel zoom functionality
                        canvas.addEventListener('wheel', function(e) {
                            e.preventDefault(); // Prevent page scroll
                            
                            const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1; // Scroll down = zoom out, scroll up = zoom in
                            const newScale = scale * zoomFactor;
                            
                            // Apply zoom limits
                            if (newScale >= 0.1 && newScale <= 8) {
                                scale = newScale;
                                
                                // Reset pan when zooming out below fit scale
                                if (scale <= fitScale) {
                                    panX = 0;
                                    panY = 0;
                                }
                                
                                updateZoom();
                            }
                        });

                        // Update cursor based on zoom level
                        function updateCursor() {
                            if (scale > fitScale * 1.1) {
                                canvas.style.cursor = 'grab';
                            } else {
                                canvas.style.cursor = 'default';
                            }
                        }

                        // Add mobile touch gesture support for PDF interaction
                        if (typeof window !== 'undefined' && window.innerWidth <= 768) {
                            console.log('📱 Setting up mobile PDF touch gestures for canvas');
                            
                            // Touch gesture variables
                            let touchStartX = 0, touchStartY = 0;
                            let touchStartPanX = 0, touchStartPanY = 0;
                            let initialDistance = 0;
                            let initialScale = 1;
                            let isTouchPanning = false;
                            let isPinching = false;
                            
                            // Touch start event
                            canvas.addEventListener('touchstart', function(e) {
                                console.log('🎯 PDF Canvas touchstart:', e.touches.length, 'touches');
                                
                                if (e.touches.length === 1) {
                                    // Single touch - start panning only if zoomed in
                                    if (scale > fitScale * 1.1) {
                                        e.preventDefault(); // Prevent scrolling only when panning
                                        console.log('📱 Starting single-touch pan at scale:', scale);
                                        isTouchPanning = true;
                                        touchStartX = e.touches[0].clientX;
                                        touchStartY = e.touches[0].clientY;
                                        touchStartPanX = panX;
                                        touchStartPanY = panY;
                                    } else {
                                        console.log('📜 Not zoomed - allowing native scroll');
                                    }
                                } else if (e.touches.length === 2) {
                                    // Two touches - start pinching
                                    e.preventDefault(); // Prevent zooming on pinch
                                    console.log('🤏 Starting pinch gesture');
                                    isPinching = true;
                                    isTouchPanning = false;
                                    
                                    // Calculate initial distance between fingers
                                    const touch1 = e.touches[0];
                                    const touch2 = e.touches[1];
                                    initialDistance = Math.sqrt(
                                        Math.pow(touch2.clientX - touch1.clientX, 2) + 
                                        Math.pow(touch2.clientY - touch1.clientY, 2)
                                    );
                                    initialScale = scale;
                                    console.log('🤏 Initial pinch distance:', initialDistance, 'scale:', initialScale);
                                }
                            }, { passive: false });
                            
                            // Touch move event
                            canvas.addEventListener('touchmove', function(e) {
                                
                                if (isTouchPanning && e.touches.length === 1) {
                                    // Single touch panning - prevent scroll only when actively panning
                                    e.preventDefault();
                                    const deltaX = e.touches[0].clientX - touchStartX;
                                    const deltaY = e.touches[0].clientY - touchStartY;
                                    
                                    panX = touchStartPanX - deltaX;
                                    panY = touchStartPanY - deltaY;
                                    
                                    // Limit panning to keep content visible
                                    if (pdfDoc) {
                                        pdfDoc.getPage(pageNum).then(function(page) {
                                            const viewport = page.getViewport({ scale: scale });
                                            const container = document.querySelector('.pdf-viewer-wrapper');
                                            const maxWidth = container.clientWidth - 40;
                                            const maxHeight = container.clientHeight - 40;
                                            
                                            const maxPanX = Math.max(0, viewport.width - maxWidth);
                                            const maxPanY = Math.max(0, viewport.height - maxHeight);
                                            
                                            panX = Math.max(0, Math.min(panX, maxPanX));
                                            panY = Math.max(0, Math.min(panY, maxPanY));
                                            
                                            renderPage(pageNum);
                                        });
                                    }
                                    
                                    console.log('📱 Touch panning - deltaX:', deltaX, 'deltaY:', deltaY, 'panX:', panX, 'panY:', panY);
                                    
                                } else if (isPinching && e.touches.length === 2) {
                                    // Two-finger pinch zoom - prevent default zoom
                                    e.preventDefault();
                                    const touch1 = e.touches[0];
                                    const touch2 = e.touches[1];
                                    const currentDistance = Math.sqrt(
                                        Math.pow(touch2.clientX - touch1.clientX, 2) + 
                                        Math.pow(touch2.clientY - touch1.clientY, 2)
                                    );
                                    
                                    // Calculate scale change
                                    const scaleChange = currentDistance / initialDistance;
                                    const newScale = initialScale * scaleChange;
                                    
                                    // Apply zoom limits
                                    if (newScale >= 0.1 && newScale <= 8) {
                                        scale = newScale;
                                        
                                        // Reset pan when zooming out below fit scale
                                        if (scale <= fitScale) {
                                            panX = 0;
                                            panY = 0;
                                        }
                                        
                                        updateZoom();
                                        console.log('🤏 Pinch zoom - distance:', currentDistance, 'scale:', scale);
                                    }
                                }
                            }, { passive: false });
                            
                            // Touch end event
                            canvas.addEventListener('touchend', function(e) {
                                console.log('🎯 PDF Canvas touchend, remaining touches:', e.touches.length);
                                
                                if (e.touches.length === 0) {
                                    // All touches ended
                                    isTouchPanning = false;
                                    isPinching = false;
                                    console.log('📱 All touches ended - resetting gesture states');
                                } else if (e.touches.length === 1 && isPinching) {
                                    // Went from 2 touches to 1 - switch to panning if allowed
                                    isPinching = false;
                                    if (scale > fitScale * 1.1) {
                                        console.log('📱 Switching from pinch to pan');
                                        isTouchPanning = true;
                                        touchStartX = e.touches[0].clientX;
                                        touchStartY = e.touches[0].clientY;
                                        touchStartPanX = panX;
                                        touchStartPanY = panY;
                                    }
                                }
                            }, { passive: false });
                            
                            console.log('✅ Mobile PDF touch gestures initialized for canvas element');
                        } else {
                            console.log('🖥️ Desktop detected - using mouse controls only');
                        }
                        
                        // Handle orientation change and window resize
                        let resizeTimeout;
                        const handleOrientationChange = function() {
                            clearTimeout(resizeTimeout);
                            resizeTimeout = setTimeout(() => {
                                if (!pdfDoc) return;
                                
                                console.log('📱 Orientation/resize detected, recalculating layout...');
                                
                                // Recalculate fitScale based on new container dimensions with retry
                                function recalculateScale(retryCount = 0) {
                                    pdfDoc.getPage(pageNum).then(function(page) {
                                        const container = document.querySelector('.pdf-viewer-wrapper');
                                        
                                        // Force reflow
                                        void container.offsetHeight;
                                        
                                        let containerWidth = container.clientWidth - 40;
                                        let containerHeight = container.clientHeight - 80;
                                        const viewport = page.getViewport({ scale: 1.0 });
                                        
                                        console.log('📱 Resize - Container:', containerWidth, 'x', containerHeight);
                                        console.log('📱 Resize - Window:', window.innerWidth, 'x', window.innerHeight);
                                        
                                        // Use window dimensions as fallback on mobile or if container is invalid
                                        const isMobile = window.innerWidth <= 768;
                                        if (containerWidth <= 0 || containerHeight <= 0 || isMobile) {
                                            console.log('📱 Using window-based dimensions for resize');
                                            containerWidth = window.innerWidth - 80;
                                            containerHeight = window.innerHeight - 200;
                                            console.log('📱 Fallback dimensions:', containerWidth, 'x', containerHeight);
                                        }
                                        
                                        // Validate dimensions
                                        if (containerWidth <= 0 || containerHeight <= 0) {
                                            console.warn(`⚠️ Invalid dimensions after resize: ${containerWidth}x${containerHeight}`);
                                            
                                            if (retryCount < 3) {
                                                console.log(`Retrying in 300ms... (attempt ${retryCount + 1}/3)`);
                                                setTimeout(() => recalculateScale(retryCount + 1), 300);
                                                return;
                                            } else {
                                                console.error('❌ Failed after resize retries');
                                                return;
                                            }
                                        }
                                        
                                        // Calculate new fit scale (85% to match initial load)
                                        const newFitScale = Math.min(
                                            (containerWidth * 0.85) / viewport.width,
                                            (containerHeight * 0.85) / viewport.height
                                        );
                                        
                                        // Validate newFitScale
                                        if (newFitScale <= 0 || !isFinite(newFitScale)) {
                                            console.error('❌ Invalid newFitScale calculated:', newFitScale);
                                            return;
                                        }
                                        
                                        // If we were at fit scale, update to new fit scale
                                        // Otherwise, maintain relative zoom level
                                        const wasAtFit = Math.abs(scale - fitScale) < 0.01;
                                        
                                        if (wasAtFit) {
                                            scale = newFitScale;
                                            console.log('📱 Resetting to new fit scale:', scale);
                                        } else {
                                            // Maintain relative zoom ratio
                                            const zoomRatio = scale / fitScale;
                                            scale = newFitScale * zoomRatio;
                                            console.log('📱 Maintaining zoom ratio:', zoomRatio, 'new scale:', scale);
                                        }
                                        
                                        fitScale = newFitScale;
                                        
                                        // Reset pan when going back to fit scale
                                        if (scale <= fitScale * 1.1) {
                                            panX = 0;
                                            panY = 0;
                                        }
                                        
                                        // Update zoom display and re-render
                                        updateZoom();
                                        renderPage(pageNum);
                                        console.log('✅ Re-rendered after orientation change');
                                    });
                                }
                                
                                recalculateScale();
                            }, 300); // Debounce to avoid multiple rapid re-renders
                        };
                        
                        // Listen for both orientationchange and resize events
                        window.addEventListener('orientationchange', handleOrientationChange);
                        window.addEventListener('resize', handleOrientationChange);
                        
                        console.log('✅ Orientation change and resize handlers installed');
                        
                        // Force browser to not cache this script
                        console.log('PDF Viewer script loaded at:', new Date().toISOString());
                        } // end if (canvas)
                    </script>
                </div>
                
                <!-- Hidden data for navigation -->
                <script>
                    window.currentPdfData = {
                        file: "<?= htmlspecialchars($currentFile) ?>",
                        path: "<?= htmlspecialchars($pdfPath) ?>",
                        title: "<?= htmlspecialchars($currentData['title']) ?>",
                        section: "<?= htmlspecialchars($currentSection) ?>",
                        prevSection: "<?= htmlspecialchars($prevSection ?? '') ?>",
                        nextSection: "<?= htmlspecialchars($nextSection ?? '') ?>"
                    };
                </script>
            <?php else: ?>
                <div class="pdf-preview">
                    <div class="pdf-icon">⚠️</div>
                    <h2>File Not Found</h2>
                    <p style="color: #666; margin-top: 10px;">
                        The requested catalog page could not be located.
                    </p>
                    <p style="opacity: 0.7; margin: 20px 0;">File: <?= htmlspecialchars($currentFile) ?></p>
                    <a href="?section=main" class="primary-btn">Return to Main Catalog</a>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Default welcome screen -->
        <div class="catalog-welcome">
            <h2>🏭 Welcome to the Cadman Catalog</h2>
            <p>Use the search box above or browse Product ID indexes to find specific catalog pages with product images.</p>
            <div class="pdf-actions">
                <button class="primary-btn" onclick="showAllPages()">📄 Browse All Pages</button>
                <button class="primary-btn" onclick="showByCategory()">� Browse Product ID Indexes</button>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                <h3 style="color: #8B4513; margin-bottom: 15px;">💡 Quick Search Tips</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px; color: #6c757d;">
                    <div><strong>"page 3"</strong> - Find Page 3 product images</div>
                    <div><strong>"wedding"</strong> - Find wedding product catalog pages</div>
                    <div><strong>"rings"</strong> - Find ring collection images</div>
                    <div><strong>"engagement"</strong> - Find engagement product pages</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Create mobile-friendly console display
        function createMobileConsole() {
            const consoleDiv = document.createElement('div');
            consoleDiv.id = 'mobile-console';
            consoleDiv.style.cssText = `
                position: fixed;
                top: 10px;
                left: 10px;
                right: 10px;
                background: rgba(0,0,0,0.9);
                color: #00ff00;
                font-family: monospace;
                font-size: 12px;
                padding: 10px;
                border-radius: 5px;
                max-height: 200px;
                overflow-y: auto;
                z-index: 10000;
                display: none;
            `;
            document.body.appendChild(consoleDiv);
            
            // Add toggle button (hidden by default)
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = '📱 Debug';
            toggleBtn.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 10001;
                background: #007bff;
                color: white;
                border: none;
                padding: 10px;
                border-radius: 5px;
                font-size: 14px;
                display: none;
            `;
            toggleBtn.onclick = () => {
                const console = document.getElementById('mobile-console');
                console.style.display = console.style.display === 'none' ? 'block' : 'none';
            };
            document.body.appendChild(toggleBtn);
            
            // Override console.log
            const originalLog = console.log;
            console.log = function(...args) {
                originalLog.apply(console, args);
                const logDiv = document.getElementById('mobile-console');
                if (logDiv) {
                    logDiv.innerHTML += args.join(' ') + '<br>';
                    logDiv.scrollTop = logDiv.scrollHeight;
                }
            };
        }
        
        // Create console when DOM loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', createMobileConsole);
        } else {
            createMobileConsole();
        }
        
        // Test if JavaScript is running at all
        console.log('🚀 JavaScript started on catalog_direct.php');
        
        // Test if this is mobile
        if (window.innerWidth <= 768) {
            console.log('📱 Mobile device detected, width:', window.innerWidth);
            
            // Add a simple test to any PDF container that gets created
            setTimeout(() => {
                const pdfContainers = document.querySelectorAll('.pdf-preview-container');
                console.log('🔍 Found', pdfContainers.length, 'PDF containers');
                
                // Just log containers, don't add conflicting touch events
                pdfContainers.forEach((container, index) => {
                    console.log(`📋 Container ${index}:`, container.id);
                });
                
                // Also test for any canvas elements
                const canvases = document.querySelectorAll('canvas');
                console.log('🎨 Found', canvases.length, 'canvas elements');
                
                canvases.forEach((canvas, index) => {
                    console.log(`🖼️ Canvas ${index}:`, canvas.id || 'no-id', canvas.width + 'x' + canvas.height);
                });
            }, 3000); // Wait 3 seconds for PDFs to load
            
        } else {
            console.log('💻 Desktop device detected, width:', window.innerWidth);
        }
        
        // Prevent browser zoom on mobile devices globally for catalog_direct
        if (typeof window !== 'undefined' && window.innerWidth <= 768) {
            console.log('🚫 Setting up global browser zoom prevention for mobile');
            
            // Add a simple test for ANY touch on the page
            document.addEventListener('touchstart', function(e) {
                console.log('📱 DOCUMENT TOUCH DETECTED:', e.touches.length, 'touches at', e.touches[0]?.clientX, e.touches[0]?.clientY);
            });
            
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
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(e) {
                const now = new Date().getTime();
                if (now - lastTouchEnd <= 300) {
                    console.log('🚫 Preventing double-tap browser zoom');
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, { passive: false, capture: true });
        }
        
        // PDF Support Detection and Browser Compatibility Check
        (function() {
            // Function to test PDF support
            function testPDFSupport() {
                return new Promise((resolve) => {
                    // Method 1: Check if PDF plugin/viewer is available
                    let pdfSupported = false;
                    
                    // Check for PDF MIME type support
                    if (navigator.mimeTypes && navigator.mimeTypes['application/pdf']) {
                        pdfSupported = true;
                    }
                    
                    // Check for PDF plugin (older browsers)
                    if (navigator.plugins) {
                        for (let i = 0; i < navigator.plugins.length; i++) {
                            if (navigator.plugins[i].name.toLowerCase().indexOf('pdf') !== -1) {
                                pdfSupported = true;
                                break;
                            }
                        }
                    }
                    
                    // Modern browser test - try to create object element
                    const testObj = document.createElement('object');
                    testObj.type = 'application/pdf';
                    testObj.width = '1px';
                    testObj.height = '1px';
                    testObj.style.visibility = 'hidden';
                    testObj.style.position = 'absolute';
                    testObj.data = 'data:application/pdf;base64,JVBERi0xLjcKCjEgMCBvYmoKPDwKL1R5cGUgL0NhdGFsb2cKL1BhZ2VzIDIgMCBSCj4+CmVuZG9iagoKMiAwIG9iago8PAovVHlwZSAvUGFnZXMKL0tpZHMgWzMgMCBSXQovQ291bnQgMQo+PgplbmRvYmoKCjMgMCBvYmoKPDwKL1R5cGUgL1BhZ2UKL1BhcmVudCAyIDAgUgovTWVkaWFCb3ggWzAgMCA2MTIgNzkyXQo+PgplbmRvYmoKCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAxNSAwMDAwMCBuIAowMDAwMDAwMDY2IDAwMDAwIG4gCjAwMDAwMDAxMjMgMDAwMDAgbiAKdHJhaWxlcgo8PAovU2l6ZSA0Ci9Sb290IDEgMCBSCj4+CnN0YXJ0eHJlZgoyMDIKJSVFT0YK';
                    
                    document.body.appendChild(testObj);
                    
                    setTimeout(() => {
                        // Check if object loaded properly
                        const isWorking = testObj.contentDocument || 
                                        testObj.getSVGDocument || 
                                        (testObj.offsetWidth > 1 && testObj.offsetHeight > 1);
                        
                        if (isWorking) {
                            pdfSupported = true;
                        }
                        
                        document.body.removeChild(testObj);
                        resolve(pdfSupported);
                    }, 1000);
                });
            }
            
            // Function to send support test result to server
            function sendSupportResult(supported) {
                // Mark as tested to prevent re-asking
                sessionStorage.setItem('pdf_support_tested', 'true');
                sessionStorage.setItem('pdf_support_result', supported);
                
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=pdf_support_test&supported=' + supported
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'unsupported' && data.redirect) {
                        // Redirect to fallback catalog
                        window.location.href = data.redirect + window.location.search;
                    }
                    // If supported or error, continue normally (do nothing)
                })
                .catch(error => {
                    console.error('PDF support test failed:', error);
                    // Don't call askUserDirectly here - user already chose
                });
            }
            
            // Function to ask user directly with alert box
            function askUserDirectly() {
                // Check if user has already been asked
                if (sessionStorage.getItem('pdf_user_asked') === 'true') {
                    return; // Don't ask again
                }
                
                // Mark as asked to prevent multiple prompts
                sessionStorage.setItem('pdf_user_asked', 'true');
                
                setTimeout(() => {
                    const userChoice = confirm(
                        "PDF Viewer Detection\n\n" +
                        "We couldn't automatically detect your browser's PDF viewing capabilities.\n\n" +
                        "Can your browser display PDF files directly?\n\n" +
                        "• Click 'OK' if PDFs usually open in your browser\n" +
                        "• Click 'Cancel' if PDFs download or you have issues viewing them\n\n" +
                        "(We'll optimize the catalog experience based on your choice)"
                    );
                    
                    if (userChoice) {
                        // User says their browser supports PDFs
                        sendSupportResult('true');
                    } else {
                        // User says their browser doesn't support PDFs well
                        sendSupportResult('false');
                    }
                }, 100);
            }
            
            // Will be called from main DOMContentLoaded handler
            function runPDFSupportTest() {
                // Don't run test for AJAX requests or if already tested
                if (sessionStorage.getItem('pdf_support_tested') === 'true') {
                    // Check if we need to redirect based on previous result
                    const previousResult = sessionStorage.getItem('pdf_support_result');
                    if (previousResult === 'false') {
                        window.location.href = 'catalog.php' + window.location.search;
                    }
                    return;
                }
                
                testPDFSupport().then(function(supported) {
                    if (supported === true) {
                        sendSupportResult('true');
                    } else if (supported === false) {
                        sendSupportResult('false');
                    } else {
                        // Uncertain result, ask user
                        askUserDirectly();
                    }
                }).catch(function(error) {
                    console.error('PDF support detection error:', error);
                    askUserDirectly();
                });
            }
        })();
        
        // PDF.js Navigation Functions
        function hidePdfLoading() {
            const loading = document.getElementById('pdf-loading');
            const viewer = document.getElementById('pdf-viewer');
            
            if (loading) {
                loading.style.display = 'none';
            }
            if (viewer) {
                viewer.style.display = 'block';
            }
        }

        function getPreferredDownloadName(pdfData) {
            if (!pdfData || !pdfData.file) {
                return (pdfData && pdfData.title ? pdfData.title : 'catalog-page') + '.pdf';
            }

            const fileMatch = String(pdfData.file).match(/^page_([^\\/]+)\.pdf$/i);
            if (fileMatch && fileMatch[1]) {
                return 'page ' + fileMatch[1] + '.pdf';
            }

            return String(pdfData.file).replace(/^.*[\\/]/, '');
        }

        function showCatalogToast(message, type = 'error', duration = 4000) {
            let container = document.getElementById('catalog-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'catalog-toast-container';
                container.className = 'catalog-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `catalog-toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);

            requestAnimationFrame(() => toast.classList.add('show'));

            const removeToast = () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                    if (container && !container.hasChildNodes() && container.parentNode) {
                        container.parentNode.removeChild(container);
                    }
                }, 220);
            };

            const timer = setTimeout(removeToast, duration);
            toast.addEventListener('click', () => {
                clearTimeout(timer);
                removeToast();
            });
        }
        
        function downloadCurrentPdf() {
            if (!window.currentPdfData || !window.currentPdfData.path) {
                console.error('Download blocked: missing PDF path in currentPdfData', window.currentPdfData);
                showCatalogToast('Unable to download this file right now. Please refresh and try again.', 'error');
                return;
            }

            const link = document.createElement('a');
            link.href = window.currentPdfData.path;
            link.download = getPreferredDownloadName(window.currentPdfData);
            link.click();
        }
        
        function openPdfInNewTab() {
            if (!window.currentPdfData || !window.currentPdfData.path) {
                console.error('Open in new tab blocked: missing PDF path in currentPdfData', window.currentPdfData);
                showCatalogToast('Unable to open this file right now. Please refresh and try again.', 'error');
                return;
            }

            window.open(window.currentPdfData.path, '_blank');
        }
        
        function loadPdfBySection(section) {
            if (!section) return;
            
            // Show loading state
            const viewer = document.getElementById('pdf-viewer');
            const loading = document.getElementById('pdf-loading');
            
            if (viewer) viewer.style.display = 'none';
            if (loading) loading.style.display = 'flex';
            
            // Navigate to new section
            window.location.href = '?section=' + encodeURIComponent(section);
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                if (!['input', 'textarea', 'select'].includes(e.target.tagName.toLowerCase())) {
                    e.preventDefault();
                    
                    // Always use the server-calculated navigation to ensure proper context
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
            
            // Additional PDF shortcuts
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'd':
                        e.preventDefault();
                        downloadCurrentPdf();
                        break;
                    case 'o':
                        e.preventDefault();
                        openPdfInNewTab();
                        break;
                }
            }
        });
        
        // Get DOM elements
        const searchInput = document.getElementById('searchInput');
        const showAllPagesBtn = document.getElementById('showAllPagesBtn');
        const showByCategoryBtn = document.getElementById('showByCategoryBtn');
        const showCurrentBtn = document.getElementById('showCurrentBtn');
        const backToMainBtn = document.getElementById('backToMainBtn');
        const backToHomeBtn = document.getElementById('backToHomeBtn');
        const mainContainer = document.getElementById('mainContainer');
        
        // Search functionality
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCatalog(this.value);
            }
        });
        
        // Button event listeners
        showAllPagesBtn.addEventListener('click', showAllPages);
        showByCategoryBtn.addEventListener('click', showByCategory);
        showCurrentBtn.addEventListener('click', showCurrentPage);
        backToMainBtn.addEventListener('click', backToMain);
        backToHomeBtn.addEventListener('click', backToHome);
        
        // Initialize the page - check for view parameter and auto-load appropriate view
        function initializeViewState() {
            const urlParams = new URLSearchParams(window.location.search);
            const viewType = urlParams.get('view');
            
            if (viewType === 'all') {
                // Auto-load All Pages view
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_catalog_structure'
                })
                .then(response => response.json())
                .then(data => {
                    showContentWithData(data, 'all');
                })
                .catch(error => {
                    console.error('Error loading catalog data:', error);
                });
            } else if (viewType === 'categories') {
                // Auto-load Product ID categories view
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_catalog_structure'
                })
                .then(response => response.json())
                .then(data => {
                    showContentWithData(data, 'category');
                })
                .catch(error => {
                    console.error('Error loading catalog data:', error);
                });
            }
        }
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.view) {
                if (e.state.view === 'all') {
                    showAllPages();
                } else if (e.state.view === 'categories') {
                    showByCategory();
                }
            } else {
                // No view state, reload to show normal page view  
                window.location.reload();
            }
        });
        
        // Initialize view state when page loads
        initializeViewState();
        
        // Show all pages function
        function showAllPages() {
            // Update URL to include view parameter
            const url = new URL(window.location);
            url.searchParams.set('view', 'all');
            window.history.pushState({view: 'all'}, '', url);
            
            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_catalog_structure'
            })
            .then(response => response.json())
            .then(data => {
                showContentWithData(data, 'all');
            })
            .catch(error => {
                console.error('Error loading catalog data:', error);
                showError('Failed to load catalog data');
            });
        }
        
        // Show Product ID indexes - helps users find specific catalog pages with product images
        function showByCategory() {
            // Update URL to include view parameter
            const url = new URL(window.location);
            url.searchParams.set('view', 'categories');
            window.history.pushState({view: 'categories'}, '', url);
            
            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_catalog_structure'
            })
            .then(response => response.json())
            .then(data => {
                showContentWithData(data, 'category');
            })
            .catch(error => {
                console.error('Error loading catalog data:', error);
                showError('Failed to load catalog data');
            });
        }
        
        // Show current page
        function showCurrentPage() {
            window.location.reload();
        }
        
        // Back to main page function
        function backToMain() {
            window.location.href = '?section=main';
        }
        
        // Clear view mode and return to normal page view
        function showCurrentPage() {
            const url = new URL(window.location);
            url.searchParams.delete('view');
            window.history.pushState({}, '', url);
            window.location.reload();
        }
        
        // Back to home page function
        function backToHome() {
            window.location.href = 'index.php';
        }
        
        // Search catalog function
        function searchCatalog(query) {
            if (!query.trim()) return;
            
            // Hide navigation immediately when search starts
            const catalogNav = document.querySelector('.catalog-nav');
            if (catalogNav) {
                catalogNav.style.display = 'none';
            }
            
            // Sanitize input
            let searchTerm = query.trim();
            
            // Length limit (reasonable for product IDs and page numbers)
            if (searchTerm.length > 50) {
                searchTerm = searchTerm.substring(0, 50);
            }
            
            // Character whitelist: alphanumeric, basic symbols, spaces
            searchTerm = searchTerm.replace(/[^a-zA-Z0-9\s\-_\.#]/g, '');
            
            // Final cleanup
            searchTerm = searchTerm.toLowerCase().trim();
            
            if (!searchTerm) {
                showError('Please enter a valid search term');
                return;
            }
            
            fetch(window.location.pathname, {
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
                console.log('Search response received:', data); // Debug log
                
                // Handle errors from backend
                if (data.error) {
                    showError(data.error);
                    return;
                }
                
                if (data.type === 'direct_page') {
                    // Instead of redirecting immediately, show it in search results
                    const combinedResult = {
                        catalog_details: data,
                        database_details: null
                    };
                    showDatabaseSearchResults(combinedResult, searchTerm);
                    
                } else if (data.type === 'database_match') {
                    // Show database search results with enhanced display
                    showDatabaseSearchResults(data, searchTerm);
                    
                } else if (data.type === 'keyword_match') {
                    // Handle keyword matches (like bracelets, medical, etc.)
                    console.log('Processing keyword match:', data); // Debug log
                    // Transform the data to match showSearchResults format
                    const transformedData = [{
                        category: data.description || data.section,
                        description: `Found ${data.indexes.length} index page(s) for ${data.section}`,
                        data: {
                            indexes: data.indexes,
                            content_pages: data.content_pages || []
                        }
                    }];
                    console.log('Transformed data:', transformedData); // Debug log
                    showSearchResults(transformedData, searchTerm);
                    
                } else if (data.length > 0) {
                    // Show search results
                    showSearchResults(data, searchTerm);
                } else {
                    showError('No results found for "' + searchTerm + '"');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Search failed. Please try again.');
            });
        }
        
        // Show page preview area for numeric searches
        function showPagePreviewArea(pageNumber) {
            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_catalog_structure'
            })
            .then(response => response.json())
            .then(data => {
                const matchingPages = [];
                
                // Find all pages that start with the page number
                if (data.content_pages) {
                    for (const [pageGroup, files] of Object.entries(data.content_pages)) {
                        files.forEach(fileData => {
                            const file = fileData.filename || fileData; // Handle both old and new format
                            // Match patterns like page_05a.pdf, page_5b.pdf for search "5"
                            const pageMatch = file.match(/page_0*(\d+)([a-z]*)/i);
                            if (pageMatch && pageMatch[1] === pageNumber) {
                                matchingPages.push({
                                    file: file,
                                    fileData: fileData,
                                    pageLabel: `Page ${pageNumber.toUpperCase()}${pageMatch[2] ? pageMatch[2].toUpperCase() : ''}`,
                                    suffix: pageMatch[2] || ''
                                });
                            }
                        });
                    }
                }
                
                if (matchingPages.length > 0) {
                    showPagePreviews(matchingPages, pageNumber);
                } else {
                    showError(`No pages found for Page ${pageNumber}`);
                }
            })
            .catch(error => {
                console.error('Error loading page previews:', error);
                showError('Failed to load page previews');
            });
        }
        
        // Show page previews in a grid
        function showPagePreviews(pages, pageNumber) {
            let content = '<div class="catalog-welcome">';
            content += `<h2>📄 Page ${pageNumber} Series</h2>`;
            content += `<p>Found ${pages.length} catalog page(s) with product images in the Page ${pageNumber} series. Click any preview to view:</p>`;
            content += '<div class="page-preview-grid">';
            
            pages.forEach(page => {
                const sectionKey = getSectionKeyForFile(page.file);
                const fileSize = (page.fileData && page.fileData.sizeFormatted) ? page.fileData.sizeFormatted : 'Unknown size';
                
                // Extract pageId from filename (e.g., "page_03a.pdf" -> "03a") 
                const pageIdMatch = page.file.match(/page_([0-9]+[a-z]*|[a-z]+)/i);
                const pageId = pageIdMatch ? pageIdMatch[1] : page.file.replace('.pdf', '').replace(/[^a-zA-Z0-9]/g, '_');
                
                content += `<div class="page-preview-card">`;
                content += `<div class="preview-header">`;
                content += `<div class="preview-icon">📄</div>`;
                content += `<h3><a href="?section=${sectionKey}" style="color: #007bff; text-decoration: none; cursor: pointer;" title="View ${page.pageLabel} in catalog with navigation">${page.pageLabel}</a></h3>`;
                content += `</div>`;
                content += `<div class="preview-info">`;
                content += `<p class="file-name">${page.file}</p>`;
                content += `<p class="file-size">${fileSize}</p>`;
                content += `</div>`;
                content += `<div class="preview-actions">`;
                content += `<a href="?section=${sectionKey}&from_search=1" class="preview-btn primary">📖 View Page</a>`;
                content += `<a href="Cadman_catalog/${page.file}" class="preview-btn secondary" target="_blank">🔗 Open PDF</a>`;
                content += `<a href="Cadman_catalog/${page.file}" class="preview-btn secondary" download>📥 Download</a>`;
                content += `</div>`;
                
                // Add PDF preview container using pageId for consistency
                content += `<div id="pdf-preview-${pageId}" style="margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">`;
                content += `<div style="background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd;">`;
                content += `<strong style="color: #333;">📄 ${page.pageLabel} Preview</strong>`;
                content += `</div>`;
                content += `<div class="pdf-viewer-wrapper" style="height: 600px; background: #f5f5f5; position: relative;">`;
                content += `<div class="pdf-loading" id="pdf-loading-${pageId}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666;">`;
                content += `<div style="font-size: 2em; margin-bottom: 10px;">📄</div>`;
                content += `<div>Loading PDF preview...</div>`;
                content += `</div>`;
                content += `<div class="pdf-container" id="pdf-container-${pageId}" style="display: none; height: 100%; overflow: auto;">`;
                content += `<div class="pdf-controls" style="background: #34495e; padding: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">`;
                content += `<button id="prev-page-${pageId}" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">‹ Prev</button>`;
                content += `<span id="page-info-${pageId}" style="color: white; margin: 0 15px;">Page 1 of 1</span>`;
                content += `<button id="next-page-${pageId}" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Next ›</button>`;
                content += `<button id="zoom-out-${pageId}" style="background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom Out</button>`;
                content += `<span id="zoom-display-${pageId}" style="color: white; margin: 0 10px;">100%</span>`;
                content += `<button id="zoom-in-${pageId}" style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom In</button>`;
                content += `<button id="fit-width-${pageId}" style="background: #9b59b6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Fit Width</button>`;
                content += `</div>`;
                content += `<canvas id="pdf-canvas-${pageId}" style="display: block; margin: 20px auto; max-height: calc(100% - 80px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); background: white; user-select: none;"></canvas>`;
                content += `</div>`;
                content += `</div>`;
                content += `</div>`;
                
                content += `</div>`;
            });
            
            content += '</div>';
            content += '<div style="margin-top: 30px; text-align: center;">';
            content += '<button onclick="showCurrentPage()" class="control-btn">🏠 Back to Current Page</button>';
            content += '<button onclick="showAllPages()" class="control-btn secondary">📄 Show All Pages</button>';
            content += '</div>';
            content += '</div>';
            
            mainContainer.innerHTML = content;
            
            // Hide navigation when page previews are displayed
            const catalogNav = document.querySelector('.catalog-nav');
            if (catalogNav) {
                catalogNav.style.display = 'none';
            }
            
            // Auto-load PDF previews after content is rendered
            setTimeout(() => {
                pages.forEach(page => {
                    // Extract pageId from filename (e.g., "page_03a.pdf" -> "03a")
                    const pageIdMatch = page.file.match(/page_([0-9]+[a-z]*|[a-z]+)/i);
                    const pageId = pageIdMatch ? pageIdMatch[1] : page.file.replace('.pdf', '').replace(/[^a-zA-Z0-9]/g, '_');
                    
                    console.log(`Loading preview for ${page.file} -> pageId: ${pageId}`);
                    initPDFPreview(pageId);
                });
            }, 100);
        }
        
        // Show content with data (all pages or categories)
        function showContentWithData(catalogData, displayType) {
            let content = '<div class="catalog-welcome">';
            
            if (displayType === 'all') {
                content += '<h2>📄 All Available Pages</h2>';
                content += '<p>Click on any page to view it:</p>';
                content += '<div class="category-grid">';
                
                // Show all content pages
                if (catalogData.content_pages) {
                    for (const [pageGroup, files] of Object.entries(catalogData.content_pages)) {
                        files.forEach(fileData => {
                            const file = fileData.filename || fileData; // Handle both old and new format
                            const pageLabel = file.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
                            const sectionKey = getSectionKeyForFile(file);
                            const fileSize = (fileData.sizeFormatted) ? ` (${fileData.sizeFormatted})` : '';
                            content += `<a href="?section=${sectionKey}" class="category-card">`;
                            content += `<h3>📄 ${pageLabel}</h3>`;
                            content += `<p>Click to view${fileSize}</p>`;
                            content += `</a>`;
                        });
                    }
                }
            } else if (displayType === 'category') {
                content += '<h2>� Product ID Indexes</h2>';
                content += '<p>Select a Product ID index to find specific catalog pages with product images:</p>';
                content += '<div class="category-grid">';
                
                // Show categories
                if (catalogData.indexes) {
                    for (const [category, fileData] of Object.entries(catalogData.indexes)) {
                        const file = fileData.filename || fileData; // Handle both old and new format
                        const sectionKey = getSectionKeyForFile(file);
                        const fileSize = (fileData.sizeFormatted) ? ` (${fileData.sizeFormatted})` : '';
                        content += `<a href="?section=${sectionKey}" class="category-card">`;
                        content += `<h3>� ${category}</h3>`;
                        content += `<p>Product ID Index${fileSize}</p>`;
                        content += `</a>`;
                    }
                }
            }
            
            content += '</div>';
            content += '<div style="margin-top: 30px; text-align: center;">';
            content += '<button onclick="showCurrentPage()" class="control-btn">🏠 Back to Current Page</button>';
            content += '</div>';
            content += '</div>';
            
            mainContainer.innerHTML = content;
        }
        
        // Show database search results with product details
        function showDatabaseSearchResults(result, searchTerm) {
            let content = '<div class="catalog-welcome">';
            content += `<h2>🔍 Product Search Results for "${searchTerm.toUpperCase()}"</h2>`;
            
            // Check for catalog page results first and show them with clean PDF preview
            if (result.catalog_details && result.catalog_details.type === 'direct_page' && result.catalog_details.files) {
                content += '<div style="margin: 10px 0;">';
                
                // Show all matching page files with clean layout
                result.catalog_details.files.forEach((file, index) => {
                    const pageLabel = file.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
                    const sectionKey = getSectionKeyForFile(file);
                    
                    // Extract pageId from filename (e.g., "page_03a.pdf" -> "03a")
                    const pageIdMatch = file.match(/page_([0-9]+[a-z]*|[a-z]+)/i);
                    const pageId = pageIdMatch ? pageIdMatch[1] : file.replace('.pdf', '').replace(/[^a-zA-Z0-9]/g, '_');
                    
                    content += `<div class="page-preview-card">`;
                    content += `<div class="preview-header">`;
                    content += `<div class="preview-icon">📄</div>`;
                    content += `<h3><a href="?section=${sectionKey}" style="color: #007bff; text-decoration: none; cursor: pointer;" title="View ${pageLabel} in catalog with navigation">${pageLabel}</a></h3>`;
                    content += `</div>`;

                    // Add PDF preview container using pageId for consistency  
                    content += `<div id="pdf-preview-${pageId}" style="margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">`;
                    content += `<div style="background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd;">`;
                    content += `<strong style="color: #333;">📄 ${pageLabel} Preview</strong>`;
                    content += `</div>`;
                    content += `<div class="pdf-viewer-wrapper" style="height: 600px; background: #f5f5f5; position: relative;">`;
                    content += `<div class="pdf-loading" id="pdf-loading-${pageId}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666;">`;
                    content += `<div style="font-size: 2em; margin-bottom: 10px;">📄</div>`;
                    content += `<div>Loading PDF preview...</div>`;
                    content += `</div>`;
                    content += `<div class="pdf-container" id="pdf-container-${pageId}" style="display: none; height: 100%; overflow: auto;">`;
                    content += `<div class="pdf-controls" style="background: #34495e; padding: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">`;
                    content += `<a href="Cadman_catalog/${file}" class="preview-btn secondary" download style="background: #9b59b6; color: white; border: none; padding: 8px 16px; border-radius: 4px; text-decoration: none;">📥 Download</a>`;
                    content += `<button id="zoom-out-${pageId}" style="background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom Out</button>`;
                    content += `<span id="zoom-display-${pageId}" style="color: white; margin: 0 10px;">100%</span>`;
                    content += `<button id="zoom-in-${pageId}" style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom In</button>`;
                    content += `<button id="fit-width-${pageId}" style="background: #9b59b6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Fit Width</button>`;
                    content += `</div>`;
                    content += `<canvas id="pdf-canvas-${pageId}" style="display: block; margin: 20px auto; max-height: calc(100% - 80px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); background: white; user-select: none;"></canvas>`;
                    content += `</div>`;
                    content += `</div>`;
                    content += `</div>`;
                    
                    content += `</div>`;
                });
                
                content += '</div>';
            }
            
            // Check if we found an exact match (either by ID or product name)
            let hasExactMatch = false;
            if (result.database_details && 
                ((result.database_details.exact && result.database_details.exact.length > 0) ||
                 (result.database_details.product_name_exact && result.database_details.product_name_exact.length > 0))) {
                hasExactMatch = true;
                if (result.database_details.exact && result.database_details.exact.length > 0) {
                    content += `<p style="color: #28a745; font-weight: bold;">✅ Found exact product: ${result.database_details.exact[0].product_id}</p>`;
                } else {
                    content += `<p style="color: #28a745; font-weight: bold;">✅ Found exact product name match: ${result.database_details.product_name_exact[0].product_name}</p>`;
                }
            } else if (result.database_details) {
                content += `<p style="color: #dc3545; font-weight: bold;">❌ Exact product "${searchTerm.toUpperCase()}" not found. Showing similar products:</p>`;
            }
            
            // Group products by PDF file and show them together
            if (result.database_details) {
                // First, collect all products and group by PDF file
                const productsByPdf = {};
                
                // Process in priority order: exact first, then variants, then others
                const priorityOrder = ['exact', 'variants', 'product_name_exact', 'regex_patterns', 'starts_with', 'product_name_partial', 'partial', 'category', 'specifications'];
                
                for (const resultType of priorityOrder) {
                    if (result.database_details[resultType]) {
                        result.database_details[resultType].forEach(product => {
                            // Use page_reference (modernized format) instead of pdf_file
                            const pdfFile = product.page_reference ? product.page_reference + '.pdf' : null;
                            if (pdfFile) {
                                if (!productsByPdf[pdfFile]) {
                                    productsByPdf[pdfFile] = {
                                        exact: [],
                                        variants: [],
                                        product_name_exact: [],
                                        starts_with: [],
                                        product_name_partial: [],
                                        partial: [],
                                        category: [],
                                        specifications: []
                                    };
                                }
                                productsByPdf[pdfFile][resultType].push(product);
                            }
                        });
                    }
                }
                
                content += '<div class="search-results">';
                if (hasExactMatch) {
                    content += '<h4 style="color: #333; margin: 15px 0 10px 0;">📄 Products Found</h4>';
                } else {
                    content += '<h4 style="color: #333; margin: 15px 0 10px 0;">📄 Similar Products</h4>';
                }
                
                // Show each PDF with its products, prioritizing those with exact matches
                const sortedPdfs = Object.entries(productsByPdf).sort(([, a], [, b]) => {
                    // PDFs with exact matches (ID or name) come first
                    const aHasExact = (a.exact.length > 0 || a.product_name_exact.length > 0);
                    const bHasExact = (b.exact.length > 0 || b.product_name_exact.length > 0);
                    
                    if (aHasExact && !bHasExact) return -1;
                    if (!aHasExact && bHasExact) return 1;
                    return 0;
                });
                
                // Limit to top 5 most relevant pages
                const limitedPdfs = sortedPdfs.slice(0, 5);
                
                console.log('Processing PDF files for search results:', limitedPdfs.map(([file, _]) => file));
                
                for (const [pdfFile, productGroups] of limitedPdfs) {
                    const pageLabel = pdfFile.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
                    const sectionKey = getSectionKeyForFile(pdfFile);
                    const cleanFileId = pdfFile.replace('.pdf', '').replace('page_', ''); // Clean ID for elements
                    
                    console.log(`Creating preview for file: ${pdfFile}, cleanId: ${cleanFileId}`);
                    
                    // Highlight exact match pages differently
                    const hasAnyExactMatch = (productGroups.exact.length > 0 || productGroups.product_name_exact.length > 0);
                    const borderColor = hasAnyExactMatch ? '#28a745' : '#ccc';
                    const bgColor = hasAnyExactMatch ? '#f8fff9' : '#ffffff';
                    
                    content += `<div style="background: ${bgColor}; border: 1px solid ${borderColor}; border-radius: 6px; margin: 10px 0; padding: 15px;">`;
                    content += `<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">`;
                    content += `<div>`;
                    
                    if (productGroups.exact.length > 0 || productGroups.product_name_exact.length > 0) {
                        content += `<h3 style="margin: 0; color: #28a745;"><a href="?section=${sectionKey}" style="color: #28a745; text-decoration: none; cursor: pointer;" title="View ${pageLabel} in catalog with navigation">🎯 ${pageLabel} - Your Exact Product Here!</a></h3>`;
                    } else {
                        content += `<h3 style="margin: 0; color: #2c5aa0;"><a href="?section=${sectionKey}" style="color: #2c5aa0; text-decoration: none; cursor: pointer;" title="View ${pageLabel} in catalog with navigation">📄 ${pageLabel} - Similar Products</a></h3>`;
                    }
                    
                    content += `</div>`;
                    content += `</div>`;
                    
                    // Add PDF preview container (visible by default)
                    content += `<div id="pdf-preview-${cleanFileId}" style="margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">`;
                    content += `<div style="background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd;">`;
                    content += `<strong style="color: #333;">📄 ${pageLabel} Preview</strong>`;
                    content += `</div>`;
                    content += `<div class="pdf-viewer-wrapper" style="height: 600px; background: #f5f5f5; position: relative;">`;
                    content += `<div class="pdf-loading" id="pdf-loading-${cleanFileId}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666;">`;
                    content += `<div style="font-size: 2em; margin-bottom: 10px;">📄</div>`;
                    content += `<div>Loading PDF preview...</div>`;
                    content += `</div>`;
                    content += `<div class="pdf-container" id="pdf-container-${cleanFileId}" style="display: none; height: 100%; overflow: auto;">`;
                    content += `<div class="pdf-controls" style="background: #34495e; padding: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">`;
                    content += `<button id="prev-page-${cleanFileId}" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">‹ Prev</button>`;
                    content += `<span id="page-info-${cleanFileId}" style="color: white; margin: 0 15px;">Page 1 of 1</span>`;
                    content += `<button id="next-page-${cleanFileId}" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Next ›</button>`;
                    content += `<button id="zoom-out-${cleanFileId}" style="background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom Out</button>`;
                    content += `<span id="zoom-display-${cleanFileId}" style="color: white; margin: 0 10px;">100%</span>`;
                    content += `<button id="zoom-in-${cleanFileId}" style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Zoom In</button>`;
                    content += `<button id="fit-width-${cleanFileId}" style="background: #9b59b6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Fit Width</button>`;
                    content += `</div>`;
                    content += `<canvas id="pdf-canvas-${cleanFileId}" style="display: block; margin: 20px auto; max-height: calc(100% - 80px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); background: white; user-select: none;"></canvas>`;
                    content += `</div>`;
                    content += `</div>`;
                    content += `</div>`;
                    
                    // Show products in priority order
                    let hasProducts = false;
                    
                    for (const matchType of priorityOrder) {
                        const products = productGroups[matchType];
                        if (products && products.length > 0) {
                            hasProducts = true;
                            let typeLabel = matchType === 'exact' ? '🎯 Exact Match' :
                                           matchType === 'variants' ? '🔧 Material Variants' :
                                           matchType === 'product_name_exact' ? '🎯 Exact Name' :
                                           matchType === 'regex_patterns' ? '🔍 Pattern Match' :
                                           matchType === 'starts_with' ? '🔤 Similar' :
                                           matchType === 'product_name_partial' ? '🔤 Name Match' :
                                           matchType === 'partial' ? '🔍 Contains' :
                                           matchType === 'category' ? '📂 Category' :
                                           '📋 Specs';
                            
                            content += `<div style="margin-bottom: 10px;">`;
                            content += `<div style="font-weight: bold; color: #495057; margin-bottom: 5px; font-size: 0.9em;">${typeLabel}</div>`;
                            content += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 8px;">';
                            
                            // Show fewer similar products if we have exact matches
                            const maxShow = (matchType === 'exact' || matchType === 'variants' || matchType === 'product_name_exact' || matchType === 'regex_patterns') ? 5 : (hasExactMatch ? 6 : 12);
                            
                            products.slice(0, maxShow).forEach(product => {
                                const bgClass = (matchType === 'exact' || matchType === 'variants' || matchType === 'product_name_exact' || matchType === 'regex_patterns') ? 'white; border: 2px solid #28a745' : 'white; border: 1px solid #ddd';
                                
                                // Check if product has images for modal display
                                const hasImages = product.has_images && product.image_files && product.image_files !== 'no images found';
                                
                                if (hasImages) {
                                    // Product with images - show in horizontal layout with thumbnail and modal trigger
                                    content += `<div style="background: ${bgClass}; border-radius: 4px; padding: 12px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 15px;" onclick="(window.ProductModal && ProductModal.open) ? ProductModal.open('${product.product_id}') : (window.location.href='unified_detail.php?product='+encodeURIComponent('${product.product_id}'))">`;
                                    
                                    // Product thumbnail
                                    content += `<div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">`;
                                    content += `<img src="${product.image_files}" alt="${product.product_id}" style="max-width: 100%; max-height: 100%; object-fit: contain;" loading="lazy" onerror="this.style.display='none'; this.parentNode.innerHTML='<span style=color:#666;font-size:10px>📷</span>'">`;
                                    content += `</div>`;
                                    
                                    // Product details
                                    content += `<div style="flex: 1;">`;
                                    content += `<div style="font-weight: bold; color: #2c3e50; font-size: 1em; margin-bottom: 4px;">${product.product_id} 🔍</div>`;
                                    content += `<div style="color: #6c757d; font-size: 0.85em;">${product.category}</div>`;
                                    if (product.style && product.style.trim() !== '') {
                                        content += `<div style="color: #6c757d; font-size: 0.8em;">${product.style}</div>`;
                                    }
                                    if (product.pattern && product.pattern.trim() !== '') {
                                        content += `<div style="color: #8B4513; font-size: 0.8em; font-weight: 500;">${product.pattern}</div>`;
                                    }
                                    content += `<div style="color: #007bff; font-size: 0.75em; margin-top: 4px;">📷 Click for details</div>`;
                                    content += `</div>`;
                                } else {
                                    // Product without images - show in horizontal layout  
                                    content += `<div style="background: ${bgClass}; border-radius: 4px; padding: 12px; display: flex; align-items: center; gap: 15px;">`;
                                    content += `<div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #999; font-size: 18px;">📦</div>`;
                                    content += `<div style="flex: 1;">`;
                                    content += `<div style="font-weight: bold; color: #2c3e50; font-size: 1em; margin-bottom: 4px;">${product.product_id}</div>`;
                                    content += `<div style="color: #6c757d; font-size: 0.85em;">${product.category}</div>`;
                                    if (product.style && product.style.trim() !== '') {
                                        content += `<div style="color: #6c757d; font-size: 0.8em;">${product.style}</div>`;
                                    }
                                    if (product.pattern && product.pattern.trim() !== '') {
                                        content += `<div style="color: #8B4513; font-size: 0.8em; font-weight: 500;">${product.pattern}</div>`;
                                    }
                                    content += `</div>`;
                                }
                                content += '</div>';
                            });
                            
                            content += '</div>';
                            
                            if (products.length > maxShow) {
                                content += `<div style="text-align: center; color: #666; font-style: italic; margin-top: 8px;">... and ${products.length - maxShow} more products</div>`;
                            }
                            
                            content += '</div>';
                        }
                    }
                    
                    if (!hasProducts) {
                        content += '<div style="color: #666; font-style: italic;">No products found in this page</div>';
                    }
                    
                    content += '</div>';
                }
                
                content += '</div>';
            } else {
                content += '<div style="text-align: center; color: #dc3545; font-size: 1.2em; margin: 40px 0;">';
                content += `<h3>❌ No products found for "${searchTerm.toUpperCase()}"</h3>`;
                content += '<p>Try searching for:</p>';
                content += '<ul style="text-align: left; display: inline-block;">';
                content += '<li>A different product number (e.g., "700TM", "4T72M")</li>';
                content += '<li>A page number (e.g., "23a", "5b")</li>';
                content += '<li>A category (e.g., "wedding", "rings")</li>';
                content += '</ul>';
                content += '</div>';
            }
            
            content += '<div style="margin-top: 30px; text-align: center;">';
            content += '<button onclick="showCurrentPage()" class="control-btn">🏠 Back to Current Page</button>';
            content += '</div>';
            content += '</div>';
            
            mainContainer.innerHTML = content;
            
            // Auto-load PDF previews after content is rendered with more debugging
            console.log('Content rendered, triggering auto-load of PDF previews...');
            setTimeout(() => {
                console.log('setTimeout callback executing...');
                autoLoadPDFPreviews();
            }, 500); // Increased timeout to ensure DOM is ready
        }
        
        // Helper function to capitalize result type
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        // Show search results
        function showSearchResults(results, searchTerm) {
            let content = '<div class="catalog-welcome">';
            content += `<h2>🔍 Search Results for "${searchTerm}"</h2>`;
            
            results.forEach(result => {
                content += '<div class="search-results">';
                content += `<h3>${result.category}</h3>`;
                content += `<p>${result.description}</p>`;
                content += '<div class="category-grid">';
                
                if (result.data.indexes && result.data.indexes.length > 0) {
                    result.data.indexes.forEach(file => {
                        const sectionKey = getSectionKeyForFile(file);
                        const displayName = file.replace('.pdf', '').replace('index_page_', '').replace(/_/g, ' ');
                        content += `<div class="category-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0;">`;
                        content += `<h3>📑 ${displayName}</h3>`;
                        content += `<p>Index Page</p>`;
                        content += `<div style="margin-top: 10px; display: flex; gap: 10px;">`;
                        content += `<a href="?section=${sectionKey}&from_search=1" style="text-decoration: none; background: #007bff; color: white; padding: 6px 12px; border-radius: 4px; font-size: 0.85em;">📖 View Page</a>`;
                        content += `<a href="Cadman_catalog/${file}" target="_blank" style="text-decoration: none; background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; font-size: 0.85em;" onclick="return checkPDFLink(this)">🔗 Open PDF</a>`;
                        content += `</div>`;
                        content += `</div>`;
                    });
                }
                
                if (result.data.content_pages && result.data.content_pages.length > 0) {
                    result.data.content_pages.forEach(file => {
                        const pageLabel = file.replace('.pdf', '').replace('page_', 'Page ').toUpperCase();
                        const sectionKey = getSectionKeyForFile(file);
                        content += `<div class="category-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0;">`;
                        content += `<h3>📄 ${pageLabel}</h3>`;
                        content += `<p>Content Page</p>`;
                        content += `<div style="margin-top: 10px; display: flex; gap: 10px;">`;
                        content += `<a href="?section=${sectionKey}&from_search=1" style="text-decoration: none; background: #007bff; color: white; padding: 6px 12px; border-radius: 4px; font-size: 0.85em;">📖 View Page</a>`;
                        content += `<a href="Cadman_catalog/${file}" target="_blank" style="text-decoration: none; background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; font-size: 0.85em;" onclick="return checkPDFLink(this)">🔗 Open PDF</a>`;
                        content += `</div>`;
                        content += `</div>`;
                    });
                }
                
                content += '</div>';
                content += '</div>';
            });
            
            content += '<div style="margin-top: 30px; text-align: center;">';
            content += '<button onclick="showCurrentPage()" class="control-btn">🏠 Back to Current Page</button>';
            content += '</div>';
            content += '</div>';
            
            mainContainer.innerHTML = content;
            
            // Hide navigation when search results are displayed
            const catalogNav = document.querySelector('.catalog-nav');
            if (catalogNav) {
                catalogNav.style.display = 'none';
            }
        }
        
        // Helper function to get section key for a file
        function getSectionKeyForFile(filename) {
            // Comprehensive file-to-section mapping for page preview navigation
            const fileMap = {
                // Index and special pages
                'index_main.pdf': 'main',
                'Title_page.pdf': 'title',
                'celtic.pdf': 'celtic',
                
                // Product ID index pages
                'index_page_10k-crosses_01.pdf': 'crosses',
                'index_page_10K-LOCKETS_01.pdf': 'lockets',
                'index_page_engagementsets_01.pdf': 'engagement',
                'index_page_wedding_01.pdf': 'wedding1',
                'index_page_wedding_02.pdf': 'wedding2',
                'index_page_wedding_03.pdf': 'wedding3',
                'index_page_bracelets_01.pdf': 'bracelets',
                'index_page_EMBLEMATIC_01.pdf': 'emblematic',
                'index_page_gents-rings_01.pdf': 'gents-rings',
                'index_page_ladiesstone-001.pdf': 'ladiesstone-001',
                'index_page_ladiesstone-002.pdf': 'ladiesstone-002',
                'index_page_medical_01.pdf': 'medical',
                'index_page_mens-jewellry_01.pdf': 'mens-jewellry',
                'index_page_mother-001.pdf': 'mother-001',
                'index_page_pendants-earrings_01.pdf': 'pendants-earrings',
                'index_page_signets_01.pdf': 'signets',
                'index_page_ster-crosses_01.pdf': 'ster-crosses',
                'index_page_STER-LOCKETS_01.pdf': 'ster-lockets',
                'index_corp.pdf': 'corp',
                
                // Common page mappings - numbered pages
                'page_01a.pdf': '01a',
                'page_01b.pdf': '01b',
                'page_02a.pdf': '02a',
                'page_02b.pdf': '02b',
                'page_02c.pdf': '02c',
                'page_02d.pdf': '02d',
                
                // Page 3 series
                'page_03.pdf': '03',
                'page_03a.pdf': '03a',
                'page_03b.pdf': '03b',
                'page_03c.pdf': '03c',
                'page_03d.pdf': '03d',
                'page_03e.pdf': '03e',
                'page_03ee.pdf': '03ee',
                'page_03eee.pdf': '03eee',
                'page_03g.pdf': '03g',
                'page_03h.pdf': '03h',
                'page_03i.pdf': '03i',
                'page_03j.pdf': '03j',
                
                'page_04a.pdf': '04a',
                'page_04b.pdf': '04b',
                'page_04c.pdf': '04c',
                'page_05a.pdf': '05a',
                'page_05b.pdf': '05b',
                
                // Page 6 series
                'page_06a.pdf': '06a',
                'page_06b.pdf': '06b',
                'page_06c.pdf': '06c',
                'page_06d.pdf': '06d',
                'page_06e.pdf': '06e',
                'page_06f.pdf': '06f',
                'page_06g.pdf': '06g',
                'page_06h.pdf': '06h',
                'page_06i.pdf': '06i',
                'page_06j.pdf': '06j',
                'page_06k.pdf': '06k',
                'page_06l.pdf': '06l',
                
                // Page 7 series
                'page_07a.pdf': '07a',
                'page_07b.pdf': '07b',
                'page_07c.pdf': '07c',
                'page_07d.pdf': '07d',
                'page_07e.pdf': '07e',
                'page_07f.pdf': '07f',
                
                // Page 8 series
                'page_08a.pdf': '08a',
                'page_08b.pdf': '08b',
                'page_08c.pdf': '08c',
                'page_08d.pdf': '08d',
                
                // Page 9 series
                'page_09a.pdf': '09a',
                'page_09aa.pdf': '09aa',
                'page_09b.pdf': '09b',
                'page_09c.pdf': '09c',
                'page_09d.pdf': '09d',
                
                // Page 10 series
                'page_10.pdf': '10',
                'page_10a.pdf': '10a',
                'page_10b.pdf': '10b',
                'page_10c.pdf': '10c',
                
                // Page 11 series
                'page_11a.pdf': '11a',
                'page_11b.pdf': '11b',
                'page_11c.pdf': '11c',
                'page_11d.pdf': '11d',
                'page_11e.pdf': '11e',
                'page_11g.pdf': '11g',
                'page_11r.pdf': '11r',
                
                // Page 12 series
                'page_12a.pdf': '12a',
                'page_12r.pdf': '12r',
                
                // Page 15 series
                'page_15g.pdf': '15g',
                
                // Page 20+ series
                'page_20a.pdf': '20a',
                'page_21a.pdf': '21a',
                'page_21b.pdf': '21b',
                'page_21c.pdf': '21c',
                'page_21d.pdf': '21d',
                'page_22.pdf': '22',
                'page_22a.pdf': '22a',
                'page_22b.pdf': '22b',
                'page_22c.pdf': '22c',
                'page_23a.pdf': '23a',
                'page_23b.pdf': '23b',
                'page_24A.pdf': '24a',
                'page_24B.pdf': '24b',
                'page_25a.pdf': '25a',
                'page_26a.pdf': '26a',
                'page_27a.pdf': '27a',
                'page_27b.pdf': '27b',
                'page_33G.pdf': '33g',
                'page_34N.pdf': '34n',
                'page_35N.pdf': '35n'
            };
            
            // If we have an exact mapping, use it
            if (fileMap[filename]) {
                return fileMap[filename];
            }
            
            // Dynamic mapping for page files not explicitly listed
            let pageMatch = filename.match(/page_(\d+)([a-z]*)/i);
            if (pageMatch) {
                const pageNum = pageMatch[1].padStart(2, '0'); // Ensure 2-digit format
                const suffix = pageMatch[2] ? pageMatch[2].toLowerCase() : '';
                return pageNum + suffix;
            }
            
            // Dynamic mapping for index pages
            if (filename.startsWith('index_page_')) {
                return filename.replace('index_page_', '').replace('_01.pdf', '');
            }
            
            // Fallback: clean up common patterns
            return filename.replace('.pdf', '').replace('page_', '').replace('index_page_', '');
        }
        
        // Helper function to get URL for a file
        function getUrlForFile(filename) {
            const sectionKey = getSectionKeyForFile(filename);
            return `?section=${sectionKey}`;
        }
        
        // Show error message
        function showError(message) {
            mainContainer.innerHTML = `
                <div class="catalog-welcome">
                    <div class="pdf-preview">
                        <div class="pdf-icon">⚠️</div>
                        <h2>Error</h2>
                        <p style="color: #666;">${message}</p>
                        <button onclick="showCurrentPage()" class="primary-btn">Return</button>
                    </div>
                </div>
            `;
        }
        
        // Check if PDF link works and handle missing files
        function checkPDFLink(linkElement) {
            const url = linkElement.href;
            
            // Try to fetch the PDF to check if it exists
            fetch(url, { method: 'HEAD' })
                .then(response => {
                    if (!response.ok) {
                        // File doesn't exist or error occurred
                        linkElement.style.background = '#6c757d';
                        linkElement.style.opacity = '0.6';
                        linkElement.innerHTML = '📄 PDF Missing';
                        linkElement.onclick = function(e) {
                            e.preventDefault();
                            showCatalogToast('PDF file not found: ' + url.split('/').pop(), 'error');
                            return false;
                        };
                        return false;
                    }
                    // File exists, proceed normally
                    return true;
                })
                .catch(error => {
                    // Network error or file doesn't exist
                    linkElement.style.background = '#6c757d';
                    linkElement.style.opacity = '0.6';
                    linkElement.innerHTML = '📄 PDF Missing';
                    linkElement.onclick = function(e) {
                        e.preventDefault();
                        showCatalogToast('PDF file not found: ' + url.split('/').pop(), 'error');
                        return false;
                    };
                    return false;
                });
            
            // Return true to allow the click to proceed initially
            return true;
        }
        
        console.log('🏭 Cadman Direct PDF Catalog with Search - Section: <?= $currentSection ?>');
        console.log('🔍 Modal system loaded - Version: <?= date("Y-m-d H:i:s") ?>');
        
        
        // PDF Preview functionality for search results
        const pdfViewers = {}; // Store PDF viewer instances
        const autoLoadedPreviews = new Set(); // Track which previews we've auto-loaded
        
        // Auto-load PDF previews when search results are shown
        function autoLoadPDFPreviews() {
            console.log('autoLoadPDFPreviews() called');
            
            // Find all PDF preview containers that are visible
            const previewContainers = document.querySelectorAll('[id^="pdf-preview-"]:not([style*="display: none"])');
            
            console.log('Found preview containers:', previewContainers.length);
            console.log('Container IDs:', Array.from(previewContainers).map(c => c.id));
            
            previewContainers.forEach((container, index) => {
                const pageId = container.id.replace('pdf-preview-', '');
                console.log(`🔧 Processing container ${index + 1}: pageId = "${pageId}"`);
                console.log('📋 Container element:', container);
                
                if (!autoLoadedPreviews.has(pageId)) {
                    console.log(`Auto-loading preview for pageId: "${pageId}"`);
                    autoLoadedPreviews.add(pageId);
                    // Immediately call initPDFPreview
                    initPDFPreview(pageId);
                } else {
                    console.log(`Preview for pageId "${pageId}" already loaded`);
                }
            });
        }
        
        function togglePDFPreview(pageId) {
            const previewContainer = document.getElementById(`pdf-preview-${pageId}`);
            if (!previewContainer) return;
            
            if (previewContainer.style.display === 'none') {
                previewContainer.style.display = 'block';
                if (!pdfViewers[pageId]) {
                    initPDFPreview(pageId);
                }
            } else {
                previewContainer.style.display = 'none';
            }
        }
        
        function initPDFPreview(pageId) {
            console.log(`initPDFPreview called for pageId: "${pageId}"`);
            
            // Validate pageId format - should be like "01a", "10", "03b", "03eee", etc. OR special pages like "celtic"
            const validPagePattern = /^([0-9]+[a-z]*|celtic)$/i;
            if (!validPagePattern.test(pageId)) {
                console.warn(`⚠️ Invalid pageId format: "${pageId}" - skipping PDF load`);
                
                // Hide the PDF preview container for invalid pageIds
                const containerEl = document.getElementById(`pdf-container-${pageId}`);
                const loadingEl = document.getElementById(`pdf-loading-${pageId}`);
                
                if (loadingEl) {
                    loadingEl.innerHTML = `<div style="color: #6c757d; text-align: center; padding: 20px;">
                        📄 No PDF available for this result
                        <br><small style="color: #999;">Page ID: ${pageId}</small>
                    </div>`;
                }
                if (containerEl) {
                    containerEl.style.display = 'none';
                }
                return;
            }
            
            // Import PDF.js dynamically for search result previews
            import('./assets/pdfjs/build/pdf.mjs').then(({ getDocument, GlobalWorkerOptions }) => {
                console.log('PDF.js imported successfully');
                GlobalWorkerOptions.workerSrc = './assets/pdfjs/build/pdf.worker.mjs';
                
                // Try different path constructions - match the main viewer's approach
                const pathOptions = [
                    `./Cadman_catalog/page_${pageId}.pdf`,        // With ./ prefix
                    `Cadman_catalog/page_${pageId}.pdf`,          // Without ./ prefix  
                    `./Cadman_catalog/${pageId}.pdf`,             // Direct pageId as filename
                    `Cadman_catalog/${pageId}.pdf`,               // Direct pageId as filename, no ./
                ];
                
                console.log('=== TESTING MULTIPLE PDF PATHS ===');
                pathOptions.forEach((path, index) => {
                    console.log(`Path option ${index + 1}: "${path}"`);
                });
                
                // Start with the first option (matches main viewer pattern)
                const pdfPath = pathOptions[0];
                console.log(`PRIMARY ATTEMPT: "${pdfPath}"`);
                
                // Also log the current page location for reference
                console.log('Current page location:', window.location.href);
                console.log('Document base URI:', document.baseURI);
                
                const canvas = document.getElementById(`pdf-canvas-${pageId}`);
                if (!canvas) {
                    console.error(`Canvas not found: pdf-canvas-${pageId}`);
                    return;
                }
                console.log('Canvas found:', canvas);
                
                const ctx = canvas.getContext('2d');
                const loadingEl = document.getElementById(`pdf-loading-${pageId}`);
                const containerEl = document.getElementById(`pdf-container-${pageId}`);
                
                console.log('Elements found - loading:', !!loadingEl, 'container:', !!containerEl);
                
                let pdfDoc = null;
                let pageNum = 1;
                let scale = 1.0;
                let fitScale = 1.0;
                let panX = 0, panY = 0;
                let isDragging = false;
                let dragStartX = 0, dragStartY = 0;
                let dragStartPanX = 0, dragStartPanY = 0;
                
                // Function to try loading with different paths
                async function tryLoadPDF(pathIndex = 0) {
                    if (pathIndex >= pathOptions.length) {
                        console.error('ALL PATH OPTIONS FAILED');
                        if (loadingEl) {
                            loadingEl.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">
                                ❌ Error: PDF not found<br>
                                <small style="color: #666; font-family: monospace; display: block; margin: 5px 0;">Tried all paths:</small>
                                ${pathOptions.map(p => `<small style="color: #999; display: block;">${p}</small>`).join('')}
                            </div>`;
                        }
                        return;
                    }
                    
                    const currentPath = pathOptions[pathIndex];
                    console.log(`ATTEMPT ${pathIndex + 1}: Trying "${currentPath}"`);
                    
                    try {
                        const pdf = await getDocument(currentPath).promise;
                        console.log(`✅ SUCCESS with path: "${currentPath}" - ${pdf.numPages} pages`);
                        
                        pdfDoc = pdf;
                        pdfViewers[pageId] = { pdf: pdfDoc, pageNum, scale, fitScale, panX, panY };
                        
                        // Calculate initial scale
                        const firstPage = await pdf.getPage(1);
                        const container = canvas.parentElement;
                        const containerWidth = container.clientWidth - 40;
                        const containerHeight = 500;
                        const viewport = firstPage.getViewport({ scale: 1.0 });
                        
                        console.log('=== SCALE CALCULATION DEBUG ===');
                        console.log(`Container dimensions: ${containerWidth}x${containerHeight}`);
                        console.log(`PDF viewport at scale 1.0: ${viewport.width}x${viewport.height}`);
                        console.log(`Container clientWidth: ${container.clientWidth}`);
                        console.log(`Container offsetWidth: ${container.offsetWidth}`);
                        console.log(`Container scrollWidth: ${container.scrollWidth}`);
                        
                        const widthScale = (containerWidth * 0.9) / viewport.width;
                        const heightScale = (containerHeight * 0.9) / viewport.height;
                        
                        console.log(`Width scale: (${containerWidth} * 0.9) / ${viewport.width} = ${widthScale}`);
                        console.log(`Height scale: (${containerHeight} * 0.9) / ${viewport.height} = ${heightScale}`);
                        
                        fitScale = Math.min(widthScale, heightScale);
                        console.log(`Fit scale (min of width/height): ${fitScale}`);
                        
                        // Ensure we have a reasonable minimum scale
                        if (fitScale <= 0 || isNaN(fitScale)) {
                            console.warn(`❌ Invalid fitScale: ${fitScale}, using fallback 0.5`);
                            fitScale = 0.5;
                        }
                        
                        scale = fitScale;
                        pdfViewers[pageId].scale = scale;
                        pdfViewers[pageId].fitScale = fitScale;
                        
                        console.log(`Final calculated scale: ${scale}`);
                        console.log(`Final fitScale: ${fitScale}`);
                        console.log('=== END SCALE CALCULATION ===');
                        
                        if (loadingEl) loadingEl.style.display = 'none';
                        if (containerEl) containerEl.style.display = 'block';
                        
                        renderPreviewPage(pageId);
                        updatePreviewZoom(pageId);
                        
                    } catch (error) {
                        console.log(`❌ FAILED path "${currentPath}": ${error.message}`);
                        // Try next path
                        tryLoadPDF(pathIndex + 1);
                    }
                }
                
                // Start trying paths
                tryLoadPDF(0);
                
                // Event listeners for this specific preview (only attach if elements exist)
                const prevPageBtn = document.getElementById(`prev-page-${pageId}`);
                const nextPageBtn = document.getElementById(`next-page-${pageId}`);
                
                if (prevPageBtn) {
                    prevPageBtn.addEventListener('click', () => {
                        if (pdfViewers[pageId] && pdfViewers[pageId].pageNum > 1) {
                            pdfViewers[pageId].pageNum--;
                            renderPreviewPage(pageId);
                        }
                    });
                }
                
                if (nextPageBtn) {
                    nextPageBtn.addEventListener('click', () => {
                        if (pdfViewers[pageId] && pdfViewers[pageId].pageNum < pdfViewers[pageId].pdf.numPages) {
                            pdfViewers[pageId].pageNum++;
                            renderPreviewPage(pageId);
                        }
                    });
                }
                
                document.getElementById(`zoom-out-${pageId}`).addEventListener('click', () => {
                    if (pdfViewers[pageId] && pdfViewers[pageId].scale > 0.1) {
                        pdfViewers[pageId].scale *= 0.9;
                        if (pdfViewers[pageId].scale <= pdfViewers[pageId].fitScale) {
                            pdfViewers[pageId].panX = 0;
                            pdfViewers[pageId].panY = 0;
                        }
                        renderPreviewPage(pageId);
                        updatePreviewZoom(pageId);
                    }
                });
                
                document.getElementById(`zoom-in-${pageId}`).addEventListener('click', () => {
                    if (pdfViewers[pageId] && pdfViewers[pageId].scale < 8) {
                        pdfViewers[pageId].scale *= 1.1;
                        renderPreviewPage(pageId);
                        updatePreviewZoom(pageId);
                    }
                });
                
                document.getElementById(`fit-width-${pageId}`).addEventListener('click', () => {
                    if (pdfViewers[pageId]) {
                        pdfViewers[pageId].scale = pdfViewers[pageId].fitScale;
                        pdfViewers[pageId].panX = 0;
                        pdfViewers[pageId].panY = 0;
                        renderPreviewPage(pageId);
                        updatePreviewZoom(pageId);
                    }
                });
                
                // Mouse events for panning
                canvas.addEventListener('mousedown', function(e) {
                    if (pdfViewers[pageId] && pdfViewers[pageId].scale > pdfViewers[pageId].fitScale * 1.1) {
                        isDragging = true;
                        dragStartX = e.clientX;
                        dragStartY = e.clientY;
                        dragStartPanX = pdfViewers[pageId].panX;
                        dragStartPanY = pdfViewers[pageId].panY;
                        canvas.style.cursor = 'grabbing';
                        e.preventDefault();
                    }
                });
                
                document.addEventListener('mousemove', function(e) {
                    if (isDragging && pdfViewers[pageId]) {
                        const deltaX = e.clientX - dragStartX;
                        const deltaY = e.clientY - dragStartY;
                        
                        pdfViewers[pageId].panX = dragStartPanX - deltaX;
                        pdfViewers[pageId].panY = dragStartPanY - deltaY;
                        
                        renderPreviewPage(pageId);
                    }
                });
                
                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        canvas.style.cursor = pdfViewers[pageId] && pdfViewers[pageId].scale > pdfViewers[pageId].fitScale * 1.1 ? 'grab' : 'default';
                    }
                });
                
                // Mouse wheel zoom
                canvas.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    if (pdfViewers[pageId]) {
                        const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1;
                        const newScale = pdfViewers[pageId].scale * zoomFactor;
                        
                        if (newScale >= 0.1 && newScale <= 8) {
                            pdfViewers[pageId].scale = newScale;
                            if (pdfViewers[pageId].scale <= pdfViewers[pageId].fitScale) {
                                pdfViewers[pageId].panX = 0;
                                pdfViewers[pageId].panY = 0;
                            }
                            renderPreviewPage(pageId);
                            updatePreviewZoom(pageId);
                        }
                    }
                });
                
                // Touch events for mobile support
                console.log('🔧 Setting up touch events for pageId:', pageId);
                
                let touchStartX = 0, touchStartY = 0;
                let isTouchDragging = false;
                let initialPinchDistance = 0;
                let isPinching = false;
                let lastTouchScale = 1;
                let touchStartPanX = 0, touchStartPanY = 0;
                
                // Test if canvas exists
                if (!canvas) {
                    console.error('❌ Canvas not found for touch events, pageId:', pageId);
                    return;
                }
                
                console.log('✅ Canvas found, adding touch events to:', canvas);
                console.log('🔧 About to attach main gesture touch events for pageId:', pageId);
                console.log('⚡ Attaching touchstart event listener now...');
                
                // Remove the simple test events - they're conflicting
                // canvas.addEventListener('click', function(e) {
                //     console.log('🖱️ Canvas click detected on pageId:', pageId);
                //     alert('Canvas click works! PageID: ' + pageId);
                // });
                
                canvas.addEventListener('touchstart', function(e) {
                    console.log('🔥 CATALOG DIRECT TOUCH START:', e.touches.length, 'touches');
                    console.log('🔍 Checking pdfViewers object:', typeof pdfViewers);
                    console.log('🔍 Available pageIds:', Object.keys(pdfViewers || {}));
                    console.log('🔍 Looking for pageId:', pageId);
                    console.log('🔍 pdfViewer exists for this pageId:', !!pdfViewers[pageId]);
                    
                    if (pdfViewers[pageId]) {
                        console.log('✅ Found pdfViewer:', pdfViewers[pageId]);
                        console.log('📊 Current scale:', pdfViewers[pageId].scale);
                        console.log('📊 Current panX/Y:', pdfViewers[pageId].panX, pdfViewers[pageId].panY);
                    }
                    
                    // Prevent browser zoom
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (!pdfViewers[pageId]) {
                        console.log('❌ No pdfViewer found for pageId:', pageId);
                        console.log('🆘 Creating emergency pdfViewer for testing');
                        // Create a minimal pdfViewer for testing
                        if (typeof pdfViewers === 'undefined') {
                            window.pdfViewers = {};
                        }
                        pdfViewers[pageId] = {
                            scale: 1.0,
                            fitScale: 1.0,
                            panX: 0,
                            panY: 0
                        };
                        console.log('✅ Emergency pdfViewer created');
                    }
                    
                    if (e.touches.length === 1) {
                        // Single touch - prepare for panning (TESTING: removed zoom restriction)
                        isTouchDragging = true;
                        touchStartX = e.touches[0].clientX;
                        touchStartY = e.touches[0].clientY;
                        touchStartPanX = pdfViewers[pageId].panX;
                        touchStartPanY = pdfViewers[pageId].panY;
                        console.log('📱 Single touch panning enabled, current pan:', touchStartPanX, touchStartPanY);
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
                        lastTouchScale = pdfViewers[pageId].scale;
                        console.log('🤏 Pinch zoom started, distance:', initialPinchDistance, 'current scale:', lastTouchScale);
                    }
                }, { passive: false });
                
                canvas.addEventListener('touchmove', function(e) {
                    // Prevent browser zoom
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (!pdfViewers[pageId]) {
                        console.log('❌ No pdfViewer found in touchmove for pageId:', pageId);
                        return;
                    }
                    
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
                        
                        console.log('🤏 Pinch zooming:', scaleChange.toFixed(2), 'new scale:', newScale.toFixed(2));
                        
                        // Apply zoom limits
                        if (newScale >= 0.1 && newScale <= 8) {
                            pdfViewers[pageId].scale = newScale;
                            if (pdfViewers[pageId].scale <= pdfViewers[pageId].fitScale) {
                                pdfViewers[pageId].panX = 0;
                                pdfViewers[pageId].panY = 0;
                            }
                            renderPreviewPage(pageId);
                            updatePreviewZoom(pageId);
                        }
                    } else if (isTouchDragging && e.touches.length === 1) {
                        // Handle single finger panning when zoomed in
                        const deltaX = e.touches[0].clientX - touchStartX;
                        const deltaY = e.touches[0].clientY - touchStartY;
                        
                        console.log('🔄 Panning, delta:', deltaX, deltaY);
                        
                        pdfViewers[pageId].panX = touchStartPanX - deltaX;
                        pdfViewers[pageId].panY = touchStartPanY - deltaY;
                        
                        renderPreviewPage(pageId);
                    }
                    e.preventDefault();
                }, { passive: false });
                
                canvas.addEventListener('touchend', function(e) {
                    console.log('🔥 Touch end, remaining touches:', e.touches.length);
                    
                    // Prevent browser zoom
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (e.touches.length === 0) {
                        isTouchDragging = false;
                        isPinching = false;
                        console.log('👋 All touch gestures ended');
                    }
                }, { passive: false });
            });
        }
        
        function renderPreviewPage(pageId) {
            console.log(`🎨 renderPreviewPage called for pageId: ${pageId}`);
            const viewer = pdfViewers[pageId];
            if (!viewer || !viewer.pdf) {
                console.error(`❌ No viewer found for pageId: ${pageId}`, viewer);
                return;
            }
            
            // Validate scale before rendering
            if (viewer.scale <= 0 || !isFinite(viewer.scale)) {
                console.error(`❌ Invalid scale for ${pageId}:`, viewer.scale, '- resetting to fitScale');
                viewer.scale = viewer.fitScale > 0 ? viewer.fitScale : 0.5;
            }
            
            console.log(`📊 Viewer state: pageNum=${viewer.pageNum}, scale=${viewer.scale}, fitScale=${viewer.fitScale}, panX=${viewer.panX}, panY=${viewer.panY}`);
            
            const canvas = document.getElementById(`pdf-canvas-${pageId}`);
            if (!canvas) {
                console.error(`❌ Canvas not found: pdf-canvas-${pageId}`);
                return;
            }
            console.log(`✅ Canvas found:`, canvas);
            
            const ctx = canvas.getContext('2d');
            
            viewer.pdf.getPage(viewer.pageNum).then(function(page) {
                console.log(`📄 Got page ${viewer.pageNum}, starting render...`);
                const viewport = page.getViewport({ scale: viewer.scale });
                console.log(`📐 Viewport: ${viewport.width}x${viewport.height} at scale ${viewer.scale}`);
                
                // Validate viewport dimensions
                if (viewport.width <= 0 || viewport.height <= 0 || !isFinite(viewport.width) || !isFinite(viewport.height)) {
                    console.error(`❌ Invalid viewport dimensions for ${pageId}:`, viewport.width, 'x', viewport.height);
                    return;
                }
                
                const isZoomedIn = viewer.scale > viewer.fitScale * 1.1;
                console.log(`🔍 Is zoomed in: ${isZoomedIn} (scale ${viewer.scale} vs fitScale*1.1 ${viewer.fitScale * 1.1})`);
                
                if (isZoomedIn) {
                    const maxWidth = 800;
                    const maxHeight = 500;
                    
                    canvas.width = Math.max(100, Math.min(viewport.width, maxWidth));
                    canvas.height = Math.max(100, Math.min(viewport.height, maxHeight));
                    console.log(`🔧 Zoomed canvas size: ${canvas.width}x${canvas.height} (max: ${maxWidth}x${maxHeight})`);
                    
                    // Validate canvas dimensions
                    if (canvas.width <= 0 || canvas.height <= 0) {
                        console.error(`❌ Invalid canvas dimensions for ${pageId}:`, canvas.width, 'x', canvas.height);
                        return;
                    }
                    
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.save();
                    ctx.translate(-viewer.panX, -viewer.panY);
                    console.log(`🔄 Applied pan translation: ${-viewer.panX}, ${-viewer.panY}`);
                } else {
                    canvas.width = Math.max(100, viewport.width);
                    canvas.height = Math.max(100, viewport.height);
                    console.log(`📏 Fit canvas size: ${canvas.width}x${canvas.height}`);
                    
                    // Validate canvas dimensions
                    if (canvas.width <= 0 || canvas.height <= 0) {
                        console.error(`❌ Invalid canvas dimensions for ${pageId}:`, canvas.width, 'x', canvas.height);
                        return;
                    }
                    
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                console.log(`🚀 Starting PDF.js render...`);
                page.render(renderContext).promise.then(function() {
                    console.log(`✅ PDF render completed successfully for ${pageId}`);
                    if (isZoomedIn) {
                        ctx.restore();
                        console.log(`🔧 Context restored after zoomed render`);
                    }
                    
                    // Update page info
                    document.getElementById(`page-info-${pageId}`).textContent = `Page ${viewer.pageNum} of ${viewer.pdf.numPages}`;
                    
                    // Update button states
                    document.getElementById(`prev-page-${pageId}`).disabled = viewer.pageNum <= 1;
                    document.getElementById(`next-page-${pageId}`).disabled = viewer.pageNum >= viewer.pdf.numPages;
                    
                    // Update cursor
                    canvas.style.cursor = viewer.scale > viewer.fitScale * 1.1 ? 'grab' : 'default';
                }).catch(function(renderError) {
                    console.error(`❌ PDF render failed for ${pageId}:`, renderError);
                });
            }).catch(function(pageError) {
                console.error(`❌ Failed to get page ${viewer.pageNum} for ${pageId}:`, pageError);
            });
        }
        
        function updatePreviewZoom(pageId) {
            const viewer = pdfViewers[pageId];
            if (!viewer) {
                console.warn(`⚠️ updatePreviewZoom: No viewer found for ${pageId}`);
                return;
            }
            
            console.log(`🔍 updatePreviewZoom for ${pageId}: scale=${viewer.scale}`);
            const zoomPercent = Math.round(viewer.scale * 100);
            console.log(`📊 Calculated zoom percent: ${viewer.scale} * 100 = ${zoomPercent}%`);
            
            const zoomDisplay = document.getElementById(`zoom-display-${pageId}`);
            if (zoomDisplay) {
                zoomDisplay.textContent = zoomPercent + '%';
                console.log(`✅ Updated zoom display to: ${zoomPercent}%`);
            } else {
                console.error(`❌ Zoom display element not found: zoom-display-${pageId}`);
            }
        }
        
        // Handle orientation change and window resize for multi-viewer setup
        let multiViewerResizeTimeout;
        const handleMultiViewerOrientationChange = function() {
            clearTimeout(multiViewerResizeTimeout);
            multiViewerResizeTimeout = setTimeout(() => {
                console.log('📱 Multi-viewer: Orientation/resize detected, recalculating layouts...');
                
                // Re-render all active PDF viewers
                Object.keys(pdfViewers).forEach(pageId => {
                    const viewer = pdfViewers[pageId];
                    if (!viewer || !viewer.pdf) return;
                    
                    const canvas = document.getElementById(`pdf-canvas-${pageId}`);
                    if (!canvas) return;
                    
                    console.log(`📱 Recalculating fitScale for ${pageId}...`);
                    
                    // Recalculate fitScale based on new container dimensions
                    viewer.pdf.getPage(viewer.pageNum).then(function(page) {
                        const container = canvas.parentElement;
                        
                        // Force reflow
                        container.offsetHeight;
                        
                        const containerWidth = container.clientWidth - 40;
                        const containerHeight = 500;
                        const viewport = page.getViewport({ scale: 1.0 });
                        
                        console.log(`📐 ${pageId} container: ${containerWidth}x${containerHeight}, viewport: ${viewport.width}x${viewport.height}`);
                        
                        // Validate container dimensions
                        if (containerWidth <= 0 || containerHeight <= 0) {
                            console.warn(`⚠️ Invalid container dimensions for ${pageId}: ${containerWidth}x${containerHeight}`);
                            return;
                        }
                        
                        const widthScale = (containerWidth * 0.9) / viewport.width;
                        const heightScale = (containerHeight * 0.9) / viewport.height;
                        const newFitScale = Math.min(widthScale, heightScale);
                        
                        // Ensure we have a reasonable minimum scale
                        if (newFitScale <= 0 || !isFinite(newFitScale)) {
                            console.warn(`❌ Invalid newFitScale for ${pageId}: ${newFitScale}, skipping...`);
                            return;
                        }
                        
                        // If we were at fit scale, update to new fit scale
                        // Otherwise, maintain relative zoom level
                        const wasAtFit = Math.abs(viewer.scale - viewer.fitScale) < 0.01;
                        
                        if (wasAtFit) {
                            viewer.scale = newFitScale;
                            console.log(`📱 ${pageId}: Resetting to new fit scale:`, viewer.scale);
                        } else {
                            // Maintain relative zoom ratio
                            const zoomRatio = viewer.scale / viewer.fitScale;
                            viewer.scale = newFitScale * zoomRatio;
                            console.log(`📱 ${pageId}: Maintaining zoom ratio:`, zoomRatio, 'new scale:', viewer.scale);
                        }
                        
                        viewer.fitScale = newFitScale;
                        
                        // Reset pan when going back to fit scale
                        if (viewer.scale <= viewer.fitScale * 1.1) {
                            viewer.panX = 0;
                            viewer.panY = 0;
                        }
                        
                        // Update zoom display and re-render
                        updatePreviewZoom(pageId);
                        renderPreviewPage(pageId);
                        console.log(`✅ ${pageId}: Re-rendered after orientation change`);
                    }).catch(function(err) {
                        console.error(`❌ Failed to recalculate ${pageId} on resize:`, err);
                    });
                });
            }, 300); // Debounce to avoid multiple rapid re-renders
        };
        
        // Install orientation/resize handlers for multi-viewer
        window.addEventListener('orientationchange', handleMultiViewerOrientationChange);
        window.addEventListener('resize', handleMultiViewerOrientationChange);
        console.log('✅ Multi-viewer orientation change and resize handlers installed');
        
        // Debug: Check if modal functions are available
        setTimeout(() => {
            if (typeof openCatalogModal === 'function') {
                console.log('✅ Modal functions loaded successfully');
            } else {
                console.warn('⚠ openCatalogModal helper not detected; continuing without legacy modal bridge');
            }
        }, 100);
        
        // Test function to manually test PDF loading
        window.testPDFLoading = function(pageId = '10') {
            console.log('=== TESTING PDF LOADING ===');
            console.log('Creating test preview container for page:', pageId);
            
            // Create a test container in the main area
            const mainContainer = document.getElementById('mainContainer');
            const testHtml = `
                <div style="padding: 20px; border: 2px solid red; margin: 20px;">
                    <h2>PDF Loading Test</h2>
                    <div id="pdf-preview-${pageId}" style="margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">
                        <div style="background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd;">
                            <strong>📄 Test PDF Preview (Page ${pageId})</strong>
                        </div>
                        <div class="pdf-viewer-wrapper" style="height: 600px; background: #f5f5f5; position: relative;">
                            <div class="pdf-loading" id="pdf-loading-${pageId}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666;">
                                <div style="font-size: 2em; margin-bottom: 10px;">📄</div>
                                <div>Loading PDF preview...</div>
                            </div>
                            <div class="pdf-container" id="pdf-container-${pageId}" style="display: none; height: 100%; overflow: auto;">
                                <div class="pdf-controls" style="background: #34495e; padding: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <button id="prev-page-${pageId}">‹ Prev</button>
                                    <span id="page-info-${pageId}" style="color: white;">Page 1 of 1</span>
                                    <button id="next-page-${pageId}">Next ›</button>
                                    <button id="zoom-out-${pageId}">Zoom Out</button>
                                    <span id="zoom-display-${pageId}" style="color: white;">100%</span>
                                    <button id="zoom-in-${pageId}">Zoom In</button>
                                    <button id="fit-width-${pageId}">Fit Width</button>
                                </div>
                                <canvas id="pdf-canvas-${pageId}" style="display: block; margin: 20px auto; background: white;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            mainContainer.innerHTML = testHtml;
            
            // Try to load the PDF
            setTimeout(() => {
                console.log('Calling initPDFPreview for test...');
                initPDFPreview(pageId);
            }, 100);
        };
        
        console.log('Test function available: window.testPDFLoading("10")');
        console.log('Try calling testPDFLoading() in console to test PDF loading directly');
        
        // Analytics Tracking System
        class CatalogAnalyticsTracker {
            constructor() {
                this.startTime = Date.now();
                this.currentPDF = null;
                this.deviceInfo = this.getDeviceInfo();
            }
            
            getDeviceInfo() {
                const width = window.innerWidth || document.documentElement.clientWidth;
                const height = window.innerHeight || document.documentElement.clientHeight;
                
                return {
                    screen_resolution: `${width}x${height}`,
                    connection_speed: this.getConnectionSpeed(),
                    user_agent: navigator.userAgent
                };
            }
            
            getConnectionSpeed() {
                if ('connection' in navigator) {
                    const connection = navigator.connection;
                    if (connection.effectiveType) {
                        return ['slow-2g', '2g', '3g'].includes(connection.effectiveType) ? 'slow' : 'fast';
                    }
                }
                return 'unknown';
            }
            
            trackPDFView(filename, sectionName, additionalData = {}) {
                const data = {
                    action: 'log_analytics',
                    pdf_filename: filename,
                    section_name: sectionName,
                    ...this.deviceInfo,
                    load_time_ms: Date.now() - this.startTime,
                    ...additionalData
                };
                
                // Send analytics via AJAX
                fetch('catalog_analytics.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                }).catch(e => console.log('Analytics tracking failed:', e));
                
                this.currentPDF = filename;
            }
            
            trackCacheRequest(filename, successful = false) {
                if (this.currentPDF === filename) {
                    this.trackPDFView(filename, null, {
                        cache_requested: true,
                        cache_successful: successful
                    });
                }
            }
        }
        
        // Initialize analytics tracker
        window.analyticsTracker = new CatalogAnalyticsTracker();
        
        // Merge current PDF data to avoid losing `path` from earlier initialization.
        window.currentPdfData = Object.assign({}, window.currentPdfData || {}, {
            file: <?= json_encode($currentFile ?? null) ?>,
            path: <?= json_encode($pdfPath ?? null) ?>,
            section: <?= json_encode($currentSection ?? 'main') ?>,
            title: <?= json_encode($currentData['title'] ?? '') ?>,
            prevSection: <?= json_encode($prevSection ?? null) ?>,
            nextSection: <?= json_encode($nextSection ?? null) ?>,
            prevFile: <?= json_encode($prevData['file'] ?? null) ?>,
            nextFile: <?= json_encode($nextData['file'] ?? null) ?>
        });
        
        // Track current page view if PDF is loaded
        <?php if ($currentSection && isset($currentFile)): ?>
        window.analyticsTracker.trackPDFView(
            '<?= addslashes($currentFile) ?>', 
            '<?= addslashes($currentData['title']) ?>'
        );
        <?php endif; ?>
        
        // Initialize smart PDF preloader and all other functionality after page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - initializing all functionality...');
            
            // Initialize PDF support test
            if (typeof runPDFSupportTest === 'function') {
                runPDFSupportTest();
            }
            
            // Initialize navigation button handlers
            const navButtons = document.querySelectorAll('.nav-btn[href*="section="]');
            navButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const viewer = document.getElementById('pdf-viewer');
                    const loading = document.getElementById('pdf-loading');
                    if (viewer) viewer.style.display = 'none';
                    if (loading) loading.style.display = 'block';
                });
            });
            
            // Wait a moment for scripts to fully load, then initialize preloader
            setTimeout(() => {
                if (typeof SmartPDFPreloader !== 'undefined') {
                    console.log('SmartPDFPreloader found, initializing...');
                    try {
                        window.smartPDFPreloader = new SmartPDFPreloader();
                        console.log('Smart PDF Preloader initialized successfully');
                    } catch (error) {
                        console.error('Error initializing Smart PDF Preloader:', error);
                    }
                } else {
                    console.error('SmartPDFPreloader class not found. Check if scripts loaded properly.');
                }
            }, 500);
        });
    </script>
    <?php 
    // Include the modern ProductModal container (preferred path)
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();

    // Legacy catalog_detail_modal intentionally not rendered here.
    // catalog_direct now uses the unified ProductModal implementation.
    ?>

</body>
</html>