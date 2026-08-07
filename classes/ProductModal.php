<?php
/**
 * ProductModal - Reusable Product Detail Modal System
 * 
 * Provides a unified modal interface for all product categories:
 * - Celtic Bands, Plain Bands, Fancy Bands, Cultural Bands
 * - Supports configurators, image galleries, PDF fallbacks
 * - Works across all *_php directories and index page
 * 
 * @version 1.0
 * @author Cadman Manufacturing
 */

// Load global site configuration
require_once __DIR__ . '/../includes/site_config.php';

class ProductModal {
    private $productId;
    private $productData;
    private $configuratorData;
    
    /**
     * Constructor
     * @param string $productId - Product ID to load
     */
    public function __construct($productId = null) {
        if ($productId) {
            $this->productId = $productId;
            $this->loadProductData();
        }
    }
    
    /**
     * Render the modal HTML structure
     * Call this once per page to include the modal container
     */
    public static function renderModalContainer() {
        ?>
        <!-- Product Detail Modal Container -->
        <div id="product-modal" class="product-modal-overlay" style="display: none;">
            <div class="product-modal-container">
                <div class="product-modal-content">
                    <button class="product-modal-close" onclick="ProductModal.close()" aria-label="Close product details">
                        <span>&times;</span>
                    </button>
                    
                    <div class="product-modal-header">
                        <h2 id="product-modal-title">Product Details</h2>
                        <p id="product-modal-subtitle">Loading product information...</p>
                    </div>
                    
                    <div class="product-modal-body">
                        <div class="product-loading" id="product-loading">
                            <div class="spinner"></div>
                            <p>Loading product details...</p>
                        </div>
                        
                        <div class="product-content" id="product-content" style="display: none;">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        // Include the CSS and JavaScript
        self::renderModalStyles();
        self::renderModalScripts();
    }
    
