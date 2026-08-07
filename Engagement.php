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
<meta name="keywords" content="Engagement rings, diamond rings, proposal rings, Cadman Manufacturing, custom engagement rings" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Engagement Rings - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('engagement'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php require_once 'includes/site_config.php'; ?>
    <?php include 'image_loader_v2.php'; ?>
    <?php include 'includes/search_modal.php'; ?>
    
    <?php
    // Function to get base name from filename (remove variant suffixes)
    function getEngagementBaseName($filename) {
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
    function groupEngagementImagesByBaseName($directory) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $grouped = [];
            
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $baseName = getEngagementBaseName($file);
                    
                    if (!isset($grouped[$baseName])) {
                        $grouped[$baseName] = [];
                    }
                    $grouped[$baseName][] = $file;
                }
            }
            
            // Sort variants for each group (main image first, then alts)
            foreach ($grouped as $baseName => &$variants) {
                usort($variants, function($a, $b) {
                    $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $a);
                    $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $b);
                    
                    if ($aIsMain && !$bIsMain) return -1;
                    if (!$aIsMain && $bIsMain) return 1;
                    return strcmp($a, $b);
                });
                
                $images[] = [
                    'baseName' => $baseName,
                    'mainImage' => $variants[0],
                    'variants' => $variants,
                    'variantCount' => count($variants)
                ];
            }
        }
        return $images;
    }

    // Engagement-specific price generation function
    function generateEngagementPrice($category, $filename) {
        $basePrice = 2850;
        if (strpos($filename, 'MM') !== false) {
            $basePrice += 500; // Premium for MM series
        }
        if (strpos($filename, 'WM') !== false) {
            $basePrice += 300; // Wedding sets
        }
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'DB') !== false) {
            $basePrice += 800; // Diamond premium
        }
        if ($category === 'MK_series') {
            $basePrice += 200; // Premium for MK series
        }
        return $basePrice;
    }
    
    // Engagement-specific category icon function
    function getEngagementCategoryIcon($category) {
        switch ($category) {
            case 'MK_series': return '💍';
            case 'MM_series': return '💎';
            case 'WM_series': return '💕';
            default: return '💖';
        }
    }
    
    // Define categories and their directories - using new organized structure
    $categories = [
        'MK_series' => [
            'path' => 'Engagement_php/images/MK_series',
            'display_name' => 'MK Collection',
            'description' => 'Our flagship engagement ring collection featuring classic and contemporary designs with brilliant diamonds'
        ],
        'MM_series' => [
            'path' => 'Engagement_php/images/MM_series',
            'display_name' => 'MM Collection',
            'description' => 'Premium marquise and specialty cut engagement rings for the discerning bride'
        ],
        'WM_series' => [
            'path' => 'Engagement_php/images/WM_series',
            'display_name' => 'Wedding Sets',
            'description' => 'Complete bridal sets with matching engagement and wedding bands for perfect harmony'
        ]
    ];
    
    // Generate all items using the same approach as other collection pages
    $allItems = [];
    $totalItems = 0;
    
    foreach ($categories as $categoryKey => $category) {
        $categoryPath = $category['path'];
        $categoryImages = groupEngagementImagesByBaseName($categoryPath);
        
        foreach ($categoryImages as $item) {
            $allItems[] = [
                'category' => $categoryKey,
                'categoryPath' => '/' . $categoryPath,
                'categoryInfo' => $category,
                'baseName' => $item['baseName'],
                'mainImage' => $item['mainImage'],
                'variants' => $item['variants'],
                'variantCount' => $item['variantCount'],
                'price' => generateEngagementPrice($categoryKey, $item['mainImage'])
            ];
            $totalItems++;
        }
    }
    
    // Generate filter buttons dynamically based on available items
    $filterButtons = [];
    foreach ($categories as $key => $category) {
        $images = groupEngagementImagesByBaseName($category['path']);
        if (!empty($images)) {
            $filterButtons[$key] = $category['display_name'];
        }
    }
    ?>
    
    <!-- Collection Header -->
    <div class="engagement-header">
        <div class="collection-header">
            <h1>Engagement Ring Collection</h1>
            <p>Begin your forever with a ring as unique as your love story. Our engagement ring collection features exquisite diamonds and precious gemstones set in timeless and contemporary designs, each crafted to capture the magic of your special moment.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #8B008B;">Filter by Collection</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Rings</button>
        <?php foreach ($filterButtons as $key => $displayName): ?>
        <button class="filter-btn" onclick="filterItems('<?php echo $key; ?>')"><?php echo getEngagementCategoryIcon($key); ?> <?php echo $displayName; ?></button>
        <?php endforeach; ?>
    </div>
    
    <!-- Gallery Container -->
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
            <?php 
            $itemIndex = 0;
            foreach ($allItems as $item): 
                $itemIndex++;
                $categoryInfo = $item['categoryInfo'];
                $mainImagePath = $item['categoryPath'] . '/' . $item['mainImage'];
                
                // Check for thumbnail with proper path handling
                $thumbPath = str_replace('images/', 'thumbs/images/', $mainImagePath);
                if (!file_exists($thumbPath)) {
                    $thumbPath = $mainImagePath;
                }
                
                // Debug output for first few items
                if ($itemIndex <= 3) {
                    echo "<!-- Debug Item $itemIndex: Main: $mainImagePath, Thumb: $thumbPath, Exists: " . (file_exists($thumbPath) ? 'yes' : 'no') . " -->\n";
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
                         data-current-variant="0"
                         onclick="rotateImage(this)">
                        <img src="<?php echo $thumbPath; ?>" 
                             alt="<?php echo createDisplayName($item['mainImage']); ?>" 
                             class="rotating-image"
                             loading="lazy"
                             onerror="this.style.opacity='1';"
                             onload="this.style.opacity='1';"
                             style="opacity: 1;">
                        <?php if ($item['variantCount'] > 1): ?>
                        <div class="rotation-indicator">▶</div>
                        <?php endif; ?>
                        <div class="sparkle"></div>
                    </div>
                    
                    <div class="item-info">
                        <h3><?php echo createDisplayName($item['mainImage']); ?></h3>
                        <p><?php echo $categoryInfo['description']; ?></p>
                        <div class="item-details">
                            <span class="category-badge"><?php echo getEngagementCategoryIcon($item['category']); ?> <?php echo $categoryInfo['display_name']; ?></span>
                            <?php if (SHOW_PRICING): ?>
                            <div class="item-price">Starting at $<?php echo number_format($item['price']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="item-actions">
                            <button onclick="viewDetails('<?php echo $item['baseName']; ?>')" class="btn btn-primary">
                                View Details
                            </button>
                            <button class="add-to-cart-btn btn btn-secondary" 
                                    data-collection="engagement"
                                    data-item-id="<?php echo strtoupper($item['category']) . '_' . strtoupper(str_replace(['-', '_', ' '], '_', $item['baseName'])); ?>"
                                    data-category="<?php echo $item['category']; ?>"
                                    data-name="<?php echo ucwords(str_replace(['_', '-'], ' ', $item['baseName'])); ?>"
                                    <?php if (SHOW_PRICING): ?>data-price="<?php echo $item['price']; ?>"<?php endif; ?>
                                    data-image="<?php echo '/' . $item['mainImage']; ?>">
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
    
    
    <!-- Diamond Education Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; max-width: 1000px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #8B008B; margin-bottom: 20px; text-align: center;">The Four C's of Diamonds</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin: 25px 0;">
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(255,105,180,0.1);">
                <h4 style="color: #DC143C; margin-bottom: 10px; font-size: 1.3em;">💎 Cut</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">The quality of angles, proportions, and polish that determines brilliance and fire</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(255,105,180,0.1);">
                <h4 style="color: #DC143C; margin-bottom: 10px; font-size: 1.3em;">🔍 Clarity</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">The absence of inclusions and blemishes visible under 10x magnification</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(255,105,180,0.1);">
                <h4 style="color: #DC143C; margin-bottom: 10px; font-size: 1.3em;">🌈 Color</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">The absence of color in white diamonds, graded from D (colorless) to Z</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(255,105,180,0.1);">
                <h4 style="color: #DC143C; margin-bottom: 10px; font-size: 1.3em;">⚖️ Carat</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">The unit of measurement for diamond weight (1 carat = 200 milligrams)</p>
            </div>
        </div>
        <div style="text-align: center; margin-top: 25px;">
            <a href="#formtable" style="background: linear-gradient(145deg, #FF69B4, #FF1493); color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
                Schedule Diamond Consultation
            </a>
        </div>
    </div>
    
    <!-- Proposal Tips Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #8B008B; margin-bottom: 15px;">Perfect Proposal Planning</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Need help planning the perfect proposal? Our experts are here to guide you through every step, from selecting the ideal ring to choosing the perfect moment. We offer discreet consultations and can create a completely custom ring that reflects your unique love story.
        </p>
        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
            <a href="#formtable" style="background: linear-gradient(145deg, #FF69B4, #FF1493); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">Ring Consultation</a>
            <a href="#formtable" style="background: linear-gradient(145deg, #8B008B, #6B0074); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">Proposal Planning</a>
            <a href="#formtable" style="background: linear-gradient(145deg, #DC143C, #B22222); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">Custom Design</a>
        </div>
    </div>
    
    
    <script>
    // Engagement pagination - wrapped in namespace to avoid conflicts
    (function() {
        let currentPage = 1;
        let itemsPerPage = 4; // Default, will be calculated dynamically
        let totalItems = 0;
        let totalPages = 1;
        let allItems = [];
        let currentFilter = 'all';

        // Function to calculate optimal items per page based on viewport
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

        function initializeEngagementPagination() {
            allItems = Array.from(document.querySelectorAll('.paginated-item'));
            totalItems = allItems.length;
            
            // Calculate optimal items per page dynamically
            itemsPerPage = calculateItemsPerPage();
            
            console.log(`Found ${totalItems} items to paginate, showing ${itemsPerPage} per page`);
            updatePagination();
        }

        function updatePagination() {
            // Filter items based on current filter
            const filteredItems = currentFilter === 'all' 
                ? allItems 
                : allItems.filter(item => item.dataset.category === currentFilter);
            
            const filteredCount = filteredItems.length;
            totalPages = Math.ceil(filteredCount / itemsPerPage);
            
            console.log(`Filtered count: ${filteredCount}, Items per page: ${itemsPerPage}, Total pages: ${totalPages}`);
            
            // Ensure current page is valid
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }
            
            // Hide all items first and remove visible class
            allItems.forEach(item => {
                item.style.display = 'none';
                item.classList.remove('visible');
            });
            
            // Show items for current page with staggered animation
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const itemsToShow = filteredItems.slice(startIndex, endIndex);
            
            console.log(`Showing items ${startIndex + 1}-${Math.min(endIndex, filteredCount)} of ${filteredCount}`);
            
            itemsToShow.forEach((item, index) => {
                item.style.display = 'block';
                setTimeout(() => {
                    item.classList.add('visible');
                }, index * 100);
            });
            
            updatePaginationControls(filteredCount);
        }

        function updatePaginationControls(filteredCount) {
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const pageInfo = document.getElementById('page-info');
            const itemsInfo = document.getElementById('items-info');
            const pageNumbersDiv = document.getElementById('page-numbers');
            
            console.log(`Updating controls: page ${currentPage} of ${totalPages}`);
            
                        // Handle Previous/Show All button
            if (prevBtn) {
                if (currentPage === 1) {
                    prevBtn.disabled = false;
                    prevBtn.style.opacity = '1';
                    prevBtn.innerHTML = '<span class="btn-icon">👁</span><span class="btn-text">Show All</span>';
                    prevBtn.onclick = showAllItems;
                } else {
                    prevBtn.disabled = false;
                    prevBtn.style.opacity = '1';
                    prevBtn.innerHTML = '<span class="btn-icon">‹</span><span class="btn-text">Previous</span>';
                    prevBtn.onclick = window.prevPage;
                }
            }
            
            if (nextBtn) {
                nextBtn.disabled = currentPage >= totalPages;
                nextBtn.style.opacity = currentPage >= totalPages ? '0.5' : '1';
            }
            
            // Create numbered page buttons (show only 3 at a time)
            if (pageNumbersDiv) {
                pageNumbersDiv.innerHTML = '';
                if (totalPages > 1) {
                    const start = Math.max(1, currentPage - 1);
                    const end = Math.min(totalPages, start + 2);
                    
                    for (let i = start; i <= end; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.textContent = i;
                        pageBtn.className = i === currentPage ? 'page-number-btn active' : 'page-number-btn';
                        pageBtn.onclick = () => window.goToPage(i);
                        pageNumbersDiv.appendChild(pageBtn);
                    }
                }
            }
            
            if (pageInfo) {
                pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            }
            
            if (itemsInfo) {
                const startItem = (currentPage - 1) * itemsPerPage + 1;
                const endItem = Math.min(currentPage * itemsPerPage, filteredCount);
                itemsInfo.textContent = `Showing ${startItem}-${endItem} of ${filteredCount} items`;
            }
            
            // Hide pagination if only one page
            const paginationControls = document.getElementById('pagination-controls');
            if (paginationControls) {
                paginationControls.style.display = totalPages <= 1 ? 'none' : 'flex';
            }
        }

        // Global functions that can be called from HTML
        window.prevPage = function() {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
                scrollToGallery();
            }
        };

        window.nextPage = function() {
            if (currentPage < totalPages) {
                currentPage++;
                updatePagination();
                scrollToGallery();
            }
        };

        window.filterItems = function(category) {
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
        };

        // Global function for direct page navigation (used by page number buttons)
        window.goToPage = function(pageNumber) {
            if (pageNumber >= 1 && pageNumber <= totalPages) {
                currentPage = pageNumber;
                updatePagination();
                scrollToGallery();
            }
        };

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
                    currentPage = 1;
                    updatePagination();
                };
                prevBtn.disabled = false;
            }
            
            // Disable Next button and hide page numbers
            if (nextBtn) {
                nextBtn.disabled = true;
                nextBtn.style.opacity = '0.5';
            }
            
            if (pageNumbersDiv) {
                pageNumbersDiv.style.display = 'none';
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

        function scrollToGallery() {
            const gallery = document.getElementById('jewelry-gallery');
            if (gallery) {
                gallery.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Image rotation functionality (simplified)
        function initializeImageRotation() {
            const rotatingContainers = document.querySelectorAll('.rotating-image-container');
            
            rotatingContainers.forEach(container => {
                // Add click handler for image rotation
                container.addEventListener('click', function() {
                    rotateImage(this);
                });
            });
        }

        function rotateImage(container) {
            const variants = JSON.parse(container.dataset.variants || '[]');
            const categoryPath = container.dataset.categoryPath;
            
            if (variants.length <= 1) return;
            
            let currentVariant = parseInt(container.dataset.currentVariant) || 0;
            currentVariant = (currentVariant + 1) % variants.length;
            
            const image = container.querySelector('.rotating-image');
            const nextVariant = variants[currentVariant];
            
            // Update image source
            let imagePath = categoryPath + '/' + nextVariant;
            let thumbPath = imagePath.replace('images/', 'thumbs/images/');
            
            // Try thumbnail first, fallback to original
            const img = new Image();
            img.onload = function() {
                image.src = thumbPath;
                container.dataset.currentVariant = currentVariant;
            };
            img.onerror = function() {
                image.src = imagePath;
                container.dataset.currentVariant = currentVariant;
            };
            img.src = thumbPath;
        }

        // Handle window resize to recalculate pagination
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                itemsPerPage = calculateItemsPerPage();
                updatePagination();
            }, 250); // Debounce resize events
        });

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Engagement page loading...');
            
            // Initialize pagination
            setTimeout(() => {
                initializeEngagementPagination();
                console.log('Engagement pagination initialized');
            }, 100);
            
            // Initialize image rotation
            setTimeout(() => {
                initializeImageRotation();
                console.log('Image rotation initialized');
            }, 200);
            
            console.log('Engagement page initialized');
        });
    })();
    
    // View details functionality using ProductModal
    function viewDetails(itemId) {
        if (typeof ProductModal !== 'undefined') {
            ProductModal.open(itemId);
        } else {
            console.error('ProductModal not loaded, falling back to unified_detail.php');
            window.location.href = 'unified_detail.php?collection=engagement&id=' + encodeURIComponent(itemId);
        }
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
    renderFooter('engagement');
    ?>
</body>
</html>
