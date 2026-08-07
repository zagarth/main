<?php
// Use centralized session manager for consistent session handling
require_once __DIR__ . '/session_manager.php';

// Pre-load carousel data server-side to avoid mobile fetch/CORS issues
$_carouselDataInline = null;
try {
    require_once 'includes/db_config.php';
    $_pdo = getDBConnection();
    $_configFile = __DIR__ . '/admin/carousel_filter_data.json';
    $_carouselConfig = ['active' => false];
    if (file_exists($_configFile)) {
        $_carouselConfig = json_decode(file_get_contents($_configFile), true) ?: ['active' => false];
    }
    if (!empty($_carouselConfig['active']) && !empty($_carouselConfig['filter'])) {
        $_category = $_carouselConfig['filter'];
        $_stmt = $_pdo->prepare(
            "SELECT product_id, product_name, category, image_files
             FROM catalog_products
             WHERE category = ? AND has_images = 1 AND image_files IS NOT NULL
             AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
             AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
             ORDER BY RAND() LIMIT 20"
        );
        $_stmt->execute([$_category]);
        $_rows = $_stmt->fetchAll(PDO::FETCH_ASSOC);
        $_items = [];
        foreach ($_rows as $_row) {
            if ($_row['image_files']) {
                $_items[] = [
                    'product_id' => $_row['product_id'],
                    'category'   => $_row['category'],
                    'src'        => $_row['image_files'],
                    'name'       => $_row['product_name'],
                ];
            }
        }
        if (!empty($_items)) {
            $_carouselDataInline = json_encode($_items);
        }
    }
} catch (Exception $_e) {
    $_carouselDataInline = null;
}
// Pre-load metal prices — eliminates client-side AJAX calls entirely
try {
    // db_config.php already loaded above for carousel — reuse its connection
    $_mPdo = getDBConnection();

    $_stmt = $_mPdo->query("SELECT price, recorded_at FROM gold_prices ORDER BY recorded_at DESC LIMIT 2");
    $_gRows = $_stmt->fetchAll(PDO::FETCH_ASSOC);
    $_gCurrent = $_gRows[0] ?? null;
    $_gPrev    = $_gRows[1] ?? null;
    $_stmt = $_mPdo->query("SELECT price, DATE_FORMAT(recorded_at,'%a') as day FROM (SELECT price, recorded_at FROM gold_prices ORDER BY recorded_at DESC LIMIT 10) sub ORDER BY recorded_at ASC");
    $_gHistory = $_stmt->fetchAll(PDO::FETCH_ASSOC);

    $_stmt = $_mPdo->query("SELECT price, recorded_at FROM silver_prices ORDER BY recorded_at DESC LIMIT 2");
    $_sRows = $_stmt->fetchAll(PDO::FETCH_ASSOC);
    $_sCurrent = $_sRows[0] ?? null;
    $_sPrev    = $_sRows[1] ?? null;
    $_stmt = $_mPdo->query("SELECT price, DATE_FORMAT(recorded_at,'%a') as day FROM (SELECT price, recorded_at FROM silver_prices ORDER BY recorded_at DESC LIMIT 5) sub ORDER BY recorded_at ASC");
    $_sHistory = $_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_me) {
    $_gCurrent = $_gPrev = $_gHistory = $_sCurrent = $_sPrev = $_sHistory = null;
}

// Release session lock so concurrent requests don't queue behind this page
session_write_close();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0066CC" />
<meta name="description" content="Cadman Manufacturing crafts custom jewelry, wedding bands, and engagement rings in Canada. Live gold and silver prices in CAD, 3D jewelry viewer, and authorized retailer locator." />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Permissions-Policy" content="geolocation=*">
<link rel="canonical" href="https://cadmanmfg.com/" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Cadman Manufacturing - Custom Corperate and Family Jewellery </title>

<!-- Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:url" content="https://cadmanmfg.com/" />
<meta property="og:title" content="Cadman Manufacturing - Custom Corperate and Family Jewellery" />
<meta property="og:description" content="Cadman Manufacturing crafts custom jewelry, wedding bands, and engagement rings in Canada. Live gold and silver prices in CAD, 3D jewelry viewer, and authorized retailer locator." />
<meta property="og:image" content="https://cadmanmfg.com/PNG/logo.png" />

<!-- LocalBusiness JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "JewelryStore",
  "name": "Cadman Manufacturing",
  "url": "https://cadmanmfg.com/",
  "logo": "https://cadmanmfg.com/PNG/logo.png",
  "description": "Custom jewelry manufacturer specializing in wedding bands, engagement rings, and fine jewelry in Canada.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "17 Main Street of Courtland",
    "addressLocality": "Courtland",
    "addressRegion": "ON",
    "postalCode": "N0J 1E0",
    "addressCountry": "CA"
  },
  "telephone": "+1-519-688-2121",
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday"],
      "opens": "08:00",
      "closes": "16:30"
    }
  ],
  "sameAs": []
}
</script>
<script src="js/jquery-3.6.0.min.js" defer></script>

<!-- Leaflet CSS/JS loaded lazily when map scrolls into view -->
<style>
/* Mobile responsive positioning for preload indicator */
@media (max-width: 768px) {
    #preload-indicator {
        bottom: 10px !important;
        left: 10px !important;
        right: 10px !important;
        max-width: none !important;
        padding: 8px 12px !important;
        font-size: 11px !important;
    }
    
    #preload-indicator div:first-child {
        margin-bottom: 3px !important;
    }
    
    #preload-indicator .progress-container {
        width: 100% !important;
        height: 3px !important;
    }
}

