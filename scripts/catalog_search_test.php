<?php
/**
 * Simple test page to verify CatalogSearch class works independently
 */

require_once 'classes/CatalogSearch.php';

// Handle AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $catalogSearch = new CatalogSearch();
    $catalogSearch->handleAjaxRequest();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Catalog Search Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .search-box { margin: 20px 0; }
        .search-box input { padding: 10px; width: 300px; }
        .search-box button { padding: 10px 20px; }
        .results { margin-top: 20px; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Catalog Search Test Page</h1>
    
    <div style="background: #f0f8ff; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #4CAF50;">
        <h3>📊 Database Management</h3>
        <p>Download products that need page references assigned (824 products after auto-fixes):</p>
        <a href="export_missing_page_refs.php" download style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-right: 10px;">
            📥 Download CSV - Products Missing Page References
        </a>
        <a href="auto_fix_gender_variants.php" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
            🔧 Auto-Fix Gender Variants (L/M pairs)
        </a>
        <p style="font-size: 12px; color: #666; margin-top: 10px;">
            <strong>CSV Export:</strong> Contains all products with names but no page references. Fill in the "Page Reference" column and use import tool.<br>
            <strong>Auto-Fix:</strong> Automatically copies page references between L and M variants of the same product (e.g., 200L → 200M).
        </p>
    </div>
    
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Enter product ID or keyword..." value="4T72">
        <button onclick="testSearch()">Search</button>
    </div>
    
    <div id="results" class="results" style="display: none;">
        <h3>Search Results:</h3>
        <div id="resultsContent"></div>
    </div>
    
    <script>
    function testSearch() {
        const searchTerm = document.getElementById('searchInput').value;
        
        if (!searchTerm.trim()) {
            alert('Please enter a search term');
            return;
        }
        
        // Show loading
        document.getElementById('results').style.display = 'block';
        document.getElementById('resultsContent').innerHTML = 'Searching...';
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'search');
        formData.append('term', searchTerm);
        
        // Send request
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Search response:', data);
            let html = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            document.getElementById('resultsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Search error:', error);
            document.getElementById('resultsContent').innerHTML = 'Error: ' + error.message;
        });
    }
    </script>
</body>
</html>