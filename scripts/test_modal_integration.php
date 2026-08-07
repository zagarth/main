<?php
/**
 * Test the search modal integration on pages
 */

echo "Testing search modal integration...\n\n";

// Test 1: Check if search modal include works
echo "Test 1: Search modal include\n";
try {
    ob_start();
    include 'includes/search_modal.php';
    $modal_output = ob_get_clean();
    
    if (strpos($modal_output, 'search-modal') !== false) {
        echo "✓ Search modal HTML generated successfully\n";
    } else {
        echo "✗ Search modal HTML not found\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading search modal: " . $e->getMessage() . "\n";
}

// Test 2: Check if JavaScript file exists and is readable
echo "\nTest 2: JavaScript file accessibility\n";
if (file_exists('js/search_modal.js') && is_readable('js/search_modal.js')) {
    $js_content = file_get_contents('js/search_modal.js');
    if (strpos($js_content, 'openSearchModal') !== false && 
        strpos($js_content, 'searchProducts') !== false) {
        echo "✓ JavaScript file contains required functions\n";
    } else {
        echo "✗ JavaScript file missing required functions\n";
    }
} else {
    echo "✗ JavaScript file not accessible\n";
}

// Test 3: Check if ProductModal class is accessible
echo "\nTest 3: ProductModal class\n";
try {
    require_once 'classes/ProductModal.php';
    echo "✓ ProductModal class loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error loading ProductModal: " . $e->getMessage() . "\n";
}

// Test 4: Test Bands.php integration
echo "\nTest 4: Bands.php integration\n";
try {
    // Check if Bands.php includes the modal
    $bands_content = file_get_contents('Bands.php');
    if (strpos($bands_content, 'includes/search_modal.php') !== false && 
        strpos($bands_content, 'js/search_modal.js') !== false) {
        echo "✓ Bands.php includes search modal components\n";
    } else {
        echo "✗ Bands.php missing search modal includes\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking Bands.php: " . $e->getMessage() . "\n";
}

echo "\nIntegration test completed!\n";
?>