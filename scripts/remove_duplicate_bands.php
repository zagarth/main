<?php
/**
 * Remove duplicate bands from plain folder
 * This script identifies bands that exist in celtic, cultural, or fancy folders
 * and removes them from the plain folder to eliminate duplicates
 */

// Function to get base name (remove variant suffixes)
function getBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    // Remove _alt1, _alt2, etc. suffixes
    $name = preg_replace('/_alt\d*$/', '', $name);
    // Remove -alt1, -alt2, etc. suffixes (different naming pattern)
    $name = preg_replace('/-alt\d*$/', '', $name);
    // Remove other view suffixes like _view2, _art2
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    // Remove -view2, -art2 patterns
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    return $name;
}

// Function to get all band base names from a category
function getBandBaseNames($category) {
    $baseNames = [];
    $imagePath = "bands_php/images/$category/";
    
    if (is_dir($imagePath)) {
        $files = scandir($imagePath);
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $baseName = getBaseName($file);
                if (!in_array($baseName, $baseNames)) {
                    $baseNames[] = $baseName;
                }
            }
        }
    }
    
    return $baseNames;
}

// Function to find all files for a base name in a category
function findAllVariants($baseName, $category) {
    $variants = [];
    $imagePath = "bands_php/images/$category/";
    
    if (is_dir($imagePath)) {
        $files = scandir($imagePath);
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $fileBaseName = getBaseName($file);
                if ($fileBaseName === $baseName) {
                    $variants[] = $file;
                }
            }
        }
    }
    
    return $variants;
}

echo "Starting duplicate band removal process...\n\n";

// Step 1: Get all band base names from priority categories
echo "Step 1: Scanning priority categories (celtic, cultural, fancy)...\n";

$celticBands = getBandBaseNames('celtic');
$culturalBands = getBandBaseNames('cultural');
$fancyBands = getBandBaseNames('fancy');

echo "Found " . count($celticBands) . " bands in celtic category\n";
echo "Found " . count($culturalBands) . " bands in cultural category\n";
echo "Found " . count($fancyBands) . " bands in fancy category\n";

// Combine all priority bands
$priorityBands = array_unique(array_merge($celticBands, $culturalBands, $fancyBands));
echo "Total unique bands in priority categories: " . count($priorityBands) . "\n\n";

// Step 2: Check what exists in plain that should be removed
echo "Step 2: Checking for duplicates in plain category...\n";

$plainBands = getBandBaseNames('plain');
echo "Found " . count($plainBands) . " bands in plain category\n";

$bandsToRemove = array_intersect($plainBands, $priorityBands);
echo "Found " . count($bandsToRemove) . " duplicate bands to remove from plain\n\n";

if (empty($bandsToRemove)) {
    echo "No duplicates found. Exiting.\n";
    exit;
}

// Step 3: List the bands that will be removed
echo "Step 3: Bands to be removed from plain folder:\n";
foreach ($bandsToRemove as $band) {
    $variants = findAllVariants($band, 'plain');
    echo "  $band (" . count($variants) . " files): " . implode(', ', $variants) . "\n";
}

echo "\nPress 'y' to proceed with removal, any other key to cancel: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'y' && trim($line) !== 'Y') {
    echo "Operation cancelled.\n";
    exit;
}

// Step 4: Remove the duplicate files
echo "\nStep 4: Removing duplicate files...\n";

$removedCount = 0;
$totalFiles = 0;

foreach ($bandsToRemove as $band) {
    $variants = findAllVariants($band, 'plain');
    $totalFiles += count($variants);
    
    echo "Removing $band variants:\n";
    
    foreach ($variants as $file) {
        $imagePath = "bands_php/images/plain/$file";
        $thumbPath = "bands_php/thumbs/images/plain/$file";
        
        // Remove main image
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                echo "  ✓ Removed: $imagePath\n";
                $removedCount++;
            } else {
                echo "  ✗ Failed to remove: $imagePath\n";
            }
        }
        
        // Remove thumbnail if it exists
        if (file_exists($thumbPath)) {
            if (unlink($thumbPath)) {
                echo "  ✓ Removed thumbnail: $thumbPath\n";
            } else {
                echo "  ✗ Failed to remove thumbnail: $thumbPath\n";
            }
        }
    }
}

echo "\nCleanup complete!\n";
echo "Total files processed: $totalFiles\n";
echo "Files successfully removed: $removedCount\n";
echo "Duplicate bands eliminated: " . count($bandsToRemove) . "\n";

// Step 5: Final verification
echo "\nStep 5: Final verification...\n";
$remainingPlainBands = getBandBaseNames('plain');
$remainingDuplicates = array_intersect($remainingPlainBands, $priorityBands);

if (empty($remainingDuplicates)) {
    echo "✓ Success! No more duplicates found in plain category.\n";
} else {
    echo "⚠ Warning: " . count($remainingDuplicates) . " duplicates still remain:\n";
    foreach ($remainingDuplicates as $duplicate) {
        echo "  - $duplicate\n";
    }
}

echo "\nPlain category now contains " . count($remainingPlainBands) . " unique bands.\n";
?>
