<?php
/**
 * Optimized Image Loader Functions - v2.0
 * Updated with new specifications for all collection pages
 */

// Function to scan directory and get image files with improved filtering
function getImagesFromDirectory($directory) {
    $images = [];
    if (!is_dir($directory)) {
        return $images;
    }
    
    $files = scandir($directory);
    foreach ($files as $file) {
        // Skip hidden files and directories
        if ($file[0] === '.' || is_dir($directory . '/' . $file)) {
            continue;
        }
        
        // Check for supported image extensions
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            continue;
        }
        
        // Skip alternate versions for main gallery display
        if (preg_match('/[_-]alt\d*/', $file)) {
            continue;
        }
        
        // Skip thumbnail files that might be in the main directory
        if (strpos($file, '_thumb') !== false || strpos($file, 'thumbnail') !== false) {
            continue;
        }
        
        $images[] = $file;
    }
    
    // Sort images naturally (handles numbers correctly)
    natsort($images);
    return array_values($images);
}

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

// Function to group images by base name (for variants)
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
    
    // Sort each group to have main image first, then variants
    foreach ($grouped as $baseName => $variants) {
        usort($variants, function($a, $b) {
            $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $a);
            $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $b);
            
            if ($aIsMain && !$bIsMain) return -1;
            if (!$aIsMain && $bIsMain) return 1;
            return 0;
        });
        $grouped[$baseName] = $variants;
    }
    
    return $grouped;
}

// Function to get thumbnail path with multiple fallback options
function getThumbnailPath($imagePath, $category) {
    $pathInfo = pathinfo($imagePath);
    $directory = $pathInfo['dirname'];
    $filename = $pathInfo['basename'];
    
    // Primary thumbnail paths to check (in order of preference)
    $thumbnailPaths = [
        // New specification: /thumbs/images/category/
        str_replace('/images/', '/thumbs/images/', $imagePath),
        // Alternative: /images/thumbnails/category/
        str_replace('/images/', '/images/thumbnails/', $imagePath),
        // Legacy: /thumbs/category/
        $directory . '/../thumbs/' . $category . '/' . $filename,
        // Direct thumbs in category
        $directory . '/thumbs/' . $filename
    ];
    
    // Check each path and return the first existing one
    foreach ($thumbnailPaths as $thumbPath) {
        if (file_exists($thumbPath)) {
            return $thumbPath;
        }
    }
    
    // Fallback to original image
    return $imagePath;
}

// Function to create display name from filename with improved formatting
function createDisplayName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remove common prefixes and suffixes
    $name = preg_replace('/^(img_|image_|photo_|pic_)/', '', $name);
    $name = preg_replace('/(_img|_image|_photo|_pic)$/', '', $name);
    
    // Replace separators with spaces
    $name = preg_replace('/[_-]+/', ' ', $name);
    
    // Remove alt version indicators
    $name = preg_replace('/\s+alt\d*\s*/', ' ', $name);
    
    // Clean up whitespace
    $name = trim($name);
    
    // Convert to title case
    return ucwords(strtolower($name));
}

// Function to generate price based on category and filename with improved logic
function generatePrice($category, $filename) {
    $basePrices = [
        'bands' => 850,
        'school' => 750,
        'corp' => 900,
        'signet' => 1200,
        'accessories' => 450,
        'family' => 650,
        'engagement' => 2850,
        'gems' => 1250,
        'pearls' => 950,
        'bridal' => 2850
    ];
    
    $basePrice = isset($basePrices[$category]) ? $basePrices[$category] : 750;
    
    // Price modifiers based on filename patterns
    $filename = strtolower($filename);
    
    // Premium materials
    if (strpos($filename, 'gold') !== false || strpos($filename, 'au') !== false) {
        $basePrice *= 1.3;
    }
    if (strpos($filename, 'platinum') !== false || strpos($filename, 'pt') !== false) {
        $basePrice *= 1.5;
    }
    if (strpos($filename, 'silver') !== false || strpos($filename, 'ag') !== false) {
        $basePrice *= 0.8;
    }
    
    // Gemstones and diamonds
    if (strpos($filename, 'diamond') !== false || strpos($filename, 'd') === 0) {
        $basePrice += 500;
    }
    if (strpos($filename, 'ruby') !== false || strpos($filename, 'sapphire') !== false) {
        $basePrice += 300;
    }
    if (strpos($filename, 'emerald') !== false) {
        $basePrice += 400;
    }
    
    // Size indicators
    if (strpos($filename, 'xl') !== false || strpos($filename, 'large') !== false) {
        $basePrice *= 1.2;
    }
    if (strpos($filename, 'sm') !== false || strpos($filename, 'small') !== false || strpos($filename, 'petite') !== false) {
        $basePrice *= 0.85;
    }
    if (strpos($filename, 'medium') !== false || strpos($filename, 'med') !== false || strpos($filename, 'm') === strlen($filename) - 1) {
        $basePrice *= 0.95;
    }
    
    // Special collections
    if (strpos($filename, 'custom') !== false || strpos($filename, 'bespoke') !== false) {
        $basePrice *= 1.4;
    }
    if (strpos($filename, 'vintage') !== false || strpos($filename, 'antique') !== false) {
        $basePrice *= 1.15;
    }
    
    // Round to nearest 50
    return round($basePrice / 50) * 50;
}

