/**
 * Shopping Cart JavaScript Library
 * Handles frontend cart operations and UI updates
 * Cadman Manufacturing - Jewelry E-commerce
 */

console.log('===== CART.JS LOADING =====');
console.log('cart.js file is being loaded');

// Global flag to prevent multiple event listener setups
window.cartEventListenersSetup = window.cartEventListenersSetup || false;

class CadmanCart {
    constructor() {
        // Dynamically determine API URL based on current page location
        this.apiUrl = this.determineApiUrl();
        this.csrfToken = null;
        this.cartCount = 0;
        this.isLoading = false;
        
        // Initialize cart
        this.init();
    }
    
    /**
     * Determine the correct API URL based on current page location
     */
    determineApiUrl() {
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/');
        const fileName = pathParts[pathParts.length - 1];
        
        console.log('Determining API URL for path:', currentPath);
        console.log('Path parts:', pathParts);
        console.log('File name:', fileName);
        
        // Check if we're in a subdirectory
        const isInSubdirectory = pathParts.length > 2 && pathParts[pathParts.length - 2] !== '';
        
        // Also check for known subdirectory patterns
        const knownSubdirs = ['family_php', 'bands_php', 'accessories_php', 'corp_php', 'ladys_stoneset_php'];
        const isKnownSubdir = knownSubdirs.some(subdir => currentPath.includes('/' + subdir + '/'));
        
        // Check if we're on a detail page (unified_detail.php or *_detail.php)
        const isDetailPage = fileName === 'unified_detail.php' || fileName.endsWith('_detail.php');
        
        console.log('Is in subdirectory:', isInSubdirectory);
        console.log('Is known subdir:', isKnownSubdir);
        console.log('Is detail page:', isDetailPage);
        
        let apiUrl;
        if (isInSubdirectory || isKnownSubdir || isDetailPage) {
            // For subdirectories and detail pages, use relative path
            apiUrl = isDetailPage && !isInSubdirectory ? 'cart/cart_api.php' : '../cart/cart_api.php';
        } else {
            apiUrl = 'cart/cart_api.php';
        }
        
        console.log('Determined API URL:', apiUrl);
        return apiUrl;
    }
    
