<?php
// Debug script to check what's happening with Bands.php

echo "Testing Bands.php image loading...\n";

// Test the function directly
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

$categories = [
    'celtic' => [
        'path' => 'bands_php/images/celtic',
        'display_name' => 'Celtic Bands',
        'description' => 'Traditional Celtic knotwork and patterns with intricate designs'
    ],
    'fancy' => [
        'path' => 'bands_php/images/fancy',
        'display_name' => 'Designer Bands',
        'description' => 'Elegant designer bands with sophisticated styling and details'
    ],
    'plain' => [
        'path' => 'bands_php/images/plain',
        'display_name' => 'Classic Bands',
        'description' => 'Timeless classic bands with clean lines and traditional appeal'
    ],
    'cultural' => [
        'path' => 'bands_php/images/cultural',
        'display_name' => 'Cultural Bands',
        'description' => 'Bands inspired by cultural traditions from around the world'
    ]
];

foreach ($categories as $key => $category) {
    echo "\nChecking category: {$key}\n";
    echo "Directory: {$category['path']}\n";
    echo "Exists: " . (is_dir($category['path']) ? 'YES' : 'NO') . "\n";
    
    $images = getImagesFromDirectory($category['path']);
    echo "Images found: " . count($images) . "\n";
    
    $filteredImages = array_filter($images, function($image) {
        return strpos($image, '_alt') === false;
    });
    echo "Non-alt images: " . count($filteredImages) . "\n";
    
    if (count($filteredImages) > 0) {
        echo "First few images: " . implode(', ', array_slice($filteredImages, 0, 3)) . "\n";
    }
}
?>
