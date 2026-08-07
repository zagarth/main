<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/InputValidator.php';

// Include image loader functions
include 'image_loader_v2.php';

// Function to get base name without variants
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

// Function to group images by base name
function groupImagesByBaseName($directory) {
    $images = getImagesFromDirectory($directory);
    $grouped = [];
    
    foreach ($images as $file) {
        $baseName = getBaseName($file);
        
        if (!isset($grouped[$baseName])) {
            $grouped[$baseName] = [];
        }
        $grouped[$baseName][] = $file;
    }
    
    return $grouped;
}

// Define categories
$categories = [
    'mother' => 'family_php/images/Mother',
    'father' => 'family_php/images/Father',
    'daughter' => 'family_php/images/Daughter'
];

try {
    $baseName = InputValidator::validateBaseName($_GET['base'] ?? '');
    $category = InputValidator::validateCategory($_GET['category'] ?? '');
    $currentImageUrl = InputValidator::validateFilename(basename($_GET['current'] ?? ''));
    
    if ($baseName === false || $category === false || $currentImageUrl === false) {
        throw new Exception('Invalid parameters provided');
    }
    
    if (!isset($categories[$category])) {
        throw new Exception('Invalid category');
    }
    
    $directory = $categories[$category];
    $groupedImages = groupImagesByBaseName($directory);
    
    if (!isset($groupedImages[$baseName])) {
        throw new Exception('Base name not found');
    }
    
    $variants = $groupedImages[$baseName];
    
    // Extract current image filename from URL
    $currentImagePath = parse_url($currentImageUrl, PHP_URL_PATH);
    $currentImageFile = basename($currentImagePath);
    
    // Find current image in variants
    $currentIndex = -1;
    for ($i = 0; $i < count($variants); $i++) {
        $variantPath = $directory . '/' . $variants[$i];
        $variantThumbPath = str_replace('family_php/images/', 'family_php/thumbs/images/', $variantPath);
        
        // Check both original and thumbnail paths
        if (basename($variantPath) === $currentImageFile || basename($variantThumbPath) === $currentImageFile) {
            $currentIndex = $i;
            break;
        }
    }
    
    if ($currentIndex === -1) {
        throw new Exception('Current image not found in variants');
    }
    
    // Get next image (cycle back to 0 if at end)
    $nextIndex = ($currentIndex + 1) % count($variants);
    $nextImageFile = $variants[$nextIndex];
    
    // Build paths
    $nextImagePath = $directory . '/' . $nextImageFile;
    $nextThumbPath = str_replace('family_php/images/', 'family_php/thumbs/images/', $nextImagePath);
    
    // Use thumbnail if it exists, otherwise use original
    $nextImageUrl = file_exists($nextThumbPath) ? $nextThumbPath : $nextImagePath;
    
    echo json_encode([
        'success' => true,
        'nextImage' => $nextImageUrl,
        'currentIndex' => $currentIndex,
        'nextIndex' => $nextIndex,
        'totalVariants' => count($variants)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
