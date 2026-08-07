<?php
/**
 * Fix Detail Pages Script
 * - Updates back links to correct collection pages
 * - Fixes thumbnail paths from /thumbnails/ to /thumbs/images/
 */

// Mapping of prefixes to correct collection page names
$collectionMapping = [
    'bands_php_' => 'Bands.php',
    'accessories_php_' => 'Accessories.php',
    'corp_php_' => 'Corp.php',
    'school_php_' => 'School.php',
    'signet_php_' => 'Signet.php',
    'family_php_' => 'Family.php',
    'engagement_php_' => 'Engagement.php',
    'emblematic_php_' => 'Emblematic.php',
    'ladys_stoneset_php_' => 'Ladys_Stoneset.php',
    'mother_and_daughters_php_' => 'Mother_and_Daughters.php'
];

// Get all detail page files
$detailFiles = glob('*_detail.php');

if (empty($detailFiles)) {
    echo "No detail page files found.\n";
    exit;
}

echo "Found " . count($detailFiles) . " detail pages to fix.\n\n";

$fixedFiles = 0;
$errorFiles = 0;

foreach ($detailFiles as $file) {
    echo "Processing: $file\n";
    
    // Read file content
    $content = file_get_contents($file);
    if ($content === false) {
        echo "  ERROR: Could not read file\n";
        $errorFiles++;
        continue;
    }
    
    $originalContent = $content;
    $changes = [];
    
    // Determine which collection this belongs to
    $collectionPage = null;
    foreach ($collectionMapping as $prefix => $page) {
        if (strpos($file, $prefix) === 0) {
            $collectionPage = $page;
            break;
        }
    }
    
    if (!$collectionPage) {
        echo "  ERROR: Could not determine collection for this file\n";
        $errorFiles++;
        continue;
    }
    
    // Fix back link
    $backLinkPattern = '/href="[^"]*_php\.php"/';
    if (preg_match($backLinkPattern, $content)) {
        $content = preg_replace($backLinkPattern, 'href="' . $collectionPage . '"', $content);
        $changes[] = "Fixed back link to $collectionPage";
    }
    
    // Fix thumbnail paths: /images/thumbnails/ -> /thumbs/images/
    $thumbnailPattern = '/\/images\/thumbnails\//';
    if (preg_match($thumbnailPattern, $content)) {
        $content = preg_replace($thumbnailPattern, '/thumbs/images/', $content);
        $changes[] = "Fixed thumbnail paths";
    }
    
    // Write back if changes were made
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content) !== false) {
            echo "  SUCCESS: " . implode(", ", $changes) . "\n";
            $fixedFiles++;
        } else {
            echo "  ERROR: Could not write file\n";
            $errorFiles++;
        }
    } else {
        echo "  SKIP: No changes needed\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Fixed: $fixedFiles files\n";
echo "Errors: $errorFiles files\n";
echo "Total processed: " . count($detailFiles) . " files\n";
echo str_repeat("=", 50) . "\n";
?>
