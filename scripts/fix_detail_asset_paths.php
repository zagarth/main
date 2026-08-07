<?php
/**
 * Fix Detail Pages Asset Paths
 * Updates CSS and favicon paths for detail pages moved into subdirectories
 */

// Define collection directories to scan
$collectionDirs = [
    'bands_php',
    'accessories_php', 
    'corp_php',
    'school_php',
    'signet_php',
    'family_php',
    'engagement_php',
    'emblematic_php',
    'ladys_stoneset_php',
    'mother_and_daughters_php'
];

$fixedFiles = 0;
$errorFiles = 0;
$totalFiles = 0;

foreach ($collectionDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    echo "Processing directory: $dir\n";
    echo str_repeat("-", 40) . "\n";
    
    $detailFiles = glob($dir . '/*_detail.php');
    
    if (empty($detailFiles)) {
        echo "No detail files found in $dir\n\n";
        continue;
    }
    
    foreach ($detailFiles as $file) {
        $totalFiles++;
        echo "  Processing: " . basename($file) . "... ";
        
        // Read file content
        $content = file_get_contents($file);
        if ($content === false) {
            echo "ERROR (read failed)\n";
            $errorFiles++;
            continue;
        }
        
        $originalContent = $content;
        $changes = [];
        
        // Fix CSS link
        if (strpos($content, 'href="styles.css"') !== false) {
            $content = str_replace('href="styles.css"', 'href="../styles.css"', $content);
            $changes[] = 'CSS path';
        }
        
        // Fix favicon link
        if (strpos($content, 'href="favicon.ico"') !== false) {
            $content = str_replace('href="favicon.ico"', 'href="../favicon.ico"', $content);
            $changes[] = 'Favicon path';
        }
        
        // Write back if changes were made
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content) !== false) {
                echo "SUCCESS (" . implode(", ", $changes) . ")\n";
                $fixedFiles++;
            } else {
                echo "ERROR (write failed)\n";
                $errorFiles++;
            }
        } else {
            echo "SKIP (no changes needed)\n";
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Fixed: $fixedFiles files\n";
echo "Errors: $errorFiles files\n";
echo "Total processed: $totalFiles files\n";
echo str_repeat("=", 50) . "\n";
?>
