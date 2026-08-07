<?php
/**
 * Shopping Cart API Handler
 * Handles AJAX requests for cart operations
 * Cadman Manufacturing - Jewelry E-commerce
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Start session first for CSRF token management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include cart session manager
require_once 'cart_session.php';

// Initialize cart session
$cart = new CartSession();

// Get the action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF protection for POST requests (except for token generation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'get_token') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    switch ($action) {
        case 'get_token':
            $result = handleGetToken();
            break;
            
        case 'add':
            $result = handleAddItem($cart);
            break;
            
        case 'remove':
            $result = handleRemoveItem($cart);
            break;
            
        case 'update':
            $result = handleUpdateQuantity($cart);
            break;
            
        case 'get':
            $result = handleGetCart($cart);
            break;
            
        case 'clear':
            $result = handleClearCart($cart);
            break;
            
        case 'count':
            $result = handleGetCount($cart);
            break;
            
        case 'count':
            $result = handleGetCount($cart);
            break;
            
        default:
            http_response_code(400);
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Handle add item to cart
 */
function handleAddItem($cart) {
    $requiredFields = ['collection', 'item_id', 'name'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            return ['success' => false, 'message' => "Missing required field: {$field}"];
        }
    }
    
    // Generate temporary price if not provided or is 0
    $price = floatval($_POST['price'] ?? 0);
    if ($price <= 0) {
        $price = generateTemporaryPrice($_POST['collection'], $_POST['category'] ?? '', $_POST['name']);
    }
    
    $itemData = [
        'collection' => $_POST['collection'],
        'item_id' => $_POST['item_id'],
        'category' => $_POST['category'] ?? '',
        'name' => $_POST['name'],
        'price' => $price,
        'price_source' => ($price === floatval($_POST['price'] ?? 0) && $price > 0) ? 'user' : 'temporary',
        'image' => $_POST['image'] ?? '',
        'quantity' => max(1, intval($_POST['quantity'] ?? 1)),
        'customization' => $_POST['customization'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    $result = $cart->addItem($itemData);
    
    // Add cart summary to response
    if ($result['success']) {
        $result['cart'] = $cart->getCartSummary();
    }
    
    return $result;
}

/**
 * Handle remove item from cart
 */
function handleRemoveItem($cart) {
    $cartItemId = $_POST['cart_item_id'] ?? '';
    
    if (empty($cartItemId)) {
        return ['success' => false, 'message' => 'Missing cart item ID'];
    }
    
    $result = $cart->removeItem($cartItemId);
    
    if ($result['success']) {
        $result['cart'] = $cart->getCartSummary();
    }
    
    return $result;
}

/**
 * Handle update item quantity
 */
function handleUpdateQuantity($cart) {
    $cartItemId = $_POST['cart_item_id'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if (empty($cartItemId)) {
        return ['success' => false, 'message' => 'Missing cart item ID'];
    }
    
    $result = $cart->updateQuantity($cartItemId, $quantity);
    
    if ($result['success']) {
        $result['cart'] = $cart->getCartSummary();
    }
    
    return $result;
}

/**
 * Handle get cart contents
 */
function handleGetCart($cart) {
    return [
        'success' => true,
        'cart' => $cart->getCartSummary()
    ];
}

/**
 * Handle clear cart
 */
function handleClearCart($cart) {
    $result = $cart->clearCart();
    
    if ($result['success']) {
        $result['cart'] = $cart->getCartSummary();
    }
    
    return $result;
}

/**
 * Handle get cart count
 */
function handleGetCount($cart) {
    return [
        'success' => true,
        'count' => $cart->getItemCount()
    ];
}

/**
 * Handle get CSRF token
 */
function handleGetToken() {
    return [
        'success' => true,
        'csrf_token' => generateCSRFToken()
    ];
}

/**
 * Generate CSRF token if it doesn't exist
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate temporary price for items
 * This will be replaced by mainframe pricing in production
 */
function generateTemporaryPrice($collection, $category = '', $itemName = '') {
    // Base prices by collection
    $basePrices = [
        'bands' => 450,
        'engagement' => 850,
        'family' => 320,
        'accessories' => 125,
        'corp' => 280,
        'signet' => 380,
        'frontline_workers' => 320,
        'ladys_stoneset' => 650,
        'school' => 290
    ];
    
    $basePrice = $basePrices[$collection] ?? 300; // Default fallback
    
    // Category modifiers
    $categoryModifiers = [
        // Bands
        'celtic' => 1.2,
        'fancy' => 1.3,
        'cultural' => 1.15,
        'plain' => 1.0,
        
        // Engagement
        'MK_series' => 1.1,
        'MM_series' => 1.2,
        'WM_series' => 1.4,
        
        // Accessories
        'crosses' => 1.0,
        'idents' => 0.8,
        'pendant_earrings' => 1.1,
        
        // Family
        'mother' => 1.0,
        'father' => 1.1,
        'daughter' => 0.9,
        
        // Corp
        'awards' => 1.2,
        'executive' => 1.5,
        
        // Signet
        'crest_top' => 1.1,
        'jewel_top' => 1.3,
        
        // Frontline Workers
        'firefighter' => 1.0,
        'clinical_services' => 1.0,
        
        // Lady's Stoneset
        'gems' => 1.2,
        'pearls' => 1.1,
        
        // School
        'bands' => 0.9,
        'crest_tops' => 1.1,
        'shoulders' => 1.0
    ];
    
    $modifier = $categoryModifiers[$category] ?? 1.0;
    
    // Special material modifiers based on item name
    $materialModifiers = [
        'diamond' => 2.5,
        'ruby' => 2.0,
        'emerald' => 2.2,
        'sapphire' => 1.8,
        'gold' => 1.4,
        'platinum' => 1.6,
        'silver' => 1.0,
        'titanium' => 1.2
    ];
    
    $itemNameLower = strtolower($itemName);
    $materialModifier = 1.0;
    
    foreach ($materialModifiers as $material => $mult) {
        if (strpos($itemNameLower, $material) !== false) {
            $materialModifier = max($materialModifier, $mult);
        }
    }
    
    // Calculate final price
    $finalPrice = $basePrice * $modifier * $materialModifier;
    
    // Round to nearest $5
    $finalPrice = round($finalPrice / 5) * 5;
    
    // Ensure minimum price of $50
    return max(50, $finalPrice);
}

// Generate CSRF token for future requests
generateCSRFToken();
?>