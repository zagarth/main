<?php
/**
 * Update All Collection Pages with New Image Loader
 * This script updates all collection pages to use the new image_loader_v2.php
 */

echo "Collection Page Updater\n";
echo "======================\n\n";

$collectionPages = [
    'Bands.php',
    'School.php', 
    'Corp.php',
    'Signet.php',
    'Accessories.php',
    'Family.php',
    'Engagement.php',
    'Ladys_Stoneset.php'
];

foreach ($collectionPages as $page) {
    echo "Processing $page...\n";
    
    if (!file_exists($page)) {
        echo "  SKIP: File not found\n";
        continue;
    }
    
    $content = file_get_contents($page);
    $originalContent = $content;
    
    // Check if already includes the new image loader
    if (strpos($content, 'image_loader_v2.php') !== false) {
        echo "  OK: Already using new image loader\n";
        continue;
    }
    
    // Add the image loader include after the navigation includes
    $pattern = '/(<?php include \'topButton\.php\'; renderTopButton\(\); \?>)/';
    $replacement = '$1' . "\n    <?php include 'image_loader_v2.php'; // Include new image loader functions ?>";
    
    $content = preg_replace($pattern, $replacement, $content);
    
    if ($content !== $originalContent) {
        if (file_put_contents($page, $content)) {
            echo "  UPDATED: Added image_loader_v2.php include\n";
        } else {
            echo "  ERROR: Could not write to $page\n";
        }
    } else {
        echo "  SKIP: No changes made\n";
    }
}

echo "\n======================\n";
echo "Collection page updates complete!\n";
?>
