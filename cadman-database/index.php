<?php
// Session protection - require authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadman Manufacturing - Database Browser</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #434343 0%, #000000 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .load-section {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        
        .load-button {
            background: #10b981;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .load-button:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .load-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .status-grid {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .status-grid.visible {
            display: grid;
        }
        
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-card.loaded {
            border-left-color: #10b981;
        }
        
        .status-card.error {
            border-left-color: #ef4444;
        }
        
        .status-card h3 {
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-card .count {
            font-size: 2em;
            font-weight: bold;
            color: #1f2937;
        }
        
        .status-card .label {
            color: #9ca3af;
            font-size: 0.9em;
        }
        
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            overflow-x: auto;
        }
        
        .tab {
            padding: 15px 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1em;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .tab:hover {
            color: #1f2937;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
            padding: 30px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .search-box {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .price {
            color: #10b981;
            font-weight: 600;
        }
        
        .cost {
            color: #6b7280;
        }
        
        .metal-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .metal-14K { background: #fef3c7; color: #92400e; }
        .metal-18K { background: #fef08a; color: #713f12; }
        .metal-STER { background: #e0e7ff; color: #3730a3; }
        .metal-10K { background: #fde68a; color: #78350f; }
        
        .calculator-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .calc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .calc-field {
            display: flex;
            flex-direction: column;
        }
        
        .calc-field label {
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .calc-field input {
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        
        .calc-result {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 2px solid #10b981;
        }
        
        .calc-result h3 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .calc-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .calc-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .calc-line.total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 2px solid #1f2937;
            border-bottom: none;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        /* Sales Tab Styles */
        .sales-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .sales-tab {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sales-tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        .sales-tab:hover {
            color: #6366f1;
        }
        
        .sales-tab-content {
            display: none;
        }
        
        .sales-tab-content.active {
            display: block;
        }
        
        .search-options {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .search-row {
            display: flex;
            align-items: end;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .search-row:last-child {
            margin-bottom: 0;
            justify-content: center;
        }
        
        .search-field {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .search-field label {
            font-size: 0.9em;
            color: #374151;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .search-field input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            width: 100%;
            min-width: 300px;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
        }
        
        .info-value {
            color: #6b7280;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        /* Autocomplete Styles */
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-results.visible {
            display: block;
        }
        
        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.2s;
        }
        
        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: #f8f9fa;
        }
        
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        
        .autocomplete-primary {
            font-weight: 600;
            color: #1f2937;
        }
        
        .autocomplete-secondary {
            font-size: 0.9em;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        /* Cart Integration Styles */
        .cart-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .cart-summary {
            font-size: 18px;
            font-weight: 500;
        }
        
        .cart-actions {
            display: flex;
            gap: 10px;
        }
        
        .cart-items-list {
            min-height: 200px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #fff;
        }
        
        .cart-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .item-info {
            flex: 1;
            min-width: 0; /* Allow text to truncate */
        }
        
        .item-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .item-details {
            font-size: 14px;
            color: #666;
        }
        
        .item-code {
            font-family: 'Roboto Mono', monospace;
            font-weight: 600;
            color: #2563eb;
            margin-right: 10px;
        }
        
        .metal-type {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: #495057;
        }
        
        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: #28a745;
            margin: 0 20px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 20px;
        }
        
        .quantity-controls button {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .quantity-controls button:hover {
            background: #e9ecef;
        }
        
        .quantity {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .item-total {
            font-size: 16px;
            font-weight: 700;
            color: #dc3545;
            margin: 0 20px;
        }
        
        .item-actions button {
            width: 30px;
            height: 30px;
            border: none;
            background: #dc3545;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .item-actions button:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Cadman Manufacturing Database</h1>
            <p>Complete database access with AR12 pricing calculator</p>
            <div style="margin-top: 15px;">
                <a href="orders.php" style="background: rgba(16, 185, 129, 0.3); color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; display: inline-block; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.3);" onmouseover="this.style.background='rgba(16, 185, 129, 0.5)'" onmouseout="this.style.background='rgba(16, 185, 129, 0.3)'">
                    📝 Order Entry
                </a>
                <a href="../admin/" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; display: inline-block; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.3); margin-left: 10px;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    🛡️ Back to Admin
                </a>
                <a href="../" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; display: inline-block; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.3); margin-left: 10px;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    🏠 Main Site
                </a>
            </div>
        </header>
        
        <div class="load-section">
            <button class="load-button" id="loadButton" onclick="loadAllData()">
                Load Database
            </button>
            <button class="btn btn-secondary" style="margin-left: 10px;" onclick="loadAllData(true)">
                🔄 Force Refresh
            </button>
            <button class="btn btn-secondary" style="margin-left: 10px;" onclick="toggleStatus()">
                Show Status
            </button>
            <button class="btn btn-secondary" style="margin-left: 10px;" onclick="clearDatabaseCache()">
                🗑️ Clear Cache
            </button>
            
            <div id="cacheStatus" style="margin-top: 10px; padding: 10px; background: #e0f2fe; border-left: 4px solid #0277bd; display: none;">
                <strong>💾 Cache Status:</strong> <span id="cacheInfo">Checking...</span>
            </div>
            
            <div class="status-grid" id="statusGrid">
                <div class="status-card" id="status-inventory">
                    <h3>Inventory (IC)</h3>
                    <div class="count">-</div>
                    <div class="label">Products</div>
                </div>
                <div class="status-card" id="status-pricing">
                    <h3>Pricing (IP)</h3>
                    <div class="count">-</div>
                    <div class="label">Price Records</div>
                </div>
                <div class="status-card" id="status-customers">
                    <h3>Customers (AR)</h3>
                    <div class="count">-</div>
                    <div class="label">Customers</div>
                </div>
                <div class="status-card" id="status-sales">
                    <h3>Sales (SA)</h3>
                    <div class="count">-</div>
                    <div class="label">Transactions</div>
                </div>
                <div class="status-card" id="status-bom">
                    <h3>Bill of Materials (BM)</h3>
                    <div class="count">-</div>
                    <div class="label">Components</div>
                </div>
                <div class="status-card" id="status-settings">
                    <h3>System Settings (SY)</h3>
                    <div class="count">-</div>
                    <div class="label">Configuration</div>
                </div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('pricing')">💰 Pricing</button>
            <button class="tab" onclick="showTab('inventory')">📦 Inventory</button>
            <button class="tab" onclick="showTab('customers')">👥 Customers</button>
            <button class="tab" onclick="showTab('sales')">📈 Sales</button>
            <button class="tab" onclick="showTab('bom')">🔧 Bill of Materials</button>
            <button class="tab" onclick="showTab('calculator')">🧮 Price Calculator</button>
            <button class="tab cart-tab" onclick="showTab('cart')">
                🛒 Cart 
                <span id="cartBadge" class="cart-badge">0</span>
            </button>
        </div>
        
        <div id="pricing" class="tab-content active">
            <h2>Pricing Database (15,234 records)</h2>
            <input type="text" class="search-box" placeholder="Search by item code or description..." onkeyup="searchPricing(this.value)">
            
            <div class="button-group">
                <button class="btn btn-primary" onclick="showTopSellers()">Top 50 Sellers</button>
                <button class="btn btn-secondary" onclick="show14KItems()">14K Gold</button>
                <button class="btn btn-secondary" onclick="show18KItems()">18K Gold</button>
                <button class="btn btn-secondary" onclick="showSterlingItems()">Sterling Silver</button>
            </div>
            
            <div id="pricingResults"></div>
        </div>
        
        <div id="inventory" class="tab-content">
            <h2>Inventory Control (539 products)</h2>
            <input type="text" class="search-box" placeholder="Search inventory..." onkeyup="searchInventory(this.value)">
            <div id="inventoryResults"></div>
        </div>
        
        <div id="customers" class="tab-content">
            <h2>Customer Database (785 customers)</h2>
            <input type="text" class="search-box" placeholder="Search customers..." onkeyup="searchCustomers(this.value)">
            <div id="customerResults"></div>
        </div>
        
        <div id="sales" class="tab-content">
            <div class="sales-tabs">
                <button class="sales-tab active" onclick="showSalesTab('orders')">📋 Order Search</button>
                <button class="sales-tab" onclick="showSalesTab('transactions')">📈 Sales Transactions</button>
            </div>
            
            <!-- Order Search Section -->
            <div id="sales-orders" class="sales-tab-content active">
                <h2>Order Search & Management</h2>
                
                <div class="search-options">
                    <div class="search-row">
                        <div class="search-field">
                            <label>Search by Order ID or Invoice #:</label>
                            <input type="text" id="orderIdSearch" placeholder="Enter Order ID or invoice # (e.g. 123 or 621720)">
                            <button onclick="searchOrderById()" class="btn btn-primary">🔍 Find Order</button>
                        </div>
                    </div>
                    
                    <div class="search-row">
                        <div class="search-field">
                            <label>Search by Store/Customer Name:</label>
                            <div style="position: relative;">
                                <input type="text" id="storeNameSearch" placeholder="Enter business name or customer code" autocomplete="off">
                                <div id="storeSearchResults" class="autocomplete-results"></div>
                            </div>
                            <button onclick="searchOrdersByStore()" class="btn btn-primary">🏪 Find Orders</button>
                        </div>
                    </div>
                    
                    <div class="search-row">
                        <button onclick="loadRecentOrders()" class="btn btn-secondary">⏰ Show Recent Orders</button>
                        <button onclick="loadAllOrders()" class="btn btn-secondary">📋 Show All Orders</button>
                    </div>
                </div>
                
                <div id="orderSearchResults"></div>
            </div>
            
            <!-- Sales Transactions Section -->
            <div id="sales-transactions" class="sales-tab-content">
                <h2>Sales Transactions (2,779 sales)</h2>
                <input type="text" class="search-box" placeholder="Search sales..." onkeyup="searchSales(this.value)">
                <div id="salesResults"></div>
            </div>
        </div>
        
        <div id="bom" class="tab-content">
            <h2>Bill of Materials (29,684 components)</h2>
            <input type="text" class="search-box" placeholder="Search by item code..." onkeyup="searchBOM(this.value)">
            <div id="bomResults"></div>
        </div>
        
        <div id="calculator" class="tab-content">
            <h2>AR12 Pricing Calculator</h2>
            
            <div class="info-box">
                <strong>About this calculator:</strong> This replicates the AR12 COBOL pricing logic from the production system.
                It calculates prices based on material costs, labor, precious metals (gold/sterling), stones, markups, and tax.
            </div>
            
            <div class="calculator-section">
                <h3>Item Information</h3>
                <div class="calc-grid">
                    <div class="calc-field">
                        <label>Item Code</label>
                        <input type="text" id="calc-item" placeholder="Enter item code">
                    </div>
                    <div class="calc-field">
                        <label>Description</label>
                        <input type="text" id="calc-desc" readonly>
                    </div>
                </div>
                
                <h3 style="margin-top: 20px;">Costs & Stone Details</h3>
                <div class="calc-grid">
                    <div class="calc-field">
                        <label>Material Cost ($)</label>
                        <input type="number" id="calc-material" step="0.01" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Labour Cost ($)</label>
                        <input type="number" id="calc-labor-cost" step="0.01" value="0" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Standard Hours</label>
                        <input type="number" id="calc-labor-hours" step="0.001" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Material Metal</label>
                        <select id="calc-karat">
                            <option value="">None</option>
                            <option value="10K">10K</option>
                            <option value="14K">14K</option>
                            <option value="18K">18K</option>
                            <option value="STER">STER</option>
                            <option value="GF">GF</option>
                        </select>
                    </div>
                    <div class="calc-field">
                        <label>Min Stones</label>
                        <input type="number" id="calc-stone-min" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Gold Weight (grams)</label>
                        <input type="number" id="calc-gold-grams" step="0.001" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Gold Cost ($)</label>
                        <input type="number" id="calc-gold-cost" step="0.01" value="0" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Max Stones</label>
                        <input type="number" id="calc-stone-max" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Sterling Weight (grams)</label>
                        <input type="number" id="calc-sterling-grams" step="0.001" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Sterling Cost ($)</label>
                        <input type="number" id="calc-sterling-cost" step="0.01" value="0" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Stone Size</label>
                        <input type="number" id="calc-stone-size" step="0.01" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Stone Cost ($)</label>
                        <input type="number" id="calc-stone" step="0.01" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Star Cost ($)</label>
                        <input type="number" id="calc-star" step="0.01" value="0">
                    </div>
                    <div class="calc-field">
                        <label></label>
                    </div>
                    <div class="calc-field">
                        <label>Stone Set ($)</label>
                        <input type="number" id="calc-stone-set" step="0.01" value="0">
                    </div>
                    <div class="calc-field">
                        <label>Total Cost ($)</label>
                        <input type="number" id="calc-total-cost" step="0.01" value="0" readonly style="font-weight: bold;">
                    </div>
                    <div class="calc-field">
                        <label></label>
                    </div>
                </div>
                
                <h3 style="margin-top: 20px;">Markups & Tax</h3>
                <div class="calc-grid">
                    <div class="calc-field">
                        <label>Mark-Up (%)</label>
                        <input type="number" id="calc-markup" step="0.1" value="50">
                    </div>
                    <div class="calc-field">
                        <label>Base Margin (%) - Applied to All Items</label>
                        <input type="number" id="calc-base-margin" step="0.1" value="20">
                    </div>
                    <div class="calc-field">
                        <label>Selling Price ($)</label>
                        <input type="number" id="calc-selling-price" step="0.01" value="0" readonly style="font-weight: bold; font-size: 1.1em;">
                    </div>
                    <div class="calc-field">
                        <label>Sales Tax Rate (%) - Optional</label>
                        <input type="number" id="calc-tax" step="0.01" value="0">
                    </div>
                </div>
                
                <h3 style="margin-top: 20px;">Accounts & Classification</h3>
                <div class="calc-grid">
                    <div class="calc-field">
                        <label>Sales Account</label>
                        <input type="text" id="calc-sales-account" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Cost Account</label>
                        <input type="text" id="calc-cost-account" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Catalogue Page/Line</label>
                        <input type="text" id="calc-catalogue" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Analysis Page/Line</label>
                        <input type="text" id="calc-analysis" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Category</label>
                        <input type="text" id="calc-category" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Sales Group</label>
                        <input type="text" id="calc-sales-group" readonly>
                    </div>
                    <div class="calc-field">
                        <label>Sample Y/N?</label>
                        <input type="text" id="calc-sample" readonly>
                    </div>
                </div>
                
                <h3 style="margin-top: 20px;">Notes</h3>
                <div class="calc-grid">
                    <div class="calc-field">
                        <label>Note 1:</label>
                        <input type="text" id="calc-note1" readonly style="grid-column: span 2;">
                    </div>
                    <div class="calc-field">
                        <label>Note 2:</label>
                        <input type="text" id="calc-note2" readonly style="grid-column: span 2;">
                    </div>
                </div>
                
                <button class="load-button" style="margin-top: 20px;" onclick="calculatePrice()">
                    Calculate / Recalculate
                </button>
                
                <button class="load-button" style="margin-top: 10px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);" onclick="addToOrder()">
                    ➕ Add to Order Entry
                </button>
            </div>
            
            <div id="calcResultDiv" style="display: none;">
                <!-- Results will be inserted here -->
            </div>
        </div>
        
        <div id="cart" class="tab-content">
            <h2>Shopping Cart</h2>
            
            <div class="cart-header">
                <div class="cart-summary">
                    <span id="cartSummary">Cart is empty</span>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-success" onclick="openOrderSystem()" id="proceedToOrderBtn" disabled>
                        <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                    </button>
                    <button class="btn btn-outline" onclick="clearCart()" id="clearCartBtn" disabled>
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>
            </div>
            
            <div id="cartItems" class="cart-items-list">
                <!-- Cart items will be displayed here -->
            </div>
            
            <div class="cart-help" id="cartHelp">
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> How to use the shopping cart:</h4>
                    <ul>
                        <li><strong>Add items:</strong> Search for products in the Pricing tab and click "Add" buttons</li>
                        <li><strong>Manage quantities:</strong> Use +/- buttons in the cart</li>
                        <li><strong>Proceed to checkout:</strong> Click "Proceed to Checkout" when ready to purchase</li>
                        <li><strong>Secure storage:</strong> Your cart is saved securely in your session</li>
                        <li><strong>Multi-source:</strong> Items from database, calculator, and catalog can all be added</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/cart/cart.js"></script>
    <script src="js/data-loader.js"></script>
    <script src="js/pricing-calculator.js"></script>
    <script>
        let dataLoader = new DataLoader();
        let pricingCalc = new PricingCalculator();
        let currentTab = 'pricing';
        let initialDataLoadStarted = false;
        
        // Initialize cart integration when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCart();
        });
        
        // Show cache status on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCacheStatus(false, null);
        });

        // Auto-load once on first visit so users don't need manual refresh/clicks
        document.addEventListener('DOMContentLoaded', function() {
            if (!initialDataLoadStarted) {
                initialDataLoadStarted = true;
                loadAllData(false);
            }
        });
        
        // Make sure function is globally accessible
        window.loadAllData = async function loadAllData(forceRefresh = false) {
            const button = document.getElementById('loadButton');
            button.disabled = true;
            button.textContent = forceRefresh ? 'Force Refreshing...' : 'Loading...';
            
            try {
                const results = await dataLoader.loadAll('./', forceRefresh);
                
                // Update cache status
                updateCacheStatus(results.cached, results.cacheType);
                
                // Update status cards
                const status = dataLoader.getLoadStatus();
                updateStatusCard('inventory', status.inventory);
                updateStatusCard('pricing', status.pricing);
                updateStatusCard('customers', status.customers);
                updateStatusCard('sales', status.sales);
                updateStatusCard('bom', status.billOfMaterials);
                updateStatusCard('settings', status.systemSettings);
                
                // Load settings into pricing calculator
                if (status.systemSettings.loaded) {
                    pricingCalc.loadSystemSettings(status.systemSettings.settings);
                    pricingCalc.pricing = dataLoader.indexes.pricingByItem;
                    pricingCalc.inventory = dataLoader.indexes.inventoryByPart;
                    pricingCalc.billOfMaterials = dataLoader.indexes.bomByItem;
                    
                    // Recalculate all prices with current gold price and settings
                    dataLoader.recalculateAllPrices(pricingCalc);
                }
                
                const statusText = results.cached ? 
                    getCacheStatusText(results.cacheType, status.pricing.count) : 
                    `✅ Fresh data loaded (${status.pricing.count} records)`;
                    
                button.textContent = statusText;
                button.style.background = '#10b981';
                
                // Load initial data into current tab
                refreshCurrentTab();
                
            } catch (error) {
                button.textContent = 'Error Loading Data';
                button.style.background = '#ef4444';
                console.error('Load error:', error);
                alert('Error loading data from database: ' + error.message);
            }
        }
        
        window.toggleStatus = function toggleStatus() {
            const statusGrid = document.getElementById('statusGrid');
            statusGrid.classList.toggle('visible');
            const button = event.target;
            button.textContent = statusGrid.classList.contains('visible') ? 'Hide Status' : 'Show Status';
        }
        
        window.clearDatabaseCache = function clearDatabaseCache() {
            if (confirm('Clear database cache? Next load will fetch fresh data from server.')) {
                dataLoader.clearCache();
                updateCacheStatus(false, null);
                alert('Database cache cleared. Next load will be fresh from server.');
            }
        }
        
        function getCacheStatusText(cacheType, pricingCount) {
            // Handle case where function is called without parameters
            if (cacheType === undefined) {
                // Determine cache type from localStorage
                try {
                    const cached = localStorage.getItem(dataLoader.cacheKey);
                    if (cached) {
                        const data = JSON.parse(cached);
                        if (data.isPartial) cacheType = 'partial';
                        else if (data.isMinimal) cacheType = 'minimal';
                        else if (data.isEmergency) cacheType = 'emergency';
                        else cacheType = 'full';
                        
                        // Get pricing count
                        pricingCount = dataLoader.data.pricing.length || 0;
                    } else {
                        cacheType = 'none';
                        pricingCount = 0;
                    }
                } catch (e) {
                    cacheType = 'none';
                    pricingCount = 0;
                }
            }
            
            // Ensure pricingCount is a number
            if (typeof pricingCount !== 'number') {
                pricingCount = 0;
            }
            
            switch(cacheType) {
                case 'full':
                    return `✅ Full cache (${pricingCount.toLocaleString()} records)`;
                case 'partial':
                    return `📦 Smart cache (${pricingCount.toLocaleString()} top items)`;
                case 'minimal':
                    return `💫 Minimal cache (customers + settings)`;
                case 'emergency':
                    return `🆘 Basic cache (settings only)`;
                case 'none':
                    return `❌ No cache available`;
                default:
                    return `✅ Cached (${pricingCount.toLocaleString()} records)`;
            }
        }
        
        function updateCacheStatus(fromCache, cacheType = null) {
            const cacheStatus = document.getElementById('cacheStatus');
            const cacheInfo = document.getElementById('cacheInfo');
            
            if (dataLoader.hasCachedData()) {
                const cached = JSON.parse(localStorage.getItem(dataLoader.cacheKey));
                const age = Math.round((Date.now() - cached.timestamp) / (1000 * 60 * 60));
                
                if (fromCache) {
                    let cacheTypeText = '';
                    if (cached.isPartial) {
                        cacheTypeText = ' (Smart Cache - Top items only)';
                    } else if (cached.isMinimal) {
                        cacheTypeText = ' (Minimal Cache - Limited data)';
                    } else if (cached.isEmergency) {
                        cacheTypeText = ' (Emergency Cache - Settings only)';
                    }
                    cacheInfo.textContent = `Loaded from cache (${age}h old)${cacheTypeText}`;
                } else {
                    let saveTypeText = '';
                    if (cached.isPartial) {
                        saveTypeText = ' (Smart Cache due to size limits)';
                    } else if (cached.isMinimal) {
                        saveTypeText = ' (Minimal Cache due to storage limits)';
                    } else if (cached.isEmergency) {
                        saveTypeText = ' (Emergency Cache - Very limited storage)';
                    }
                    cacheInfo.textContent = `Fresh data cached${saveTypeText}`;
                }
                cacheStatus.style.display = 'block';
            } else {
                cacheInfo.textContent = 'No cached data available';
                cacheStatus.style.display = 'block';
            }
        }
        
        function updateStatusCard(type, status) {
            const card = document.getElementById(`status-${type}`);
            const count = card.querySelector('.count');
            
            if (status.loaded) {
                const value = type === 'settings' ? '✓ Loaded' : status.count.toLocaleString();
                count.textContent = value;
                card.classList.add('loaded');
            } else {
                count.textContent = '✗';
                card.classList.add('error');
            }
        }
        
        window.showTab = function showTab(tabName) {
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
            
            currentTab = tabName;
            refreshCurrentTab();
        }
        
        function refreshCurrentTab() {
            switch(currentTab) {
                case 'pricing':
                    showTopSellers();
                    break;
                case 'inventory':
                    displayInventory(dataLoader.data.inventory.slice(0, 100));
                    break;
                case 'customers':
                    displayCustomers(dataLoader.data.customers.slice(0, 100));
                    break;
                case 'sales':
                    displaySales(dataLoader.data.sales.slice(0, 100));
                    break;
                case 'bom':
                    // BOM requires search
                    document.getElementById('bomResults').innerHTML = '<p class="info-box">Enter an item code to see its bill of materials</p>';
                    break;
            }
        }
        
        // Integration with existing cart system
        let cadmanCart = null;
        
        function initializeCart() {
            // Wait for CadmanCart to be available, then initialize
            if (typeof CadmanCart !== 'undefined') {
                cadmanCart = new CadmanCart();
                console.log('✅ Integrated with existing Cadman Cart system');
                updateCartDisplay();
            } else {
                // Retry initialization after a short delay
                setTimeout(initializeCart, 100);
            }
        }
        
        // Add item to Order Entry page (not shopping cart)
        // Add item to order entry system (Net 30/Invoice workflow)
        function addToOrderEntry(itemCode) {
            try {
                // Look up the item from global map
                const item = window.pricingItemsMap[itemCode];
                if (!item) {
                    alert('Item data not found. Please try searching again.');
                    return;
                }
                
                // Get existing order items from localStorage
                let customItems = [];
                try {
                    const stored = localStorage.getItem('customOrderItems');
                    if (stored) {
                        customItems = JSON.parse(stored);
                    }
                } catch (e) {
                    console.warn('Could not load existing custom items:', e);
                    customItems = [];
                }
                
                // Format item for order entry with all necessary fields
                const orderItem = {
                    full_item_code: item.itemCode,
                    variant_description: item.description,
                    price: parseFloat(item.price || 0),
                    selling_price: parseFloat(item.price || 0),
                    cost: item.cost || 0,
                    gold_grams: item.goldGrams || 0,
                    sterling_grams: item.sterlingGrams || 0,
                    labor_hours: item.laborHours || 0,
                    stone_cost: item.stoneCost || 0,
                    stone_setting_cost: item.stoneSettingCost || 0,
                    star_cost: item.starCost || 0,
                    material_cost: item.materialCost || 0,
                    metal_hi: item.metalHi || item.metalType || '10K',
                    metal_type: item.metalType || '10K',
                    markup_percent: item.markup_percent || 50,
                    isCustom: false
                };
                
                customItems.push(orderItem);
                localStorage.setItem('customOrderItems', JSON.stringify(customItems));
                
                // Debug: verify storage
                console.log('Added item to localStorage:', item.itemCode);
                console.log('Total items in storage:', customItems.length);
                console.log('Storage contents:', JSON.parse(localStorage.getItem('customOrderItems')));
                
                // Show confirmation without leaving page
                const itemCount = customItems.length;
                showNotification(`Added ${item.itemCode} to order entry (${itemCount} item${itemCount !== 1 ? 's' : ''} total)`, 'success');
                
            } catch (error) {
                console.error('Failed to add to order entry:', error);
                showNotification('Failed to add item to order entry', 'error');
            }
        }
        
        // Show notification message
        function showNotification(message, type = 'info') {
            // Remove any existing notifications
            const existing = document.querySelector('.notification-toast');
            if (existing) {
                existing.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification-toast notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 500;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Keep cart function for shopping cart page (if needed elsewhere)
        function addToCart(itemCode, description, price, metalType = '') {
            if (!cadmanCart) {
                console.error('Cart system not initialized');
                return;
            }
            
            // Use existing cart system with cadman-database format
            const itemData = {
                collection: 'database',
                item_id: itemCode,
                name: description,
                price: parseFloat(price),
                metal_type: metalType,
                category: 'pricing_database',
                source: 'cadman_database'
            };
            
            cadmanCart.addItem(itemData)
                .then(response => {
                    if (response.success) {
                        showCartNotification(itemCode);
                        updateCartDisplay();
                    } else {
                        console.error('Failed to add item to cart:', response.message);
                        alert('Failed to add item to cart: ' + response.message);
                    }
                })
                .catch(error => {
                    console.error('Cart error:', error);
                    alert('Failed to add item to cart');
                });
        }
        
        function updateCartDisplay() {
            if (!cadmanCart) return;
            
            // Update cart count first
            cadmanCart.updateCartCount()
                .then(() => {
                    // Update cart badge using the cartCount property
                    const badge = document.getElementById('cartBadge');
                    if (badge) {
                        badge.textContent = cadmanCart.cartCount;
                        badge.style.display = cadmanCart.cartCount > 0 ? 'inline' : 'none';
                    }
                    
                    // Get full cart data for summary
                    return cadmanCart.getCart();
                })
                .then(cartResponse => {
                    const summary = document.getElementById('cartSummary');
                    if (summary && cartResponse && cartResponse.cart) {
                        const cartData = cartResponse.cart;
                        const itemCount = cartData.metadata?.item_count || 0;
                        const total = cartData.totals?.total || 0;
                        summary.innerHTML = itemCount > 0 
                            ? `${itemCount} items - $${total.toFixed(2)}` 
                            : 'Cart is empty';
                        
                        // Update action buttons
                        const proceedBtn = document.getElementById('proceedToOrderBtn');
                        const clearBtn = document.getElementById('clearCartBtn');
                        if (proceedBtn) proceedBtn.disabled = itemCount === 0;
                        if (clearBtn) clearBtn.disabled = itemCount === 0;
                        
                        // Update cart items display if on cart tab
                        if (currentTab === 'cart') {
                            displayCartItems(cartData);
                        }
                    }
                })
                .catch(error => {
                    console.warn('Failed to update cart display:', error);
                });
        }
        
        function displayCartItems(cartData) {
            const cartItemsDiv = document.getElementById('cartItems');
            const cartHelpDiv = document.getElementById('cartHelp');
            
            if (!cartData || !cartData.items || Object.keys(cartData.items).length === 0) {
                cartItemsDiv.innerHTML = '';
                cartHelpDiv.style.display = 'block';
                return;
            }
            
            cartHelpDiv.style.display = 'none';
            
            const itemsHtml = Object.entries(cartData.items).map(([cartItemId, item]) => `
                <div class="cart-item" data-cart-id="${cartItemId}">
                    <div class="item-info">
                        <h4>${item.name}</h4>
                        <div class="item-details">
                            <span class="item-code">${item.item_id}</span>
                            ${item.metal_type ? `<span class="metal-type">${item.metal_type}</span>` : ''}
                        </div>
                    </div>
                    <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
                    <div class="quantity-controls">
                        <button class="btn btn-sm" onclick="updateCartQuantity('${cartItemId}', ${item.quantity - 1})">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="btn btn-sm" onclick="updateCartQuantity('${cartItemId}', ${item.quantity + 1})">+</button>
                    </div>
                    <div class="item-total">$${(item.price * item.quantity).toFixed(2)}</div>
                    <div class="item-actions">
                        <button class="btn btn-sm btn-danger" onclick="removeCartItem('${cartItemId}')">×</button>
                    </div>
                </div>
            `).join('');
            
            cartItemsDiv.innerHTML = itemsHtml;
        }
        
        function updateCartQuantity(cartItemId, newQuantity) {
            if (!cadmanCart) return;
            
            cadmanCart.updateQuantity(cartItemId, newQuantity)
                .then(response => {
                    if (response.success) {
                        updateCartDisplay();
                    } else {
                        console.error('Failed to update quantity:', response.message);
                    }
                })
                .catch(error => {
                    console.error('Update quantity error:', error);
                });
        }
        
        function removeCartItem(cartItemId) {
            if (!cadmanCart) return;
            
            cadmanCart.removeFromCart(cartItemId)
                .then(response => {
                    if (response.success) {
                        updateCartDisplay();
                    } else {
                        console.error('Failed to remove item:', response.message);
                    }
                })
                .catch(error => {
                    console.error('Remove item error:', error);
                });
        }
        
        function clearCart() {
            if (!cadmanCart) return;
            
            cadmanCart.clearCart()
                .then(response => {
                    if (response && response.success) {
                        updateCartDisplay();
                    } else if (response) {
                        console.error('Failed to clear cart:', response.message);
                    }
                    // Note: clearCart() has its own confirmation dialog
                })
                .catch(error => {
                    console.error('Clear cart error:', error);
                });
        }
        
        function showCartNotification(itemCode) {
            // Create temporary notification
            const notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i> 
                ${itemCode} added to cart
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                z-index: 10000;
                font-weight: 500;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
            `;
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function viewCatalog(itemCode) {
            // Open the catalog detail page for this item
            const catalogUrl = `/unified_detail.php?item=${encodeURIComponent(itemCode)}`;
            window.open(catalogUrl, '_blank');
        }
        
        function openOrderSystem() {
            // Open the existing checkout system
            window.open('/cart/checkout.php', '_blank');
        }
        
        // Global storage for pricing items (for addToOrderEntry)
        window.pricingItemsMap = window.pricingItemsMap || {};
        
        function displayPricing(items) {
            // Store all items in global map for easy lookup
            items.forEach(item => {
                window.pricingItemsMap[item.itemCode] = item;
            });
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Metal</th>
                            <th>Price</th>
                            <th>12-Mo Sales</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => {
                            const metalType = item.metalHi ? `${item.metalHi}${item.metalLo || ''}` : '';
                            const metalClass = item.metalHi ? `metal-${item.metalHi}${item.metalLo}` : '';
                            const salesData = item.totalSales || item.salesData?.total_quantity || 0;
                            
                            return `
                                <tr>
                                    <td>
                                        <strong class="item-code" onclick="selectItem('${item.itemCode}')">${item.itemCode}</strong>
                                        ${item.baseCode && item.baseCode !== item.itemCode ? 
                                            `<br><small class="base-code">Base: ${item.baseCode}</small>` : ''
                                        }
                                    </td>
                                    <td class="description">
                                        ${item.description}
                                        ${item.category ? `<br><small class="category">${item.category}</small>` : ''}
                                    </td>
                                    <td>
                                        ${metalType ? `<span class="metal-badge ${metalClass}">${metalType}</span>` : '-'}
                                        ${item.goldGrams > 0 ? `<br><small>${item.goldGrams}g Au</small>` : ''}
                                        ${item.sterlingGrams > 0 ? `<br><small>${item.sterlingGrams}g Ag</small>` : ''}
                                    </td>
                                    <td class="price">
                                        <strong>$${parseFloat(item.price || 0).toFixed(2)}</strong>
                                        ${item.laborHours > 0 ? `<br><small>${item.laborHours}h labor</small>` : ''}
                                    </td>
                                    <td class="sales-data">
                                        ${salesData}
                                        ${item.salesData?.last_sale_date ? 
                                            `<br><small>Last: ${item.salesData.last_sale_date}</small>` : ''
                                        }
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-primary btn-sm add-to-order" 
                                                onclick="addToOrderEntry('${item.itemCode}')"
                                                title="Add to Order Entry">
                                            <i class="fas fa-plus"></i> Add to Order
                                        </button>
                                        ${item.pdfFile ? 
                                            `<br><button class="btn btn-outline btn-sm mt-1" 
                                                        onclick="viewCatalog('${item.itemCode}')" 
                                                        title="View in Catalog">
                                                <i class="fas fa-book"></i>
                                            </button>` : ''
                                        }
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('pricingResults').innerHTML = html;
        }
        
        function displayInventory(items) {
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>Part #</th>
                            <th>Description</th>
                            <th>Class</th>
                            <th>Cost</th>
                            <th>On Hand</th>
                            <th>Available</th>
                            <th>On Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr>
                                <td><strong>${item.partNumber}</strong></td>
                                <td>${item.description}</td>
                                <td>${item.class}</td>
                                <td class="cost">$${item.cost.toFixed(2)}</td>
                                <td>${item.onHand}</td>
                                <td>${item.available}</td>
                                <td>${item.onOrder}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('inventoryResults').innerHTML = html;
        }
        
        function displayCustomers(customers) {
            const formatMoney = (value) => {
                const numeric = Number(value);
                return Number.isFinite(numeric) ? numeric.toFixed(2) : '0.00';
            };

            if (!Array.isArray(customers) || customers.length === 0) {
                document.getElementById('customerResults').innerHTML = '<p class="info-box">No customers found. Try a different search term.</p>';
                return;
            }

            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>City, State</th>
                            <th>Balance</th>
                            <th>Credit Limit</th>
                            <th>YTD Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${customers.map(c => `
                            <tr>
                                <td><strong>${c.customerCode}</strong></td>
                                <td>${c.name}</td>
                                <td>${c.city}, ${c.state}</td>
                                <td class="cost">$${formatMoney(c.balance)}</td>
                                <td>$${formatMoney(c.creditLimit)}</td>
                                <td class="price">$${formatMoney(c.ytdSales)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('customerResults').innerHTML = html;
        }
        
        function displaySales(sales) {
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Extended</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sales.map(s => `
                            <tr>
                                <td><strong>${s.invoiceNumber}</strong></td>
                                <td>${s.invoiceDate}</td>
                                <td>${s.customerCode}</td>
                                <td>${s.itemCode}</td>
                                <td>${s.quantity}</td>
                                <td>$${s.price.toFixed(2)}</td>
                                <td class="price">$${s.extendedPrice.toFixed(2)}</td>
                                <td class="cost">$${s.profit.toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('salesResults').innerHTML = html;
        }
        
        function displayBOM(itemCode, components) {
            const html = `
                <h3>Components for ${itemCode}</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Part #</th>
                            <th>Description</th>
                            <th>Class</th>
                            <th>Metal Type</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Extended Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${components.map(c => {
                            const extCost = c.quantity * c.cost;
                            return `
                                <tr>
                                    <td><strong>${c.partNumber}</strong></td>
                                    <td>${c.description}</td>
                                    <td>${c.class}</td>
                                    <td><span class="metal-badge metal-${c.metalType}">${c.metalType}</span></td>
                                    <td>${c.quantity}</td>
                                    <td class="cost">$${c.cost.toFixed(2)}</td>
                                    <td class="price">$${extCost.toFixed(2)}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('bomResults').innerHTML = html;
        }
        
        async function searchPricing(query) {
            if (!query || query.length < 2) {
                showTopSellers();
                return;
            }
            
            // Check if we have Smart Cache or limited cache - use live search
            const cacheStatus = getCacheStatusText();
            const isLimitedCache = cacheStatus.includes('Smart') || cacheStatus.includes('Minimal') || cacheStatus.includes('Emergency');
            
            if (isLimitedCache) {
                console.log('🔍 Using live database search due to limited cache');
                
                // Show loading indicator
                document.getElementById('pricingResults').innerHTML = `
                    <div class="search-loading">
                        <i class="fas fa-spinner fa-spin"></i> Searching database...
                    </div>
                `;
                
                try {
                    // Use live search from database
                    const results = await dataLoader.searchItemsLive(query, 100);
                    displayPricing(results);
                    
                    if (results.length === 0) {
                        document.getElementById('pricingResults').innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <p>No items found for "${query}"</p>
                                <p><small>Searched entire database (${dataLoader.getFullPricingCount()} items)</small></p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Live search failed:', error);
                    // Fallback to cached search
                    const cachedResults = dataLoader.searchItems(query);
                    displayPricing(cachedResults.slice(0, 100));
                    
                    if (cachedResults.length === 0) {
                        document.getElementById('pricingResults').innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <p>No items found in cached data for "${query}"</p>
                                <p><small>Live search failed - showing cached results only (${dataLoader.data.pricing.length} items)</small></p>
                            </div>
                        `;
                    }
                }
            } else {
                // Use cached search for full cache
                console.log('🔍 Using cached search (full cache available)');
                const results = dataLoader.searchItems(query);
                displayPricing(results.slice(0, 100));
                
                if (results.length === 0) {
                    document.getElementById('pricingResults').innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <p>No items found for "${query}"</p>
                        </div>
                    `;
                }
            }
        }
        
        function searchInventory(query) {
            if (!query || query.length < 2) {
                displayInventory(dataLoader.data.inventory.slice(0, 100));
                return;
            }
            const lowerQuery = query.toLowerCase();
            const results = dataLoader.data.inventory.filter(item =>
                item.partNumber.toLowerCase().includes(lowerQuery) ||
                item.description.toLowerCase().includes(lowerQuery)
            );
            displayInventory(results.slice(0, 100));
        }
        
        function searchCustomers(query) {
            if (!query || query.length < 2) {
                displayCustomers(dataLoader.data.customers.slice(0, 100));
                return;
            }
            const results = dataLoader.searchCustomers(query);
            displayCustomers(results.slice(0, 100));
        }
        
        function searchSales(query) {
            if (!query || query.length < 2) {
                displaySales(dataLoader.data.sales.slice(0, 100));
                return;
            }
            const lowerQuery = query.toLowerCase();
            const results = dataLoader.data.sales.filter(s =>
                s.itemCode.toLowerCase().includes(lowerQuery) ||
                s.customerCode.toLowerCase().includes(lowerQuery) ||
                s.invoiceNumber.toLowerCase().includes(lowerQuery)
            );
            displaySales(results.slice(0, 100));
        }
        
        function searchBOM(query) {
            if (!query || query.length < 2) {
                document.getElementById('bomResults').innerHTML = '<p class="info-box">Enter an item code to see its bill of materials</p>';
                return;
            }
            const components = dataLoader.getBOM(query.toUpperCase());
            if (components.length === 0) {
                document.getElementById('bomResults').innerHTML = `<p class="error-box">No bill of materials found for ${query}</p>`;
            } else {
                displayBOM(query.toUpperCase(), components);
            }
        }
        
        function showTopSellers() {
            const topItems = dataLoader.getTopSellers(50);
            displayPricing(topItems);
        }
        
        function show14KItems() {
            const items = dataLoader.getItemsByMetal('14K');
            displayPricing(items.slice(0, 100));
        }
        
        function show18KItems() {
            const items = dataLoader.getItemsByMetal('18K');
            displayPricing(items.slice(0, 100));
        }
        
        function showSterlingItems() {
            const items = dataLoader.getItemsByMetal('STER');
            displayPricing(items.slice(0, 100));
        }
        
        function selectItem(itemCode) {
            // Switch to calculator tab and load item
            showTab('calculator');
            loadItemIntoCalculator(itemCode);
        }
        
        function loadItemIntoCalculator(itemCode) {
            const item = dataLoader.getItem(itemCode);
            if (!item) return;
            
            // Calculate labor hours from labor cost if not already set
            let laborHours = item.laborHours || 0;
            if (laborHours === 0 && item.laborCost > 0) {
                const laborRate = dataLoader.systemSettings?.laborRate || 28;
                laborHours = item.laborCost / laborRate;
            }
            
            document.getElementById('calc-item').value = item.itemCode;
            document.getElementById('calc-desc').value = item.description;
            document.getElementById('calc-material').value = item.materialCost || 0;
            document.getElementById('calc-labor-hours').value = laborHours.toFixed(3);
            document.getElementById('calc-gold-grams').value = item.goldGrams || 0;
            document.getElementById('calc-karat').value = item.metalHi + (item.metalLo || '');
            document.getElementById('calc-sterling-grams').value = item.sterlingGrams || 0;
            document.getElementById('calc-stone').value = item.stoneCost || 0;
            document.getElementById('calc-star').value = item.starCost || 0;
            document.getElementById('calc-stone-set').value = item.stoneSettingCost || 0;
            document.getElementById('calc-markup').value = item.markup || 50;
            document.getElementById('calc-base-margin').value = 20.0; // Default base margin for all items
            document.getElementById('calc-tax').value = item.salesTax || 0;
            
            // Stone details
            document.getElementById('calc-stone-min').value = 0;
            document.getElementById('calc-stone-max').value = 0;
            document.getElementById('calc-stone-size').value = 0;
            
            // Accounts & classification
            document.getElementById('calc-sales-account').value = item.salesAccount || '';
            document.getElementById('calc-cost-account').value = item.costAccount || '';
            document.getElementById('calc-catalogue').value = item.category || '';
            document.getElementById('calc-analysis').value = item.group || '';
            document.getElementById('calc-category').value = item.category || '';
            document.getElementById('calc-sales-group').value = item.group || '';
            document.getElementById('calc-sample').value = '';
            
            // Notes
            document.getElementById('calc-note1').value = item.info1 || '';
            document.getElementById('calc-note2').value = item.info2 || '';
            
            // Trigger calculation to show all values
            calculatePrice();
        }
        
        function calculatePrice() {
            // Get values
            const itemCode = document.getElementById('calc-item').value;
            const material = parseFloat(document.getElementById('calc-material').value) || 0;
            const laborHours = parseFloat(document.getElementById('calc-labor-hours').value) || 0;
            const goldGrams = parseFloat(document.getElementById('calc-gold-grams').value) || 0;
            const karat = document.getElementById('calc-karat').value;
            const sterlingGrams = parseFloat(document.getElementById('calc-sterling-grams').value) || 0;
            const stoneCost = parseFloat(document.getElementById('calc-stone').value) || 0;
            const starCost = parseFloat(document.getElementById('calc-star').value) || 0;
            const stoneSetCost = parseFloat(document.getElementById('calc-stone-set').value) || 0;
            const markup = parseFloat(document.getElementById('calc-markup').value) || 0;
            const baseMargin = parseFloat(document.getElementById('calc-base-margin').value) || 0;
            const tax = parseFloat(document.getElementById('calc-tax').value) || 0;
            
            // Calculate costs
            const goldCost = pricingCalc.calculateGoldCost(goldGrams, karat);
            const sterlingCost = pricingCalc.calculateSterlingCost(sterlingGrams);
            const laborCost = pricingCalc.calculateLaborCost(laborHours);
            const totalCost = material + laborCost + goldCost + sterlingCost + stoneCost + starCost + stoneSetCost;
            
            // Apply item markup, then base margin, then optional tax
            let sellingPrice = totalCost * (1 + markup / 100) * (1 + baseMargin / 100);
            if (tax > 0) {
                sellingPrice = sellingPrice * (1 + tax / 100);
            }
            const preRoundedPrice = sellingPrice;
            sellingPrice = pricingCalc.roundToQuarter(sellingPrice);
            console.log(`Pre-rounded: $${preRoundedPrice.toFixed(5)}, Rounded: $${sellingPrice.toFixed(2)}`);
            
            // Update calculated fields
            document.getElementById('calc-labor-cost').value = laborCost.toFixed(2);
            document.getElementById('calc-gold-cost').value = goldCost.toFixed(2);
            document.getElementById('calc-sterling-cost').value = sterlingCost.toFixed(2);
            document.getElementById('calc-total-cost').value = totalCost.toFixed(2);
            document.getElementById('calc-selling-price').value = sellingPrice.toFixed(2);
            
            const profit = sellingPrice - totalCost;
            const margin = totalCost > 0 ? ((profit / sellingPrice) * 100).toFixed(2) : 0;
            
            // Display results summary
            const resultHTML = `
                <div class="calc-result">
                    <h3>Pricing Calculation Results</h3>
                    <div class="calc-breakdown">
                        <div>
                            <div class="calc-line">
                                <span>Material Cost:</span>
                                <span>$${material.toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>Labor (${laborHours.toFixed(2)} hrs @ $${pricingCalc.settings.labourRate}/hr):</span>
                                <span>$${laborCost.toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>Gold (${goldGrams.toFixed(2)}g ${karat}):</span>
                                <span>$${goldCost.toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>Sterling (${sterlingGrams.toFixed(2)}g):</span>
                                <span>$${sterlingCost.toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>Stone Cost:</span>
                                <span>$${stoneCost.toFixed(2)}</span>
                            </div>
                            <div class="calc-line total">
                                <span>Total Cost:</span>
                                <span>$${totalCost.toFixed(2)}</span>
                            </div>
                        </div>
                        <div>
                            <div class="calc-line">
                                <span>Base Cost:</span>
                                <span>$${totalCost.toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>+ Item Markup (${markup}%):</span>
                                <span>$${(totalCost * (markup / 100)).toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>+ Market Markup (${pricingCalc.settings.marketMarkup}%):</span>
                                <span>$${((totalCost * (1 + markup/100)) * (pricingCalc.settings.marketMarkup / 100)).toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>+ Sales Tax (${tax}%):</span>
                                <span>$${((totalCost * (1 + markup/100) * (1 + pricingCalc.settings.marketMarkup/100)) * (tax / 100)).toFixed(2)}</span>
                            </div>
                            <div class="calc-line">
                                <span>Rounded to $0.25:</span>
                                <span>--</span>
                            </div>
                            <div class="calc-line total">
                                <span style="color: #10b981;">Selling Price:</span>
                                <span style="color: #10b981;">$${sellingPrice.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="info-box" style="margin-top: 20px;">
                        <strong>Profit:</strong> $${profit.toFixed(2)} (${margin}% margin) | 
                        <strong>Gold Price:</strong> $${pricingCalc.settings.goldPrice}/oz | 
                        <strong>Labor Rate:</strong> $${pricingCalc.settings.labourRate}/hr
                    </div>
                </div>
            `;
            
            document.getElementById('calcResultDiv').innerHTML = resultHTML;
            document.getElementById('calcResultDiv').style.display = 'block';
        }
        
        function addToOrder() {
            // Get current calculated values
            const itemCode = document.getElementById('calc-item').value || 'CUSTOM-' + Date.now();
            const description = document.getElementById('calc-desc').value || 'Custom Item';
            const sellingPrice = parseFloat(document.getElementById('calc-selling-price').value) || 0;
            const totalCost = parseFloat(document.getElementById('calc-total-cost').value) || 0;
            
            // Validate that price has been calculated
            if (sellingPrice === 0) {
                alert('Please calculate the price first before adding to order.');
                return;
            }
            
            // Get all calculation details for the custom item
            const customItem = {
                full_item_code: itemCode,
                variant_description: description,
                selling_price: sellingPrice,
                material_cost: parseFloat(document.getElementById('calc-material').value) || 0,
                labor_hours: parseFloat(document.getElementById('calc-labor-hours').value) || 0,
                gold_grams: parseFloat(document.getElementById('calc-gold-grams').value) || 0,
                metal_hi: document.getElementById('calc-karat').value,
                sterling_grams: parseFloat(document.getElementById('calc-sterling-grams').value) || 0,
                stone_cost: parseFloat(document.getElementById('calc-stone').value) || 0,
                star_cost: parseFloat(document.getElementById('calc-star').value) || 0,
                stone_setting_cost: parseFloat(document.getElementById('calc-stone-set').value) || 0,
                markup_percent: parseFloat(document.getElementById('calc-markup').value) || 0,
                cost: totalCost,
                isCustom: true,
                addedAt: new Date().toISOString()
            };
            
            // Get existing custom items from localStorage
            let customItems = JSON.parse(localStorage.getItem('customOrderItems') || '[]');
            
            // Add new item
            customItems.push(customItem);
            
            // Save back to localStorage
            localStorage.setItem('customOrderItems', JSON.stringify(customItems));
            
            // Redirect to order entry immediately
            window.location.href = 'orders.php';
        }
        
        window.showSalesTab = function showSalesTab(tabName) {
            // Hide all sales tab contents
            document.querySelectorAll('.sales-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all sales tabs
            document.querySelectorAll('.sales-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(`sales-${tabName}`).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        // Global order search functions
        window.searchOrderById = async function searchOrderById() {
            const searchValue = (document.getElementById('orderIdSearch').value || '').trim();
            if (!searchValue) {
                alert('Please enter an Order ID or Invoice #');
                return;
            }
            
            try {
                const params = new URLSearchParams({
                    order_id: searchValue,
                    invoice_number: searchValue
                });
                const response = await fetch(`api/get_order.php?${params.toString()}`);
                const data = await response.json();
                
                if (data.success && data.order) {
                    displaySingleOrder(data.order, data.lineItems);
                } else {
                    displayOrderSearchError(`No order found for: ${searchValue}`);
                }
            } catch (error) {
                displayOrderSearchError('Error searching for order: ' + error.message);
            }
        }
        
        window.searchOrdersByStore = async function searchOrdersByStore() {
            const storeName = document.getElementById('storeNameSearch').value;
            if (!storeName || storeName.length < 2) {
                alert('Please enter at least 2 characters for store name search');
                return;
            }
            
            try {
                const response = await fetch(`api/search_orders.php?store_name=${encodeURIComponent(storeName)}`);
                const data = await response.json();
                
                if (data.success && data.orders.length > 0) {
                    displayOrderList(data.orders, `Orders for: ${storeName}`);
                } else {
                    displayOrderSearchError(`No orders found for store: ${storeName}`);
                }
            } catch (error) {
                displayOrderSearchError('Error searching orders: ' + error.message);
            }
        }
        
        window.loadRecentOrders = async function loadRecentOrders() {
            try {
                const response = await fetch('api/get_recent_orders.php');
                const data = await response.json();
                
                if (data.success && data.orders.length > 0) {
                    displayOrderList(data.orders, 'Recent Orders');
                } else {
                    displayOrderSearchError('No recent orders found');
                }
            } catch (error) {
                displayOrderSearchError('Error loading recent orders: ' + error.message);
            }
        }
        
        window.loadAllOrders = async function loadAllOrders() {
            try {
                document.getElementById('orderSearchResults').innerHTML = '<p style="color:#6b7280;">Loading all invoices...</p>';
                const response = await fetch('api/get_all_orders.php?limit=2000');
                const data = await response.json();
                
                if (data.success && data.orders.length > 0) {
                    displayOrderList(data.orders, `All Invoices (Jan 2025 – Mar 2026)`);
                } else {
                    displayOrderSearchError('No orders found in database');
                }
            } catch (error) {
                displayOrderSearchError('Error loading orders: ' + error.message);
            }
        }
        
        function displaySingleOrder(order, lineItems) {
            const html = `
                <div class="order-card">
                    <div class="order-header">
                        <h3>Order #${order.order_number}</h3>
                        <span class="status-badge status-${order.payment_status.toLowerCase()}">${order.payment_status}</span>
                    </div>
                    <div class="order-info">
                        <div class="info-item">
                            <span class="info-label">Order ID:</span>
                            <span class="info-value">${order.order_id}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date:</span>
                            <span class="info-value">${new Date(order.order_date).toLocaleDateString()}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Customer:</span>
                            <span class="info-value">${order.business_name || order.customer_name}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Customer Code:</span>
                            <span class="info-value">${order.customer_code || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Terms:</span>
                            <span class="info-value">${order.terms}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total:</span>
                            <span class="info-value price">$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                    ${lineItems && lineItems.length > 0 ? `
                    <div style="margin-top: 20px;">
                        <h4>Order Items (${lineItems.length})</h4>
                        <table style="width: 100%; margin-top: 10px;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 8px; text-align: left;">Item Code</th>
                                    <th style="padding: 8px; text-align: left;">Description</th>
                                    <th style="padding: 8px; text-align: left;">Qty</th>
                                    <th style="padding: 8px; text-align: left;">Unit Price</th>
                                    <th style="padding: 8px; text-align: left;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${lineItems.map(item => `
                                <tr>
                                    <td style="padding: 8px;">${item.item_code}</td>
                                    <td style="padding: 8px;">${item.description}</td>
                                    <td style="padding: 8px;">${item.quantity}</td>
                                    <td style="padding: 8px;">$${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td style="padding: 8px; font-weight: 600;">$${parseFloat(item.extended_price).toFixed(2)}</td>
                                </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : ''}
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="view_order.php?order_id=${order.order_id}" class="btn btn-primary" target="_blank">View Full Order</a>
                        ${order.payment_status === 'PENDING' && (order.terms === 'COD' || order.terms === 'CIA') ? `
                        <a href="order_payment.php?order_id=${order.order_id}" class="btn btn-secondary" target="_blank">Process Payment</a>
                        ` : ''}
                    </div>
                </div>
            `;
            document.getElementById('orderSearchResults').innerHTML = html;
        }
        
        // Paginated order list — call with page=0 to reset
        let _orderListData = [];
        let _orderListTitle = '';
        const ORDER_PAGE_SIZE = 100;

        function displayOrderList(orders, title, page = 0) {
            _orderListData  = orders;
            _orderListTitle = title;
            renderOrderPage(page);
        }

        function renderOrderPage(page) {
            const orders     = _orderListData;
            const totalPages = Math.ceil(orders.length / ORDER_PAGE_SIZE);
            const start      = page * ORDER_PAGE_SIZE;
            const pageOrders = orders.slice(start, start + ORDER_PAGE_SIZE);
            const totalAmount = orders.reduce((sum, o) => sum + parseFloat(o.total_amount), 0);

            const rows = pageOrders.map(order => `
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px;">${order.order_number}</td>
                    <td style="padding: 10px;">${order.business_name || order.customer_code || '—'}</td>
                    <td style="padding: 10px;">${order.customer_code || ''}</td>
                    <td style="padding: 10px;">${new Date(order.order_date).toLocaleDateString('en-CA')}</td>
                    <td style="padding: 10px; font-weight: 600; color: #059669;">$${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td style="padding: 10px;">
                        <button onclick="loadOrderDetails(${order.order_id})" class="btn btn-primary" style="padding: 4px 10px; font-size: 0.82em;">View</button>
                    </td>
                </tr>
            `).join('');

            const pagination = totalPages > 1 ? `
                <div style="display:flex; align-items:center; gap:10px; margin-top:14px; flex-wrap:wrap;">
                    <button onclick="renderOrderPage(${page - 1})" class="btn btn-secondary" style="padding:5px 14px;" ${page === 0 ? 'disabled' : ''}>← Prev</button>
                    <span style="color:#6b7280; font-size:0.9em;">Page ${page + 1} of ${totalPages} &nbsp;·&nbsp; showing ${start + 1}–${Math.min(start + ORDER_PAGE_SIZE, orders.length)} of ${orders.length}</span>
                    <button onclick="renderOrderPage(${page + 1})" class="btn btn-secondary" style="padding:5px 14px;" ${page >= totalPages - 1 ? 'disabled' : ''}>Next →</button>
                </div>
            ` : '';

            const html = `
                <div style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:8px;">
                    <div>
                        <h3 style="margin:0;">${_orderListTitle}</h3>
                        <p style="color:#6b7280; margin:4px 0 0;">${orders.length} invoices &nbsp;·&nbsp; Grand total: <strong>$${totalAmount.toFixed(2)}</strong></p>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.93em;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Invoice #</th>
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Customer</th>
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Code</th>
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Date</th>
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Total</th>
                                <th style="padding:10px; text-align:left; border-bottom:2px solid #e9ecef;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                ${pagination}
            `;
            document.getElementById('orderSearchResults').innerHTML = html;
        }
        
        function displayOrderSearchError(message) {
            const html = `
                <div class="error-box">
                    <strong>No Results Found</strong>
                    <p>${message}</p>
                </div>
            `;
            document.getElementById('orderSearchResults').innerHTML = html;
        }
        
        async function loadOrderDetails(orderId) {
            try {
                const response = await fetch(`api/get_order.php?order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.order) {
                    displaySingleOrder(data.order, data.lineItems);
                } else {
                    displayOrderSearchError(`Could not load order ${orderId}`);
                }
            } catch (error) {
                displayOrderSearchError('Error loading order details: ' + error.message);
            }
        }
        
        // Store name autocomplete functionality
        let storeSearchTimeout;
        
        // Initialize store autocomplete when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing store autocomplete...');
            const storeInput = document.getElementById('storeNameSearch');
            const storeResults = document.getElementById('storeSearchResults');
            
            console.log('Store input found:', !!storeInput);
            console.log('Store results div found:', !!storeResults);
            
            if (storeInput) {
                storeInput.addEventListener('input', function() {
                    clearTimeout(storeSearchTimeout);
                    const searchTerm = this.value.trim();
                    console.log('Store search term:', searchTerm);
                    
                    if (searchTerm.length < 2) {
                        storeResults.classList.remove('visible');
                        return;
                    }
                    
                    storeSearchTimeout = setTimeout(async () => {
                        try {
                            console.log('Fetching customers for:', searchTerm);
                            const response = await fetch(`api/get_customers.php?q=${encodeURIComponent(searchTerm)}&limit=10`);
                            const data = await response.json();
                            console.log('API response:', data);
                            
                            if (data.success && data.data && data.data.length > 0) {
                                console.log('Found customers:', data.data.length);
                                displayStoreAutocomplete(data.data);
                            } else {
                                console.log('No customers found');
                                storeResults.classList.remove('visible');
                            }
                        } catch (error) {
                            console.error('Error searching stores:', error);
                            storeResults.classList.remove('visible');
                        }
                    }, 300);
                });
                
                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if (!storeInput.contains(e.target) && !storeResults.contains(e.target)) {
                        storeResults.classList.remove('visible');
                    }
                });
                
                // Handle keyboard navigation
                storeInput.addEventListener('keydown', function(e) {
                    const items = storeResults.querySelectorAll('.autocomplete-item');
                    let activeItem = storeResults.querySelector('.autocomplete-item.active');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (activeItem) {
                            activeItem.classList.remove('active');
                            const next = activeItem.nextElementSibling;
                            if (next) {
                                next.classList.add('active');
                                activeItem = next;
                            } else {
                                items[0].classList.add('active');
                                activeItem = items[0];
                            }
                        } else if (items.length > 0) {
                            items[0].classList.add('active');
                            activeItem = items[0];
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (activeItem) {
                            activeItem.classList.remove('active');
                            const prev = activeItem.previousElementSibling;
                            if (prev) {
                                prev.classList.add('active');
                                activeItem = prev;
                            } else {
                                items[items.length - 1].classList.add('active');
                                activeItem = items[items.length - 1];
                            }
                        } else if (items.length > 0) {
                            items[items.length - 1].classList.add('active');
                            activeItem = items[items.length - 1];
                        }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (activeItem) {
                            const customerCode = activeItem.getAttribute('data-customer-code');
                            const businessName = activeItem.getAttribute('data-business-name');
                            selectStore(customerCode, businessName);
                        } else {
                            searchOrdersByStore();
                        }
                    } else if (e.key === 'Escape') {
                        storeResults.classList.remove('visible');
                    }
                });
            }
        });
        
        function displayStoreAutocomplete(customers) {
            const storeResults = document.getElementById('storeSearchResults');
            const html = customers.map((customer, index) => {
                const businessName = customer.business_name || customer.customer_code || 'Unknown';
                const customerCode = customer.customer_code || '';
                const location = [];
                if (customer.city) location.push(customer.city);
                if (customer.province) location.push(customer.province);
                const locationStr = location.join(', ');
                
                return `
                    <div class="autocomplete-item" data-customer-code="${customerCode}" data-business-name="${businessName}" data-index="${index}">
                        <div class="autocomplete-primary">${businessName}</div>
                        <div class="autocomplete-secondary">
                            ${customerCode ? `Code: ${customerCode}` : ''}
                            ${locationStr ? ` • ${locationStr}` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            storeResults.innerHTML = html;
            storeResults.classList.add('visible');
            
            // Add click handlers to each item
            storeResults.querySelectorAll('.autocomplete-item').forEach(item => {
                item.addEventListener('click', function() {
                    const customerCode = this.getAttribute('data-customer-code');
                    const businessName = this.getAttribute('data-business-name');
                    selectStore(customerCode, businessName);
                });
            });
        }
        
        function selectStore(customerCode, businessName) {
            const storeInput = document.getElementById('storeNameSearch');
            const storeResults = document.getElementById('storeSearchResults');
            
            storeInput.value = businessName || customerCode;
            storeResults.classList.remove('visible');
            
            // Automatically trigger search
            setTimeout(() => {
                searchOrdersByStore();
            }, 100);
        }
    </script>
</body>
</html>
