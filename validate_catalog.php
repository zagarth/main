<?php
// Cross-check catalog array entries with actual files
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
    ];
}

$catalogSections = getCatalogSections();
$catalogDir = './Cadman_catalog/';

echo "<h2>📋 Catalog Array Validation Report</h2>\n";

$found = 0;
$missing = 0;
$caseIssues = 0;

foreach ($catalogSections as $key => $data) {
    $filename = $data['file'];
    $exactPath = $catalogDir . $filename;
    
    if (file_exists($exactPath)) {
        echo "✅ $key: $filename - EXISTS<br>\n";
        $found++;
    } else {
        // Check case-insensitive
        $files = scandir($catalogDir);
        $targetFilename = strtolower($filename);
        $foundCaseInsensitive = false;
        
        foreach ($files as $file) {
            if (strtolower($file) === $targetFilename) {
                echo "⚠️ $key: $filename - CASE ISSUE (actual: $file)<br>\n";
                $caseIssues++;
                $foundCaseInsensitive = true;
                break;
            }
        }
        
        if (!$foundCaseInsensitive) {
            echo "❌ $key: $filename - MISSING<br>\n";
            $missing++;
        }
    }
}

echo "<br><h3>📊 Summary:</h3>\n";
echo "✅ Found: $found files<br>\n";
echo "⚠️ Case Issues: $caseIssues files<br>\n";  
echo "❌ Missing: $missing files<br>\n";
echo "📁 Total in array: " . count($catalogSections) . " files<br>\n";
?>