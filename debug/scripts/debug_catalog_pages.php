<?php
require_once 'classes/CatalogSearch.php';
$search = new CatalogSearch();

echo "=== TESTING CATALOG PAGE SUGGESTIONS ===\n";

// Test different search terms to see which ones show green catalog pages
$test_terms = [
    '2BMC' => 'Shows green catalog pages',
    'F25' => 'Regular product search',
    'RN1' => 'Page 9A product',
    'engagement' => 'Category search',
    'wedding' => 'Category search',
    'medical' => 'Category search'
];

foreach ($test_terms as $term => $description) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Testing: '$term' ($description)\n";
    echo str_repeat("=", 50) . "\n";
    
    $results = $search->search($term);
    
    // Check if there are catalog details and what type they are
    if (isset($results['catalog_details'])) {
        echo "CATALOG DETAILS FOUND:\n";
        echo "  Type: {$results['catalog_details']['type']}\n";
        echo "  Files: " . implode(', ', $results['catalog_details']['files']) . "\n";
        echo "  Description: {$results['catalog_details']['description']}\n";
        
        // Check if there's index page info
        if (isset($results['catalog_details']['index_info'])) {
            echo "  Index Info: " . print_r($results['catalog_details']['index_info'], true) . "\n";
        }
        
        // Check for any green/category indicators
        if (isset($results['catalog_details']['category'])) {
            echo "  Category: {$results['catalog_details']['category']}\n";
        }
    } else {
        echo "NO CATALOG DETAILS\n";
    }
    
    // Check database details
    if (isset($results['database_details'])) {
        echo "DATABASE DETAILS FOUND:\n";
        foreach ($results['database_details'] as $type => $matches) {
            echo "  $type: " . count($matches) . " matches\n";
        }
    } else {
        echo "NO DATABASE DETAILS\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "CHECKING INDEX PAGES TABLE FOR 2BMC\n";
echo str_repeat("=", 60) . "\n";

// Check if there's an index_pages table that might contain category info
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=CadmanClients",
        "cadman_admin",
        "Admin2025!Cadman",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if index_pages table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'index_pages'");
    if ($stmt->rowCount() > 0) {
        echo "Index pages table exists. Checking for 2BMC or related entries:\n";
        
        $stmt = $pdo->query("SELECT * FROM index_pages WHERE keywords LIKE '%2BMC%' OR keywords LIKE '%medical%' OR category LIKE '%medical%'");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            foreach ($results as $row) {
                echo "Found: Category={$row['category']}, Section={$row['section_name']}, Keywords={$row['keywords']}\n";
            }
        } else {
            echo "No specific 2BMC entries found. Checking all medical-related entries:\n";
            $stmt = $pdo->query("SELECT * FROM index_pages WHERE category LIKE '%medical%' OR section_name LIKE '%medical%' OR keywords LIKE '%medical%'");
            $medical_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($medical_results as $row) {
                echo "Medical entry: {$row['category']} - {$row['section_name']} - {$row['keywords']}\n";
            }
        }
    } else {
        echo "No index_pages table found.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>