// Function to generate detail page path
function getDetailPagePath($directory, $filename) {
    // Extract collection name from directory path
    $collectionName = '';
    if (preg_match('/(\w+)_php/', $directory, $matches)) {
        $collectionName = $matches[1];
    }
    
    $itemCode = pathinfo($filename, PATHINFO_FILENAME);
    $detailPath = $collectionName . '_php/' . $collectionName . '_php_' . $itemCode . '_detail.php';
    
    return file_exists($detailPath) ? $detailPath : null;
}

// Function to get category icon
function getCategoryIcon($category) {
    $icons = [
        'bands' => '💍',
        'school' => '🎓',
        'corp' => '🏢',
        'signet' => '📜',
        'accessories' => '✨',
        'family' => '👨‍👩‍👧‍👦',
        'engagement' => '💎',
        'bridal' => '👰',
        'gems' => '💎',
        'pearls' => '🐚',
        'celtic' => '☘️',
        'antique' => '🏛️',
        'contemporary' => '🔷',
        'vintage' => '📿'
    ];
    
    return isset($icons[$category]) ? $icons[$category] : '💍';
}

// Function to render jewelry item HTML
function renderJewelryItem($imagePath, $category, $displayName, $description) {
    $filename = basename($imagePath);
    $price = generatePrice($category, $filename);
    $thumbnailPath = getThumbnailPath($imagePath, $category);
    $detailPath = getDetailPagePath(dirname($imagePath), $filename);
    
    echo '<div class="jewelry-item" data-category="' . htmlspecialchars($category) . '">';
    echo '<img src="' . htmlspecialchars($thumbnailPath) . '" alt="' . htmlspecialchars($displayName) . '" loading="lazy">';
    echo '<div class="item-info">';
    echo '<h3>' . htmlspecialchars($displayName) . '</h3>';
    echo '<p>' . htmlspecialchars($description) . '</p>';
    echo '<div class="item-price">Starting at $' . number_format($price) . '</div>';
    
    if ($detailPath) {
        echo '<a href="' . htmlspecialchars($detailPath) . '" class="view-details-btn">View Details</a>';
    } else {
        echo '<a href="#formtable" class="view-details-btn">Request Quote</a>';
    }
    
    echo '</div>';
    echo '</div>';
}

// Function to render category filter buttons
function renderCategoryFilters($categories) {
    echo '<button class="filter-btn active" onclick="filterItems(\'all\')">All Items</button>';
    
    foreach ($categories as $key => $category) {
        $images = getImagesFromDirectory($category['path']);
        if (!empty($images)) {
            $icon = getCategoryIcon($key);
            echo '<button class="filter-btn" onclick="filterItems(\'' . $key . '\')">';
            echo $icon . ' ' . htmlspecialchars($category['display_name']);
            echo '</button>';
        }
    }
}

// Debug function to check image loading
function debugImageLoading($directory) {
    if (!is_dir($directory)) {
        echo "Directory does not exist: $directory\n";
        return;
    }
    
    $files = scandir($directory);
    $imageCount = 0;
    $thumbnailCount = 0;
    
    foreach ($files as $file) {
        if (preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $file)) {
            $imageCount++;
            $thumbnailPath = getThumbnailPath($directory . '/' . $file, 'test');
            if (file_exists($thumbnailPath) && $thumbnailPath !== $directory . '/' . $file) {
                $thumbnailCount++;
            }
        }
    }
    
    echo "Directory: $directory\n";
    echo "Images found: $imageCount\n";
    echo "Thumbnails available: $thumbnailCount\n";
}
?>
