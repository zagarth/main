<?php
// 301 Redirect: Bands.php is superseded by the SEO-friendly /wedding/bands/ URL tree.
// Preserve query string so deep links keep working.
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('HTTP/1.1 301 Moved Permanently');
header('Location: /wedding/bands/' . $qs);
header('Cache-Control: max-age=86400');
exit;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="/css/configurator.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Wedding bands, men's bands, women's bands, Cadman Manufacturing, band collection" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Bands Collection - Cadman Manufacturing</title>
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
    
    <?php include 'navigation.php'; renderNavigation('bands'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php require_once 'includes/site_config.php'; ?>
    
    <?php
    // Get base name for grouping band products - plain bands group by SERIES, others group by M/L variants
    function getBandsBaseName($filename, $category = null) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Remove _alt1, _alt2, etc. suffixes
        $name = preg_replace('/_alt\d*$/', '', $name);
        // Remove -alt1, -alt2, etc. suffixes (different naming pattern)
        $name = preg_replace('/-alt\d*$/', '', $name);
        // Remove other view suffixes like _view2, _art2
        $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
        // Remove -view2, -art2 patterns
        $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
        
        // Remove M/L gender suffixes first
        $name = preg_replace('/[ML]$/i', '', $name);
        
        // SPECIAL GROUPING FOR PLAIN BANDS - group by series for configurator
        if ($category === 'plain') {
            // Standard Tiffany: 200, 250, 300, 380, 400, 450, 500, 600, 700, 800, 900, 1000 -> "Tiffany-Standard"
            if (preg_match('/^(200|250|300|380|400|450|500|600|700|800|900|1000)$/i', $name)) {
                return 'Tiffany-Standard';
            }
            // Lightweight Tiffany 4-digit: 3001, 4001, 5001, 6001 -> "Tiffany-Lightweight"
            if (preg_match('/^\d{4}$/i', $name)) {
                return 'Tiffany-Lightweight';
            }
            // Rectangular Standard 1.5mm: 400R, 600R, 800R (but not S-series) -> "Rectangular-Standard"
            if (preg_match('/^\d+R$/i', $name) && !preg_match('/^S\d+R$/i', $name)) {
                return 'Rectangular-Standard';
            }
            // Rectangular Lightweight 1.0mm: S200R, S300R, S400R, S500R, S600R -> "Rectangular-Lightweight"
            if (preg_match('/^S\d+R$/i', $name)) {
                return 'Rectangular-Lightweight';
            }
            // Tiffany Comfort Curve T18 series: 3T18, 4T18, 5T18, 6T18, 7T18, 8T18 -> "Tiffany-Comfort-Curve"
            if (preg_match('/^\d+T18$/i', $name)) {
                return 'Tiffany-Comfort-Curve';
            }
            // Rectangular Comfort Curve T00R series: 4T00R, 5T00R, 6T00R, 7T00R, 8T00R -> "Rectangular-Comfort-Curve"
            if (preg_match('/^\d+T00R$/i', $name)) {
                return 'Rectangular-Comfort-Curve';
            }
            // Premium T-series: 400T, 500T, 550T, 600T, 700T -> "Premium-Series"
            if (preg_match('/^\d+T$/i', $name)) {
                return 'Premium-Series';
            }
        }
        
        // FOR ALL OTHER CATEGORIES (celtic, fancy, cultural) - simple M/L grouping
        // Special handling for sequential numbered fancy bands (e.g., 5770M/5771L, 5774M/5775L)
        if (preg_match('/^(\d+)([13579])$/i', $name, $matches)) {
            $baseNumber = $matches[1];
            $lastDigit = intval($matches[2]);
            $prevDigit = $lastDigit - 1;
            $name = $baseNumber . $prevDigit;
        }
        
        return $name;
    }
    
    // Function to group images by base name - same approach as Family.php
    function groupBandsImagesByBaseName($directory, $category = null) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $grouped = [];
            
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $baseName = getBandsBaseName($file, $category);
                    
                    // Normalize the base name to handle case inconsistencies
                    // Convert to uppercase for grouping (200RM, 200Rm, 200rm all group together)
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
                    $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $a);
                    $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $b);
                    
                    if ($aIsMain && !$bIsMain) return -1;
                    if (!$aIsMain && $bIsMain) return 1;
                    return strcmp($a, $b);
                });
                
                // Extract the first real product ID (without _alt suffix) for opening configurator
                $firstProductId = pathinfo($variants[0], PATHINFO_FILENAME);
                $firstProductId = preg_replace('/_alt\d*$/', '', $firstProductId);
                $firstProductId = preg_replace('/-alt\d*$/', '', $firstProductId);
                $firstProductId = preg_replace('/_(view\d*|art\d*)$/', '', $firstProductId);
                $firstProductId = preg_replace('/-(view\d*|art\d*)$/', '', $firstProductId);
                
                // For plain bands series, use the first product ID as the display name instead of series name
                $displayName = $baseName;
                $seriesBaseName = $baseName; // Keep the original series name for display
                if ($category === 'plain' && in_array($baseName, ['TIFFANY-STANDARD', 'TIFFANY-LIGHTWEIGHT', 'RECTANGULAR-STANDARD', 'RECTANGULAR-LIGHTWEIGHT', 'TIFFANY-COMFORT-CURVE', 'RECTANGULAR-COMFORT-CURVE', 'PREMIUM-SERIES'])) {
                    $displayName = $firstProductId;
                }
                
                $images[] = [
                    'baseName' => $displayName,
                    'seriesBaseName' => $seriesBaseName,
                    'firstProductId' => $firstProductId,
                    'mainImage' => $variants[0],
                    'variants' => $variants,
                    'variantCount' => count($variants)
                ];
            }
        }
        return $images;
    }
    
    // Band-specific price generation function
    function generateBandPrice($category, $filename) {
        $basePrice = 385;
        if (strpos($filename, 'Diamond') !== false || strpos($filename, 'C58') !== false) {
            $basePrice += 400;
        }
        if (strpos($filename, 'Ruby') !== false || strpos($filename, 'Emerald') !== false || strpos($filename, 'Sapphire') !== false) {
            $basePrice += 300;
        }
        if ($category === 'fancy') {
            $basePrice += 200;
        } elseif ($category === 'celtic') {
            $basePrice += 150;
        } elseif ($category === 'cultural') {
            $basePrice += 100;
        }
        return $basePrice;
    }
    
    // Band-specific category icon function
    function getBandCategoryIcon($category) {
        switch ($category) {
            case 'celtic': return '🍀';
            case 'cultural': return '🌍';
            case 'fancy': return '💎';
            case 'plain': return '💍';
            default: return '�';
        }
    }
    
    // Convert base name to proper series name for display
    function getSeriesDisplayName($baseName, $category) {
        if ($category !== 'plain') {
            return null; // Only plain bands have series names
        }
        
        $baseNameUpper = strtoupper($baseName);
        switch ($baseNameUpper) {
            case 'TIFFANY-STANDARD':
                return 'Standard Tiffany';
            case 'TIFFANY-LIGHTWEIGHT':
                return 'Tiffany Lightweight';
            case 'RECTANGULAR-STANDARD':
                return 'Rectangular Standard';
            case 'RECTANGULAR-LIGHTWEIGHT':
                return 'Rectangular Lightweight';
            case 'TIFFANY-COMFORT-CURVE':
                return 'Tiffany Comfort Curve';
            case 'RECTANGULAR-COMFORT-CURVE':
                return 'Rectangular Comfort Curve';
            case 'PREMIUM-SERIES':
                return 'Premium Series';
            default:
                return null;
        }
    }
    
    // Define categories and their directories - using actual directory structure
    $categories = [
        'celtic' => [
            'path' => 'bands_php/images/celtic',
            'display_name' => 'Celtic Bands',
            'description' => 'Traditional Celtic wedding bands with intricate knotwork designs'
        ],
        'cultural' => [
            'path' => 'bands_php/images/cultural',
            'display_name' => 'Cultural Bands',
            'description' => 'Wedding bands featuring cultural symbols and heritage designs'
        ],
        'fancy' => [
            'path' => 'bands_php/images/fancy',
            'display_name' => 'Fancy Bands',
            'description' => 'Elaborate wedding bands with ornate details and premium styling'
        ],
        'plain' => [
            'path' => 'bands_php/images/plain',
            'display_name' => 'Classic Bands',
            'description' => 'Classic wedding bands with clean, timeless designs'
        ]
    ];
    
    // Generate all items using Family.php approach
    $allItems = [];
    $totalItems = 0;
    
    foreach ($categories as $categoryKey => $category) {
        $categoryPath = $category['path'];
        $categoryImages = groupBandsImagesByBaseName($categoryPath, $categoryKey);
        
        // Debug output
        error_log("Category: $categoryKey, Path: $categoryPath, Images found: " . count($categoryImages));
        
        foreach ($categoryImages as $item) {
            $allItems[] = [
                'category' => $categoryKey,
                'categoryPath' => '/' . $categoryPath,
                'categoryInfo' => $category,
                'baseName' => $item['baseName'],
                'seriesBaseName' => $item['seriesBaseName'] ?? $item['baseName'],
                'firstProductId' => isset($item['firstProductId']) ? $item['firstProductId'] : $item['baseName'],
                'mainImage' => $item['mainImage'],
                'variants' => $item['variants'],
                'variantCount' => $item['variantCount'],
                'price' => generateBandPrice($categoryKey, $item['mainImage'])
            ];
            $totalItems++;
        }
    }
    
    echo "<!-- Total items generated: $totalItems -->\n";
    echo "<!-- Sample items: -->\n";
    foreach (array_slice($allItems, 0, 3) as $i => $item) {
        echo "<!-- Item $i: baseName={$item['baseName']}, firstProductId={$item['firstProductId']}, category={$item['category']} -->\n";
    }
    ?>
    
    <!-- Collection Header -->
    <div class="bands-header">
        <div class="collection-header">
            <h1>Wedding Bands Collection</h1>
            <p>Discover our exquisite collection of wedding bands, crafted with precision and designed to celebrate your eternal bond. From Celtic traditions to modern elegance, each band tells a story of love and commitment.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #8B4513;">Filter by Style</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Bands</button>
        <button class="filter-btn" onclick="filterItems('celtic')">🍀 Celtic Bands</button>
        <button class="filter-btn" onclick="filterItems('cultural')">🌍 Cultural Bands</button>
        <button class="filter-btn" onclick="filterItems('fancy')">💎 Fancy Bands</button>
        <button class="filter-btn" onclick="filterItems('plain')">💍 Classic Bands</button>
    </div>
    
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
        <?php 
        echo "<!-- DEBUG: About to loop through " . count($allItems) . " items -->\n";
        $itemIndex = 0;
        foreach ($allItems as $item): 
            $itemIndex++;
            echo "<!-- DEBUG: Processing item $itemIndex: {$item['baseName']} -->\n";
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
                        <span class="category-icon"><?php echo getBandCategoryIcon($item['category']); ?></span>
                        <?php echo $categoryInfo['display_name']; ?>
                    </div>
                    
                    <h3 class="item-title"><?php echo ucwords(str_replace(['_', '-'], ' ', $item['baseName'])); ?></h3>
                    
                    <?php if ($item['category'] === 'plain'): ?>
                        <?php 
                        $seriesName = getSeriesDisplayName($item['seriesBaseName'] ?? $item['baseName'], $item['category']);
                        if ($seriesName): ?>
                            <p class="item-description"><strong><?php echo $seriesName; ?> Collection</strong> - Multiple widths available</p>
                        <?php else: ?>
                            <p class="item-description">Series Collection - Multiple widths available</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="item-description"><?php echo $categoryInfo['description']; ?></p>
                    <?php endif; ?>
                    
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
                                data-collection="bands"
                                data-item-id="<?php echo strtoupper($item['category']) . '_' . strtoupper(str_replace(['-', '_', ' '], '_', $item['baseName'])); ?>"
                                data-category="<?php echo $item['category']; ?>"
                                data-name="<?php echo ucwords(str_replace(['_', '-'], ' ', $item['baseName'])); ?>"
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
    
    <script>
    // Bands pagination - wrapped in namespace to avoid conflicts
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

        function initializeBandsPagination() {
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
                // Stagger the animations for a nice effect like Family.php
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

        // Image rotation functionality (simplified)
        function initializeImageRotation() {
            const rotatingContainers = document.querySelectorAll('.rotating-image-container');
            
            rotatingContainers.forEach(container => {
                const variants = JSON.parse(container.dataset.variants || '[]');
                
                if (variants.length > 1) {
                    container.addEventListener('click', function(e) {
                        e.preventDefault();
                        rotateImage(this);
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
            console.log('Bands page loading...');
            
            // Initialize pagination
            setTimeout(() => {
                initializeBandsPagination();
                console.log('Bands pagination initialized');
            }, 100);
            
            // Initialize image rotation
            setTimeout(() => {
                initializeImageRotation();
                console.log('Image rotation initialized');
            }, 200);
            
            console.log('Bands page initialized');
        });
    })();
    
    // View details functionality (global)
    function viewDetails(itemId, category) {
        category = category || ''; // default to empty if not provided
        
        // For all band categories, open the configurator instead of unified_detail
        if (['plain', 'celtic', 'cultural', 'fancy'].includes(category)) {
            openConfigurator(itemId, category);
            return;
        }
        
        // For other categories, go to unified_detail
        let url = 'unified_detail.php?collection=bands&id=' + encodeURIComponent(itemId);
        if (category) {
            url += '&category=' + encodeURIComponent(category);
        }
        window.location.href = url;
    }
    
    // Open configurator for all band categories
    function openConfigurator(productId, category) {
        console.log('Opening ProductModal for product:', productId, 'category:', category);
        
        // Use the new ProductModal system instead of navigating to unified_detail.php
        if (typeof ProductModal !== 'undefined') {
            ProductModal.open(productId);
        } else {
            console.error('ProductModal not loaded, falling back to unified_detail.php');
            const url = 'unified_detail.php?collection=bands&id=' + encodeURIComponent(productId) + '&category=' + encodeURIComponent(category);
            window.location.href = url;
        }
    }
    
    // Debug cart modal existence
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const cartModal = document.getElementById('cartModal');
            const cartButton = document.querySelector('.cart-toggle');
            console.log('=== CART DEBUG ===');
            console.log('Cart modal exists:', !!cartModal);
            console.log('Cart button exists:', !!cartButton);
            if (cartModal) {
                console.log('Modal display style:', cartModal.style.display);
                console.log('Modal computed display:', window.getComputedStyle(cartModal).display);
            }
            if (cartButton) {
                console.log('Button classes:', cartButton.className);
            }
            console.log('CadmanCart exists:', typeof window.cadmanCart !== 'undefined');
        }, 1000);
    });
    </script>

    <?php 
    // Include ProductModal System
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    
    include 'footer.php'; 
    renderFooter('bands');
    ?>

    <!-- Search Modal JavaScript -->
    <script src="/js/search_modal.js?v=20260604_1" defer></script>
</body>
</html>
