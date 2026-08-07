<?php
/**
 * Image Migration Script
 * Scans existing image directories and populates the database with references
 */

require_once __DIR__ . '/../includes/JewelryDatabase.php';

class ImageMigration {
    private $db;
    private $baseDir;
    private $migrationLog = [];
    
    public function __construct() {
        $this->db = new JewelryDatabase();
        $this->baseDir = dirname(__DIR__);
    }
    
    /**
     * Run the complete migration
     */
    public function migrate($dryRun = false) {
        $this->log("Starting image migration" . ($dryRun ? " (DRY RUN)" : ""));
        
        $collections = $this->db->getCollections();
        
        foreach ($collections as $collection) {
            $this->migrateCollection($collection, $dryRun);
        }
        
        $this->log("Migration completed");
        return $this->migrationLog;
    }
    
    /**
     * Migrate a single collection
     */
    private function migrateCollection($collection, $dryRun = false) {
        $this->log("Migrating collection: " . $collection['collection_name']);
        
        $categories = $this->db->getCategories($collection['collection_key']);
        
        foreach ($categories as $category) {
            $this->migrateCategory($collection, $category, $dryRun);
        }
        
        // Also check for images in the main collection directory (no category)
        $this->migrateCategoryDirectory($collection, null, $collection['directory_path'] . '/images', $dryRun);
    }
    
    /**
     * Migrate a single category
     */
    private function migrateCategory($collection, $category, $dryRun = false) {
        $this->log("  Migrating category: " . $category['category_name']);
        
        // Handle case-sensitive directory paths
        $categoryPath = $this->baseDir . '/' . $category['directory_path'];
        
        // Check for exact path first, then try case variations
        if (!is_dir($categoryPath)) {
            // Try different case combinations
            $pathVariations = [
                $categoryPath,
                str_replace('_php', '_php', $categoryPath), // Keep original case
                str_replace('Engagement_php', 'engagement_php', $categoryPath),
                str_replace('Frontline_Workers_php', 'frontline_workers_php', $categoryPath),
                str_replace('corp_php', 'Corp_php', $categoryPath)
            ];
            
            $foundPath = null;
            foreach ($pathVariations as $variation) {
                if (is_dir($variation)) {
                    $foundPath = $variation;
                    break;
                }
            }
            
            if (!$foundPath) {
                $this->log("    Directory not found: $categoryPath", 'warning');
                return;
            }
            
            $categoryPath = $foundPath;
        }
        
        $this->migrateCategoryDirectory($collection, $category, $categoryPath, $dryRun);
    }
    
    /**
     * Migrate images from a specific directory
     */
    private function migrateCategoryDirectory($collection, $category, $dirPath, $dryRun = false) {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // First check for images directly in the directory
        $files = glob($dirPath . '/*.{' . implode(',', $imageExtensions) . '}', GLOB_BRACE);
        
        foreach ($files as $filePath) {
            $this->migrateImage($collection, $category, $filePath, $dryRun);
        }
        
        // Also check subdirectories (like MK_series, MM_series, etc.)
        $subdirectories = glob($dirPath . '/*', GLOB_ONLYDIR);
        foreach ($subdirectories as $subdir) {
            $subdirName = basename($subdir);
            
            // Skip thumbs directory
            if ($subdirName === 'thumbs') {
                continue;
            }
            
            $this->log("    Checking subdirectory: $subdirName");
            $subFiles = glob($subdir . '/*.{' . implode(',', $imageExtensions) . '}', GLOB_BRACE);
            
            foreach ($subFiles as $filePath) {
                $this->migrateImage($collection, $category, $filePath, $dryRun);
            }
        }
    }
    
    /**
     * Migrate a single image file
     */
    private function migrateImage($collection, $category, $filePath, $dryRun = false) {
        $filename = basename($filePath);
        $itemCode = pathinfo($filename, PATHINFO_FILENAME);
        
        // Skip if item already exists in this specific category
        if ($this->db->itemCodeExists($collection['collection_key'], $itemCode, $category ? $category['category_key'] : null)) {
            $this->log("    Skipping existing item: $itemCode in " . ($category ? $category['category_key'] : 'root'));
            return;
        }
        
        // Get image info
        $imageInfo = @getimagesize($filePath);
        $fileSize = @filesize($filePath);
        $mimeType = $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
        
        // Generate item name from filename
        $itemName = $this->generateItemName($itemCode);
        
        // Calculate relative path from web root
        $relativePath = str_replace($this->baseDir . '/', '', $filePath);
        
        // Look for thumbnail
        $thumbnailPath = $this->findThumbnail($filePath);
        $thumbnailRelativePath = $thumbnailPath ? str_replace($this->baseDir . '/', '', $thumbnailPath) : null;
        
        // Prepare item data
        $itemData = [
            'collection_id' => $collection['collection_id'],
            'category_id' => $category ? $category['category_id'] : null,
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'description' => $this->generateDescription($collection, $category, $itemName),
            'base_price' => $category ? $category['base_price'] : 500.00,
            'file_path' => $relativePath,
            'thumbnail_path' => $thumbnailRelativePath,
            'image_alt' => $itemName . ' - ' . $collection['collection_name'],
            'file_size' => $fileSize,
            'image_width' => $imageInfo ? $imageInfo[0] : null,
            'image_height' => $imageInfo ? $imageInfo[1] : null,
            'mime_type' => $mimeType,
            'sort_order' => 0
        ];
        
        if ($dryRun) {
            $this->log("    Would add: $itemCode ($relativePath)");
        } else {
            try {
                if ($this->db->addItem($itemData)) {
                    $this->log("    Added: $itemCode");
                    
                    // Log the upload
                    $this->db->logUpload([
                        'original_filename' => $filename,
                        'secure_filename' => $filename,
                        'file_path' => $relativePath,
                        'collection_key' => $collection['collection_key'],
                        'category_key' => $category ? $category['category_key'] : null,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'upload_status' => 'processed',
                        'uploaded_by' => 'migration',
                        'processing_notes' => 'Migrated from existing files'
                    ]);
                } else {
                    $this->log("    Failed to add: $itemCode", 'error');
                }
            } catch (Exception $e) {
                $this->log("    Error adding $itemCode: " . $e->getMessage(), 'error');
            }
        }
    }
    