/* Custom Map Marker Styles */
.cadman-marker {
    filter: drop-shadow(0 3px 6px rgba(0,0,0,0.3));
    transition: all 0.3s ease;
}

.cadman-marker:hover {
    filter: drop-shadow(0 5px 10px rgba(0,0,0,0.4));
    transform: scale(1.05);
}

.cadman-cluster-wrapper {
    transition: all 0.3s ease;
}

.cadman-cluster-wrapper:hover {
    transform: scale(1.05);
}

.cadman-cluster-icon {
    transition: all 0.3s ease;
}

/* Map Popup Enhancements */
.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.leaflet-popup-tip {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
}
</style>
<script>
window._cadmanCarouselData = <?= !empty($_carouselDataInline) ? $_carouselDataInline : 'null' ?>;
</script>
<script src="js/carousel.js?v=20260604_1" defer></script>
</head>
<body>
    <!-- Search Modal -->
    <?php include 'includes/search_modal.php'; ?>
    
    <?php include 'navigation.php'; renderNavigation('home'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    
    <main id="wrapper">
        <!-- Continuous Scrolling Gallery Carousel -->
        <style>
        @media (max-width: 768px) {
            .carousel-section { padding: 15px 0 !important; margin-top: 4px !important; }
            .carousel-section h1 { font-size: 1.65em !important; margin-bottom: 6px !important; }
            .carousel-section > div:first-child { margin-bottom: 12px !important; }
            .carousel-hero-text { font-size: 0.9em !important; padding: 10px 16px !important; line-height: 1.5 !important; }
            .carousel-container { height: 155px !important; }
        }
        </style>
        <div class="carousel-section" style="margin-top: 10px; padding: 40px 0; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #FFD700; font-size: 2.5em; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Welcome to Cadman Manufacturing</h1>
                <style>
                    @keyframes fadeSlideIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to   { opacity: 1; transform: translateY(0); }
                    }
                </style>
                <p class="carousel-hero-text" style="padding: 14px 30px; color: rgba(255,255,255,0.75); background: rgba(255,255,255,0.07); font-size: 1.1em; line-height: 1.7; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); max-width: 760px; margin: 0 auto; animation: fadeSlideIn 1.2s ease 0.3s both;">Discover our handcrafted jewellery, our pride since 1932. Cadman's has grown from Walter Cadman travelling from town to town. Showing his jewellery to Canada. Now with the fourth generation, Jim Dickenson leads Cadmans into the 21st century. Combining artisan craftsmanship with the latest technology to create unique pieces of jewellery.</p>
            </div>
            
            <!-- Carousel Container -->
            <div class="carousel-container" style="position: relative; overflow: hidden; width: 100%; height: 200px; border-radius: 10px; background: rgba(0,0,0,0.1);">
                <!-- Carousel Track -->
                <div class="carousel-track" id="carouselTrack" style="display: flex; position: absolute; top: 0; left: 0; height: 100%; animation: scrollRight 60s linear infinite;">
                    <!-- Images will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Pause Button - positioned below carousel -->
            <div class="carousel-controls" style="text-align: center; margin-top: 20px;">
                <button id="pauseBtn" class="pause-button" style="background: rgba(0,0,0,0.8); color: #FFD700; border: 2px solid #FFD700; border-radius: 25px; padding: 10px 18px; cursor: pointer; font-size: 14px; font-weight: bold; transition: all 0.3s ease; backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                    ⏸️ Pause
                </button>
            </div>
        </div>
        
        <!-- Welcome Section -->
        <div style="text-align: center; padding: 10px 20px; background: rgba(255,255,255,0.9); margin: 10px; border-radius: 10px;">
            <h2 style="color: #333; font-size: 2.5em; margin-bottom: 20px;">Daily Metal Prices</h2>
            <p style="font-size: 1.2em; color: #666; max-width: 800px; margin: 0 auto; line-height: 1.6;">
                update provided by <a href="www.goldapi.io">goldapi</a>
            </p>
            
            <!-- Metals Grid Container -->
            <div id="metals-grid" style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 100%; overflow: hidden;">
            
            <!-- Gold Section -->
            <div style="padding: 20px; background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,255,255,0.05)); border-radius: 15px; border-left: 4px solid #FFD700; box-sizing: border-box;">
                <h3 style="color: #FFD700; margin-bottom: 20px; font-size: 1.3em; display: flex; align-items: center; gap: 10px; justify-content: center;">
                    🥇 Gold
                </h3>
                <div id="gold-price-container" style="position: relative; display: flex; flex-direction: column; gap: 15px;">
                    <!-- Skeleton Loader Overlay -->
                    <div id="gold-loading" class="skeleton-loader" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: transparent; border-radius: 15px; display: none; flex-direction: column; gap: 15px; z-index: 10;">
                        <!-- Price Skeleton -->
                        <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-height: 105px; box-sizing: border-box;">
                            <div class="skeleton-box" style="height: 14px; width: 60px; margin: 0 auto 10px;"></div>
                            <div class="skeleton-box" style="height: 35px; width: 180px; margin: 0 auto 10px;"></div>
                            <div class="skeleton-box" style="height: 14px; width: 140px; margin: 0 auto;"></div>
                        </div>
                        <!-- Chart Skeleton -->
                        <div style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <div class="skeleton-box" style="height: 180px; width: 100%; border-radius: 5px;"></div>
                        </div>
                    </div>
                    
                    <!-- Price Display -->
                    <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-height: 105px; box-sizing: border-box;">
                        <div style="color: #666; font-size: 0.85em; margin-bottom: 5px; min-height: 1.2em;">CAD/oz</div>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px; min-height: 2.2em;">
                            <div id="gold-price" style="font-size: 2em; font-weight: bold; color: #FFD700;">$—</div>
                            <div id="gold-change" style="font-size: 1.5em; font-weight: bold;">—</div>
                        </div>
                        <div id="last-updated" style="color: #999; font-size: 0.75em; margin-top: 8px; min-height: 1em;">Last updated: —</div>
                    </div>
                    
                    <!-- Chart -->
                    <div class="chart-wrapper" style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); box-sizing: border-box;">
                        <canvas id="goldChart" width="400" height="160"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Silver Section -->
            <div style="padding: 20px; background: linear-gradient(135deg, rgba(192,192,192,0.1), rgba(255,255,255,0.05)); border-radius: 15px; border-left: 4px solid #C0C0C0; box-sizing: border-box;">
                <h3 style="color: #888; margin-bottom: 20px; font-size: 1.3em; display: flex; align-items: center; gap: 10px; justify-content: center;">
                    🥈 Silver
                </h3>
                <div id="silver-price-container" style="position: relative; display: flex; flex-direction: column; gap: 15px;">
                    <!-- Skeleton Loader Overlay -->
                    <div id="silver-loading" class="skeleton-loader" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: transparent; border-radius: 15px; display: none; flex-direction: column; gap: 15px; z-index: 10;">
                        <!-- Price Skeleton -->
                        <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-height: 105px; box-sizing: border-box;">
                            <div class="skeleton-box" style="height: 14px; width: 60px; margin: 0 auto 10px;"></div>
                            <div class="skeleton-box" style="height: 35px; width: 180px; margin: 0 auto 10px;"></div>
                            <div class="skeleton-box" style="height: 14px; width: 140px; margin: 0 auto;"></div>
                        </div>
                        <!-- Chart Skeleton -->
                        <div style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <div class="skeleton-box" style="height: 180px; width: 100%; border-radius: 5px;"></div>
                        </div>
                    </div>
                    
                    <!-- Price Display -->
                    <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-height: 105px; box-sizing: border-box;">
                        <div style="color: #666; font-size: 0.85em; margin-bottom: 5px; min-height: 1.2em;">CAD/oz</div>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px; min-height: 2.2em;">
                            <div id="silver-price" style="font-size: 2em; font-weight: bold; color: #888;">$—</div>
                            <div id="silver-change" style="font-size: 1.5em; font-weight: bold;">—</div>
                        </div>
                        <div id="silver-last-updated" style="color: #999; font-size: 0.75em; margin-top: 8px; min-height: 1em;">Last updated: —</div>
                    </div>
                    
                    <!-- Chart -->
                    <div class="chart-wrapper" style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); box-sizing: border-box;">
                        <canvas id="silverChart" width="400" height="160"></canvas>
                    </div>
                </div>
            </div>
            </div>

            <style>
                @keyframes shimmer {
                    0% {
                        background-position: -468px 0;
                    }
                    100% {
                        background-position: 468px 0;
                    }
                }
                
                @keyframes fadeOut {
                    0% { opacity: 1; }
                    100% { opacity: 0; }
                }
                
                .skeleton-box {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 400% 100%;
                    animation: shimmer 1.5s ease-in-out infinite;
                    border-radius: 4px;
                }
                
                .skeleton-loader {
                    pointer-events: none;
                }
                .loading-hidden {
                    animation: fadeOut 0.3s ease forwards;
                    pointer-events: none;
                }
                
                /* Make canvases responsive */
                #goldChart, #silverChart {
                    max-width: 100%;
                    width: 100% !important;
                    height: 180px !important;
                    display: block;
                }
                .chart-wrapper {
                    overflow: visible;
                }
                
                /* Mobile responsive styles for precious metals */
                @media (max-width: 768px) {
                    #metals-grid {
                        grid-template-columns: 1fr !important;
                        padding: 0 5px;
                    }
                    
                    #metals-grid > div {
                        max-width: 100%;
                        box-sizing: border-box;
                        padding: 20px 15px !important;
                    }
                    
                    #gold-price, #silver-price {
                        font-size: 1.8em !important;
                        min-width: 120px;
                    }
                    
                    #gold-change, #silver-change {
                        font-size: 1.3em !important;
                        min-width: 30px;
                    }
                    
                    #last-updated, #silver-last-updated {
                        min-height: 1em;
                        display: block;
                    }
                }
            </style>
            
