<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Entry - Cadman Manufacturing</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        header h1 {
            font-size: 2em;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .header-link {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .header-link:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.6);
        }
        
        .order-section {
            padding: 30px;
        }
        
        .order-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .field-group {
            display: flex;
            flex-direction: column;
        }
        
        .field-group label {
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .field-group input,
        .field-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .field-group input:focus,
        .field-group select:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .customer-info {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ffc107;
        }
        
        .customer-info h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        
        .customer-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            color: #856404;
        }
        
        .line-items {
            margin-bottom: 30px;
        }
        
        .line-items h3 {
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .line-item {
            display: grid;
            grid-template-columns: 1.6fr 2.4fr 0.8fr 1fr 0.9fr 1fr 110px;
            gap: 12px;
            padding: 16px;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            align-items: end;
        }
        
        .line-item input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1em;
            width: 100%;
        }
        
        .line-item input:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .line-item button {
            padding: 8px 12px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9em;
            min-width: 35px;
        }
        
        .line-item button:hover {
            background: #374151;
            transform: translateY(-1px);
        }
        
        .line-item button:last-child {
            background: #ef4444;
        }
        
        .line-item button:last-child:hover {
            background: #dc2626;
        }
        
        .line-item .button-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* Responsive design for smaller screens */
        @media (max-width: 1200px) {
            .line-item {
                grid-template-columns: 1.5fr 2fr 80px 80px 80px 80px 100px;
                gap: 10px;
                font-size: 0.9em;
            }
            
            .line-item input {
                padding: 8px;
            }
        }
        
        @media (max-width: 900px) {
            .line-item {
                grid-template-columns: 1fr 2fr 70px 70px 70px 70px 80px;
                gap: 8px;
                font-size: 0.85em;
            }
        }
        
        .add-line-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .add-line-btn:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .order-totals {
            background: #e7f3ff;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 1.1em;
        }
        
        .total-row.grand-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #6366f1;
            font-weight: bold;
            font-size: 1.3em;
            color: #6366f1;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .loading-banner {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            display: none;
        }

        .loading-banner.visible {
            display: block;
        }

        .line-item-preview {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 90px;
            padding: 8px;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            background: #fafafa;
        }

        .line-item-preview img,
        .line-item-preview iframe {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
        }

        .line-item-preview .preview-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 0.8em;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
        }

        .line-item-preview .preview-caption {
            color: #6b7280;
            font-size: 0.85em;
            line-height: 1.4;
        }

        .media-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .media-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 90vw;
            max-height: 90vh;
            padding: 16px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .media-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            border: none;
            background: #ef4444;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
        }

        .media-modal-content img,
        .media-modal-content iframe {
            max-width: 100%;
            max-height: 78vh;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
        }
        
        /* Calculator Edit Modal */
        .calc-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            backdrop-filter: blur(3px);
        }
        
        .calc-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .calc-modal h2 {
            margin: 0 0 25px 0;
            color: #374151;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        
        .calc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .calc-field {
            display: flex;
            flex-direction: column;
        }
        
        .calc-field label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #374151;
        }
        
        .calc-field input,
        .calc-field select {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .calc-field input:focus,
        .calc-field select:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .calc-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .calc-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calc-btn.primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }
        
        .calc-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        
        .calc-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .calc-btn.secondary:hover {
            background: #e5e7eb;
        }
        
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
            display: block;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
            display: block;
        }
        
        .status-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
            display: block;
        }
        
        .search-dropdown {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #6366f1;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-results.visible {
            display: block;
        }
        
        .search-result-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-result-item:hover {
            background: #f3f4f6;
        }
        
        .item-code {
            font-weight: bold;
            color: #6366f1;
        }
        
        .item-desc {
            color: #6b7280;
            font-size: 0.9em;
        }
        
        .item-price {
            color: #10b981;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📋 Order Entry System</h1>
            <div class="header-actions">
                <a href="index.php" class="header-link">➕ Add Custom Item</a>
                <a href="./index.php" class="header-link">Admin</a>
            </div>
        </header>
        
        <div class="order-section">
            <div id="statusMessage" class="status-message"></div>
            <div id="loadingBanner" class="loading-banner visible">Loading order data…</div>
            
            <!-- Order Header -->
            <div class="order-header">
                <div class="field-group">
                    <label>Order Date</label>
                    <input type="date" id="orderDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="field-group">
                    <label>Customer / Code</label>
                    <div class="search-dropdown">
                        <input type="text" id="customerCode" placeholder="Search customer name or code...">
                        <div class="search-results" id="customerSearchResults"></div>
                    </div>
                </div>
                <div class="field-group">
                    <label>PO Number</label>
                    <input type="text" id="poNumber" placeholder="Optional PO number">
                </div>
                <div class="field-group">
                    <label>Payment Terms</label>
                    <select id="terms">
                        <option value="NET30">Net 30 Days (Invoice)</option>
                        <option value="NET60">Net 60 Days (Invoice)</option>
                        <option value="COD">COD - Cash on Delivery (Payment Required)</option>
                        <option value="CIA">Cash in Advance (Payment Required)</option>
                    </select>
                </div>
            </div>
            
            <!-- Customer Info Display -->
            <div id="customerInfo" class="customer-info" style="display: none;">
                <h3>Customer Information</h3>
                <div class="customer-details">
                    <div><strong>Name:</strong> <span id="custName"></span></div>
                    <div><strong>Phone:</strong> <span id="custPhone"></span></div>
                    <div><strong>Address:</strong> <span id="custAddress"></span></div>
                    <div><strong>Credit Limit:</strong> <span id="custCredit"></span></div>
                </div>
            </div>
            
            <!-- Line Items -->
            <div class="line-items">
                <h3>Order Items</h3>
                <div id="lineItemsContainer">
                    <div class="line-item" data-line="1">
                        <div class="field-group">
                            <label>Item Code</label>
                            <div class="search-dropdown">
                                <input type="text" class="item-code-input" placeholder="Search item...">
                                <div class="search-results"></div>
                            </div>
                        </div>
                        <div class="field-group">
                            <label>Description</label>
                            <input type="text" class="item-desc-input" readonly>
                        </div>
                        <div class="field-group">
                            <label>Quantity</label>
                            <input type="number" class="item-qty-input" value="1" min="1">
                        </div>
                        <div class="field-group">
                            <label>Price</label>
                            <input type="number" class="item-price-input" step="0.01" readonly>
                        </div>
                        <div class="field-group">
                            <label>Discount %</label>
                            <input type="number" class="item-disc-input" value="0" min="0" max="100" step="0.1">
                        </div>
                        <div class="field-group">
                            <label>Extended</label>
                            <input type="number" class="item-ext-input" readonly>
                        </div>
                        <div class="field-group">
                            <label>&nbsp;</label>
                            <div class="button-group">
                                <button onclick="editLine(1)" title="Edit with Calculator">✎</button>
                                <button onclick="removeLine(1)">✕</button>
                            </div>
                        </div>
                        <div class="field-group" style="grid-column: 1 / -1;">
                            <div class="line-item-preview" data-media-url="" data-media-type="none"></div>
                        </div>
                    </div>
                </div>
                <button class="add-line-btn" onclick="addLine()">+ Add Line Item</button>
            </div>
            
            <!-- Order Totals -->
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">$0.00</span>
                </div>
                <div class="total-row">
                    <span>Discount:</span>
                    <span id="discount">-$0.00</span>
                </div>
                <div class="total-row">
                    <span>Tax (0%):</span>
                    <span id="tax">$0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span id="grandTotal">$0.00</span>
                </div>
            </div>
            
            <!-- Session Status Info -->
            <div id="sessionStatus" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #6366f1; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong>📝 Session Data Status</strong>
                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8em;" onclick="toggleSessionStatus()">Hide</button>
                </div>
                <div id="sessionInfo" style="font-size: 0.9em; color: #6b7280;">
                    <div>💼 Active Order Lines: <span id="orderLinesCount">0</span></div>
                    <div>🧮 Calculator Items: <span id="customItemsCount">0</span></div>
                    <div style="margin-top: 10px;">
                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8em; margin-right: 10px;" onclick="clearSessionData()">🗑️ Clear All Session Data</button>
                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8em;" onclick="clearCustomItems()">🧮 Clear Calculator Items Only</button>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-info" onclick="showSessionStatus()" style="background: #6366f1;">📊 Session Info</button>
                <button class="btn btn-secondary" onclick="clearOrder()">Clear</button>
                <button class="btn btn-danger" onclick="processOrder()">Process Order</button>
                <button class="btn btn-primary" onclick="saveOrder()">Save Order</button>
            </div>
        </div>
    </div>
    
    <!-- Calculator Edit Modal -->
    <div id="calcModal" class="calc-modal">
        <div class="calc-modal-content">
            <h2>Edit Item Calculator</h2>
            <div class="calc-grid">
                <div class="calc-field">
                    <label>Item Code</label>
                    <input type="text" id="modal-item-code" readonly>
                </div>
                <div class="calc-field">
                    <label>Description</label>
                    <input type="text" id="modal-description">
                </div>
                <div class="calc-field">
                    <label>Quantity</label>
                    <input type="number" id="modal-quantity" min="1" value="1">
                </div>
            </div>
            
            <h3 style="margin: 25px 0 15px 0; color: #374151;">Material Costs</h3>
            <div class="calc-grid">
                <div class="calc-field">
                    <label>Gold Weight (grams)</label>
                    <input type="number" id="modal-gold-grams" step="0.1" value="0">
                </div>
                <div class="calc-field">
                    <label>Gold Karat</label>
                    <select id="modal-karat">
                        <option value="10K">10K (41.67%)</option>
                        <option value="14K" selected>14K (58.33%)</option>
                        <option value="18K">18K (75%)</option>
                    </select>
                </div>
                <div class="calc-field">
                    <label>Sterling Weight (grams)</label>
                    <input type="number" id="modal-sterling-grams" step="0.1" value="0">
                </div>
            </div>
            
            <div class="calc-grid">
                <div class="calc-field">
                    <label>Labor Hours</label>
                    <input type="number" id="modal-labor-hours" step="0.1" value="0">
                </div>
                <div class="calc-field">
                    <label>Stone Cost ($)</label>
                    <input type="number" id="modal-stone-cost" step="0.01" value="0">
                </div>
                <div class="calc-field">
                    <label>Stone Setting Cost ($)</label>
                    <input type="number" id="modal-stone-setting" step="0.01" value="0">
                </div>
            </div>
            
            <div class="calc-grid">
                <div class="calc-field">
                    <label>Star/Special Cost ($)</label>
                    <input type="number" id="modal-star-cost" step="0.01" value="0">
                </div>
                <div class="calc-field">
                    <label>Markup %</label>
                    <input type="number" id="modal-markup" step="0.1" value="0" min="0" max="100">
                </div>
                <div class="calc-field">
                    <label>Final Price ($)</label>
                    <input type="number" id="modal-final-price" step="0.01" readonly style="background: #f0f9ff; font-weight: bold;">
                </div>
            </div>

            <div class="calc-grid">
                <div class="calc-field">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="modal-engraving" style="width: auto; margin: 0;">
                        Engraving
                    </label>
                </div>
                <div class="calc-field">
                    <label>Engraving Text</label>
                    <input type="text" id="modal-engraving-text" placeholder="Enter engraving text">
                </div>
                <div class="calc-field">
                    <label>Engraving Cost ($)</label>
                    <input type="number" id="modal-engraving-cost" step="0.01" value="0">
                </div>
            </div>

            <div class="calc-grid">
                <div class="calc-field" style="grid-column: 1 / -1;">
                    <label>Line Note</label>
                    <textarea id="modal-line-note" rows="3" placeholder="Add a note for this item"></textarea>
                </div>
            </div>
            
            <div class="calc-buttons">
                <button type="button" class="calc-btn secondary" onclick="closeCalcModal()">Cancel</button>
                <button type="button" class="calc-btn primary" onclick="recalculateModal()">Recalculate</button>
                <button type="button" class="calc-btn primary" onclick="saveModalChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <div id="lineMediaModal" class="media-modal">
        <div class="media-modal-content">
            <button type="button" class="media-modal-close" onclick="closeLineMediaModal()">×</button>
            <div id="lineMediaModalBody"></div>
        </div>
    </div>
    
    <script>
        let lineCounter = 1;
        let productData = [];
        let customCatalog = [];
        let searchCache = new Map();
        let activeSearchController = null;
        let orderLines = []; // Persistent order lines array
        let systemSettings = {
            goldPrice: 7300,
            laborRate: 28,
            sterlingGF: 130,
            baseMargin: 20
        };
        
        // Save order lines to localStorage
        function saveOrderLines() {
            localStorage.setItem('currentOrderLines', JSON.stringify(orderLines));
        }
        
        // Load order lines from localStorage
        function loadOrderLines() {
            try {
                const savedLines = JSON.parse(localStorage.getItem('currentOrderLines') || '[]');
                orderLines = Array.isArray(savedLines) ? savedLines : [];
                
                if (orderLines.length > 0) {
                    const container = document.getElementById('lineItemsContainer');
                    if (!container) return;

                    const existingLines = container.querySelectorAll('.line-item');
                    existingLines.forEach((line, index) => {
                        if (index > 0) line.remove();
                    });

                    const firstLine = container.querySelector('.line-item[data-line="1"]') || addLine();
                    if (!firstLine) return;

                    lineCounter = Math.max(...orderLines.map(line => Number(line.lineNumber) || 1)) + 1;
                    
                    orderLines.forEach((line, index) => {
                        let domLine;
                        if (index === 0) {
                            domLine = firstLine;
                            domLine.dataset.line = line.lineNumber || 1;
                        } else {
                            addLine();
                            domLine = container.querySelector(`[data-line="${lineCounter - 1}"]`);
                            if (domLine) {
                                domLine.dataset.line = line.lineNumber || lineCounter - 1;
                            }
                        }

                        if (!domLine) return;

                        domLine.querySelector('.item-code-input').value = line.itemCode || '';
                        domLine.querySelector('.item-desc-input').value = line.description || '';
                        domLine.querySelector('.item-qty-input').value = line.quantity || 1;
                        domLine.querySelector('.item-price-input').value = Number(line.sellingPrice || 0).toFixed(2);
                        domLine.querySelector('.item-disc-input').value = line.discount || 0;
                        renderLineItemPreview(domLine, line.previewMedia || line);
                        calculateLineTotal(line.lineNumber || 1);
                    });
                    
                    console.log(`Restored ${orderLines.length} order lines from localStorage`);
                    calculateTotals();
                }
            } catch (error) {
                console.error('Error loading order lines:', error);
                orderLines = [];
            }
        }
        
        // Add item to order lines (both array and DOM)
        async function addToOrderLines(product) {
            console.log('addToOrderLines called with:', product?.full_item_code || product?.item_code || product?.description);
            
            const sellingPrice = product?.price || await calculateSellingPrice(product);
            const cost = product?.cost || 0;
            const newLine = addLine();
            const lineNumber = newLine ? Number(newLine.dataset.line) : lineCounter;
            
            const orderLine = {
                lineNumber: lineNumber,
                itemCode: product.full_item_code,
                description: product.variant_description || product.description,
                quantity: 1,
                sellingPrice: sellingPrice,
                cost: cost,
                discount: 0,
                isCustom: product.isCustom || false,
                engraving_requested: false,
                engraving_text: '',
                engraving_cost: 0,
                line_note: '',
                gold_grams: product.gold_grams || product.goldGrams || 0,
                metal_hi: product.metal_hi || product.metalHi || '14K',
                sterling_grams: product.sterling_grams || product.sterlingGrams || 0,
                labor_hours: product.labor_hours || product.laborHours || 0,
                stone_cost: product.stone_cost || product.stoneCost || 0,
                stone_setting_cost: product.stone_setting_cost || product.stoneSettingCost || 0,
                star_cost: product.star_cost || product.starCost || 0,
                markup_percent: product.markup_percent || 0,
                previewMedia: getPreviewMedia(product)
            };
            orderLines.push(orderLine);
            
            const domLine = document.querySelector(`[data-line="${lineNumber}"]`);
            if (domLine) {
                domLine.querySelector('.item-code-input').value = orderLine.itemCode;
                domLine.querySelector('.item-desc-input').value = orderLine.description;
                domLine.querySelector('.item-qty-input').value = orderLine.quantity;
                domLine.querySelector('.item-price-input').value = orderLine.sellingPrice.toFixed(2);
                domLine.querySelector('.item-disc-input').value = orderLine.discount;
                renderLineItemPreview(domLine, orderLine.previewMedia || orderLine);
                
                if (orderLine.isCustom) {
                    const codeInput = domLine.querySelector('.item-code-input');
                    codeInput.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    codeInput.style.color = 'white';
                    codeInput.style.fontWeight = 'bold';
                    codeInput.setAttribute('title', 'Custom item from calculator');
                }
                
                calculateLineTotal(lineNumber);
            }
            
            saveOrderLines();
            console.log(`✓ Completed adding item ${orderLine.itemCode} to order lines`);
        }
        
        // Clear order lines and localStorage
        function clearOrderLines() {
            orderLines = [];
            localStorage.removeItem('currentOrderLines');
            console.log('Cleared order lines from memory and localStorage');
        }
        
        // Load system settings
        async function loadSystemSettings() {
            try {
                const response = await fetch('./api/get_settings.php');
                const text = await response.text();
                let result = null;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('Invalid JSON from settings endpoint:', text);
                    return;
                }

                if (result.success && result.settings) {
                    systemSettings.goldPrice = result.settings.gold_price?.value || 7300;
                    systemSettings.laborRate = result.settings.labor_rate?.value || 28;
                    systemSettings.sterlingGF = result.settings.sterling_gf?.value || 130;
                    systemSettings.baseMargin = result.settings.base_margin?.value || 20;
                    console.log('Loaded system settings:', systemSettings);
                }
            } catch (error) {
                console.error('Error loading system settings:', error);
            }
        }
        
        // Load custom items without preloading the full catalog
        async function loadProducts() {
            try {
                await loadCustomItems();
            } catch (error) {
                console.error('Error loading catalog helpers:', error);
            }
            return [];
        }
        
        // Load custom items from calculator
        async function loadCustomItems() {
            try {
                const stored = localStorage.getItem('customOrderItems');
                console.log('loadCustomItems - Raw localStorage:', stored);
                
                const customItems = JSON.parse(stored || '[]');
                console.log('loadCustomItems - Parsed items:', customItems);
                console.log('loadCustomItems - Item count:', customItems.length);
                
                if (Array.isArray(customItems) && customItems.length > 0) {
                    customCatalog = customItems.map(item => ({
                        ...item,
                        isCustom: true,
                        variant_description: item.variant_description || item.description || item.full_item_code || ''
                    }));
                    console.log(`Loaded ${customCatalog.length} custom items from calculator`);
                    
                    const addPromises = customCatalog.map((item, index) => {
                        console.log(`Processing item ${index + 1}/${customCatalog.length}:`, item.full_item_code);
                        if (typeof addToOrderLines === 'function') {
                            return addToOrderLines(item);
                        }
                        console.error('addToOrderLines function not found! Check script loading order.');
                        return Promise.resolve();
                    });
                    
                    await Promise.all(addPromises);
                    console.log(`Auto-added ${customCatalog.length} custom items to order`);
                    localStorage.removeItem('customOrderItems');
                    console.log('Cleared customOrderItems from localStorage after successful load');
                } else {
                    customCatalog = [];
                }
            } catch (error) {
                console.error('Error loading custom items:', error);
            }
        }
        
        // Clear custom items (call when order is completed)
        function clearCustomItems() {
            localStorage.removeItem('customOrderItems');
            showMessage('Custom items cleared', 'success');
        }
        
        // Calculate selling price using centralized PricingCalculator
        async function calculateSellingPrice(product) {
            // If this is a custom item from calculator, use its pre-calculated price
            if (product.isCustom && product.selling_price) {
                return product.selling_price;
            }
            
            // Use live API calculation for consistency with search
            try {
                const itemCode = product.full_item_code || product.itemCode;
                if (!itemCode) return 0;
                
                const response = await fetch(`./api/get_item.php?item_code=${encodeURIComponent(itemCode)}`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    return data.data.price || 0;
                }
            } catch (error) {
                console.warn('Live pricing failed, using fallback:', error);
            }
            
            // Fallback to local calculation if API fails
            return calculateSellingPriceFallback(product);
        }
        
        function calculatePriceFromComponents({
            goldGrams = 0,
            metalType = '14K',
            sterlingGrams = 0,
            laborHours = 0,
            stoneCost = 0,
            stoneSettingCost = 0,
            starCost = 0,
            materialCost = 0,
            markupPercent = 50
        } = {}) {
            let goldCost = 0;
            if (goldGrams > 0) {
                const pricePerGram = systemSettings.goldPrice / 31.1035;
                let pureGoldGrams = goldGrams;

                if (metalType.includes('10K')) {
                    pureGoldGrams *= (10/24);
                } else if (metalType.includes('14K')) {
                    pureGoldGrams *= (14/24);
                } else if (metalType.includes('18K')) {
                    pureGoldGrams *= (18/24);
                }

                goldCost = pricePerGram * pureGoldGrams;
            }

            const sterlingCost = sterlingGrams > 0
                ? sterlingGrams * systemSettings.sterlingGF * 0.03215076
                : 0;

            const laborCost = (laborHours || 0) * systemSettings.laborRate;

            const totalCost =
                (materialCost || 0) +
                laborCost +
                goldCost +
                sterlingCost +
                (stoneCost || 0) +
                (starCost || 0) +
                (stoneSettingCost || 0);

            const sellingPrice = totalCost * (1 + (markupPercent || 0) / 100) * (1 + systemSettings.baseMargin / 100);
            return roundToQuarter(sellingPrice);
        }

        // Fallback pricing calculation (should match PricingCalculator logic)
        function calculateSellingPriceFallback(product) {
            return calculatePriceFromComponents({
                goldGrams: product.gold_grams || product.goldGrams || 0,
                metalType: product.metal_hi || product.metalHi || product.metal_type || '14K',
                sterlingGrams: product.sterling_grams || product.sterlingGrams || 0,
                laborHours: product.labor_hours || product.laborHours || 0,
                stoneCost: product.stone_cost || product.stoneCost || 0,
                stoneSettingCost: product.stone_setting_cost || product.stoneSettingCost || 0,
                starCost: product.star_cost || product.starCost || 0,
                materialCost: product.material_cost || 0,
                markupPercent: product.markup_percent || 50
            });
        }
        
        // Round to nearest quarter dollar ($0.25) - matches AR12 logic
        function roundToQuarter(amount) {
            // Work in 0.001 cent precision (100000x) to avoid rounding errors
            const hundredThousandths = Math.round(amount * 100000);
            const dollars = Math.floor(hundredThousandths / 100000);
            const fractionalPart = hundredThousandths % 100000;
            
            // Convert to cents with high precision (100x = cents, but we have 100000x)
            const preciseCents = fractionalPart / 1000;  // Now in cents with 3 decimal precision
            
            // OE27 COBOL rounding logic: >75 → $1, >50 → .75, >25 → .50, else → .25
            if (preciseCents > 75) {
                return dollars + 1.00;
            } else if (preciseCents > 50) {
                return dollars + 0.75;
            } else if (preciseCents > 25) {
                return dollars + 0.50;
            } else {
                return dollars + 0.25;
            }
        }
        
        // Search products with debounce on typing, Enter works immediately
        function setupSearch(container) {
            if (!container) return;

            const input = container.querySelector('.item-code-input');
            const resultsDiv = container.querySelector('.search-results');
            if (!input || !resultsDiv) return;

            const lineNumber = container.dataset.line || '0';
            let searchTimeout;
            
            const clearResults = () => {
                resultsDiv.classList.remove('visible');
                resultsDiv._matches = [];
            };
            
            console.log('setupSearch called for container:', lineNumber);
            
            input.addEventListener('input', () => {
                clearTimeout(searchTimeout);

                const search = input.value.trim();
                if (search.length < 2) {
                    clearResults();
                    return;
                }

                resultsDiv.innerHTML = '<div class="search-result-item">Searching…</div>';
                resultsDiv.classList.add('visible');
                resultsDiv._matches = [];
                
                searchTimeout = setTimeout(async () => {
                    await searchProducts(search, resultsDiv, lineNumber);
                }, 300);
            });

            input.addEventListener('keydown', async (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                
                clearTimeout(searchTimeout);

                const search = input.value.trim();
                if (search.length < 2) {
                    clearResults();
                    return;
                }

                await searchProducts(search, resultsDiv, lineNumber);
            });
            
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) {
                    resultsDiv.classList.remove('visible');
                }
            });
        }

        async function searchProducts(searchText, resultsDiv, lineNumber) {
            const term = searchText.trim().toLowerCase();
            if (!term) return;

            const cacheKey = `${term}:${lineNumber}`;
            if (searchCache.has(cacheKey)) {
                renderSearchResults(resultsDiv, searchCache.get(cacheKey), lineNumber);
                return;
            }

            if (activeSearchController) {
                activeSearchController.abort();
            }

            const controller = new AbortController();
            activeSearchController = controller;
            resultsDiv.innerHTML = '<div class="search-result-item">Searching…</div>';
            resultsDiv.classList.add('visible');

            try {
                const response = await fetch(`./api/get_products.php?search=${encodeURIComponent(term)}&limit=10`, {
                    signal: controller.signal
                });
                const text = await response.text();
                let payload = null;
                try {
                    payload = JSON.parse(text);
                } catch (parseError) {
                    console.error('Invalid JSON from search endpoint:', text);
                    payload = { success: false, error: 'Invalid JSON' };
                }

                const remoteResults = Array.isArray(payload?.data) ? payload.data : [];
                const merged = [];
                const seen = new Set();

                const pushItem = (item) => {
                    if (!item) return;
                    const code = item.full_item_code || item.itemCode || '';
                    if (!code || seen.has(code)) return;
                    seen.add(code);
                    merged.push(item);
                };

                remoteResults.forEach(pushItem);
                customCatalog.forEach(pushItem);

                const matches = merged
                    .filter(item => {
                        const code = (item.full_item_code || item.itemCode || '').toLowerCase();
                        const description = (item.variant_description || item.description || '').toLowerCase();
                        return code.includes(term) || description.includes(term);
                    })
                    .sort((a, b) => {
                        if (a.isCustom && !b.isCustom) return -1;
                        if (!a.isCustom && b.isCustom) return 1;
                        return 0;
                    })
                    .slice(0, 10);

                searchCache.set(cacheKey, matches);
                renderSearchResults(resultsDiv, matches, lineNumber);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search failed:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item">No matches found</div>';
                    resultsDiv.classList.add('visible');
                    resultsDiv._matches = [];
                }
            } finally {
                if (activeSearchController === controller) {
                    activeSearchController = null;
                }
            }
        }

        function normalizeSearchItem(item) {
            if (!item || typeof item !== 'object') return item;

            const normalized = { ...item };
            normalized.itemCode = normalized.itemCode || normalized.full_item_code || normalized.item_code || '';
            normalized.full_item_code = normalized.full_item_code || normalized.itemCode || normalized.item_code || '';
            normalized.variant_description = normalized.variant_description || normalized.description || normalized.base_description || normalized.full_item_code || '';
            normalized.description = normalized.description || normalized.variant_description || normalized.base_description || normalized.full_item_code || '';
            normalized.price = normalized.price ?? normalized.selling_price ?? normalized.sell_price ?? 0;
            normalized.selling_price = normalized.selling_price ?? normalized.price ?? 0;
            normalized.cost = normalized.cost ?? normalized.total_cost ?? 0;
            normalized.total_cost = normalized.total_cost ?? normalized.cost ?? 0;
            normalized.gold_grams = normalized.gold_grams ?? normalized.goldGrams ?? 0;
            normalized.sterling_grams = normalized.sterling_grams ?? normalized.sterlingGrams ?? 0;
            normalized.labor_hours = normalized.labor_hours ?? normalized.laborHours ?? 0;
            normalized.stone_cost = normalized.stone_cost ?? normalized.stoneCost ?? 0;
            normalized.stone_setting_cost = normalized.stone_setting_cost ?? normalized.stoneSettingCost ?? 0;
            normalized.star_cost = normalized.star_cost ?? normalized.starCost ?? 0;
            normalized.markup_percent = normalized.markup_percent ?? normalized.markupPercent ?? 0;
            normalized.metal_hi = normalized.metal_hi ?? normalized.metalHi ?? normalized.metal_type ?? '14K';
            normalized.metal_type = normalized.metal_type ?? normalized.metalHi ?? normalized.metal_hi ?? '14K';
            return normalized;
        }

        function renderSearchResults(resultsDiv, matches, lineNumber) {
            resultsDiv._matches = matches;

            if (!matches.length) {
                resultsDiv.innerHTML = '<div class="search-result-item">No matches found</div>';
                resultsDiv.classList.add('visible');
                return;
            }

            resultsDiv.innerHTML = matches.map((item, index) => {
                const normalizedItem = normalizeSearchItem(item);
                const customBadge = normalizedItem.isCustom ? '<span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.75em; margin-left: 5px;">CUSTOM</span>' : '';
                const code = normalizedItem.full_item_code || normalizedItem.itemCode || '';
                const description = normalizedItem.variant_description || normalizedItem.description || '';
                const price = Number(normalizedItem.selling_price || normalizedItem.price || 0).toFixed(2);
                return `
                    <div class="search-result-item" data-item-index="${index}" onclick="selectItem(${index}, ${lineNumber})">
                        <div class="item-code">${code}${customBadge}</div>
                        <div class="item-desc">${description}</div>
                        <div class="item-price">$${price}</div>
                    </div>
                `;
            }).join('');
            resultsDiv.classList.add('visible');
        }
        
        async function selectItem(itemIndex, lineNum) {
            const line = document.querySelector(`[data-line="${lineNum}"]`);
            const resultsDiv = line.querySelector('.search-results');
            const matches = resultsDiv._matches || [];
            const product = normalizeSearchItem(matches[itemIndex]);
            
            if (product) {
                line.querySelector('.item-code-input').value = product.full_item_code || product.itemCode || '';
                line.querySelector('.item-desc-input').value = product.variant_description || product.description || '';
                
                const priceInput = line.querySelector('.item-price-input');
                priceInput.value = '0.00';
                
                try {
                    const currentPrice = await calculateSellingPrice(product);
                    priceInput.value = currentPrice.toFixed(2);
                    renderLineItemPreview(line, getPreviewMedia(product));
                    
                    let orderLine = orderLines.find(ol => ol.lineNumber == lineNum);
                    if (orderLine) {
                        orderLine.itemCode = product.full_item_code || product.itemCode || '';
                        orderLine.description = product.variant_description || product.description || '';
                        orderLine.sellingPrice = currentPrice;
                        orderLine.cost = product.cost || product.total_cost || 0;
                        orderLine.isCustom = product.isCustom || false;
                        orderLine.gold_grams = product.gold_grams || product.goldGrams || 0;
                        orderLine.metal_hi = product.metal_hi || product.metalHi || '14K';
                        orderLine.sterling_grams = product.sterling_grams || product.sterlingGrams || 0;
                        orderLine.labor_hours = product.labor_hours || product.laborHours || 0;
                        orderLine.stone_cost = product.stone_cost || product.stoneCost || 0;
                        orderLine.stone_setting_cost = product.stone_setting_cost || product.stoneSettingCost || 0;
                        orderLine.star_cost = product.star_cost || product.starCost || 0;
                        orderLine.markup_percent = product.markup_percent || 0;
                        orderLine.previewMedia = getPreviewMedia(product);
                    } else {
                        orderLine = {
                            lineNumber: lineNum,
                            itemCode: product.full_item_code || product.itemCode || '',
                            description: product.variant_description || product.description || '',
                            quantity: 1,
                            sellingPrice: currentPrice,
                            cost: product.cost || product.total_cost || 0,
                            discount: 0,
                            isCustom: product.isCustom || false,
                            gold_grams: product.gold_grams || product.goldGrams || 0,
                            metal_hi: product.metal_hi || product.metalHi || '14K',
                            sterling_grams: product.sterling_grams || product.sterlingGrams || 0,
                            labor_hours: product.labor_hours || product.laborHours || 0,
                            stone_cost: product.stone_cost || product.stoneCost || 0,
                            stone_setting_cost: product.stone_setting_cost || product.stoneSettingCost || 0,
                            star_cost: product.star_cost || product.starCost || 0,
                            markup_percent: product.markup_percent || 0,
                            previewMedia: getPreviewMedia(product)
                        };
                        orderLines.push(orderLine);
                    }
                    
                    if (orderLine.isCustom) {
                        const codeInput = line.querySelector('.item-code-input');
                        codeInput.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                        codeInput.style.color = 'white';
                        codeInput.style.fontWeight = 'bold';
                        codeInput.setAttribute('title', 'Custom item from calculator');
                    }
                    
                } catch (error) {
                    console.error('Price calculation failed:', error);
                    priceInput.value = '0.00';
                }
                
                resultsDiv.classList.remove('visible');
                saveOrderLines();
                calculateLineTotal(lineNum);
            }
        }
        
        function isValidMediaValue(value) {
            if (value === null || value === undefined) return false;
            const raw = String(value).trim();
            if (!raw) return false;
            const lower = raw.toLowerCase();
            return !['', 'none', 'no images found', 'no image found', 'n/a', 'na'].includes(lower);
        }

        function buildAssetUrl(value) {
            if (!isValidMediaValue(value)) return '';

            const raw = String(value).trim();
            if (/^(https?:)?\/\//.test(raw) || raw.startsWith('data:')) return raw;
            if (raw.startsWith('./') || raw.startsWith('../') || raw.startsWith('/')) return raw;

            const normalized = raw.replace(/\\/g, '/').replace(/^\/+/, '');
            const lower = normalized.toLowerCase();

            if (lower.startsWith('cadman_catalog/')) {
                return `/${normalized}`;
            }
            if (lower.startsWith('bands_php/') || lower.startsWith('accessories_php/') || lower.startsWith('family_php/') || lower.startsWith('engagement_php/') || lower.startsWith('corp_php/') || lower.startsWith('signet_php/') || lower.startsWith('frontline_workers_php/') || lower.startsWith('school_php/') || lower.startsWith('images/')) {
                return `/${normalized}`;
            }
            if (lower.endsWith('.pdf')) {
                return `/Cadman_catalog/${normalized}`;
            }
            return `/${normalized}`;
        }

        function getPreviewMedia(product) {
            if (!product) return null;

            const media = {
                image: null,
                pdf: null,
                page: null,
                fallback: null
            };

            if (isValidMediaValue(product.image)) {
                media.image = buildAssetUrl(product.image);
            }
            if (isValidMediaValue(product.pdf)) {
                media.pdf = buildAssetUrl(product.pdf);
            }
            if (isValidMediaValue(product.page)) {
                media.page = buildAssetUrl(product.page);
            }

            if (product.image || product.pdf || product.page || product.fallback) {
                const hasAnyValidMedia = media.image || media.pdf || media.page;
                if (hasAnyValidMedia) {
                    return media;
                }
            }

            if (isValidMediaValue(product.pdf)) {
                media.pdf = buildAssetUrl(product.pdf);
            }
            if (isValidMediaValue(product.page)) {
                media.page = buildAssetUrl(product.page);
            }

            const imageFiles = product.imageFiles || product.image_files || '';
            if (!media.image && isValidMediaValue(imageFiles)) {
                const candidate = String(imageFiles).split(',')[0].trim();
                media.image = buildAssetUrl(candidate);
            }

            const pdfFile = product.pdfFile || product.pdf_file || '';
            if (!media.pdf && isValidMediaValue(pdfFile)) {
                const normalizedPdf = String(pdfFile).trim();
                media.pdf = buildAssetUrl(normalizedPdf.includes('/') ? normalizedPdf : `Cadman_catalog/${normalizedPdf}`);
            }

            const pageReference = product.pageReference || product.page_reference || '';
            if (!media.page && isValidMediaValue(pageReference)) {
                const normalizedPage = String(pageReference).trim();
                const pagePath = normalizedPage.endsWith('.pdf') ? normalizedPage : `${normalizedPage}.pdf`;
                media.page = buildAssetUrl(pagePath.includes('/') ? pagePath : `Cadman_catalog/${pagePath}`);
            }

            if (!media.image && !media.pdf && !media.page) {
                media.fallback = true;
            }

            return media;
        }

        function openLineMediaModal(lineNum) {
            const line = document.querySelector(`[data-line="${lineNum}"]`);
            if (!line) return;

            const preview = line.querySelector('.line-item-preview');
            if (!preview) return;

            const mediaUrl = preview.dataset.mediaUrl || '';
            const mediaType = preview.dataset.mediaType || 'image';
            const modal = document.getElementById('lineMediaModal');
            const body = document.getElementById('lineMediaModalBody');
            if (!modal || !body) return;

            body.innerHTML = '';
            if (mediaType === 'image' && mediaUrl) {
                body.innerHTML = `<img src="${mediaUrl}" alt="Line item preview">`;
            } else if ((mediaType === 'pdf' || mediaType === 'page') && mediaUrl) {
                body.innerHTML = `<iframe src="${mediaUrl}" title="Catalog preview"></iframe>`;
            } else {
                body.innerHTML = '<div style="padding: 40px 20px; color: #64748b;">No image available</div>';
            }

            modal.style.display = 'flex';
        }

        function closeLineMediaModal() {
            const modal = document.getElementById('lineMediaModal');
            if (modal) modal.style.display = 'none';
        }

        function renderLineItemPreview(line, product) {
            const preview = line?.querySelector('.line-item-preview');
            if (!preview) return;

            const media = getPreviewMedia(product);
            const placeholder = '<div class="preview-placeholder">No image<br>available</div>';

            if (!product && !media) {
                preview.innerHTML = `
                    ${placeholder}
                    <div class="preview-caption">Select an item</div>
                `;
                preview.dataset.mediaUrl = '';
                preview.dataset.mediaType = 'none';
                return;
            }

            if (media?.image) {
                preview.innerHTML = `
                    <img src="${media.image}" alt="Item preview" onclick="openLineMediaModal(${line.dataset.line})">
                    <div class="preview-caption">Click to enlarge</div>
                `;
                preview.dataset.mediaUrl = media.image;
                preview.dataset.mediaType = 'image';
                return;
            }

            if (media?.pdf || media?.page) {
                const pdfUrl = media.pdf || media.page;
                preview.innerHTML = `
                    <a href="${pdfUrl}" target="_blank" rel="noopener noreferrer" title="Open catalog PDF in a new tab">
                        <iframe src="${pdfUrl}" title="Catalog preview"></iframe>
                    </a>
                    <div class="preview-caption">Open PDF in new tab</div>
                `;
                preview.dataset.mediaUrl = pdfUrl;
                preview.dataset.mediaType = 'pdf';
                return;
            }

            preview.innerHTML = `
                ${placeholder}
                <div class="preview-caption">No image available</div>
            `;
            preview.dataset.mediaUrl = '';
            preview.dataset.mediaType = 'none';
        }

        function addLine() {
            const container = document.getElementById('lineItemsContainer');
            if (!container) return null;

            lineCounter += 1;
            const newLine = document.createElement('div');
            newLine.className = 'line-item';
            newLine.dataset.line = lineCounter;
            newLine.innerHTML = `
                <div class="field-group">
                    <label>Item Code</label>
                    <div class="search-dropdown">
                        <input type="text" class="item-code-input" placeholder="Search item...">
                        <div class="search-results"></div>
                    </div>
                </div>
                <div class="field-group">
                    <label>Description</label>
                    <input type="text" class="item-desc-input" readonly>
                </div>
                <div class="field-group">
                    <label>Quantity</label>
                    <input type="number" class="item-qty-input" value="1" min="1" onchange="calculateLineTotal(${lineCounter})">
                </div>
                <div class="field-group">
                    <label>Price</label>
                    <input type="number" class="item-price-input" step="0.01" readonly>
                </div>
                <div class="field-group">
                    <label>Discount %</label>
                    <input type="number" class="item-disc-input" value="0" min="0" max="100" step="0.1" onchange="calculateLineTotal(${lineCounter})">
                </div>
                <div class="field-group">
                    <label>Extended</label>
                    <input type="number" class="item-ext-input" readonly>
                </div>
                <div class="field-group">
                    <label>&nbsp;</label>
                    <div class="button-group">
                        <button type="button" onclick="editLine(${lineCounter})" title="Edit with Calculator">✎</button>
                        <button type="button" onclick="removeLine(${lineCounter})">✕</button>
                    </div>
                </div>
                <div class="field-group" style="grid-column: 1 / -1;">
                    <div class="line-item-preview" data-media-url="" data-media-type="none"></div>
                </div>
            `;
            container.appendChild(newLine);
            setupSearch(newLine);
            return newLine;
        }
        
        function editLine(lineNum) {
            // Open calculator modal instead of simple price edit
            openCalcModal(lineNum);
        }
        
        let currentEditingLine = null;
        
        function openCalcModal(lineNum) {
            currentEditingLine = lineNum;
            const line = document.querySelector(`[data-line="${lineNum}"]`);
            const orderLine = orderLines.find(ol => ol.lineNumber == lineNum);
            
            if (!orderLine) {
                alert('Order line data not found!');
                return;
            }
            
            // Populate modal with current values
            document.getElementById('modal-item-code').value = orderLine.itemCode || '';
            document.getElementById('modal-description').value = orderLine.description || '';
            document.getElementById('modal-quantity').value = orderLine.quantity || 1;
            
            // Get extended data if available (from custom items)
            const goldGrams = orderLine.gold_grams || 0;
            const sterlingGrams = orderLine.sterling_grams || 0;
            const laborHours = orderLine.labor_hours || 0;
            const stoneCost = orderLine.stone_cost || 0;
            const stoneSettingCost = orderLine.stone_setting_cost || 0;
            const starCost = orderLine.star_cost || 0;
            const markup = orderLine.markup_percent || 50;
            const engravingRequested = Boolean(orderLine.engraving_requested || orderLine.engravingRequested || false);
            const engravingText = orderLine.engraving_text || orderLine.engravingText || '';
            const engravingCost = orderLine.engraving_cost || orderLine.engravingCost || 0;
            const lineNote = orderLine.line_note || orderLine.lineNote || orderLine.notes || '';
            
            document.getElementById('modal-gold-grams').value = goldGrams;
            document.getElementById('modal-sterling-grams').value = sterlingGrams;
            document.getElementById('modal-labor-hours').value = laborHours;
            document.getElementById('modal-stone-cost').value = stoneCost;
            document.getElementById('modal-stone-setting').value = stoneSettingCost;
            document.getElementById('modal-star-cost').value = starCost;
            document.getElementById('modal-markup').value = markup;
            document.getElementById('modal-engraving').checked = engravingRequested;
            document.getElementById('modal-engraving-text').value = engravingText;
            document.getElementById('modal-engraving-cost').value = engravingCost;
            document.getElementById('modal-line-note').value = lineNote;
            document.getElementById('modal-final-price').value = orderLine.sellingPrice.toFixed(2);
            
            // Set karat if available
            const karat = orderLine.metal_hi || '14K';
            document.getElementById('modal-karat').value = karat;
            
            // Show modal
            document.getElementById('calcModal').style.display = 'block';
        }
        
        function closeCalcModal() {
            document.getElementById('calcModal').style.display = 'none';
            currentEditingLine = null;
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('calcModal');
            if (e.target === modal) {
                closeCalcModal();
            }
        });
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('calcModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCalcModal();
                }
            });
        });
        
        function recalculateModal() {
            const goldGrams = parseFloat(document.getElementById('modal-gold-grams').value) || 0;
            const karat = document.getElementById('modal-karat').value;
            const sterlingGrams = parseFloat(document.getElementById('modal-sterling-grams').value) || 0;
            const laborHours = parseFloat(document.getElementById('modal-labor-hours').value) || 0;
            const stoneCost = parseFloat(document.getElementById('modal-stone-cost').value) || 0;
            const stoneSettingCost = parseFloat(document.getElementById('modal-stone-setting').value) || 0;
            const starCost = parseFloat(document.getElementById('modal-star-cost').value) || 0;
            const engravingCost = parseFloat(document.getElementById('modal-engraving-cost').value) || 0;
            const markup = parseFloat(document.getElementById('modal-markup').value) || 50;
            const orderLine = orderLines.find(ol => ol.lineNumber == currentEditingLine);

            if (!orderLine) {
                document.getElementById('modal-final-price').value = '0.00';
                return;
            }

            const originalPrice = Number(orderLine.sellingPrice || 0);
            const originalCostDelta = (orderLine.stone_cost || 0) + (orderLine.stone_setting_cost || 0) + (orderLine.star_cost || 0) + (orderLine.engraving_cost || orderLine.engravingCost || 0);
            const newCostDelta = stoneCost + stoneSettingCost + starCost + engravingCost;
            const costDelta = newCostDelta - originalCostDelta;

            let finalPrice = originalPrice + costDelta;
            if (finalPrice < 0) finalPrice = 0;
            finalPrice = roundToQuarter(finalPrice);

            document.getElementById('modal-final-price').value = finalPrice.toFixed(2);
        }
        
        function saveModalChanges() {
            if (!currentEditingLine) return;
            
            // Get all values from modal
            const description = document.getElementById('modal-description').value;
            const quantity = parseFloat(document.getElementById('modal-quantity').value) || 1;
            const goldGrams = parseFloat(document.getElementById('modal-gold-grams').value) || 0;
            const karat = document.getElementById('modal-karat').value;
            const sterlingGrams = parseFloat(document.getElementById('modal-sterling-grams').value) || 0;
            const laborHours = parseFloat(document.getElementById('modal-labor-hours').value) || 0;
            const stoneCost = parseFloat(document.getElementById('modal-stone-cost').value) || 0;
            const stoneSettingCost = parseFloat(document.getElementById('modal-stone-setting').value) || 0;
            const starCost = parseFloat(document.getElementById('modal-star-cost').value) || 0;
            const engravingRequested = document.getElementById('modal-engraving').checked;
            const engravingText = document.getElementById('modal-engraving-text').value.trim();
            const engravingCost = parseFloat(document.getElementById('modal-engraving-cost').value) || 0;
            const lineNote = document.getElementById('modal-line-note').value.trim();
            const markup = parseFloat(document.getElementById('modal-markup').value) || 0;
            const finalPrice = parseFloat(document.getElementById('modal-final-price').value) || 0;
            
            // Update orderLines array
            const orderLine = orderLines.find(ol => ol.lineNumber == currentEditingLine);
            if (orderLine) {
                orderLine.description = description;
                orderLine.quantity = quantity;
                orderLine.sellingPrice = finalPrice;
                
                // Store calculator data
                orderLine.gold_grams = goldGrams;
                orderLine.metal_hi = karat;
                orderLine.sterling_grams = sterlingGrams;
                orderLine.labor_hours = laborHours;
                orderLine.stone_cost = stoneCost;
                orderLine.stone_setting_cost = stoneSettingCost;
                orderLine.star_cost = starCost;
                orderLine.engraving_requested = engravingRequested;
                orderLine.engraving_text = engravingText;
                orderLine.engraving_cost = engravingCost;
                orderLine.line_note = lineNote;
                orderLine.notes = lineNote;
                orderLine.markup_percent = markup;
                
                saveOrderLines();
            }
            
            // Update DOM
            const line = document.querySelector(`[data-line="${currentEditingLine}"]`);
            if (line) {
                line.querySelector('.item-desc-input').value = description;
                line.querySelector('.item-qty-input').value = quantity;
                line.querySelector('.item-price-input').value = finalPrice.toFixed(2);
                calculateLineTotal(currentEditingLine);
            }
            
            closeCalcModal();
            showMessage('Line item updated successfully', 'success');
        }
        
        function removeLine(lineNum) {
            const line = document.querySelector(`[data-line="${lineNum}"]`);
            if (line) {
                line.remove();
                
                // Remove from orderLines array
                const index = orderLines.findIndex(ol => ol.lineNumber == lineNum);
                if (index !== -1) {
                    orderLines.splice(index, 1);
                    saveOrderLines();
                }
                
                calculateTotals();
            }
        }
        
        function calculateLineTotal(lineNum) {
            const line = document.querySelector(`[data-line="${lineNum}"]`);
            const qty = parseFloat(line.querySelector('.item-qty-input').value) || 0;
            const price = parseFloat(line.querySelector('.item-price-input').value) || 0;
            const disc = parseFloat(line.querySelector('.item-disc-input').value) || 0;
            
            const extended = qty * price * (1 - disc / 100);
            line.querySelector('.item-ext-input').value = extended.toFixed(2);
            
            // Update orderLines array
            const orderLine = orderLines.find(ol => ol.lineNumber == lineNum);
            if (orderLine) {
                orderLine.quantity = qty;
                orderLine.sellingPrice = price;
                orderLine.discount = disc;
                saveOrderLines();
            }
            
            calculateTotals();
        }
        
        function calculateOrderBreakdown(subtotal, discountPercent, province) {
            const subtotalBeforeDiscount = subtotal || 0;
            const discountAmount = subtotalBeforeDiscount * ((discountPercent || 0) / 100);
            const discountedSubtotal = Math.max(0, subtotalBeforeDiscount - discountAmount);

            let taxRate = 0;
            if (province) {
                taxRate = getTaxRate(province);
            }

            const taxAmount = taxRate > 0 ? discountedSubtotal * (taxRate / 100) : 0;
            const totalAmount = discountedSubtotal + taxAmount;

            return {
                subtotal: discountedSubtotal,
                discountAmount,
                taxAmount,
                totalAmount,
                taxRate
            };
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.item-ext-input').forEach(input => {
                subtotal += parseFloat(input.value) || 0;
            });

            const discountPercent = parseFloat(document.getElementById('discountPercent')?.value || 0);
            const province = selectedCustomer && selectedCustomer.province ? selectedCustomer.province : '';
            const breakdown = calculateOrderBreakdown(subtotal, discountPercent, province);

            document.getElementById('subtotal').textContent = `$${breakdown.subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = `-$${breakdown.discountAmount.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${breakdown.taxAmount.toFixed(2)} (${breakdown.taxRate}%)`;
            document.getElementById('grandTotal').textContent = `$${breakdown.totalAmount.toFixed(2)}`;
        }
        
        // Canadian tax rates by province
        function getTaxRate(province) {
            const taxRates = {
                'AB': 5.0,    // Alberta - GST only
                'BC': 12.0,   // British Columbia - GST + PST
                'MB': 12.0,   // Manitoba - GST + PST 
                'NB': 15.0,   // New Brunswick - HST
                'NL': 15.0,   // Newfoundland - HST
                'NF': 15.0,   // Newfoundland (old code)
                'NS': 15.0,   // Nova Scotia - HST
                'ON': 13.0,   // Ontario - HST
                'PE': 15.0,   // Prince Edward Island - HST
                'QC': 14.975, // Quebec - GST + QST
                'SK': 11.0,   // Saskatchewan - GST + PST
                'NT': 5.0,    // Northwest Territories - GST only
                'NU': 5.0,    // Nunavut - GST only
                'YT': 5.0     // Yukon - GST only
            };
            
            // Clean up province code
            const cleanProvince = province.trim().toUpperCase().replace(/\s+/g, '');
            console.log(`getTaxRate - Input: "${province}", Cleaned: "${cleanProvince}"`);
            
            // Handle common variations
            if (cleanProvince === 'BC' || cleanProvince === 'BRITISHCOLUMBIA') return taxRates['BC'];
            if (cleanProvince === 'B.C.' || cleanProvince === 'B C') return taxRates['BC'];
            
            const rate = taxRates[cleanProvince] || 0;
            console.log(`getTaxRate - Result: ${rate}%`);
            return rate; // Default to 0 if province not found
        }
        
        function clearOrder() {
            if (confirm('Clear all order data and custom items?')) {
                clearCustomItems();
                clearOrderLines();
                location.reload();
            }
        }

        function getNextInvoiceNumber() {
            const storageKey = 'cadmanNextInvoiceNumber';
            const startingNumber = 690000;
            const storedValue = parseInt(localStorage.getItem(storageKey) || '', 10);
            const nextNumber = Number.isFinite(storedValue) && storedValue >= startingNumber
                ? storedValue
                : startingNumber;
            localStorage.setItem(storageKey, String(nextNumber + 1));
            return nextNumber;
        }
        
        function showSessionStatus() {
            updateSessionStatusInfo();
            document.getElementById('sessionStatus').style.display = 'block';
        }
        
        function toggleSessionStatus() {
            const status = document.getElementById('sessionStatus');
            status.style.display = status.style.display === 'none' ? 'block' : 'none';
        }
        
        function updateSessionStatusInfo() {
            // Count order lines
            const orderLines = JSON.parse(localStorage.getItem('currentOrderLines') || '[]');
            const orderLinesCountEl = document.getElementById('orderLinesCount');
            if (orderLinesCountEl) {
                orderLinesCountEl.textContent = orderLines.length;
            }
            
            // Count custom items  
            const customItems = JSON.parse(localStorage.getItem('customOrderItems') || '[]');
            const customItemsCountEl = document.getElementById('customItemsCount');
            if (customItemsCountEl) {
                customItemsCountEl.textContent = customItems.length;
            }
        }
        
        function clearSessionData() {
            if (confirm('Clear ALL session data (order lines + calculator items)? This will start completely fresh.')) {
                localStorage.removeItem('currentOrderLines');
                localStorage.removeItem('customOrderItems');
                orderLines = [];
                
                // Clear the line items container properly
                const lineItemsContainer = document.getElementById('lineItemsContainer');
                if (lineItemsContainer) {
                    // Clear all line items except keep one empty line
                    const allLines = lineItemsContainer.querySelectorAll('.line-item');
                    allLines.forEach((line, index) => {
                        if (index === 0) {
                            // Reset first line
                            line.querySelector('.item-code-input').value = '';
                            line.querySelector('.item-desc-input').value = '';
                            line.querySelector('.item-qty-input').value = '1';
                            line.querySelector('.item-price-input').value = '';
                            line.querySelector('.item-disc-input').value = '0';
                            line.dataset.line = '1';
                        } else {
                            // Remove extra lines
                            line.remove();
                        }
                    });
                    
                    // Reset line counter
                    lineCounter = 2;
                }
                
                calculateTotals();
                updateSessionStatusInfo();
                alert('✅ All session data cleared. Starting fresh.');
            }
        }
        
        function buildOrderPayload(invoiceNumber = null) {
            const customerId = selectedCustomer?.client_id || 0;
            const customerCode = selectedCustomer?.customer_code || document.getElementById('customerCode')?.value || '';
            const customerName = selectedCustomer?.business_name || document.getElementById('customerCode')?.value || 'Walk-in Customer';
            const orderDate = document.getElementById('orderDate')?.value || new Date().toISOString().split('T')[0];
            const poNumber = document.getElementById('poNumber')?.value || '';
            const terms = document.getElementById('terms')?.value || 'NET30';
            const discountPercent = parseFloat(document.getElementById('discountPercent')?.value || 0);
            
            const items = orderLines.map((line, index) => ({
                line: line.lineNumber || (index + 1),
                item_code: line.itemCode,
                itemCode: line.itemCode,
                description: line.description,
                descriptionText: line.description,
                quantity: line.quantity,
                price: line.sellingPrice,
                cost: line.cost || 0,
                engraving_requested: Boolean(line.engraving_requested || line.engravingRequested || false),
                engraving_text: line.engraving_text || line.engravingText || '',
                engraving_cost: Number(line.engraving_cost || line.engravingCost || 0),
                line_note: line.line_note || line.lineNote || line.notes || '',
                notes: line.line_note || line.lineNote || line.notes || ''
            }));
            
            const subtotal = orderLines.reduce((sum, line) => sum + (line.sellingPrice * line.quantity), 0);
            const discount = subtotal * (discountPercent / 100);
            const discountedSubtotal = Math.max(0, subtotal - discount);
            
            let taxRate = 0;
            if (selectedCustomer && selectedCustomer.province) {
                taxRate = getTaxRate(selectedCustomer.province);
            } else {
                taxRate = getTaxRate('ON');
            }
            const tax = taxRate > 0 ? discountedSubtotal * (taxRate / 100) : 0;
            const total = discountedSubtotal + tax;
            
            return {
                customer_id: customerId,
                customer_code: customerCode,
                customer_name: customerName,
                customerName: customerName,
                customerPhone: selectedCustomer?.phone || '',
                customerLocation: selectedCustomer?.city && selectedCustomer?.province ? `${selectedCustomer.city}, ${selectedCustomer.province}` : '',
                accountNumber: selectedCustomer?.customer_code || customerCode || 'N/A',
                salesRep: 'WEB',
                order_date: orderDate,
                po_number: poNumber,
                terms: terms,
                discount_percent: discountPercent,
                subtotal: discountedSubtotal,
                discount: discount,
                tax: tax,
                total: total,
                items: items,
                payment_status: 'PENDING',
                payment_method: '',
                notes: '',
                order_number: invoiceNumber ? String(invoiceNumber) : null,
                orderNumber: invoiceNumber ? String(invoiceNumber) : null
            };
        }

        function processOrder() {
            saveOrder({ processOrder: true });
        }
        
        async function saveOrder({ processOrder = false } = {}) {
            if (orderLines.length === 0) {
                alert('Please add at least one item to the order');
                return;
            }
            
            const invoiceNumber = getNextInvoiceNumber();
            const orderData = buildOrderPayload(invoiceNumber);
            orderData.process_order = processOrder;
            const terms = orderData.terms || 'NET30';
            const requiresPayment = terms.toUpperCase() === 'COD' || terms.toUpperCase() === 'CIA';
            
            try {
                showMessage(processOrder ? 'Processing order...' : 'Saving order...', 'info');
                
                const response = await fetch('api/save_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Unknown error');
                }
                
                if (requiresPayment) {
                    clearOrderLines();
                    clearCustomItems();
                    window.location.href = `order_payment.php?order_id=${result.order_id}`;
                    return;
                }

                await printInvoiceWithOrderNumber(invoiceNumber, orderData);
                clearOrderLines();
                clearCustomItems();
                location.reload();
                showMessage(`Order ${result.order_number} ${processOrder ? 'processed' : 'saved'} successfully. Invoice #${invoiceNumber} generated.`, 'success');
            } catch (error) {
                console.error('Error saving order:', error);
                showMessage('Error saving order: ' + error.message, 'error');
            }
        }
        
        async function printInvoiceWithOrderNumber(invoiceNumber, orderData) {
            // Collect line items
            const items = [];
            orderLines.forEach(line => {
                items.push({
                    line: line.lineNumber,
                    itemCode: line.itemCode,
                    description: line.description,
                    quantity: line.quantity,
                    price: line.sellingPrice,
                    engraving_requested: Boolean(line.engraving_requested || line.engravingRequested || false),
                    engraving_text: line.engraving_text || line.engravingText || '',
                    engraving_cost: Number(line.engraving_cost || line.engravingCost || 0),
                    line_note: line.line_note || line.lineNote || line.notes || '',
                    notes: line.line_note || line.lineNote || line.notes || ''
                });
            });
            
            // Calculate totals
            const subtotal = orderLines.reduce((sum, line) => sum + (line.sellingPrice * line.quantity), 0);
            const discountPercent = parseFloat(document.getElementById('discountPercent')?.value || 0);
            const discountAmount = subtotal * (discountPercent / 100);
            const discountedSubtotal = Math.max(0, subtotal - discountAmount);
            let taxRate = 0;
            if (selectedCustomer && selectedCustomer.province) {
                taxRate = getTaxRate(selectedCustomer.province);
            }
            const taxAmount = taxRate > 0 ? discountedSubtotal * (taxRate / 100) : 0;
            const total = discountedSubtotal + taxAmount;
            
            // Prepare full order data for invoice
            const invoiceData = {
                customerName: orderData.customer_name || 'Customer',
                customerPhone: orderData.customerPhone || selectedCustomer?.phone || '',
                customerLocation: orderData.customerLocation || (selectedCustomer?.city && selectedCustomer?.province ? `${selectedCustomer.city}, ${selectedCustomer.province}` : ''),
                accountNumber: selectedCustomer?.customer_code || orderData.customer_code || 'N/A',
                salesRep: 'WEB',
                orderNumber: String(invoiceNumber),
                orderDate: new Date().toISOString().split('T')[0],
                terms: orderData.terms || 'NET30',
                items: items,
                subtotal: discountedSubtotal,
                discount: discountAmount,
                tax: taxAmount,
                total: total
            };
            
            // Send to invoice generator
            const response = await fetch('generate_invoice.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(invoiceData)
            });

            const data = await response.json();

            if (data.success) {
                const popup = window.open('', '_blank');
                if (popup) {
                    popup.location.href = data.url;
                } else {
                    window.open(data.url, '_blank');
                }
                showMessage('Invoice generated: ' + data.filename, 'success');
                return data;
            }

            throw new Error(data.error || 'Unknown error');
        }
        
        function showMessage(text, type) {
            const msg = document.getElementById('statusMessage');
            msg.textContent = text;
            msg.className = `status-message ${type}`;
            setTimeout(() => {
                msg.className = 'status-message';
            }, 5000);
        }
        
        // Initialize
        console.log('Starting initialization...');
        console.log('addToOrderLines function defined:', typeof addToOrderLines);
        
        // Customer lookup - initialize before loading order lines
        let selectedCustomer = null;
        
        // Initialize everything when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const loadingBanner = document.getElementById('loadingBanner');
            const finishLoading = () => {
                if (loadingBanner) {
                    loadingBanner.classList.remove('visible');
                    loadingBanner.textContent = 'Ready';
                }
            };

            loadSystemSettings();
            loadProducts().then(() => {
                const firstLine = document.querySelector('.line-item');
                if (firstLine) {
                    setupSearch(firstLine);
                }
                finishLoading();
            }).catch(() => {
                finishLoading();
            });
            loadOrderLines(); // Load persistent order lines
            
            // Customer search with dropdown
            const customerInput = document.getElementById('customerCode');
            const customerResults = document.getElementById('customerSearchResults');
            let customerSearchTimeout;
        
        customerInput.addEventListener('input', function() {
            clearTimeout(customerSearchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length < 2) {
                customerResults.classList.remove('visible');
                return;
            }
            
            customerSearchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/get_customers.php?search=${encodeURIComponent(searchTerm)}`);
                    const result = await response.json();
                    
                    if (result.success && result.data.length > 0) {
                        customerResults.innerHTML = result.data.map(customer => `
                            <div class="search-result-item" onclick="selectCustomer('${customer.client_id}')">
                                <strong>${customer.customer_code || 'N/A'}</strong> - ${customer.business_name}
                                <br><small>${customer.city}, ${customer.province || ''}</small>
                            </div>
                        `).join('');
                        customerResults.classList.add('visible');
                    } else {
                        customerResults.innerHTML = '<div class="search-result-item">No customers found</div>';
                        customerResults.classList.add('visible');
                    }
                } catch (error) {
                    console.error('Error searching customers:', error);
                }
            }, 300);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-dropdown')) {
                customerResults.classList.remove('visible');
            }
        });
        
        // Make selectCustomer globally accessible for onclick handlers
        window.selectCustomer = async function(clientId) {
            try {
                const response = await fetch(`api/get_customers.php?client_id=${clientId}`);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const customer = result.data[0];
                    selectedCustomer = customer;
                    console.log('Customer selected:', customer.business_name, 'Province:', customer.province);
                    
                    // Set customer code in input
                    const customerInput = document.getElementById('customerCode');
                    const customerResults = document.getElementById('customerSearchResults');
                    customerInput.value = customer.customer_code || customer.business_name;
                    customerResults.classList.remove('visible');
                    
                    // Display customer information
                    document.getElementById('customerInfo').style.display = 'block';
                    document.getElementById('custName').textContent = customer.business_name || 'N/A';
                    document.getElementById('custPhone').textContent = customer.phone || 'N/A';
                    document.getElementById('custAddress').textContent = customer.city && customer.province 
                        ? `${customer.city}, ${customer.province}` 
                        : 'N/A';
                    document.getElementById('custCredit').textContent = `Disc: ${customer.discount_percent || 0}% | Level: ${customer.price_level || 1}`;
                    
                    // Auto-set terms if available
                    if (customer.terms) {
                        const termsMap = {
                            'O': 'NET30',
                            'N': 'NET30',
                            'C': 'COD'
                        };
                        const termValue = termsMap[customer.terms] || 'NET30';
                        document.getElementById('terms').value = termValue;
                    }
                    
                    // Auto-apply customer discount to all line items
                    if (customer.discount_percent > 0) {
                        document.querySelectorAll('.item-disc-input').forEach(input => {
                            if (parseFloat(input.value) === 0) {
                                input.value = customer.discount_percent;
                            }
                        });
                    }
                    
                    // Calculate totals with tax (do this after customer is set)
                    calculateTotals();
                }
            } catch (error) {
                console.error('Error loading customer:', error);
            }
        }; // END window.selectCustomer
        
        document.getElementById('customerCode').addEventListener('blur', async function() {
            const customerCode = this.value.trim();
            if (!customerCode) {
                document.getElementById('customerInfo').style.display = 'none';
                selectedCustomer = null;
                calculateTotals(); // Recalculate without customer (tax = 0)
                return;
            }
            
            // If already selected from dropdown, don't search again
            if (selectedCustomer && (selectedCustomer.customer_code === customerCode || selectedCustomer.business_name === customerCode)) {
                return;
            }
            
            try {
                const response = await fetch(`./api/get_customers.php?search=${encodeURIComponent(customerCode)}`);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const customer = result.data[0];
                    selectedCustomer = customer;
                    console.log('Customer looked up (blur):', customer.business_name, 'Province:', customer.province);
                    
                    // Display customer information
                    document.getElementById('customerInfo').style.display = 'block';
                    document.getElementById('custName').textContent = customer.business_name || 'N/A';
                    document.getElementById('custPhone').textContent = customer.phone || 'N/A';
                    document.getElementById('custAddress').textContent = customer.city && customer.province 
                        ? `${customer.city}, ${customer.province}` 
                        : 'N/A';
                    document.getElementById('custCredit').textContent = `Disc: ${customer.discount_percent || 0}% | Level: ${customer.price_level || 1}`;
                    
                    // Auto-set terms if available
                    if (customer.terms) {
                        const termsMap = {
                            'O': 'NET30',
                            'N': 'NET30',
                            'C': 'COD'
                        };
                        const termValue = termsMap[customer.terms] || 'NET30';
                        document.getElementById('terms').value = termValue;
                    }
                    
                    // Auto-apply customer discount to all line items
                    if (customer.discount_percent > 0) {
                        document.querySelectorAll('.item-disc-input').forEach(input => {
                            if (parseFloat(input.value) === 0) {
                                input.value = customer.discount_percent;
                            }
                        });
                    }
                    
                    // Calculate totals with tax (do this after customer is set)
                    calculateTotals();
                } else {
                    document.getElementById('customerInfo').style.display = 'none';
                    selectedCustomer = null;
                    showMessage('Customer not found', 'error');
                }
            } catch (error) {
                console.error('Error looking up customer:', error);
                showMessage('Error looking up customer', 'error');
            }
        }); // END customerCode blur event listener
        
        }); // END DOMContentLoaded event listener
    </script>
</body>
</html>
