<?php
if (!function_exists('renderSchoolShoulderCustomizer')) {
    function renderSchoolShoulderCustomizer(array $options = []): void
    {
        $display = !empty($options['visible']) ? 'block' : 'none';
        ?>
<div class="shoulder-selector-section" id="shoulderSelector" style="display: <?= $display ?>; background: rgba(255,255,255,0.95); margin: 20px; padding: 30px; border-radius: 10px; text-align: center;">
    <h2 style="color: #8B4513; margin-bottom: 20px;">Customize Your Ring Shoulders</h2>
    <p style="color: #666; margin-bottom: 30px;">Select shoulder designs for your school ring. Choose the same design for both sides or mix and match.</p>

    <div class="ring-preview-container" style="margin-bottom: 30px;">
        <img src="/school_php/images/Shoulders/classic_collection.jpg" alt="Classic School Ring Collection"
             style="max-width: 400px; height: auto; object-fit: contain;"
             onerror="this.src='/school_php/images/Bands/band_classic.jpg'; this.onerror=null;">
    </div>

    <div style="margin-bottom: 20px;">
        <button onclick="clearSelections()"
                style="background: #dc3545; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px;">Clear All Selections</button>
    </div>

    <div class="canvas-container" style="display: flex; justify-content: center; gap: 40px; margin-bottom: 30px; flex-wrap: wrap;">
        <div class="mobile-selection-indicator" id="mobileStatusIndicator" style="display: none;">
            Tap a canvas above, then tap a shoulder design below to customize your ring
        </div>

        <div class="shoulder-canvas-wrapper" style="text-align: center;"
             onclick="setSelectionMode('left')"
             ontouchstart="setSelectionMode('left')">
            <h4 style="color: #8B4513; margin-bottom: 10px;">Left Shoulder</h4>
            <canvas id="leftShoulderCanvas" width="150" height="150"
                    style="border: 2px solid #FFD700; border-radius: 8px; background: #f9f9f9; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;"
                    onclick="setSelectionMode('left')"></canvas>
            <p style="font-size: 12px; color: #666; margin-top: 8px;">Click image to select shoulder</p>
        </div>

        <div class="shoulder-canvas-wrapper" style="text-align: center;"
             onclick="setSelectionMode('right')"
             ontouchstart="setSelectionMode('right')">
            <h4 style="color: #8B4513; margin-bottom: 10px;">Right Shoulder</h4>
            <canvas id="rightShoulderCanvas" width="150" height="150"
                    style="border: 2px solid #ccc; border-radius: 8px; background: #f9f9f9; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;"
                    onclick="setSelectionMode('right')"></canvas>
            <p style="font-size: 12px; color: #666; margin-top: 8px;">Click image to select shoulder</p>
        </div>
    </div>

    <div class="shoulder-gallery-container">
        <h3 style="color: #8B4513; margin-bottom: 5px;">Available Shoulder Designs</h3>
        <p style="font-size: 14px; color: #666; margin-bottom: 15px; text-align: center;">
            Desktop and mobile: Tap any design to preview and assign to left/right shoulder.
        </p>
        <div class="shoulder-gallery" id="shoulderGallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white;">
        </div>
    </div>

    <div id="shoulderImageModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; background: white; border-radius: 10px; box-shadow: 0 8px 25px rgba(0,0,0,0.3); padding: 25px; max-width: 400px; max-height: 80vh; pointer-events: auto;">
        <button onclick="closeShoulderModal()" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; line-height: 1;">&times;</button>

        <img id="shoulderModalImage" src="" alt="" style="max-width: 100%; max-height: 300px; object-fit: contain; border-radius: 8px;">
        <p id="shoulderModalTitle" style="text-align: center; margin: 10px 0; color: #666; font-size: 14px;"></p>

        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
            <button id="modalSelectLeftBtn" onclick="selectFromModal('left')"
                    style="background: #FFD700; color: black; border: 2px solid #FFD700; padding: 10px 18px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">Add to Left</button>
            <button id="modalSelectRightBtn" onclick="selectFromModal('right')"
                    style="background: #32CD32; color: white; border: 2px solid #32CD32; padding: 10px 18px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">Add to Right</button>
        </div>
    </div>
    <div id="shoulderModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 9998; pointer-events: auto;" onclick="closeShoulderModal()"></div>
</div>
        <?php
    }
}

