<?php
// Test script to verify Family detail page functionality
echo "<h1>Family Detail Page Tests</h1>\n";

// Test different item IDs from the gallery
$test_ids = [
    '1879',    // Mother category
    'FFC41',   // Father category  
    '1208',    // Daughter category
    'F2520.1', // Mother with variant notation
    'FP91'     // Mother with multiple views
];

foreach ($test_ids as $test_id) {
    echo "<h2>Testing ID: {$test_id}</h2>\n";
    
    // Simulate the detail page request
    $_GET['id'] = $test_id;
    
    // Include the unified detail page
    ob_start();
    try {
        include 'family_php/unified_detail.php';
        $output = ob_get_clean();
        
        // Check if output contains expected elements
        if (strpos($output, '<title>') !== false) {
            echo "✓ Page generates title<br>\n";
        }
        if (strpos($output, 'jewelry-detail-container') !== false) {
            echo "✓ Contains detail container<br>\n";
        }
        if (strpos($output, 'main-image') !== false) {
            echo "✓ Contains main image<br>\n";
        }
        
        // Extract the title
        preg_match('/<title>(.*?)<\/title>/', $output, $title_matches);
        if (!empty($title_matches[1])) {
            echo "Title: " . $title_matches[1] . "<br>\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>\n";
        ob_end_clean();
    }
    
    echo "<hr>\n";
}

echo "<h2>Direct Link Test</h2>\n";
echo '<a href="family_php/unified_detail.php?id=1879">Test Mother Item 1879</a><br>';
echo '<a href="family_php/unified_detail.php?id=FFC41">Test Father Item FFC41</a><br>';  
echo '<a href="family_php/unified_detail.php?id=1208">Test Daughter Item 1208</a><br>';
echo '<a href="Family.php">Back to Family Gallery</a><br>';
?>
