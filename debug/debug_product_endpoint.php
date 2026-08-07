<?php
header('Content-Type: application/json');

// Debug the actual request being received
error_log('Debug endpoint called with: ' . print_r($_POST, true));

$productId = $_POST['product_id'] ?? '';
if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID required', 'received_post' => $_POST]);
    exit;
}

try {
    // Test database connection
    include 'includes/db_config.php';
    $pdo = getDBConnection();
    
    // Test simple query first
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM catalog_products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($countResult['count'] == 0) {
        echo json_encode(['success' => false, 'error' => 'Product not found', 'product_id' => $productId, 'count_check' => $countResult]);
        exit;
    }
    
    // If we get here, the product exists
    echo json_encode(['success' => true, 'message' => 'Product found', 'product_id' => $productId, 'count' => $countResult['count']]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage(), 'product_id' => $productId]);
}
?>