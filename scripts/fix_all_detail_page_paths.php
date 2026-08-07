<?php
/**
 * Fix All Detail Page Paths
 * This script fixes the back links and image paths in all detail pages
 * that are now located in their respective *_php directories
 */

echo "Detail Page Path Fixer\n";
echo "=====================\n\n";

$baseDir = __DIR__;
$phpDirectories = [
    'accessories_php',
    'bands_php', 
    'corp_php',
    'family_php',
    'signet_php',
    'school_php',
    'engagement_php',
    'ladys_stoneset_php',
    'emblematic_php',
    'mother_and_daughters_php'
];

$totalFixed = 0;
$totalFiles = 0;

foreach ($phpDirectories as $directory) {
    $dirPath = $baseDir . '/' . $directory;
    
    if (!is_dir($dirPath)) {
        echo "SKIP: Directory $directory does not exist\n";
        continue;
    }
    
    echo "Processing directory: $directory\n";
    echo str_repeat("-", 50) . "\n";
    
    // Find all detail page files
    $detailFiles = glob($dirPath . '/*_detail.php');
    
    if (empty($detailFiles)) {
        echo "No detail files found in $directory\n\n";
        continue;
    }
    
    echo "Found " . count($detailFiles) . " detail files\n";
    $filesFixed = 0;
    
    foreach ($detailFiles as $filePath) {
        $fileName = basename($filePath);
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        if (!$content) {
            echo "ERROR: Could not read $fileName\n";
            continue;
        }
        
        // Extract the collection name from directory (remove _php suffix)
        $collectionName = str_replace('_php', '', $directory);
        $collectionTitle = ucfirst($collectionName);
        
        // Fix back link - add ../ prefix
        $content = preg_replace(
            '/href="(' . preg_quote($collectionTitle, '/') . '\.php)"/',
            'href="../$1"',
            $content
        );
        
        // Fix image paths - add ../ prefix to *_php/images/ paths
        $content = preg_replace(
            '/src="(' . preg_quote($directory, '/') . '\/images\/[^"]*)"/',
            'src="../$1"',
            $content
        );
        
        // Fix thumbnail paths - add ../ prefix to *_php/thumbs/ paths  
        $content = preg_replace(
            '/src="(' . preg_quote($directory, '/') . '\/thumbs\/[^"]*)"/',
            'src="../$1"',
            $content
        );
        
        // Check if any changes were made
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content)) {
                echo "FIXED: $fileName\n";
                $filesFixed++;
            } else {
                echo "ERROR: Could not write to $fileName\n";
            }
        } else {
            echo "OK: $fileName (no changes needed)\n";
        }
        
        $totalFiles++;
    }
    
    echo "Fixed $filesFixed files in $directory\n\n";
    $totalFixed += $filesFixed;
}

echo str_repeat("=", 60) . "\n";
echo "SUMMARY:\n";
echo "Total files processed: $totalFiles\n";
echo "Total files fixed: $totalFixed\n";
echo "Path fixing complete!\n";
echo str_repeat("=", 60) . "\n";
?>
