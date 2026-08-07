<?php
/**
 * Jewelry Database Manager
 * Handles all database operations for jewelry collections
 */

require_once __DIR__ . '/db_config.php';

class JewelryDatabase {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Get all active collections with their categories
     */
    public function getCollections($activeOnly = true) {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT cat.category_id) as category_count,
                       COUNT(DISTINCT i.item_id) as item_count
                FROM jewelry_collections c
                LEFT JOIN jewelry_categories cat ON c.collection_id = cat.collection_id AND cat.is_active = 1
                LEFT JOIN jewelry_items i ON c.collection_id = i.collection_id AND i.is_active = 1
                WHERE " . ($activeOnly ? "c.is_active = 1" : "1=1") . "
                GROUP BY c.collection_id
                ORDER BY c.sort_order, c.collection_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get collection by key
     */
    public function getCollection($collectionKey) {
        $sql = "SELECT * FROM jewelry_collections WHERE collection_key = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$collectionKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get categories for a collection
     */
    public function getCategories($collectionKey, $activeOnly = true) {
        $sql = "SELECT cat.*, c.collection_key,
                       COUNT(i.item_id) as item_count
                FROM jewelry_categories cat
                JOIN jewelry_collections c ON cat.collection_id = c.collection_id
                LEFT JOIN jewelry_items i ON cat.category_id = i.category_id AND i.is_active = 1
                WHERE c.collection_key = ? " . ($activeOnly ? "AND cat.is_active = 1" : "") . "
                GROUP BY cat.category_id
                ORDER BY cat.sort_order, cat.category_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$collectionKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get jewelry items for a collection/category
     */
    public function getItems($collectionKey, $categoryKey = null, $limit = null, $offset = 0) {
        $sql = "SELECT i.*, c.collection_key, cat.category_key, cat.category_name,
                       v.file_path as thumbnail_path
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                LEFT JOIN jewelry_item_variants v ON i.item_id = v.item_id AND v.variant_type = 'thumbnail'
                WHERE c.collection_key = ? AND i.is_active = 1";
        
        $params = [$collectionKey];
        
        if ($categoryKey) {
            $sql .= " AND cat.category_key = ?";
            $params[] = $categoryKey;
        }
        
        $sql .= " ORDER BY i.is_featured DESC, i.sort_order, i.item_name";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get item count for pagination
     */
    public function getItemCount($collectionKey, $categoryKey = null) {
        $sql = "SELECT COUNT(*) as count
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                WHERE c.collection_key = ? AND i.is_active = 1";
        
        $params = [$collectionKey];
        
        if ($categoryKey) {
            $sql .= " AND cat.category_key = ?";
            $params[] = $categoryKey;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get a specific jewelry item
     */
    public function getItem($collectionKey, $itemCode) {
        $sql = "SELECT i.*, c.collection_key, cat.category_key, cat.category_name, cat.base_price as category_base_price
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                WHERE c.collection_key = ? AND i.item_code = ? AND i.is_active = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$collectionKey, $itemCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get item variants (additional images)
     */
    public function getItemVariants($itemId) {
        $sql = "SELECT * FROM jewelry_item_variants WHERE item_id = ? ORDER BY sort_order, variant_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new jewelry item to the database
     */
    public function addItem($data) {
        $sql = "INSERT INTO jewelry_items 
                (collection_id, category_id, item_code, item_name, description, base_price, 
                 file_path, thumbnail_path, image_alt, file_size, image_width, image_height, 
                 mime_type, sort_order) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['collection_id'],
            $data['category_id'],
            $data['item_code'],
            $data['item_name'],
            $data['description'],
            $data['base_price'],
            $data['file_path'],
            $data['thumbnail_path'],
            $data['image_alt'],
            $data['file_size'],
            $data['image_width'],
            $data['image_height'],
            $data['mime_type'],
            $data['sort_order'] ?? 0
        ]);
    }
    
    /**
     * Add item variant (additional image)
     */
    public function addItemVariant($itemId, $variantType, $filePath, $fileSize = null, $width = null, $height = null, $sortOrder = 0) {
        $sql = "INSERT INTO jewelry_item_variants 
                (item_id, variant_type, file_path, file_size, image_width, image_height, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$itemId, $variantType, $filePath, $fileSize, $width, $height, $sortOrder]);
    }
    
    /**
     * Log file upload
     */
    public function logUpload($data) {
        $sql = "INSERT INTO jewelry_upload_log 
                (item_id, original_filename, secure_filename, file_path, collection_key, 
                 category_key, file_size, mime_type, upload_status, uploaded_by, processing_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['item_id'] ?? null,
            $data['original_filename'],
            $data['secure_filename'],
            $data['file_path'],
            $data['collection_key'],
            $data['category_key'],
            $data['file_size'],
            $data['mime_type'],
            $data['upload_status'] ?? 'uploaded',
            $data['uploaded_by'] ?? 'admin',
            $data['processing_notes'] ?? null
        ]);
    }
    
    /**
     * Get collection and category IDs for upload processing
     */
    public function getCollectionCategoryIds($collectionKey, $categoryKey = null) {
        $sql = "SELECT c.collection_id, cat.category_id
                FROM jewelry_collections c
                LEFT JOIN jewelry_categories cat ON c.collection_id = cat.collection_id AND cat.category_key = ?
                WHERE c.collection_key = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryKey, $collectionKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if item code exists in collection and category
     */
    public function itemCodeExists($collectionKey, $itemCode, $categoryKey = null) {
        $sql = "SELECT COUNT(*) as count
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                WHERE c.collection_key = ? AND i.item_code = ?";
        
        $params = [$collectionKey, $itemCode];
        
        if ($categoryKey) {
            $sql .= " AND cat.category_key = ?";
            $params[] = $categoryKey;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Get featured items across collections
     */
    public function getFeaturedItems($limit = 6) {
        $sql = "SELECT i.*, c.collection_key, cat.category_key
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                WHERE i.is_featured = 1 AND i.is_active = 1 AND c.is_active = 1
                ORDER BY RAND()
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search items across all collections
     */
    public function searchItems($searchTerm, $collectionKey = null, $limit = 50) {
        $sql = "SELECT i.*, c.collection_key, cat.category_key, cat.category_name
                FROM jewelry_items i
                JOIN jewelry_collections c ON i.collection_id = c.collection_id
                LEFT JOIN jewelry_categories cat ON i.category_id = cat.category_id
                WHERE i.is_active = 1 AND c.is_active = 1
                AND (i.item_name LIKE ? OR i.item_code LIKE ? OR i.description LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
        
        if ($collectionKey) {
            $sql .= " AND c.collection_key = ?";
            $params[] = $collectionKey;
        }
        
        $sql .= " ORDER BY i.item_name LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>