<?php
// Start session management before any output
require_once __DIR__ . '/session_manager.php';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="/css/configurator.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Family jewelry, mother rings, father jewelry, daughter jewelry, Cadman Manufacturing, family collection" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Family Collection - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
<script>
console.log('===== BASIC JS TEST =====');
console.log('JavaScript is working!');
console.log('jQuery loaded:', typeof $ !== 'undefined');
</script>
</head>
<body>
    <!-- Search Modal -->
    <?php include 'includes/search_modal.php'; ?>
    
    <?php include 'navigation.php'; renderNavigation('family'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php require_once 'includes/site_config.php'; ?>
    
    <?php
    // Get base name for grouping family products
    function getFamilyBaseName($filename, $category = null) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Remove _alt1, _alt2, _Alt1, _Alt2 etc. suffixes (case-insensitive)
        $name = preg_replace('/_alt\d*/i', '', $name);
        // Remove -alt1, -alt2, -Alt1, -Alt2 etc. suffixes (case-insensitive)
        $name = preg_replace('/-alt\d*/i', '', $name);
        // Remove other view suffixes like _view2, _art2, _View2, _Art2 (case-insensitive)
        $name = preg_replace('/_(view\d*|art\d*)/i', '', $name);
        // Remove -view2, -art2, -View2, -Art2 patterns (case-insensitive)
        $name = preg_replace('/-(view\d*|art\d*)/i', '', $name);
        
        // No category-specific grouping - product numbers are unique identifiers
        // Variants are handled by _alt, _view, _art suffix removal above
        
        return $name;
    }
    
    // Function to group images by base name - Family collection approach
    function groupFamilyImagesByBaseName($directory, $category = null) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $grouped = [];
            
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $baseName = getFamilyBaseName($file, $category);
                    
                    // Normalize the base name to handle case inconsistencies
                    $normalizedBaseName = strtoupper($baseName);
                    
                    if (!isset($grouped[$normalizedBaseName])) {
                        $grouped[$normalizedBaseName] = [];
                    }
                    $grouped[$normalizedBaseName][] = $file;
                }
            }
            
            // Sort variants for each group (main image first, then alts)
            foreach ($grouped as $baseName => &$variants) {
                usort($variants, function($a, $b) {
                    // Check if image is main (no alt/view/art suffix) - case insensitive
                    $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/i', $a);
                    $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/i', $b);
                    
                    if ($aIsMain && !$bIsMain) return -1;
                    if (!$aIsMain && $bIsMain) return 1;
                    return strcmp($a, $b);
                });
                
                // Extract the first real product ID (without _alt suffix) for opening configurator
                $firstProductId = pathinfo($variants[0], PATHINFO_FILENAME);
                $firstProductId = preg_replace('/_alt\d*/i', '', $firstProductId);
                $firstProductId = preg_replace('/-alt\d*/i', '', $firstProductId);
                $firstProductId = preg_replace('/_(view\d*|art\d*)/i', '', $firstProductId);
                $firstProductId = preg_replace('/-(view\d*|art\d*)/i', '', $firstProductId);
                
                $images[] = [
                    'baseName' => $baseName,
                    'firstProductId' => $firstProductId,
                    'mainImage' => $variants[0],
                    'variants' => $variants,
                    'variantCount' => count($variants)
                ];
            }
        }
        return $images;
    }
    
    // Function to create display name from filename
    function createFamilyDisplayName($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Remove alt/view/art suffixes (case insensitive)
        $name = preg_replace('/_alt\d*/i', '', $name);
        $name = preg_replace('/-alt\d*/i', '', $name);
        $name = preg_replace('/_(view|art)\d*/i', '', $name);
        $name = preg_replace('/-(view|art)\d*/i', '', $name);
        // Replace underscores and hyphens with spaces
        $name = preg_replace('/[_-]/', ' ', $name);
        $name = trim($name);
        return ucwords(strtolower($name));
    }
    
    // Family-specific price generation function
    function generateFamilyPrice($category, $filename) {
        $basePrice = 285;
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'TGD') !== false) {
            $basePrice += 400;
        }
        if (strpos($filename, 'TG') !== false || strpos($filename, 'Gold') !== false) {
            $basePrice += 250;
        }
        if (strpos($filename, 'Ruby') !== false || strpos($filename, 'Emerald') !== false || strpos($filename, 'Sapphire') !== false) {
            $basePrice += 300;
        }
        if ($category === 'mother') {
            $basePrice += 150;
        } elseif ($category === 'father') {
            $basePrice += 200;
        } elseif ($category === 'daughter') {
            $basePrice += 100;
        }
        return $basePrice;
    }
    
    // Family-specific category icon function
    function getFamilyCategoryIcon($category) {
        switch ($category) {
            case 'mother': return '💐';
            case 'father': return '👔';
            case 'daughter': return '🌸';
            default: return '💍';
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
    
    // Generate all items using modern approach
    $allItems = [];
    $totalItems = 0;
    
    foreach ($categories as $categoryKey => $category) {
        $categoryPath = $category['path'];
        $categoryImages = groupFamilyImagesByBaseName($categoryPath, $categoryKey);
        
        // Debug output
        error_log("Family Category: $categoryKey, Path: $categoryPath, Images found: " . count($categoryImages));
        
        foreach ($categoryImages as $item) {
            $allItems[] = [
                'category' => $categoryKey,
                'categoryPath' => '/' . $categoryPath,
                'categoryInfo' => $category,
                'baseName' => $item['baseName'],
                'firstProductId' => isset($item['firstProductId']) ? $item['firstProductId'] : $item['baseName'],
                'mainImage' => $item['mainImage'],
                'variants' => $item['variants'],
                'variantCount' => $item['variantCount'],
                'price' => generateFamilyPrice($categoryKey, $item['mainImage'])
            ];
            $totalItems++;
        }
    }
    
    echo "<!-- Total family items generated: $totalItems -->\n";
    echo "<!-- Sample items: -->\n";
    foreach (array_slice($allItems, 0, 3) as $i => $item) {
        echo "<!-- Item $i: baseName={$item['baseName']}, firstProductId={$item['firstProductId']}, category={$item['category']} -->\n";
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
        <button class="filter-btn" onclick="filterItems('mother')">💐 Mother's Collection</button>
        <button class="filter-btn" onclick="filterItems('father')">👔 Father's Collection</button>
        <button class="filter-btn" onclick="filterItems('daughter')">🌸 Daughter's Collection</button>
    </div>
    
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
        <?php 
        echo "<!-- DEBUG: About to loop through " . count($allItems) . " family items -->\n";
        $itemIndex = 0;
        foreach ($allItems as $item): 
            $itemIndex++;
            echo "<!-- DEBUG: Processing family item $itemIndex: {$item['baseName']} -->\n";
            $categoryInfo = $item['categoryInfo'];
            $mainImagePath = $item['categoryPath'] . '/' . $item['mainImage'];
            
            // Check for thumbnail with proper path handling
            $thumbPath = str_replace('/images/', '/thumbs/images/', $mainImagePath);
            $absoluteThumbPath = __DIR__ . '/' . $thumbPath;
            if (!file_exists($absoluteThumbPath)) {
                $thumbPath = $mainImagePath;
            }
            
            // Debug output for first few items
            if ($itemIndex <= 3) {
                echo "<!-- Debug Family Item $itemIndex: Main: $mainImagePath, Thumb: $thumbPath, Exists: " . (file_exists($absoluteThumbPath) ? 'yes' : 'no') . " -->\n";
            }
        ?>
            <div class="item jewelry-item paginated-item" 
                 data-category="<?php echo $item['category']; ?>"
                 data-variants='<?php echo json_encode($item['variants']); ?>'
                 data-category-path="<?php echo $item['categoryPath']; ?>"
                 data-base-name="<?php echo $item['baseName']; ?>">
                
                <div class="rotating-image-container" 
                     data-variants='<?php echo json_encode($item['variants']); ?>'
                     data-category-path="<?php echo $item['categoryPath']; ?>"
                     data-current-variant="0">
                    
                    <img src="<?php echo $thumbPath; ?>" 
                         alt="<?php echo createFamilyDisplayName($item['mainImage']); ?>" 
                         class="rotating-image"
                         loading="lazy"
                         onerror="console.log('Failed to load family image: <?php echo $thumbPath; ?>'); this.style.opacity='1';"
                         onload="this.style.opacity='1';"
                         style="opacity: 1;">
                    
                    <?php if ($item['variantCount'] > 1): ?>
                    <div class="rotation-indicator">▶</div>
                    <?php endif; ?>
                </div>
                
                <div class="item-info">
                    <div class="category-badge">
                        <span class="category-icon"><?php echo getFamilyCategoryIcon($item['category']); ?></span>
                        <?php echo $categoryInfo['display_name']; ?>
                    </div>
                    
                    <h3 class="item-title"><?php echo createFamilyDisplayName($item['mainImage']); ?></h3>
                    
                    <p class="item-description"><?php echo $categoryInfo['description']; ?></p>
                    
                    <?php if (SHOW_PRICING): ?>
                    <div class="item-details">
                        <div class="price">$<?php echo number_format($item['price']); ?>+</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="item-actions">
                        <button onclick="viewDetails('<?php echo $item['firstProductId']; ?>', '<?php echo $item['category']; ?>')" class="btn btn-primary">
                            View Details
                        </button>
                        <button class="add-to-cart-btn btn btn-secondary" 
                                data-collection="family"
                                data-item-id="<?php echo strtoupper($item['category']) . '_' . strtoupper(str_replace(['-', '_', ' '], '_', $item['baseName'])); ?>"
                                data-category="<?php echo $item['category']; ?>"
                                data-name="<?php echo createFamilyDisplayName($item['mainImage']); ?>"
                                <?php if (SHOW_PRICING): ?>data-price="<?php echo $item['price']; ?>"<?php endif; ?>
                                data-image="<?php echo $mainImagePath; ?>">
                            🛒 Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        
        <!-- Dynamic Pagination Controls -->
        <?php include 'includes/pagination_controls.php'; ?>
    </div>
    
    <!-- Heritage Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #8B4513; margin-bottom: 15px;">Family Bonds & Precious Memories</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Our family collection celebrates the special relationships that define our lives. Each piece is thoughtfully designed to honor the unique bond between mothers, fathers, and daughters, creating lasting memories to treasure forever.
        </p>
        <a href="#formtable" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Custom Family Jewelry Consultation
        </a>
    </div>
    
    <script>
    // Image rotation functionality - with on-demand variant loading
    function rotateImage(container) {
        const variants = JSON.parse(container.dataset.variants || '[]');
        const categoryPath = container.dataset.categoryPath;
        
        if (variants.length <= 1) return;
        
        let currentVariant = parseInt(container.dataset.currentVariant) || 0;
        currentVariant = (currentVariant + 1) % variants.length;
        
        const image = container.querySelector('.rotating-image');
        const nextVariant = variants[currentVariant];
        
        // Construct image paths - prioritize thumbnails for faster loading
        let thumbPath = categoryPath.replace('family_php/images/', 'family_php/thumbs/images/') + '/' + nextVariant;
        let fullPath = categoryPath + '/' + nextVariant;
        
        // Load variant image on-demand (only when user rotates)
        const variantImg = new Image();
        
        variantImg.onload = function() {
            // Variant loaded successfully, update the display
            updateImageWithAnimation(image, thumbPath);
            container.dataset.currentVariant = currentVariant;
        };
        
        variantImg.onerror = function() {
            // Thumbnail failed, try full image
            const fallbackImg = new Image();
            fallbackImg.onload = function() {
                updateImageWithAnimation(image, fullPath);
                container.dataset.currentVariant = currentVariant;
            };
            fallbackImg.onerror = function() {
                console.warn('Failed to load image variant:', nextVariant);
                // Don't update currentVariant if image fails to load
            };
            fallbackImg.src = fullPath;
        };
        
        // Start loading the variant image only when needed
        variantImg.src = thumbPath;
    }
    
    function updateImageWithAnimation(imageElement, newSrc) {
        const container = imageElement.closest('.rotating-image-container');
        
        // Show loading state
        container.classList.add('loading');
        imageElement.style.opacity = '0';
        
        // Create a new image to implement smooth transition
        const tempImg = new Image();
        
        tempImg.onload = function() {
            // Image loaded successfully
            container.classList.remove('loading');
            imageElement.src = newSrc;
            imageElement.classList.add('image-rotating');
            imageElement.style.opacity = '1';
            
            setTimeout(() => {
                imageElement.classList.remove('image-rotating');
            }, 400);
        };
        
        tempImg.onerror = function() {
            // Failed to load
            container.classList.remove('loading');
            console.warn('Failed to load image:', newSrc);
            imageElement.style.opacity = '1'; // Show previous image
        };
        
        // Start loading
        tempImg.src = newSrc;
    }
    
    // Auto-rotation functionality
    let autoRotationIntervals = new Map();
    
    function startAutoRotation(container) {
        const variants = JSON.parse(container.dataset.variants || '[]');
        if (variants.length <= 1) return;
        
        // Stop any existing rotation first
        stopAutoRotation(container);
        
        container.classList.add('auto-rotating');
        
        const interval = setInterval(() => {
            rotateImage(container);
        }, 2500); // Slightly slower rotation for better UX
        
        autoRotationIntervals.set(container, interval);
    }
    
    function stopAutoRotation(container) {
        container.classList.remove('auto-rotating');
        
        if (autoRotationIntervals.has(container)) {
            clearInterval(autoRotationIntervals.get(container));
            autoRotationIntervals.delete(container);
        }
    }

    // Initialize image rotation handlers with modern enhancements
    function initializeImageRotation() {
        const rotatingContainers = document.querySelectorAll('.rotating-image-container');
        
        rotatingContainers.forEach(container => {
            const variants = JSON.parse(container.dataset.variants || '[]');
            
            if (variants.length > 1) {
                // Click to manually rotate with smooth transitions
                container.addEventListener('click', function(e) {
                    e.preventDefault();
                    stopAutoRotation(this);
                    rotateImage(this);
                });
                
                // Start auto-rotation on hover for better user experience
                container.addEventListener('mouseenter', function() {
                    startAutoRotation(this);
                });
                
                // Stop auto-rotation when mouse leaves
                container.addEventListener('mouseleave', function() {
                    stopAutoRotation(this);
                });
                
                // Ensure images are visible with fade-in
                const img = container.querySelector('.rotating-image');
                if (img && img.complete) {
                    img.style.opacity = '1';
                }
            }
        });
    }
    
    // Modern pagination system with responsive calculation
    let currentPage = 1;
    let itemsPerPage = 6; // Default fallback
    let totalItems = 0;
    let totalPages = 1;
    let allItems = [];
    let currentFilter = 'all';

    // Enhanced function to calculate optimal items per page based on viewport and content
    function calculateItemsPerPage() {
        const container = document.querySelector('.gallery-container');
        const grid = document.querySelector('.gallery-grid');
        
        if (!container || !grid) return 6; // Fallback
        
        // Get viewport dimensions
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        
        // Account for header, filters, pagination controls, etc.
        const headerHeight = 200; // Approximate space for header and filters
        const footerHeight = 150; // Approximate space for pagination and footer
        const availableHeight = viewportHeight - headerHeight - footerHeight;
        
        // Calculate grid layout
        const containerStyle = window.getComputedStyle(container);
        const containerPadding = parseInt(containerStyle.paddingLeft) + parseInt(containerStyle.paddingRight);
        const availableWidth = Math.min(1200, viewportWidth - 40) - containerPadding; // Max width 1200px minus margins
        
        // Item dimensions (from CSS: minmax(260px, 1fr) + gap)
        const minItemWidth = 260;
        const gap = 20;
        
        // Calculate columns that can fit
        const columns = Math.floor((availableWidth + gap) / (minItemWidth + gap));
        
        // Calculate rows that can fit (assuming ~350px per row including gap)
        const estimatedItemHeight = 350; // Item + gap
        const rows = Math.floor(availableHeight / estimatedItemHeight);
        
        // Calculate total items, with reasonable min/max bounds
        const calculatedItems = Math.max(4, Math.min(24, columns * rows));
        
        console.log(`Dynamic pagination: ${viewportWidth}x${viewportHeight} → ${columns}cols × ${rows}rows = ${calculatedItems} items`);
        
        return calculatedItems;
    }

    function initializePagination() {
        // Calculate optimal items per page for current viewport
        itemsPerPage = calculateItemsPerPage();
        
        allItems = Array.from(document.querySelectorAll('.paginated-item'));
        console.log(`Family pagination initialized: ${allItems.length} items, ${itemsPerPage} per page`);
        
        updatePagination();
    }

    function updatePagination() {
        // Filter items based on current category filter
        const filteredItems = currentFilter === 'all' 
            ? allItems 
            : allItems.filter(item => item.dataset.category === currentFilter);
        
        totalItems = filteredItems.length;
        totalPages = Math.ceil(totalItems / itemsPerPage);
        
        // Ensure current page is valid after filtering
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        
        // Hide all items first
        allItems.forEach(item => {
            item.style.display = 'none';
            item.classList.remove('visible');
        });
        
        // Show items for current page with staggered animation
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const itemsToShow = filteredItems.slice(startIndex, endIndex);
        
        itemsToShow.forEach((item, index) => {
            item.style.display = 'block';
            // Smooth staggered fade-in animation
            setTimeout(() => {
                item.classList.add('visible');
            }, index * 100);
        });
        
        // Update pagination controls with current filtered count
        updatePaginationControls(totalItems);
        
        console.log(`Family pagination: showing page ${currentPage} of ${totalPages} (${itemsToShow.length} items)`);
    }

    function updatePaginationControls(filteredCount) {
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');
        const pageNumbersDiv = document.getElementById('page-numbers');
        
        // Update Previous button
        if (prevBtn) {
            if (currentPage <= 1) {
                // On first page, offer "Show All" functionality
                prevBtn.disabled = false;
                prevBtn.style.opacity = '1';
                prevBtn.innerHTML = '<span class="btn-icon">👁</span><span class="btn-text">Show All</span>';
                prevBtn.onclick = showAllItems;
            } else {
                // Normal previous page functionality
                prevBtn.disabled = false;
                prevBtn.style.opacity = '1';
                prevBtn.innerHTML = '<span class="btn-icon">‹</span><span class="btn-text">Previous</span>';
                prevBtn.onclick = () => changePage(-1);
            }
        }
        
        // Update Next button
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.style.opacity = currentPage >= totalPages ? '0.5' : '1';
            nextBtn.onclick = () => changePage(1);
        }
        
        // Create modern numbered page buttons
        if (pageNumbersDiv) {
            pageNumbersDiv.innerHTML = '';
            
            if (totalPages > 1) {
                // Calculate visible page range (max 5 buttons)
                const maxButtons = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
                let endPage = Math.min(totalPages, startPage + maxButtons - 1);
                
                // Adjust range if near edges
                if (endPage - startPage < maxButtons - 1) {
                    startPage = Math.max(1, endPage - maxButtons + 1);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.textContent = i;
                    pageBtn.className = 'page-number-btn' + (i === currentPage ? ' active' : '');
                    pageBtn.onclick = () => goToPage(i);
                    pageNumbersDiv.appendChild(pageBtn);
                }
            }
        }
        
        // Update pagination information display
        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        }
        
        const itemsInfo = document.getElementById('items-info');
        if (itemsInfo) {
            const startItem = (currentPage - 1) * itemsPerPage + 1;
            const endItem = Math.min(currentPage * itemsPerPage, filteredCount);
            itemsInfo.textContent = `Showing ${startItem}-${endItem} of ${filteredCount} items`;
        }
        
        // Hide pagination controls if only one page
        const paginationControls = document.getElementById('pagination-controls');
        if (paginationControls) {
            paginationControls.style.display = totalPages <= 1 ? 'none' : 'flex';
        }
    }

    function changePage(direction) {
        const newPage = currentPage + direction;
        if (newPage >= 1 && newPage <= totalPages) {
            currentPage = newPage;
            updatePagination();
            scrollToGallery();
        }
    }
    
    // Smooth scroll to gallery top
    function scrollToGallery() {
        const gallery = document.getElementById('jewelry-gallery');
        if (gallery) {
            gallery.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Compatibility wrappers for shared include (calls changePage/goToPage)
    function prevPage() {
        // If on first page, the include expects prev to act as 'Show All' when applicable
        if (currentPage <= 1) {
            showAllItems();
        } else {
            changePage(-1);
        }
    }

    function nextPage() {
        changePage(1);
    }
    
    function goToPage(pageNumber) {
        if (pageNumber >= 1 && pageNumber <= totalPages) {
            currentPage = pageNumber;
            updatePagination();
            scrollToGallery();
        }
    }
    
    function filterItems(category) {
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Update filter and reset to page 1
        currentFilter = category;
        currentPage = 1;
        
        // Recalculate items per page for the new filter
        itemsPerPage = calculateItemsPerPage();
        
        updatePagination();
        scrollToGallery();
    }
    
    // Expose functions to global scope for pagination controls
    window.prevPage = prevPage;
    window.nextPage = nextPage;
    window.goToPage = goToPage;
    window.filterItems = filterItems;

    // Show all items function (replaces Previous button on first page)
    function showAllItems() {
        // Filter items based on current filter
        const filteredItems = currentFilter === 'all' 
            ? allItems 
            : allItems.filter(item => item.dataset.category === currentFilter);
        
        // Hide all items first and remove visible class
        allItems.forEach(item => {
            item.style.display = 'none';
            item.classList.remove('visible');
        });
        
        // Show all filtered items with staggered animation
        filteredItems.forEach((item, index) => {
            item.style.display = 'block';
            // Stagger the animations for a nice effect
            setTimeout(() => {
                item.classList.add('visible');
            }, index * 50); // Faster stagger for show all since there are more items
        });
        
        // Update controls for "show all" mode
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');
        const itemsInfo = document.getElementById('items-info');
        const pageNumbersDiv = document.getElementById('page-numbers');
        
        // Change Previous button to "Show Pages" to return to pagination
        if (prevBtn) {
            prevBtn.innerHTML = '<span class="btn-icon">📄</span><span class="btn-text">Show Pages</span>';
            prevBtn.onclick = function() {
                // Return to paginated view
                currentPage = 1;
                updatePagination();
            };
        }
        
        // Disable Next button and hide page numbers
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.5';
        }
        
        if (pageNumbersDiv) {
            pageNumbersDiv.innerHTML = '';
        }
        
        // Update info to show "all items"
        if (pageInfo) {
            pageInfo.textContent = 'Showing All Items';
        }
        
        if (itemsInfo) {
            itemsInfo.textContent = `Showing all ${filteredItems.length} items`;
        }
        
        console.log(`Show all mode: displaying all ${filteredItems.length} items`);
    }

    function goToPage(pageNumber) {
        if (pageNumber >= 1 && pageNumber <= totalPages) {
            currentPage = pageNumber;
            updatePagination();
            
            // Scroll to top of gallery smoothly
            const gallery = document.getElementById('jewelry-gallery');
            if (gallery) {
                gallery.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    // Enhanced category filtering with modern UX
    function filterItems(category) {
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update active button with smooth transition
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('onclick') === `filterItems('${category}')`) {
                btn.classList.add('active');
            }
        });
        
        // Update filter state and reset to page 1
        currentFilter = category;
        currentPage = 1;
        
        // Recalculate items per page in case viewport changed
        itemsPerPage = calculateItemsPerPage();
        
        // Apply filter with smooth transition
        updatePagination();
        
        // Scroll to gallery top smoothly
        const gallery = document.getElementById('jewelry-gallery');
        if (gallery) {
            gallery.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        console.log(`Family filter applied: '${category}' (${totalItems} items found)`);
    }

    // Modern cart integration system
    function addToCart(itemData) {
        console.log('Adding family item to cart:', itemData);
        
        // Visual feedback with modern styling
        const button = document.querySelector(`[data-item-id="${itemData.itemId}"]`);
        if (button) {
            const originalText = button.innerHTML;
            const originalClasses = button.className;
            
            button.innerHTML = 'Added! ✓';
            button.classList.add('btn-success', 'added');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.className = originalClasses;
            }, 2000);
        }
        
        // Integrate with existing cart system if available
        if (typeof window.cartSystem !== 'undefined') {
            window.cartSystem.add(itemData);
        }
        
        // Show toast notification
        showNotification(`${itemData.name} added to cart`, 'success');
    }

    // Enhanced details view with configurator support
    function viewDetails(productId, category) {
        console.log('Opening family product details:', productId, 'Category:', category);
        
        // Try modern modal first, fallback to page navigation
        if (typeof openProductModal === 'function') {
            openProductModal(productId, 'family', category);
        } else if (typeof ProductModal !== 'undefined') {
            ProductModal.open(productId, { collection: 'family', category: category });
        } else {
            // Fallback to detail page
            const detailUrl = `catalog_detail_modal.php?product_id=${encodeURIComponent(productId)}&collection=family&category=${encodeURIComponent(category)}`;
            window.open(detailUrl, '_blank');
        }
    }

    // Notification system for user feedback
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            backgroundColor: type === 'success' ? '#4CAF50' : '#2196F3',
            color: 'white',
            borderRadius: '4px',
            zIndex: '9999',
            transform: 'translateX(400px)',
            transition: 'transform 0.3s ease'
        });
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Animate out and remove
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Enhanced image loading with preload (like Signet and Bands)
    function loadImageWithFallback(imgElement, primarySrc, fallbackSrc) {
        const container = imgElement.closest('.jewelry-item');
        if (container) {
            container.classList.add('loading');
        }
        
        // Try primary source first
        const primaryImg = new Image();
        
        primaryImg.onload = function() {
            imgElement.src = primarySrc;
            imgElement.style.opacity = '1';
            if (container) {
                container.classList.remove('loading');
            }
        };
        
        primaryImg.onerror = function() {
            // Try fallback source
            const fallbackImg = new Image();
            
            fallbackImg.onload = function() {
                imgElement.src = fallbackSrc;
                imgElement.style.opacity = '1';
                if (container) {
                    container.classList.remove('loading');
                }
            };
            
            fallbackImg.onerror = function() {
                console.warn('Failed to load both primary and fallback images for:', primarySrc);
                imgElement.style.opacity = '1'; // Show broken image rather than infinite loading
                if (container) {
                    container.classList.remove('loading');
                }
            };
            
            fallbackImg.src = fallbackSrc;
        };
        
        // Start loading
        primaryImg.src = primarySrc;
    }
    
    // Initialize image loading for visible items
    function initializeImageLoading() {
        const visibleImages = document.querySelectorAll('.jewelry-item img.main-gallery-image');
        
        visibleImages.forEach(img => {
            // Get original src and create fallback path
            const originalSrc = img.src;
            const fallbackSrc = originalSrc.replace('/thumbs/', '/'); // Use full size if thumb fails
            
            // Set initial opacity for smooth loading (override onload attribute)
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
            img.removeAttribute('onload'); // Remove the inline onload handler
            
            // Load with fallback
            loadImageWithFallback(img, originalSrc, fallbackSrc);
        });
    }

    // Modern initialization with comprehensive setup
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Family page modern initialization starting...');
        
        // Initialize core systems in proper sequence
        initializePagination();
        
        // Setup cart button handlers with event delegation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                e.stopPropagation();
                
                const button = e.target;
                const itemData = {
                    collection: button.dataset.collection,
                    itemId: button.dataset.itemId,
                    category: button.dataset.category,
                    name: button.dataset.name,
                    price: parseFloat(button.dataset.price),
                    image: button.dataset.image
                };
                
                addToCart(itemData);
            }
        });
        
        // Initialize image loading with modern lazy loading
        setTimeout(initializeImageLoading, 100);
        
        // Initialize image rotation with enhanced UX
        setTimeout(initializeImageRotation, 300);
        
        // Show initial items with staggered animation
        setTimeout(() => {
            const visibleItems = document.querySelectorAll('.paginated-item:not([style*="display: none"])');
            visibleItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('visible');
                }, index * 100);
            });
        }, 200);
        
        console.log('Family page modern initialization complete');
    });

    // Enhanced responsive handling with debouncing
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const newItemsPerPage = calculateItemsPerPage();
            
            // Only update if the calculated items per page changed significantly
            if (Math.abs(newItemsPerPage - itemsPerPage) >= 2) {
                console.log(`Family pagination responsive update: ${itemsPerPage} → ${newItemsPerPage} items per page`);
                itemsPerPage = newItemsPerPage;
                
                // Maintain current position relative to content
                const currentFirstItem = (currentPage - 1) * itemsPerPage;
                currentPage = Math.floor(currentFirstItem / newItemsPerPage) + 1;
                
                updatePagination();
            }
        }, 250); // Debounce resize events
    });
    
    // Global function compatibility for shared pagination controls
    window.goToPage = goToPage;
    window.prevPage = () => changePage(-1);
    window.nextPage = () => changePage(1);
    window.filterItems = filterItems;
    window.viewDetails = viewDetails;
    
    // View details functionality
    function viewDetails(itemId) {
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with sizing options, metal choices, and customization features.');
    }
    
    // Add loading states for image rotation (keep only unique styles not in global CSS)
    const style = document.createElement('style');
    style.textContent = `
        .jewelry-item.loading {
            position: relative;
        }
        
        .jewelry-item.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #FFD700;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 10;
        }
    `;
    document.head.appendChild(style);
    </script>

    <!-- Modern view details functionality using ProductModal -->
    <script>
    // Legacy compatibility and enhanced modal integration
    function viewDetails(itemId) {
        console.log('Family viewDetails called with:', itemId);
        
        if (typeof openProductModal === 'function') {
            // Use modern modal system with family context
            openProductModal(itemId, 'family');
        } else if (typeof ProductModal !== 'undefined') {
            // Fallback to ProductModal class
            ProductModal.open(itemId, { collection: 'family' });
        } else {
            // Final fallback to detail page
            console.warn('No modal system available, using detail page');
            window.location.href = 'catalog_detail_modal.php?product_id=' + encodeURIComponent(itemId) + '&collection=family';
        }
    }
    
    // Enhanced cart functionality for family items
    if (typeof window.cartSystem === 'undefined') {
        window.cartSystem = {
            add: function(itemData) {
                console.log('Simple cart system: Adding', itemData);
                // Store in localStorage for persistence
                const cart = JSON.parse(localStorage.getItem('familyCart') || '[]');
                cart.push(itemData);
                localStorage.setItem('familyCart', JSON.stringify(cart));
                
                // Update cart counter if present
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    cartCounter.textContent = cart.length;
                    cartCounter.style.display = cart.length > 0 ? 'inline' : 'none';
                }
            }
        };
    }
    </script>

    <script src="/js/search_modal.js?v=20260604_1" defer></script>

    <!-- Include ProductModal System -->
    <?php
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    ?>

    <?php 
    include 'footer.php'; 
    renderFooter('family');
    ?>

</body>
</html>
