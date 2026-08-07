<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Family jewelry, mother rings, father jewelry, daughter jewelry, Cadman Manufacturing, family collection" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Family Collection - Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('family'); ?>
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
        $basePrice = 295;
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'TGD') !== false) {
            $basePrice += 250;
        }
        if (strpos($filename, 'Gold') !== false || strpos($filename, 'TG') !== false) {
            $basePrice += 200;
        }
        if ($category === 'mother') {
            $basePrice += 150;
        } elseif ($category === 'father') {
            $basePrice += 100;
        }
        return $basePrice;
    }
    
    // Function to get category icon
    function getCategoryIcon($category) {
        switch ($category) {
            case 'mother': return '💝';
            case 'father': return '👔';
            case 'daughter': return '🌸';
            default: return '👨‍👩‍👧‍👦';
        }
    }
    
    // Define categories and their directories
    $categories = [
        'mother' => [
            'path' => 'family_php/images/Mother',
            'display_name' => 'Mother\'s Collection',
            'description' => 'Beautiful jewelry pieces designed to celebrate and honor mothers with love and elegance'
        ],
        'father' => [
            'path' => 'family_php/images/Father',
            'display_name' => 'Father\'s Collection',
            'description' => 'Distinguished pieces for fathers, combining strength and sophistication'
        ],
        'daughter' => [
            'path' => 'family_php/images/Daughter',
            'display_name' => 'Daughter\'s Collection',
            'description' => 'Delicate and charming pieces perfect for daughters of all ages'
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
    <div class="family-header">
        <div class="collection-header">
            <h1>Family Collection</h1>
            <p>Celebrate the bonds that matter most with our heartwarming family jewelry collection. From mother's rings to father's keepsakes and daughter's treasures, each piece honors the special relationships that define our lives.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #8B4513;">Filter by Family Member</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Family</button>
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
                    $thumbPath = str_replace('family_php/images/', 'family_php/thumbs/images/', $imagePath);
                    
                    // Fallback to original image if thumbnail doesn't exist
                    if (!file_exists($thumbPath)) {
                        $thumbPath = $imagePath;
                    }
                    
                    $displayName = createDisplayName($image);
                    $price = generatePrice($categoryKey, $image);
                    $icon = getCategoryIcon($categoryKey);
                    $itemId = strtolower(str_replace([' ', '-', '_'], '-', $displayName . '-' . pathinfo($image, PATHINFO_FILENAME)));
                    $detailUrl = 'family_detail.php?category=' . $categoryKey . '&item=' . urlencode($image);
                    
                    echo '<div class="jewelry-item" data-category="' . $categoryKey . '">';
                    echo '<div class="family-icon">' . $icon . '</div>';
                    echo '<img src="' . $thumbPath . '" alt="' . $displayName . '" loading="lazy">';
                    echo '<div class="item-info">';
                    echo '<h3>' . $displayName . ' - ' . strtoupper(pathinfo($image, PATHINFO_FILENAME)) . '</h3>';
                    echo '<p>' . $category['description'] . '</p>';
                    echo '<div class="item-price">Starting at $' . $price . '</div>';
                    echo '<a href="' . $detailUrl . '" class="view-details-btn">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
                    <h3>Family Birthstone Ring - F1379</h3>
                    <p>Celebrate your family with birthstone representation</p>
                    <div class="item-price">Starting at $375</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('family-birthstone-ring-F1379')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F138.png" alt="Elegant Mother's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Elegant Mother's Ring - F138</h3>
                    <p>Sophisticated design honoring motherhood</p>
                    <div class="item-price">Starting at $495</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('elegant-mothers-ring-F138')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F2519.png" alt="Multi-Stone Mother's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Multi-Stone Mother's Ring - F2519</h3>
                    <p>Multiple birthstones representing each child</p>
                    <div class="item-price">Starting at $625</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('multi-stone-mothers-ring-F2519')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F2532.png" alt="Classic Family Ring" loading="lazy">
                <div class="item-info">
                    <h3>Classic Family Ring - F2532</h3>
                    <p>Timeless family ring with traditional styling</p>
                    <div class="item-price">Starting at $545</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('classic-family-ring-F2532')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F2540.png" alt="Heritage Mother's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Heritage Mother's Ring - F2540</h3>
                    <p>Heritage design celebrating maternal love</p>
                    <div class="item-price">Starting at $475</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('heritage-mothers-ring-F2540')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F2552.png" alt="Modern Mother's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Modern Mother's Ring - F2552</h3>
                    <p>Contemporary mother's ring with modern appeal</p>
                    <div class="item-price">Starting at $395</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('modern-mothers-ring-F2552')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/F2554.png" alt="Delicate Family Ring" loading="lazy">
                <div class="item-info">
                    <h3>Delicate Family Ring - F2554</h3>
                    <p>Delicate design perfect for everyday wear</p>
                    <div class="item-price">Starting at $325</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('delicate-family-ring-F2554')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/FP91.png" alt="Mother's Pendant" loading="lazy">
                <div class="item-info">
                    <h3>Mother's Pendant - FP91</h3>
                    <p>Beautiful pendant celebrating motherhood</p>
                    <div class="item-price">Starting at $275</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mothers-pendant-FP91')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/FP93.png" alt="Family Tree Pendant" loading="lazy">
                <div class="item-info">
                    <h3>Family Tree Pendant - FP93</h3>
                    <p>Symbolic family tree with birthstone leaves</p>
                    <div class="item-price">Starting at $395</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('family-tree-pendant-FP93')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="mother">
                <div class="celtic-pattern">💝</div>
                <img src="family_php/images/Mother/FPHH.png" alt="Heart Pendant" loading="lazy">
                <div class="item-info">
                    <h3>Heart Pendant - FPHH</h3>
                    <p>Loving heart pendant with family significance</p>
                    <div class="item-price">Starting at $225</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('heart-pendant-FPHH')">View Details</a>
                </div>
            </div>
            
            <!-- Father Collection Items -->
            <div class="jewelry-item" data-category="father">
                <div class="celtic-pattern">👨</div>
                <img src="family_php/images/Father/FFC41.png" alt="Father's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Father's Ring - FFC41</h3>
                    <p>Masculine ring celebrating fatherhood</p>
                    <div class="item-price">Starting at $525</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('fathers-ring-FFC41')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="father">
                <div class="celtic-pattern">👨</div>
                <img src="family_php/images/Father/FFC42.png" alt="Dad's Signet Ring" loading="lazy">
                <div class="item-info">
                    <h3>Dad's Signet Ring - FFC42</h3>
                    <p>Traditional signet ring for proud fathers</p>
                    <div class="item-price">Starting at $595</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('dads-signet-ring-FFC42')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="father">
                <div class="celtic-pattern">👨</div>
                <img src="family_php/images/Father/FS32-2.png" alt="Family Crest Ring" loading="lazy">
                <div class="item-info">
                    <h3>Family Crest Ring - FS32</h3>
                    <p>Custom family crest ring for patriarchs</p>
                    <div class="item-price">Starting at $675</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('family-crest-ring-FS32')">View Details</a>
                </div>
            </div>
            
            <!-- Daughter Collection Items -->
            <div class="jewelry-item" data-category="daughter">
                <div class="celtic-pattern">👧</div>
                <img src="family_php/images/Daughter/1208.png" alt="Daughter's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Daughter's Ring - 1208</h3>
                    <p>Sweet ring perfect for beloved daughters</p>
                    <div class="item-price">Starting at $285</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('daughters-ring-1208')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="daughter">
                <div class="celtic-pattern">👧</div>
                <img src="family_php/images/Daughter/2273.png" alt="Young Lady's Ring" loading="lazy">
                <div class="item-info">
                    <h3>Young Lady's Ring - 2273</h3>
                    <p>Elegant ring for growing daughters</p>
                    <div class="item-price">Starting at $325</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('young-ladys-ring-2273')">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Family Heritage Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #2c2c2c; margin-bottom: 15px;">Celebrating Family Bonds</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Family is the heart of everything we hold dear. Our family collection honors these precious relationships with jewelry that tells your unique story, celebrates milestones, and creates lasting memories for generations to come.
        </p>
        <a href="#formtable" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Create Your Family Jewelry
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
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with family customization options, birthstone choices, and personalization details.');
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
