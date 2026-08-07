<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Wedding bands, engagement rings, Celtic bands, custom rings, Cadman Manufacturing" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Bands Collection - Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('bands'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php // Temporarily commented out: include 'image_loader_v2.php'; ?>
    
    <?php
    // Function to get base name from filename (remove variant suffixes)
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
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $grouped = [];
            
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $baseName = getBaseName($file);
                    
                    if (!isset($grouped[$baseName])) {
                        $grouped[$baseName] = [];
                    }
                    $grouped[$baseName][] = $file;
                }
            }
            
            // Sort variants for each group (main image first, then alts)
            foreach ($grouped as $baseName => $variants) {
                usort($variants, function($a, $b) {
                    // Main image (without _alt) comes first
                    $aHasAlt = strpos($a, '_alt') !== false;
                    $bHasAlt = strpos($b, '_alt') !== false;
                    
                    if (!$aHasAlt && $bHasAlt) return -1;
                    if ($aHasAlt && !$bHasAlt) return 1;
                    
                    // Both are alts or both are main, sort alphabetically
                    return strcmp($a, $b);
                });
                $grouped[$baseName] = $variants;
            }
            
            $images = $grouped;
        }
        return $images;
    }
    
    // Function to create display name from filename
    function createDisplayName($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[_-]/', ' ', $name);
        $name = preg_replace('/alt\d+/', '', $name);
        $name = trim($name);
        return ucwords($name);
    }
    
    // Function to generate price based on category and filename
    function generatePrice($category, $filename) {
        $basePrice = 250;
        if (strpos($filename, 'TG') !== false || strpos($filename, 'Gold') !== false) {
            $basePrice += 200;
        }
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'TGD') !== false) {
            $basePrice += 300;
        }
        if ($category === 'celtic') {
            $basePrice += 100;
        } elseif ($category === 'fancy') {
            $basePrice += 150;
        } elseif ($category === 'cultural') {
            $basePrice += 75;
        }
        return $basePrice;
    }
    
    // Function to get category icon
    function getCategoryIcon($category) {
        switch ($category) {
            case 'celtic': return '🍀';
            case 'fancy': return '💍';
            case 'plain': return '⭕';
            case 'cultural': return '🌍';
            default: return '💎';
        }
    }
    
    // Define categories and their directories
    $categories = [
        'celtic' => [
            'path' => 'bands_php/images/celtic',
            'display_name' => 'Celtic Bands',
            'description' => 'Traditional Celtic knotwork and patterns with intricate designs'
        ],
        'fancy' => [
            'path' => 'bands_php/images/fancy',
            'display_name' => 'Designer Bands',
            'description' => 'Elegant designer bands with sophisticated styling and details'
        ],
        'plain' => [
            'path' => 'bands_php/images/plain',
            'display_name' => 'Classic Bands',
            'description' => 'Timeless classic bands with clean lines and traditional appeal'
        ],
        'cultural' => [
            'path' => 'bands_php/images/cultural',
            'display_name' => 'Cultural Bands',
            'description' => 'Culturally-inspired designs representing diverse heritage and traditions'
        ]
    ];
    
    // Generate filter buttons dynamically
    $filterButtons = [];
    foreach ($categories as $key => $category) {
        $groupedImages = groupImagesByBaseName($category['path']);
        if (!empty($groupedImages)) {
            $filterButtons[$key] = $category['display_name'];
        }
    }
    ?>
    
    <!-- Collection Header -->
    <div class="bands-header">
        <div class="collection-header">
            <h1>Bands Collection</h1>
            <p>Discover our complete collection of wedding bands, combining traditional Celtic designs with contemporary styles. Each piece represents a commitment to craftsmanship, heritage, and the promise of forever.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #8B4513;">Filter by Style</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Bands</button>
        <?php foreach ($filterButtons as $key => $displayName): ?>
        <button class="filter-btn" onclick="filterItems('<?php echo $key; ?>')"><?php echo $displayName; ?></button>
        <?php endforeach; ?>
    </div>
    
    <!-- Gallery Container -->
    <div class="gallery-container">
        <!-- Pagination Controls -->
        <div class="pagination-controls" id="pagination-controls">
            <button id="prev-page" class="pagination-btn" onclick="changePage(-1)" disabled>
                ← Previous
            </button>
            <span class="page-info" id="page-info">
                Page <span id="current-page">1</span> of <span id="total-pages">1</span>
            </span>
            <button id="next-page" class="pagination-btn" onclick="changePage(1)">
                Next →
            </button>
        </div>

        <div class="gallery-grid" id="jewelry-gallery">
            <?php
            // Generate gallery items dynamically with grouped variants
            $itemCount = 0;
            
            foreach ($categories as $categoryKey => $category) {
                $groupedImages = groupImagesByBaseName($category['path']);
                
                foreach ($groupedImages as $baseName => $imageVariants) {
                    $itemCount++;
                    
                    // Use the first image (main image) for display
                    $mainImage = $imageVariants[0];
                    $imagePath = $category['path'] . '/' . $mainImage;
                    $thumbPath = str_replace('bands_php/images/', 'bands_php/thumbs/images/', $imagePath);
                    
                    // Fallback to original image if thumbnail doesn't exist
                    if (!file_exists($thumbPath)) {
                        $thumbPath = $imagePath;
                    }
                    
                    $displayName = createDisplayName($mainImage);
                    $price = generatePrice($categoryKey, $mainImage);
                    $icon = getCategoryIcon($categoryKey);
                    $itemId = strtolower(str_replace([' ', '-', '_'], '-', $displayName . '-' . $baseName));
                    $detailUrl = 'bands_php/unified_detail.php?id=' . urlencode($baseName);
                    
                    // Count total variants for this item
                    $variantCount = count($imageVariants);
                    $variantText = $variantCount > 1 ? " ($variantCount views)" : "";
                    
                    // Show only first 6 items, hide the rest for pagination
                    $itemStyle = $itemCount > 6 ? 'style="display: none;"' : '';
                    
                    echo '<div class="jewelry-item paginated-item" data-category="' . $categoryKey . '" data-variants="' . $variantCount . '" data-item-index="' . $itemCount . '" ' . $itemStyle . '>';
                    echo '<div class="band-icon">' . $icon . '</div>';
                    
                    // Add variant indicator if multiple images exist
                    if ($variantCount > 1) {
                        echo '<div class="variant-indicator">' . $variantCount . ' views</div>';
                    }
                    
                    // Create a rotating image container with all variants as data attributes
                    echo '<div class="rotating-image-container" data-current-variant="0"';
                    if ($variantCount > 1) {
                        echo ' data-variants="' . htmlspecialchars(json_encode($imageVariants)) . '"';
                        echo ' data-category-path="' . $category['path'] . '"';
                    }
                    echo '>';
                    
                    // All items load their main image immediately (since only 6 are visible)
                    echo '<img src="' . $thumbPath . '" alt="' . $displayName . '" class="main-gallery-image rotating-image" onload="this.style.opacity=1">';
                    
                    if ($variantCount > 1) {
                        echo '<div class="rotation-indicator">▶</div>';
                    }
                    echo '</div>';
                    
                    echo '<div class="item-info">';
                    echo '<h3>' . $displayName . ' - ' . strtoupper($baseName) . $variantText . '</h3>';
                    echo '<p>' . $category['description'] . '</p>';
                    echo '<div class="item-price">Starting at $' . $price . '</div>';
                    echo '<a href="' . $detailUrl . '" class="view-details-btn">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Pagination Info -->
        <div class="pagination-info" id="pagination-info">
            Showing <span id="showing-start">1</span> - <span id="showing-end">6</span> of <span id="total-items"><?php echo $itemCount; ?></span> bands
        </div>
    </div>
    
    <!-- Heritage Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #8B4513; margin-bottom: 15px;">Celtic Heritage & Modern Craftsmanship</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Our bands collection unites ancient Celtic traditions with contemporary design excellence. Each piece tells a story of heritage, love, and commitment, crafted with precision and care to last a lifetime.
        </p>
        <a href="#formtable" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Custom Design Consultation
        </a>
    </div>
    
    <script>
    // Filter functionality
    function filterItems(category) {
        const items = document.querySelectorAll('.jewelry-item');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Filter items
        items.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.5s ease';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Image rotation functionality - with on-demand variant loading
    function rotateImage(container) {
        const variants = JSON.parse(container.dataset.variants || '[]');
        const categoryPath = container.dataset.categoryPath;
        
