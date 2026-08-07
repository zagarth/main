<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Jewelry accessories, earrings, necklaces, pendants, Cadman Manufacturing, fine jewelry" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Accessories Collection - Cadman Manufacturing</title>
<script src="../js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('accessories'); ?>
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
    function createDisplayName($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[_-]/', ' ', $name);
        $name = preg_replace('/alt\d+/', '', $name);
        $name = trim($name);
        return ucwords($name);
    }
    
    // Function to generate price based on category and filename
    function generatePrice($category, $filename) {
        $basePrice = 185;
        if (strpos($filename, 'TG') !== false || strpos($filename, 'Gold') !== false) {
            $basePrice += 150;
        }
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'TGD') !== false) {
            $basePrice += 200;
        }
        if ($category === 'crosses') {
            $basePrice += 50;
        } elseif ($category === 'earrings') {
            $basePrice += 100;
        }
        return $basePrice;
    }
    
    // Function to get category icon
    function getCategoryIcon($category) {
        switch ($category) {
            case 'crosses': return '✝️';
            case 'idents': return '🏷️';
            case 'earrings': return '💎';
            default: return '✨';
        }
    }
    
    // Define categories and their directories
    $categories = [
        'crosses' => [
            'path' => 'accessories_php/images/Crosses_and_Lockets',
            'display_name' => 'Crosses & Lockets',
            'description' => 'Beautiful crosses and lockets with traditional and contemporary designs'
        ],
        'idents' => [
            'path' => 'accessories_php/images/Idents',
            'display_name' => 'Idents',
            'description' => 'Professional identification pieces for business and formal settings'
        ],
        'earrings' => [
            'path' => 'accessories_php/images/Pendant_earrings',
            'display_name' => 'Pendant Earrings',
            'description' => 'Elegant pendant earrings and matching accessories'
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
    <div class="accessories-header">
        <div class="collection-header">
            <h1>Accessories Collection</h1>
            <p>Complete your look with our stunning collection of fine jewelry accessories. From elegant earrings to eye-catching pendants, each piece is crafted with attention to detail and designed to complement your personal style.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #0066CC;">Filter by Type</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Accessories</button>
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
                    $displayName = createDisplayName($image);
                    $price = generatePrice($categoryKey, $image);
                    $icon = getCategoryIcon($categoryKey);
                    $itemId = strtolower(str_replace([' ', '-', '_'], '-', $displayName . '-' . pathinfo($image, PATHINFO_FILENAME)));
                    
                    echo '<div class="jewelry-item" data-category="' . $categoryKey . '">';
                    echo '<div class="accessory-icon">' . $icon . '</div>';
                    echo '<img src="' . $imagePath . '" alt="' . $displayName . '" loading="lazy">';
                    echo '<div class="item-info">';
                    echo '<h3>' . $displayName . ' - ' . strtoupper(pathinfo($image, PATHINFO_FILENAME)) . '</h3>';
                    echo '<p>' . $category['description'] . '</p>';
                    echo '<div class="item-price">Starting at $' . $price . '</div>';
                    echo '<a href="#" class="view-details-btn" onclick="viewDetails(\'' . $itemId . '\')">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Care Instructions Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; max-width: 1000px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #0066CC; margin-bottom: 20px; text-align: center;">Jewelry Care & Maintenance</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left;">
            <div>
                <h3 style="color: #333; margin-bottom: 10px;">Daily Care</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Remove jewelry before sleeping or exercising</li>
                    <li>Apply lotions and perfumes before putting on jewelry</li>
                    <li>Store pieces separately to prevent scratching</li>
                </ul>
            </div>
            <div>
                <h3 style="color: #333; margin-bottom: 10px;">Cleaning</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Use a soft cloth for regular cleaning</li>
                    <li>Gentle soap solution for deeper cleaning</li>
                    <li>Professional cleaning for valuable pieces</li>
                </ul>
            </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="#formtable" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
                Get Care Instructions
            </a>
        </div>
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
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with customization options, materials, and sizing information.');
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
