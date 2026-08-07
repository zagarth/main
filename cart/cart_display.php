<?php
/**
 * Cart Display Component
 * Renders cart UI elements
 * Cadman Manufacturing - Jewelry E-commerce
 */

function renderCartIcon() {
    return '
    <div class="cart-icon-container">
        <button class="cart-toggle" aria-label="View Shopping Cart">
            <span class="cart-icon">🛒</span>
            <span class="cart-badge">0</span>
        </button>
    </div>
    ';
}

function renderCartModal() {
    return '
    <div id="cartModal" class="cart-modal">
        <div class="cart-modal-content">
            <div class="cart-header">
                <h2>Shopping Cart</h2>
                <button class="cart-close">&times;</button>
            </div>
            <div class="cart-display">
                <div class="empty-cart">Your cart is empty</div>
            </div>
            <div class="cart-actions">
                <button class="btn btn-secondary" onclick="cadmanCart.clearCart()">Clear Cart</button>
                <button class="btn btn-primary" onclick="window.location.href=\'cart/checkout.php\'">Checkout</button>
            </div>
        </div>
    </div>
    ';
}

function renderCartStyles() {
    return '
    <style>
    /* Cart Icon Styles */
    .cart-icon-container {
        position: relative;
        display: inline-block;
    }
    
    .cart-icon-fixed {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .cart-toggle {
        background: linear-gradient(145deg, #FFD700, #FFA500);
        border: 2px solid #FFD700;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .cart-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    }
    
    .cart-icon {
        font-size: 20px;
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
    }
    
    /* Cart Modal Styles */
    .cart-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
    }
    
    .cart-modal.show {
        display: flex;
    }
    
    .cart-modal-content {
        background: white;
        border-radius: 15px;
        padding: 0;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        border-bottom: 2px solid #FFD700;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 15px 15px 0 0;
    }
    
    .cart-header h2 {
        margin: 0;
        color: #333;
        font-size: 24px;
    }
    
    .cart-close {
        background: none;
        border: none;
        font-size: 30px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .cart-close:hover {
        background: #f0f0f0;
        color: #333;
    }
    
    .cart-display {
        padding: 20px 30px;
        min-height: 200px;
    }
    
    .empty-cart {
        text-align: center;
        color: #666;
        font-style: italic;
        padding: 40px 20px;
        font-size: 18px;
    }
    
    /* Cart Items Styles */
    .cart-items {
        margin-bottom: 20px;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 60px 1fr auto auto auto;
        gap: 15px;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .item-image img {
        width: 60px;
        height: 45px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #e0e0e0;
    }
    
    .no-image {
        width: 60px;
        height: 45px;
        background: #f0f0f0;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #ccc;
        border: 1px solid #e0e0e0;
    }
    
    .item-details {
        min-width: 0;
    }
    
    .item-name {
        font-weight: bold;
        color: #333;
        font-size: 14px;
        margin-bottom: 3px;
        word-wrap: break-word;
    }
    
    .item-collection {
        color: #666;
        font-size: 12px;
        margin-bottom: 2px;
    }
    
    .item-customization {
        color: #007bff;
        font-size: 11px;
        font-style: italic;
    }
    
    .item-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 3px;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: all 0.2s ease;
    }
    
    .qty-btn:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }
    
    .quantity {
        font-weight: bold;
        min-width: 20px;
        text-align: center;
    }
    
    .item-price {
        font-weight: bold;
        color: #333;
        font-size: 14px;
        text-align: right;
    }
    
    .remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
        transition: all 0.2s ease;
    }
    
    .remove-btn:hover {
        background: #c82333;
    }
    
    /* Cart Totals Styles */
    .cart-totals {
        border-top: 2px solid #e0e0e0;
        padding-top: 15px;
        margin-top: 20px;
    }
    
    .total-line {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .total-line.total {
        border-top: 1px solid #ddd;
        padding-top: 8px;
        margin-top: 8px;
        font-size: 16px;
    }
    
    /* Cart Actions Styles */
    .cart-actions {
        display: flex;
        justify-content: space-between;
        padding: 20px 30px;
        border-top: 1px solid #e0e0e0;
        background: #f8f9fa;
        border-radius: 0 0 15px 15px;
    }
    
    .cart-actions .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #545b62;
    }
    
    .btn-primary {
        background: linear-gradient(145deg, #FFD700, #FFA500);
        color: black;
        border: 2px solid #FFD700;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    }
    
    /* Add to Cart Button Styles */
    .add-to-cart-btn {
        background: linear-gradient(145deg, #28a745, #20c997);
        color: white;
        border: 2px solid #28a745;
        padding: 12px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .add-to-cart-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    
    .add-to-cart-btn:disabled {
        background: #cccccc;
        border-color: #cccccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .cart-modal-content {
            width: 95%;
            margin: 10px;
        }
        
        .cart-item {
            grid-template-columns: 50px 1fr auto;
            gap: 10px;
        }
        
        .item-quantity,
        .item-actions {
            grid-column: 2 / -1;
            justify-self: start;
            margin-top: 10px;
        }
        
        .item-price {
            grid-column: -2;
            grid-row: 1;
        }
        
        .cart-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .cart-actions .btn {
            width: 100%;
            text-align: center;
        }
    }
    
    /* Loading States */
    .cart-loading {
        display: none;
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .cart-loading:before {
        content: "⏳";
        margin-right: 10px;
    }
    </style>
    ';
}

// DEPRECATED: includeCart() function removed to prevent duplication
// Use renderNavigation() instead which properly includes cart system
?>