<?php
/**
 * Generate missing thumbnails for Family collection
 * This script will create thumbnails for any main images that don't have corresponding thumbnails
 */

set_time_limit(300); // 5 minutes timeout
ini_set('memory_limit', '512M');

echo "=== Family Collection Thumbnail Generator ===\n\n";

// Define categories
$categories = [
    'Mother' => '/var/www/html/homesite/family_php/images/Mother',
    'Father' => '/var/www/html/homesite/family_php/images/Father', 
    'Daughter' => '/var/www/html/homesite/family_php/images/Daughter'
];

// Thumbnail settings
$thumbWidth = 300;
$thumbHeight = 300;
$quality = 85;

$totalGenerated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($categories as $categoryName => $sourceDir) {
    echo "Processing $categoryName category...\n";
    
    $thumbDir = str_replace('/images/', '/thumbs/images/', $sourceDir);
    
    // Ensure thumbnail directory exists
    if (!is_dir($thumbDir)) {
        echo "Creating thumbnail directory: $thumbDir\n";
        mkdir($thumbDir, 0755, true);
    }
    
    // Get all image files from source directory
    $files = scandir($sourceDir);
    $imageFiles = array_filter($files, function($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff']);
    });
    
    $categoryGenerated = 0;
    $categorySkipped = 0;
    $categoryErrors = 0;
    
    foreach ($imageFiles as $imageFile) {
        $sourcePath = $sourceDir . '/' . $imageFile;
        
        // Convert .tif files to .png thumbnails, keep others as same extension
        $thumbFile = $imageFile;
        if (strtolower(pathinfo($imageFile, PATHINFO_EXTENSION)) === 'tif') {
            $thumbFile = pathinfo($imageFile, PATHINFO_FILENAME) . '.png';
        }
        
        $thumbPath = $thumbDir . '/' . $thumbFile;
        
        // Skip if thumbnail already exists
        if (file_exists($thumbPath)) {
            echo "  ✓ Thumbnail exists: $thumbFile\n";
            $categorySkipped++;
            continue;
        }
        
        echo "  → Generating: $thumbFile";
        
        // Generate thumbnail using ImageMagick
        $command = sprintf(
            'convert "%s" -resize %dx%d^ -gravity center -extent %dx%d -quality %d "%s" 2>&1',
            escapeshellarg($sourcePath),
            $thumbWidth,
            $thumbHeight,
            $thumbWidth,
            $thumbHeight,
            $quality,
            escapeshellarg($thumbPath)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($thumbPath)) {
            $fileSize = round(filesize($thumbPath) / 1024, 1);
            echo " ✓ ({$fileSize}KB)\n";
            $categoryGenerated++;
        } else {
            echo " ✗ FAILED\n";
            if (!empty($output)) {
                echo "    Error: " . implode(' ', $output) . "\n";
            }
            $categoryErrors++;
        }
    }
    
    echo "  $categoryName Summary: {$categoryGenerated} generated, {$categorySkipped} skipped, {$categoryErrors} errors\n\n";
    
    $totalGenerated += $categoryGenerated;
    $totalSkipped += $categorySkipped;
    $totalErrors += $categoryErrors;
}

echo "=== FINAL SUMMARY ===\n";
echo "Total thumbnails generated: $totalGenerated\n";
echo "Total thumbnails skipped (already exist): $totalSkipped\n";
echo "Total errors: $totalErrors\n";

if ($totalGenerated > 0) {
    echo "\n✓ Thumbnail generation completed successfully!\n";
    echo "You can now refresh Family.php to see faster loading images.\n";
} else if ($totalSkipped > 0 && $totalErrors === 0) {
    echo "\n✓ All thumbnails already exist!\n";
} else {
    echo "\n⚠ Some errors occurred during thumbnail generation.\n";
}

echo "\nTo verify thumbnails, check:\n";
foreach ($categories as $categoryName => $sourceDir) {
    $thumbDir = str_replace('/images/', '/thumbs/images/', $sourceDir);
    echo "- $thumbDir\n";
}
?>
