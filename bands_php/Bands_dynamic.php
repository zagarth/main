<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Wedding bands, engagement rings, Celtic bands, custom rings, Cadman Manufacturing" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Bands Collection - Cadman Manufacturing</title>
<script src="../js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('bands'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    
    <?php
    // Function to scan directory and get image files
    function getImagesFromDirectory($directory) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'png' || pathinfo($file, PATHINFO_EXTENSION) === 'jpg') {
                    $images[] = $file;
                }
            }
        }
        return $images;
    }
    
    // Function to create display name from filename
    function createDisplayName($filename, $category) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove alt suffixes for display
        $name = preg_replace('/_alt\d+/', '', $name);
        
        // Add category prefix for better naming
        if ($category === 'celtic') {
            $name = 'Celtic Band ' . $name;
        } elseif ($category === 'fancy') {
            $name = 'Designer Band ' . $name;
        } elseif ($category === 'plain') {
            $name = 'Classic Band ' . $name;
        } elseif ($category === 'cultural') {
            $name = 'Cultural Band ' . $name;
        }
        
        return $name;
    }
    
    // Function to generate description based on category
    function generateDescription($category) {
        switch ($category) {
            case 'celtic':
                return 'Traditional Celtic design with intricate knotwork patterns';
            case 'fancy':
                return 'Elegant designer band with sophisticated detailing';
            case 'plain':
                return 'Timeless classic band with clean, simple lines';
            case 'cultural':
                return 'Cultural heritage design celebrating tradition';
            default:
                return 'Beautiful wedding band crafted with precision';
        }
    }
    
    // Function to generate price based on category and filename
    function generatePrice($category, $filename) {
        $basePrice = 485;
        
        if ($category === 'celtic') {
            $basePrice = 650;
        } elseif ($category === 'fancy') {
            $basePrice = 750;
            if (strpos($filename, 'T') !== false) {
                $basePrice += 150; // Titanium or special metals
            }
        } elseif ($category === 'cultural') {
            $basePrice = 595;
        } elseif ($category === 'plain') {
            $basePrice = 485;
        }
        
        // Size adjustments
        if (strpos($filename, 'L') !== false) {
            $basePrice += 50; // Ladies size premium
        } elseif (strpos($filename, 'M') !== false) {
            $basePrice += 25; // Men's size
        }
        
        return $basePrice;
    }
    
    // Function to get category icon
    function getCategoryIcon($category) {
        switch ($category) {
            case 'celtic': return '☘️';
            case 'fancy': return '💎';
            case 'plain': return '⭕';
            case 'cultural': return '🌟';
            default: return '💍';
        }
    }
    
    // Define categories and their directories
    $categories = [
        'celtic' => [
            'path' => 'bands_php/images/celtic',
            'display_name' => 'Celtic',
            'description' => 'Traditional Celtic bands with intricate knotwork'
        ],
        'fancy' => [
            'path' => 'bands_php/images/fancy',
            'display_name' => 'Designer',
            'description' => 'Sophisticated designer bands with premium details'
        ],
        'plain' => [
            'path' => 'bands_php/images/plain',
            'display_name' => 'Classic',
            'description' => 'Timeless classic bands with clean lines'
        ],
        'cultural' => [
            'path' => 'bands_php/images/cultural',
            'display_name' => 'Cultural',
            'description' => 'Cultural heritage designs celebrating tradition'
        ]
    ];
    
    // Generate filter buttons dynamically
    $filterButtons = [];
    foreach ($categories as $key => $category) {
        $images = getImagesFromDirectory($category['path']);
        if (!empty($images)) {
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
        <div class="gallery-grid" id="jewelry-gallery">
            <?php
            // Generate gallery items dynamically
            foreach ($categories as $categoryKey => $category) {
                $images = getImagesFromDirectory($category['path']);
                
                foreach ($images as $image) {
                    // Skip alternate images for main display (but keep them for detail views)
                    if (strpos($image, '_alt') !== false) continue;
                    
                    $imagePath = $category['path'] . '/' . $image;
                    $displayName = createDisplayName($image, $categoryKey);
                    $description = generateDescription($categoryKey);
                    $price = generatePrice($categoryKey, $image);
                    $icon = getCategoryIcon($categoryKey);
                    $itemId = strtolower(str_replace([' ', '-', '_'], '-', $displayName));
                    
                    echo '<div class="jewelry-item" data-category="' . $categoryKey . '">';
                    echo '<div class="celtic-pattern">' . $icon . '</div>';
                    
                    // Add special badges for certain items
                    if ($categoryKey === 'celtic') {
                        echo '<div class="heritage-badge">Heritage</div>';
                    } elseif ($categoryKey === 'fancy') {
                        echo '<div class="premium-badge">Premium</div>';
                    }
                    
                    echo '<img src="' . $imagePath . '" alt="' . $displayName . '" loading="lazy">';
                    echo '<div class="item-info">';
                    echo '<h3>' . $displayName . '</h3>';
                    echo '<p>' . $description . '</p>';
                    echo '<div class="item-price">Starting at $' . $price . '</div>';
                    echo '<a href="#" class="view-details-btn" onclick="viewDetails(\'' . $itemId . '\')">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
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
    
    // View details functionality
    function viewDetails(itemId) {
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with sizing options, metal choices, and customization features.');
    }
    
    // Add fade-in animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize gallery animation on load
    $(document).ready(function() {
        $('.jewelry-item').each(function(index) {
            $(this).delay(index * 100).animate({
                opacity: 1
            }, 500);
        });
    });
    </script>
</body>
</html>
