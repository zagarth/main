<?php
/**
 * Direct test for bracelets search issue
 */

require_once 'classes/CatalogSearch.php';

echo "<!DOCTYPE html><html><head><title>Bracelets Search Test</title></head><body>";
echo "<h1>Testing Bracelets Search</h1>";

$catalogSearch = new CatalogSearch();

// Test direct search
echo "<h2>Direct Search Method:</h2>";
$result = $catalogSearch->search('bracelets');
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";

// Test AJAX simulation
echo "<h2>AJAX Request Simulation:</h2>";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'search';
$_POST['term'] = 'bracelets';

ob_start();
$catalogSearch->handleAjaxRequest();
$ajaxOutput = ob_get_clean();

echo "<pre>Raw AJAX Output: " . htmlspecialchars($ajaxOutput) . "</pre>";

// Parse and display nicely
$ajaxData = json_decode($ajaxOutput, true);
if ($ajaxData) {
    echo "<h3>Parsed AJAX Response:</h3>";
    echo "<pre>" . json_encode($ajaxData, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($ajaxData['indexes']) && !empty($ajaxData['indexes'])) {
        echo "<h3>✅ INDEX FILES FOUND:</h3>";
        foreach ($ajaxData['indexes'] as $index) {
            echo "<p>📄 " . $index . "</p>";
        }
    } else {
        echo "<h3>❌ NO INDEX FILES FOUND</h3>";
    }
}

echo "</body></html>";
?>