    /**
     * Find corresponding thumbnail for an image
     */
    private function findThumbnail($imagePath) {
        $pathInfo = pathinfo($imagePath);
        $directory = dirname($imagePath);
        
        // Common thumbnail directory patterns
        $thumbnailPaths = [
            $directory . '/thumbs/' . $pathInfo['basename'],
            dirname($directory) . '/thumbs/' . basename($directory) . '/' . $pathInfo['basename'],
            dirname($directory) . '/thumbs/' . $pathInfo['basename']
        ];
        
        foreach ($thumbnailPaths as $thumbPath) {
            if (file_exists($thumbPath)) {
                return $thumbPath;
            }
        }
        
        return null;
    }
    
    /**
     * Generate a readable item name from filename
     */
    private function generateItemName($itemCode) {
        // Remove common prefixes/suffixes
        $name = preg_replace('/^(img_|image_|pic_|photo_)/', '', $itemCode);
        $name = preg_replace('/(_img|_image|_pic|_photo)$/', '', $name);
        
        // Convert underscores and hyphens to spaces
        $name = str_replace(['_', '-'], ' ', $name);
        
        // Capitalize words
        $name = ucwords(strtolower($name));
        
        // Handle special cases
        $name = preg_replace('/\bDb\b/', 'DB', $name);
        $name = preg_replace('/\bFp(\d+)\b/', 'FP$1', $name);
        $name = preg_replace('/\bSr(\d+)\b/', 'SR$1', $name);
        
        return trim($name);
    }
    
    /**
     * Generate description based on collection and category
     */
    private function generateDescription($collection, $category, $itemName) {
        $descriptions = [
            'accessories' => [
                'crosses_lockets' => "Beautiful {$itemName} featuring elegant design perfect for personal expression and faith.",
                'idents' => "Professional {$itemName} identification piece ideal for business and formal settings.",
                'pendant_earrings' => "Elegant {$itemName} pendant earrings designed to complement your style."
            ],
            'bands' => [
                'celtic' => "Traditional Celtic {$itemName} featuring intricate knotwork and heritage designs.",
                'cultural' => "Cultural {$itemName} celebrating heritage and tradition with meaningful symbolism.",
                'fancy' => "Elaborate {$itemName} with ornate detailing for those who appreciate luxury.",
                'plain' => "Classic {$itemName} with timeless simplicity and elegant craftsmanship."
            ],
            'family' => [
                'mother' => "Beautiful {$itemName} celebrating the special bond with mothers.",
                'father' => "Meaningful {$itemName} honoring fathers and their important role.",
                'daughter' => "Delicate {$itemName} perfect for daughters and young women."
            ],
            'ladys_stoneset' => [
                'gems' => "Exquisite {$itemName} featuring precious gemstones and expert craftsmanship.",
                'pearls' => "Elegant {$itemName} showcasing lustrous pearls and refined beauty."
            ],
            'school' => [
                'bands' => "Academic {$itemName} celebrating educational achievements and school pride.",
                'crest_tops' => "School {$itemName} featuring institutional emblems and academic symbols."
            ]
        ];
        
        $collectionKey = $collection['collection_key'];
        $categoryKey = $category ? $category['category_key'] : null;
        
        if (isset($descriptions[$collectionKey][$categoryKey])) {
            return $descriptions[$collectionKey][$categoryKey];
        } elseif (isset($descriptions[$collectionKey])) {
            $categoryDescs = array_values($descriptions[$collectionKey]);
            return str_replace('{$itemName}', $itemName, $categoryDescs[0]);
        }
        
        return "Beautiful {$itemName} from our {$collection['collection_name']} collection, crafted with attention to detail and designed to last.";
    }
    
    /**
     * Log migration progress
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message";
        
        $this->migrationLog[] = $logEntry;
        echo $logEntry . "\n";
    }
    
    /**
     * Get migration statistics
     */
    public function getStats() {
        $collections = $this->db->getCollections();
        $stats = [
            'collections' => count($collections),
            'categories' => 0,
            'items' => 0
        ];
        
        foreach ($collections as $collection) {
            $categories = $this->db->getCategories($collection['collection_key']);
            $stats['categories'] += count($categories);
            $stats['items'] += $collection['item_count'];
        }
        
        return $stats;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $migration = new ImageMigration();
    
    $dryRun = in_array('--dry-run', $argv);
    $showStats = in_array('--stats', $argv);
    
    if ($showStats) {
        $stats = $migration->getStats();
        echo "Current Database Stats:\n";
        echo "Collections: " . $stats['collections'] . "\n";
        echo "Categories: " . $stats['categories'] . "\n";
        echo "Items: " . $stats['items'] . "\n";
        exit;
    }
    
    echo "Image Migration Tool\n";
    echo "==================\n";
    
    if ($dryRun) {
        echo "DRY RUN MODE - No changes will be made\n\n";
    }
    
    $migration->migrate($dryRun);
    
    echo "\nFinal stats:\n";
    $stats = $migration->getStats();
    echo "Collections: " . $stats['collections'] . "\n";
    echo "Categories: " . $stats['categories'] . "\n";
    echo "Items: " . $stats['items'] . "\n";
}
?>