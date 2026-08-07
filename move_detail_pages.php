<?php
/**
 * Move Detail Pages to Correct Directories
 * Organizes all *_detail.php files into their proper collection directories
 */

// Directory mapping
$collectionDirs = [
    'bands_php_' => 'bands_php',
    'accessories_php_' => 'accessories_php',
    'corp_php_' => 'corp_php',
    'school_php_' => 'school_php',
    'signet_php_' => 'signet_php',
    'family_php_' => 'family_php',
    'engagement_php_' => 'engagement_php',
    'emblematic_php_' => 'emblematic_php',
    'ladys_stoneset_php_' => 'ladys_stoneset_php',
    'mother_and_daughters_php_' => 'mother_and_daughters_php'
];

// Get all detail page files in the root directory
$detailFiles = glob('*_detail.php');

if (empty($detailFiles)) {
    echo "No detail page files found in root directory.\n";
    exit;
}

echo "Found " . count($detailFiles) . " detail pages to organize.\n\n";

$movedFiles = 0;
$errorFiles = 0;
$skippedFiles = 0;

foreach ($detailFiles as $file) {
    echo "Processing: $file\n";
    
    // Determine which collection this belongs to
    $targetDir = null;
    foreach ($collectionDirs as $prefix => $dir) {
        if (strpos($file, $prefix) === 0) {
            $targetDir = $dir;
            break;
        }
    }
    
    if (!$targetDir) {
        echo "  SKIP: Could not determine target directory\n";
        $skippedFiles++;
        continue;
    }
    
    // Create target directory if it doesn't exist
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            echo "  ERROR: Could not create directory: $targetDir\n";
            $errorFiles++;
            continue;
        }
        echo "  Created directory: $targetDir\n";
    }
    
    $targetPath = $targetDir . '/' . $file;
    
    // Check if file already exists in target
    if (file_exists($targetPath)) {
        echo "  SKIP: File already exists in $targetDir\n";
        $skippedFiles++;
        continue;
    }
    
    // Move the file
    if (rename($file, $targetPath)) {
        echo "  SUCCESS: Moved to $targetDir/\n";
        $movedFiles++;
    } else {
        echo "  ERROR: Could not move file to $targetDir\n";
        $errorFiles++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Moved: $movedFiles files\n";
echo "Skipped: $skippedFiles files\n";
echo "Errors: $errorFiles files\n";
echo "Total processed: " . count($detailFiles) . " files\n";
echo str_repeat("=", 50) . "\n";
?>
