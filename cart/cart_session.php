<?php
/**
 * Secure Shopping Cart Session Manager
 * Handles cart data storage, validation, and security
 * Cadman Manufacturing - Jewelry E-commerce
 */

class CartSession {
    private $sessionKey = 'cadman_cart';
    private $cartTimeout = 7200; // 2 hours
    
    public function __construct() {
        // Ensure session is started securely
        if (session_status() === PHP_SESSION_NONE) {
            $this->startSecureSession();
        }
        
        // Initialize cart if it doesn't exist
        if (!isset($_SESSION[$this->sessionKey])) {
            $this->initializeCart();
        }
        
        // Clean expired items
        $this->cleanExpiredItems();
    }
    
    /**
     * Start secure session with proper configuration
     */
    private function startSecureSession() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
    }
    
    /**
     * Initialize empty cart structure
     */
    private function initializeCart() {
        $_SESSION[$this->sessionKey] = [
            'items' => [],
            'totals' => [
                'subtotal' => 0.00,
                'tax' => 0.00,
                'shipping' => 0.00,
                'total' => 0.00
            ],
            'metadata' => [
                'created' => time(),
                'updated' => time(),
                'item_count' => 0
            ],
            'customer' => [
                'email' => '',
                'name' => '',
                'phone' => ''
            ]
        ];
    }
    
    /**
     * Add item to cart with validation
     */
    public function addItem($itemData) {
        try {
            // Validate required fields
            $requiredFields = ['collection', 'item_id', 'name', 'price'];
            foreach ($requiredFields as $field) {
                if (!isset($itemData[$field]) || empty($itemData[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Sanitize and validate data
            $sanitizedItem = $this->sanitizeItemData($itemData);
            
            // Generate unique cart item ID
            $cartItemId = $this->generateCartItemId($sanitizedItem);
            
            // Check if item already exists in cart
            if (isset($_SESSION[$this->sessionKey]['items'][$cartItemId])) {
                // Update quantity instead of adding duplicate
                $_SESSION[$this->sessionKey]['items'][$cartItemId]['quantity']++;
            } else {
                // Add new item to cart
                $_SESSION[$this->sessionKey]['items'][$cartItemId] = $sanitizedItem;
            }
            
            // Update cart metadata
            $this->updateCartMetadata();
            $this->calculateTotals();
            
            return [
                'success' => true,
                'message' => 'Item added to cart successfully',
                'cart_item_id' => $cartItemId,
                'item_count' => $this->getItemCount()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error adding item to cart: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($cartItemId) {
        if (isset($_SESSION[$this->sessionKey]['items'][$cartItemId])) {
            unset($_SESSION[$this->sessionKey]['items'][$cartItemId]);
            $this->updateCartMetadata();
            $this->calculateTotals();
            
            return [
                'success' => true,
                'message' => 'Item removed from cart'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Item not found in cart'
        ];
    }
    
    /**
     * Update item quantity
     */
    public function updateQuantity($cartItemId, $quantity) {
        $quantity = max(0, intval($quantity));
        
        if ($quantity === 0) {
            return $this->removeItem($cartItemId);
        }
        
        if (isset($_SESSION[$this->sessionKey]['items'][$cartItemId])) {
            $_SESSION[$this->sessionKey]['items'][$cartItemId]['quantity'] = $quantity;
            $this->updateCartMetadata();
            $this->calculateTotals();
            
            return [
                'success' => true,
                'message' => 'Quantity updated'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Item not found in cart'
        ];
    }
    
    /**
     * Get all cart items
     */
    public function getItems() {
        return $_SESSION[$this->sessionKey]['items'] ?? [];
    }
    
    /**
     * Get cart totals
     */
    public function getTotals() {
        return $_SESSION[$this->sessionKey]['totals'] ?? [];
    }
    
    /**
     * Get cart metadata
     */
    public function getMetadata() {
        return $_SESSION[$this->sessionKey]['metadata'] ?? [];
    }
    
    /**
     * Get total item count
     */
    public function getItemCount() {
        $count = 0;
        foreach ($this->getItems() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart() {
        $this->initializeCart();
        return [
            'success' => true,
            'message' => 'Cart cleared'
        ];
    }
    
    /**
     * Sanitize item data for security
     */
    private function sanitizeItemData($itemData) {
        return [
            'collection' => htmlspecialchars(trim($itemData['collection']), ENT_QUOTES, 'UTF-8'),
            'item_id' => htmlspecialchars(trim($itemData['item_id']), ENT_QUOTES, 'UTF-8'),
            'category' => htmlspecialchars(trim($itemData['category'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars(trim($itemData['name']), ENT_QUOTES, 'UTF-8'),
            'price' => floatval($itemData['price']),
            'image' => htmlspecialchars(trim($itemData['image'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'quantity' => max(1, intval($itemData['quantity'] ?? 1)),
            'customization' => htmlspecialchars(trim($itemData['customization'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'notes' => htmlspecialchars(trim($itemData['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'added_at' => time()
        ];
    }
    
    /**
     * Generate unique cart item ID
     */
    private function generateCartItemId($itemData) {
        $identifier = $itemData['collection'] . '_' . $itemData['item_id'] . '_' . ($itemData['category'] ?? '');
        if (!empty($itemData['customization'])) {
            $identifier .= '_' . md5($itemData['customization']);
        }
        return md5($identifier);
    }
    
    /**
     * Update cart metadata
     */
    private function updateCartMetadata() {
        $_SESSION[$this->sessionKey]['metadata']['updated'] = time();
        $_SESSION[$this->sessionKey]['metadata']['item_count'] = $this->getItemCount();
    }
    
    /**
     * Calculate cart totals
     */
    private function calculateTotals() {
        $subtotal = 0.00;
        
        foreach ($this->getItems() as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Calculate tax (example: 13% HST for Ontario, Canada)
        $taxRate = 0.13;
        $tax = $subtotal * $taxRate;
        
        // Calculate shipping (example: free over $500, otherwise $25)
        $shipping = $subtotal >= 500 ? 0.00 : 25.00;
        
        $total = $subtotal + $tax + $shipping;
        
        $_SESSION[$this->sessionKey]['totals'] = [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'tax_rate' => $taxRate,
            'shipping' => round($shipping, 2),
            'total' => round($total, 2)
        ];
    }
    
    /**
     * Clean expired items from cart
     */
    private function cleanExpiredItems() {
        $currentTime = time();
        $items = $this->getItems();
        $itemsRemoved = false;
        
        foreach ($items as $cartItemId => $item) {
            if (($currentTime - $item['added_at']) > $this->cartTimeout) {
                unset($_SESSION[$this->sessionKey]['items'][$cartItemId]);
                $itemsRemoved = true;
            }
        }
        
        if ($itemsRemoved) {
            $this->updateCartMetadata();
            $this->calculateTotals();
        }
    }
    
    /**
     * Validate cart before checkout
     */
    public function validateCart() {
        $items = $this->getItems();
        $errors = [];
        
        if (empty($items)) {
            $errors[] = 'Cart is empty';
        }
        
        foreach ($items as $cartItemId => $item) {
            // Validate item data integrity
            if (empty($item['name']) || $item['price'] <= 0) {
                $errors[] = "Invalid item data for item {$item['name']}";
            }
            
            // Check if item is still available (could add inventory check here)
            if ($item['quantity'] <= 0) {
                $errors[] = "Invalid quantity for item {$item['name']}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get cart summary for display
     */
    public function getCartSummary() {
        return [
            'items' => $this->getItems(),
            'totals' => $this->getTotals(),
            'metadata' => $this->getMetadata(),
            'item_count' => $this->getItemCount(),
            'is_valid' => $this->validateCart()['valid']
        ];
    }
}
?>