    /**
     * Initialize cart system
     */
    async init() {
        try {
            // Set up event listeners immediately — no blocking API calls at startup.
            // CSRF token and cart count are fetched lazily on first user interaction.
            this.setupEventListeners();
            console.log('Cadman Cart initialized successfully');
        } catch (error) {
            console.error('Error initializing cart:', error);
        }
    }


    
    /**
     * Get CSRF token from server
     */
    async getCSRFToken() {
        try {
            console.log('Getting CSRF token from:', this.apiUrl + '?action=get_token');
            const response = await fetch(this.apiUrl + '?action=get_token', {
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('CSRF token response:', data);
            
            if (data.success && data.csrf_token) {
                this.csrfToken = data.csrf_token;
                console.log('CSRF token retrieved successfully:', this.csrfToken.substring(0, 8) + '...');
            } else {
                throw new Error('Failed to get CSRF token: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error getting CSRF token:', error);
            console.error('API URL used:', this.apiUrl);
            throw error;
        }
    }
    
    /**
     * Add item to cart
     */
    async addItem(itemData) {
        if (this.isLoading) return;
        
        try {
            this.setLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('csrf_token', await this.getCSRFTokenValue());
            
            // Add item data
            Object.keys(itemData).forEach(key => {
                formData.append(key, itemData[key]);
            });
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Item added to cart!', 'success');
                this.updateCartCount();
                this.updateCartDisplay(result.cart);
            } else {
                this.showNotification(result.message, 'error');
            }
            
            return result;
            
        } catch (error) {
            console.error('Error adding item to cart:', error);
            this.showNotification('Error adding item to cart', 'error');
            return { success: false, message: error.message };
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Remove item from cart
     */
    async removeItem(cartItemId) {
        if (this.isLoading) return;
        
        try {
            this.setLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('csrf_token', await this.getCSRFTokenValue());
            formData.append('cart_item_id', cartItemId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Item removed from cart', 'success');
                this.updateCartCount();
                this.updateCartDisplay(result.cart);
            } else {
                this.showNotification(result.message, 'error');
            }
            
            return result;
            
        } catch (error) {
            console.error('Error removing item from cart:', error);
            this.showNotification('Error removing item from cart', 'error');
            return { success: false, message: error.message };
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Update item quantity
     */
    async updateQuantity(cartItemId, quantity) {
        if (this.isLoading) return;
        
        try {
            this.setLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('csrf_token', await this.getCSRFTokenValue());
            formData.append('cart_item_id', cartItemId);
            formData.append('quantity', quantity);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateCartCount();
                this.updateCartDisplay(result.cart);
            } else {
                this.showNotification(result.message, 'error');
            }
            
            return result;
            
        } catch (error) {
            console.error('Error updating quantity:', error);
            this.showNotification('Error updating quantity', 'error');
            return { success: false, message: error.message };
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Get cart contents
     */
    async getCart() {
        try {
            const response = await fetch(this.apiUrl + '?action=get', {
                credentials: 'same-origin'
            });
            const result = await response.json();
            
            if (result.success) {
                this.updateCartDisplay(result.cart);
            }
            
            return result;
            
        } catch (error) {
            console.error('Error getting cart:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Clear cart
     */
    async clearCart() {
        if (!confirm('Are you sure you want to clear your cart?')) {
            return;
        }
        
        try {
            this.setLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'clear');
            formData.append('csrf_token', await this.getCSRFTokenValue());
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Cart cleared', 'success');
                this.updateCartCount();
                this.updateCartDisplay(result.cart);
            } else {
                this.showNotification(result.message, 'error');
            }
            
            return result;
            
        } catch (error) {
            console.error('Error clearing cart:', error);
            this.showNotification('Error clearing cart', 'error');
            return { success: false, message: error.message };
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Update cart count display
     */
    async updateCartCount() {
        try {
            const response = await fetch(this.apiUrl + '?action=count', {
                credentials: 'same-origin'
            });
            const result = await response.json();
            
            if (result.success) {
                this.cartCount = result.count;
                this.updateCartCountDisplay();
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }
    
    /**
     * Update cart count in UI
     */
    updateCartCountDisplay() {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = this.cartCount;
            element.style.display = this.cartCount > 0 ? 'inline' : 'none';
        });
        
        // Update cart badge
        const cartBadges = document.querySelectorAll('.cart-badge');
        cartBadges.forEach(badge => {
            badge.textContent = this.cartCount;
            badge.style.display = this.cartCount > 0 ? 'inline-block' : 'none';
        });
    }
    
    /**
     * Update cart display
     */
    updateCartDisplay(cartData) {
        const cartDisplays = document.querySelectorAll('.cart-display');
        cartDisplays.forEach(display => {
            this.renderCartDisplay(display, cartData);
        });
    }
    
    /**
     * Render cart display
     */
    renderCartDisplay(container, cartData) {
        if (!cartData || !cartData.items) {
            container.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
            return;
        }

        // Use cart_item_id as key when iterating through items
        const cartItems = Object.entries(cartData.items);
        const totals = cartData.totals;
        
        let html = '<div class="cart-items">';
        
        cartItems.forEach(([cartItemId, item]) => {
            html += `
                <div class="cart-item" data-cart-item-id="${cartItemId}">
                    <div class="item-image">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div class="no-image">📷</div>'}
                    </div>
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-collection">${item.collection}</div>
                        ${item.customization ? `<div class="item-customization">${item.customization}</div>` : ''}
                    </div>
                    <div class="item-quantity">
                        <button class="qty-btn" onclick="cadmanCart.updateQuantity('${cartItemId}', ${item.quantity - 1})">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="qty-btn" onclick="cadmanCart.updateQuantity('${cartItemId}', ${item.quantity + 1})">+</button>
                    </div>
                    <div class="item-price">$${(item.price * item.quantity).toFixed(2)}</div>
                    <div class="item-actions">
                        <button class="remove-btn" onclick="cadmanCart.removeItem('${cartItemId}')">Remove</button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Add totals
        if (totals) {
            html += `
                <div class="cart-totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>$${totals.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="total-line">
                        <span>Tax:</span>
                        <span>$${totals.tax.toFixed(2)}</span>
                    </div>
                    <div class="total-line">
                        <span>Shipping:</span>
                        <span>${totals.shipping > 0 ? '$' + totals.shipping.toFixed(2) : 'FREE'}</span>
                    </div>
                    <div class="total-line total">
                        <span><strong>Total:</strong></span>
                        <span><strong>$${totals.total.toFixed(2)}</strong></span>
                    </div>
                </div>
            `;
        }
        
        container.innerHTML = html;
    }
    
    /**
     * Get cart item ID - matches PHP generateCartItemId method
     */
    getCartItemId(item) {
        // Create identifier that matches PHP: collection_item_id_category[_customization_hash]
        let identifier = item.collection + '_' + item.item_id + '_' + (item.category || '');
        if (item.customization && item.customization.trim()) {
            // Use a simple hash for customization (MD5 equivalent not available in JS, but this should work)
            identifier += '_' + this.simpleHash(item.customization);
        }
        
        // Use a simple hash function that should match PHP md5 concept
        return this.simpleHash(identifier);
    }
    
    /**
     * Simple hash function for cart item IDs
     */
    simpleHash(str) {
        let hash = 0;
        if (str.length === 0) return hash.toString();
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        // Convert to positive hex string to match MD5 format
        return Math.abs(hash).toString(16).padStart(8, '0');
    }
    
    /**
     * Set up event listeners (prevent multiple setups)
     */
    setupEventListeners() {
        // Check if event listeners already set up globally
        if (window.cartEventListenersSetup) {
            console.log('===== EVENT LISTENERS ALREADY SETUP =====');
            console.log('Skipping duplicate event listener setup');
            return;
        }
        
        console.log('===== SETTING UP CART EVENT LISTENERS =====');
        
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                this.handleAddToCartClick(e.target);
            }
        });
        
        // Cart toggle button
        document.addEventListener('click', (e) => {
            // Check if clicked element or its parent has cart-toggle class
            let target = e.target;
            console.log('Click detected on:', target, 'classes:', target.className);
            while (target && target !== document) {
                if (target.classList && target.classList.contains('cart-toggle')) {
                    console.log('===== CART BUTTON CLICKED =====');
                    console.log('Cart toggle clicked!', target);
                    console.log('Target classes:', target.className);
                    e.preventDefault();
                    this.toggleCartDisplay();
                    return;
                }
                target = target.parentElement;
            }
        });
        
        // Cart close button
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cart-close')) {
                console.log('Cart close button clicked');
                e.preventDefault();
                this.hideCartDisplay();
            }
        });
        
        // Close on background click
        document.addEventListener('click', (e) => {
            const cartModal = document.getElementById('cartModal');
            if (e.target === cartModal) {
                console.log('Background clicked, closing cart');
                this.hideCartDisplay();
            }
        });
        
        // Mark event listeners as set up
        window.cartEventListenersSetup = true;
        console.log('Event listeners setup completed');
    }
    
    /**
     * Handle add to cart button click
     */
    handleAddToCartClick(button) {
        const itemData = {
            collection: button.dataset.collection || '',
            item_id: button.dataset.itemId || '',
            category: button.dataset.category || '',
            name: button.dataset.name || '',
            price: parseFloat(button.dataset.price) || 0,
            image: button.dataset.image || '',
            quantity: 1
        };
        
        // Get customization data if available
        const customizationForm = button.closest('form');
        if (customizationForm) {
            const customization = customizationForm.querySelector('[name="customization"]');
            const notes = customizationForm.querySelector('[name="notes"]');
            
            if (customization) itemData.customization = customization.value;
            if (notes) itemData.notes = notes.value;
        }
        
        this.addItem(itemData);
    }
    
    /**
     * Toggle cart display
     */
    toggleCartDisplay() {
        console.log('===== TOGGLE CART DISPLAY =====');
        console.log('toggleCartDisplay called');
        const cartModal = document.getElementById('cartModal');
        console.log('Cart modal element:', cartModal);
        
        if (cartModal) {
            console.log('Modal innerHTML:', cartModal.innerHTML.substring(0, 100) + '...');
            console.log('Modal style.display:', cartModal.style.display);
            console.log('Modal computed display:', window.getComputedStyle(cartModal).display);
            console.log('Modal classes:', cartModal.className);
            console.log('Modal has show class:', cartModal.classList.contains('show'));
            
            const isVisible = cartModal.classList.contains('show');
            console.log('Modal is currently visible:', isVisible);
            
            if (isVisible) {
                this.hideCartDisplay();
            } else {
                this.showCartDisplay();
            }
        } else {
            console.error('Cart modal not found!');
        }
        console.log('===== END TOGGLE =====');
    }
    
    /**
     * Show cart display
     */
    showCartDisplay() {
        console.log('Showing cart modal - adding show class');
        const cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.classList.add('show');
            console.log('Calling this.getCart()');
            this.getCart(); // Refresh cart display when opening
            console.log('After show - Modal classes:', cartModal.className);
            console.log('After show - Modal computed display:', window.getComputedStyle(cartModal).display);
        }
    }
    
    /**
     * Hide cart display
     */
    hideCartDisplay() {
        console.log('Hiding cart modal - removing show class');
        const cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.classList.remove('show');
            console.log('After hide - Modal classes:', cartModal.className);
            console.log('After hide - Modal computed display:', window.getComputedStyle(cartModal).display);
        }
    }
    
    /**
     * Get CSRF token value
     */
    async getCSRFTokenValue() {
        if (!this.csrfToken) {
            await this.getCSRFToken();
        }
        return this.csrfToken;
    }
    
    /**
     * Set loading state
     */
    setLoading(loading) {
        this.isLoading = loading;
        const loadingElements = document.querySelectorAll('.cart-loading');
        loadingElements.forEach(element => {
            element.style.display = loading ? 'block' : 'none';
        });
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `cart-notification cart-notification-${type}`;
        notification.textContent = message;
        
        // Style the notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-weight: bold;
            max-width: 300px;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Initialize cart when DOM is loaded (prevent multiple initializations)
document.addEventListener('DOMContentLoaded', () => {
    // Prevent multiple cart initializations
    if (window.cadmanCart) {
        console.log('===== CART ALREADY INITIALIZED =====');
        console.log('Skipping duplicate cart initialization');
        return;
    }
    
    console.log('===== CART INITIALIZATION =====');
    console.log('DOM loaded, creating CadmanCart...');
    window.cadmanCart = new CadmanCart();
    console.log('CadmanCart created:', window.cadmanCart);
    
    // Debug cart modal and button after initialization
    setTimeout(() => {
        const cartModal = document.getElementById('cartModal');
        const cartButton = document.querySelector('.cart-toggle');
        console.log('After init - Cart modal exists:', !!cartModal);
        console.log('After init - Cart button exists:', !!cartButton);
        if (cartModal) {
            console.log('After init - Modal style:', cartModal.style.display);
            console.log('After init - Modal computed:', window.getComputedStyle(cartModal).display);
            console.log('After init - Modal classes:', cartModal.className);
        }
        if (cartButton) {
            console.log('After init - Button classes:', cartButton.className);
        }
    }, 100);
    console.log('===== END CART INIT =====');
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CadmanCart;
}