<?php
$_gPrice   = $_gCurrent ? floatval($_gCurrent['price']) : 6304.25;
$_gPrev    = $_gPrev    ? floatval($_gPrev['price'])    : $_gPrice;
$_gChange  = $_gPrice - $_gPrev;
$_gUpdated = $_gCurrent['recorded_at'] ?? null;
$_gHist    = !empty($_gHistory) ? $_gHistory : [
    ['price'=>6125.70,'day'=>'Mon'],['price'=>6199.94,'day'=>'Tue'],
    ['price'=>6183.79,'day'=>'Wed'],['price'=>6076.18,'day'=>'Thu'],['price'=>6143.65,'day'=>'Fri']
];
$_sPrice   = $_sCurrent ? floatval($_sCurrent['price']) : 39.85;
$_sPrev    = $_sPrev    ? floatval($_sPrev['price'])    : $_sPrice;
$_sChange  = $_sPrice - $_sPrev;
$_sUpdated = $_sCurrent['recorded_at'] ?? null;
$_sHist    = !empty($_sHistory) ? $_sHistory : [
    ['price'=>39.25,'day'=>'Mon'],['price'=>39.48,'day'=>'Tue'],
    ['price'=>39.62,'day'=>'Wed'],['price'=>39.15,'day'=>'Thu'],['price'=>39.85,'day'=>'Fri']
];
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" defer></script>
<script>
window._metalPrices = <?= json_encode([
    'gold'   => ['price'=>$_gPrice, 'change'=>$_gChange, 'updated'=>$_gUpdated, 'history'=>$_gHist],
    'silver' => ['price'=>$_sPrice, 'change'=>$_sChange, 'updated'=>$_sUpdated, 'history'=>$_sHist],
]) ?>;
</script>
            <script>
            // Metal prices rendered from server-injected data — zero AJAX on page load
            let goldChart, silverChart;

            function showGoldLoading() {
                const loader = document.getElementById('gold-loading');
                if (loader) {
                    loader.style.display = 'flex';
                    loader.classList.remove('loading-hidden');
                }
            }
            
            function hideGoldLoading() {
                const loader = document.getElementById('gold-loading');
                if (loader) {
                    loader.classList.add('loading-hidden');
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 300);
                }
            }

            async function fetchGoldPrice() {
                try {
                    console.log('🥇 Fetching gold price...');
                    showGoldLoading();
                    
                    const response = await fetch('gold_price_api.php', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (!response.ok) throw new Error('API request failed');
                    
                    const data = await response.json();
                    console.log('🥇 Gold API Response:', data);
                    
                    if (data.success) {
                        updateGoldDisplay(data);
                        // Use server-side price history - keep full objects with day property
                        if (data.price_history && data.price_history.length > 0) {
                            priceHistory = data.price_history;
                            console.log('🥇 Loaded price history:', priceHistory);
                        } else {
                            // Fallback: use current price if no history yet
                            priceHistory = [{ price: parseFloat(data.current_price.replace(/,/g, '')), day: 'Today' }];
                        }
                        if (typeof Chart !== 'undefined') {
                            updateChart();
                        }
                        hideGoldLoading();
                    } else {
                        console.error('Gold price API error:', data.error);
                        hideGoldLoading();
                        document.getElementById('gold-price').textContent = 'Error';
                    }
                } catch (error) {
                    console.error('🥇 Error fetching gold price:', error);
                    hideGoldLoading();
                    
                    // Use real fallback data
                    priceHistory = [
                        { price: 6975.70, day: 'Monday' },
                        { price: 7049.94, day: 'Tuesday' },
                        { price: 7033.79, day: 'Wednesday' },
                        { price: 6926.18, day: 'Thursday' },
                        { price: 6893.65, day: 'Friday' }
                    ];
                    
                    document.getElementById('gold-price').textContent = 'CA$6,893.65';
                    document.getElementById('gold-change').textContent = '▼';
                    document.getElementById('gold-change').style.color = '#dc3545';
                    
                    if (typeof Chart !== 'undefined') {
                        updateChart();
                    }
                }
            }

            function updateGoldDisplay(data) {
                // Update price with proper Canadian formatting - REMOVE COMMAS BEFORE PARSING
                const price = parseFloat(data.current_price.replace(/,/g, ''));
                document.getElementById('gold-price').textContent = `CA$${price.toLocaleString('en-CA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;;
                
                // Update trend indicator (arrow only)
                const changeAmount = parseFloat(data.change_amount.replace(/,/g, ''));
                const isPositive = changeAmount >= 0;
                
                const changeColor = isPositive ? '#28a745' : '#dc3545';
                const changeSymbol = isPositive ? '▲' : '▼';
                
                document.getElementById('gold-change').textContent = changeSymbol;
                document.getElementById('gold-change').style.color = changeColor;
                
                // Update timestamp with next update time
                const now = new Date();
                const updateInfo = data.next_update ? ` • Next: ${data.next_update}` : '';
                document.getElementById('last-updated').textContent = `Updated: ${now.toLocaleTimeString()}${updateInfo}`;
            }

            function updateChart() {
                console.log('🥇 updateChart called with priceHistory:', priceHistory);
                const ctx = document.getElementById('goldChart').getContext('2d');
                
                if (goldChart) {
                    goldChart.destroy();
                }
                
                // Process price history data - handle objects with day property
                const chartData = priceHistory.map(item => {
                    if (typeof item === 'object' && item.price) {
                        return parseFloat(item.price);
                    } else {
                        return parseFloat(item);
                    }
                });
                
                const chartLabels = priceHistory.map((item, index) => {
                    if (item.day) {
                        return item.day; // Monday, Tuesday, etc.
                    } else if (item.date) {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('en-US', { weekday: 'short' });
                    } else {
                        return `Day ${index + 1}`;
                    }
                });
                
                console.log('🥇 Chart Data:', chartData);
                console.log('🥇 Chart Labels:', chartLabels);
                
                // Calculate min/max for better scaling
                const minPrice = Math.min(...chartData);
                const maxPrice = Math.max(...chartData);
                const padding = (maxPrice - minPrice) * 0.05 || 100;
                
                goldChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Gold Price (CAD/oz)',
                            data: chartData,
                            borderColor: '#FFD700',
                            backgroundColor: 'rgba(255, 215, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 5,
                            pointBackgroundColor: '#FFD700',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#FFD700',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleColor: '#FFD700',
                                bodyColor: '#fff',
                                borderColor: '#FFD700',
                                borderWidth: 1,
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        return `CA$${parseFloat(context.parsed.y).toLocaleString('en-CA', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        })} /oz`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                min: Math.max(0, minPrice - padding),
                                max: maxPrice + padding,
                                grid: {
                                    color: 'rgba(255, 215, 0, 0.1)',
                                    drawBorder: false
                                },
                                afterBuildTicks: scale => { scale.ticks = [{value: minPrice}, {value: maxPrice}]; },
                                ticks: {
                                    color: '#666',
                                    callback: function(value) {
                                        return 'CA$' + parseFloat(value).toLocaleString('en-CA', {
                                            maximumFractionDigits: 0
                                        });
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#666',
                                    maxRotation: 45,
                                    minRotation: 0
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
                
                console.log('🥇 Chart created successfully');
            }

            // Render prices directly from server-injected data on DOMContentLoaded
            // (Chart.js defer runs before DOMContentLoaded, so Chart is available here)
            document.addEventListener('DOMContentLoaded', function() {
                const g = window._metalPrices.gold;
                const s = window._metalPrices.silver;

                // Gold display
                const gPrice = g.price;
                const gPos   = g.change >= 0;
                document.getElementById('gold-price').textContent =
                    'CA$' + gPrice.toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('gold-change').textContent = gPos ? '\u25b2' : '\u25bc';
                document.getElementById('gold-change').style.color  = gPos ? '#28a745' : '#dc3545';
                document.getElementById('last-updated').textContent =
                    g.updated ? 'Updated: ' + g.updated.slice(0,10) : '';

                // Silver display
                const sPrice = s.price;
                const sPos   = s.change >= 0;
                document.getElementById('silver-price').textContent =
                    'CA$' + sPrice.toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('silver-change').textContent = sPos ? '\u25b2' : '\u25bc';
                document.getElementById('silver-change').style.color  = sPos ? '#28a745' : '#dc3545';
                document.getElementById('silver-last-updated').textContent =
                    s.updated ? 'Updated: ' + s.updated.slice(0,10) : '';

                // Gold chart
                (function() {
                    const hist   = g.history;
                    const data   = hist.map(i => parseFloat(i.price));
                    const labels = hist.map(i => i.day || '');
                    const minP = Math.min(...data), maxP = Math.max(...data);
                    const pad  = (maxP - minP) * 0.05 || 100;
                    const ctx = document.getElementById('goldChart').getContext('2d');
                    if (goldChart) goldChart.destroy();
                    goldChart = new Chart(ctx, {
                        type: 'line',
                        data: { labels, datasets: [{
                            data, borderColor:'#FFD700', backgroundColor:'rgba(255,215,0,0.1)',
                            borderWidth:3, fill:true, tension:0.3,
                            pointRadius:5, pointBackgroundColor:'#FFD700',
                            pointBorderColor:'#fff', pointBorderWidth:2,
                            pointHoverRadius:7
                        }]},
                        options: {
                            responsive:true, maintainAspectRatio:true,
                            plugins:{ legend:{display:false}, tooltip:{
                                backgroundColor:'rgba(0,0,0,0.8)', titleColor:'#FFD700', bodyColor:'#fff',
                                borderColor:'#FFD700', borderWidth:1, padding:12,
                                callbacks:{ label: ctx => 'CA$'+parseFloat(ctx.parsed.y).toLocaleString('en-CA',{minimumFractionDigits:2})+' /oz' }
                            }},
                            scales:{
                                y:{ beginAtZero:false, min:Math.max(0,minP-pad), max:maxP+pad,
                                    grid:{color:'rgba(255,215,0,0.1)',drawBorder:false},
                                    afterBuildTicks: scale => { scale.ticks = [{value:minP},{value:maxP}]; },
                                    ticks:{color:'#666', callback: v=>'CA$'+parseFloat(v).toLocaleString('en-CA',{maximumFractionDigits:0})} },
                                x:{ grid:{display:false,drawBorder:false}, ticks:{color:'#666',maxRotation:45,minRotation:0} }
                            },
                            interaction:{intersect:false, mode:'index'}
                        }
                    });
                })();

                // Silver chart
                (function() {
                    const hist   = s.history;
                    const data   = hist.map(i => parseFloat(i.price));
                    const labels = hist.map(i => i.day || '');
                    const minP = Math.min(...data), maxP = Math.max(...data);
                    const pad  = (maxP - minP) * 0.05 || 5;
                    const ctx = document.getElementById('silverChart').getContext('2d');
                    if (silverChart) silverChart.destroy();
                    silverChart = new Chart(ctx, {
                        type: 'line',
                        data: { labels, datasets: [{
                            data, borderColor:'#C0C0C0', backgroundColor:'rgba(192,192,192,0.1)',
                            borderWidth:3, fill:true, tension:0.3,
                            pointRadius:5, pointBackgroundColor:'#C0C0C0',
                            pointBorderColor:'#fff', pointBorderWidth:2,
                            pointHoverRadius:7
                        }]},
                        options: {
                            responsive:true, maintainAspectRatio:false,
                            plugins:{ legend:{display:false}, tooltip:{
                                backgroundColor:'rgba(0,0,0,0.8)', titleColor:'#C0C0C0', bodyColor:'#fff',
                                borderColor:'#C0C0C0', borderWidth:1, padding:12,
                                callbacks:{ label: ctx => 'CA$'+parseFloat(ctx.parsed.y).toLocaleString('en-CA',{minimumFractionDigits:2})+' /oz' }
                            }},
                            scales:{
                                y:{ beginAtZero:false, min:Math.max(0,minP-pad), max:maxP+pad,
                                    grid:{color:'rgba(192,192,192,0.1)',drawBorder:false},
                                    afterBuildTicks: scale => { scale.ticks = [{value:minP},{value:maxP}]; },
                                    ticks:{color:'#666', callback: v=>'CA$'+parseFloat(v).toLocaleString('en-CA',{maximumFractionDigits:0})} },
                                x:{ grid:{display:false,drawBorder:false}, ticks:{color:'#666',maxRotation:45,minRotation:0} }
                            },
                            interaction:{intersect:false, mode:'index'}
                        }
                    });
                })();
            });
            </script>
            </p>
        </div>
        
        <!-- Authorized Retailers Locator -->
        <div style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(240,248,255,0.95)); margin: 60px 20px 40px; padding: 40px 30px; border-radius: 20px; box-shadow: 0 12px 35px rgba(0,0,0,0.15); max-width: 1200px; margin-left: auto; margin-right: auto;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: #333; font-size: 2.2em; margin-bottom: 15px; font-weight: bold;">🏪 Find an Authorized Retailer</h2>
                <p style="color: #666; font-size: 1.1em; line-height: 1.6; max-width: 700px; margin: 0 auto;">
                    Discover Cadman Manufacturing jewelry at authorized retailers across Canada. Our trusted partners provide expert service and authentic Cadman products.
                </p>
            </div>
            
            <!-- Full-Width Interactive Map -->
            <div style="background: rgba(255,255,255,0.9); padding: 25px; border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); margin-bottom: 30px;">
                
                <!-- Leaflet Map Container with Zoom Toggle -->
                <div style="position: relative; width: 100%; height: 500px; border-radius: 15px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.15);">
                    <!-- Map placeholder prevents CLS; replaced once Leaflet loads -->
                    <div id="map-placeholder" style="width:100%;height:500px;background:linear-gradient(135deg,#e8f0f8,#d0e4f7);display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:15px;">
                        <div style="font-size:3em;">🗺️</div>
                        <div style="color:#555;font-size:1em;margin-top:12px;">Map loading…</div>
                    </div>
                    <div id="canada-map" style="width:100%;height:500px;min-height:500px;display:none;"></div>
                    
                    <!-- Scroll Zoom Toggle Button -->
                    <button id="scroll-zoom-toggle" onclick="toggleScrollZoom()" style="
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        z-index: 1000;
                        background: rgba(255, 255, 255, 0.9);
                        border: 2px solid #ccc;
                        border-radius: 6px;
                        padding: 8px 12px;
                        cursor: pointer;
                        font-size: 12px;
                        font-weight: 500;
                        color: #333;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                        transition: all 0.3s ease;
                        backdrop-filter: blur(5px);
                    " onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.borderColor='#0066CC';" onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.borderColor='#ccc';">
                        🖱️ Scroll Zoom: ON
                    </button>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                        📍 Interactive map showing all <strong><span id="retailer-count">loading...</span> authorized retailers</strong> across Canada<br>
                        <small style="color: #999;">Click and drag to explore • Use zoom controls to find retailers near you</small>
                    </p>
                    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                                <button id="view-all-btn" onclick="viewAllCanada()" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.85em; font-weight: 500; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                                    🇨🇦 View All Canada
                                </button>
                        <button id="find-nearest-btn" onclick="findnearme()" style="background: linear-gradient(145deg, #28a745, #1e7e34); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.85em; font-weight: 500; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                            📍 Find Nearest
                        </button>
                        <button onclick="openSearchModal(); setTimeout(() => showAllRetailersList(), 100);" style="background: linear-gradient(145deg, #6c757d, #495057); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.85em; font-weight: 500; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                            📋 View List
                        </button>
                        <a href="/retailers.php" style="display: inline-block; background: linear-gradient(145deg, #5a3e8c, #3d2a60); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.85em; font-weight: 500; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                            🗂️ All Retailers
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Become a Retailer Section -->
            <div style="margin-top: 40px; padding: 25px; background: linear-gradient(135deg, rgba(0,102,204,0.1), rgba(0,102,204,0.05)); border-radius: 15px; border-left: 4px solid #0066CC; text-align: center;">
                <h3 style="color: #0066CC; margin-bottom: 15px; font-size: 1.3em;">Interested in becoming an authorized retailer?</h3>
                <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">Join our network of trusted partners and offer your customers exceptional Cadman Manufacturing jewelry. We provide comprehensive support, training, and marketing materials.</p>
                <a href="javascript:void(0);" onclick="openContactModalWithTracking(&quot;Homepage&quot;, &quot;Become a Retailer CTA&quot;, &quot;I am interested in becoming an authorized Cadman Manufacturing retailer. Please provide information about partnership opportunities.&quot;);" style="display: inline-block; background: linear-gradient(145deg, #0066CC, #004499); color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,102,204,0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,102,204,0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,102,204,0.3)';"  >
                    🤝 Partner With Us
                </a>
            </div>
        </div>
        
        <!-- Enhanced Retailer Map JavaScript -->
        <script src="js/retailer_map.js" defer></script>
        
        <!-- Custom CSS for Leaflet markers and animations -->
        <style>
        .custom-marker {
            background: transparent !important;
            border: none !important;
        }
        
        .leaflet-popup-content-wrapper {
            border-radius: 10px !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
        }
        
        .leaflet-popup-tip {
            background: white !important;
        }
        
        /* User location marker animation */
        .user-location-marker {
            animation: userLocationPulse 2s ease-in-out infinite;
        }
        
        @keyframes userLocationPulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Loading spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Notification slide down animation */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        /* Ensure Leaflet zoom controls are properly styled and positioned */
        .leaflet-control-zoom {
            border: none !important;
            border-radius: 6px !important;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2) !important;
        }
        
        .leaflet-control-zoom a {
            background-color: white !important;
            color: #333 !important;
            border: none !important;
            border-radius: 0 !important;
            width: 30px !important;
            height: 30px !important;
            line-height: 30px !important;
            text-align: center !important;
            text-decoration: none !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        
        .leaflet-control-zoom a:hover {
            background-color: #f0f0f0 !important;
            color: #0066CC !important;
        }
        
        .leaflet-control-zoom a:first-child {
            border-top-left-radius: 6px !important;
            border-top-right-radius: 6px !important;
        }
        
        .leaflet-control-zoom a:last-child {
            border-bottom-left-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
        }
        
        /* Ensure map container has proper positioning */
        #canada-map {
            position: relative !important;
            z-index: 1 !important;
        }
        
        /* Continuous Scrolling Carousel Styles */
        .carousel-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 200px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            background: linear-gradient(45deg, rgba(255,215,0,0.1), rgba(255,255,255,0.05));
        }
        
        .carousel-track {
            display: flex;
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            animation: scrollRight 60s linear infinite;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            will-change: transform;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }
        
        .carousel-track.paused {
            animation-play-state: paused;
        }
        
        .carousel-item {
            flex: none;
            width: 160px;
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            border: 3px solid transparent;
        }
        
        .carousel-item:hover {
            transform: scale(1.15);
            box-shadow: 0 15px 40px rgba(255,215,0,0.4);
            border-color: #FFD700;
            z-index: 5;
        }
        
        .carousel-item.celtic {
            border-color: rgba(34, 139, 34, 0.5);
        }
        
        .carousel-item.regular {
            border-color: rgba(70, 130, 180, 0.5);
        }
        
        .carousel-item:hover.celtic {
            border-color: #228B22;
            box-shadow: 0 15px 40px rgba(34, 139, 34, 0.4);
        }
        
        .carousel-item:hover.regular {
            border-color: #4682B4;
            box-shadow: 0 15px 40px rgba(70, 130, 180, 0.4);
        }
        
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .carousel-item:hover img {
            transform: scale(1.1);
        }
        
        .carousel-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30%;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .carousel-item:hover::after {
            opacity: 1;
        }
        
        .item-label {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            color: white;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        .carousel-item:hover .item-label {
            opacity: 1;
        }
        
        .pause-button {
            background: rgba(0,0,0,0.7);
            color: #FFD700;
            border: 2px solid #FFD700;
            border-radius: 25px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .pause-button:hover {
            background: rgba(255,215,0,0.2);
            transform: scale(1.05);
        }
        
        .pause-button.playing {
            color: #4CAF50;
            border-color: #4CAF50;
        }
        
        @keyframes scrollRight {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        /* Mobile Responsive Carousel */
        @media (max-width: 768px) {
            .carousel-section {
                padding: 20px 0 !important;
                margin-top: 5px !important;
            }
            
            .carousel-container {
                height: 140px !important;
                margin: 0 10px;
                overflow: hidden;
            }
            
            .carousel-track {
                gap: 20px !important;
                padding: 10px 0 !important;
                animation: scrollRight 40s linear infinite !important;
            }
            
            .carousel-item {
                width: 120px !important;
                height: 120px !important;
                flex-shrink: 0;
                min-width: 120px;
            }
            
            .carousel-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .item-label {
                font-size: 0.7em !important;
                padding: 2px 4px !important;
            }
            
            .pause-button {
                padding: 8px 12px !important;
                font-size: 12px !important;
            }
            
            /* Hide carousel section titles on mobile for more space */
            .carousel-section h2 {
                font-size: 1.8em !important;
                margin-bottom: 15px !important;
            }
            
            .carousel-section p {
                font-size: 1em !important;
                margin-bottom: 20px !important;
            }
        }
        
        @media (max-width: 480px) {
            .carousel-section {
                padding: 15px 0 !important;
                margin: 5px !important;
            }
            
            .carousel-container {
                height: 120px !important;
                margin: 0 5px;
            }
            
            .carousel-track {
                gap: 15px !important;
                padding: 5px 0 !important;
                animation: scrollRight 35s linear infinite !important;
            }
            
            .carousel-item {
                width: 100px !important;
                height: 100px !important;
                min-width: 100px;
            }
            
            .carousel-section h2 {
                font-size: 1.5em !important;
                margin-bottom: 10px !important;
            }
            
            .carousel-section p {
                font-size: 0.9em !important;
                margin-bottom: 15px !important;
            }
        }
        
        /* Enhanced Gallery Styles */
        #prevThumbnail:hover img, #nextThumbnail:hover img {
            opacity: 1 !important;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3) !important;
        }
        
        /* Responsive gallery adjustments */
        @media (max-width: 768px) {
            .midimg > div {
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            #prevThumbnail, #nextThumbnail {
                display: none !important;
            }
            
            #midimg {
                max-width: 90% !important;
            }
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
            100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
        }
        
        /* Static map pin animations */
        @keyframes pinPulse {
            0% { box-shadow: 0 2px 8px rgba(0,102,204,0.4); }
            50% { box-shadow: 0 4px 15px rgba(0,102,204,0.7); }
            100% { box-shadow: 0 2px 8px rgba(0,102,204,0.4); }
        }
        
        @keyframes pinHighlight {
            0% { transform: translate(-50%, -50%) scale(1); background: linear-gradient(145deg, #0066CC, #004499); }
            50% { transform: translate(-50%, -50%) scale(1.5); background: linear-gradient(145deg, #ff6b35, #e55100); }
            100% { transform: translate(-50%, -50%) scale(1); background: linear-gradient(145deg, #0066CC, #004499); }
        }
        
        /* Static pin hover effects */
        .static-pin:hover .pin-tooltip {
            opacity: 1 !important;
        }
        
        .static-pin:hover div:first-child {
            transform: scale(1.3) !important;
            box-shadow: 0 4px 15px rgba(0,102,204,0.8) !important;
        }
        
        /* Mobile Responsive Styles for Retailer Section */
        @media (max-width: 768px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
            
            div[style*="grid-template-columns: 1fr auto"] {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }
            
            .retailer-item {
                padding: 15px !important;
            }
            
            /* Mobile map controls */
            div[style*="flex-wrap: wrap"] {
                flex-direction: column !important;
                gap: 8px !important;
            }
            
            div[style*="flex-wrap: wrap"] button,
            div[style*="flex-wrap: wrap"] a {
                width: 100% !important;
                text-align: center !important;
                margin: 0 !important;
            }
        }
        
        /* Button hover effects */
        button[id$="-btn"]:hover {
            box-shadow: 0 6px 20px rgba(0,102,204,0.4) !important;
        }
        
        button[id="find-nearest-btn"]:hover {
            box-shadow: 0 6px 20px rgba(40,167,69,0.4) !important;
        }
        
        .collection-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        </style>
        
        <!-- 3D Services Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
            <div class="collection-card" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                <a href="javascript:void(0);" style="text-decoration: none; color: inherit;" onclick="openContactModalWithTracking('Homepage', 'Corporate Services Card', 'I am interested in Corporate Services. Please provide more information about your corporate jewelry solutions.');">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3em; margin-bottom: 15px; color: #0066CC;">🏢</div>
                        <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5em;">Corporate Service</h3>
                        <p style="color: #666; line-height: 1.6;">Professional corporate jewelry solutions for businesses, institutions, and organizations. Custom designs that represent your brand with excellence.</p>
                    </div>
                </a>
            </div>
            
            <div class="collection-card" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                <a href="javascript:void(0);" style="text-decoration: none; color: inherit;" onclick="openContactModalWithTracking('Homepage', 'Custom Engagement Card', 'I am interested in Custom Engagement rings. Please help me create the perfect engagement ring for my special someone.');">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3em; margin-bottom: 15px; color: #DC143C;">💎</div>
                        <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5em;">Custom Engagement</h3>
                        <p style="color: #666; line-height: 1.6;">Create the perfect engagement ring with our expert designers. From concept to completion, we'll help you design a ring as unique as your love story.</p>
                    </div>
                </a>
            </div>
            
            <div class="collection-card" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                <a href="javascript:void(0);" style="text-decoration: none; color: inherit;" onclick="openContactModalWithTracking('Homepage', 'Special Request Card', 'I have a special request for custom jewelry. Please contact me to discuss my unique design requirements.');">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3em; margin-bottom: 15px; color: #FFD700;">⭐</div>
                        <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5em;">Special Request</h3>
                        <p style="color: #666; line-height: 1.6;">Have something unique in mind? Our master craftsmen can bring any vision to life. From restoration to completely custom pieces, we handle special requests with care.</p>
                    </div>
                </a>
            </div>
        </div>
    </main>
    
    <!-- Preload Progress Indicator - Bottom Left -->
    <div id="preload-indicator" style="position: fixed; bottom: 20px; left: 20px; z-index: 999; background: rgba(0,0,0,0.8); color: white; padding: 10px 15px; border-radius: 8px; font-size: 12px; backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0,0,0,0.3); max-width: 250px;">
        <div style="margin-bottom: 5px; font-weight: bold;">Loading Images...</div>
        <div class="progress-container" style="width: 200px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; overflow: hidden;">
            <div id="preload-progress" style="height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); width: 0%; transition: width 0.3s ease;"></div>
        </div>
        <div id="preload-status" style="margin-top: 5px; font-size: 10px; color: rgba(255,255,255,0.8);">Preparing gallery images...</div>
    </div>
    
    <!-- Footer -->
    <?php 
    include 'footer.php'; 
    renderFooter('home');
    ?>

    <!-- Search Modal JavaScript -->
    <script src="js/search_modal.js?v=20260604_1" defer></script>

    <!-- Include ProductModal System for Product Search -->
    <?php
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    ?>
</body>
</html>
