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
<meta name="keywords" content="School jewelry, class rings, graduation rings, Cadman Manufacturing, school recognition" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>School Collection - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
<style>
/* Shoulder Selector Responsive Styles */
@media (max-width: 768px) {
    .shoulder-selector-section {
        margin: 10px !important;
        padding: 20px !important;
    }
    
    .canvas-container {
        flex-direction: column !important;
        gap: 20px !important;
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
            console.log('School page loading...');
            
            // Initialize pagination
            setTimeout(() => {
                initializeSchoolPagination();
                console.log('School pagination initialized');
            }, 100);
            
            // Initialize image rotation
            setTimeout(() => {
                initializeImageRotation();
                console.log('Image rotation initialized');
            }, 200);
            
            console.log('School page initialized');
        });
    })();
    
    // View details functionality using ProductModal
    function viewDetails(itemId) {
        if (typeof ProductModal !== 'undefined') {
            ProductModal.open(itemId);
        } else {
            console.error('ProductModal not loaded, falling back to unified_detail.php');
            window.location.href = 'unified_detail.php?collection=school&id=' + encodeURIComponent(itemId);
        }
    }

    // Shoulder Selection System
    (function() {
        if (window.__sharedShoulderCustomizerLoaded) {
            return;
        }
        let currentSelectionMode = 'left'; // 'left' or 'right'
        let leftShoulderImage = null;
        let rightShoulderImage = null;
        
        // Shoulder system functions - initialized on-demand
        window.loadShoulderImages = function() {
            const shoulderImagesList = [
                '4-H_S9.jpg', 'ATV_large.jpg', 'Mapleleafl_S9B.jpg', 'Mens_Gymnastics_S7.jpg',
                'SR60DM.jpg', 'SR610B_gold.jpg', 'SR610B_silver.jpg', 'SR610DB_16.jpg', 'SR610DB_20.jpg',
                'airplane_large.jpg', 'all_star_ladies_small.jpg', 'allstar.jpg', 'aquarius midsize.jpg',
                'archery_midsize.jpg', 'aries midsize.jpg', 'art_petite.jpg', 'badminton_large.jpg',
                'ballerina_petite.jpg', 'band_classic.jpg', 'banner_dated_med.jpg', 'baseball player.jpg',
                'basketball_mens_large.jpg', 'bear.jpg', 'beauty_pageant_small.jpg', 'bowling_medium.jpg',
                'boxer.jpg', 'broadcasting_midsize.jpg', 'broomball.jpg', 'bulldog_mediium.jpg',
                'caduceus_midsize.jpg', 'camera_dated_small.jpg', 'camera_small.jpg', 'cancer midsize.jpg',
                'capricorn midsize.jpg', 'cardinal.jpg', 'carpentry.jpg', 'chess_medium.jpg', 'chief_large.jpg',
                'clarinet.jpg', 'class of large.jpg', 'class of.jpg', 'class_of_mid.jpg',
                'class_of_with_diamond_setting.jpg', 'classe_de_large.jpg', 'cobra_sm.jpg', 'computer.jpg',
                'condor_mid.jpg', 'condor_midsize_gold.jpg', 'cross_and_laurel_sm.jpg', 'crossed bats dated.jpg',
                'crossed hockey sticks.jpg', 'crown_small.jpg', 'curling_sm.jpg', 'dated_cougar_lrge.jpg',
                'devil_mid.jpg', 'dragon_mid.jpg', 'drum_lrg.jpg', 'eagle_small.jpg', 'electrician.jpg',
                'excellence.jpg', 'female_hockey_figure_s9.jpg', 'fieldhockey.jpg', 'fieldhockey_small.jpg',
                'figure_skater_petite.jpg', 'fish_lrg.jpg', 'fishing_medium.jpg', 'flag dated.jpg',
                'football.jpg', 'football_helmet_lrg.jpg', 'gemini midsize.jpg', 'geography_s9.jpg',
                'goalie_large.jpg', 'golf petite.jpg', 'grad_full_year.jpg', 'griffin classic.jpg',
                'guitar_dated_large.jpg', 'gymnastics_s9.jpg', 'hawk_medium.jpg', 'hockey figure dated midsize mans.jpg',
                'husky_small.png', 'jazz.png', 'justice midsize.jpg', 'knight_midsize.jpg', 'lacrosse_large.jpg',
                'lamp&laurels_S9.jpg', 'lamp&laurels_large_undated.jpg', 'lancer.jpg', 'laurelslamp_dated_large.jpg',
                'leo midsize.jpg', 'libra midsize.jpg', 'lightning.jpg', 'lion_midsize.jpg', 'marlin_mans.jpg',
                'martial_arts_lrg.jpg', 'mechanic_lrg.jpg', 'motorcross.jpg', 'music_dated.jpg', 'mustang_midsize.jpg',
                'ontario_scholar_lrg.jpg', 'panther_midsize.jpg', 'patriot_ lrg.jpg', 'paw_petite.jpg',
                'petite_RN.jpg', 'petite_frog.jpg', 'petite_honours.jpg', 'petite_paw.jpg', 'petite_ringette.jpg',
                'petite_sadd.jpg', 'phoenix_sm.jpg', 'pirate_midsize.jpg', 'pisces midsize.jpg', 'ram_dated.jpg',
                'rifle.jpg', 'roadrunner_midsize.jpg', 'rodeo_mid.jpg', 'rowing_sm.jpg', 'rugby_dated_lrg.jpg',
                'rugby_player_midsize.jpg', 'rugby_womans.jpg', 'rugby_womans_small.jpg', 'rugbyplayer_lrg.jpg',
                'rugbyplayer_lrg.png', 'sagittarius midsize.jpg', 'sailing_midsize.jpg', 'save petite.jpg',
                'scorpio midsize.jpg', 'shark midsize.jpg', 'shield coach lrg.jpg', 'shield equipment manager lrg.jpg',
                'shield manager lrg.jpg', 'shield only small.jpg', 'shield treasurer lrg.jpg', 'skate boarder midsize.jpg',
                'skate dated large.jpg', 'skate_petite.jpg', 'skier female small.jpg', 'snowboarder midsize.jpg',
                'snowmoble large.jpg', 'soccer female figure dated midsize.jpg', 'soccer large.jpg',
                'sport_shield_number_lrg.jpg', 'star_of_life_lrg.jpg', 'student council small.jpg',
                'student govt large.jpg', 'swimmer female midsize.jpg', 'taurus midsize.jpg', 'thunderbird midsize.jpg',
                'tiger large with tiger eye stone.jpg', 'tiger large.jpg', 'titians large.jpg',
                'torch and laurels petite.jpg', 'track & field large.jpg', 'track & field petite.jpg',
                'trojan small.jpg', 'valedictorian sm.jpg', 'viking midsize.jpg', 'virgo midsize.jpg',
                'volleyball dated small.jpg', 'weightlifter large.jpg', 'welding midsize.jpg', 'welding.jpg',
                'wildcats misize.jpg', 'wolf small.jpg', 'wolverines midsize.jpg', 'wrestling large.jpg',
                'year_only_mid.jpg', 'yearbook.jpg'
            ];
            
            const gallery = document.getElementById('shoulderGallery');
            if (!gallery) return;
            
            gallery.innerHTML = '';
            
            shoulderImagesList.forEach(filename => {
                const imgContainer = document.createElement('div');
                imgContainer.style.cssText = 'position: relative; cursor: pointer; transition: transform 0.2s;';
                
                const img = document.createElement('img');
                img.src = `school_php/images/Shoulders/${filename}`;
                img.alt = filename.replace(/\\.(jpg|png|jpeg)$/i, '').replace(/[_-]/g, ' ');
                img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 2px solid transparent; transition: all 0.2s;';
                img.title = img.alt + ' - Click to view and select';
                
                // Add larger hover area for easier targeting
                const hoverPadding = document.createElement('div');
                hoverPadding.style.cssText = 'position: absolute; top: -10px; left: -10px; right: -10px; bottom: -10px; pointer-events: none;';
                imgContainer.appendChild(hoverPadding);
                
                // Store filename for modal use only
                imgContainer.dataset.filename = filename;
                
                // Simple single click/tap to open modal on all devices
                imgContainer.onclick = (e) => {
                    e.preventDefault();
                    currentModalFilename = filename;
                    showShoulderModal(filename, img.alt);
                };
                
                // Only add hover effects on devices with actual hover capability
                if (window.matchMedia('(hover: hover)').matches) {
                    imgContainer.onmouseenter = () => {
                        imgContainer.style.transform = 'scale(1.05)';
                        img.style.borderColor = '#FFD700';
                    };
                    imgContainer.onmouseleave = () => {
                        imgContainer.style.transform = 'scale(1)';
                        if (!img.classList.contains('selected')) {
                            img.style.borderColor = 'transparent';
                        }
                    };
                }
                
                img.onerror = () => {
                    img.style.opacity = '0.5';
                    imgContainer.style.background = '#f0f0f0';
                    console.warn(`Failed to load: ${filename}`);
                };
                
                imgContainer.appendChild(img);
                gallery.appendChild(imgContainer);
            });
        }
        
        // Enhanced Shoulder Preloader System - Expanding existing site preloader
        window.preloadShoulderImages = function() {
            return new Promise((resolve, reject) => {
                const shoulderImagesList = [
                    '4-H_S9.jpg', 'ATV_large.jpg', 'Mapleleafl_S9B.jpg', 'Mens_Gymnastics_S7.jpg',
                    'SR60DM.jpg', 'SR610B_gold.jpg', 'SR610B_silver.jpg', 'SR610DB_16.jpg', 'SR610DB_20.jpg',
                    'airplane_large.jpg', 'all_star_ladies_small.jpg', 'allstar.jpg', 'aquarius midsize.jpg',
                    'archery_midsize.jpg', 'aries midsize.jpg', 'art_petite.jpg', 'badminton_large.jpg',
                    'ballerina_petite.jpg', 'band_classic.jpg', 'banner_dated_med.jpg', 'baseball player.jpg',
                    'basketball_mens_large.jpg', 'bear.jpg', 'beauty_pageant_small.jpg', 'bowling_medium.jpg',
                    'boxer.jpg', 'broadcasting_midsize.jpg', 'broomball.jpg', 'bulldog_mediium.jpg',
                    'caduceus_midsize.jpg', 'camera_dated_small.jpg', 'camera_small.jpg', 'cancer midsize.jpg',
                    'capricorn midsize.jpg', 'cardinal.jpg', 'carpentry.jpg', 'chess_medium.jpg', 'chief_large.jpg',
                    'clarinet.jpg', 'class of large.jpg', 'class of.jpg', 'class_of_mid.jpg',
                    'class_of_with_diamond_setting.jpg', 'classe_de_large.jpg', 'cobra_sm.jpg', 'computer.jpg',
                    'condor_mid.jpg', 'condor_midsize_gold.jpg', 'cross_and_laurel_sm.jpg', 'crossed bats dated.jpg',
                    'crossed hockey sticks.jpg', 'crown_small.jpg', 'curling_sm.jpg', 'dated_cougar_lrge.jpg',
                    'devil_mid.jpg', 'dragon_mid.jpg', 'drum_lrg.jpg', 'eagle_small.jpg', 'electrician.jpg',
                    'excellence.jpg', 'female_hockey_figure_s9.jpg', 'fieldhockey.jpg', 'fieldhockey_small.jpg',
                    'figure_skater_petite.jpg', 'fish_lrg.jpg', 'fishing_medium.jpg', 'flag dated.jpg',
                    'football.jpg', 'football_helmet_lrg.jpg', 'gemini midsize.jpg', 'geography_s9.jpg',
                    'goalie_large.jpg', 'golf petite.jpg', 'grad_full_year.jpg', 'griffin classic.jpg',
                    'guitar_dated_large.jpg', 'gymnastics_s9.jpg', 'hawk_medium.jpg', 'hockey figure dated midsize mans.jpg',
                    'husky_small.png', 'jazz.png', 'justice midsize.jpg', 'knight_midsize.jpg', 'lacrosse_large.jpg',
                    'lamp&laurels_S9.jpg', 'lamp&laurels_large_undated.jpg', 'lancer.jpg', 'laurelslamp_dated_large.jpg',
                    'leo midsize.jpg', 'libra midsize.jpg', 'lightning.jpg', 'lion_midsize.jpg', 'marlin_mans.jpg',
                    'martial_arts_lrg.jpg', 'mechanic_lrg.jpg', 'motorcross.jpg', 'music_dated.jpg', 'mustang_midsize.jpg',
                    'ontario_scholar_lrg.jpg', 'panther_midsize.jpg', 'patriot_ lrg.jpg', 'paw_petite.jpg',
                    'petite_RN.jpg', 'petite_frog.jpg', 'petite_honours.jpg', 'petite_paw.jpg', 'petite_ringette.jpg',
                    'petite_sadd.jpg', 'phoenix_sm.jpg', 'pirate_midsize.jpg', 'pisces midsize.jpg', 'ram_dated.jpg',
                    'rifle.jpg', 'roadrunner_midsize.jpg', 'rodeo_mid.jpg', 'rowing_sm.jpg', 'rugby_dated_lrg.jpg',
                    'rugby_player_midsize.jpg', 'rugby_womans.jpg', 'rugby_womans_small.jpg', 'rugbyplayer_lrg.jpg',
                    'rugbyplayer_lrg.png', 'sagittarius midsize.jpg', 'sailing_midsize.jpg', 'save petite.jpg',
                    'scorpio midsize.jpg', 'shark midsize.jpg', 'shield coach lrg.jpg', 'shield equipment manager lrg.jpg',
                    'shield manager lrg.jpg', 'shield only small.jpg', 'shield treasurer lrg.jpg', 'skate boarder midsize.jpg',
                    'skate dated large.jpg', 'skate_petite.jpg', 'skier female small.jpg', 'snowboarder midsize.jpg',
                    'snowmoble large.jpg', 'soccer female figure dated midsize.jpg', 'soccer large.jpg',
                    'sport_shield_number_lrg.jpg', 'star_of_life_lrg.jpg', 'student council small.jpg',
                    'student govt large.jpg', 'swimmer female midsize.jpg', 'taurus midsize.jpg', 'thunderbird midsize.jpg',
                    'tiger large with tiger eye stone.jpg', 'tiger large.jpg', 'titians large.jpg',
                    'torch and laurels petite.jpg', 'track & field large.jpg', 'track & field petite.jpg',
                    'trojan small.jpg', 'valedictorian sm.jpg', 'viking midsize.jpg', 'virgo midsize.jpg',
                    'volleyball dated small.jpg', 'weightlifter large.jpg', 'welding midsize.jpg', 'welding.jpg',
                    'wildcats misize.jpg', 'wolf small.jpg', 'wolverines midsize.jpg', 'wrestling large.jpg',
                    'year_only_mid.jpg', 'yearbook.jpg'
                ];
                
                let loadedCount = 0;
                let failedCount = 0;
                const totalImages = shoulderImagesList.length;
                
                console.log(`Starting preload of ${totalImages} shoulder images...`);
                console.log('Expanding existing site preloader for shoulder gallery optimization');
                
                const startTime = performance.now();
                
                const loadPromises = shoulderImagesList.map((filename, index) => {
                    return new Promise((resolveImg) => {
                        const img = new Image();
                        const imagePath = `school_php/images/Shoulders/${filename}`;
                        
                        img.onload = function() {
                            loadedCount++;
                            updateShoulderPreloadProgress(loadedCount, totalImages, failedCount);
                            resolveImg({ success: true, filename, src: imagePath });
                        };
                        
                        img.onerror = function() {
                            failedCount++;
                            loadedCount++; // Count as processed
                            console.warn(`Failed to preload shoulder image: ${filename}`);
                            updateShoulderPreloadProgress(loadedCount, totalImages, failedCount);
                            resolveImg({ success: false, filename, src: imagePath });
                        };
                        
                        // Add small delay between requests to avoid overwhelming the server
                        setTimeout(() => {
                            img.src = imagePath;
                        }, index * 10); // 10ms stagger
                    });
                });
                
                Promise.all(loadPromises)
                    .then((results) => {
                        const endTime = performance.now();
                        const loadTime = Math.round(endTime - startTime);
                        const successCount = results.filter(r => r.success).length;
                        
                        console.log(`Shoulder preloading complete: ${successCount}/${totalImages} loaded successfully in ${loadTime}ms`);
                        console.log('Enhanced preloader integration successful - shoulder gallery ready for instant browsing');
                        
                        // Update final status
                        updateShoulderPreloadProgress(totalImages, totalImages, failedCount, true);
                        
                        setTimeout(() => {
                            resolve(results);
                        }, 300); // Brief delay to show completion
                    })
                    .catch(reject);
            });
        };
        
        // Update shoulder preloading progress
        function updateShoulderPreloadProgress(loaded, total, failed, isComplete = false) {
            const progressBar = document.getElementById('shoulder-progress-bar');
            const statusText = document.getElementById('shoulder-status-text');
            const progressText = document.getElementById('shoulder-progress-text');
            
            if (progressBar) {
                const percentage = Math.round((loaded / total) * 100);
                progressBar.style.width = percentage + '%';
                
                // Color coding for progress bar with smooth transitions
                if (percentage < 30) {
                    progressBar.style.background = '#FFD700'; // Gold
                } else if (percentage < 70) {
                    progressBar.style.background = 'linear-gradient(90deg, #FFD700, #32CD32)'; // Gold to green gradient
                } else {
                    progressBar.style.background = '#32CD32'; // Green
                }
            }
            
            if (statusText) {
                if (isComplete) {
                    const successCount = total - failed;
                    statusText.innerHTML = `✅ Shoulder gallery ready! <span style="font-size: 16px;">(${successCount}/${total} designs loaded)</span>`;
                    statusText.style.color = '#32CD32';
                } else if (loaded < total * 0.3) {
                    statusText.innerHTML = '🎨 Loading shoulder designs...';
                } else if (loaded < total * 0.7) {
                    statusText.innerHTML = '⚡ Loading more design options...';
                } else {
                    statusText.innerHTML = '🏁 Almost finished loading...';
                }
            }
            
            if (progressText) {
                if (isComplete) {
                    progressText.innerHTML = `<strong>${total - failed}/${total} designs ready for browsing!</strong>`;
                    if (failed > 0) {
                        progressText.innerHTML += ` <span style="color: #ff6b6b;">(${failed} unavailable)</span>`;
                    }
                } else {
                    progressText.textContent = `${loaded}/${total} images loaded`;
                    if (failed > 0) {
                        progressText.textContent += ` (${failed} failed)`;
                    }
                }
            }
        }
        
        // Show enhanced shoulder loading indicator
        window.showShoulderLoadingIndicator = function() {
            const shoulderSelector = document.getElementById('shoulderSelector');
            if (!shoulderSelector) return;
            
            // Create loading overlay
            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'shoulder-loading-overlay';
            loadingOverlay.innerHTML = `
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.95);
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    z-index: 1000;
                    border-radius: 10px;
                ">
                    <div style="
                        width: 60px;
                        height: 60px;
                        border: 6px solid rgba(255, 215, 0, 0.3);
                        border-top: 6px solid #FFD700;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin-bottom: 25px;
                    "></div>
                    <div id="shoulder-status-text" style="
                        font-size: 20px;
                        font-weight: bold;
                        margin-bottom: 15px;
                        color: #8B4513;
                    ">Preparing shoulder gallery...</div>
                    <div style="
                        width: 300px;
                        background: rgba(255,255,255,0.4);
                        border-radius: 15px;
                        overflow: hidden;
                        margin-bottom: 15px;
                        border: 2px solid #FFD700;
                    ">
                        <div id="shoulder-progress-bar" style="
                            height: 12px;
                            background: #FFD700;
                            width: 0%;
                            transition: width 0.3s ease;
                            border-radius: 10px;
                        "></div>
                    </div>
                    <div id="shoulder-progress-text" style="
                        font-size: 14px;
                        color: #666;
                        margin-bottom: 10px;
                    ">0/0 images loaded</div>
                    <div style="
                        font-size: 12px;
                        color: #999;
                        text-align: center;
                        max-width: 300px;
                        line-height: 1.4;
                    ">Loading shoulder design options for your school ring customization</div>
                </div>
            `;
            
            shoulderSelector.style.position = 'relative';
            shoulderSelector.appendChild(loadingOverlay);
            
            // Add animation keyframes if not already added
            if (!document.getElementById('shoulder-spinner-styles')) {
                const styleSheet = document.createElement('style');
                styleSheet.id = 'shoulder-spinner-styles';
                styleSheet.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(styleSheet);
            }
        }
        
        // Hide shoulder loading indicator
        window.hideShoulderLoadingIndicator = function() {
            const overlay = document.getElementById('shoulder-loading-overlay');
            if (overlay) {
                // Fade out effect
                overlay.style.transition = 'opacity 0.5s ease';
                overlay.style.opacity = '0';
                
                setTimeout(() => {
                    overlay.remove();
                }, 500);
            }
        }
        
        // Expose preloader functions globally  
        window.preloadShoulderImages = window.preloadShoulderImages;
        
        window.setupCanvases = function() {
            const leftCanvas = document.getElementById('leftShoulderCanvas');
            const rightCanvas = document.getElementById('rightShoulderCanvas');
            
            if (!leftCanvas || !rightCanvas) return;
            
            clearCanvas(leftCanvas);
            clearCanvas(rightCanvas);
        }
        
        // Store current hovered filename for modal selection
        let currentModalFilename = null;
        
        // Modal selection function with mobile enhancements
        // Modal selection function with auto-close
        window.selectFromModal = function(side) {
            if (!currentModalFilename) {
                console.log('No filename selected');
                return;
            }
            
            console.log(`Selecting ${currentModalFilename} for ${side} side`);
            
            // Mobile haptic feedback
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(30);
            }
            
            try {
                if (side === 'both') {
                    // Apply to both sides
                    selectShoulderImageDirect(currentModalFilename, 'left');
                    selectShoulderImageDirect(currentModalFilename, 'right');
                } else {
                    // Apply to one side and update button to show "both" option
                    selectShoulderImageDirect(currentModalFilename, side);
                    updateButtonAfterSelection(side);
                    
                    // Don't close modal immediately - let user add to both sides if wanted
                    return;
                }
            } catch (error) {
                console.error('Error selecting shoulder:', error);
            }
            
            // Close modal after selection (both sides or single)
            closeShoulderModal();
        };
        
        // Update button to show "both" option after selection
        function updateButtonAfterSelection(selectedSide) {
            const leftBtn = document.getElementById('modalSelectLeftBtn');
            const rightBtn = document.getElementById('modalSelectRightBtn');
            
            if (!leftBtn || !rightBtn) return;
            
            if (selectedSide === 'left') {
                // Left was selected, change left button to "both"
                leftBtn.textContent = 'Add to Both';
                leftBtn.onclick = function() { selectFromModal('both'); };
                leftBtn.style.background = '#8A2BE2';
                leftBtn.style.borderColor = '#8A2BE2';
                leftBtn.className += ' modal-both-button';
                // Only add hover effects on devices with actual hover capability
                if (window.matchMedia('(hover: hover)').matches) {
                    leftBtn.onmouseover = function() { this.style.background = '#6B1B8A'; };
                    leftBtn.onmouseout = function() { this.style.background = '#8A2BE2'; };
                }
            } else if (selectedSide === 'right') {
                // Right was selected, change right button to "both"
                rightBtn.textContent = 'Add to Both';
                rightBtn.onclick = function() { selectFromModal('both'); };
                rightBtn.style.background = '#8A2BE2';
                rightBtn.style.borderColor = '#8A2BE2';
                rightBtn.className += ' modal-both-button';
                // Only add hover effects on devices with actual hover capability
                if (window.matchMedia('(hover: hover)').matches) {
                    rightBtn.onmouseover = function() { this.style.background = '#6B1B8A'; };
                    rightBtn.onmouseout = function() { this.style.background = '#8A2BE2'; };
                }
            }
        }
        
        // Update modal button visibility based on current selection mode
        window.setSelectionMode = function(mode) {
            currentSelectionMode = mode;
            
            const leftCanvas = document.getElementById('leftShoulderCanvas');
            const rightCanvas = document.getElementById('rightShoulderCanvas');
            const mobileIndicator = document.getElementById('mobileStatusIndicator');
            
            // Show mobile indicator on touch devices
            if (mobileIndicator) {
                const isMobile = window.innerWidth <= 768 || 'ontouchstart' in window;
                if (isMobile) {
                    mobileIndicator.style.display = 'block';
                    mobileIndicator.textContent = mode === 'left' ? 
                        'Selected LEFT shoulder - now tap a design below' : 
                        'Selected RIGHT shoulder - now tap a design below';
                    mobileIndicator.style.background = mode === 'left' ? '#FFD700' : '#32CD32';
                } else {
                    mobileIndicator.style.display = 'none';
                }
            }
            
            if (mode === 'left') {
                // Highlight left side
                leftCanvas.style.borderColor = '#FFD700';
                leftCanvas.style.boxShadow = '0 0 10px rgba(255, 215, 0, 0.5)';
                
                // Normal right side
                rightCanvas.style.borderColor = '#ccc';
                rightCanvas.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            } else {
                // Highlight right side
                rightCanvas.style.borderColor = '#32CD32';
                rightCanvas.style.boxShadow = '0 0 10px rgba(50, 205, 50, 0.5)';
                
                // Normal left side
                leftCanvas.style.borderColor = '#ccc';
                leftCanvas.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            }
            
            console.log(`Selection mode: ${mode} shoulder`);
        };
        
        // Direct shoulder selection without requiring image element
        function selectShoulderImageDirect(filename, targetSide) {
            const targetCanvas = targetSide === 'left' ? 
                document.getElementById('leftShoulderCanvas') : 
                document.getElementById('rightShoulderCanvas');
            
            if (!targetCanvas) {
                console.error('Canvas not found:', targetSide);
                return;
            }
            
            console.log(`Adding ${filename} to ${targetSide} canvas`);
            
            const img = new Image();
            img.onload = () => {
                drawImageToCanvas(targetCanvas, img);
                
                if (targetSide === 'left') {
                    leftShoulderImage = { filename, img };
                } else {
                    rightShoulderImage = { filename, img };
                }
                
                // Update gallery visual feedback
                updateGallerySelection(filename, targetSide);
            };
            img.onerror = () => {
                console.error('Failed to load image:', filename);
            };
            img.src = `school_php/images/Shoulders/${filename}`;
        }
        
        // Update gallery visual feedback
        function updateGallerySelection(filename, targetSide) {
            // Clear old selections
            document.querySelectorAll('#shoulderGallery img.selected').forEach(img => {
                img.classList.remove('selected');
                img.style.borderColor = 'transparent';
            });
            
            // Find and highlight the selected image
            const imgElement = document.querySelector(`#shoulderGallery img[src*="${filename}"]`);
            if (imgElement) {
                imgElement.classList.add('selected');
                imgElement.style.borderColor = targetSide === 'left' ? '#FFD700' : '#32CD32';
            }
        }
        
        function selectShoulderImage(filename, imgElement, forceSide = null) {
            const targetSide = forceSide || currentSelectionMode;
            const targetCanvas = targetSide === 'left' ? 
                document.getElementById('leftShoulderCanvas') : 
                document.getElementById('rightShoulderCanvas');
            
            if (!targetCanvas) return;
            
            document.querySelectorAll('#shoulderGallery img.selected').forEach(img => {
                img.classList.remove('selected');
                img.style.borderColor = 'transparent';
            });
            
            imgElement.classList.add('selected');
            imgElement.style.borderColor = targetSide === 'left' ? '#FFD700' : '#32CD32';
            
            const img = new Image();
            img.onload = () => {
                drawImageToCanvas(targetCanvas, img);
                
                if (targetSide === 'left') {
                    leftShoulderImage = { filename, img };
                } else {
                    rightShoulderImage = { filename, img };
                }
            };
            img.src = `school_php/images/Shoulders/${filename}`;
        }
        
        function drawImageToCanvas(canvas, img) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const canvasAspect = canvas.width / canvas.height;
            const imgAspect = img.width / img.height;
            
            let drawWidth = canvas.width;
            let drawHeight = canvas.height;
            let offsetX = 0;
            let offsetY = 0;
            
            if (imgAspect > canvasAspect) {
                drawHeight = canvas.width / imgAspect;
                offsetY = (canvas.height - drawHeight) / 2;
            } else {
                drawWidth = canvas.height * imgAspect;
                offsetX = (canvas.width - drawWidth) / 2;
            }
            
            ctx.drawImage(img, offsetX, offsetY, drawWidth, drawHeight);
        }
        
        function clearCanvas(canvas) {
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Center the "No selection" text
            ctx.fillStyle = '#999';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('Click to select', canvas.width / 2, canvas.height / 2 - 8);
            ctx.fillText('this side', canvas.width / 2, canvas.height / 2 + 8);
        }
        
        window.clearSelections = function() {
            leftShoulderImage = null;
            rightShoulderImage = null;
            
            clearCanvas(document.getElementById('leftShoulderCanvas'));
            clearCanvas(document.getElementById('rightShoulderCanvas'));
            
            document.querySelectorAll('#shoulderGallery img.selected').forEach(img => {
                img.classList.remove('selected');
                img.style.borderColor = 'transparent';
            });
            
            // Reset to left selection mode
            setSelectionMode('left');
        };
        
        // Modal functions for image preview - Fixed timing
        let modalShowTimeout = null;
        let modalHideTimeout = null;
        let modalIsVisible = false;
        
        // Close modal function
        window.closeShoulderModal = function() {
            const modal = document.getElementById('shoulderImageModal');
            const overlay = document.getElementById('shoulderModalOverlay');
            
            if (!modal || !overlay) return;
            
            modalIsVisible = false;
            currentModalFilename = null;
            
            // Immediate hide with fade
            overlay.style.opacity = '0';
            modal.style.opacity = '0';
            modal.style.transform = 'translate(-50%, -50%) scale(0.95)';
            
            setTimeout(() => {
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }, 150);
        };
        
        // Keep modal open when hovering over it
        window.keepModalOpen = function() {
            // Clear any pending hide timeout
            if (window.modalHideTimeout) {
                clearTimeout(window.modalHideTimeout);
                window.modalHideTimeout = null;
            }
            console.log('Modal kept open by hover');
        };
        
        // Close modal function
        window.closeShoulderModal = function() {
            const modal = document.getElementById('shoulderImageModal');
            const overlay = document.getElementById('shoulderModalOverlay');
            
            if (!modal || !overlay) return;
            
            modalIsVisible = false;
            currentModalFilename = null;
            
            // Immediate hide with fade
            overlay.style.opacity = '0';
            modal.style.opacity = '0';
            modal.style.transform = 'translate(-50%, -50%) scale(0.95)';
            
            setTimeout(() => {
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }, 150);
        };
        
        function showShoulderModal(filename, altText, delay = 0) {
            // Clear any pending hide timeout
            if (modalHideTimeout) {
                clearTimeout(modalHideTimeout);
                modalHideTimeout = null;
            }
            
            // If modal is already visible, just update content immediately
            if (modalIsVisible) {
                updateModalContent(filename, altText);
                return;
            }
            
            // Set show timeout if delay specified and modal not visible
            if (delay > 0 && !modalIsVisible) {
                modalShowTimeout = setTimeout(() => {
                    displayModal(filename, altText);
                }, delay);
            } else {
                displayModal(filename, altText);
            }
        }
        
        function updateModalContent(filename, altText) {
            const modalImage = document.getElementById('shoulderModalImage');
            const modalTitle = document.getElementById('shoulderModalTitle');
            
            if (modalImage && modalTitle) {
                modalImage.src = `school_php/images/Shoulders/${filename}`;
                
                // Create clean, user-friendly title
                let cleanTitle = altText || filename.replace(/\.(jpg|png|jpeg)$/i, '');
                
                // Clean up the title - remove underscores, hyphens, and capitalize properly
                cleanTitle = cleanTitle
                    .replace(/[_-]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
                
                modalTitle.textContent = cleanTitle;
                resetModalButtons(); // Reset buttons to default state
            }
        }
        
        // Reset modal buttons to default state
        function resetModalButtons() {
            const leftBtn = document.getElementById('modalSelectLeftBtn');
            const rightBtn = document.getElementById('modalSelectRightBtn');
            
            if (leftBtn) {
                leftBtn.textContent = 'Add to Left';
                leftBtn.onclick = function() { selectFromModal('left'); };
                leftBtn.style.background = '#FFD700';
                leftBtn.style.borderColor = '#FFD700';
                leftBtn.className = leftBtn.className.replace(' modal-both-button', '');
                // Only add hover effects on devices with actual hover capability
                if (window.matchMedia('(hover: hover)').matches) {
                    leftBtn.onmouseover = function() { this.style.background = '#FFA500'; };
                    leftBtn.onmouseout = function() { this.style.background = '#FFD700'; };
                }
            }
            
            if (rightBtn) {
                rightBtn.textContent = 'Add to Right';
                rightBtn.onclick = function() { selectFromModal('right'); };
                rightBtn.style.background = '#32CD32';
                rightBtn.style.borderColor = '#32CD32';
                rightBtn.className = rightBtn.className.replace(' modal-both-button', '');
                // Only add hover effects on devices with actual hover capability
                if (window.matchMedia('(hover: hover)').matches) {
                    rightBtn.onmouseover = function() { this.style.background = '#228B22'; };
                    rightBtn.onmouseout = function() { this.style.background = '#32CD32'; };
                }
            }
        }
        
        function displayModal(filename, altText) {
            const modal = document.getElementById('shoulderImageModal');
            const overlay = document.getElementById('shoulderModalOverlay');
            
            if (!modal || !overlay) return;
            
            updateModalContent(filename, altText);
            
            // Show modal immediately
            overlay.style.display = 'block';
            modal.style.display = 'block';
            modalIsVisible = true;
            
            // Trigger animations
            setTimeout(() => {
                overlay.style.opacity = '1';
                modal.style.opacity = '1';
                modal.style.transform = 'translate(-50%, -50%) scale(1)';
            }, 10);
        }
        
        window.showCanvasModal = function(side) {
            const shoulderImage = side === 'left' ? leftShoulderImage : rightShoulderImage;
            
            if (shoulderImage && shoulderImage.filename) {
                showShoulderModal(shoulderImage.filename, `${side.charAt(0).toUpperCase() + side.slice(1)} Shoulder: ${shoulderImage.filename}`, 100);
            }
        };
        
        function hideShoulderModal(delay = 0) {
            // Clear any pending show timeout
            if (modalShowTimeout) {
                clearTimeout(modalShowTimeout);
                modalShowTimeout = null;
            }
            
            // Set hide timeout if delay specified
            if (delay > 0) {
                window.modalHideTimeout = setTimeout(() => {
                    doHideModal();
                }, delay);
            } else {
                doHideModal();
            }
        }
        
        function doHideModal() {
            const modal = document.getElementById('shoulderImageModal');
            const overlay = document.getElementById('shoulderModalOverlay');
            
            if (!modal || !overlay) return;
            
            // Additional check - don't hide if mouse is over modal
            const isHoveringModal = modal.matches(':hover');
            if (isHoveringModal) {
                console.log('Modal still being hovered, keeping open');
                return;
            }
            
            modalIsVisible = false;
            currentModalFilename = null; // Clear filename when actually hiding
            
            // Fade out
            overlay.style.opacity = '0';
            modal.style.opacity = '0';
            modal.style.transform = 'translate(-50%, -50%) scale(0.95)';
            
            // Actually hide after animation, but only if still meant to be hidden
            setTimeout(() => {
                if (!modalIsVisible && !modal.matches(':hover')) {
                    modal.style.display = 'none';
                    overlay.style.display = 'none';
                }
            }, 200);
        }
        
    })();
    </script>

    <script src="/js/search_modal.js?v=20260604_1" defer></script>

    <!-- Include ProductModal System -->
    <?php
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    ?>

    <?php 
    include 'footer.php'; 
    renderFooter('school');
    ?>
</body>
</html>
