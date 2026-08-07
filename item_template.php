<?php
// Item Template for Collection Pages
// This template can be used to display individual jewelry items

function renderJewelryItem($item) {
    $categoryClass = $item['category'] ?? 'general';
    $imageAlt = $item['name'] ?? 'Jewelry Item';
    $imageSrc = $item['image'] ?? 'bands_php/images/fancy/2291.png';
    $name = $item['name'] ?? 'Jewelry Item';
    $description = $item['description'] ?? 'Beautiful jewelry piece';
    $price = $item['price'] ?? 'Contact for pricing';
    $itemId = $item['id'] ?? 'item-' . uniqid();
    $badges = $item['badges'] ?? [];
    $icon = $item['icon'] ?? '💎';
    
    echo '<div class="jewelry-item" data-category="' . htmlspecialchars($categoryClass) . '">';
    
    // Badges
    if (!empty($badges)) {
        foreach ($badges as $badge) {
            $badgeClass = $badge['type'] ?? 'default';
            $badgeText = $badge['text'] ?? 'Featured';
            echo '<div class="' . $badgeClass . '-badge">' . htmlspecialchars($badgeText) . '</div>';
        }
    }
    
    // Icon
    if ($icon) {
        echo '<div class="accessory-icon">' . $icon . '</div>';
    }
    
    // Image
    echo '<img src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($imageAlt) . '" loading="lazy">';
    
    // Item Info
    echo '<div class="item-info">';
    echo '<h3>' . htmlspecialchars($name) . '</h3>';
    echo '<p>' . htmlspecialchars($description) . '</p>';
    echo '<div class="item-price">' . htmlspecialchars($price) . '</div>';
    echo '<a href="#" class="view-details-btn" onclick="viewDetails(\'' . htmlspecialchars($itemId) . '\')">View Details</a>';
    echo '</div>';
    
    echo '</div>';
}

// Sample data structures for different collections
function getBandsData() {
    return [
        [
            'id' => 'classic-gold-band',
            'name' => 'Classic Gold Band',
            'description' => 'Timeless 14K gold wedding band with polished finish',
            'price' => 'Starting at $850',
            'category' => 'classic',
            'image' => 'bands_php/images/fancy/2291.png',
            'icon' => '💍'
        ],
        [
            'id' => 'modern-platinum-band',
            'name' => 'Modern Platinum Band',
            'description' => 'Contemporary platinum design with brushed finish',
            'price' => 'Starting at $1,250',
            'category' => 'modern',
            'image' => 'bands_php/images/fancy/2291.png',
            'icon' => '💍',
            'badges' => [['type' => 'premium', 'text' => 'Premium']]
        ]
    ];
}

function getCelticData() {
    return [
        [
            'id' => 'celtic-knot-ring',
            'name' => 'Celtic Knot Ring',
            'description' => 'Traditional endless knot symbolizing eternal love',
            'price' => 'Starting at $925',
            'category' => 'knot',
            'image' => 'bands_php/images/celtic/5310L.png',
            'icon' => '🍀'
        ],
        [
            'id' => 'claddagh-ring',
            'name' => 'Claddagh Ring',
            'description' => 'Heart, hands, and crown - love, friendship, loyalty',
            'price' => 'Starting at $875',
            'category' => 'claddagh',
            'image' => 'bands_php/images/celtic/5310M.png',
            'icon' => '🍀'
        ]
    ];
}

function getEngagementData() {
    return [
        [
            'id' => 'classic-solitaire',
            'name' => 'Classic Solitaire',
            'description' => 'Timeless elegance with a brilliant round diamond',
            'price' => 'Starting at $2,850',
            'category' => 'solitaire',
            'image' => 'signet_php/images/crest_top/C19.jpg',
            'icon' => '💎',
            'badges' => [['type' => 'premium', 'text' => 'Premium']]
        ],
        [
            'id' => 'halo-diamond-ring',
            'name' => 'Halo Diamond Ring',
            'description' => 'Center stone surrounded by brilliant diamonds',
            'price' => 'Starting at $3,250',
            'category' => 'halo',
            'image' => 'signet_php/images/jewel top/C526.jpg',
            'icon' => '💎'
        ]
    ];
}

function getAccessoriesData() {
    return [
        [
            'id' => 'diamond-stud-earrings',
            'name' => 'Diamond Stud Earrings',
            'description' => 'Classic brilliance in 14K white gold settings',
            'price' => 'Starting at $485',
            'category' => 'earrings',
            'image' => 'accessories_php/images/Pendant_earrings/UN1E.png',
            'icon' => '👂',
            'badges' => [['type' => 'new', 'text' => 'New']]
        ],
        [
            'id' => 'pearl-necklace',
            'name' => 'Cultured Pearl Necklace',
            'description' => 'Timeless elegance with lustrous cultured pearls',
            'price' => 'Starting at $325',
            'category' => 'necklaces',
            'image' => 'accessories_php/images/Crosses_and_Lockets/21.png',
            'icon' => '📿'
        ]
    ];
}

// Utility function to render a complete gallery
function renderJewelryGallery($items) {
    echo '<div class="gallery-grid" id="jewelry-gallery">';
    foreach ($items as $item) {
        renderJewelryItem($item);
    }
    echo '</div>';
}

// Filter functionality for categories
function renderCategoryFilter($categories, $activeCategory = 'all') {
    echo '<div class="category-filter">';
    echo '<h3 style="margin-bottom: 15px; color: #333;">Filter by Category</h3>';
    
    // All items button
    $activeClass = $activeCategory === 'all' ? ' active' : '';
    echo '<button class="filter-btn' . $activeClass . '" onclick="filterItems(\'all\')">All Items</button>';
    
    // Category buttons
    foreach ($categories as $key => $label) {
        $activeClass = $activeCategory === $key ? ' active' : '';
        echo '<button class="filter-btn' . $activeClass . '" onclick="filterItems(\'' . htmlspecialchars($key) . '\')">' . htmlspecialchars($label) . '</button>';
    }
    
    echo '</div>';
}

// Common JavaScript for all collection pages
function renderCollectionJavaScript() {
    echo '
    <script>
    // Filter functionality
    function filterItems(category) {
        const items = document.querySelectorAll(\'.jewelry-item\');
        const buttons = document.querySelectorAll(\'.filter-btn\');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove(\'active\'));
        event.target.classList.add(\'active\');
        
        // Filter items
        items.forEach(item => {
            if (category === \'all\' || item.dataset.category === category) {
                item.style.display = \'block\';
                item.style.animation = \'fadeIn 0.5s ease\';
            } else {
                item.style.display = \'none\';
            }
        });
    }
    
    // View details functionality
    function viewDetails(itemId) {
        // This would typically open a modal or navigate to a detail page
        console.log(\'View details for:\', itemId);
        
        // For now, show an alert with item information
        alert(\'View details for: \' + itemId + \'\\n\\nThis would open a detailed view with specifications, additional images, and customization options.\');
        
        // In a real implementation, you might do:
        // window.location.href = \'item-details.php?id=\' + itemId;
        // or open a modal with detailed information
    }
    
    // Add fade-in animation
    const style = document.createElement(\'style\');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize gallery animation on load (defer-safe)
    document.addEventListener('DOMContentLoaded', function() {
        $(\'.jewelry-item\').each(function(index) {
            $(this).delay(index * 100).animate({
                opacity: 1
            }, 500);
        });
    });
    </script>';
}
?>
