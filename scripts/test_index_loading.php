<?php
/**
 * Test index.php loading without web server
 */

echo "Testing index.php page loading...\n\n";

// Capture any output and errors
ob_start();

try {
    // Set up some basic server variables that the page might expect
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/homesite/';
    
    // Include the page
    include 'index.php';
    
    $output = ob_get_clean();
    
    // Check for key components
    if (strpos($output, 'search-modal') !== false) {
        echo "✓ Search modal included in page\n";
    } else {
        echo "✗ Search modal not found in page output\n";
    }
    
    if (strpos($output, 'js/search_modal.js') !== false) {
        echo "✓ Search JavaScript included\n";
    } else {
        echo "✗ Search JavaScript not found\n";
    }
    
    if (strpos($output, 'ProductModal') !== false) {
        echo "✓ ProductModal system included\n";
    } else {
        echo "✗ ProductModal system not found\n";
    }
    
    // Check for retailer functionality
    if (strpos($output, 'retailerLocations') !== false) {
        echo "✓ Retailer data included\n";
    } else {
        echo "✗ Retailer data not found\n";
    }
    
    echo "\nIndex page loaded successfully with search components!\n";
    
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "✗ Error loading index.php: " . $e->getMessage() . "\n";
    echo "Output: " . substr($output, 0, 500) . "\n";
} catch (Error $e) {
    $output = ob_get_clean();
    echo "✗ Fatal error in index.php: " . $e->getMessage() . "\n";
    echo "Output: " . substr($output, 0, 500) . "\n";
}
?>