window.addEventListener("load",eventWindowLoaded,false);

// Enhanced Image preloading system for Cadman Manufacturing website (IMAGES ONLY)
var preloadedImages = [];
var imagePreloadQueue = [];
var imagePreloadProgress = 0;
var totalImagePreloadCount = 0;

// Priority images to preload first (main gallery rotation)
var priorityImages = [
    "bands_php/images/celtic/5310L.png",
    "bands_php/images/fancy/2291.png", 
    "Engagement_php/images/MK_series/MK2207.png",
    "accessories_php/images/Crosses_and_Lockets/21.png"
];

// Collection preview images to preload for faster navigation
var collectionPreviewImages = [
    // Bands collection samples
    "bands_php/images/celtic/5310M.png",
    "bands_php/images/fancy/1T026L.png",
    "bands_php/images/plain/200RM.png",
    
    // Engagement collection samples (all series updated today)
    "Engagement_php/images/MK_series/MK2207.png", // Already in priority
    "Engagement_php/images/MM_series/MM13.png",
    "Engagement_php/images/WM_series/WM13.png",
    
    // Accessories collection samples  
    "accessories_php/images/Idents/1BE.png",
    "accessories_php/images/Pendant_earrings/UN1E.png",
    
    // Family collection samples
    "family_php/images/Mother/1879.png",
    
    // Corporate collection samples
    "corp_php/images/awards/SA12CM_alt1.png",
    
    // Signet collection samples
    "signet_php/images/crest_top/C19.jpg",
    "signet_php/images/jewel top/C526.jpg",
    
    // Frontline Workers samples (formerly emergency services)
    "Frontline_Workers_php/images/Firefighter/LPFF6.png",
    "Frontline_Workers_php/images/clinical_services/PA1.png",
    
    // Additional key images
    "PNG/cadman_crest_only_gold.jpg", // Logo
    "ladys_stoneset_php/Gems/1898.png", // Lady's stoneset sample
    "school_php/images/Bands/SR109D.jpg" // School sample
];

function preloadImageFile(src, priority = false) {
    if (priority) {
        imagePreloadQueue.unshift(src); // Add to front of queue
    } else {
        imagePreloadQueue.push(src);
    }
}

function updateImagePreloadProgress() {
    imagePreloadProgress = preloadedImages.length;
    var percentage = totalImagePreloadCount > 0 ? Math.round((imagePreloadProgress / totalImagePreloadCount) * 100) : 0;
    
    // Update progress bar
    var progressBar = document.getElementById('preload-progress');
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
    
    // Update status text
    var statusText = document.getElementById('preload-status');
    if (statusText) {
        if (percentage < 25) {
            statusText.textContent = 'Loading priority gallery images...';
        } else if (percentage < 75) {
            statusText.textContent = 'Loading collection previews...';
        } else if (percentage < 100) {
            statusText.textContent = 'Almost ready...';
        } else {
            statusText.textContent = 'Gallery images loaded successfully!';
        }
    }
    
    // Hide progress indicator when complete
    if (percentage >= 100) {
        setTimeout(function() {
            var indicator = document.getElementById('preload-indicator');
            if (indicator) {
                indicator.style.opacity = '0';
                indicator.style.transition = 'opacity 1s ease';
                setTimeout(function() {
                    indicator.style.display = 'none';
                }, 1000);
            }
        }, 1500); // Show "success" message for a bit longer
    }
}

function startImagePreloading() {
    totalImagePreloadCount = imagePreloadQueue.length;
    if (totalImagePreloadCount === 0) return;
    
    var index = 0;
    var maxConcurrentImages = 3; // Load up to 3 images simultaneously
    var activeImageLoads = 0;
    
    function loadNextImage() {
        while (activeImageLoads < maxConcurrentImages && index < totalImagePreloadCount) {
            loadSingleImage(imagePreloadQueue[index]);
            index++;
            activeImageLoads++;
        }
    }
    
    function loadSingleImage(src) {
        var img = new Image();
        img.onload = function() {
            preloadedImages.push(this);
            activeImageLoads--;
            updateImagePreloadProgress();
            loadNextImage();
        };
        img.onerror = function() {
            activeImageLoads--;
            updateImagePreloadProgress();
            loadNextImage();
        };
        img.src = src;
    }
    
    loadNextImage();
}

