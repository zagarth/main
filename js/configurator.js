/**
 * Product Configurator Engine
 * Blue Nile-style interactive product customization
 * Version: 1.0
 * Date: 2025-10-09
 */

class ProductConfigurator {
    constructor(element) {
        this.element = element;
        this.config = null;
        this.selections = {};
        this.priceModifiers = {};
        this.thumbnailCache = {}; // Cache for Celtic thumbnails
        this.userInteracted = {}; // Track which options user has actually clicked
        
        // Extract context from data attributes
        this.extractContext();
    }
    
    /**
     * Apply ring size defaults based on product type and category
     * Custom logic for Celtic rings vs regular rings
     */
    applyRingSizeDefaults() {
        const productIdUpper = this.productId.toUpperCase();
        console.log('Product ID for size logic:', `"${productIdUpper}"`, 'Length:', productIdUpper.length);
        console.log('Last character:', `"${productIdUpper.slice(-1)}"`);
        console.log('Category:', this.category);
        console.log('Ends with L?', productIdUpper.endsWith('L'));
        console.log('Ends with M?', productIdUpper.endsWith('M'));
        
        // Check if this is a Celtic ring for custom sizing logic
        if (this.category === 'celtic') {
            this.applyCelticRingSizeDefaults(productIdUpper);
        } else {
            this.applyStandardRingSizeDefaults(productIdUpper);
        }
    }
    
    /**
     * Celtic ring size defaults - custom logic for Celtic bands
     * TODO: Implement Celtic-specific sizing requirements
     * Note: Celtic rings may have different sizing considerations due to:
     * - Intricate patterns that affect fit
     * - Different band widths affecting comfort
     * - Cultural sizing preferences
     */
    applyCelticRingSizeDefaults(productIdUpper) {
        console.log('🍀 Applying Celtic ring size defaults');
        
        // For now, use similar logic but with Celtic-specific adjustments
        // TODO: Customize based on Celtic ring requirements:
        // - Consider pattern complexity affecting fit
        // - Account for wider Celtic bands
        // - Regional sizing preferences
        
        if (productIdUpper.endsWith('L')) {
            // Celtic Ladies ring - might need slightly larger due to pattern depth
            this.selections['size'] = '7';  // Slightly larger than standard 6.5
            console.log(`🍀✅ Celtic Ladies ring detected (${this.productId}): Setting default size to 7`);
        } else if (productIdUpper.endsWith('M')) {
            // Celtic Mens ring - standard sizing for now
            this.selections['size'] = '10';
            console.log(`🍀✅ Celtic Mens ring detected (${this.productId}): Setting default size to 10`);
        } else {
            // Celtic unisex - use JSON default
            console.log(`🍀ℹ️ Celtic generic ring detected (${this.productId}): Using default size ${this.config.options.size.default}`);
        }
    }
    
    /**
     * Standard ring size defaults for non-Celtic rings
     */
    applyStandardRingSizeDefaults(productIdUpper) {
        console.log('💍 Applying standard ring size defaults');
        
        if (productIdUpper.endsWith('L')) {
            // Ladies ring - default to size 6.5
            this.selections['size'] = '6.5';
            console.log(`💍✅ Ladies ring detected (${this.productId}): Setting default size to 6.5`);
        } else if (productIdUpper.endsWith('M')) {
            // Mens ring - default to size 10
            this.selections['size'] = '10';
            console.log(`💍✅ Mens ring detected (${this.productId}): Setting default size to 10`);
        } else {
            // Generic/unisex - keep default from JSON but log it
            console.log(`💍ℹ️ Generic ring detected (${this.productId}): Using default size ${this.config.options.size.default}`);
        }
    }

    extractContext() {
        // Check if element exists
        if (!this.element) {
            console.error('ProductConfigurator: No element provided');
            this.collection = 'bands';
            this.productId = null;
            this.category = null;
            this.productName = null;
            this.basePrice = 500;
            this.baseProductId = '';
            return;
        }
        
        // Extract context data from element attributes
        const dataset = this.element.dataset || {};
        const rawData = {
            collection: dataset.collection || this.element.getAttribute('data-collection'),
            productId: dataset.productId || this.element.getAttribute('data-product-id'),
            category: dataset.category || this.element.getAttribute('data-category'),
            productName: dataset.productName || this.element.getAttribute('data-product-name'),
            basePrice: dataset.basePrice || this.element.getAttribute('data-base-price'),
            baseProductId: dataset.baseProductId || this.element.getAttribute('data-base-product-id')
        };
        
        console.log('Raw data attributes:', rawData);
        console.log('Category specifically:', rawData.category);
        
        // Process and store the extracted values
        this.collection = rawData.collection || 'bands';
        this.productId = rawData.productId;
        this.category = rawData.category;
        this.productName = rawData.productName;
        this.basePrice = parseFloat(rawData.basePrice) || 500;
        this.baseProductId = rawData.baseProductId || this.productId?.replace(/[ML]$/, '') || '';
        
        console.log('Extracted values:', {
            collection: this.collection,
            productId: this.productId,
            category: this.category,
            productName: this.productName,
            basePrice: this.basePrice,
            baseProductId: this.baseProductId
        });
    }
    
    applyFilterSpecificStyling() {
        // Apply filter-specific CSS classes based on category - CELTIC ONLY
        if (!this.element || !this.category) return;
        
        // Only apply special styling to Celtic bands
        if (this.category !== 'celtic') return;
        
        // Find the configurator wrapper (might be the element itself or a parent)
        let configuratorWrapper = this.element.querySelector('.configurator-wrapper');
        if (!configuratorWrapper) {
            configuratorWrapper = this.element.closest('.configurator-wrapper');
        }
        if (!configuratorWrapper) {
            configuratorWrapper = this.element;
        }
        
        // Apply Celtic-specific class only
        configuratorWrapper.classList.add('configurator-celtic');
        
        console.log(`Applied Celtic-specific styling to configurator`);
        
        // Apply Celtic-specific features
        if (this.collection === 'bands' && this.category === 'celtic') {
            this.applyCelticSpecificFeatures();
        }
    }
    
    applyCelticSpecificFeatures() {
        // Celtic-specific configurator enhancements
        console.log('Applying Celtic-specific features...');
        
        // Any Celtic-specific JavaScript behavior can go here
        // For example, special validation, pattern recommendations, etc.
    }
    
    async init() {
        try {
            // Log product context for debugging
            console.log('Configurator initialized with context:', {
                collection: this.collection,
                productId: this.productId,
                category: this.category,
                productName: this.productName,
                basePrice: this.basePrice
            });
            
            // Show loading state
            this.showLoading();
            
            await this.loadConfiguration();
            console.log('Configuration loaded, applying filter-specific styling...');
            this.applyFilterSpecificStyling();
            console.log('Defaults set, rendering...');
            this.setDefaults();
            console.log('Defaults set, rendering...');
            this.render();
            this.attachEventListeners();
            this.calculatePrice();
            // Small delay to ensure DOM is fully rendered before updating badges
            setTimeout(() => {
                console.log('🕐 Running delayed badge update...');
                console.log('Current element:', this.element);
                console.log('Option groups found:', this.element.querySelectorAll('[data-option]').length);
                this.updateAllRequiredBadges();
            }, 100);
            console.log('Configurator initialization complete');
        } catch (error) {
            console.error('Failed to initialize configurator:', error);
            this.showError('Unable to load product options. Please refresh the page.');
        }
    }
    
    showLoading() {
        if (this.element) {
            this.element.innerHTML = `
                <div class="configurator-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading product options...</p>
                </div>
            `;
        }
    }
    