if (!function_exists('renderSchoolShoulderCustomizerScript')) {
    function renderSchoolShoulderCustomizerScript(array $options = []): void
    {
        $imageBase = $options['image_base'] ?? '/school_php/images/Shoulders';
        $autoInit = !empty($options['auto_init']) ? 'true' : 'false';
        ?>
<script>
(function() {
    window.__sharedShoulderCustomizerLoaded = true;

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

    const shoulderImageBase = <?= json_encode(rtrim((string)$imageBase, '/')) ?>;
    const autoInit = <?= $autoInit ?>;

    let currentSelectionMode = 'left';
    let leftShoulderImage = null;
    let rightShoulderImage = null;
    let currentModalFilename = null;
    let modalShowTimeout = null;
    let modalHideTimeout = null;
    let modalIsVisible = false;

    window.loadShoulderImages = function() {
        const gallery = document.getElementById('shoulderGallery');
        if (!gallery) return;

        gallery.innerHTML = '';

        shoulderImagesList.forEach(filename => {
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'position: relative; cursor: pointer; transition: transform 0.2s;';

            const img = document.createElement('img');
            img.src = `${shoulderImageBase}/${filename}`;
            img.alt = filename.replace(/\.(jpg|png|jpeg)$/i, '').replace(/[_-]/g, ' ');
            img.style.cssText = 'width: 80px; height: 180px; object-fit: cover; border-radius: 4px; border: 2px solid transparent; transition: all 0.2s;';
            img.title = img.alt + ' - Click to view and select';

            imgContainer.dataset.filename = filename;
            imgContainer.onclick = (e) => {
                e.preventDefault();
                currentModalFilename = filename;
                showShoulderModal(filename, img.alt);
            };
        // Append the image FIRST
        imgContainer.appendChild(img);

        //  ADD THIS RIGHT HERE 
        const caption = document.createElement('div');
        caption.className = 'shoulder-name';
        caption.innerText = img.alt;
        caption.style.cssText = 'text-align:center; font-size:0.8rem; margin-top:4px; color:#333;';
        imgContainer.appendChild(caption);
        //END OF INSERT 
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
            };

            imgContainer.appendChild(img);
            gallery.appendChild(imgContainer);
        });
    };

    window.preloadShoulderImages = function() {
        return new Promise((resolve, reject) => {
            let loadedCount = 0;
            let failedCount = 0;
            const totalImages = shoulderImagesList.length;
            const startTime = performance.now();

            const loadPromises = shoulderImagesList.map((filename, index) => {
                return new Promise((resolveImg) => {
                    const img = new Image();
                    const imagePath = `${shoulderImageBase}/${filename}`;

                    img.onload = function() {
                        loadedCount++;
                        updateShoulderPreloadProgress(loadedCount, totalImages, failedCount);
                        resolveImg({ success: true, filename, src: imagePath });
                    };

                    img.onerror = function() {
                        failedCount++;
                        loadedCount++;
                        updateShoulderPreloadProgress(loadedCount, totalImages, failedCount);
                        resolveImg({ success: false, filename, src: imagePath });
                    };

                    setTimeout(() => {
                        img.src = imagePath;
                    }, index * 10);
                });
            });

            Promise.all(loadPromises)
                .then((results) => {
                    const endTime = performance.now();
                    const loadTime = Math.round(endTime - startTime);
                    const successCount = results.filter(r => r.success).length;

                    console.log(`Shoulder preloading complete: ${successCount}/${totalImages} in ${loadTime}ms`);
                    updateShoulderPreloadProgress(totalImages, totalImages, failedCount, true);

                    setTimeout(() => {
                        resolve(results);
                    }, 300);
                })
                .catch(reject);
        });
    };

    function updateShoulderPreloadProgress(loaded, total, failed, isComplete = false) {
        const progressBar = document.getElementById('shoulder-progress-bar');
        const statusText = document.getElementById('shoulder-status-text');
        const progressText = document.getElementById('shoulder-progress-text');

        if (progressBar) {
            const percentage = Math.round((loaded / total) * 100);
            progressBar.style.width = percentage + '%';
            if (percentage < 30) {
                progressBar.style.background = '#FFD700';
            } else if (percentage < 70) {
                progressBar.style.background = 'linear-gradient(90deg, #FFD700, #32CD32)';
            } else {
                progressBar.style.background = '#32CD32';
            }
        }

        if (statusText) {
            if (isComplete) {
                const successCount = total - failed;
                statusText.innerHTML = `Shoulder gallery ready! (${successCount}/${total} designs loaded)`;
                statusText.style.color = '#32CD32';
            } else if (loaded < total * 0.3) {
                statusText.innerHTML = 'Loading shoulder designs...';
            } else if (loaded < total * 0.7) {
                statusText.innerHTML = 'Loading more design options...';
            } else {
                statusText.innerHTML = 'Almost finished loading...';
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

    window.showShoulderLoadingIndicator = function() {
        const shoulderSelector = document.getElementById('shoulderSelector');
        if (!shoulderSelector) return;

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
            </div>
        `;

        shoulderSelector.style.position = 'relative';
        shoulderSelector.appendChild(loadingOverlay);

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
    };

    window.hideShoulderLoadingIndicator = function() {
        const overlay = document.getElementById('shoulder-loading-overlay');
        if (overlay) {
            overlay.style.transition = 'opacity 0.5s ease';
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
            }, 500);
        }
    };

    window.setupCanvases = function() {
        const leftCanvas = document.getElementById('leftShoulderCanvas');
        const rightCanvas = document.getElementById('rightShoulderCanvas');

        if (!leftCanvas || !rightCanvas) return;

        clearCanvas(leftCanvas);
        clearCanvas(rightCanvas);
    };

    window.selectFromModal = function(side) {
        if (!currentModalFilename) {
            return;
        }

        if (window.navigator && window.navigator.vibrate) {
            window.navigator.vibrate(30);
        }

        try {
            if (side === 'both') {
                selectShoulderImageDirect(currentModalFilename, 'left');
                selectShoulderImageDirect(currentModalFilename, 'right');
            } else {
                selectShoulderImageDirect(currentModalFilename, side);
                updateButtonAfterSelection(side);
                return;
            }
        } catch (error) {
            console.error('Error selecting shoulder:', error);
        }

        closeShoulderModal();
    };

    function updateButtonAfterSelection(selectedSide) {
        const leftBtn = document.getElementById('modalSelectLeftBtn');
        const rightBtn = document.getElementById('modalSelectRightBtn');

        if (!leftBtn || !rightBtn) return;

        if (selectedSide === 'left') {
            leftBtn.textContent = 'Add to Both';
            leftBtn.onclick = function() { selectFromModal('both'); };
            leftBtn.style.background = '#8A2BE2';
            leftBtn.style.borderColor = '#8A2BE2';
            leftBtn.className += ' modal-both-button';
            if (window.matchMedia('(hover: hover)').matches) {
                leftBtn.onmouseover = function() { this.style.background = '#6B1B8A'; };
                leftBtn.onmouseout = function() { this.style.background = '#8A2BE2'; };
            }
        } else if (selectedSide === 'right') {
            rightBtn.textContent = 'Add to Both';
            rightBtn.onclick = function() { selectFromModal('both'); };
            rightBtn.style.background = '#8A2BE2';
            rightBtn.style.borderColor = '#8A2BE2';
            rightBtn.className += ' modal-both-button';
            if (window.matchMedia('(hover: hover)').matches) {
                rightBtn.onmouseover = function() { this.style.background = '#6B1B8A'; };
                rightBtn.onmouseout = function() { this.style.background = '#8A2BE2'; };
            }
        }
    }

    window.setSelectionMode = function(mode) {
        currentSelectionMode = mode;

        const leftCanvas = document.getElementById('leftShoulderCanvas');
        const rightCanvas = document.getElementById('rightShoulderCanvas');
        const mobileIndicator = document.getElementById('mobileStatusIndicator');

        if (!leftCanvas || !rightCanvas) {
            return;
        }

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
            leftCanvas.style.borderColor = '#FFD700';
            leftCanvas.style.boxShadow = '0 0 10px rgba(255, 215, 0, 0.5)';
            rightCanvas.style.borderColor = '#ccc';
            rightCanvas.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            rightCanvas.style.borderColor = '#32CD32';
            rightCanvas.style.boxShadow = '0 0 10px rgba(50, 205, 50, 0.5)';
            leftCanvas.style.borderColor = '#ccc';
            leftCanvas.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        }
    };

    function selectShoulderImageDirect(filename, targetSide) {
        const targetCanvas = targetSide === 'left' ?
            document.getElementById('leftShoulderCanvas') :
            document.getElementById('rightShoulderCanvas');

        if (!targetCanvas) {
            return;
        }

        const img = new Image();
        img.onload = () => {
            drawImageToCanvas(targetCanvas, img);

            if (targetSide === 'left') {
                leftShoulderImage = { filename, img };
            } else {
                rightShoulderImage = { filename, img };
            }

            updateGallerySelection(filename, targetSide);
        };
        img.onerror = () => {
            console.error('Failed to load image:', filename);
        };
        img.src = `${shoulderImageBase}/${filename}`;
    }

    function updateGallerySelection(filename, targetSide) {
        document.querySelectorAll('#shoulderGallery img.selected').forEach(img => {
            img.classList.remove('selected');
            img.style.borderColor = 'transparent';
        });

        const imgElement = document.querySelector(`#shoulderGallery img[src*="${filename}"]`);
        if (imgElement) {
            imgElement.classList.add('selected');
            imgElement.style.borderColor = targetSide === 'left' ? '#FFD700' : '#32CD32';
        }
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

        setSelectionMode('left');
    };

    window.closeShoulderModal = function() {
        const modal = document.getElementById('shoulderImageModal');
        const overlay = document.getElementById('shoulderModalOverlay');

        if (!modal || !overlay) return;

        modalIsVisible = false;
        currentModalFilename = null;

        overlay.style.opacity = '0';
        modal.style.opacity = '0';
        modal.style.transform = 'translate(-50%, -50%) scale(0.95)';

        setTimeout(() => {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }, 150);
    };

    window.keepModalOpen = function() {
        if (modalHideTimeout) {
            clearTimeout(modalHideTimeout);
            modalHideTimeout = null;
        }
    };

    function showShoulderModal(filename, altText, delay = 0) {
        if (modalHideTimeout) {
            clearTimeout(modalHideTimeout);
            modalHideTimeout = null;
        }

        if (modalIsVisible) {
            updateModalContent(filename, altText);
            return;
        }

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
            modalImage.src = `${shoulderImageBase}/${filename}`;

            let cleanTitle = altText || filename.replace(/\.(jpg|png|jpeg)$/i, '');
            cleanTitle = cleanTitle
                .replace(/[_-]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(' ');

            modalTitle.textContent = cleanTitle;
            resetModalButtons();
        }
    }

    function resetModalButtons() {
        const leftBtn = document.getElementById('modalSelectLeftBtn');
        const rightBtn = document.getElementById('modalSelectRightBtn');

        if (leftBtn) {
            leftBtn.textContent = 'Add to Left';
            leftBtn.onclick = function() { selectFromModal('left'); };
            leftBtn.style.background = '#FFD700';
            leftBtn.style.borderColor = '#FFD700';
            leftBtn.className = leftBtn.className.replace(' modal-both-button', '');
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

        overlay.style.display = 'block';
        modal.style.display = 'block';
        modalIsVisible = true;

        setTimeout(() => {
            overlay.style.opacity = '1';
            modal.style.opacity = '1';
            modal.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 10);
    }

    function doHideModal() {
        const modal = document.getElementById('shoulderImageModal');
        const overlay = document.getElementById('shoulderModalOverlay');

        if (!modal || !overlay) return;

        const isHoveringModal = modal.matches(':hover');
        if (isHoveringModal) {
            return;
        }

        modalIsVisible = false;
        currentModalFilename = null;

        overlay.style.opacity = '0';
        modal.style.opacity = '0';
        modal.style.transform = 'translate(-50%, -50%) scale(0.95)';

        setTimeout(() => {
            if (!modalIsVisible && !modal.matches(':hover')) {
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }
        }, 200);
    }

    function initShoulderCustomizer() {
        const shoulderSelector = document.getElementById('shoulderSelector');
        if (!shoulderSelector) {
            return;
        }

        shoulderSelector.style.display = 'block';

        showShoulderLoadingIndicator();
        preloadShoulderImages().then(() => {
            loadShoulderImages();
            setupCanvases();
            setSelectionMode('left');
            setTimeout(() => {
                hideShoulderLoadingIndicator();
            }, 500);
        }).catch(() => {
            loadShoulderImages();
            setupCanvases();
            setSelectionMode('left');
            hideShoulderLoadingIndicator();
        });
    }

    window.initShoulderCustomizer = initShoulderCustomizer;

    if (autoInit) {
        document.addEventListener('DOMContentLoaded', initShoulderCustomizer);
    }
})();
</script>
        <?php
    }
}
