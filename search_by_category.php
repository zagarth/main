<?php
/**
 * Category-specific product search endpoint
 * Handles searches within specific product categories (like celtic_bands)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once 'classes/CatalogSearch.php';
    
    // Get parameters
    $searchTerm = '';
    $category = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchTerm = $_POST['term'] ?? '';
        $category = $_POST['category'] ?? '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $searchTerm = $_GET['term'] ?? '';
        $category = $_GET['category'] ?? '';
    }
    
    // Validate inputs
    if (empty($searchTerm)) {
        echo json_encode(['error' => 'Search term is required']);
        exit;
    }
    
    // Initialize search
    $search = new CatalogSearch();
    
    // Perform category-specific search
    $results = $search->searchByCategory($searchTerm, $category);
    
    // Return results
    echo json_encode($results);
    
} catch (Exception $e) {
    error_log('Category search endpoint error: ' . $e->getMessage());
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>