    async loadConfiguration() {
        // Determine the correct collection for API call
        // For bands with specific categories, use the category as collection
        let apiCollection = this.collection;
        if (this.collection === 'bands' && this.category) {
            // Map band categories to their specific configurator files
            const categoryMap = {
                'plain': 'plain',
                'celtic': 'celtic', 
                'cultural': 'cultural',
                'fancy': 'fancy'
            };
            if (categoryMap[this.category]) {
                apiCollection = categoryMap[this.category];
            }
        }
        
        try {
            // Use absolute path from web root with cache busting based on timestamp
            const cacheBuster = Date.now();
            const apiUrl = `/api/get_configurator_config.php?collection=${apiCollection}&_t=${cacheBuster}`;
            
            console.log('Loading configuration from:', apiUrl);
            console.log('Original collection:', this.collection, 'Category:', this.category, 'API collection:', apiCollection);
            
            // Add timeout to prevent hanging
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
            
            const response = await fetch(apiUrl, { 
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            console.log('Configuration data received:', data);
            
            // API returns collection-specific data
            if (data.data) {
                this.config = data.data;
            } else {
                // Fallback for full config response
                this.config = data.collections[this.collection];
            }
            
            // Base price from PHP context takes precedence
            if (!this.basePrice && this.config.base_price) {
                this.basePrice = this.config.base_price;
            }
            
            // Merge category-specific options if available
            if (this.category && this.config.category_overrides && this.config.category_overrides[this.category]) {
                const categoryOptions = this.config.category_overrides[this.category];
                console.log(`Merging category-specific options for '${this.category}':`, categoryOptions);
                this.config.options = { ...this.config.options, ...categoryOptions };
            } else if (this.category) {
                console.log(`No category-specific options found for '${this.category}'`);
            }
            
            console.log('Final configuration options:', Object.keys(this.config.options));
            
            // Cache in localStorage for performance
            localStorage.setItem(`configurator_cache_${apiCollection}`, JSON.stringify(data));
            localStorage.setItem(`configurator_cache_time_${apiCollection}`, Date.now());
        } catch (error) {
            // Try to load from cache if fetch fails
            const cachedData = localStorage.getItem(`configurator_cache_${apiCollection}`);
            const cacheTime = localStorage.getItem(`configurator_cache_time_${apiCollection}`);
            
            // Use cache if it's less than 1 hour old
            if (cachedData && cacheTime && (Date.now() - parseInt(cacheTime)) < 3600000) {
                const data = JSON.parse(cachedData);
                if (data.data) {
                    this.config = data.data;
                    this.basePrice = this.config.base_price;
                } else {
                    this.config = data.collections[this.collection];
                    this.basePrice = this.config.base_price;
                }
            } else {
                throw error;
            }
        }
    }
    
    setDefaults() {
        for (const [optionKey, optionConfig] of Object.entries(this.config.options)) {
            if (optionConfig.required && optionConfig.default) {
                this.selections[optionKey] = optionConfig.default;
            }
        }
        
        // Set gender-specific ring size defaults based on product ID
        if (this.config.options.size && this.productId) {
            this.applyRingSizeDefaults();
        } else {
            console.log('❌ Size logic skipped - missing size option or productId:', {
                hasSize: !!this.config.options.size,
                productId: this.productId
            });
        }
        
        // Debug: Log all selections after defaults are set
        console.log('Default selections set:', this.selections);
    }
    
    getMatchingProductId(productId) {
        if (!productId || typeof productId !== 'string') {
            return { found: false, id: '', gender: '', selectedWidth: null };
        }
        
        const productIdUpper = productId.toUpperCase();
        const lastChar = productIdUpper.slice(-1);
        
        // Check if it ends with M or L
        if (lastChar === 'M') {
            // Mens ring - matching would be Ladies
            const baseId = productId.slice(0, -1);
            return {
                found: true,
                id: baseId + 'L',
                gender: 'Ladies',
                selectedWidth: this.getSelectedCelticWidth(productId)
            };
        } else if (lastChar === 'L') {
            // Ladies ring - matching would be Mens
            const baseId = productId.slice(0, -1);
            return {
                found: true,
                id: baseId + 'M',
                gender: 'Mens',
                selectedWidth: this.getSelectedCelticWidth(productId)
            };
        }
        
        // No M or L suffix - no matching set available
        return { found: false, id: '', gender: '', selectedWidth: null };
    }

    getSelectedCelticWidth(productId) {
        // Check if we have a Celtic grid selection that matches this product
        const widthSelection = this.selections.width;
        if (widthSelection && widthSelection.startsWith('width_')) {
            const selectedProductId = widthSelection.replace('width_', '').replace(/[ML]$/, '');
            const baseProductId = productId.replace(/[ML]$/, '');
            
            if (selectedProductId === baseProductId) {
                // Find the width from the Celtic grid configuration
                const celticConfig = this.config.options.width?.celtic_override;
                if (celticConfig && celticConfig.grid_layout) {
                    for (const row of celticConfig.grid_layout.rows) {
                        for (const option of row.options) {
                            if (option.product_id === selectedProductId) {
                                return option.width;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    getMatchingSetOptions(currentProductId) {
        const matchingProduct = this.getMatchingProductId(currentProductId);
        if (!matchingProduct.found) {
            return [];
        }

        // Get Celtic grid configuration
        const celticConfig = this.config.options.width?.celtic_override;
        if (!celticConfig || !celticConfig.grid_layout) {
            return [{
                productId: matchingProduct.id,
                width: 'Standard',
                gender: matchingProduct.gender,
                price_modifier: 0
            }];
        }

        // Find all width options for the matching product
        const baseId = matchingProduct.id.replace(/[ML]$/, '');
        const options = [];

        for (const row of celticConfig.grid_layout.rows) {
            for (const option of row.options) {
                const optionBaseId = option.product_id.replace(/[ML]$/, '');
                if (optionBaseId === baseId) {
                    const genderSuffix = matchingProduct.id.slice(-1);
                    options.push({
                        productId: option.product_id + genderSuffix,
                        width: option.width,
                        gender: matchingProduct.gender,
                        price_modifier: option.price_modifier || 0,
                        pattern: row.pattern
                    });
                }
            }
        }

        return options;
    }
    
    getProductThumbnailPath(productId) {
        // Get the category from the current page's data attribute
        let category = 'plain'; // Default category
        
        // Try to get category from the container's data attribute
        if (this.element && this.element.getAttribute) {
            const containerCategory = this.element.getAttribute('data-category');
            if (containerCategory) {
                category = containerCategory;
            }
        }
        
        // If still no category, try to detect from URL or other context
        if (category === 'plain' && this.category) {
            category = this.category;
        }
        
        // Generate the correct thumbnail path structure
        return `/bands_php/thumbs/images/${category}/${productId}.png`;
    }
    
    getCelticPatternThumbnail(row) {
        // Use the current product's image for pattern display with M/L fallback logic
        if (this.productId) {
            const baseProductId = this.productId.replace(/[ML]$/, '');
            
            // Try to get the best available image for this pattern
            return new Promise((resolve) => {
                this.getCelticWidthThumbnails(baseProductId).then(thumbnails => {
                    // Prefer M first, then L, then placeholder
                    if (thumbnails.M && thumbnails.M !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.M);
                    } else if (thumbnails.L && thumbnails.L !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.L);
                    } else {
                        resolve('images/jewelry-placeholder.jpg');
                    }
                });
            });
        }
        // Fallback to first option's image
        return Promise.resolve(`/bands_php/images/celtic/${row.options[0].product_id}.png`);
    }
    
    getCelticWidthThumbnail(baseProductId, suffix) {
        // Use the API to get the best available thumbnail
        return fetch(`/homesite/api/get_celtic_thumbnails.php?product_id=${baseProductId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.warn('Celtic thumbnail API error:', data.error);
                    return '/images/jewelry-placeholder.jpg';
                }
                
                // Return the appropriate image for the requested suffix
                if (suffix === 'M' && data.M) {
                    return data.M;
                } else if (suffix === 'L' && data.L) {
                    return data.L;
                } else {
                    // Fallback to placeholder
                    return '/images/jewelry-placeholder.jpg';
                }
            })
            .catch(error => {
                console.warn('Celtic thumbnail fetch error:', error);
                return '/images/jewelry-placeholder.jpg';
            });
    }
    
    loadPatternThumbnail(row) {
        const patternImg = document.getElementById('celtic-pattern-image');
        if (patternImg) {
            if (this.collection === 'cultural') {
                this.getCulturalPatternThumbnail(row).then(imagePath => {
                    patternImg.src = imagePath;
                });
            } else if (this.collection === 'plain') {
                this.getPlainPatternThumbnail(row).then(imagePath => {
                    patternImg.src = imagePath;
                });
            } else if (this.collection === 'fancy') {
                this.getFancyPatternThumbnail(row).then(imagePath => {
                    patternImg.src = imagePath;
                });
            } else {
                this.getCelticPatternThumbnail(row).then(imagePath => {
                    patternImg.src = imagePath;
                });
            }
        }
    }
    
    loadCollectionThumbnails() {
        const thumbnailImages = document.querySelectorAll('.width-image[data-product-id]');
        
        if (thumbnailImages.length === 0) {
            console.log(`No ${this.collection} thumbnail images found to load`);
            return;
        }
        
        console.log(`Loading ${this.collection} thumbnails for ${thumbnailImages.length} images`);
        
        // Group images by base product ID to make one API call per product
        const productGroups = {};
        thumbnailImages.forEach(img => {
            const productId = img.getAttribute('data-product-id');
            const suffix = img.getAttribute('data-suffix');
            
            if (productId && suffix) {
                if (!productGroups[productId]) {
                    productGroups[productId] = [];
                }
                productGroups[productId].push({ img, suffix });
            }
        });
        
        console.log('Product groups for thumbnails:', Object.keys(productGroups));
        
        // Load thumbnails for each product group
        Object.keys(productGroups).forEach(productId => {
            let thumbnailsPromise;
            
            if (this.collection === 'cultural') {
                thumbnailsPromise = this.getCulturalWidthThumbnails(productId);
            } else if (this.collection === 'plain') {
                thumbnailsPromise = this.getPlainWidthThumbnails(productId);
            } else if (this.collection === 'fancy') {
                thumbnailsPromise = this.getFancyWidthThumbnails(productId);
            } else {
                thumbnailsPromise = this.getCelticWidthThumbnails(productId);
            }
                
            thumbnailsPromise.then(thumbnails => {
                console.log(`Loaded thumbnails for ${productId}:`, thumbnails);
                productGroups[productId].forEach(({ img, suffix }) => {
                    if (suffix === 'M' && thumbnails.M) {
                        img.src = thumbnails.M;
                        console.log(`Set M thumbnail for ${productId}: ${thumbnails.M}`);
                    } else if (suffix === 'L' && thumbnails.L) {
                        img.src = thumbnails.L;
                        console.log(`Set L thumbnail for ${productId}: ${thumbnails.L}`);
                    }
                });
            }).catch(error => {
                console.warn(`Failed to load thumbnails for ${productId}:`, error);
            });
        });
    }
    
    getCelticWidthThumbnails(baseProductId) {
        // Check cache first
        if (this.thumbnailCache[baseProductId]) {
            return Promise.resolve(this.thumbnailCache[baseProductId]);
        }
        
        // Get thumbnails for both M and L variants using the API
        // Use absolute path from web root
        const apiUrl = `/api/get_celtic_thumbnails.php?product_id=${baseProductId}`;
        
        return fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.warn('Celtic thumbnail API error:', data.error);
                    const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                    this.thumbnailCache[baseProductId] = fallback;
                    return fallback;
                }
                
                const result = {
                    M: data.M || 'images/jewelry-placeholder.jpg',
                    L: data.L || 'images/jewelry-placeholder.jpg'
                };
                
                // Cache the result
                this.thumbnailCache[baseProductId] = result;
                return result;
            })
            .catch(error => {
                console.warn('Celtic thumbnail fetch error:', error);
                const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                this.thumbnailCache[baseProductId] = fallback;
                return fallback;
            });
    }
    
    // Helper method to get the best available Celtic thumbnail
    getCelticThumbnailWithFallback(baseProductId) {
        // Try M first (preferred), then L as fallback
        const currentSuffix = this.productId && this.productId.endsWith('L') ? 'L' : 'M';
        return `/bands_php/thumbs/images/celtic/${baseProductId}${currentSuffix}.png`;
    }
    
    // Cultural thumbnail methods (similar to Celtic)
    getCulturalPatternThumbnail(row) {
        // Use the current product's image for pattern display with M/L fallback logic
        if (this.productId) {
            const baseProductId = this.productId.replace(/[ML]$/, '');
            
            // Try to get the best available image for this pattern
            return new Promise((resolve) => {
                this.getCulturalWidthThumbnails(baseProductId).then(thumbnails => {
                    // Prefer M first, then L, then placeholder
                    if (thumbnails.M && thumbnails.M !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.M);
                    } else if (thumbnails.L && thumbnails.L !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.L);
                    } else {
                        resolve('images/jewelry-placeholder.jpg');
                    }
                });
            });
        }
        // No fallback needed - API handles all image resolution
        return Promise.resolve('images/jewelry-placeholder.jpg');
    }
    
    getCulturalWidthThumbnails(baseProductId) {
        // Check cache first
        if (this.thumbnailCache[`cultural_${baseProductId}`]) {
            return Promise.resolve(this.thumbnailCache[`cultural_${baseProductId}`]);
        }
        
        // Get thumbnails for both M and L variants using the API
        const apiUrl = `/api/get_collection_thumbnails.php?product_id=${baseProductId}&collection=cultural`;
        
        return fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.warn('Cultural thumbnail API error:', data.error);
                    const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                    this.thumbnailCache[`cultural_${baseProductId}`] = fallback;
                    return fallback;
                }
                
                const result = {
                    M: data.M || 'images/jewelry-placeholder.jpg',
                    L: data.L || 'images/jewelry-placeholder.jpg'
                };
                
                // Cache the result
                this.thumbnailCache[`cultural_${baseProductId}`] = result;
                return result;
            })
            .catch(error => {
                console.warn('Cultural thumbnail fetch error:', error);
                const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                this.thumbnailCache[`cultural_${baseProductId}`] = fallback;
                return fallback;
            });
    }

    // Plain thumbnail methods (similar to Celtic/Cultural)
    getPlainPatternThumbnail(row) {
        if (!row || !row.options || !row.options[0]) {
            return Promise.resolve('images/jewelry-placeholder.jpg');
        }
        
        const baseProductId = row.options[0].product_id.replace(/[ML]$/, '');
        
        return new Promise((resolve) => {
            if (baseProductId) {
                this.getPlainWidthThumbnails(baseProductId).then(thumbnails => {
                    if (thumbnails.M && thumbnails.M !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.M);
                    } else if (thumbnails.L && thumbnails.L !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.L);
                    } else {
                        resolve('images/jewelry-placeholder.jpg');
                    }
                });
            } else {
                resolve('images/jewelry-placeholder.jpg');
            }
        });
    }
    
    getPlainWidthThumbnails(baseProductId) {
        // Check cache first
        if (this.thumbnailCache[`plain_${baseProductId}`]) {
            return Promise.resolve(this.thumbnailCache[`plain_${baseProductId}`]);
        }
        
        const apiUrl = `/api/get_collection_thumbnails.php?product_id=${baseProductId}&collection=plain`;
        
        return fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.warn('Plain thumbnail API error:', data.error);
                    const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                    this.thumbnailCache[`plain_${baseProductId}`] = fallback;
                    return fallback;
                }
                
                const result = {
                    M: data.M || 'images/jewelry-placeholder.jpg',
                    L: data.L || 'images/jewelry-placeholder.jpg'
                };
                
                this.thumbnailCache[`plain_${baseProductId}`] = result;
                return result;
            })
            .catch(error => {
                console.warn('Plain thumbnail fetch error:', error);
                const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                this.thumbnailCache[`plain_${baseProductId}`] = fallback;
                return fallback;
            });
    }

    // Fancy thumbnail methods (similar to other collections)
    getFancyPatternThumbnail(row) {
        if (!row || !row.options || !row.options[0]) {
            return Promise.resolve('images/jewelry-placeholder.jpg');
        }
        
        const baseProductId = row.options[0].product_id.replace(/[ML]$/, '');
        
        return new Promise((resolve) => {
            if (baseProductId) {
                this.getFancyWidthThumbnails(baseProductId).then(thumbnails => {
                    if (thumbnails.M && thumbnails.M !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.M);
                    } else if (thumbnails.L && thumbnails.L !== 'images/jewelry-placeholder.jpg') {
                        resolve(thumbnails.L);
                    } else {
                        resolve('images/jewelry-placeholder.jpg');
                    }
                });
            } else {
                resolve('images/jewelry-placeholder.jpg');
            }
        });
    }
    
    getFancyWidthThumbnails(baseProductId) {
        // Check cache first
        if (this.thumbnailCache[`fancy_${baseProductId}`]) {
            return Promise.resolve(this.thumbnailCache[`fancy_${baseProductId}`]);
        }
        
        const apiUrl = `/api/get_collection_thumbnails.php?product_id=${baseProductId}&collection=fancy`;
        
        return fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.warn('Fancy thumbnail API error:', data.error);
                    const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                    this.thumbnailCache[`fancy_${baseProductId}`] = fallback;
                    return fallback;
                }
                
                const result = {
                    M: data.M || 'images/jewelry-placeholder.jpg',
                    L: data.L || 'images/jewelry-placeholder.jpg'
                };
                
                this.thumbnailCache[`fancy_${baseProductId}`] = result;
                return result;
            })
            .catch(error => {
                console.warn('Fancy thumbnail fetch error:', error);
                const fallback = { M: 'images/jewelry-placeholder.jpg', L: 'images/jewelry-placeholder.jpg' };
                this.thumbnailCache[`fancy_${baseProductId}`] = fallback;
                return fallback;
            });
    }
    
    // Get category-specific name for configurator header
    getCategorySpecificName() {
        console.log('Getting category-specific name for category:', this.category);
        
        if (!this.category) {
            console.log('No category found, using default config name:', this.config.name);
            return this.config.name; // fallback to default
        }
        
        // Use category_names from config if available, otherwise fallback to hardcoded
        const categoryNames = this.config.category_names || {
            'celtic': 'Celtic Bands',
            'fancy': 'Designer Wedding Bands', 
            'plain': 'Classic Wedding Bands',
            'cultural': 'Heritage Wedding Bands'
        };
        
        const result = categoryNames[this.category] || this.config.name;
        console.log('Category-specific name result:', result);
        return result;
    }
    
    render() {
        if (!this.element) {
            console.error('Container element not found');
            return;
        }
        
        let html = `
            <div class="configurator-wrapper">
                <div class="configurator-header">
                    <h2>Customize Your ${this.getCategorySpecificName()}</h2>
                    <div class="price-display">
                        <span class="price-label">Estimated Price:</span>
                        <span class="price-value" id="configurator-price">$${this.formatPrice(this.basePrice)}</span>
                    </div>
                </div>
                
                <div class="configurator-body">
                    <div class="options-panel">
                        ${this.renderOptions()}
                    </div>
                </div>
                
                <div class="configurator-summary">
                    ${this.renderSummary()}
                </div>
                
                <div class="configurator-footer">
                    ${this.renderActions()}
                </div>
            </div>
        `;
        
        this.element.innerHTML = html;
        
        // Store reference to configurator instance for accordion functionality
        this.element.querySelector('.configurator-wrapper').productConfigurator = this;
        
        // Remove animation class after animation completes
        setTimeout(() => {
            this.element.querySelectorAll('.option-group.animating-in').forEach(group => {
                group.classList.remove('animating-in');
            });
        }, 400); // Match animation duration
        
        // Initialize accordions if needed
        this.initializeAccordions();
    }
    
    renderOptions() {
        let html = '';
        
        for (const [optionKey, optionConfig] of Object.entries(this.config.options)) {
            // Check if option should be visible based on other selections
            let isNewlyVisible = false;
            if (optionConfig.visible_when) {
                let shouldShow = false;
                for (const [dependentKey, dependentValues] of Object.entries(optionConfig.visible_when)) {
                    if (dependentValues.includes(this.selections[dependentKey])) {
                        shouldShow = true;
                        // Check if this option wasn't visible before
                        if (!this.previouslyVisible || !this.previouslyVisible.has(optionKey)) {
                            isNewlyVisible = true;
                        }
                        break;
                    }
                }
                if (!shouldShow) continue;
                
                // Track visible options for next render
                if (!this.previouslyVisible) {
                    this.previouslyVisible = new Set();
                }
                this.previouslyVisible.add(optionKey);
            }
            
            // Check for Celtic category override
            let displayConfig = optionConfig;
            if (optionKey === 'width' && this.category === 'celtic' && optionConfig.celtic_override) {
                displayConfig = optionConfig.celtic_override;
            }
            
            html += `
                <div class="option-group ${this.shouldUseAccordion() ? 'accordion' : ''} ${isNewlyVisible ? 'animating-in' : ''}" data-option="${optionKey}">
                    <div class="option-header ${this.shouldUseAccordion() ? 'accordion-header' : ''}" ${this.shouldUseAccordion() ? 'onclick="this.closest(\'.configurator-wrapper\').productConfigurator.toggleAccordion(this)"' : ''}>
                        <h3>${displayConfig.label}</h3>
                        ${displayConfig.required ? '<span class="required-badge">Required</span>' : ''}
                        ${displayConfig.help_text ? `<p class="help-text">${displayConfig.help_text}</p>` : ''}
                    </div>
                    <div class="accordion-content ${this.shouldUseAccordion() ? 'collapsed' : ''}">
                        ${this.renderOptionType(optionKey, optionConfig)}
                    </div>
                </div>
            `;
        }
        
        return html;
    }
    
    renderOptionType(optionKey, optionConfig) {
        // Debug log for width option
        if (optionKey === 'style_and_width' || optionKey === 'width') {
            console.log(`🔍 RENDERING ${optionKey} with type: ${optionConfig.type}`, optionConfig);
        }
        
        // Check for Celtic/Cultural category override for width selection
        if (optionKey === 'width' && (this.category === 'celtic' || this.category === 'cultural') && optionConfig.celtic_override) {
            return this.renderCelticGrid(optionKey, optionConfig.celtic_override);
        }
        
        // Check for Plain bands style_and_width - use Celtic grid system
        if (optionKey === 'style_and_width' && this.category === 'plain') {
            return this.renderPlainAsGrid(optionKey, optionConfig);
        }
        
        switch (optionConfig.type) {
            case 'plain_grid':
                return this.renderPlainGrid(optionKey, optionConfig);
            case 'single_select':
                return this.renderSingleSelect(optionKey, optionConfig);
            case 'text_input':
                return this.renderTextInput(optionKey, optionConfig);
            case 'checkbox':
                return this.renderCheckbox(optionKey, optionConfig);
            case 'celtic_grid':
                return this.renderCelticGrid(optionKey, optionConfig);
            case 'plain_grid':
                return this.renderPlainGrid(optionKey, optionConfig);
            default:
                return '<p>Unsupported option type: ' + optionConfig.type + '</p>';
        }
    }
    
    renderSingleSelect(optionKey, optionConfig) {
        // Skip single select logic for plain_grid - it has its own rendering
        if (optionConfig.type === 'plain_grid') {
            return this.renderPlainGrid(optionKey, optionConfig);
        }
        
        // Use compact dropdown for options with many choices (like ring size)
        if (optionConfig.options.length > 12 && !optionConfig.options[0].description) {
            return this.renderCompactDropdown(optionKey, optionConfig);
        }
        
        let html = '<div class="option-grid">';
        
        // Get gender filter if this option has gender-based filtering
        const genderFilter = this.getGenderFilter(optionConfig);
        
        for (const option of optionConfig.options) {
            // Filter options based on available_for property
            if (option.available_for && !option.available_for.includes(this.category)) {
                continue; // Skip this option for current category
            }
            
            // Filter options based on available_genders
            if (genderFilter && option.available_genders) {
                if (!option.available_genders.includes(genderFilter)) {
                    continue; // Skip this option for current gender
                }
            }
            
            const isSelected = this.selections[optionKey] === option.id;
            const priceModifier = option.price_modifier || 0;
            const priceDisplay = priceModifier > 0 ? `+$${this.formatPrice(priceModifier)}` : 
                                priceModifier < 0 ? `-$${this.formatPrice(Math.abs(priceModifier))}` : '';
            
            // Get visual indicator (emoji, color, etc.)
            const visualIndicator = this.getVisualIndicator(optionKey, option);
            
            html += `
                <div class="option-item compact ${isSelected ? 'selected' : ''}" 
                     data-option-key="${optionKey}" 
                     data-option-value="${option.id}"
                     data-price-modifier="${priceModifier}"
                     title="${option.name}${option.description ? ' - ' + option.description : ''}">
                    ${visualIndicator ? `<div class="option-visual">${visualIndicator}</div>` : `<div class="option-name-compact">${option.name}</div>`}
                    ${priceDisplay ? `<div class="option-price-compact">${priceDisplay}</div>` : ''}
                    <div class="selection-check">✓</div>
                </div>
            `;
            
            // Render sub-options if they exist
            if (option.sub_options && isSelected) {
                html += this.renderSubOptions(optionKey, option);
            }
        }
        
        html += '</div>';
        return html;
    }
    
    renderCompactDropdown(optionKey, optionConfig) {
        // Apply smart defaults for ring size based on product ID
        let defaultValue = optionConfig.default;
        if (optionKey === 'size' && this.productId) {
            const productIdUpper = this.productId.toUpperCase();
            if (productIdUpper.endsWith('L')) {
                defaultValue = '6.5'; // Ladies
            } else if (productIdUpper.endsWith('M')) {
                defaultValue = '10'; // Mens
            }
        }
        
        const selected = this.selections[optionKey] || defaultValue || '';
        
        // Get gender filter if this option has gender-based filtering
        const genderFilter = this.getGenderFilter(optionConfig);
        
        let html = `
            <select class="compact-dropdown" data-option-key="${optionKey}">
                <option value="">Select ${optionConfig.label}...</option>
        `;
        
        for (const option of optionConfig.options) {
            // Filter options based on available_for property
            if (option.available_for && !option.available_for.includes(this.category)) {
                continue; // Skip this option for current category
            }
            
            // Filter options based on available_genders
            if (genderFilter && option.available_genders) {
                if (!option.available_genders.includes(genderFilter)) {
                    continue; // Skip this option for current gender
                }
            }
            
            const priceModifier = option.price_modifier || 0;
            const priceDisplay = priceModifier > 0 ? ` (+$${this.formatPrice(priceModifier)})` : 
                                priceModifier < 0 ? ` (-$${this.formatPrice(Math.abs(priceModifier))})` : '';
            const isSelected = selected === option.id ? 'selected' : '';
            
            html += `
                <option value="${option.id}" 
                        data-price-modifier="${priceModifier}"
                        ${isSelected}>
                    ${option.name}${priceDisplay}
                </option>
            `;
        }
        
        html += '</select>';
        return html;
    }
    
    getVisualIndicator(optionKey, option) {
        // Karat level icons
        const karatIcons = {
            '950_silver': '🥈',
            '10k': '10K',
            '14k': '14K',
            '18k': '18K'
        };
        
        // Metal color swatches
        const metalColors = {
            'yellow': '#FFD700',
            'white': '#E5E4E2',
            'rose': '#B76E79'
        };
        
        // Finish patterns
        const finishIcons = {
            'polished': '✨',
            'brushed': '≡',
            'satin': '~',
            'hammered': '⚒',
            'florentine': '⬚',
            'antiqued': '◐',
            'two_tone': '◧'
        };
        
        // Profile shapes
        const profileIcons = {
            'flat': '▬',
            'comfort_fit': '⌢',
            'domed': '⬤',
            'beveled': '◇',
            'stepped': '⊟'
        };
        
        // Celtic patterns
        const patternIcons = {
            'trinity': '☘️',
            'lovers': '💚',
            'claddagh': '👑',
            'spiral': '🌀'
        };
        
        // Stone types
        const stoneIcons = {
            'diamond': '💎',
            'sapphire': '🔷',
            'ruby': '🔴',
            'emerald': '💚'
        };
        
        // Cultural styles
        const culturalIcons = {
            'irish': '☘️',
            'scottish': '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
            'norse': '⚔️'
        };
        
        // Two-tone accent metals
        const twoToneIcons = {
            'none': '⚪',
            'white_gold_accent': '⚪🟡',
            'yellow_gold_accent': '🟡⚪',
            'rose_gold_accent': '🌹⚪',
            'silver_accent': '⚪🥈'
        };
        
        // Premium finish options
        const premiumFinishIcons = {
            'none': '✨',
            'antiqued': '◐',
            'two_tone': '◧'
        };
        
        if (optionKey === 'karat_level' && karatIcons[option.id]) {
            return `<span class="karat-icon">${karatIcons[option.id]}</span>`;
        } else if (optionKey === 'metal_color' && metalColors[option.id]) {
            return `<div class="metal-swatch" style="background: ${metalColors[option.id]}"></div>`;
        } else if (optionKey === 'finish' && finishIcons[option.id]) {
            return `<span class="finish-icon">${finishIcons[option.id]}</span>`;
        } else if (optionKey === 'profile' && profileIcons[option.id]) {
            return `<span class="profile-icon">${profileIcons[option.id]}</span>`;
        } else if (optionKey === 'pattern' && patternIcons[option.id]) {
            return `<span class="pattern-icon">${patternIcons[option.id]}</span>`;
        } else if (optionKey === 'stone_type' && stoneIcons[option.id]) {
            return `<span class="stone-icon">${stoneIcons[option.id]}</span>`;
        } else if (optionKey === 'cultural_style' && culturalIcons[option.id]) {
            return `<span class="cultural-icon">${culturalIcons[option.id]}</span>`;
        } else if (optionKey === 'two_tone_accent' && twoToneIcons[option.id]) {
            return `<span class="two-tone-icon">${twoToneIcons[option.id]}</span>`;
        } else if (optionKey === 'premium_finish' && premiumFinishIcons[option.id]) {
            return `<span class="premium-finish-icon">${premiumFinishIcons[option.id]}</span>`;
            return `<span class="cultural-icon">${culturalIcons[option.id]}</span>`;
        } else if (optionKey === 'width') {
            // Width bars
            const widthNum = parseInt(option.id);
            const barWidth = Math.min(widthNum * 10, 80);
            return `<div class="width-bar" style="width: ${barWidth}px; height: 4px; background: #333;"></div>`;
        }
        
        return '';
    }
    
    renderSubOptions(parentKey, parentOption) {
        if (!parentOption.sub_options) return '';
        
        const subKey = `${parentKey}_sub`;
        let html = `
            <div class="sub-options">
                <label class="sub-options-label">${parentOption.sub_options.label}</label>
                <select class="sub-options-select" data-parent-key="${parentKey}" data-sub-key="${subKey}">
                    <option value="">Select...</option>
        `;
        
        for (const choice of parentOption.sub_options.choices) {
            const selected = this.selections[subKey] === choice.id ? 'selected' : '';
            html += `<option value="${choice.id}" ${selected}>${choice.name}</option>`;
        }
        
        html += `
                </select>
            </div>
        `;
        
        return html;
    }
    
    renderTextInput(optionKey, optionConfig) {
        const value = this.selections[optionKey] || '';
        const priceModifier = optionConfig.price_modifier || 0;
        
        let html = `
            <div class="text-input-wrapper">
                <input type="text" 
                       class="text-input" 
                       data-option-key="${optionKey}"
                       placeholder="Enter text..."
                       maxlength="${optionConfig.max_characters || 50}"
                       value="${value}">
                <div class="input-meta">
                    <span class="char-count">0/${optionConfig.max_characters || 50}</span>
                    ${priceModifier > 0 ? `<span class="input-price">+$${this.formatPrice(priceModifier)}</span>` : ''}
                </div>
        `;
        
        // Font options for engraving - use simple text display
        if (optionConfig.font_options) {
            html += `
                <div class="font-options">
                    <label>Font Style:</label>
                    <div class="font-grid">
            `;
            
            for (const font of optionConfig.font_options) {
                const fontKey = `${optionKey}_font`;
                const isSelected = this.selections[fontKey] === font.id;
                
                html += `
                    <div class="font-option ${isSelected ? 'selected' : ''}"
                         data-option-key="${fontKey}"
                         data-option-value="${font.id}">
                        <div class="font-name">${font.name}</div>
                        <div class="font-description">${font.description}</div>
                    </div>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        return html;
    }
    
    renderCheckbox(optionKey, optionConfig) {
        const isChecked = this.selections[optionKey] || false;
        const priceModifier = optionConfig.price_modifier || 0;
        const discount = optionConfig.discount_percentage || 0;
        
        // Handle dynamic matching set labels
        let displayLabel = optionConfig.label;
        let helpText = optionConfig.help_text || '';
        let thumbnailHtml = '';
        
        if (optionKey === 'matching_set' && optionConfig.dynamic_label && this.productId) {
            const matchingProduct = this.getMatchingProductId(this.productId);
            if (matchingProduct.found) {
                displayLabel = `Add Matching ${matchingProduct.gender} Ring`;
                helpText = `${matchingProduct.id} (${matchingProduct.gender})`;
                
                // Generate thumbnail path
                const thumbnailPath = this.getProductThumbnailPath(matchingProduct.id);
                thumbnailHtml = `<img src="${thumbnailPath}" alt="${matchingProduct.id}" class="matching-set-thumbnail" onerror="this.style.display='none'">`;
            }
        }
        
        let matchingSetOptions = '';
        if (optionKey === 'matching_set' && isChecked && this.productId) {
            const matchingOptions = this.getMatchingSetOptions(this.productId);
            if (matchingOptions.length > 0) {
                const selectedMatchingWidth = this.selections['matching_set_width'] || matchingOptions[0].width;
                
                matchingSetOptions = `
                    <div class="matching-set-options" style="margin-top: 10px; padding-left: 20px;">
                        <label class="matching-width-label">Choose Width for Matching Ring:</label>
                        <select class="matching-width-select" data-option-key="matching_set_width">
                            ${matchingOptions.map(option => `
                                <option value="${option.width}" 
                                        data-product-id="${option.productId}"
                                        data-price-modifier="${option.price_modifier}"
                                        ${selectedMatchingWidth === option.width ? 'selected' : ''}>
                                    ${option.width} ${option.price_modifier > 0 ? `(+$${this.formatPrice(option.price_modifier)})` : ''}
                                </option>
                            `).join('')}
                        </select>
                        <div class="matching-set-details">
                            <small>Pattern: ${matchingOptions[0].pattern || 'Same as selected'}</small>
                        </div>
                    </div>
                `;
            }
        }
        
        return `
            <div class="checkbox-wrapper">
                ${thumbnailHtml}
                <label class="checkbox-label">
                    <input type="checkbox" 
                           class="checkbox-input"
                           data-option-key="${optionKey}"
                           ${isChecked ? 'checked' : ''}>
                    <span class="checkbox-text">
                        ${displayLabel}
                    </span>
                </label>
                ${helpText ? `<div class="help-text-small">${helpText}</div>` : ''}
                ${priceModifier > 0 ? `<div class="checkbox-price">+$${this.formatPrice(priceModifier)}</div>` : ''}
                ${matchingSetOptions}
            </div>
        `;
    }
    
    renderTwoToneSelector(optionKey, optionConfig) {
        // Check if two-tone is enabled
        const isTwoToneEnabled = this.selections[optionKey + '_enabled'] || false;
        const primaryMetal = this.selections[optionKey + '_primary'] || optionConfig.options.primary_metal.options[0].id;
        const secondaryMetal = this.selections[optionKey + '_secondary'] || optionConfig.options.secondary_metal.options[1].id; // Default to different metal
        
        const priceModifier = isTwoToneEnabled ? (optionConfig.options.enabled.price_modifier || 0) : 0;
        
        let html = `
            <div class="two-tone-wrapper">
                <div class="two-tone-enable-section">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               class="two-tone-enable-checkbox"
                               data-option-key="${optionKey}"
                               ${isTwoToneEnabled ? 'checked' : ''}>
                        <span class="checkbox-text">
                            ${optionConfig.options.enabled.name}
                        </span>
                    </label>
                    <div class="help-text-small">${optionConfig.options.enabled.description}</div>
                    ${priceModifier > 0 ? `<div class="checkbox-price">+$${this.formatPrice(priceModifier)}</div>` : ''}
                </div>
                
                <div class="two-tone-metal-selectors" style="display: ${isTwoToneEnabled ? 'block' : 'none'}; margin-top: 15px; padding-left: 20px;">
                    <div class="two-tone-metal-section">
                        <label class="two-tone-metal-label">${optionConfig.options.primary_metal.label}</label>
                        <div class="help-text-small">${optionConfig.options.primary_metal.help_text}</div>
                        <div class="option-grid" style="margin-top: 10px;">
                            ${optionConfig.options.primary_metal.options.map(option => {
                                const isSelected = primaryMetal === option.id;
                                const visualIndicator = this.getMetalVisualIndicator(option.id);
                                return `
                                    <div class="option-item compact ${isSelected ? 'selected' : ''}" 
                                         data-option-key="${optionKey}_primary" 
                                         data-option-value="${option.id}"
                                         data-price-modifier="0"
                                         title="${option.name}">
                                        <div class="option-visual">${visualIndicator}</div>
                                        <div class="option-name-compact">${option.name.replace(' Gold', '')}</div>
                                        <div class="selection-check">✓</div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                    
                    <div class="two-tone-metal-section" style="margin-top: 15px;">
                        <label class="two-tone-metal-label">${optionConfig.options.secondary_metal.label}</label>
                        <div class="help-text-small">${optionConfig.options.secondary_metal.help_text}</div>
                        <div class="option-grid" style="margin-top: 10px;">
                            ${optionConfig.options.secondary_metal.options.map(option => {
                                const isSelected = secondaryMetal === option.id;
                                const visualIndicator = this.getMetalVisualIndicator(option.id);
                                return `
                                    <div class="option-item compact ${isSelected ? 'selected' : ''}" 
                                         data-option-key="${optionKey}_secondary" 
                                         data-option-value="${option.id}"
                                         data-price-modifier="0"
                                         title="${option.name}">
                                        <div class="option-visual">${visualIndicator}</div>
                                        <div class="option-name-compact">${option.name.replace(' Gold', '')}</div>
                                        <div class="selection-check">✓</div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                    
                    <div class="two-tone-preview" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        <small><strong>Two-Tone Preview:</strong> ${this.getMetalName(primaryMetal)} pattern on ${this.getMetalName(secondaryMetal)} background</small>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }
    
    getMetalVisualIndicator(metalId) {
        const indicators = {
            'yellow': '🟡',
            'white': '⚪',
            'rose': '🌸'
        };
        return indicators[metalId] || '⚫';
    }
    
    getMetalName(metalId) {
        const names = {
            'yellow': 'Yellow Gold',
            'white': 'White Gold',
            'rose': 'Rose Gold'
        };
        return names[metalId] || metalId;
    }
    
    renderPlainBandsGrid(optionKey, optionConfig, gridData, selectedValue, genderFilter) {
        // Find which row contains the current product ID
        let currentRow = null;
        let currentProductBase = null;
        
        if (this.productId) {
            const baseProductId = this.productId.replace(/[ML]$/, ''); // Remove M/L suffix
            console.log('Looking for plain band row matching product:', baseProductId);
            
            for (const row of gridData.rows) {
                for (const option of row.options) {
                    if (option.available === false) continue;
                    
                    // Check if this option's product_id matches our base
                    const optionBase = option.product_id.replace(/[ML]$/, '');
                    
                    // Match on various patterns: exact match, starts with, or contains
                    if (optionBase === baseProductId || 
                        baseProductId === option.product_id ||
                        baseProductId.startsWith(optionBase) ||
                        optionBase.startsWith(baseProductId)) {
                        currentRow = row;
                        currentProductBase = optionBase;
                        console.log('Found matching row:', row.pattern, 'for product:', baseProductId);
                        break;
                    }
                }
                if (currentRow) break;
            }
        }
        
        // If no match found, use first available row
        if (!currentRow) {
            currentRow = gridData.rows[0];
            console.log('No matching row found, using default:', currentRow.pattern);
        }
        
        let html = `
            <div class="plain-bands-grid-container">
                <div class="plain-pattern-section">
                    <div class="pattern-header">
                        <h4>${currentRow.pattern}</h4>
                        <p class="pattern-description">${currentRow.description}</p>
                    </div>
                    <div class="width-grid">
        `;
        
        currentRow.options.forEach((option, index) => {
            const isAvailable = option.available !== false;
            
            // Skip if option doesn't match gender filter
            if (genderFilter && option.available_genders) {
                if (!option.available_genders.includes(genderFilter)) {
                    console.log('Skipping option', option.width, 'because gender', genderFilter, 'not in', option.available_genders);
                    return; // Skip this option
                }
            }
            
            if (isAvailable) {
                const productIdWithSuffix = `${option.product_id}${genderFilter || 'M'}`;
                const optionId = `width_${productIdWithSuffix}`;
                const isSelected = selectedValue === optionId;
                const genderLabel = genderFilter === 'L' ? 'Ladies' : 'Mens';
                const defaultSize = genderFilter === 'L' ? '7' : '10';
                
                html += `
                    <div class="width-option ${isSelected ? 'selected' : ''}">
                        <input type="radio" 
                               id="${optionId}"
                               name="${optionKey}"
                               value="${optionId}"
                               data-product-id="${productIdWithSuffix}"
                               data-price-modifier="${option.price_modifier || 0}"
                               data-width="${option.width}"
                               data-pattern="${currentRow.pattern}"
                               data-gender="${genderFilter || 'M'}"
                               data-default-size="${defaultSize}"
                               ${isSelected ? 'checked' : ''}
                               class="plain-width-radio">
                        <label for="${optionId}" class="plain-width-label">
                            <div class="width-size">${option.width}</div>
                            ${option.price_modifier > 0 ? `<div class="price-modifier">+$${option.price_modifier}</div>` : ''}
                            ${option.note ? `<div class="width-note">${option.note}</div>` : ''}
                        </label>
                    </div>
                `;
            }
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }
    
    getGenderFilter(optionConfig) {
        // Check if this option has filter_options_by configuration
        if (!optionConfig.filter_options_by) {
            return null;
        }
        
        const filterField = optionConfig.filter_options_by.field;
        const filterRule = optionConfig.filter_options_by.rule;
        
        // Get the selected value from the filter field
        const filterValue = this.selections[filterField];
        if (!filterValue) {
            return null;
        }
        
        // Return the filter value (e.g., 'M' or 'L')
        return filterValue;
    }
    
    renderPlainGrid(optionKey, optionConfig) {
        const selectedValue = this.selections[optionKey] || '';
        
        // Detect the current product's style from productId - this is FIXED, not changeable
        let currentStyle = 'tiffany';
        let seriesName = 'Standard Tiffany';
        let validProduct = false;
        
        if (this.productId) {
            const baseId = this.productId.replace(/[ML]$/, ''); // Remove M/L suffix
            
            console.log('Detecting series for productId:', this.productId, 'baseId:', baseId);
            
            // Detect style based on product ID patterns
            if (baseId.includes('R')) {
                if (baseId.includes('T00')) {
                    currentStyle = 'rectangular_comfort';
                    seriesName = 'Rectangular Comfort Curve';
                } else if (baseId.startsWith('S')) {
                    currentStyle = 'rectangular_lightweight';
                    seriesName = 'Rectangular Lightweight';
                } else {
                    currentStyle = 'rectangular';
                    seriesName = 'Rectangular Standard';
                }
            } else if (baseId.includes('T18')) {
                currentStyle = 'tiffany_comfort';
                seriesName = 'Tiffany Comfort Curve';
            } else if (baseId.includes('T') && !baseId.includes('T18')) {
                currentStyle = 'premium';
                seriesName = 'Premium Series';
            } else if (baseId.match(/^\d{4}$/)) {
                currentStyle = 'tiffany_lightweight';
                seriesName = 'Tiffany Lightweight';
            } else {
                currentStyle = 'tiffany';
                seriesName = 'Standard Tiffany';
            }
            
            // Verify this product exists in the detected series
            const gridLayout = this.config.options?.style_and_width?.grid_layout;
            if (gridLayout && gridLayout.series) {
                const styleToSeriesId = {
                    'tiffany': 'standard_tiffany',
                    'tiffany_lightweight': 'tiffany_lightweight',
                    'rectangular': 'rectangular_standard',
                    'rectangular_lightweight': 'rectangular_lightweight',
                    'tiffany_comfort': 'tiffany_comfort_curve',
                    'rectangular_comfort': 'rectangular_comfort_curve',
                    'premium': 'premium_series'
                };
                
                const seriesId = styleToSeriesId[currentStyle];
                const seriesData = gridLayout.series.find(s => s.id === seriesId);
                
                if (seriesData && seriesData.products) {
                    // Check if this baseId exists in the series
                    validProduct = seriesData.products.some(p => p.base_id === baseId);
                    
                    if (!validProduct) {
                        console.warn(`Product ${baseId} not found in ${seriesName}. Available products:`, 
                            seriesData.products.map(p => p.base_id).join(', '));
                    }
                }
            }
        }
        
        let html = `
            <div class="plain-grid-container">
                <div class="plain-series-info">
                    <div class="series-badge">${seriesName}</div>
                    <p class="series-note">Band style is fixed - select width to see different products in this series</p>
                    ${!validProduct && this.productId ? `<p class="error-note" style="color: red; font-size: 0.9em;">⚠️ Product ${this.productId} not found in this series</p>` : ''}
                </div>
                
                <div class="plain-width-selector">
                    <h4>Select Width:</h4>
                    <div class="width-options-grid" id="width-options-grid">
                        <!-- Width options will be populated with thumbnails -->
                    </div>
                </div>
            </div>
        `;
        
        // After rendering, load the current style's width options
        setTimeout(() => {
            this.loadPlainWidthOptions(currentStyle);
        }, 0);
        
        return html;
    }
    
    renderPlainAsGrid(optionKey, optionConfig) {
        const selectedValue = this.selections[optionKey] || '';
        
        // Get width options from the configurator JSON grid_layout
        const gridLayout = optionConfig?.grid_layout;
        if (!gridLayout || !gridLayout.series) {
            console.error('Grid layout not found in plain configurator config');
            return '<p>Configuration error</p>';
        }
        
        // Auto-detect current series based on product ID
        let currentSeries = gridLayout.series[0]; // Default to first series
        if (this.productId) {
            const baseId = this.productId.replace(/[ML]$/, '');
            for (const series of gridLayout.series) {
                if (series.products.some(p => p.base_id === baseId)) {
                    currentSeries = series;
                    break;
                }
            }
        }

        const currentStyle = this.detectStyleFromProductId(this.productId);
        console.log('renderPlainAsGrid - Current style detected:', currentStyle);
        
        let html = `
            <div class="plain-grid-container">
                <div class="plain-series-info">
                    <div class="series-badge">${currentSeries.name}</div>
                    <p class="series-note">${currentSeries.description}</p>
                </div>
                
                <div class="plain-width-selector">
                    <h4>Select Width:</h4>
                    <div class="width-options-grid" id="width-options-grid">
                        <!-- Width options will be populated with thumbnails -->
                    </div>
                </div>
            </div>
        `;
        
        // After rendering, load the current style's width options
        setTimeout(() => {
            this.loadPlainWidthOptions(currentStyle);
        }, 0);
        
        return html;
    }
    
    detectStyleFromProductId(productId) {
        if (!productId) return 'tiffany';
        
        const baseId = productId.replace(/[ML]$/, '');
        if (baseId.includes('R')) {
            if (baseId.includes('T00')) {
                return 'rectangular_comfort';
            } else if (baseId.startsWith('S')) {
                return 'rectangular_lightweight';
            } else {
                return 'rectangular';
            }
        } else if (baseId.includes('T18')) {
            return 'tiffany_comfort';
        } else if (baseId.includes('T') && !baseId.includes('T18')) {
            return 'premium';
        } else if (baseId.match(/^\d{4}$/)) {
            return 'tiffany_lightweight';
        }
        return 'tiffany';
    }

    renderCelticGrid(optionKey, optionConfig) {
        const selectedValue = this.selections[optionKey] || '';
        
        // Get gender filter if configured
        const genderFilter = this.getGenderFilter(optionConfig);
        
        // For Celtic/Cultural: Load pattern data from XML via API
        if (this.category === 'celtic' || this.category === 'cultural') {
            return this.renderCelticPatternGrid(optionKey, optionConfig, selectedValue, genderFilter);
        }
        
        // For plain bands: show all patterns/styles as selectable
        const gridData = optionConfig.grid_layout;
        return this.renderPlainBandsGrid(optionKey, optionConfig, gridData, selectedValue, genderFilter);
    }
    
    renderCelticPatternGrid(optionKey, optionConfig, selectedValue, genderFilter) {
        // Create container that will be populated by API call
        const containerId = `celtic-pattern-container-${Math.random().toString(36).substr(2, 9)}`;
        
        let html = `
            <div id="${containerId}" class="celtic-grid-container">
                <div class="loading-message">
                    <div class="loading-spinner"></div>
                    <p>Loading pattern data...</p>
                </div>
            </div>
        `;
        
        // After rendering, load the pattern data from XML via API
        setTimeout(() => {
            this.loadCelticPatternFromXML(containerId, optionKey, selectedValue, genderFilter);
        }, 50);
        
        return html;
    }
    
    async loadCelticPatternFromXML(containerId, optionKey, selectedValue, genderFilter) {
        try {
            const container = document.getElementById(containerId);
            if (!container) {
                console.error('Celtic pattern container not found:', containerId);
                return;
            }
            
            if (!this.productId) {
                container.innerHTML = '<p class="error-message">No product ID specified</p>';
                return;
            }
            
            // Call API to get pattern data
            const response = await fetch(`/api/get_celtic_pattern_data.php?product_id=${this.productId}&category=${this.category}`);
            const data = await response.json();
            
            if (!data.success) {
                container.innerHTML = `<p class="error-message">Error loading pattern: ${data.error}</p>`;
                return;
            }
            
            const pattern = data.pattern;
            const baseProductId = data.base_product_id;
            
            // Render the pattern-specific width grid
            let html = `
                <div class="celtic-pattern-display">
                    <div class="pattern-info">
                        <div class="pattern-thumbnail">
                            <img src="images/jewelry-placeholder.jpg" 
                                 alt="${pattern.name}" 
                                 class="pattern-image"
                                 id="celtic-pattern-image-${containerId}"
                                 onerror="this.style.display='none'">
                        </div>
                        <div class="pattern-details">
                            <div class="pattern-name">${pattern.name}</div>
                            <div class="pattern-symbol">${pattern.symbol || ''}</div>
                            <div class="pattern-description">${pattern.description || ''}</div>
                            <div class="pattern-heritage">${pattern.heritage || ''}</div>
                        </div>
                    </div>
                </div>
                <div class="celtic-width-options">
                    <h4>Select Width:</h4>
                    <div class="width-grid">
            `;
            
            // Render width options for this specific pattern
            pattern.width_options.forEach((option, index) => {
                // Add both M and L versions for each width
                ['M', 'L'].forEach(suffix => {
                    const productIdWithSuffix = `${option.product_id}${suffix}`;
                    const optionId = `width_${productIdWithSuffix}`;
                    const isSelected = selectedValue === optionId;
                    const genderLabel = suffix === 'M' ? 'Mens' : 'Ladies';
                    const defaultSize = suffix === 'M' ? '10' : '6.5';
                    
                    // Skip if gender filter doesn't match
                    if (genderFilter && genderFilter !== suffix) {
                        return;
                    }
                    
                    html += `
                        <div class="width-option">
                            <input type="radio" 
                                   id="${optionId}"
                                   name="${optionKey}"
                                   value="${optionId}"
                                   data-product-id="${productIdWithSuffix}"
                                   data-price-modifier="${option.price_modifier}"
                                   data-width="${option.width}"
                                   data-pattern="${pattern.name}"
                                   data-gender="${suffix}"
                                   data-default-size="${defaultSize}"
                                   ${isSelected ? 'checked' : ''}
                                   class="celtic-width-radio">
                            <label for="${optionId}" class="celtic-width-label">
                                <div class="width-thumbnail">
                                    <img src="images/jewelry-placeholder.jpg" 
                                         alt="${productIdWithSuffix}" 
                                         class="width-image"
                                         data-product-id="${baseProductId}"
                                         data-suffix="${suffix}"
                                         onerror="this.style.display='none'">
                                </div>
                                <div class="width-size">${option.width}</div>
                                <div class="gender-label">${genderLabel}</div>
                                <div class="product-id">${productIdWithSuffix}</div>
                                <div class="size-hint">Size ${defaultSize}</div>
                                ${option.price_modifier > 0 ? `<div class="price-modifier">+$${option.price_modifier}</div>` : ''}
                            </label>
                        </div>
                    `;
                });
            });
            
            html += `
                    </div>
                </div>
                <div class="celtic-legend">
                    <p><strong>${pattern.name}</strong> - Choose your preferred width and see the corresponding product number.</p>
                </div>
            `;
            
            container.innerHTML = html;
            
            // Attach event listeners to the new radio buttons
            container.querySelectorAll('.celtic-width-radio').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const optionKey = e.target.name;
                    this.handleSelection(optionKey, e.target.value);
                });
            });
            
            // Load thumbnails
            setTimeout(() => {
                console.log('🔄 Loading Celtic pattern thumbnails...');
                this.loadCollectionThumbnails();
                // Load the pattern image
                this.loadCelticPatternImage(pattern, `celtic-pattern-image-${containerId}`);
            }, 50);
            
        } catch (error) {
            console.error('Error loading Celtic pattern data:', error);
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = `<p class="error-message">Failed to load pattern data. Please refresh the page.</p>`;
            }
        }
    }
    
    loadCelticPatternImage(pattern, imageId) {
        const patternImg = document.getElementById(imageId);
        if (patternImg && this.productId) {
            const baseProductId = this.productId.replace(/[ML]$/, '');
            
            // Try to get the best available image for this pattern
            this.getCelticWidthThumbnails(baseProductId).then(thumbnails => {
                // Prefer M first, then L, then placeholder
                if (thumbnails.M && thumbnails.M !== 'images/jewelry-placeholder.jpg') {
                    patternImg.src = thumbnails.M;
                } else if (thumbnails.L && thumbnails.L !== 'images/jewelry-placeholder.jpg') {
                    patternImg.src = thumbnails.L;
                } else {
                    patternImg.src = 'images/jewelry-placeholder.jpg';
                }
            });
        }
    }
    
    renderSummary() {
        let html = `
            <div class="summary-content">
                <h3>Your Configuration</h3>
                <div class="summary-items" id="summary-items">
                    ${this.renderSummaryItems()}
                </div>
                
                ${this.config.production_time ? `
                <div class="production-time">
                    <strong>Production Time:</strong>
                    <div class="time-options">
                        ${this.renderProductionTimeOptions()}
                    </div>
                </div>
                ` : ''}
                
                ${this.config.warranty ? `
                <div class="warranty-info">
                    <svg width="16" height="16" viewBox="0 0 16 16" class="warranty-icon">
                        <path d="M8 0 L2 3 L2 7 C2 11 8 16 8 16 C8 16 14 11 14 7 L14 3 Z" fill="currentColor"/>
                    </svg>
                    <span>${this.config.warranty.included}</span>
                </div>
                ` : ''}
            </div>
        `;
        
        return html;
    }
    
    renderSummaryItems() {
        let html = '<ul class="summary-list">';
        
        // Track pattern and band metals for two-tone display
        let patternMetal = null;
        let bandMetal = null;
        
        for (const [key, value] of Object.entries(this.selections)) {
            if (value && !key.endsWith('_font') && !key.endsWith('_sub') && !key.endsWith('_primary') && !key.endsWith('_secondary') && !key.endsWith('_enabled')) {
                const optionConfig = this.config.options[key];
                if (!optionConfig) continue;
                
                let displayValue = value;
                
                if (optionConfig.type === 'single_select') {
                    const selectedOption = optionConfig.options.find(opt => opt.id === value);
                    displayValue = selectedOption ? selectedOption.name : value;
                    
                    // Track metals for two-tone detection
                    if (key === 'pattern_metal') {
                        patternMetal = value;
                    } else if (key === 'band_metal') {
                        bandMetal = value;
                    }
                } else if (optionConfig.type === 'checkbox') {
                    displayValue = value ? 'Yes' : 'No';
                }
                
                html += `
                    <li>
                        <span class="summary-label">${optionConfig.label}:</span>
                        <span class="summary-value">${displayValue}</span>
                    </li>
                `;
            }
        }
        
        // Add two-tone indicator if metals are different
        if (patternMetal && bandMetal && patternMetal !== bandMetal) {
            html += `
                <li class="two-tone-indicator">
                    <span class="summary-label">Two-Tone Design:</span>
                    <span class="summary-value">+$150</span>
                </li>
            `;
        }
        
        html += '</ul>';
        return html;
    }
    
    renderProductionTimeOptions() {
        // Check if production_time options exist in config
        if (!this.config.production_time || typeof this.config.production_time !== 'object') {
            return ''; // Return empty string if no production time options
        }
        
        let html = '';
        
        for (const [timeKey, timeConfig] of Object.entries(this.config.production_time)) {
            const isSelected = this.selections.production_time === timeKey;
            
            html += `
                <label class="time-option ${isSelected ? 'selected' : ''}">
                    <input type="radio" 
                           name="production_time" 
                           value="${timeKey}"
                           data-option-key="production_time"
                           data-price-modifier="${timeConfig.price_modifier}"
                           ${isSelected ? 'checked' : ''}>
                    <span class="time-label">${timeConfig.label}</span>
                    ${timeConfig.price_modifier > 0 ? `<span class="time-price">+$${this.formatPrice(timeConfig.price_modifier)}</span>` : ''}
                </label>
            `;
        }
        
        return html;
    }
    
    renderActions() {
        return `
            <div class="action-buttons">
                <button class="btn btn-secondary" id="save-configuration">
                    <svg width="16" height="16" viewBox="0 0 16 16">
                        <path d="M2 2 L2 14 L14 14 L14 2 Z M4 0 L12 0 L12 4 L4 4 Z" fill="currentColor"/>
                    </svg>
                    Save Configuration
                </button>
                
                <button class="btn btn-primary" id="add-to-cart">
                    <svg width="16" height="16" viewBox="0 0 16 16">
                        <path d="M0 1 L3 1 L5 11 L15 11 L13 5 L5 5" stroke="currentColor" fill="none" stroke-width="2"/>
                        <circle cx="6" cy="14" r="1" fill="currentColor"/>
                        <circle cx="13" cy="14" r="1" fill="currentColor"/>
                    </svg>
                    Add to Cart - $<span id="cart-price">${this.formatPrice(this.basePrice)}</span>
                </button>
                
                <button class="btn btn-outline" id="request-quote">
                    <svg width="16" height="16" viewBox="0 0 16 16">
                        <rect x="2" y="2" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2"/>
                        <line x1="5" y1="6" x2="11" y2="6" stroke="currentColor" stroke-width="2"/>
                        <line x1="5" y1="9" x2="11" y2="9" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Request Quote
                </button>
            </div>
        `;
    }
    
    attachEventListeners() {
        if (!this.element) {
            console.error('Cannot attach event listeners - no element');
            return;
        }
        
        // Single select options
        this.element.querySelectorAll('.option-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const optionKey = e.currentTarget.dataset.optionKey;
                const optionValue = e.currentTarget.dataset.optionValue;
                this.handleSelection(optionKey, optionValue);
            });
        });
        
        // Compact dropdowns
        this.element.querySelectorAll('.compact-dropdown').forEach(select => {
            select.addEventListener('change', (e) => {
                const optionKey = e.target.dataset.optionKey;
                const optionValue = e.target.value;
                this.handleSelection(optionKey, optionValue);
            });
        });
        
        // Text inputs
        this.element.querySelectorAll('.text-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const optionKey = e.target.dataset.optionKey;
                const value = e.target.value;
                this.handleTextInput(optionKey, value, e.target);
            });
        });
        
        // Checkboxes
        this.element.querySelectorAll('.checkbox-input').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const optionKey = e.target.dataset.optionKey;
                const isChecked = e.target.checked;
                this.handleCheckbox(optionKey, isChecked);
            });
        });
        
        // Matching set width selections
        this.element.querySelectorAll('.matching-width-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const matchingType = e.target.dataset.matchingType;
                const width = e.target.value;
                this.handleMatchingSetWidth(matchingType, width);
            });
        });
        
        // Font options
        this.element.querySelectorAll('.font-option').forEach(item => {
            item.addEventListener('click', (e) => {
                const optionKey = e.currentTarget.dataset.optionKey;
                const optionValue = e.currentTarget.dataset.optionValue;
                this.handleSelection(optionKey, optionValue);
            });
        });
        
        // Production time
        this.element.querySelectorAll('input[name="production_time"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleSelection('production_time', e.target.value);
            });
        });
        
        // Celtic grid selections
        this.element.querySelectorAll('.celtic-width-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const optionKey = e.target.name;
                this.handleSelection(optionKey, e.target.value);
            });
        });
        
        // Plain bands grid selections
        this.element.querySelectorAll('.plain-width-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const optionKey = e.target.name;
                this.handleSelection(optionKey, e.target.value);
            });
        });
        
        // Sub-options selects
        this.element.querySelectorAll('.sub-options-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const subKey = e.target.dataset.subKey;
                this.handleSelection(subKey, e.target.value);
            });
        });
        
        // Action buttons
        const saveBtn = document.getElementById('save-configuration');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveConfiguration());
        }
        
        const cartBtn = document.getElementById('add-to-cart');
        if (cartBtn) {
            cartBtn.addEventListener('click', () => this.addToCart());
        }
        
        const quoteBtn = document.getElementById('request-quote');
        if (quoteBtn) {
            quoteBtn.addEventListener('click', () => this.requestQuote());
        }
        
        // Window resize handler for responsive accordion behavior
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }
    
    handleResize() {
        // Re-evaluate accordion mode on window resize
        const shouldAccordion = this.shouldUseAccordion();
        const optionGroups = this.element.querySelectorAll('.option-group');
        
        optionGroups.forEach(group => {
            const header = group.querySelector('.option-header');
            const content = group.querySelector('.accordion-content');
            
            if (shouldAccordion) {
                // Switch to accordion mode
                group.classList.add('accordion');
                header.classList.add('accordion-header');
                if (!header.onclick) {
                    header.onclick = () => this.toggleAccordion(header);
                }
                // Collapse all by default when switching to accordion
                if (!content.classList.contains('collapsed')) {
                    content.classList.add('collapsed');
                    header.classList.remove('active');
                    content.style.maxHeight = '0px';
                }
            } else {
                // Switch to normal mode
                group.classList.remove('accordion');
                header.classList.remove('accordion-header', 'active');
                content.classList.remove('collapsed');
                content.style.maxHeight = 'none';
                header.onclick = null;
            }
        });
        
        // Re-initialize accordions if needed
        if (shouldAccordion) {
            this.initializeAccordions();
        }
    }
    
    handleSelection(optionKey, optionValue) {
        this.selections[optionKey] = optionValue;
        
        // Mark that user has interacted with this option
        this.userInteracted[optionKey] = true;
        
        // Auto-select metal color when gold karat is chosen
        if (optionKey === 'karat_level' && ['10k', '14k', '18k'].includes(optionValue)) {
            if (!this.selections['metal_color']) {
                this.selections['metal_color'] = 'yellow'; // Set default
            }
        }
        
        // Clear metal color if silver is selected
        if (optionKey === 'karat_level' && optionValue === '950_silver') {
            delete this.selections['metal_color'];
        }
        
        // If gender changes, reload the current style's width options
        if (optionKey === 'gender') {
            // Detect current style from productId
            let currentStyle = 'tiffany';
            if (this.productId) {
                const baseId = this.productId.replace(/[ML]$/, '');
                if (baseId.includes('R')) {
                    if (baseId.includes('T00')) {
                        currentStyle = 'rectangular_comfort';
                    } else if (baseId.startsWith('S')) {
                        currentStyle = 'rectangular_lightweight';
                    } else {
                        currentStyle = 'rectangular';
                    }
                } else if (baseId.includes('T18')) {
                    currentStyle = 'tiffany_comfort';
                } else if (baseId.includes('T') && !baseId.includes('T18')) {
                    currentStyle = 'premium';
                } else if (baseId.match(/^\d{4}$/)) {
                    currentStyle = 'tiffany_lightweight';
                }
            }
            
            // Update product ID based on new gender and currently selected width
            const currentWidthSelection = this.selections['style_and_width'];
            if (currentWidthSelection) {
                const widthRadio = this.element.querySelector(`input[value="${currentWidthSelection}"]`);
                if (widthRadio) {
                    const newGender = optionValue;
                    const productIdM = widthRadio.dataset.productIdM;
                    const productIdL = widthRadio.dataset.productIdL;
                    const newProductId = newGender === 'M' ? productIdM : productIdL;
                    
                    if (newProductId && newProductId !== this.productId) {
                        console.log('Gender change: Product ID changing from', this.productId, 'to', newProductId);
                        this.productId = newProductId;
                        
                        // Update the data attribute on the configurator container
                        this.element.setAttribute('data-product-id', newProductId);
                        
                        this.loadProductImages(newProductId);
                        
                        // Update price and summary to reflect new product ID
                        this.calculatePrice();
                        this.updateSummary();
                    }
                }
            }
            
            this.loadPlainWidthOptions(currentStyle);
        }
        
        // Check if this selection affects conditional visibility of other options OR filtering
        let needsRerender = false;
        for (const [key, config] of Object.entries(this.config.options)) {
            if (config.visible_when && config.visible_when[optionKey]) {
                needsRerender = true;
                break;
            }
            // Check if this selection affects filtering of other options
            if (config.filter_options_by && config.filter_options_by.field === optionKey) {
                needsRerender = true;
                break;
            }
        }
        
        if (needsRerender) {
            // Store which accordion was open
            const openAccordion = this.element.querySelector('.accordion-header.active');
            const openOptionKey = openAccordion ? openAccordion.closest('.option-group')?.dataset.option : null;
            
            // Full re-render to show/hide conditional options or apply filters
            this.render();
            this.attachEventListeners();
            this.calculatePrice();
            this.updateSummary();
            
            // Update all badges after re-render
            setTimeout(() => {
                this.updateAllRequiredBadges();
            }, 100);
            
            // Re-open the accordion that was open
            if (openOptionKey) {
                const accordionToOpen = this.element.querySelector(`[data-option="${openOptionKey}"] .accordion-header`);
                if (accordionToOpen && !accordionToOpen.classList.contains('active')) {
                    this.toggleAccordion(accordionToOpen);
                }
            }
            return;
        }
        
        // Update UI
        const optionGroup = this.element.querySelector(`[data-option="${optionKey}"]`);
        if (optionGroup) {
            optionGroup.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            const selectedItem = optionGroup.querySelector(`[data-option-value="${optionValue}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
            }
        }
        
        // Font options use different selector
        const fontOptions = this.element.querySelectorAll(`[data-option-key="${optionKey}"]`);
        fontOptions.forEach(item => {
            item.classList.remove('selected');
            if (item.dataset.optionValue === optionValue) {
                item.classList.add('selected');
            }
        });
        
        // Re-render if option has sub-options
        const optionConfig = this.config.options[optionKey.replace('_sub', '').replace('_font', '')];
        if (optionConfig && optionConfig.options) {
            const selectedOption = optionConfig.options.find(opt => opt.id === optionValue);
            if (selectedOption && selectedOption.sub_options) {
                this.render();
                this.attachEventListeners();
            }
        }
        
        // Update the required badge with the selected value
        this.updateRequiredBadge(optionKey, optionValue);
        
        this.calculatePrice();
        this.updateSummary();
    }
    
    loadPlainWidthOptions(style) {
        console.log('=== loadPlainWidthOptions called ===');
        console.log('Style:', style);
        
        const widthContainer = this.element.querySelector('#width-options-grid');
        console.log('Width container found:', !!widthContainer);
        if (!widthContainer) return;
        
        // Get the selected gender (M or L)
        const selectedGender = this.selections['gender'] || 'M';
        console.log('Selected gender:', selectedGender);
        
        // Get width options from the configurator JSON grid_layout
        const gridLayout = this.config.options?.style_and_width?.grid_layout;
        if (!gridLayout || !gridLayout.series) {
            console.error('Grid layout not found in configurator config');
            return;
        }
        
        // Map style names to series IDs
        const styleToSeriesId = {
            'tiffany': 'standard_tiffany',
            'tiffany_lightweight': 'tiffany_lightweight',
            'rectangular': 'rectangular_standard',
            'rectangular_lightweight': 'rectangular_lightweight',
            'tiffany_comfort': 'tiffany_comfort_curve',
            'rectangular_comfort': 'rectangular_comfort_curve',
            'premium': 'premium_series'
        };
        
        // Find the matching series
        const seriesId = styleToSeriesId[style];
        const seriesData = gridLayout.series.find(s => s.id === seriesId);
        
        if (!seriesData || !seriesData.products) {
            console.error('Series data not found for style:', style);
            return;
        }
        
        const widths = seriesData.products;
        
        let html = '';
        widths.forEach((product, index) => {
            // Determine which product ID to use based on gender
            let productId = null;
            let note = '';
            
            // Check if ladies_only flag is set
            if (product.ladies_only) {
                if (selectedGender === 'L') {
                    productId = product.product_id_l;
                    note = 'Ladies only';
                } else {
                    return; // Skip this product for men's
                }
            } else {
                // Use the appropriate product ID based on gender
                productId = selectedGender === 'M' ? product.product_id_m : product.product_id_l;
                console.log('Product:', product.base_id, 'Gender:', selectedGender, 'ProductId:', productId, 'M-ID:', product.product_id_m, 'L-ID:', product.product_id_l);
            }
            
            // Skip if product ID doesn't exist for this gender
            if (!productId) {
                return;
            }
            
            // Add size restriction note if present
            if (seriesData.size_restrictions) {
                note = note ? `${note}, ${seriesData.size_restrictions}` : seriesData.size_restrictions;
            }
            
            const optionId = `width_${product.base_id}`;
            const noteHtml = note ? `<div class="width-note">${note}</div>` : '';
            const priceModifier = product.price_modifier || 0;
            const priceHtml = priceModifier !== 0 ? `<div class="price-modifier">${priceModifier > 0 ? '+' : ''}$${priceModifier}</div>` : '';
            
            html += `
                <div class="width-option">
                    <input type="radio" 
                           id="${optionId}"
                           name="style_and_width"
                           value="${optionId}"
                           data-product-id-m="${product.product_id_m || ''}"
                           data-product-id-l="${product.product_id_l || ''}"
                           data-base-id="${product.base_id}"
                           data-width="${product.width}"
                           data-style="${style}"
                           data-price-modifier="${priceModifier}"
                           class="plain-width-radio">
                    <label for="${optionId}" class="plain-width-label">
                        <div class="width-icon">⚪</div>
                        <div class="width-size">${product.width}</div>
                        <div class="product-id-display">${productId}</div>
                        ${priceHtml}
                        ${noteHtml}
                    </label>
                </div>
            `;
        });
        
        console.log('Generated HTML length:', html.length);
        console.log('First 200 chars of HTML:', html.substring(0, 200));
        
        widthContainer.innerHTML = html || '<p class="loading-message">No widths available for this style</p>';
        
        console.log('HTML set in container. Container children count:', widthContainer.children.length);
        console.log('Container computed style - display:', window.getComputedStyle(widthContainer).display);
        console.log('Container computed style - grid-template-columns:', window.getComputedStyle(widthContainer).gridTemplateColumns);
        
        // Reattach event listeners for the new radio buttons
        widthContainer.querySelectorAll('.plain-width-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleSelection('style_and_width', e.target.value);
                
                // Determine the correct product ID based on current gender selection
                const currentGender = this.selections['gender'] || 'M';
                const productIdM = e.target.dataset.productIdM;
                const productIdL = e.target.dataset.productIdL;
                const newProductId = currentGender === 'M' ? productIdM : productIdL;
                
                if (newProductId && newProductId !== this.productId) {
                    console.log('Product ID changing from', this.productId, 'to', newProductId, 'for gender', currentGender);
                    this.productId = newProductId;
                    
                    // Update the data attribute on the configurator container
                    this.element.setAttribute('data-product-id', newProductId);
                    
                    // Update the main product image gallery
                    this.loadProductImages(newProductId);
                    
                    // Update price
                    this.updatePrice();
                }
            });
        });
    }
    
    loadProductImages(productId) {
        console.log('Loading images for product:', productId);
        
        // Find the main image container
        const mainImageContainer = document.querySelector('.product-main-image');
        const thumbnailContainer = document.querySelector('.product-thumbnails');
        
        if (!mainImageContainer) {
            console.log('Main image container not found');
            return;
        }
        
        // Build image paths
        const basePath = 'bands_php/images/Bands/';
        const mainImagePath = `${basePath}${productId}.png`;
        const alt1Path = `${basePath}${productId}_alt1.png`;
        const alt2Path = `${basePath}${productId}_alt2.png`;
        
        // Update main image
        const mainImg = mainImageContainer.querySelector('img');
        if (mainImg) {
            mainImg.src = mainImagePath;
            mainImg.alt = productId;
            mainImg.onerror = function() {
                this.src = 'images/jewelry-placeholder.jpg';
            };
        }
        
        // Update thumbnails if container exists
        if (thumbnailContainer) {
            thumbnailContainer.innerHTML = '';
            
            // Add main image thumbnail
            const thumb1 = document.createElement('img');
            thumb1.src = mainImagePath;
            thumb1.alt = productId;
            thumb1.className = 'thumbnail active';
            thumb1.onclick = () => {
                if (mainImg) mainImg.src = mainImagePath;
                thumbnailContainer.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb1.classList.add('active');
            };
            thumb1.onerror = function() { this.style.display = 'none'; };
            thumbnailContainer.appendChild(thumb1);
            
            // Try to add alt1 image
            const thumb2 = document.createElement('img');
            thumb2.src = alt1Path;
            thumb2.alt = `${productId} alt 1`;
            thumb2.className = 'thumbnail';
            thumb2.onclick = () => {
                if (mainImg) mainImg.src = alt1Path;
                thumbnailContainer.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb2.classList.add('active');
            };
            thumb2.onerror = function() { this.style.display = 'none'; };
            thumbnailContainer.appendChild(thumb2);
            
            // Try to add alt2 image
            const thumb3 = document.createElement('img');
            thumb3.src = alt2Path;
            thumb3.alt = `${productId} alt 2`;
            thumb3.className = 'thumbnail';
            thumb3.onclick = () => {
                if (mainImg) mainImg.src = alt2Path;
                thumbnailContainer.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb3.classList.add('active');
            };
            thumb3.onerror = function() { this.style.display = 'none'; };
            thumbnailContainer.appendChild(thumb3);
        }
    }
    
    handleTextInput(optionKey, value, inputElement) {
        this.selections[optionKey] = value;
        
        // Update character count
        const wrapper = inputElement.closest('.text-input-wrapper');
        if (wrapper) {
            const charCount = wrapper.querySelector('.char-count');
            if (charCount) {
                const max = inputElement.maxLength;
                charCount.textContent = `${value.length}/${max}`;
            }
        }
        
        this.calculatePrice();
        this.updateSummary();
    }
    
    handleCheckbox(optionKey, isChecked) {
        this.selections[optionKey] = isChecked;
        this.calculatePrice();
        this.updateSummary();
        
        // Re-render options to show/hide conditional sections
        this.render();
    }
    
    handleTwoToneEnable(optionKey, isEnabled) {
        // Store the enabled state
        this.selections[optionKey + '_enabled'] = isEnabled;
        
        // Mark as user-interacted
        this.userInteracted[optionKey + '_enabled'] = true;
        
        // Show/hide the metal selectors
        const wrapper = this.element.querySelector(`[data-option-key="${optionKey}"]`)?.closest('.two-tone-wrapper');
        if (wrapper) {
            const selectors = wrapper.querySelector('.two-tone-metal-selectors');
            if (selectors) {
                selectors.style.display = isEnabled ? 'block' : 'none';
            }
        }
        
        // Recalculate price
        this.calculatePrice();
        this.updateSummary();
        
        console.log(`Two-tone ${isEnabled ? 'enabled' : 'disabled'} for ${optionKey}`);
    }
    
    handleMatchingSetWidth(matchingType, width) {
        // Store the matching set width selection
        const selectionKey = `${matchingType}_width`;
        this.selections[selectionKey] = width;
        
        // Recalculate pricing with the specific width selection
        this.calculatePrice();
        this.updateSummary();
        
        // Log for debugging
        console.log(`Matching set width selected: ${matchingType} = ${width}`);
    }
    
    calculatePrice() {
        let price = this.basePrice;
        let discounts = [];
        
        for (const [key, value] of Object.entries(this.selections)) {
            if (!value) continue;
            
            // Handle production time separately
            if (key === 'production_time') {
                const timeConfig = this.config.production_time[value];
                if (timeConfig && timeConfig.price_modifier) {
                    price += timeConfig.price_modifier;
                }
                continue;
            }
            
            const optionConfig = this.config.options[key];
            if (!optionConfig) continue;
            
            // Handle different option types
            if (optionConfig.type === 'single_select') {
                const selectedOption = optionConfig.options.find(opt => opt.id === value);
                if (selectedOption) {
                    if (selectedOption.price_modifier) {
                        price += selectedOption.price_modifier;
                    }
                    if (selectedOption.price_modifier_percentage) {
                        price += (this.basePrice * selectedOption.price_modifier_percentage / 100);
                    }
                }
            } else if (optionConfig.type === 'text_input' && value.length > 0) {
                if (optionConfig.price_modifier) {
                    price += optionConfig.price_modifier;
                }
            } else if (optionConfig.type === 'checkbox' && value === true) {
                if (optionConfig.price_modifier) {
                    price += optionConfig.price_modifier;
                }
                
                // Handle matching set logic
                if (key === 'matching_set') {
                    const matchingConfig = optionConfig.matching_options;
                    if (matchingConfig && matchingConfig.price_calculation) {
                        if (matchingConfig.price_calculation.method === 'add_second_ring') {
                            // Add the price of a second ring (same base price + current modifiers)
                            price += this.basePrice;
                            // Add current option modifiers to the second ring too
                            for (const [selKey, selValue] of Object.entries(this.selections)) {
                                if (selKey !== 'matching_set' && selKey !== 'size') { // Skip matching_set itself and size
                                    const selOptionConfig = this.config.options[selKey];
                                    if (selOptionConfig && selOptionConfig.type === 'single_select') {
                                        const selectedOption = selOptionConfig.options.find(opt => opt.id === selValue);
                                        if (selectedOption && selectedOption.price_modifier) {
                                            price += selectedOption.price_modifier;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Fallback to old logic
                        price += this.basePrice;
                    }
                }
                
                if (optionConfig.discount_percentage) {
                    discounts.push({
                        key: key,
                        percentage: optionConfig.discount_percentage
                    });
                }
            }
        }
        
        // Check for two-tone pricing (pattern_metal != band_metal)
        const patternMetal = this.selections['pattern_metal'];
        const bandMetal = this.selections['band_metal'];
        if (patternMetal && bandMetal && patternMetal !== bandMetal) {
            // Check if band_metal has two_tone_price defined
            const bandMetalConfig = this.config.options['band_metal'];
            if (bandMetalConfig && bandMetalConfig.two_tone_price) {
                price += bandMetalConfig.two_tone_price;
                console.log(`Two-tone detected (${patternMetal} pattern / ${bandMetal} band), adding $${bandMetalConfig.two_tone_price}`);
            }
        }
        
        // Apply discounts
        for (const discount of discounts) {
            price = price * (1 - discount.percentage / 100);
        }
        
        this.currentPrice = Math.round(price * 100) / 100;
        this.updatePriceDisplay();
    }
    
    updatePriceDisplay() {
        const priceElement = document.getElementById('configurator-price');
        if (priceElement) {
            // Animate price change
            priceElement.classList.add('price-updating');
            setTimeout(() => {
                priceElement.textContent = `$${this.formatPrice(this.currentPrice)}`;
                priceElement.classList.remove('price-updating');
            }, 150);
        }
        
        const cartPriceElement = document.getElementById('cart-price');
        if (cartPriceElement) {
            cartPriceElement.textContent = this.formatPrice(this.currentPrice);
        }
    }
    
    updateSummary() {
        const summaryItems = document.getElementById('summary-items');
        if (summaryItems) {
            summaryItems.innerHTML = this.renderSummaryItems();
        }
    }
    
    updateRequiredBadge(optionKey, optionValue) {
        const optionGroup = this.element.querySelector(`[data-option="${optionKey}"]`);
        if (!optionGroup) {
            console.log('❌ Badge update: No option group found for', optionKey);
            console.log('Available option groups:', Array.from(this.element.querySelectorAll('[data-option]')).map(el => el.dataset.option));
            return;
        }
        
        const badge = optionGroup.querySelector('.required-badge');
        if (!badge) {
            console.log('❌ Badge update: No badge found for', optionKey);
            console.log('Option group HTML:', optionGroup.outerHTML.substring(0, 200));
            return;
        }
        
        console.log('✅ Badge update: Found badge for', optionKey, 'current text:', badge.textContent);
        
        // If no value, show "Required"
        if (!optionValue) {
            badge.textContent = 'Required';
            return;
        }
        
        // Get the config for this option
        const optionConfig = this.config.options[optionKey];
        if (!optionConfig) {
            console.log('Badge update: No config found for', optionKey);
            return;
        }
        
        // Get the display name for the selected value
        let displayText = 'Required';
        
        if (optionConfig.type === 'single_select') {
            const selectedOption = optionConfig.options.find(opt => opt.id === optionValue);
            if (selectedOption) {
                displayText = selectedOption.name;
            }
        } else if (optionConfig.type === 'celtic_grid' || optionKey === 'plain_pattern_width' || optionKey === 'width') {
            // For width selections, extract the width from the value
            // Format is typically "width_300M" or similar
            console.log('Badge update: Processing width selection', optionValue);
            
            if (typeof optionValue === 'string' && optionValue.startsWith('width_')) {
                const productIdPart = optionValue.replace('width_', '');
                console.log('Badge update: Extracted product ID part:', productIdPart);
                
                // Try to find the width from the grid options
                const gridConfig = optionConfig.celtic_override || optionConfig;
                if (gridConfig.grid_layout && gridConfig.grid_layout.rows) {
                    searchLoop:
                    for (const row of gridConfig.grid_layout.rows) {
                        for (const option of row.options) {
                            if (option.available === false) continue;
                            
                            // Check multiple patterns for matching
                            const genderSuffix = this.selections.gender_selection || 'M';
                            const withSuffix = `${option.product_id}${genderSuffix}`;
                            const withoutSuffix = option.product_id;
                            
                            console.log('Badge update: Checking option', option.product_id, 'against', productIdPart);
                            
                            if (productIdPart === withSuffix || productIdPart === withoutSuffix) {
                                displayText = option.width;
                                console.log('Badge update: Found match! Width is', displayText);
                                break searchLoop;
                            }
                        }
                    }
                }
            }
        } else if (optionConfig.type === 'checkbox') {
            displayText = optionValue ? 'Yes' : 'No';
        } else if (optionConfig.type === 'text_input') {
            displayText = optionValue || 'Required';
        }
        
        console.log('Badge update: Final display text for', optionKey, '=', displayText);
        
        // Update the badge text
        if (badge.textContent !== displayText) {
            // Skip animation if badge currently says "Required" (initial state) or on mobile
            const isMobile = window.innerWidth <= 768;
            if (badge.textContent === 'Required' || isMobile) {
                badge.textContent = displayText;
                console.log('✅ Badge updated immediately to:', displayText);
            } else {
                badge.style.transition = 'opacity 0.2s';
                badge.style.opacity = '0';
                
                setTimeout(() => {
                    badge.textContent = displayText;
                    badge.style.opacity = '1';
                    console.log('✅ Badge animated to:', displayText);
                }, 200);
            }
        } else {
            console.log('ℹ️ Badge already has correct text:', displayText);
        }
    }
    
    updateAllRequiredBadges() {
        // Update all required badges with current selections
        console.log('🔄 Updating all required badges. Current selections:', this.selections);
        for (const [optionKey, optionValue] of Object.entries(this.selections)) {
            if (optionValue) {
                console.log(`  → Updating badge for ${optionKey} = ${optionValue}`);
                this.updateRequiredBadge(optionKey, optionValue);
            }
        }
        console.log('✅ All badge updates complete');
    }
    
    formatPrice(price) {
        return price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    saveConfiguration() {
        const configData = {
            collection: this.collection,
            selections: this.selections,
            price: this.currentPrice,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('saved_configuration', JSON.stringify(configData));
        
        this.showNotification('Configuration saved successfully!', 'success');
    }
    
    addToCart() {
        const cartData = {
            type: 'configured_product',
            collection: this.collection,
            name: this.config.name,
            selections: this.selections,
            price: this.currentPrice,
            basePrice: this.basePrice
        };
        
        // Send to cart system
        fetch('/cart_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(cartData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Added to cart!', 'success');
                // Update cart badge
                this.updateCartBadge();
            } else {
                this.showNotification('Failed to add to cart. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        });
    }
    
    requestQuote() {
        const quoteData = {
            collection: this.collection,
            selections: this.selections,
            price: this.currentPrice
        };
        
        // Store in session and open contact modal
        sessionStorage.setItem('quote_request', JSON.stringify(quoteData));
        
        // Open contact modal with pre-filled message
        const message = `I would like to request a quote for a custom ${this.config.name} with the following specifications:\n\n${this.getQuoteDetails()}`;
        
        if (typeof openContactModalWithTracking === 'function') {
            openContactModalWithTracking('Product Configurator', 'Quote Request', message);
        } else {
            this.showNotification('Please contact us for a quote.', 'info');
        }
    }
    
    getQuoteDetails() {
        let details = '';
        
        for (const [key, value] of Object.entries(this.selections)) {
            if (value && !key.endsWith('_font') && !key.endsWith('_sub')) {
                const optionConfig = this.config.options[key];
                if (!optionConfig) continue;
                
                let displayValue = value;
                
                if (optionConfig.type === 'single_select') {
                    const selectedOption = optionConfig.options.find(opt => opt.id === value);
                    displayValue = selectedOption ? selectedOption.name : value;
                }
                
                details += `${optionConfig.label}: ${displayValue}\n`;
            }
        }
        
        details += `\nEstimated Price: $${this.formatPrice(this.currentPrice)} CAD`;
        
        return details;
    }
    
    updateCartBadge() {
        // Update cart count in navigation
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            const currentCount = parseInt(cartBadge.textContent) || 0;
            cartBadge.textContent = currentCount + 1;
            cartBadge.style.display = 'block';
        }
    }
    
    shouldUseAccordion() {
        // Use accordion on mobile or when there are many options
        return window.innerWidth <= 768 || Object.keys(this.config.options).length > 6;
    }
    
    toggleAccordion(headerElement) {
        const header = headerElement;
        const content = header.nextElementSibling;
        const isActive = header.classList.contains('active');
        
        // Close all other accordions first
        const allHeaders = this.element.querySelectorAll('.accordion-header');
        const allContents = this.element.querySelectorAll('.accordion-content');
        
        allHeaders.forEach(h => h.classList.remove('active'));
        allContents.forEach(c => {
            c.classList.add('collapsed');
            c.style.maxHeight = '0px';
        });
        
        // Toggle current accordion
        if (!isActive) {
            header.classList.add('active');
            content.classList.remove('collapsed');
            content.style.maxHeight = content.scrollHeight + 'px';
        }
    }
    
    initializeAccordions() {
        // Auto-open the first accordion on mobile, or required options
        if (this.shouldUseAccordion()) {
            const headers = this.element.querySelectorAll('.accordion-header');
            if (headers.length > 0) {
                // Open first required option, or first option if none required
                let firstToOpen = headers[0];
                for (const header of headers) {
                    const optionGroup = header.closest('.option-group');
                    const optionKey = optionGroup.dataset.option;
                    if (this.config.options[optionKey]?.required) {
                        firstToOpen = header;
                        break;
                    }
                }
                this.toggleAccordion(firstToOpen);
            }
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `configurator-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    showError(message) {
        if (this.element) {
            this.element.innerHTML = `
                <div class="configurator-error">
                    <svg width="48" height="48" viewBox="0 0 48 48">
                        <circle cx="24" cy="24" r="22" stroke="currentColor" fill="none" stroke-width="2"/>
                        <line x1="24" y1="12" x2="24" y2="28" stroke="currentColor" stroke-width="3"/>
                        <circle cx="24" cy="36" r="2" fill="currentColor"/>
                    </svg>
                    <h3>Oops! Something went wrong</h3>
                    <p>${message}</p>
                    <button onclick="location.reload()" class="btn btn-primary">Reload Page</button>
                </div>
            `;
        } else {
            console.error('Cannot show error - no element:', message);
        }
    }
}

// Initialize configurator when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== CONFIGURATOR INITIALIZATION ===');
    console.log('DOM loaded, looking for configurator container...');
    
    // Try both class and ID selectors
    const configuratorContainer = document.querySelector('.product-configurator') || 
                                 document.querySelector('#product-configurator');
    
    if (configuratorContainer) {
        console.log('✅ Found configurator container:', configuratorContainer);
        console.log('Container attributes:', configuratorContainer.outerHTML.substring(0, 200) + '...');
        
        try {
            window.productConfigurator = new ProductConfigurator(configuratorContainer);
            console.log('✅ ProductConfigurator created');
            window.productConfigurator.init();
            console.log('✅ ProductConfigurator.init() called');
        } catch (error) {
            console.error('❌ Failed to create or initialize ProductConfigurator:', error);
        }
    } else {
        console.log('❌ No configurator container found');
        console.log('Available elements with "configurator" in class/id:');
        document.querySelectorAll('[class*="configurator"], [id*="configurator"]').forEach(el => {
            console.log('  -', el.tagName, el.className, el.id);
        });
    }
    console.log('=== END CONFIGURATOR INITIALIZATION ===');
});
