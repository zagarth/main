<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pricing - Cadman Manufacturing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        h1 {
            color: #333;
            font-size: 28px;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: #e0e0e0;
        }
        
        .search-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .search-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .search-controls input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-controls button {
            padding: 10px 30px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .search-controls button:hover {
            background: #0056b3;
        }
        
        .system-settings {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .setting-group {
            display: flex;
            flex-direction: column;
        }
        
        .setting-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .setting-group input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .results-section {
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .product-title {
            flex: 1;
        }
        
        .product-title h3 {
            color: #007bff;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .product-title p {
            color: #666;
            font-size: 14px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .btn-save:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
        }
        
        .btn-reset:hover {
            background: #5a6268;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
        }
        
        .section h4 {
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .field-group {
            margin-bottom: 15px;
        }
        
        .field-group label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .field-group input,
        .field-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .field-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .calculated-price {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .price-row.total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #007bff;
            font-weight: 700;
            font-size: 16px;
            color: #007bff;
        }
        
        .message {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .variant-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .variant-btn {
            padding: 6px 12px;
            border: 2px solid #007bff;
            background: white;
            color: #007bff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .variant-btn.active {
            background: #007bff;
            color: white;
        }
        
        .variant-btn:hover {
            background: #e7f3ff;
        }
        
        .variant-btn.active:hover {
            background: #0056b3;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-close {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .form-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .metal-variants-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .variant-row {
            display: grid;
            grid-template-columns: 150px repeat(5, 1fr) 50px;
            gap: 10px;
            align-items: end;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 4px;
        }
        
        .variant-row:last-child {
            margin-bottom: 0;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .media-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        
        .media-status.has-media {
            border-left: 3px solid #28a745;
        }
        
        .media-status.no-media {
            border-left: 3px solid #dc3545;
        }
        
        .media-icon {
            font-size: 18px;
        }
        
        .media-info {
            flex: 1;
        }
        
        .media-label {
            font-weight: 600;
            color: #333;
        }
        
        .media-detail {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        
        .media-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-media {
            padding: 4px 10px;
            font-size: 11px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-upload {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Pricing Management</h1>
            <div class="nav-links">
                <a href="../cadman-database/">Price Calculator</a>
                <a href="index.php">Admin Dashboard</a>
                <a href="?logout=1">Logout</a>
            </div>
        </header>
        
        <div class="search-section">
            <div class="search-controls">
                <input type="text" id="searchInput" placeholder="Search by item code or description..." />
                <button onclick="searchProducts()">Search</button>
                <button onclick="loadAll()" style="background: #6c757d">Load All</button>
                <button onclick="showNewProductForm()" style="background: #28a745">+ Create New Product</button>
            </div>
            
            <div class="system-settings">
                <div class="setting-group">
                    <label>Gold Price ($/oz)</label>
                    <input type="number" id="goldPrice" value="7300" step="0.01" />
                </div>
                <div class="setting-group">
                    <label>Labor Rate ($/hr)</label>
                    <input type="number" id="laborRate" value="28" step="0.01" />
                </div>
                <div class="setting-group">
                    <label>Sterling GF</label>
                    <input type="number" id="sterlingGF" value="130" step="0.01" />
                </div>
                <div class="setting-group">
                    <label>Base Margin (%)</label>
                    <input type="number" id="baseMargin" value="8" step="0.01" />
                </div>
            </div>
            <div style="margin-top: 15px; text-align: right;">
                <button onclick="saveSystemSettings()" style="background: #28a745; padding: 10px 30px; border: none; color: white; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    💾 Save System Settings
                </button>
                <span id="settingsStatus" style="margin-left: 15px; font-size: 13px; color: #666;"></span>
            </div>
        </div>
        
        <div id="messageContainer"></div>
        <div id="resultsContainer"></div>
    </div>
    
    <!-- New Product Modal -->
    <div id="newProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Product</h2>
                <button class="modal-close" onclick="closeNewProductForm()">Close</button>
            </div>
            
            <form id="newProductForm" onsubmit="createNewProduct(event)">
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Base Code *</label>
                            <input type="text" name="base_code" required placeholder="e.g., 050DT">
                        </div>
                        <div class="field-group">
                            <label>Description *</label>
                            <input type="text" name="description" required placeholder="Product description">
                        </div>
                        <div class="field-group">
                            <label>Category</label>
                            <input type="text" name="category" placeholder="e.g., T60">
                        </div>
                        <div class="field-group">
                            <label>Group Code</label>
                            <input type="text" name="group_code" placeholder="e.g., 8">
                        </div>
                        <div class="field-group">
                            <label>Info 1</label>
                            <input type="text" name="info_1" placeholder="Additional info">
                        </div>
                        <div class="field-group">
                            <label>Info 2</label>
                            <input type="text" name="info_2" placeholder="Additional info">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Labor Costs</h3>
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Labor Hours</label>
                            <input type="number" name="labor_hours" value="0" step="0.001" 
                                   onchange="autoCalcLabor()">
                        </div>
                        <div class="field-group">
                            <label>Labor Cost ($)</label>
                            <input type="number" name="labor_cost" value="0" step="0.01" id="modalLaborCost">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Stone Costs & Info</h3>
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Stone Cost ($)</label>
                            <input type="number" name="stone_cost" value="0" step="0.01">
                        </div>
                        <div class="field-group">
                            <label>Star Cost ($)</label>
                            <input type="number" name="star_cost" value="0" step="0.01">
                        </div>
                        <div class="field-group">
                            <label>Stone Setting Cost ($)</label>
                            <input type="number" name="stone_setting_cost" value="0" step="0.01">
                        </div>
                        <div class="field-group">
                            <label>Stone Min Count</label>
                            <input type="number" name="stone_min" value="" step="1" min="0">
                        </div>
                        <div class="field-group">
                            <label>Stone Max Count</label>
                            <input type="number" name="stone_max" value="" step="1" min="0">
                        </div>
                        <div class="field-group">
                            <label>Stone Size (mm)</label>
                            <input type="number" name="stone_size" value="" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Markup & Tax</h3>
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Markup Percent (%)</label>
                            <input type="number" name="markup_percent" value="50" step="0.01">
                        </div>
                        <div class="field-group">
                            <label>Sales Tax Percent (%)</label>
                            <input type="number" name="sales_tax_percent" value="0" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Metal Variants</h3>
                    <div id="variantsContainer" class="metal-variants-section">
                        <div class="variant-row" data-variant-index="0">
                            <div class="field-group">
                                <label>Metal Type *</label>
                                <select name="variants[0][metal_type]" required>
                                    <option value="">Select...</option>
                                    <option value="10K">10K Gold</option>
                                    <option value="14K">14K Gold</option>
                                    <option value="14W">14K White Gold</option>
                                    <option value="18K">18K Gold</option>
                                    <option value="18W">18K White Gold</option>
                                    <option value="STER">Sterling Silver</option>
                                    <option value="PLAT">Platinum</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>Material Cost ($)</label>
                                <input type="number" name="variants[0][material_cost]" value="0" step="0.01">
                            </div>
                            <div class="field-group">
                                <label>Gold Grams</label>
                                <input type="number" name="variants[0][gold_grams]" value="0" step="0.01">
                            </div>
                            <div class="field-group">
                                <label>Sterling Grams</label>
                                <input type="number" name="variants[0][sterling_grams]" value="0" step="0.01">
                            </div>
                            <div class="field-group">
                                <label>Metal Variant</label>
                                <input type="text" name="variants[0][metal_variant]" placeholder="Optional">
                            </div>
                            <div class="field-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn-remove" onclick="removeVariant(0)" 
                                        style="visibility: hidden;">✕</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-add" onclick="addVariant()">+ Add Metal Variant</button>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                    <button type="button" class="btn btn-reset" onclick="closeNewProductForm()">Cancel</button>
                    <button type="submit" class="btn btn-save">Create Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../cadman-database/js/pricing-calculator.js"></script>
    <script>
        let products = [];
        let originalData = new Map();
        let pricingCalc = new PricingCalculator();
        
        // Load system settings from database on page load
        async function loadSystemSettings() {
            try {
                const response = await fetch('/cadman-database/api/get_settings.php');
                const result = await response.json();
                
                if (result.success && result.settings) {
                    // Update input fields
                    if (result.settings.gold_price) {
                        document.getElementById('goldPrice').value = result.settings.gold_price.value;
                    }
                    if (result.settings.labor_rate) {
                        document.getElementById('laborRate').value = result.settings.labor_rate.value;
                    }
                    if (result.settings.sterling_gf) {
                        document.getElementById('sterlingGF').value = result.settings.sterling_gf.value;
                    }
                    if (result.settings.base_margin) {
                        document.getElementById('baseMargin').value = result.settings.base_margin.value;
                    }
                    
                    // Load settings into pricing calculator
                    pricingCalc.loadSystemSettings({
                        goldPrice: result.settings.gold_price?.value || 7400,
                        labourRate: result.settings.labor_rate?.value || 28,
                        sterlingGF: result.settings.sterling_gf?.value || 130,
                        marketMarkup: 0
                    });
                    
                    document.getElementById('settingsStatus').innerHTML = 
                        '<span style="color: #28a745;">✓ Settings loaded from database</span>';
                }
            } catch (error) {
                console.error('Error loading system settings:', error);
                document.getElementById('settingsStatus').innerHTML = 
                    '<span style="color: #dc3545;">⚠ Using default values</span>';
            }
        }
        
        // Save system settings to database
        async function saveSystemSettings() {
            const settings = {
                gold_price: parseFloat(document.getElementById('goldPrice').value),
                labor_rate: parseFloat(document.getElementById('laborRate').value),
                sterling_gf: parseFloat(document.getElementById('sterlingGF').value),
                base_margin: parseFloat(document.getElementById('baseMargin').value)
            };
            
            try {
                const response = await fetch('api/update_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ settings })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('settingsStatus').innerHTML = 
                        `<span style="color: #28a745;">✓ Saved! Updated ${result.updated_count} settings globally</span>`;
                    showMessage('System settings saved successfully - active across all pages!', 'success');
                    
                    // Clear status after 5 seconds
                    setTimeout(() => {
                        document.getElementById('settingsStatus').innerHTML = '';
                    }, 5000);
                } else {
                    throw new Error(result.error || 'Save failed');
                }
            } catch (error) {
                document.getElementById('settingsStatus').innerHTML = 
                    '<span style="color: #dc3545;">✗ Save failed</span>';
                showMessage(`Error saving settings: ${error.message}`, 'error');
            }
        }
        
        // Load settings on page load
        loadSystemSettings();
        
        // Load products from API
        async function searchProducts() {
            const search = document.getElementById('searchInput').value.trim();
            if (!search) {
                showMessage('Please enter a search term', 'error');
                return;
            }
            
            await loadProducts(search);
        }
        
        async function loadAll() {
            await loadProducts('', 100);
        }
        
        async function loadProducts(search = '', limit = 20) {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = '<div class="loading">Loading products...</div>';
            
            try {
                const url = `/cadman-database/api/get_products.php?search=${encodeURIComponent(search)}&limit=${limit}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error('API error');
                }
                
                // DEBUG: Log raw API response for first item
                if (data.data && data.data.length > 0) {
                    console.log('First raw API item:', data.data[0]);
                }
                
                // Group variants by base_code
                const productMap = new Map();
                for (const item of data.data) {
                    if (!productMap.has(item.base_code)) {
                        productMap.set(item.base_code, {
                            base: item,
                            variants: []
                        });
                    }
                    productMap.get(item.base_code).variants.push(item);
                }
                
                products = Array.from(productMap.values());
                
                if (products.length === 0) {
                    container.innerHTML = '<div class="no-results">No products found</div>';
                    return;
                }
                
                renderProducts();
                showMessage(`Loaded ${products.length} products`, 'success');
                
            } catch (error) {
                container.innerHTML = '<div class="no-results">Error loading products</div>';
                showMessage('Error loading products: ' + error.message, 'error');
            }
        }
        
        function renderProducts() {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = '';
            
            products.forEach((product, index) => {
                // DEBUG: Log first product's catalog data
                if (index === 0) {
                    console.log('First product data:', {
                        base_code: product.base.base_code,
                        pdf_file: product.base.pdf_file,
                        image_files: product.base.image_files,
                        page_reference: product.base.page_reference,
                        first_variant: {
                            full_item_code: product.variants[0].full_item_code,
                            pdf_file: product.variants[0].pdf_file,
                            image_files: product.variants[0].image_files,
                            page_reference: product.variants[0].page_reference
                        }
                    });
                }
                
                const card = createProductCard(product, index);
                container.appendChild(card);
                // Calculate prices immediately after rendering with correct formulas
                updateCalculations(product.base.base_code);
                // Check media status for first variant
                updateMediaStatus(product.base.base_code, product.variants[0]);
            });
        }
        
        function createProductCard(product, index) {
            const div = document.createElement('div');
            div.className = 'product-card';
            div.dataset.index = index;
            
            const base = product.base;
            div.dataset.baseCode = base.base_code;
            
            // Use first variant as default
            const currentVariant = product.variants[0];
            div.dataset.activeVariant = 0;
            
            // Store original data
            originalData.set(base.base_code, JSON.parse(JSON.stringify(base)));
            
            // Helper to extract variant label from full_item_code
            const getVariantLabel = (variant) => {
                const fullCode = variant.full_item_code || '';
                const parts = fullCode.split('/');
                return parts.length > 1 ? parts[1] : (variant.metal_type || variant.metal_hi || 'Unknown');
            };
            
            div.innerHTML = `
                <div class="product-header">
                    <div class="product-title">
                        <h3>${base.base_code}</h3>
                        <p>${base.base_description}</p>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-save" onclick="saveProduct('${base.base_code}')">Save Changes</button>
                        <button class="btn btn-reset" onclick="resetProduct('${base.base_code}')">Reset</button>
                    </div>
                </div>
                
                <div class="variant-selector">
                    ${product.variants.map((v, i) => `
                        <button class="variant-btn ${i === 0 ? 'active' : ''}" 
                                data-variant-index="${i}"
                                onclick="switchVariant('${base.base_code}', ${i})">
                            ${getVariantLabel(v)}
                        </button>
                    `).join('')}
                </div>
                
                <div class="product-grid">
                    <div class="section">
                        <h4>Labor & Production</h4>
                        <div class="field-group">
                            <label>Labor Hours</label>
                            <input type="number" class="product-field" data-field="labor_hours" 
                                   value="${base.labor_hours}" step="0.001" onchange="updateCalculations('${base.base_code}')">
                        </div>
                        <div class="field-group">
                            <label>Labor Cost ($)</label>
                            <input type="number" class="product-field" data-field="labor_cost" 
                                   value="${base.labor_cost}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                        </div>
                        <div class="field-group">
                            <label>Material Cost ($)</label>
                            <input type="number" class="variant-field" data-field="material_cost" 
                                   value="${currentVariant.material_cost}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                        </div>
                    </div>
                    
                        <div class="section">
                            <h4>Stone Costs & Info</h4>
                            <div class="field-group">
                                <label>Stone Cost ($)</label>
                                <input type="number" class="product-field" data-field="stone_cost" 
                                       value="${base.stone_cost}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                            </div>
                            <div class="field-group">
                                <label>Star Cost ($)</label>
                                <input type="number" class="product-field" data-field="star_cost" 
                                       value="${base.star_cost}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                            </div>
                            <div class="field-group">
                                <label>Stone Setting Cost ($)</label>
                                <input type="number" class="product-field" data-field="stone_setting_cost" 
                                       value="${base.stone_setting_cost}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                            </div>
                            <div class="field-group">
                                <label>Stone Min Count</label>
                                <input type="number" class="product-field" data-field="stone_min" 
                                       value="${base.stone_min ?? ''}" step="1" min="0">
                            </div>
                            <div class="field-group">
                                <label>Stone Max Count</label>
                                <input type="number" class="product-field" data-field="stone_max" 
                                       value="${base.stone_max ?? ''}" step="1" min="0">
                            </div>
                            <div class="field-group">
                                <label>Stone Size (mm)</label>
                                <input type="number" class="product-field" data-field="stone_size" 
                                       value="${base.stone_size ?? ''}" step="0.01" min="0">
                            </div>
                        </div>
                    
                    <div class="section">
                        <h4>Metal & Pricing</h4>
                        <div class="field-group">
                            <label>Gold Grams</label>
                            <input type="number" class="variant-field" data-field="gold_grams" 
                                   value="${currentVariant.gold_grams}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                        </div>
                        <div class="field-group">
                            <label>Sterling Grams</label>
                            <input type="number" class="variant-field" data-field="sterling_grams" 
                                   value="${currentVariant.sterling_grams}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                        </div>
                        <div class="field-group">
                            <label>Markup (%)</label>
                            <input type="number" class="product-field" data-field="markup_percent" 
                                   value="${base.markup_percent}" step="0.01" onchange="updateCalculations('${base.base_code}')">
                        </div>
                        
                        <div class="calculated-price">
                            <div class="price-row">
                                <span>Total Cost:</span>
                                <span id="calc-cost-${base.base_code}">$${currentVariant.total_cost.toFixed(2)}</span>
                            </div>
                            <div class="price-row total">
                                <span>Selling Price:</span>
                                <span id="calc-price-${base.base_code}">$${currentVariant.selling_price.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h4>Media & Catalog</h4>
                        <div id="media-image-${base.base_code}" class="media-status">
                            <div class="media-icon">📸</div>
                            <div class="media-info">
                                <div class="media-label">Checking...</div>
                            </div>
                        </div>
                        <div id="media-pdf-${base.base_code}" class="media-status">
                            <div class="media-icon">📄</div>
                            <div class="media-info">
                                <div class="media-label">Checking...</div>
                            </div>
                        </div>
                        <div style="margin-top: 15px; text-align: center;">
                            <a href="file_upload.php" target="_blank" class="btn-media btn-upload">📤 Upload Files</a>
                        </div>
                    </div>
                </div>
            `;
            
            return div;
        }
        
        function switchVariant(baseCode, variantIndex) {
            const product = products.find(p => p.base.base_code === baseCode);
            if (!product) return;
            
            const variant = product.variants[variantIndex];
            const card = document.querySelector(`.product-card[data-base-code="${baseCode}"]`);
            if (!card) return;
            
            // Store active variant
            card.dataset.activeVariant = variantIndex;
            
            // Update variant buttons
            const buttons = card.querySelectorAll('.variant-btn');
            buttons.forEach(btn => {
                const btnVariantIndex = parseInt(btn.dataset.variantIndex);
                btn.classList.toggle('active', btnVariantIndex === variantIndex);
            });
            
            // Update variant-specific fields
            const materialCostField = card.querySelector('[data-field="material_cost"]');
            const goldGramsField = card.querySelector('[data-field="gold_grams"]');
            const sterlingGramsField = card.querySelector('[data-field="sterling_grams"]');
            
            if (materialCostField) materialCostField.value = variant.material_cost || 0;
            if (goldGramsField) goldGramsField.value = variant.gold_grams || 0;
            if (sterlingGramsField) sterlingGramsField.value = variant.sterling_grams || 0;
            
            // Don't display stored prices - let updateCalculations compute them fresh
            // This ensures consistency with quarter-rounding
            
            // Update media status for new variant
            updateMediaStatus(baseCode, variant);
            
            updateCalculations(baseCode);
        }
        
        function updateCalculations(baseCode) {
            const card = document.querySelector(`.product-card[data-base-code="${baseCode}"]`);
            if (!card) return;
            
            const goldPrice = parseFloat(document.getElementById('goldPrice').value);
            const laborRate = parseFloat(document.getElementById('laborRate').value);
            const sterlingGF = parseFloat(document.getElementById('sterlingGF').value);
            const baseMargin = parseFloat(document.getElementById('baseMargin').value);
            
            // Get current product values (shared across all variants)
            const laborHours = parseFloat(card.querySelector('[data-field="labor_hours"]').value) || 0;
            const stoneCost = parseFloat(card.querySelector('[data-field="stone_cost"]').value) || 0;
            const starCost = parseFloat(card.querySelector('[data-field="star_cost"]').value) || 0;
            const stoneSettingCost = parseFloat(card.querySelector('[data-field="stone_setting_cost"]').value) || 0;
            const markup = parseFloat(card.querySelector('[data-field="markup_percent"]').value) || 0;
            
            // Get current variant values (metal-specific)
            const goldGrams = parseFloat(card.querySelector('[data-field="gold_grams"]').value) || 0;
            const sterlingGrams = parseFloat(card.querySelector('[data-field="sterling_grams"]').value) || 0;
            const materialCost = parseFloat(card.querySelector('[data-field="material_cost"]').value) || 0;
            
            // Get the metal type from the active variant for karat calculation
            const activeVariantIndex = parseInt(card.dataset.activeVariant) || 0;
            const product = products.find(p => p.base.base_code === baseCode);
            const variant = product ? product.variants[activeVariantIndex] : null;
            const metalType = variant ? variant.metal_type : '10K';
            
            // Update pricing calculator settings
            pricingCalc.loadSystemSettings({
                goldPrice: goldPrice,
                labourRate: laborRate,
                sterlingGF: sterlingGF,
                marketMarkup: 0
            });
            
            // Use centralized pricing calculator
            const goldCost = pricingCalc.calculateGoldCost(goldGrams, metalType);
            const sterlingCost = pricingCalc.calculateSterlingCost(sterlingGrams);
            const laborCost = pricingCalc.calculateLaborCost(laborHours);
            
            // Total cost: Sum all components
            const totalCost = materialCost + laborCost + goldCost + sterlingCost + 
                            stoneCost + starCost + stoneSettingCost;
            
            // DEBUG: Log calculation
            console.log(`=== ${baseCode} Calculation ===`);
            console.log(`Material: $${materialCost.toFixed(2)}`);
            console.log(`Labor: ${laborHours}h × $${laborRate} = $${laborCost.toFixed(2)}`);
            console.log(`Gold: ${goldGrams}g × $${goldPrice}/oz ÷ 31.1035 = $${goldCost.toFixed(2)} (${metalType})`);
            console.log(`Sterling: ${sterlingGrams}g = $${sterlingCost.toFixed(2)}`);
            console.log(`Stone: $${stoneCost.toFixed(2)}, Star: $${starCost.toFixed(2)}, Setting: $${stoneSettingCost.toFixed(2)}`);
            console.log(`TOTAL COST: $${totalCost.toFixed(2)}`);
            console.log(`Markup: ${markup}%, Base Margin: ${baseMargin}%`);
            
            // Apply markup and base margin
            let sellingPrice = totalCost * (1 + markup / 100) * (1 + baseMargin / 100);
            console.log(`Pre-round price: $${sellingPrice.toFixed(2)}`);
            
            // Quarter-round using centralized function
            sellingPrice = pricingCalc.roundToQuarter(sellingPrice);
            console.log(`SELLING PRICE: $${sellingPrice.toFixed(2)}`);
            
            // Update display
            document.getElementById(`calc-cost-${baseCode}`).textContent = `$${totalCost.toFixed(2)}`;
            document.getElementById(`calc-price-${baseCode}`).textContent = `$${sellingPrice.toFixed(2)}`;
        }
        
        // Helper function to check if file exists with fallback logic
        async function checkFileExists(variantCode, baseCode, fileType = 'image') {
            // Define possible file locations and extensions
            const imageCategories = ['Crosses_and_Lockets', 'Idents', 'Pendant_earrings'];
            const imageExtensions = ['.png', '.jpg', '.jpeg', '.webp'];
            
            if (fileType === 'image') {
                // Try variant-specific image in each category
                for (const category of imageCategories) {
                    for (const ext of imageExtensions) {
                        const variantPath = `/accessories_php/images/${category}/${variantCode}${ext}`;
                        try {
                            const response = await fetch(variantPath, { method: 'HEAD' });
                            if (response.ok) {
                                return { found: true, path: variantPath, source: 'variant', category };
                            }
                        } catch (e) {
                            // Continue to next attempt
                        }
                    }
                }
                
                // Fall back to base code image
                for (const category of imageCategories) {
                    for (const ext of imageExtensions) {
                        const basePath = `/accessories_php/images/${category}/${baseCode}${ext}`;
                        try {
                            const response = await fetch(basePath, { method: 'HEAD' });
                            if (response.ok) {
                                return { found: true, path: basePath, source: 'base', category };
                            }
                        } catch (e) {
                            // Continue to next attempt
                        }
                    }
                }
            }
            
            return { found: false, path: null, source: null, category: null };
        }
        
        // Update media status display for a product variant
        async function updateMediaStatus(baseCode, variant) {
            const variantCode = variant.full_item_code || baseCode;
            const imageContainer = document.getElementById(`media-image-${baseCode}`);
            const pdfContainer = document.getElementById(`media-pdf-${baseCode}`);
            
            if (!imageContainer || !pdfContainer) return;
            
            // Check for image - ONLY use database field
            const imageFiles = variant.image_files || null;
            
            // Valid image check: not null, not empty, not the string "no images found"
            const hasValidImage = imageFiles && 
                                  imageFiles.trim() !== '' && 
                                  imageFiles.toLowerCase() !== 'no images found';
            
            if (hasValidImage) {
                const imagePath = imageFiles.startsWith('/') ? imageFiles : `/${imageFiles}`;
                imageContainer.className = 'media-status has-media';
                imageContainer.innerHTML = `
                    <div class="media-icon">📸</div>
                    <div class="media-info">
                        <div class="media-label">Image Found</div>
                        <div class="media-detail">${imageFiles}</div>
                    </div>
                    <div class="media-actions">
                        <a href="${imagePath}" target="_blank" class="btn-media btn-view">View</a>
                    </div>
                `;
            } else {
                imageContainer.className = 'media-status no-media';
                imageContainer.innerHTML = `
                    <div class="media-icon">📸</div>
                    <div class="media-info">
                        <div class="media-label">No Image</div>
                        <div class="media-detail">Not in database</div>
                    </div>
                `;
            }
            
            // Check for catalog/PDF - ONLY use database fields
            const pdfFile = variant.pdf_file || null;
            const pageRef = variant.page_reference || null;
            
            if (pdfFile || pageRef) {
                pdfContainer.className = 'media-status has-media';
                const pdfLabel = pageRef ? `Page ${pageRef}` : 'PDF Available';
                const pdfDetail = pdfFile || 'Referenced in catalog';
                pdfContainer.innerHTML = `
                    <div class="media-icon">📄</div>
                    <div class="media-info">
                        <div class="media-label">${pdfLabel}</div>
                        <div class="media-detail">${pdfDetail}</div>
                    </div>
                    ${pdfFile ? `
                        <div class="media-actions">
                            <a href="/Cadman_catalog/${pdfFile}" target="_blank" class="btn-media btn-view">View PDF</a>
                        </div>
                    ` : ''}
                `;
            } else {
                pdfContainer.className = 'media-status no-media';
                pdfContainer.innerHTML = `
                    <div class="media-icon">📄</div>
                    <div class="media-info">
                        <div class="media-label">No Catalog Page</div>
                        <div class="media-detail">No PDF reference found</div>
                    </div>
                `;
            }
        }
        
        async function saveProduct(baseCode) {
            const card = document.querySelector(`.product-card[data-base-code="${baseCode}"]`);
            if (!card) return;
            
            const product = products.find(p => p.base.base_code === baseCode);
            if (!product) return;

            const activeVariantIndex = parseInt(card.dataset.activeVariant) || 0;
            const activeVariant = product.variants[activeVariantIndex] || product.variants[0];
            
            // Collect updated values
            const updates = {
                base_code: baseCode,
                product_id: product.base.product_id,
                active_variant_id: activeVariant ? activeVariant.variant_id : null,
                labor_hours: parseFloat(card.querySelector('[data-field="labor_hours"]').value),
                labor_cost: parseFloat(card.querySelector('[data-field="labor_cost"]').value),
                stone_cost: parseFloat(card.querySelector('[data-field="stone_cost"]').value),
                star_cost: parseFloat(card.querySelector('[data-field="star_cost"]').value),
                stone_setting_cost: parseFloat(card.querySelector('[data-field="stone_setting_cost"]').value),
                stone_min: parseInt(card.querySelector('[data-field="stone_min"]').value) || null,
                stone_max: parseInt(card.querySelector('[data-field="stone_max"]').value) || null,
                stone_size: parseFloat(card.querySelector('[data-field="stone_size"]').value) || null,
                markup_percent: parseFloat(card.querySelector('[data-field="markup_percent"]').value),
                material_cost: parseFloat(card.querySelector('[data-field="material_cost"]').value),
                gold_grams: parseFloat(card.querySelector('[data-field="gold_grams"]').value),
                sterling_grams: parseFloat(card.querySelector('[data-field="sterling_grams"]').value)
            };
            
            try {
                const response = await fetch('api/update_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(updates)
                });

                const rawResponse = await response.text();
                let result = null;

                if (rawResponse) {
                    try {
                        result = JSON.parse(rawResponse);
                    } catch (parseError) {
                        const responseError = rawResponse.trim() || `Request failed with status ${response.status}`;
                        throw new Error(responseError);
                    }
                }

                if (!response.ok) {
                    throw new Error(result?.error || `Request failed with status ${response.status}`);
                }

                if (result && result.success) {
                    showMessage(`Successfully updated ${baseCode}`, 'success');
                    product.base = {
                        ...product.base,
                        labor_hours: updates.labor_hours,
                        labor_cost: updates.labor_cost,
                        stone_cost: updates.stone_cost,
                        star_cost: updates.star_cost,
                        stone_setting_cost: updates.stone_setting_cost,
                        stone_min: updates.stone_min,
                        stone_max: updates.stone_max,
                        stone_size: updates.stone_size,
                        markup_percent: updates.markup_percent
                    };
                    if (activeVariant) {
                        activeVariant.material_cost = updates.material_cost;
                        activeVariant.gold_grams = updates.gold_grams;
                        activeVariant.sterling_grams = updates.sterling_grams;
                    }
                    originalData.set(baseCode, JSON.parse(JSON.stringify(updates)));
                } else {
                    throw new Error(result?.error || 'Save failed');
                }
            } catch (error) {
                showMessage(`Error saving ${baseCode}: ${error.message}`, 'error');
            }
        }
        
        function resetProduct(baseCode) {
            const original = originalData.get(baseCode);
            if (!original) return;
            
            const card = document.querySelector(`.product-card[data-base-code="${baseCode}"]`);
            if (!card) return;
            
            // Reset all fields to original values
            Object.keys(original).forEach(field => {
                const input = card.querySelector(`[data-field="${field}"]`);
                if (input) {
                    input.value = original[field];
                }
            });
            
            updateCalculations(baseCode);
            showMessage(`Reset ${baseCode} to original values`, 'success');
        }
        
        function showMessage(text, type) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `<div class="message ${type}">${text}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        // Auto-calculate labor cost when hours change
        document.addEventListener('input', (e) => {
            if (e.target.dataset.field === 'labor_hours') {
                const card = e.target.closest('.product-card');
                const laborRate = parseFloat(document.getElementById('laborRate').value);
                const hours = parseFloat(e.target.value) || 0;
                const laborCostField = card.querySelector('[data-field="labor_cost"]');
                laborCostField.value = (hours * laborRate).toFixed(2);
            }
        });
        
        // ========== New Product Functions ==========
        
        let variantCounter = 1;
        
        function showNewProductForm() {
            document.getElementById('newProductModal').classList.add('active');
            variantCounter = 1;
        }
        
        function closeNewProductForm() {
            document.getElementById('newProductModal').classList.remove('active');
            document.getElementById('newProductForm').reset();
            
            // Reset to single variant
            const container = document.getElementById('variantsContainer');
            container.innerHTML = `
                <div class="variant-row" data-variant-index="0">
                    <div class="field-group">
                        <label>Metal Type *</label>
                        <select name="variants[0][metal_type]" required>
                            <option value="">Select...</option>
                            <option value="10K">10K Gold</option>
                            <option value="14K">14K Gold</option>
                            <option value="14W">14K White Gold</option>
                            <option value="18K">18K Gold</option>
                            <option value="18W">18K White Gold</option>
                            <option value="STER">Sterling Silver</option>
                            <option value="PLAT">Platinum</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Material Cost ($)</label>
                        <input type="number" name="variants[0][material_cost]" value="0" step="0.01">
                    </div>
                    <div class="field-group">
                        <label>Gold Grams</label>
                        <input type="number" name="variants[0][gold_grams]" value="0" step="0.01">
                    </div>
                    <div class="field-group">
                        <label>Sterling Grams</label>
                        <input type="number" name="variants[0][sterling_grams]" value="0" step="0.01">
                    </div>
                    <div class="field-group">
                        <label>Metal Variant</label>
                        <input type="text" name="variants[0][metal_variant]" placeholder="Optional">
                    </div>
                    <div class="field-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn-remove" onclick="removeVariant(0)" 
                                style="visibility: hidden;">✕</button>
                    </div>
                </div>
            `;
            variantCounter = 1;
        }
        
        function addVariant() {
            const container = document.getElementById('variantsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'variant-row';
            newRow.dataset.variantIndex = variantCounter;
            
            newRow.innerHTML = `
                <div class="field-group">
                    <label>Metal Type *</label>
                    <select name="variants[${variantCounter}][metal_type]" required>
                        <option value="">Select...</option>
                        <option value="10K">10K Gold</option>
                        <option value="14K">14K Gold</option>
                        <option value="14W">14K White Gold</option>
                        <option value="18K">18K Gold</option>
                        <option value="18W">18K White Gold</option>
                        <option value="STER">Sterling Silver</option>
                        <option value="PLAT">Platinum</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Material Cost ($)</label>
                    <input type="number" name="variants[${variantCounter}][material_cost]" value="0" step="0.01">
                </div>
                <div class="field-group">
                    <label>Gold Grams</label>
                    <input type="number" name="variants[${variantCounter}][gold_grams]" value="0" step="0.01">
                </div>
                <div class="field-group">
                    <label>Sterling Grams</label>
                    <input type="number" name="variants[${variantCounter}][sterling_grams]" value="0" step="0.01">
                </div>
                <div class="field-group">
                    <label>Metal Variant</label>
                    <input type="text" name="variants[${variantCounter}][metal_variant]" placeholder="Optional">
                </div>
                <div class="field-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn-remove" onclick="removeVariant(${variantCounter})">✕</button>
                </div>
            `;
            
            container.appendChild(newRow);
            variantCounter++;
        }
        
        function removeVariant(index) {
            const row = document.querySelector(`[data-variant-index="${index}"]`);
            if (row) {
                row.remove();
            }
        }
        
        function autoCalcLabor() {
            const form = document.getElementById('newProductForm');
            const laborHours = parseFloat(form.elements['labor_hours'].value) || 0;
            const laborRate = parseFloat(document.getElementById('laborRate').value);
            document.getElementById('modalLaborCost').value = (laborHours * laborRate).toFixed(2);
        }
        
        async function createNewProduct(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            // Build product object
            const productData = {
                base_code: formData.get('base_code'),
                description: formData.get('description'),
                labor_hours: parseFloat(formData.get('labor_hours')) || 0,
                labor_cost: parseFloat(formData.get('labor_cost')) || 0,
                stone_cost: parseFloat(formData.get('stone_cost')) || 0,
                star_cost: parseFloat(formData.get('star_cost')) || 0,
                stone_setting_cost: parseFloat(formData.get('stone_setting_cost')) || 0,
                stone_min: parseInt(formData.get('stone_min')) || null,
                stone_max: parseInt(formData.get('stone_max')) || null,
                stone_size: parseFloat(formData.get('stone_size')) || null,
                markup_percent: parseFloat(formData.get('markup_percent')) || 0,
                sales_tax_percent: parseFloat(formData.get('sales_tax_percent')) || 0,
                category: formData.get('category') || '',
                group_code: formData.get('group_code') || '',
                info_1: formData.get('info_1') || '',
                info_2: formData.get('info_2') || '',
                variants: []
            };
            
            // Collect variants
            const variantRows = document.querySelectorAll('.variant-row');
            variantRows.forEach((row, index) => {
                const metalType = formData.get(`variants[${row.dataset.variantIndex}][metal_type]`);
                if (metalType) {
                    productData.variants.push({
                        metal_type: metalType,
                        metal_variant: formData.get(`variants[${row.dataset.variantIndex}][metal_variant]`) || null,
                        material_cost: parseFloat(formData.get(`variants[${row.dataset.variantIndex}][material_cost]`)) || 0,
                        gold_grams: parseFloat(formData.get(`variants[${row.dataset.variantIndex}][gold_grams]`)) || 0,
                        sterling_grams: parseFloat(formData.get(`variants[${row.dataset.variantIndex}][sterling_grams]`)) || 0
                    });
                }
            });
            
            if (productData.variants.length === 0) {
                showMessage('Please add at least one metal variant', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/create_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(productData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(`Successfully created ${productData.base_code} with ${productData.variants.length} variants`, 'success');
                    closeNewProductForm();
                    
                    // Reload products if search is active
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput.value) {
                        await searchProducts();
                    }
                } else {
                    throw new Error(result.error || 'Creation failed');
                }
            } catch (error) {
                showMessage(`Error creating product: ${error.message}`, 'error');
            }
        }
    </script>
</body>
</html>
