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
<meta name="keywords" content="Ladies jewelry, gemstone rings, pearl jewelry, Cadman Manufacturing, women's fine jewelry" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Lady's Stoneset Collection - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('ladys_stoneset'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php require_once 'includes/site_config.php'; ?>
    <?php include 'image_loader_v2.php'; // Include new image loader functions ?>
    <?php include 'includes/search_modal.php'; ?>
    
    <?php
    // Function to generate price for ladies stoneset jewelry
    function generateLadysStonesetPrice($category, $filename) {
        $basePrice = 850;
        if ($category === 'gems') {
            $basePrice = 1250;
            if (strpos($filename, '2') === 0) {
                $basePrice += 300; // Premium gemstone pieces
            }
        } elseif ($category === 'pearls') {
            $basePrice = 950;
            if (strpos($filename, 'C') === 0) {
                $basePrice += 200; // Cultured pearl pieces
            }
        }
        return $basePrice;
    }
    
    // Function to get category icon
    function getLadysStonesetCategoryIcon($category) {
        $icons = [
            'gems' => '💎',
            'pearls' => '🤍'
        ];
        return isset($icons[$category]) ? $icons[$category] : '💍';
    }
    
    // Define categories and their directories
    $categories = [
        'gems' => [
            'path' => 'ladys_stoneset_php/Gems',
            'display_name' => 'Gemstones',
            'description' => 'Beautiful rings and jewelry featuring precious and semi-precious gemstones'
        ],
        'pearls' => [
            'path' => 'ladys_stoneset_php/Pearls',
            'display_name' => 'Pearls',
            'description' => 'Elegant pearl jewelry showcasing lustrous cultured and natural pearls'
        ]
    ];
    ?>
    
    <!-- Collection Header -->
    <div class="ladys-header">
        <div class="collection-header">
            <h1>Lady's Stoneset Collection</h1>
            <p>Discover our exquisite collection of ladies' jewelry featuring precious gemstones and lustrous pearls. Each piece is designed to celebrate feminine elegance with timeless beauty and sophisticated craftsmanship.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #8B008B;">Filter by Category</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Items</button>
        <?php
        foreach ($categories as $key => $category) {
            $images = getImagesFromDirectory($category['path']);
            if (!empty($images)) {
                echo '<button class="filter-btn" onclick="filterItems(\'' . $key . '\')">' . $category['display_name'] . '</button>';
            }
        }
        ?>
    </div>
    
    <!-- Gallery Container -->
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
            <?php
            foreach ($categories as $categoryKey => $category) {
                $images = getImagesFromDirectory($category['path']);
                
                foreach ($images as $image) {
                    $displayName = createDisplayName($image);
                    $price = generateLadysStonesetPrice($categoryKey, $image);
                    $categoryIcon = getLadysStonesetCategoryIcon($categoryKey);
                    
                    // Create thumbnail path
                    $thumbnailPath = str_replace('/', '/thumbs/', $category['path']) . '/' . $image;
                    $fullImagePath = $category['path'] . '/' . $image;
                    
                    // Get item code for ProductModal
                    $itemCode = pathinfo($image, PATHINFO_FILENAME);
                    
                    echo '<div class="jewelry-item paginated-item" data-category="' . $categoryKey . '">';
                    echo '<div class="sparkle"></div>';
                    echo '<div class="category-icon">' . $categoryIcon . '</div>';
                    if (file_exists($thumbnailPath)) {
                        echo '<img src="/' . $thumbnailPath . '" alt="' . $displayName . '" loading="lazy">';
                    } else {
                        echo '<img src="/' . $fullImagePath . '" alt="' . $displayName . '" loading="lazy">';
                    }
                    echo '<div class="item-info">';
                    echo '<h3>' . $displayName . '</h3>';
                    echo '<p>' . $category['description'] . '</p>';
                    if (SHOW_PRICING) {
                        echo '<div class="item-price">Starting at $' . number_format($price) . '</div>';
                    }
                    echo '<div class="item-actions">';
                    echo '<button onclick="viewDetails(\'' . $itemCode . '\')" class="btn btn-primary">View Details</button>';
                    echo '<button class="add-to-cart-btn btn btn-secondary" ';
                    echo 'data-collection="ladies_jewelry" ';
                    echo 'data-item-id="' . strtoupper($categoryKey) . '_' . strtoupper($itemCode) . '" ';
                    echo 'data-category="' . $categoryKey . '" ';
                    echo 'data-name="' . $displayName . '" ';
                    if (SHOW_PRICING) {
                        echo 'data-price="' . $price . '" ';
                    }
                    echo 'data-image="/' . $fullImagePath . '">';
                    echo '🛒 Add to Cart</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <!-- Dynamic Pagination Controls -->
        <?php include 'includes/pagination_controls.php'; ?>
    </div>
    
    <!-- Lady's Heritage Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #2c2c2c; margin-bottom: 15px;">Feminine Elegance & Timeless Beauty</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Our Lady's Stoneset Collection celebrates the essence of feminine beauty through carefully selected gemstones and lustrous pearls. Each piece is designed to enhance your natural elegance with sophisticated craftsmanship and timeless style.
        </p>
        <a href="#" onclick="openContactModalWithTracking('ladys_stoneset', 'custom_design')" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Design Your Perfect Ring
        </a>
    </div>
    
    <script>
    // Ladies Stoneset pagination - wrapped in namespace to avoid conflicts
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

        function initializeLadysPagination() {
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
            console.log('Ladys Stoneset page loading...');
            
            // Initialize pagination
            setTimeout(() => {
                initializeLadysPagination();
                console.log('Ladys Stoneset pagination initialized');
            }, 100);
            
            console.log('Ladys Stoneset page initialized');
        });
    })();
    
    // View details functionality using ProductModal (global)
    function viewDetails(itemId) {
        console.log('viewDetails called with itemId:', itemId);
        console.log('About to call ProductModal.open');
        
        // Add some debugging to see what happens
        if (typeof ProductModal === 'undefined') {
            console.error('ProductModal is not defined!');
            alert('ProductModal not loaded');
            return;
        }
        
        // Open ProductModal for Lady's Stoneset items (no second parameter needed)
        ProductModal.open(itemId);
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
    renderFooter('ladys_stoneset');
    ?>
</body>
</html>