    /**
     * Load product data from database
     */
    private function loadProductData() {
        try {
            // Use existing get_product_modal_data.php endpoint
            $dataUrl = '/get_product_modal_data.php?product_id=' . urlencode($this->productId);
            
            // For server-side usage, we'll make a local request
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            $response = file_get_contents($dataUrl, false, $context);
            $this->productData = json_decode($response, true);
            
        } catch (Exception $e) {
            $this->productData = ['error' => 'Failed to load product data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get product display priority for fallback logic
     * @param array $product - Product data
     * @return string - 'modal_with_images', 'modal_with_pdf', 'basic_info'
     */
    public static function getDisplayPriority($product) {
        // Priority 1: Has images - full modal with configurator
        if (!empty($product['has_images']) && !empty($product['image_files'])) {
            return 'modal_with_images';
        }
        
        // Priority 2: No images but has PDF - modal with PDF option
        if (!empty($product['pdf_file'])) {
            return 'modal_with_pdf';
        }
        
        // Priority 3: Basic info only
        return 'basic_info';
    }
    
    /**
     * Check if product supports configurator
     * @param array $product - Product data
     * @return bool
     */
    public static function hasConfigurator($product) {
        $configuratorCategories = ['celtic_bands', 'plain_bands', 'fancy_bands', 'cultural_bands'];
        return in_array($product['category'] ?? '', $configuratorCategories);
    }
    
    /**
     * Get configurator file path for category
     * @param string $category - Product category
     * @return string|null - Path to configurator JSON file
     */
    public static function getConfiguratorPath($category) {
        $configuratorMap = [
            'celtic_bands' => 'bands_php/celtic_configurator.json',
            'plain_bands' => 'bands_php/plain_configurator.json',
            'fancy_bands' => 'bands_php/fancy_configurator.json',
            'cultural_bands' => 'bands_php/cultural_configurator.json'
        ];
        
        return $configuratorMap[$category] ?? null;
    }
    
    /**
     * Render modal styles
     */
    private static function renderModalStyles() {
        ?>
        <style>
        /* Product Modal Styles */
        .product-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .product-modal-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            position: relative;
        }

        .product-modal-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
            position: relative;
            max-height: 95vh;
            overflow-y: auto;
            animation: productModalSlideIn 0.4s ease-out;
        }

        @keyframes productModalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .product-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10001;
            font-size: 24px;
            color: #666;
        }

        .product-modal-close:hover {
            background: rgba(255, 0, 0, 0.1);
            transform: scale(1.1);
            color: #ff4444;
        }

        .product-modal-header {
            padding: 30px 30px 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .product-modal-body {
            padding: 30px;
            min-height: 300px;
        }

        .product-loading {
            text-align: center;
            padding: 60px 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #8B4513;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Related Products Styles */
        .related-products-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .related-products-section h4 {
            color: #8B4513;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .width-alternatives {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .width-group {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .width-badge {
            min-width: 70px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            background: #f5f5f5;
            color: #666;
        }
        
        .width-badge.current-width {
            background: #8B4513;
            color: white;
        }
        
        .width-products, .pattern-siblings {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .product-button {
            padding: 6px 12px;
            border-radius: 15px;
            border: 1px solid #ddd;
            background: #f8f8f8;
            color: #333;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .product-button:hover {
            background: #e8e8e8;
            border-color: #8B4513;
            transform: translateY(-1px);
        }
        
        .product-button.current-product {
            background: #8B4513;
            color: white;
            border-color: #8B4513;
            cursor: default;
        }
        
        .product-button.current-product:hover {
            transform: none;
        }
        
        .product-button .width-info {
            font-size: 10px;
            opacity: 0.7;
            margin-left: 4px;
        }
        
        /* Hide price information in configurator for now */
        .configurator-options select option {
            /* This will be handled by JavaScript since CSS can't modify option text */
        }
        
        .price-modifier-hidden {
            display: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .product-modal-container {
                max-width: 95%;
            }
            
            .product-modal-header {
                padding: 20px 15px 15px;
            }
            
            .product-modal-body {
                padding: 20px 15px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Render modal JavaScript
     */
    private static function renderModalScripts() {
        ?>
        <script>
        /**
         * ProductModal JavaScript API
         * Provides client-side modal functionality
         */
        class ProductModal {
            
            /**
             * Open product modal
             * @param {string} productId - Product ID to display
             * @param {Object} options - Additional options
             */
            static open(productId, options = {}) {
                const modal = document.getElementById('product-modal');
                const loading = document.getElementById('product-loading');
                const content = document.getElementById('product-content');
                const title = document.getElementById('product-modal-title');
                const subtitle = document.getElementById('product-modal-subtitle');
                
                if (!modal) {
                    console.error('ProductModal container not found. Call ProductModal.renderModalContainer() first.');
                    return;
                }
                
                // Reset modal state
                loading.style.display = 'block';
                content.style.display = 'none';
                title.textContent = 'Product Details';
                subtitle.textContent = 'Loading product information...';
                
                // Show modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Try the product ID as-is first. Only use the M-suffix as a fallback
                // for Celtic-style base IDs (4 bare digits) that aren't in catalog_products
                // themselves. Appending M unconditionally caused 4-digit family ring IDs
                // (e.g. 1208) to be looked up as "1208M", which could match stale rows in
                // the jewelry_items fallback table with the wrong category.
                const mFallback = /^\d{4}$/.test(productId) ? productId + 'M' : null;
                this.loadProductData(productId, options, mFallback);
            }
            
            /**
             * Close modal
             */
            static close() {
                const modal = document.getElementById('product-modal');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            }
            
            /**
             * Load product data via AJAX
             * @param {string} productId 
             * @param {Object} options 
             * @param {string} originalProductId - Original product ID for fallback
             */
            static async loadProductData(productId, options = {}, mFallback = null) {
                try {
                    console.log('ProductModal: fetching data for', productId);
                    const response = await fetch(`/get_product_modal_data.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${encodeURIComponent(productId)}`
                    });
                    console.log('ProductModal: response status', response.status);
                    const data = await response.json();
                    console.log('ProductModal: data.success=', data.success, 'images=', data.product?.images?.length, 'base_price=', data.product?.base_price);
                    
                    if (!data.success && mFallback && mFallback !== productId) {
                        console.log('Product not found with original ID, trying M-suffix fallback:', mFallback);
                        return this.loadProductData(mFallback, options, null);
                    }
                    
                    if (!data.success) {
                        this.showError(data.error || 'Failed to load product data');
                        return;
                    }

                    data.product.show_pricing = !!(data.site_config && data.site_config.show_pricing);
                    data.product.quote_only = data.product.show_pricing
                        && (data.product.base_price === null || data.product.base_price === undefined);
                    
                    this.renderProductContent(data.product);
                    
                } catch (error) {
                    console.error('ProductModal fetch error:', error);
                    this.showError('Failed to load product information. Please try again.');
                }
            }
            
            /**
             * Render product content in modal
             * @param {Object} productData 
             */
            static renderProductContent(productData) {
                const loading = document.getElementById('product-loading');
                const content = document.getElementById('product-content');
                const title = document.getElementById('product-modal-title');
                const subtitle = document.getElementById('product-modal-subtitle');
                const categoryKey = String((productData && productData.category) || '').trim().toLowerCase();
                const isPlainBand = categoryKey === 'plain_bands';
                
                // Update header
                title.textContent = `${productData.product_id} - ${productData.product_name || 'Product Details'}`;
                subtitle.textContent = productData.category ? productData.category.replace('_', ' ').toUpperCase() : '';
                
                // Determine display type based on data availability
                let contentHtml = '';
                
                if (isPlainBand) {
                    // Keep plain bands on a single, modern modal path.
                    contentHtml = this.renderFullModal(productData);
                } else if (productData.images && productData.images.length > 0) {
                    // Has images - render full modal with images and configurator
                    contentHtml = this.renderFullModal(productData);
                } else if (productData.page_reference && productData.pdf_file) {
                    // No images but has PDF - render modal with PDF option
                    contentHtml = this.renderPdfModal(productData);
                } else {
                    // Basic info only
                    contentHtml = this.renderBasicModal(productData);
                }
                
                content.innerHTML = contentHtml;
                
                // Show content, hide loading
                loading.style.display = 'none';
                content.style.display = 'block';
                
                // Initialize functionality based on what was rendered
                if (productData.images && productData.images.length > 0) {
                    this.setupProductImages(productData.images);
                } else if (isPlainBand) {
                    const mainImage = document.getElementById('product-main-image');
                    if (mainImage) {
                        mainImage.src = '/assets/placeholders/pdf_available.svg';
                        mainImage.alt = (productData.product_name || productData.product_id || 'Product') + ' (catalog image)';
                    }
                    const navigation = document.getElementById('image-navigation');
                    const thumbnails = document.getElementById('image-thumbnails');
                    if (navigation) navigation.style.display = 'none';
                    if (thumbnails) thumbnails.innerHTML = '';
                }
                
                if (isPlainBand || (productData.has_configurator && productData.configurator_options)) {
                    this.setupConfigurator(productData);
                }
            }
            
            /**
             * Render full modal with images and configurator
             */
            static renderFullModal(productData) {
                return `
                    <div class="catalog-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
                        <!-- Product images section -->
                        <div class="product-images">
                            <div class="main-image-container">
                                <img id="product-main-image" src="" alt="Product Image" class="main-image">
                                <div class="image-navigation" id="image-navigation" style="display: none;">
                                    <button class="prev-image" onclick="ProductModal.previousImage()">❮</button>
                                    <button class="next-image" onclick="ProductModal.nextImage()">❯</button>
                                </div>
                            </div>
                            <div class="image-thumbnails" id="image-thumbnails"></div>
                        </div>
                        
                        <!-- Product information section -->
                        <div class="product-info">
                            <div class="basic-info">
                                <h3>${productData.product_name || productData.product_id}</h3>
                                <div class="product-details">
                                    <div class="detail-row">
                                        <span class="label">Product ID:</span>
                                        <span id="dynamic-product-id" class="value">${productData.product_id}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">Category:</span>
                                        <span class="value">${productData.category}</span>
                                    </div>
                                    ${productData.pattern ? `<div class="detail-row">
                                        <span class="label">Pattern:</span>
                                        <span class="value">${productData.pattern}</span>
                                    </div>` : ''}
                                    ${productData.width_mm ? `<div class="detail-row">
                                        <span class="label">Width:</span>
                                        <span class="value">${productData.width_mm}mm</span>
                                    </div>` : ''}
                                </div>
                            </div>
                            
                            <!-- Configurator section -->
                            <div id="configurator-section" class="configurator-section">
                                <h4>Customize Your ${productData.product_name}</h4>
                                <div class="configurator-options" id="configurator-options">
                                    <!-- Dynamic configurator options will be inserted here -->
                                </div>
                                ${this.renderPricingBlock(productData)}
                            </div>
                            
                            ${this.renderRelatedProducts(productData)}
                            
                            <!-- Action buttons -->
                            <div class="modal-actions">
                                <button class="btn btn-primary" onclick="ProductModal.requestQuote('${productData.product_id}')">
                                    Request Quote
                                </button>
                                <button class="btn btn-secondary" onclick="ProductModal.addToCart('${productData.product_id}')">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            static renderPricingBlock(productData) {
                if (productData.base_price !== null && productData.base_price !== undefined) {
                    return `
                    <div class="price-section" id="modal-price-section">
                        <div class="price-display" style="display:grid;gap:6px;">
                            <span class="price-label">Suggested Retail:</span>
                            <span class="price-value" id="total-price" data-base-price="${productData.base_price}">$${productData.base_price.toFixed(2)}</span>
                            <span id="selected-metal-type" style="font-size:.85em;color:#666;">Metal Type: .950 Silver</span>
                        </div>
                    </div>
                    `;
                }

                if (productData.quote_only) {
                    return `
                    <div class="price-section" id="modal-price-section">
                        <div class="price-display" style="display:grid;gap:10px;justify-items:start;">
                            <span class="price-label" style="font-weight:700;color:#8B4513;">Pricing available by quote for this item.</span>
                            <button class="btn btn-primary" onclick="ProductModal.requestQuote('${productData.product_id}')">Call for a Quote</button>
                        </div>
                    </div>
                    `;
                }

                return '';
            }
            
            /**
             * Get display priority for product
             * @param {Object} product 
             * @returns {string}
             */
            static getDisplayPriority(product) {
                if (product.has_images && product.image_files && product.image_files !== 'no images found') {
                    return 'modal_with_images';
                }
                if (product.pdf_file) {
                    return 'modal_with_pdf';
                }
                return 'basic_info';
            }
            
            /**
             * Check if product has configurator
             * @param {Object} product 
             * @returns {boolean}
             */
            static hasConfigurator(product) {
                const configuratorCategories = ['celtic_bands', 'plain_bands', 'fancy_bands', 'cultural_bands'];
                return configuratorCategories.includes(product.category);
            }
            
            /**
             * Render modal with images and full configurator
             * @param {Object} product 
             * @returns {string}
             */
            static renderImageModal(product) {
                // Use existing catalog_detail_modal.php logic here
                return `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
                        <div class="product-images">
                            ${this.renderProductImages(product)}
                        </div>
                        <div class="product-info">
                            ${this.renderProductInfo(product)}
                            ${this.hasConfigurator(product) ? '<div id="configurator-container"></div>' : ''}
                        </div>
                    </div>
                `;
            }
            
            /**
             * Render modal with PDF option
             * @param {Object} product 
             * @returns {string}
             */
            static renderPdfModal(product) {
                return `
                    <div class="product-info-single">
                        ${this.renderProductInfo(product)}
                        ${this.hasConfigurator(product) ? '<div id="configurator-container"></div>' : ''}
                        ${this.renderRelatedProducts(product)}
                        <div class="pdf-section" style="margin-top: 20px; text-align: center;">
                            <h4>View Catalog Page</h4>
                            <button onclick="window.open('/Cadman_catalog/${product.pdf_file}', '_blank')" 
                                    class="btn btn-secondary">
                                📄 View ${product.pdf_file}
                            </button>
                        </div>
                    </div>
                `;
            }
            
            /**
             * Render basic info modal
             * @param {Object} product 
             * @returns {string}
             */
            static renderBasicModal(product) {
                return `
                    <div class="product-info-basic">
                        ${this.renderProductInfo(product)}
                        ${this.renderRelatedProducts(product)}
                        <div class="contact-section" style="margin-top: 20px; text-align: center;">
                            <h4>Request Information</h4>
                            <p>Contact us for more details about this product.</p>
                            <button onclick="ProductModal.requestQuote('${product.product_id}')" 
                                    class="btn btn-primary">
                                Request Quote
                            </button>
                        </div>
                    </div>
                `;
            }
            
            /**
             * Render product images section
             * @param {Object} product 
             * @returns {string}
             */
            static renderProductImages(product) {
                if (!product.images || product.images.length === 0) {
                    return '<div class="no-image">No images available</div>';
                }
                
                return `
                    <div class="main-image-container">
                        <img src="${product.images[0]}" alt="${product.product_id}" class="main-image" id="main-product-image">
                        ${product.images.length > 1 ? this.renderImageNavigation() : ''}
                    </div>
                    ${product.images.length > 1 ? this.renderImageThumbnails(product.images) : ''}
                `;
            }
            
            /**
             * Render basic product information
             * @param {Object} product 
             * @returns {string}
             */
            static renderProductInfo(product) {
                return `
                    <div class="basic-info">
                        <h3>${product.product_name || product.product_id}</h3>
                        <div class="product-details">
                            <div class="detail-row">
                                <span class="label">Product ID:</span>
                                <span class="value" id="dynamic-product-id">${product.product_id}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Category:</span>
                                <span class="value">${product.category}</span>
                            </div>
                            ${product.pattern ? `<div class="detail-row">
                                <span class="label">Pattern:</span>
                                <span class="value">${product.pattern}</span>
                            </div>` : ''}
                            ${product.width_mm ? `<div class="detail-row">
                                <span class="label">Width:</span>
                                <span class="value">${product.width_mm}mm</span>
                            </div>` : ''}
                            ${product.stone_min !== undefined && product.stone_min !== null ? `<div class="detail-row">
                                <span class="label">Stone Min:</span>
                                <span class="value">${product.stone_min}</span>
                            </div>` : ''}
                            ${product.stone_max !== undefined && product.stone_max !== null ? `<div class="detail-row">
                                <span class="label">Stone Max:</span>
                                <span class="value">${product.stone_max}</span>
                            </div>` : ''}
                            ${product.stone_size !== undefined && product.stone_size !== null ? `<div class="detail-row">
                                <span class="label">Stone Size (mm):</span>
                                <span class="value">${product.stone_size}</span>
                            </div>` : ''}
                            ${product.base_price !== null && product.base_price !== undefined ? `<div class="detail-row" style="margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
                                <span class="label" style="font-weight:700; color:#8B4513;">Suggested Retail:</span>
                                <span class="value" id="total-price" data-base-price="${product.base_price}" style="font-weight:700; color:#8B4513; font-size:1.1em;">$${product.base_price.toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label" style="font-weight:700; color:#666;">Metal Type:</span>
                                <span class="value" id="selected-metal-type" style="color:#666;">Metal Type: .950 Silver</span>
                            </div>` : product.quote_only ? `<div class="detail-row" style="margin-top:10px; padding-top:10px; border-top:1px solid #eee; display:grid; gap:10px;">
                                <span class="label" style="font-weight:700; color:#8B4513;">Pricing available by quote for this item.</span>
                                <button class="btn btn-primary" onclick="ProductModal.requestQuote('${product.product_id}')">Call for a Quote</button>
                            </div>` : ''}
                        </div>
                    </div>
                `;
            }
            
            /**
             * Render related products section (adaptive to product data)
             * @param {Object} productData 
             * @returns {string}
             */
            static renderRelatedProducts(productData) {
                let relatedHtml = '';
                let hasRelatedProducts = false;
                
                // Series Siblings and Width Alternatives (for products with series data)
                if (productData.series && productData.width_alternatives && productData.width_alternatives.length > 0) {
                    hasRelatedProducts = true;
                    relatedHtml += `
                        <div class="related-products-section">
                            <h4>Available Widths in ${productData.series} Series</h4>
                            <div class="width-alternatives">`;
                    
                    productData.width_alternatives.forEach(widthOption => {
                        const isCurrentWidth = productData.width_mm && widthOption.width.includes(productData.width_mm);
                        const badgeClass = isCurrentWidth ? 'current-width' : 'alternative-width';
                        
                        relatedHtml += `
                            <div class="width-group">
                                <span class="width-badge ${badgeClass}">${widthOption.width}</span>
                                <div class="width-products">`;
                        
                        widthOption.product_ids.forEach(productId => {
                            const isCurrentProduct = productId === productData.product_id;
                            const buttonClass = isCurrentProduct ? 'current-product' : 'alternative-product';
                            const clickHandler = isCurrentProduct ? '' : `onclick="ProductModal.open('${productId}')"`;
                            
                            relatedHtml += `
                                <button class="product-button ${buttonClass}" ${clickHandler} title="View ${productId}">
                                    ${productId}
                                </button>`;
                        });
                        
                        relatedHtml += `
                                </div>
                            </div>`;
                    });
                    
                    relatedHtml += `
                            </div>
                        </div>`;
                }
                
                // Pattern Siblings (for products without series but with pattern matches)
                if (productData.pattern_siblings && productData.pattern_siblings.length > 0) {
                    hasRelatedProducts = true;
                    const sectionTitle = productData.series ? 'Other Related Products' : 'Related Products';
                    
                    relatedHtml += `
                        <div class="related-products-section">
                            <h4>${sectionTitle}</h4>
                            <div class="pattern-siblings">`;
                    
                    productData.pattern_siblings.forEach(sibling => {
                        const widthText = sibling.width_mm ? `${sibling.width_mm}mm` : '';
                        relatedHtml += `
                            <button class="product-button alternative-product" 
                                    onclick="ProductModal.open('${sibling.product_id}')" 
                                    title="View ${sibling.product_id}">
                                ${sibling.product_id}
                                ${widthText ? `<span class="width-info">(${widthText})</span>` : ''}
                            </button>`;
                    });
                    
                    relatedHtml += `
                            </div>
                        </div>`;
                }
                
                // Return empty string if no related products (adaptive)
                return hasRelatedProducts ? relatedHtml : '';
            }
            
            /**
             * Show error message
             * @param {string} message 
             */
            static showError(message) {
                const loading = document.getElementById('product-loading');
                const content = document.getElementById('product-content');
                
                loading.style.display = 'none';
                content.style.display = 'block';
                content.innerHTML = `
                    <div class="error-message" style="text-align: center; padding: 40px; color: #ff4444;">
                        <h3>Unable to Load Product</h3>
                        <p>${message}</p>
                        <button onclick="ProductModal.close()" class="btn btn-secondary">Close</button>
                    </div>
                `;
            }
            
            /**
             * Initialize configurator for supported products
             * @param {Object} product 
             */
            static initializeConfigurator(product) {
                // This will be implemented to load the appropriate configurator
                console.log('Initializing configurator for:', product.category);
            }
            
            /**
             * Request quote functionality with silent product data collection
             * @param {string} productId 
             */
            static requestQuote(productId) {
                console.log('Starting quote request for:', productId);
                
                try {
                    // Get current product data from modal
                    const currentProduct = window.currentProductModalData;
                    
                    // Build detailed product information for silent tracking
                    let productInfo = {
                        productId: productId,
                        name: 'Unknown Product',
                        category: 'General',
                        collection: 'Unknown',
                        configuredOptions: {},
                        timestamp: new Date().toISOString()
                    };
                    
                    // Extract product info from current modal data
                    if (currentProduct) {
                        productInfo.name = currentProduct.name || currentProduct.display_name || productId;
                        productInfo.category = currentProduct.category || 'General';
                        productInfo.collection = currentProduct.collection || 'Unknown';
                        
                        // Get configured options if configurator is present
                        if (window.currentConfiguratorOptions) {
                            productInfo.configuredOptions = window.currentConfiguratorOptions;
                        }
                    } else {
                        // Try to extract from modal DOM if data object not available
                        const modalTitle = document.querySelector('#product-modal .modal-title');
                        const modalCategory = document.querySelector('#product-modal .product-category');
                        
                        if (modalTitle) productInfo.name = modalTitle.textContent.trim();
                        if (modalCategory) productInfo.category = modalCategory.textContent.trim();
                    }
                    
                    // Silently send product data to server for session storage
                    fetch('/track_contact_source.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            source_page: 'Product Modal',
                            source_section: `Quote Request - ${productInfo.name}`,
                            page_url: window.location.href,
                            timestamp: new Date().toISOString(),
                            product_data: productInfo  // Silent product data collection
                        })
                    }).then(response => response.json())
                      .then(data => {
                          console.log('Product data silently captured:', data);
                      })
                      .catch(error => {
                          console.error('Error capturing product data:', error);
                      });
                    
                    // Open clean contact form without pre-filling
                    if (typeof openContactModalWithTracking === 'function') {
                        // Use empty message to show clean form
                        openContactModalWithTracking('Product Modal', `Quote Request - ${productInfo.name}`, '');
                    } else {
                        console.warn('openContactModalWithTracking not found');
                        alert(`Quote request functionality will be integrated.`);
                    }
                    
                } catch (error) {
                    console.error('Error in requestQuote:', error);
                    // Fallback to simple quote request
                    if (typeof openContactModalWithTracking === 'function') {
                        openContactModalWithTracking('Product Modal', `Quote Request - ${productId}`, '');
                    }
                }
            }
            
            // Additional methods for full catalog functionality
            
            /**
             * Setup product images with gallery functionality
             */
            static setupProductImages(images) {
                window.productModalImages = images;
                window.currentModalImageIndex = 0;
                
                const mainImage = document.getElementById('product-main-image');
                const thumbnailsContainer = document.getElementById('image-thumbnails');
                const navigation = document.getElementById('image-navigation');
                
                if (!mainImage) return;
                
                // Set main image
                mainImage.src = images[0].url;
                mainImage.alt = images[0].alt || 'Product Image';
                
                // Setup thumbnails if multiple images
                if (images.length > 1) {
                    navigation.style.display = 'flex';
                    
                    thumbnailsContainer.innerHTML = '';
                    images.forEach((image, index) => {
                        const thumb = document.createElement('img');
                        thumb.src = image.thumbnail || image.url;
                        thumb.alt = image.alt || 'Product Image';
                        thumb.className = 'thumbnail-image' + (index === 0 ? ' active' : '');
                        thumb.onclick = () => this.selectImage(index);
                        thumb.style.cssText = `
                            cursor: pointer; width: 60px; height: 60px; object-fit: cover; 
                            border-radius: 5px; margin: 5px; transition: all 0.3s ease;
                            border: ${index === 0 ? '2px solid #007bff' : '2px solid transparent'};
                        `;
                        thumbnailsContainer.appendChild(thumb);
                    });
                } else {
                    navigation.style.display = 'none';
                }
            }
            
            /**
             * Select image by index
             */
            static selectImage(index) {
                if (window.productModalImages && index >= 0 && index < window.productModalImages.length) {
                    window.currentModalImageIndex = index;
                    const mainImage = document.getElementById('product-main-image');
                    if (mainImage) {
                        mainImage.src = window.productModalImages[index].url;
                    }
                    
                    // Update thumbnail active state
                    const thumbnails = document.querySelectorAll('.thumbnail-image');
                    thumbnails.forEach((thumb, i) => {
                        thumb.style.border = i === index ? '2px solid #007bff' : '2px solid transparent';
                    });
                }
            }
            
            /**
             * Navigate to previous image
             */
            static previousImage() {
                if (window.productModalImages) {
                    const newIndex = window.currentModalImageIndex > 0 ? 
                        window.currentModalImageIndex - 1 : 
                        window.productModalImages.length - 1;
                    this.selectImage(newIndex);
                }
            }
            
            /**
             * Navigate to next image
             */
            static nextImage() {
                if (window.productModalImages) {
                    const newIndex = window.currentModalImageIndex < window.productModalImages.length - 1 ? 
                        window.currentModalImageIndex + 1 : 0;
                    this.selectImage(newIndex);
                }
            }
            
            /**
             * Setup configurator with all options
             */
            static setupConfigurator(productData) {
                let configuratorOptions = productData.configurator_options;
                const categoryKey = String((productData && productData.category) || '').trim().toLowerCase();

                if (configuratorOptions && typeof configuratorOptions === 'object' && configuratorOptions.options && !Array.isArray(configuratorOptions.options)) {
                    configuratorOptions = configuratorOptions.options;
                }

                // If the DB has configurator_options but they lack a karat_level key,
                // and price_by_metal is populated, the stored JSON is stale/incomplete.
                // Discard it so the synthesizer below builds a proper metal selector.
                // Applies to categories like 'family' that have old partial JSON in the DB.
                if (configuratorOptions
                    && typeof configuratorOptions === 'object'
                    && !configuratorOptions.karat_level
                    && productData.price_by_metal
                    && Object.keys(productData.price_by_metal).length > 0) {
                    configuratorOptions = null;
                }

                // Fallback: if a product has no dedicated configurator JSON, synthesize
                // a metal selector from price_by_metal. Works for all categories.
                // For products with two-tone variants (any *TT key), also add
                // pattern_metal + band_metal selects so the user can mix colours —
                // identical to how the celtic configurator JSON works.
                if ((!configuratorOptions || typeof configuratorOptions !== 'object') && productData) {
                    const map = productData.price_by_metal || {};
                    const has = key => Object.prototype.hasOwnProperty.call(map, key);
                    const hasTT = has('10KTT') || has('14KTT') || has('18KTT');
                    const metalOpts = [];

                    if (has('STER')) metalOpts.push({ id: '950_silver', name: '.950 Silver', price_modifier: 0 });
                    if (has('10K') || has('10KTT')) metalOpts.push({ id: '10k', name: '10K Gold', price_modifier: 0 });
                    if (has('14K') || has('14KTT')) metalOpts.push({ id: '14k', name: '14K Gold', price_modifier: 0 });
                    if (has('18K') || has('18KTT')) metalOpts.push({ id: '18k', name: '18K Gold', price_modifier: 0 });

                    if (metalOpts.length > 0) {
                        let defaultId = '950_silver';
                        const rawDefault = String(productData.default_karat || '').toLowerCase();
                        if (rawDefault.indexOf('10') !== -1) defaultId = '10k';
                        else if (rawDefault.indexOf('14') !== -1) defaultId = '14k';
                        else if (rawDefault.indexOf('18') !== -1) defaultId = '18k';
                        if (!metalOpts.some(o => o.id === defaultId)) defaultId = metalOpts[0].id;

                        const goldKarats = ['10k', '14k', '18k'];
                        const colorOpts = [
                            { id: 'yellow', name: 'Yellow Gold', price_modifier: 0 },
                            { id: 'white',  name: 'White Gold',  price_modifier: 25 },
                            { id: 'rose',   name: 'Rose Gold',   price_modifier: 15 }
                        ];

                        configuratorOptions = { karat_level: {
                            label: 'Metal / Purity',
                            required: true,
                            type: 'single_select',
                            default: defaultId,
                            options: metalOpts
                        }};

                        if (hasTT) {
                            // Two-tone capable: add pattern + band colour pickers.
                            // applyConditionalVisibility() will hide both when silver is selected.
                            configuratorOptions.pattern_metal = {
                                label: 'Pattern Metal',
                                required: true,
                                type: 'single_select',
                                default: 'yellow',
                                help_text: 'Metal colour for the decorative pattern detail',
                                visible_when: { karat_level: goldKarats },
                                options: colorOpts
                            };
                            configuratorOptions.band_metal = {
                                label: 'Band Metal',
                                required: true,
                                type: 'single_select',
                                default: 'yellow',
                                help_text: 'Metal colour for the band body — choosing a different colour from Pattern Metal adds a two-tone surcharge',
                                visible_when: { karat_level: goldKarats },
                                options: [
                                    { id: 'yellow', name: 'Yellow Gold', price_modifier: 0 },
                                    { id: 'white',  name: 'White Gold',  price_modifier: 0 },
                                    { id: 'rose',   name: 'Rose Gold',   price_modifier: 0 }
                                ]
                            };
                        } else if (metalOpts.some(o => o.id !== '950_silver')) {
                            // Single-metal gold product: add one colour picker (metal_color).
                            // applyConditionalVisibility() hides it when silver is selected.
                            configuratorOptions.metal_color = {
                                label: 'Metal Color',
                                required: true,
                                type: 'single_select',
                                default: 'yellow',
                                help_text: 'Metal colour for gold options',
                                visible_when: { karat_level: goldKarats },
                                options: colorOpts
                            };
                        }
                    }
                }

                let optionsContainer = document.getElementById('configurator-options');
                if (!optionsContainer) {
                    const fallback = document.getElementById('configurator-container');
                    if (fallback) {
                        fallback.innerHTML = '<h4>Customize Your Product</h4><div class="configurator-options" id="configurator-options"></div>';
                        optionsContainer = document.getElementById('configurator-options');
                    }
                }
                if (!optionsContainer || !configuratorOptions) return;
                
                // Store product info globally
                window.currentModalProduct = productData;
                
                optionsContainer.innerHTML = '';
                
                // Derive gender from the loaded product_id suffix (e.g. "3001M" → "M")
                const loadedProductId  = String((productData && productData.product_id) || '');
                const loadedGenderSuffix = loadedProductId.slice(-1).toUpperCase();
                const loadedGender = (loadedGenderSuffix === 'M' || loadedGenderSuffix === 'L')
                    ? loadedGenderSuffix : null;

                // Process each configurator option
                Object.keys(configuratorOptions).forEach(optionKey => {
                    const option = configuratorOptions[optionKey];
                    if (!option.options || option.options.length === 0) return;
                    
                    // Create option group
                    const optionGroup = document.createElement('div');
                    optionGroup.className = 'option-group';
                    optionGroup.setAttribute('data-option-key', optionKey);
                    optionGroup.style.marginBottom = '20px';

                    // Store visible_when rules so applyConditionalVisibility() can read them
                    if (option.visible_when) {
                        optionGroup.setAttribute('data-visible-when', JSON.stringify(option.visible_when));
                    }
                    
                    // Create label
                    const label = document.createElement('label');
                    label.textContent = option.label || optionKey;
                    label.style.cssText = 'display: block; font-weight: bold; margin-bottom: 5px;';
                    optionGroup.appendChild(label);
                    
                    // Add help text if available
                    if (option.help_text) {
                        const helpText = document.createElement('p');
                        helpText.textContent = option.help_text;
                        helpText.style.cssText = 'font-size: 0.9em; color: #666; margin: 0 0 10px 0;';
                        optionGroup.appendChild(helpText);
                    }
                    
                    // Create select
                    const select = document.createElement('select');
                    select.id = optionKey;
                    select.style.cssText = 'width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;';
                    
                    select.onchange = function() {
                        if (optionKey === 'karat_level') {
                            // Show/hide options that depend on metal type (e.g. metal_color hidden for silver)
                            ProductModal.applyConditionalVisibility();
                        }
                        if (optionKey === 'gender') {
                            // Filter sizes to only those valid for selected gender
                            ProductModal.applySizeFilter();
                        }
                        if (optionKey === 'width') {
                            // Width switches to a different product variant with its own pricing.
                            // updateProductId() triggers an async price fetch and chains updatePrice()
                            // in the completion handler — return here so updatePrice() isn't called
                            // a second time with stale price_by_metal before the fetch resolves.
                            ProductModal.updateProductId();
                            return;
                        }
                        if (optionKey === 'size' || optionKey === 'gender') {
                            ProductModal.updateProductId();
                        }
                        ProductModal.updatePrice();
                    };
                    
                    // Add options to select
                    option.options.forEach(optValue => {
                        const optElement = document.createElement('option');
                        optElement.value = optValue.id;
                        optElement.setAttribute('data-price', optValue.price_modifier || 0);
                        
                        // For width options, store the product base/variant IDs
                        if (optValue.product_base) {
                            optElement.setAttribute('data-product-base', optValue.product_base);
                        }
                        if (optValue.product_id_m) {
                            optElement.setAttribute('data-product-id-m', optValue.product_id_m);
                        }
                        if (optValue.product_id_l) {
                            optElement.setAttribute('data-product-id-l', optValue.product_id_l);
                        }

                        // For size options, store which genders can wear this size
                        if (optionKey === 'size' && optValue.available_genders) {
                            optElement.setAttribute('data-genders', JSON.stringify(optValue.available_genders));
                        }
                        
                        optElement.textContent = optValue.name;
                        
                        // Default selection logic:
                        // - gender: match the suffix of the loaded product_id (M/L)
                        // - width:  match either product_id_m or product_id_l against the loaded product_id
                        // - others: use the declared default from the JSON
                        let isDefault = false;
                        if (optionKey === 'gender' && loadedGender) {
                            isDefault = (String(optValue.id).toUpperCase() === loadedGender);
                        } else if (optionKey === 'width' || optionKey === 'style_and_width') {
                            isDefault = (optValue.product_id_m === loadedProductId ||
                                         optValue.product_id_l === loadedProductId);
                        } else {
                            isDefault = (optValue.id === option.default);
                        }
                        if (isDefault) {
                            optElement.selected = true;
                        }
                        
                        select.appendChild(optElement);
                    });
                    
                    optionGroup.appendChild(select);
                    optionsContainer.appendChild(optionGroup);
                });

                // Apply conditional visibility rules and size filter based on initial selections
                ProductModal.applyConditionalVisibility();
                ProductModal.applySizeFilter();
                
                // Set initial product ID display without triggering a price re-fetch.
                const productIdDisplay = document.getElementById('dynamic-product-id');
                if (productIdDisplay && window.currentModalProduct) {
                    productIdDisplay.textContent = window.currentModalProduct.product_id;
                }
                this.updatePrice();
            }

            /**
             * Show/hide option groups based on visible_when rules stored in data-visible-when.
             * Currently supports karat_level-based visibility (e.g. hide metal_color for silver).
             */
            static applyConditionalVisibility() {
                const karatSel = document.getElementById('karat_level');
                if (!karatSel) return;
                const karatVal = karatSel.value;

                document.querySelectorAll('#configurator-options .option-group[data-visible-when]').forEach(function(group) {
                    let rules;
                    try { rules = JSON.parse(group.getAttribute('data-visible-when')); } catch(e) { return; }

                    // Only karat_level rules are supported; extend here for other keys as needed
                    const allowedKarats = rules.karat_level;
                    if (allowedKarats && Array.isArray(allowedKarats)) {
                        const visible = allowedKarats.indexOf(karatVal) !== -1;
                        group.style.display = visible ? '' : 'none';
                        // Disable the hidden select so its data-price doesn't leak into updatePrice()
                        const sel = group.querySelector('select');
                        if (sel) sel.disabled = !visible;
                    }
                });
            }

            /**
             * Show only size options valid for the currently selected gender.
             * Options carry data-genders='["M"]' / '["L"]' / '["M","L"]'.
             * If no data-genders attribute, the option is always shown.
             */
            static applySizeFilter() {
                const genderSel = document.getElementById('gender');
                const sizeSel   = document.getElementById('size');
                if (!genderSel || !sizeSel) return;

                const gender = String(genderSel.value || '').toUpperCase();
                let firstVisible = null;
                let currentStillValid = false;
                const currentVal = sizeSel.value;

                Array.from(sizeSel.options).forEach(function(opt) {
                    const gendersAttr = opt.getAttribute('data-genders');
                    let genders = [];
                    try { genders = gendersAttr ? JSON.parse(gendersAttr) : []; } catch(e) {}

                    const show = (genders.length === 0 || genders.indexOf(gender) !== -1);
                    opt.hidden   = !show;
                    opt.disabled = !show;
                    if (show) {
                        if (!firstVisible) firstVisible = opt;
                        if (opt.value === currentVal) currentStillValid = true;
                    }
                });

                // If the currently selected size is no longer valid, jump to the first valid one
                if (!currentStillValid && firstVisible) {
                    firstVisible.selected = true;
                }
            }

            static resolveSelectedPlainBandProductId() {
                const widthSelect = document.getElementById('width');
                if (!widthSelect || widthSelect.selectedOptions.length === 0) return null;

                const selectedOption = widthSelect.selectedOptions[0];
                const genderSelect = document.getElementById('gender');
                const gender = genderSelect ? String(genderSelect.value || '').toUpperCase() : 'L';

                const productM = selectedOption.getAttribute('data-product-id-m') || '';
                const productL = selectedOption.getAttribute('data-product-id-l') || '';

                if (gender === 'M') {
                    if (productM) return productM;
                    if (productL) return productL;
                } else {
                    if (productL) return productL;
                    if (productM) return productM;
                }

                const base = selectedOption.getAttribute('data-product-base') || '';
                if (base) {
                    return base + (gender === 'M' ? 'M' : 'L');
                }

                return null;
            }

            static async syncPricingForProduct(targetProductId) {
                if (!targetProductId) return;
                if (!window.currentModalProduct) return;

                const currentId = String(window.currentModalProduct.product_id || '');
                if (currentId === String(targetProductId)) return;

                window.productModalPriceCache = window.productModalPriceCache || {};
                const cache = window.productModalPriceCache;

                if (!cache[targetProductId]) {
                    try {
                        const response = await fetch('/get_product_modal_data.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `product_id=${encodeURIComponent(targetProductId)}`
                        });
                        const data = await response.json();
                        if (data && data.success && data.product) {
                            cache[targetProductId] = {
                                product_id: data.product.product_id,
                                base_price: data.product.base_price,
                                price_by_metal: data.product.price_by_metal || {},
                                quote_only: !!data.product.quote_only
                            };
                        }
                    } catch (error) {
                        console.error('Pricing sync failed for', targetProductId, error);
                    }
                }

                const next = cache[targetProductId];
                if (!next) return;

                window.currentModalProduct.product_id = next.product_id;
                window.currentModalProduct.base_price = next.base_price;
                window.currentModalProduct.price_by_metal = next.price_by_metal;
                window.currentModalProduct.quote_only = next.quote_only;

                const productIdDisplay = document.getElementById('dynamic-product-id');
                if (productIdDisplay) {
                    productIdDisplay.textContent = next.product_id;
                }

                const priceDisplay = document.getElementById('total-price');
                if (priceDisplay && next.base_price !== null && next.base_price !== undefined) {
                    priceDisplay.setAttribute('data-base-price', String(next.base_price));
                }
            }
            
            /**
             * Update product ID based on selections (Celtic L/M logic + width mapping)
             */
            static updateProductId() {
                if (!window.currentModalProduct) return;
                
                const sizeSelect = document.getElementById('size');
                const widthSelect = document.getElementById('width');
                const productIdDisplay = document.getElementById('dynamic-product-id');
                const categoryKey = String(window.currentModalProduct.category || '').trim().toLowerCase();
                
                if (productIdDisplay && categoryKey === 'celtic_bands') {
                    let baseId = window.currentModalProduct.product_id.replace(/[LM]$/, '');
                    
                    // Handle width changes - map to different base product IDs
                    if (widthSelect && widthSelect.selectedOptions.length > 0) {
                        const selectedOption = widthSelect.selectedOptions[0];
                        const productBase = selectedOption.getAttribute('data-product-base');
                        if (productBase) {
                            baseId = productBase;
                        }
                    }
                    
                    // Apply Celtic L/M suffix logic if size selector exists
                    let finalProductId = baseId;
                    if (sizeSelect && sizeSelect.selectedOptions.length > 0) {
                        const selectedSize = parseFloat(sizeSelect.value);
                        const suffix = selectedSize <= 8 ? 'L' : 'M';
                        finalProductId = baseId + suffix;
                    } else {
                        // Default to L suffix for Celtic bands
                        finalProductId = baseId + 'L';
                    }
                    
                    productIdDisplay.textContent = finalProductId;
                    // Fetch price_by_metal for the selected width — each celtic width is
                    // a different product with its own gold weight and therefore its own price.
                    ProductModal.syncPricingForProduct(finalProductId).then(() => ProductModal.updatePrice());
                } else if (categoryKey === 'plain_bands') {
                    const nextProductId = ProductModal.resolveSelectedPlainBandProductId();
                    if (nextProductId) {
                        productIdDisplay.textContent = nextProductId;
                        ProductModal.syncPricingForProduct(nextProductId).then(() => ProductModal.updatePrice());
                    }
                }
            }
            
            /**
             * Update total price based on selections.
             *
             * Source of truth is productData.price_by_metal (a map keyed by
             * DB metal_type: STER, 10K, 10KTT, 14K, 14KTT, 18K, 18KTT, GF).
             * The karat_level select determines which entry to use; non-karat
             * selects (width, metal_color, finish, etc.) contribute additive
             * modifiers from their data-price attributes.
             *
             * If price_by_metal is absent (e.g. legacy callers), fall back to
             * the old behaviour of summing every select's data-price on top of
             * the data-base-price attribute.
             */
            static updatePrice() {
                const priceDisplay = document.getElementById('total-price');
                if (!priceDisplay) return; // No price shown (user not authenticated)
                const metalTypeDisplay = document.getElementById('selected-metal-type');

                const product = window.currentModalProduct || {};
                const priceByMetal = product.price_by_metal || {};
                const hasPerMetal = Object.keys(priceByMetal).length > 0;

                const patternMetal = document.getElementById('pattern_metal');
                const bandMetal    = document.getElementById('band_metal');
                const isTwoTone    = !!(patternMetal && bandMetal && patternMetal.value !== bandMetal.value);

                const karatSel = document.getElementById('karat_level');
                const karatIdRaw  = karatSel ? karatSel.value : null;
                const karatId = karatIdRaw ? String(karatIdRaw).trim().toLowerCase() : null;

                let basePrice;
                let usedPerMetal = false;
                if (hasPerMetal && karatId) {
                    const karatMap = {
                        '950_silver': 'STER',
                        'ster': 'STER',
                        'sterling': 'STER',
                        '10': '10K',
                        '10k':  isTwoTone ? '10KTT' : '10K',
                        '14': '14K',
                        '14k':  isTwoTone ? '14KTT' : '14K',
                        '18': '18K',
                        '18k':  isTwoTone ? '18KTT' : '18K'
                    };
                    let metalType = karatMap[karatId];
                    if (!metalType && karatId) {
                        if (karatId.indexOf('10k') !== -1) metalType = isTwoTone ? '10KTT' : '10K';
                        else if (karatId.indexOf('14k') !== -1) metalType = isTwoTone ? '14KTT' : '14K';
                        else if (karatId.indexOf('18k') !== -1) metalType = isTwoTone ? '18KTT' : '18K';
                    }
                    if (metalType && priceByMetal[metalType] != null) {
                        basePrice    = parseFloat(priceByMetal[metalType]);
                        usedPerMetal = true;
                    } else if (metalType) {
                        // Two-tone variant missing — fall back to single-metal price for same karat
                        const single = metalType.replace(/TT$/, '');
                        if (priceByMetal[single] != null) {
                            basePrice    = parseFloat(priceByMetal[single]);
                            usedPerMetal = true;
                        }
                    }
                }
                if (basePrice === undefined) {
                    basePrice = parseFloat(priceDisplay.getAttribute('data-base-price')) || 0;
                }

                let totalPrice = basePrice;

                if (usedPerMetal) {
                    // Only add explicitly named surcharge options on top of the per-metal base.
                    // - metal_color: white gold (+$25), rose gold (+$15) for all ring types
                    // - finish:      brushed (+$40), satin (+$35) for Celtic/Cultural bands
                    // - antiquing:   antiqued/oxidized (+$50) for Celtic/Cultural bands
                    // Width, size, and gender selects are NOT surcharges — width switches the
                    // product_id and triggers a fresh price lookup; size/gender have $0 modifiers.
                    ['metal_color', 'finish', 'antiquing'].forEach(function(id) {
                        const sel = document.getElementById(id);
                        if (sel && sel.selectedOptions.length > 0) {
                            totalPrice += parseFloat(sel.selectedOptions[0].getAttribute('data-price')) || 0;
                        }
                    });
                } else {
                    // Legacy fallback: no price_by_metal available — sum all selects
                    // except those that represent product switches (data-product-base).
                    const allSelects = document.querySelectorAll('#configurator-options select');
                    allSelects.forEach(function(select) {
                        if (select.selectedOptions.length > 0) {
                            const opt = select.selectedOptions[0];
                            if (opt.hasAttribute('data-product-base')) return;
                            totalPrice += parseFloat(opt.getAttribute('data-price')) || 0;
                        }
                    });
                    if (isTwoTone) totalPrice += 150;
                }

                if (totalPrice < 0) totalPrice = 0; // safety clamp
                priceDisplay.textContent = '$' + totalPrice.toFixed(2);

                if (metalTypeDisplay) {
                    const colorSel = document.getElementById('metal_color');
                    const color = colorSel ? String(colorSel.value || '').toLowerCase() : '';

                    let label = 'Metal Type: Custom';
                    if (karatId) {
                        if (karatId.indexOf('silver') !== -1 || karatId === 'ster' || karatId === 'sterling') {
                            label = 'Metal Type: .950 Silver';
                        } else if (karatId.indexOf('10k') !== -1 || karatId === '10') {
                            label = 'Metal Type: 10K ' + (color ? (color.charAt(0).toUpperCase() + color.slice(1) + ' Gold') : 'Gold');
                        } else if (karatId.indexOf('14k') !== -1 || karatId === '14') {
                            label = 'Metal Type: 14K ' + (color ? (color.charAt(0).toUpperCase() + color.slice(1) + ' Gold') : 'Gold');
                        } else if (karatId.indexOf('18k') !== -1 || karatId === '18') {
                            label = 'Metal Type: 18K ' + (color ? (color.charAt(0).toUpperCase() + color.slice(1) + ' Gold') : 'Gold');
                        }
                    }
                    if (isTwoTone) {
                        label += ' (Two-Tone)';
                    }
                    metalTypeDisplay.textContent = label;
                }
            }
            
            /**
             * Add to cart functionality
             */
            static addToCart(productId) {
                console.log('Adding to cart:', productId);
                alert('Add to cart functionality will be integrated');
            }
        }

        // Expose ProductModal on window so other inline scripts / onclick handlers
        // that use `window.ProductModal` can see it. Class declarations in classic
        // <script> blocks create a lexical binding but do NOT attach to globalThis.
        window.ProductModal = ProductModal;

        // Compatibility bridge: legacy pages call openProductModal(...).
        // Route those calls to the unified ProductModal implementation.
        window.openProductModal = function(productId, collection, category) {
            if (window.ProductModal && typeof ProductModal.open === 'function') {
                ProductModal.open(productId, {
                    collection: collection || '',
                    category: category || ''
                });
                return;
            }
            window.location.href = 'unified_detail.php?product=' + encodeURIComponent(productId);
        };

        // Handle escape key and click outside to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ProductModal.close();
            }
        });
        
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('product-modal');
            if (e.target === modal) {
                ProductModal.close();
            }
        });
        </script>
        <?php
    }
}
?>