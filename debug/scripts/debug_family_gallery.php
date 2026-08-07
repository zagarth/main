<?php
include 'image_loader_v2.php';

echo "Testing Family.php gallery generation...\n";

$categories = [
    'mother' => [
        'path' => 'family_php/images/Mother',
        'display_name' => 'Mother\'s Collection',
        'description' => 'Beautiful jewelry pieces designed to celebrate and honor mothers with love and elegance'
    ],
];

$itemCount = 0;

foreach ($categories as $categoryKey => $category) {
    echo "Processing category: $categoryKey\n";
    echo "Path: {$category['path']}\n";
    
    $groupedImages = groupImagesByBaseName($category['path']);
    echo "Grouped images count: " . count($groupedImages) . "\n";
    
    foreach ($groupedImages as $baseName => $imageVariants) {
        $itemCount++;
        echo "Item $itemCount: $baseName with " . count($imageVariants) . " variants\n";
        
        if ($itemCount >= 3) break; // Just test first 3
    }
}

echo "Total items would be: $itemCount\n";
?>