// Called when the window is finished loading - handles GALLERY IMAGES only
function eventWindowLoaded() {
    
    // Preload priority images first (main gallery rotation)
    priorityImages.forEach(function(src) {
        preloadImageFile(src, true); // High priority
    });
    
    // Then preload collection preview images
    collectionPreviewImages.forEach(function(src) {
        preloadImageFile(src, false); // Normal priority
    });
    
    // Start the gallery image preloading process
    startImagePreloading();
    
    // Initialize Continuous Scrolling Carousel
    $(document).ready(function() {
        let carouselImages = []; // Will be loaded from admin API
        let isCarouselPaused = false;
        let carouselTrack;
        
        // Load carousel images - data injected server-side via window._cadmanCarouselData
        function loadCarouselImages() {
            const inlineData = window._cadmanCarouselData;
            if (inlineData && inlineData.length > 0) {
                carouselImages = inlineData;
                console.log(`Loaded ${carouselImages.length} carousel items (server-side injected)`);
            } else {
                carouselImages = getDefaultCarouselItems();
                console.log('Using fallback carousel items (no server data)');
            }
            setTimeout(() => initializeCarousel(), 100);
        }
        
        // Default carousel items (fallback)
        function getDefaultCarouselItems() {
            return [
                // Celtic bands
                { product_id: "5310L", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5310L.png", name: "Celtic Knot Band" },
                { product_id: "5310M", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5310M.png", name: "Celtic Design" },
                { product_id: "5410M", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5410M.png", name: "Celtic Pattern" },
                { product_id: "5854M", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5854M.png", name: "Celtic Twist" },
                { product_id: "5636L", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5636L_alt1.png", name: "Celtic Band" },
                { product_id: "5312M", category: "celtic_bands", src: "bands_php/thumbs/images/celtic/5312M_alt2.png", name: "Celtic Ring" },
                
                // Plain bands  
                { product_id: "5T18M", category: "plain_bands", src: "bands_php/thumbs/images/plain/5T18M_alt1.png", name: "Classic Band" },
                { product_id: "4T00RL", category: "plain_bands", src: "bands_php/thumbs/images/plain/4T00RL_alt1.png", name: "Plain Ring" },
                { product_id: "300RM", category: "plain_bands", src: "bands_php/thumbs/images/plain/300RM_alt1.png", name: "Simple Band" },
                { product_id: "550TL", category: "plain_bands", src: "bands_php/thumbs/images/plain/550TL_alt2.png", name: "Traditional Ring" },
                { product_id: "400TL", category: "plain_bands", src: "bands_php/thumbs/images/plain/400TL_alt1.png", name: "Timeless Ring" },
                
                // Fancy bands
                { product_id: "2291", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/2291_alt1.png", name: "Fancy Design" },
                { product_id: "1T026L", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/1T026L.png", name: "Textured Band" },
                { product_id: "5758L", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/5758L.png", name: "Decorative Ring" },
                { product_id: "7T62L", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/7T62L_alt2.png", name: "Designer Band" },
                { product_id: "5771L", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/5771L.png", name: "Elegant Band" },
                { product_id: "8T14L", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/8T14L_alt1.png", name: "Modern Design" },
                { product_id: "1T028M", category: "fancy_bands", src: "bands_php/thumbs/images/fancy/1T028M.png", name: "Contemporary Band" }
            ];
        }
        
        // Initialize carousel
        function initializeCarousel() {
            carouselTrack = document.getElementById('carouselTrack');
            
            if (!carouselTrack) {
                console.warn('Carousel track element not found - DOM may not be ready yet');
                return;
            }
            
            if (!carouselImages || carouselImages.length === 0) {
                console.warn('No carousel images available for initialization');
                return;
            }
            
            // Clear existing carousel items before adding new ones
            carouselTrack.innerHTML = '';
            
            // Check if mobile
            const isMobile = window.innerWidth <= 768;
            
            // Create two sets of images for seamless loop
            const doubledImages = [...carouselImages, ...carouselImages];
            
            // Populate carousel with images
            doubledImages.forEach((item, index) => {
                const carouselItem = document.createElement('div');
                carouselItem.className = `carousel-item ${item.type}`;
                
                carouselItem.innerHTML = `
                    <img src="${item.src}" alt="${item.name}" />
                    <div class="item-label">${item.name}</div>
                `;
                
                // Generate correct action for ProductModal
                function openProductModal(item) {
                    // Extract the product ID from the image filename instead of truncated base_name
                    const productId = extractBaseName(item.src);
                    
                    // Determine category from collection/filter
                    let category = 'bands'; // default
                    if (item.collection === 'bands') {
                        if (item.filter === 'plain') category = 'plain_bands';
                        else if (item.filter === 'fancy') category = 'fancy_bands'; 
                        else if (item.filter === 'celtic') category = 'celtic_bands';
                    } else if (item.collection === 'engagement') {
                        category = 'engagement';
                    } else if (item.collection === 'family') {
                        category = 'family';
                    }
                    
                    console.log(`Opening ProductModal for: ${productId} (category: ${category})`);

                    // Open ProductModal instead of navigating to a page
                    if (typeof ProductModal !== 'undefined' && ProductModal.open) {
                        ProductModal.open(productId, {
                            collection: item.collection || '',
                            category: category
                        });
                    } else {
                        console.error('ProductModal not available');
                    }
                }
                
                // Helper function to extract base name from image path (for legacy support)
                function extractBaseName(imagePath) {
                    const pathParts = imagePath.split('/');
                    const filename = pathParts[pathParts.length - 1];
                    let baseName = filename.replace(/\.(png|jpg|jpeg)$/i, '');
                    // Remove variant suffixes
                    baseName = baseName.replace(/_alt\d*$/, '');
                    baseName = baseName.replace(/-alt\d*$/, '');
                    baseName = baseName.replace(/_view\d*$/, '');
                    baseName = baseName.replace(/_art\d*$/, '');
                    return baseName;
                }
                
                // Add click handler for ProductModal
                carouselItem.addEventListener('click', () => {
                    openProductModal(item);
                });
                
                // Add hover pause functionality (desktop only)
                if (!isMobile) {
                    carouselItem.addEventListener('mouseenter', () => {
                        carouselTrack.style.animationPlayState = 'paused';
                    });
                    
                    carouselItem.addEventListener('mouseleave', () => {
                        if (!isCarouselPaused) {
                            carouselTrack.style.animationPlayState = 'running';
                        }
                    });
                }
                
                carouselTrack.appendChild(carouselItem);
            });
            
            // Calculate total width for animation based on device
            const itemCount = doubledImages.length;
            const itemWidth = isMobile ? 140 : 180; // Adjusted for mobile: 120px + 20px gap vs 160px + 20px gap
            const totalWidth = itemCount * itemWidth;
            carouselTrack.style.width = totalWidth + 'px';
            
            // Simple mobile solution - orientation-aware
            if (isMobile) {
                // Check orientation and adjust accordingly
                const isPortrait = window.innerHeight > window.innerWidth;
                const adjustedItemWidth = isPortrait ? 100 : 120; // Smaller items in portrait
                const adjustedTotalWidth = doubledImages.length * (adjustedItemWidth + 15); // 15px gap
                const animationSpeed = isPortrait ? '30s' : '35s'; // Faster in portrait
                
                // Force CSS animation with orientation-specific properties
                carouselTrack.style.cssText = `
                    display: flex !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    height: 100% !important;
                    width: ${adjustedTotalWidth}px !important;
                    animation: scrollRight ${animationSpeed} linear infinite !important;
                    animation-play-state: running !important;
                    gap: 15px !important;
                `;
                
                // Adjust item sizes for orientation
                const items = carouselTrack.querySelectorAll('.carousel-item');
                items.forEach(item => {
                    item.style.cssText = `
                        width: ${adjustedItemWidth}px !important;
                        height: ${adjustedItemWidth}px !important;
                        flex-shrink: 0 !important;
                        min-width: ${adjustedItemWidth}px !important;
                    `;
                });
                
                console.log(`Mobile carousel: ${isPortrait ? 'Portrait' : 'Landscape'} mode, width: ${adjustedTotalWidth}px, speed: ${animationSpeed}`);
            }
        }
        
        // Enhanced pause/resume functionality for mobile and desktop
        $('#pauseBtn').click(function() {
            isCarouselPaused = !isCarouselPaused;
            const button = $(this);
            
            // More reliable mobile detection - check multiple conditions
            const isMobile = window.innerWidth <= 768 || 
                            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            console.log('Pause button clicked. Mobile:', isMobile, 'Paused:', isCarouselPaused);
            
            if (isCarouselPaused) {
                if (isMobile) {
                    // For mobile, completely stop the animation
                    carouselTrack.style.setProperty('animation', 'none', 'important');
                    console.log('Mobile animation stopped');
                } else {
                    // For desktop, use normal pause
                    carouselTrack.style.animationPlayState = 'paused';
                    console.log('Desktop animation paused');
                }
                button.html('▶️ Play').addClass('playing');
            } else {
                if (isMobile) {
                    // For mobile, restart the animation with current orientation settings
                    const isPortrait = window.innerHeight > window.innerWidth;
                    const animationSpeed = isPortrait ? '30s' : '35s';
                    carouselTrack.style.setProperty('animation', `scrollRight ${animationSpeed} linear infinite`, 'important');
                    console.log('Mobile animation restarted with speed:', animationSpeed);
                } else {
                    // For desktop, use normal resume
                    carouselTrack.style.animationPlayState = 'running';
                    console.log('Desktop animation resumed');
                }
                button.html('⏸️ Pause').removeClass('playing');
            }
        });
        
        // Initialize everything - load carousel images from admin API first
        loadCarouselImages();
        
        // Attach smooth touch event listeners to carousel for mobile
        const carouselContainer = document.querySelector('.carousel-container');
        if (carouselContainer) {
            // Add touch event listeners with better options
            carouselContainer.addEventListener('touchstart', handleTouchStart, { 
                passive: false,
                capture: true 
            });
            carouselContainer.addEventListener('touchmove', handleTouchMove, { 
                passive: false,
                capture: true 
            });
            carouselContainer.addEventListener('touchend', handleTouchEnd, { 
                passive: false,
                capture: true 
            });
            
            // Prevent context menu on long press
            carouselContainer.addEventListener('contextmenu', (e) => {
                if (isDragging) {
                    e.preventDefault();
                }
            });
            
            // Improve touch target
            carouselContainer.style.touchAction = 'pan-y pinch-zoom';
            
            console.log('Smooth touch event listeners attached to carousel');
        }
        
        // Handle orientation changes on mobile
        if (window.innerWidth <= 768) {
            window.addEventListener('orientationchange', function() {
                setTimeout(() => {
                    console.log('Orientation changed, reinitializing carousel');
                    initializeCarousel();
                }, 100); // Small delay to let orientation settle
            });
            
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(() => {
                        console.log('Mobile resize detected, reinitializing carousel');
                        initializeCarousel();
                    }, 100);
                }
            });
        }
        
        // Smooth touch support for mobile carousel scrolling
        let touchStartX = 0;
        let touchEndX = 0;
        let isDragging = false;
        let currentTransform = 0;
        let animationWasPaused = false;
        let originalAnimationState = '';
        
        function handleTouchStart(e) {
            const isMobile = window.innerWidth <= 768 || 
                            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            if (!isMobile) return;
            
            touchStartX = e.changedTouches[0].screenX;
            isDragging = true;
            
            // Store current animation state
            animationWasPaused = isCarouselPaused;
            originalAnimationState = getComputedStyle(carouselTrack).animation;
            
            // Completely pause animation during touch
            carouselTrack.style.setProperty('animation-play-state', 'paused', 'important');
            carouselTrack.style.setProperty('animation', 'none', 'important');
            
            // Get current visual position
            const rect = carouselTrack.getBoundingClientRect();
            const containerRect = carouselTrack.parentElement.getBoundingClientRect();
            currentTransform = rect.left - containerRect.left;
            
            console.log('Touch start - animation paused for smooth dragging');
        }
        
        function handleTouchMove(e) {
            if (!isDragging) return;
            
            e.preventDefault(); // Prevent page scrolling
            e.stopPropagation(); // Prevent event bubbling
            
            const touchCurrentX = e.changedTouches[0].screenX;
            const deltaX = touchCurrentX - touchStartX;
            
            // Smooth drag with dampening for better feel
            const dampening = 0.3; // Reduce movement for smoother feel
            const newTransform = currentTransform + (deltaX * dampening);
            
            // Apply transform smoothly
            carouselTrack.style.setProperty('transform', `translateX(${newTransform}px)`, 'important');
            carouselTrack.style.setProperty('transition', 'none', 'important');
        }
        
        function handleTouchEnd(e) {
            if (!isDragging) return;
            
            touchEndX = e.changedTouches[0].screenX;
            isDragging = false;
            
            // Add smooth transition back
            carouselTrack.style.setProperty('transition', 'transform 0.3s ease-out', 'important');
            
            handleSwipeEnd();
        }
        
        function handleSwipeEnd() {
            const swipeThreshold = 30; // Lower threshold for better responsiveness
            const swipeDistance = touchEndX - touchStartX;
            const isMobile = window.innerWidth <= 768 || 
                            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            console.log('Touch end - swipe distance:', swipeDistance);
            
            // Smooth return to animation after a brief delay
            setTimeout(() => {
                carouselTrack.style.removeProperty('transition');
                
                if (!animationWasPaused && isMobile) {
                    const isPortrait = window.innerHeight > window.innerWidth;
                    let animationSpeed;
                    
                    if (Math.abs(swipeDistance) > swipeThreshold) {
                        if (swipeDistance > 0) {
                            // Swipe right - slower
                            animationSpeed = isPortrait ? '45s' : '50s';
                            console.log('Swipe right detected - slowing carousel');
                        } else {
                            // Swipe left - faster  
                            animationSpeed = isPortrait ? '20s' : '25s';
                            console.log('Swipe left detected - speeding carousel');
                        }
                        
                        // Apply new speed
                        carouselTrack.style.setProperty('animation', `scrollRight ${animationSpeed} linear infinite`, 'important');
                        
                        // Return to normal speed after 2 seconds
                        setTimeout(() => {
                            if (!isCarouselPaused) {
                                const normalSpeed = isPortrait ? '30s' : '35s';
                                carouselTrack.style.setProperty('animation', `scrollRight ${normalSpeed} linear infinite`, 'important');
                                console.log('Returning to normal speed');
                            }
                        }, 2000);
                    } else {
                        // No significant swipe - resume normal
                        animationSpeed = isPortrait ? '30s' : '35s';
                        carouselTrack.style.setProperty('animation', `scrollRight ${animationSpeed} linear infinite`, 'important');
                        console.log('Resuming normal animation');
                    }
                }
            }, 100); // Small delay for smooth transition
        }
        
        // Add touch event listeners for mobile
        if (window.innerWidth <= 768) {
            const carouselContainer = document.querySelector('.carousel-container');
            if (carouselContainer) {
                carouselContainer.addEventListener('touchstart', handleTouchStart, { passive: true });
                carouselContainer.addEventListener('touchend', handleTouchEnd, { passive: true });
            }
        }
        
        // Handle orientation change and resize
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                // Restart carousel after orientation change
                if (carouselTrack) {
                    carouselTrack.style.animationPlayState = 'running';
                }
            }, 500);
        });
        
        window.addEventListener('resize', function() {
            // Adjust animation on resize
            const isMobile = window.innerWidth <= 768;
            if (carouselTrack && isMobile) {
                carouselTrack.style.animationDuration = '40s';
            } else if (carouselTrack) {
                carouselTrack.style.animationDuration = '60s';
            }
        });
        
    });
}

// (3D viewer removed)
