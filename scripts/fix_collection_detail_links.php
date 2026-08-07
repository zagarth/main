<?php
/**
 * Fix Collection Page Detail Links
 * Updates all collection pages to link to the correct organized detail pages
 */

// Collection page mappings
$collectionMappings = [
    'Bands.php' => ['prefix' => 'bands_php', 'detail_path' => 'bands_php/bands_php_'],
    'Corp.php' => ['prefix' => 'corp_php', 'detail_path' => 'corp_php/corp_php_'],
    'School.php' => ['prefix' => 'school_php', 'detail_path' => 'school_php/school_php_'],
    'Signet.php' => ['prefix' => 'signet_php', 'detail_path' => 'signet_php/signet_php_'],
    'Accessories.php' => ['prefix' => 'accessories_php', 'detail_path' => 'accessories_php/accessories_php_'],
    'Family.php' => ['prefix' => 'family_php', 'detail_path' => 'family_php/family_php_'],
    'Engagement.php' => ['prefix' => 'engagement_php', 'detail_path' => 'engagement_php/engagement_php_']
];

$fixedFiles = 0;
$errorFiles = 0;

foreach ($collectionMappings as $file => $mapping) {
    if (!file_exists($file)) {
        echo "SKIP: File not found - $file\n";
        continue;
    }
    
    echo "Processing: $file\n";
    
    // Read file content
    $content = file_get_contents($file);
    if ($content === false) {
        echo "  ERROR: Could not read file\n";
        $errorFiles++;
        continue;
    }
    
    $originalContent = $content;
    $changes = 0;
    
    // Pattern 1: Fix generic detail.php links
    $genericPattern = '/\$detailUrl = \'[^\']*_detail\.php[^\']*\';/';
    if (preg_match($genericPattern, $content)) {
        $newDetailLink = '$imageBasename = pathinfo($image, PATHINFO_FILENAME);' . "\n" . 
                         '                    $detailUrl = \'' . $mapping['detail_path'] . '\' . $imageBasename . \'_detail.php\';';
        $content = preg_replace($genericPattern, $newDetailLink, $content);
        $changes++;
    }
    
    // Pattern 2: Fix any remaining generic patterns like band_detail.php, corp_detail.php etc.
    $oldPatterns = [
        '/band_detail\.php\?[^\'\"]*/',
        '/corp_detail\.php\?[^\'\"]*/',
        '/school_detail\.php\?[^\'\"]*/',
        '/signet_detail\.php\?[^\'\"]*/',
        '/accessories_detail\.php\?[^\'\"]*/',
        '/family_detail\.php\?[^\'\"]*/',
        '/engagement_detail\.php\?[^\'\"]*/'
    ];
    
    foreach ($oldPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            // Replace with the specific detail path pattern
            $content = preg_replace($pattern, $mapping['detail_path'] . '\' . pathinfo($image, PATHINFO_FILENAME) . \'_detail.php', $content);
            $changes++;
        }
    }
    
    // Ensure we have the imageBasename variable defined before the detailUrl
    if (strpos($content, '$imageBasename = pathinfo($image, PATHINFO_FILENAME);') === false && $changes > 0) {
        $content = str_replace(
            '$detailUrl = \'' . $mapping['detail_path'],
            '$imageBasename = pathinfo($image, PATHINFO_FILENAME);' . "\n" . 
            '                    $detailUrl = \'' . $mapping['detail_path'],
            $content
        );
    }
    
    // Write back if changes were made
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content) !== false) {
            echo "  SUCCESS: Updated detail links ($changes changes)\n";
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
echo "Total processed: " . count($collectionMappings) . " files\n";
echo str_repeat("=", 50) . "\n";
?>
