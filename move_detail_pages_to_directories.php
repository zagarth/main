<?php
/**
 * Move Detail Pages to Correct Directories
 * This script analyzes detail page files and moves them to their corresponding *_php directories
 * based on the image paths referenced in the files
 */

echo "Detail Page Organization Script\n";
echo "===============================\n\n";

$baseDir = __DIR__;
$phpFiles = glob($baseDir . '/*.php');

// Directory mapping based on image path patterns
$directoryMappings = [
    'bands_php/images' => 'bands_php',
    'corp_php/images' => 'corp_php', 
    'school_php/images' => 'school_php',
    'accessories_php/images' => 'accessories_php',
    'signet_php/images' => 'signet_php',
    'family_php/images' => 'family_php',
    'ladys_stoneset_php/images' => 'ladys_stoneset_php',
    'emblematic_php/images' => 'emblematic_php',
    'mother_and_daughters_php/images' => 'mother_and_daughters_php',
    'engagement_php/images' => 'engagement_php'
];

// Files to exclude from moving (main collection pages and system files)
$excludeFiles = [
    'Bands.php', 'Corp.php', 'School.php', 'Accessories.php', 'Signet.php', 
    'Family.php', 'Ladys_Stoneset.php', 'Emblematic.php', 'Mother_and_Daughters.php',
    'Engagement.php', 'index.php', 'navigation.php', 'styles.css', 'robots.txt',
    'Celtic.php', 'Classicshoulders.php', 'Dad.php', 'Essential_Workers.php',
    'Emergency_Services.php', 'topButton.php', 'create.php'
];

$movedFiles = [];
$processedCount = 0;

foreach ($phpFiles as $phpFile) {
    $fileName = basename($phpFile);
    
    // Skip excluded files
    if (in_array($fileName, $excludeFiles)) {
        echo "SKIP: $fileName (main collection page or system file)\n";
        continue;
    }
    
    // Skip files that are already scripts or utilities
    if (strpos($fileName, '_') === 0 || 
        strpos($fileName, 'generate_') === 0 ||
        strpos($fileName, 'create_') === 0 ||
        strpos($fileName, 'update_') === 0 ||
        strpos($fileName, 'fix_') === 0 ||
        strpos($fileName, 'move_') === 0 ||
        strpos($fileName, 'validate_') === 0 ||
        strpos($fileName, 'verify_') === 0 ||
        strpos($fileName, 'check_') === 0 ||
        strpos($fileName, 'clean_') === 0 ||
        strpos($fileName, 'restore_') === 0 ||
        strpos($fileName, 'remove_') === 0 ||
        strpos($fileName, 'add_') === 0 ||
        strpos($fileName, 'consolidate_') === 0 ||
        strpos($fileName, 'comprehensive_') === 0 ||
        strpos($fileName, 'complete_') === 0 ||
        strpos($fileName, 'final_') === 0 ||
        strpos($fileName, 'center_') === 0 ||
        strpos($fileName, 'top_button_') === 0) {
        echo "SKIP: $fileName (utility script)\n";
        continue;
    }
    
    // Read file content to analyze image paths
    $content = file_get_contents($phpFile);
    
    if (!$content) {
        echo "SKIP: $fileName (could not read file)\n";
        continue;
    }
    
    // Find which directory this file belongs to
    $targetDirectory = null;
    
    foreach ($directoryMappings as $imagePath => $directory) {
        if (strpos($content, $imagePath) !== false) {
            $targetDirectory = $directory;
            break;
        }
    }
    
    if (!$targetDirectory) {
        echo "SKIP: $fileName (no image path references found)\n";
        continue;
    }
    
    // Create target directory if it doesn't exist
    $targetDir = $baseDir . '/' . $targetDirectory;
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            echo "ERROR: Could not create directory: $targetDir\n";
            continue;
        }
        echo "Created directory: $targetDirectory\n";
    }
    
    // Move the file
    $targetPath = $targetDir . '/' . $fileName;
    
    if (file_exists($targetPath)) {
        echo "SKIP: $fileName -> $targetDirectory (file already exists)\n";
        continue;
    }
    
    if (rename($phpFile, $targetPath)) {
        echo "MOVED: $fileName -> $targetDirectory/\n";
        $movedFiles[$targetDirectory][] = $fileName;
        $processedCount++;
    } else {
        echo "ERROR: Could not move $fileName to $targetDirectory\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY:\n";
echo "Total files moved: $processedCount\n\n";

foreach ($movedFiles as $directory => $files) {
    echo "$directory: " . count($files) . " files\n";
    foreach ($files as $file) {
        echo "  - $file\n";
    }
    echo "\n";
}

echo "Detail page organization complete!\n";
echo str_repeat("=", 60) . "\n";
?>
