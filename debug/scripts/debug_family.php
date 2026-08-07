<?php
// Debug script for Family collection
echo "<h2>Family Collection Debug Report</h2>\n";

// Function to scan directory and get image files (same as Family.php)
function getImagesFromDirectory($directory) {
    $images = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $images[] = $file;
            }
        }
    }
    return $images;
}

// Define categories and their directories (same as Family.php)
$categories = [
    'mother' => [
        'path' => 'family_php/images/Mother',
        'display_name' => 'Mother\'s Collection',
        'description' => 'Beautiful jewelry pieces designed to celebrate and honor mothers with love and elegance'
    ],
    'father' => [
        'path' => 'family_php/images/Father',
        'display_name' => 'Father\'s Collection',
        'description' => 'Distinguished pieces for fathers, combining strength and sophistication'
    ],
    'daughter' => [
        'path' => 'family_php/images/Daughter',
        'display_name' => 'Daughter\'s Collection',
        'description' => 'Delicate and charming pieces perfect for daughters of all ages'
    ]
];

$totalImages = 0;
$totalDisplayImages = 0;

foreach ($categories as $categoryKey => $category) {
    echo "<h3>Category: {$category['display_name']} ({$categoryKey})</h3>\n";
    echo "Directory: {$category['path']}<br>\n";
    
    // Check if directory exists
    if (!is_dir($category['path'])) {
        echo "<strong style='color: red;'>Directory does not exist!</strong><br>\n";
        continue;
    }
    
    $images = getImagesFromDirectory($category['path']);
    echo "Images found: " . count($images) . "<br>\n";
    
    if (empty($images)) {
        echo "<em>No images found in this directory.</em><br>\n";
        // List all files in directory
        $allFiles = scandir($category['path']);
        echo "All files in directory: " . implode(', ', array_filter($allFiles, function($f) { return $f[0] !== '.'; })) . "<br>\n";
    } else {
        echo "Sample images: " . implode(', ', array_slice($images, 0, 5)) . "<br>\n";
        
        // Count how many would be displayed (excluding _alt)
        $displayImages = array_filter($images, function($image) {
            return strpos($image, '_alt') === false;
        });
        echo "Images for display (non-alt): " . count($displayImages) . "<br>\n";
        $totalDisplayImages += count($displayImages);
    }
    
    $totalImages += count($images);
    echo "<hr>\n";
}

echo "<h3>Total Summary</h3>\n";
echo "Total images across all categories: $totalImages<br>\n";
echo "Total display images (non-alt): $totalDisplayImages<br>\n";
?>
