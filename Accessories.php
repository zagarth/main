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
<meta name="keywords" content="Jewelry accessories, earrings, necklaces, pendants, Cadman Manufacturing, fine jewelry" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Accessories Collection - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <!-- Search Modal -->
    <?php include 'includes/search_modal.php'; ?>
    
    <?php include 'navigation.php'; renderNavigation('accessories'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php require_once 'includes/site_config.php'; ?>
    <?php include 'image_loader_v2.php'; // Include image loader functions ?>
    
    <?php
    // Function to get base name from filename (remove variant suffixes) - same as other collections
    function getAccessoriesBaseName($filename) {
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
    
    // Function to group images by base name - same approach as other collections
    function groupAccessoriesImagesByBaseName($directory) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $grouped = [];
            
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $baseName = getAccessoriesBaseName($file);
                    
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
    
    // Accessories-specific price generation function
    function generateAccessoriesPrice($category, $filename) {
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
        } elseif ($category === 'idents') {
            $basePrice += 75;
        }
        return $basePrice;
    }
    
    // Accessories-specific category icon function
    function getAccessoriesCategoryIcon($category) {
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
    
    // Generate all items using same approach as other collections
    $allItems = [];
    $totalItems = 0;
    
    foreach ($categories as $categoryKey => $category) {
        $categoryPath = $category['path'];
        $categoryImages = groupAccessoriesImagesByBaseName($categoryPath);
        
        foreach ($categoryImages as $item) {
            $allItems[] = [
                'category' => $categoryKey,
                'categoryPath' => '/' . $categoryPath,
                'categoryInfo' => $category,
                'baseName' => $item['baseName'],
                'mainImage' => $item['mainImage'],
                'variants' => $item['variants'],
                'variantCount' => $item['variantCount'],
                'price' => generateAccessoriesPrice($categoryKey, $item['mainImage'])
            ];
            $totalItems++;
        }
    }

    // Generate filter buttons dynamically
    $filterButtons = [];
    foreach ($categories as $key => $category) {
        $categoryImages = groupAccessoriesImagesByBaseName($category['path']);
        if (!empty($categoryImages)) {
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
        <h3 style="margin-bottom: 15px; color: #8B4513;">Filter by Type</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Accessories</button>
        <button class="filter-btn" onclick="filterItems('crosses')">✝️ Crosses & Lockets</button>
        <button class="filter-btn" onclick="filterItems('idents')">🏷️ Idents</button>
        <button class="filter-btn" onclick="filterItems('earrings')">💎 Pendant Earrings</button>
    </div>
    
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
        <?php 
        $itemIndex = 0;
        foreach ($allItems as $item): 
            $itemIndex++;
            $categoryInfo = $item['categoryInfo'];
            $mainImagePath = $item['categoryPath'] . '/' . $item['mainImage'];
            
            // Check for thumbnail with proper path handling
            $thumbPath = str_replace('/images/', '/thumbs/images/', $mainImagePath);
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
                     data-current-variant="0">
                    
                    <img src="<?php echo $thumbPath; ?>" 
                         alt="<?php echo $item['baseName']; ?>" 
                         class="rotating-image"
                         loading="lazy"
                         onerror="console.log('Failed to load image: <?php echo $thumbPath; ?>'); this.style.opacity='1';"
                         onload="this.style.opacity='1';"
                         style="opacity: 1;">
                    
                    <?php if ($item['variantCount'] > 1): ?>
                    <div class="rotation-indicator">▶</div>
                    <?php endif; ?>
                </div>
                
                <div class="item-info">
                    <div class="category-badge">
                        <span class="category-icon"><?php echo getAccessoriesCategoryIcon($item['category']); ?></span>
                        <?php echo $categoryInfo['display_name']; ?>
                    </div>
                    
                    <h3 class="item-title"><?php echo ucwords(str_replace(['_', '-'], ' ', $item['baseName'])); ?></h3>
                    
                    <p class="item-description"><?php echo $categoryInfo['description']; ?></p>
                    
                    <?php if (SHOW_PRICING): ?>
                    <div class="item-details">
                        <div class="price">$<?php echo number_format($item['price']); ?>+</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="item-actions">
                        <button onclick="viewDetails('<?php echo $item['baseName']; ?>')" class="btn btn-primary">
                            View Details
                        </button>
                        <button class="add-to-cart-btn btn btn-secondary" 
                                data-collection="accessories"
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
    
    <!-- Care Instructions Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #8B4513; margin-bottom: 15px;">Jewelry Care & Maintenance</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Our accessories collection features delicate pieces that deserve proper care. From crosses and lockets to elegant earrings, each item is crafted with precision and designed to last generations with proper maintenance.
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left; margin-bottom: 20px;">
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
        <a href="#formtable" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Custom Accessories Consultation
        </a>
    </div>
    
    <script>
    // Accessories pagination - wrapped in namespace to avoid conflicts
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
            
            if (!container || !grid) return 4; // Fallback
            
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

        function initializeAccessoriesPagination() {
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
                // Stagger the animations for a nice effect
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
                if (currentPage <= 1) {
                    // On first page, make it a "Show All" button
                    prevBtn.disabled = false;
                    prevBtn.style.opacity = '1';
                    prevBtn.innerHTML = '<span class="btn-icon">👁</span><span class="btn-text">Show All</span>';
                    prevBtn.onclick = showAllItems;
                } else {
                    // On other pages, make it a normal "Previous" button
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
                    // Calculate which pages to show (3 at a time)
                    let startPage = Math.max(1, currentPage - 1);
                    let endPage = Math.min(totalPages, startPage + 2);
                    
                    // Adjust if we're near the end
                    if (endPage - startPage < 2) {
                        startPage = Math.max(1, endPage - 2);
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
                console.log(`Pagination display: ${totalPages <= 1 ? 'hidden' : 'visible'} (${totalPages} pages)`);
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

        function scrollToGallery() {
            const gallery = document.getElementById('jewelry-gallery');
            if (gallery) {
                gallery.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Image rotation functionality
        function initializeImageRotation() {
            const rotatingContainers = document.querySelectorAll('.rotating-image-container');
            
            rotatingContainers.forEach(container => {
                const variants = JSON.parse(container.dataset.variants || '[]');
                
                if (variants.length > 1) {
                    container.addEventListener('click', function(e) {
                        e.preventDefault();
                        rotateImage(this);
                    });
                    
                    // Start auto-rotation on hover
                    container.addEventListener('mouseenter', function() {
                        startAutoRotation(this);
                    });
                    
                    // Stop auto-rotation when mouse leaves
                    container.addEventListener('mouseleave', function() {
                        stopAutoRotation(this);
                    });
                }
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
            let thumbPath = imagePath.replace('/images/', '/thumbs/images/');
            
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

        // Handle window resize to recalculate pagination
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const newItemsPerPage = calculateItemsPerPage();
                
                // Only update if the calculated items per page changed significantly
                if (Math.abs(newItemsPerPage - itemsPerPage) >= 2) {
                    console.log(`Pagination updated: ${itemsPerPage} → ${newItemsPerPage} items per page`);
                    itemsPerPage = newItemsPerPage;
                    currentPage = 1; // Reset to first page
                    updatePagination();
                }
            }, 250); // Debounce resize events
        });

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Accessories page loading...');
            
            // Initialize pagination
            setTimeout(() => {
                initializeAccessoriesPagination();
                console.log('Accessories pagination initialized');
            }, 100);
            
            // Initialize image rotation
            setTimeout(() => {
                initializeImageRotation();
                console.log('Image rotation initialized');
            }, 200);
            
            console.log('Accessories page initialized');
        });
    })();
    
    // View details functionality (global)
    function viewDetails(itemId) {
        ProductModal.open(itemId, 'accessories');
    }
    </script>

    <?php 
    include 'footer.php'; 
    renderFooter('accessories');
    ?>
    <!-- Search Modal JavaScript -->
    <script src="/js/search_modal.js?v=20260604_1" defer></script>
    
    <!-- Include ProductModal System for Product Search -->
    <?php
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    ?>
</body>
</html>
