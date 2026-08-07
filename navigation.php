<?php
// Enhanced Navigation component for Cadman Mfg website
require_once __DIR__ . '/includes/catalog/CatalogNav.php';

function renderNavigation($currentPage = '') {
    // Include cart system first
    include_once 'cart/cart_display.php';
    
    // Include contact modal
    include_once 'contact_modal.php';
    
    // Detect if we're in a subdirectory by checking the current working directory and script location
    $currentScript = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($currentScript);
    
    // More robust subdirectory detection
    $isSubdirectory = false;
    if (strpos($scriptDir, '/') !== false) {
        $pathParts = explode('/', trim($scriptDir, '/'));
        $isSubdirectory = count($pathParts) > 1; // More than just the domain
    }
    
    // Alternative: check if we're in a known subdirectory
    if (!$isSubdirectory) {
        $knownSubdirs = ['family_php', 'bands_php', 'accessories_php', 'corp_php', 'ladys_stoneset_php'];
        foreach ($knownSubdirs as $subdir) {
            if (strpos($currentScript, "/$subdir/") !== false) {
                $isSubdirectory = true;
                break;
            }
        }
    }
    
    // Use absolute paths from root for compatibility with rewritten URLs (e.g., /about/)
    $pathPrefix = $isSubdirectory ? '../' : '/';
    
    $logoPath = $pathPrefix . "PNG/logo.png";
    $menuIcon = $pathPrefix . "PNG/topbtn.svg";

    // Output cart system (styles, modal, and script)
    echo renderCartStyles();
    echo renderCartModal();
    
    // Output contact modal
    renderContactModal();

    echo '
    <!-- Enhanced Navigation Styles -->
    <style>
    #fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border-bottom: 3px solid #b8860b;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }
    
    #nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 8px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 45px;
    }
    
    #search-section {
        flex: 1;
        display: flex;
        justify-content: flex-start;
        align-items: center;
    }
    
    #search-button {
        background: linear-gradient(145deg, #b8860b, #daa520);
        border: none;
        color: white;
        padding: 8px 14px;
        border-radius: 25px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(184, 134, 11, 0.3);
    }
    
    #search-button:hover {
        background: linear-gradient(145deg, #daa520, #b8860b);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(184, 134, 11, 0.4);
    }
    
    #top-logo {
        flex: 1;
        text-align: center;
    }
    
    #menu-section {
        flex: 1;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px; /* Space between cart and menu buttons */
    }
    
    /* Cart Navigation Button Styles */
    .cart-nav-container {
        position: relative;
        display: inline-block;
    }
    
    .cart-toggle.cart-nav-style {
        background: linear-gradient(145deg, #b8860b, #daa520);
        padding: 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255,255,255,0.2);
        box-shadow: 0 2px 8px rgba(184, 134, 11, 0.3);
        width: 50px;
        height: 50px;
        cursor: pointer;
        position: relative;
    }
    
    .cart-toggle.cart-nav-style:hover {
        background: linear-gradient(145deg, #daa520, #b8860b);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(184, 134, 11, 0.4);
    }
    
    .cart-nav-icon {
        font-size: 20px;
        filter: brightness(0) invert(1);
    }
    
    .cart-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .cart-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .top-nav-logo {
        height: 40px;
        width: auto;
        transition: transform 0.3s ease;
        border-radius: 5px;
    }
    
    .top-nav-logo:hover {
        transform: scale(1.05);
    }
    
    #menu {
        background: linear-gradient(145deg, #b8860b, #daa520);
        padding: 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255,255,255,0.2);
        box-shadow: 0 2px 8px rgba(184, 134, 11, 0.3);
        width: 50px;
        height: 50px;
    }
    
    #menu:hover {
        background: linear-gradient(145deg, #daa520, #b8860b);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(184, 134, 11, 0.4);
    }
    
    #menu.menu-active {
        background: linear-gradient(145deg, #daa520, #cd853f);
        transform: rotate(90deg);
    }
    
    #menu img {
        filter: brightness(0) invert(1);
        transition: transform 0.3s ease;
        width: 24px;
        height: 24px;
    }
    
    /* Hamburger icon fallback */
    .hamburger-icon {
        width: 24px;
        height: 18px;
        position: relative;
        transform: rotate(0deg);
        transition: .3s ease-in-out;
        cursor: pointer;
    }
    
    .hamburger-icon span {
        display: block;
        position: absolute;
        height: 3px;
        width: 100%;
        background: white;
        border-radius: 3px;
        opacity: 1;
        left: 0;
        transform: rotate(0deg);
        transition: .3s ease-in-out;
    }
    
    .hamburger-icon span:nth-child(1) {
        top: 0px;
    }
    
    .hamburger-icon span:nth-child(2) {
        top: 8px;
    }
    
    .hamburger-icon span:nth-child(3) {
        top: 16px;
    }
    
    .hamburger-icon.active span:nth-child(1) {
        top: 8px;
        transform: rotate(135deg);
    }
    
    .hamburger-icon.active span:nth-child(2) {
        opacity: 0;
        left: -20px;
    }
    
    .hamburger-icon.active span:nth-child(3) {
        top: 8px;
        transform: rotate(-135deg);
    }
    
    #nav {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
        padding: 20px;
        background: linear-gradient(135deg, #b8860b 0%, #daa520 50%, #cd853f 100%);
        margin: 0;
        width: 100%;
        box-sizing: border-box;
        border-top: 2px solid rgba(255,255,255,0.2);
    }
    
    /* Desktop Navigation - CSS-only display */
    @media (min-width: 769px) {
        #nav {
            display: none !important; /* Hide nav by default on desktop */
            position: static !important;
            background: linear-gradient(135deg, #b8860b 0%, #daa520 50%, #cd853f 100%);
            border-radius: 0;
            box-shadow: none;
            max-height: none;
            overflow: visible;
            padding: 20px;
        }
        
        #nav.show {
            display: flex !important; /* Show nav when toggled on desktop */
        }
        
        .nav-group {
            display: none; /* Hide all accordion groups on desktop */
        }
        
        .nav-group-header {
            display: none;
        }
        
        .nav-group-content {
            display: none;
        }
        
        .desktop-nav {
            display: flex;
            gap: 15px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }
        
        .mobile-nav {
            display: none;
        }
    }
    
    .navitem {
        color: white;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 14px;
        white-space: nowrap;
        border: 2px solid rgba(255,255,255,0.3);
        min-width: 120px;
        text-align: center;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(5px);
    }
    
    .navitem:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        border-color: white;
        color: white;
    }
    
    .navitem.active {
        background: rgba(255,255,255,0.3);
        border-color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    .navitem.admin-link {
        position: relative;
        overflow: hidden;
    }
    
    .navitem.admin-link::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .navitem.admin-link:hover::before {
        left: 100%;
    }
    
    .navitem.admin-link:hover {
        background: rgba(30, 60, 114, 0.9) !important;
        border-color: #2a5298 !important;
        color: white !important;
    }
    
    /* Desktop Styles - CSS controlled display */
    @media (min-width: 769px) {
        #menu {
            display: flex !important; /* Show menu button on desktop too */
        }
    }
    
    /* Mobile Styles */
    @media (max-width: 768px) {
        #menu-section {
            gap: 8px; /* Smaller gap on mobile */
        }
        
        .cart-toggle.cart-nav-style,
        #menu {
            width: 45px;
            height: 45px;
        }
        
        .cart-nav-icon {
            font-size: 18px;
        }
        
        .cart-badge {
            width: 18px;
            height: 18px;
            font-size: 11px;
            top: -4px;
            right: -4px;
        }
        
        .desktop-nav {
            display: none;
        }
        
        .mobile-nav {
            display: block;
        }
        
        #menu {
            display: flex;
        }
        
        #nav {
            display: none;
            position: fixed;
            top: 105px;
            left: 15px;
            right: 15px;
            flex-direction: column;
            gap: 15px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            background: linear-gradient(135deg, #b8860b 0%, #daa520 50%, #cd853f 100%);
            padding: 20px;
        }
        
        .navitem {
            text-align: center;
            padding: 15px 20px;
            margin: 0;
            border-radius: 25px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            font-size: 16px;
            font-weight: 600;
            min-height: 50px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Accordion Navigation Styles for Mobile */
        .nav-group {
            margin-bottom: 15px;
        }
        
        .nav-group-header {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            border-radius: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            min-height: 50px;
        }
        
        .nav-group-header:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .nav-group-header.active {
            background: rgba(255,255,255,0.4);
            border-radius: 25px 25px 0 0;
        }
        
        .nav-group-header.active .accordion-icon {
            transform: rotate(45deg);
        }
        
        .accordion-icon {
            font-size: 18px;
            font-weight: bold;
            transition: transform 0.3s ease;
            color: white;
        }
        
        .nav-group-content {
            display: none;
            background: rgba(255,255,255,0.1);
            border-radius: 0 0 25px 25px;
            overflow: visible;
            list-style: none;
            padding: 0;
            margin: 0;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: none;
        }
        
        .nav-group-content li {
            margin: 0;
            padding: 0;
        }
        
        .nav-group-content li:last-child .navitem {
            border-radius: 0 0 23px 23px;
        }
        
        .nav-group-content .navitem {
            border-radius: 0;
            border: none;
            background: transparent;
            backdrop-filter: none;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            display: block;
            text-align: center;
            margin: 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            min-height: 50px;
            box-sizing: border-box;
        }
        
        .nav-group-content .navitem:hover {
            background: rgba(255,255,255,0.15);
        }
        
        .nav-group-content .navitem:last-child {
            border-bottom: none;
        }
    }
    
    /* Ensure content doesn\'t hide under fixed header */
    body {
        padding-top: 45px;
    }
    
    @media (max-width: 768px) {
        body {
            padding-top: 45px;
        }
    }
    </style>
    
    <!-- Fixed Navigation Header -->
    <header id="fixed-header">
        <div id="nav-container">
            <!-- Search Section (Left) -->
            <div id="search-section">
                <button id="search-button" aria-label="Search">
                    🔍 Search
                </button>
            </div>
            
            <!-- Centered Logo -->
            <div id="top-logo">
                <a href="' . $pathPrefix . 'index.php">
                    <img src="'; echo $logoPath; echo '" alt="Cadman Manufacturing" class="top-nav-logo"/>
                </a>
            </div>
            
            <!-- Cart and Menu Section (Right) -->
            <div id="menu-section">
                <!-- Cart Button -->
                <div class="cart-nav-container">
                    <button class="cart-toggle cart-nav-style" aria-label="View Shopping Cart">
                        <span class="cart-nav-icon">🛒</span>
                        <span class="cart-badge">0</span>
                    </button>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <a id="menu" href="#" role="button" onClick="toggleMobileMenu()" aria-label="Toggle menu">
                    <div class="hamburger-icon" id="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Enhanced Navigation Menu -->';
    
    // Build Login/Dashboard HTML (UNCHANGED — preserves session-driven Login interaction)
    $loginDesktopHtml = (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true
        ? "<a class=\"navitem admin-link\" href=\"" . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php') . "\" onClick=\"closeMobileMenu()\" style=\"background: rgba(102, 126, 234, 0.8); border-color: #667eea;\">👤 Dashboard</a>"
        : "<a class=\"navitem admin-link\" href=\"{$pathPrefix}admin/login.php\" onClick=\"closeMobileMenu()\" style=\"background: rgba(102, 126, 234, 0.8); border-color: #667eea;\">🔐 Login</a>");
    $loginMobileLi = (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true
        ? "<li><a class=\"navitem admin-link\" href=\"" . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php') . "\" onClick=\"closeMobileMenu()\" style=\"background: rgba(30, 60, 114, 0.3); border-color: #1e3c72;\">👤 Dashboard</a></li>"
        : "<li><a class=\"navitem admin-link\" href=\"{$pathPrefix}admin/login.php\" onClick=\"closeMobileMenu()\" style=\"background: rgba(30, 60, 114, 0.3); border-color: #1e3c72;\">🔐 Login</a></li>");

    $catalogNav = new CatalogNav();
    $desktopLinks = $catalogNav->renderDesktopLinks($loginDesktopHtml);
    $mobileGroups = $catalogNav->renderMobileGroups($loginMobileLi);

    echo '<nav id="nav" role="navigation">
            <!-- Desktop: Simple horizontal menu, Mobile: Accordion sections -->
            
            <!-- Desktop Navigation Items (visible on desktop only) -->
            <div class="desktop-nav">
                ' . $desktopLinks . '
            </div>
            
            <!-- Mobile Navigation Items (accordion style for mobile) -->
            <div class="mobile-nav">
                ' . $mobileGroups . '
            </div>
        </nav>
    </header>';
    
    echo '
    <!-- Enhanced Mobile Navigation JavaScript -->
    <script>
    // Declare functions in global scope immediately
    function toggleMobileMenu() {
        const nav = $("#nav");
        const menuBtn = $("#menu");
        const hamburger = $("#hamburger");
        
        // Prevent rapid toggling
        if (nav.is(":animated")) {
            return false;
        }
        
        // Check if we are on desktop or mobile
        const isDesktop = window.innerWidth > 768;
        
        if (isDesktop) {
            // Desktop: Toggle with .show class
            if (nav.hasClass("show")) {
                nav.removeClass("show").hide();
                menuBtn.removeClass("menu-active");
                hamburger.removeClass("active");
            } else {
                nav.addClass("show").show();
                menuBtn.addClass("menu-active");
                hamburger.addClass("active");
            }
        } else {
            // Mobile: Use slideToggle
            nav.slideToggle(400, function() {
                // Add visual feedback after animation completes
                if (nav.is(":visible")) {
                    menuBtn.addClass("menu-active");
                    hamburger.addClass("active");
                } else {
                    menuBtn.removeClass("menu-active");
                    hamburger.removeClass("active");
                }
            });
        }
        
        // Prevent default link behavior
        return false;
    }
    
    // Function to explicitly close mobile menu
    function closeMobileMenu() {
        const nav = $("#nav");
        const menuBtn = $("#menu");
        const hamburger = $("#hamburger");
        
        // Check if we are on desktop or mobile
        const isDesktop = window.innerWidth > 768;
        
        if (isDesktop) {
            // Desktop: Remove .show class and hide
            nav.removeClass("show").hide();
            menuBtn.removeClass("menu-active");
            hamburger.removeClass("active");
        } else {
            // Mobile: Only close if currently visible
            if (nav.is(":visible")) {
                nav.slideUp(400, function() {
                    menuBtn.removeClass("menu-active");
                    hamburger.removeClass("active");
                });
            }
        }
        return true; // Allow navigation to proceed
    }
    
    // Accordion toggle function for mobile navigation groups
    function toggleAccordion(element) {
        // Only function on mobile (when nav is displayed as mobile menu)
        const nav = $("#nav");
        if (!nav.is(":visible") || window.innerWidth > 768) {
            return false;
        }
        
        const header = $(element);
        const content = header.next(".nav-group-content");
        const isActive = header.hasClass("active");
        
        // Close all other accordion groups
        $(".nav-group-header").removeClass("active");
        $(".nav-group-content").slideUp(300);
        
        // Toggle current group
        if (!isActive) {
            header.addClass("active");
            content.slideDown(300);
        }
        
        return false; // Prevent event bubbling
    }
    
    // Make functions globally available
    window.toggleMobileMenu = toggleMobileMenu;
    window.closeMobileMenu = closeMobileMenu;
    window.toggleAccordion = toggleAccordion;
    
    // Legacy support
    function myFunction() {
        return toggleMobileMenu();
    }
    window.myFunction = myFunction;
    
    // Bind event handlers after jQuery is loaded (defer-safe: runs after deferred scripts)
    document.addEventListener("DOMContentLoaded", function() {
        // Close mobile menu when clicking outside
        $(document).on("click", function(event) {
            const nav = $("#nav");
            const menuBtn = $("#menu");
            
            // If menu is open and click is outside nav area
            if (nav.is(":visible") && 
                !$(event.target).closest("#nav").length && 
                !$(event.target).closest("#menu").length) {
                closeMobileMenu();
            }
        });
        
        // Handle window resize - close mobile menu on desktop
        $(window).on("resize", function() {
            if (window.innerWidth > 768) {
                const nav = $("#nav");
                // Force close mobile menu when switching to desktop
                if (nav.is(":visible")) {
                    nav.removeClass("show").hide();
                    $("#menu").removeClass("menu-active");
                    $("#hamburger").removeClass("active");
                }
            }
        });
        
        // Smooth scrolling for anchor links
        $(document).on("click", "a[href^=\'#\']", function(e) {
            const href = $(this).attr("href");
            if (href && href !== "#") {
                const target = $(href);
                if (target.length) {
                    e.preventDefault();
                    closeMobileMenu();
                    $("html, body").animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            }
        });
        
        // Add scroll effect to header
        $(window).on("scroll", function() {
            const header = $("#fixed-header");
            const scrollTop = $(window).scrollTop();
            
            if (scrollTop > 50) {
                header.css({
                    "background": "rgba(255, 255, 255, 0.98)",
                    "box-shadow": "0 2px 20px rgba(0,0,0,0.15)"
                });
            } else {
                header.css({
                    "background": "rgba(255, 255, 255, 0.95)",
                    "box-shadow": "0 2px 10px rgba(0,0,0,0.1)"
                });
            }
        });
        
        // Initialize navigation
        $("#nav").hide();
        $("#menu").show();
        $("#menu").removeClass("menu-active");
        $("#hamburger").removeClass("active");
        
        // Preload menu icon for smoother hover effects
        const menuIcon = new Image();
        menuIcon.src = "'; echo $menuIcon; echo '";
    });
    </script>';
    
    // Include cart JavaScript with correct path prefix
    echo '<script src="' . $pathPrefix . 'cart/cart.js" defer></script>';
}
