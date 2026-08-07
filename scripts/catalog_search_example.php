<?php
/**
 * Example usage of CatalogSearch class on any page
 * This demonstrates how to integrate the catalog search functionality
 */

// Include the CatalogSearch class
require_once 'classes/CatalogSearch.php';

// Example 1: Handle AJAX search requests
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $catalogSearch = new CatalogSearch();
    $catalogSearch->handleAjaxRequest();
    // This will handle the response and exit
}

// Example 2: Perform a search and get results
function performCatalogSearch($searchTerm) {
    $catalogSearch = new CatalogSearch();
    $results = $catalogSearch->search($searchTerm);
    
    if ($results['has_results']) {
        return $catalogSearch->generateSearchResultsHTML($results);
    } else {
        return '<p>No results found for: ' . htmlspecialchars($searchTerm) . '</p>';
    }
}

// Example 3: Search only the database for products
function searchForProduct($productId) {
    $catalogSearch = new CatalogSearch();
    $databaseResults = $catalogSearch->searchProductDatabase($productId);
    
    return $databaseResults;
}

// Example 4: Search catalog sections by keywords
function searchCatalogSections($keyword) {
    $catalogSearch = new CatalogSearch();
    $catalogResults = $catalogSearch->searchCatalogIntelligent($keyword);
    
    return $catalogResults;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Catalog Search Example</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Catalog Search Example</h1>
    
    <!-- Simple search form -->
    <div>
        <input type="text" id="searchInput" placeholder="Search for products or pages...">
        <button onclick="performSearch()">Search</button>
    </div>
    
    <!-- Results container -->
    <div id="searchResults"></div>
    
    <script>
    function performSearch() {
        const searchTerm = document.getElementById('searchInput').value;
        
        if (!searchTerm.trim()) {
            alert('Please enter a search term');
            return;
        }
        
        // Use AJAX to search
        $.post('', {
            action: 'search',
            search_term: searchTerm
        }, function(response) {
            if (response.success) {
                document.getElementById('searchResults').innerHTML = response.html;
            } else {
                document.getElementById('searchResults').innerHTML = '<p>Search failed: ' + response.error + '</p>';
            }
        }, 'json');
    }
    
    // Allow Enter key to trigger search
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    </script>
</body>
</html>