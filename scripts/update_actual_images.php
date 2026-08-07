<?php
/**
 * Update Image Files in Database
 * Scans *_php directories for actual images and updates catalog_products table
 */

require_once 'includes/db_config.php';

class ImageUpdater {
    private $pdo;
    private $imageDirectories = [];
    private $foundImages = [];
    
    public function __construct() {
        $this->connectDatabase();
        $this->scanImageDirectories();
    }
    
    private function connectDatabase() {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=CadmanClients",
                "cadman_admin",
                "Admin2025!Cadman",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "Database connected successfully.\n";
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function scanImageDirectories() {
        $baseDir = '/var/www/html/homesite';
        $phpDirs = glob($baseDir . '/*_php', GLOB_ONLYDIR);
        
        echo "Scanning image directories...\n";
        
        foreach ($phpDirs as $dir) {
            echo "Scanning: " . basename($dir) . "\n";
            $this->scanDirectoryForImages($dir);
        }
        
        echo "Found " . count($this->foundImages) . " total images\n";
    }
    
    private function scanDirectoryForImages($dir) {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        // Use recursive iterator to find all image files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($extension, $extensions)) {
                    $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
                    $relativePath = str_replace('/var/www/html/homesite/', '', $file->getPathname());
                    
                    // Store both the base filename and full path
                    if (!isset($this->foundImages[$baseFilename])) {
                        $this->foundImages[$baseFilename] = [];
                    }
                    $this->foundImages[$baseFilename][] = $relativePath;
                }
            }
        }
    }
    
    private function findImagesForProduct($productId) {
        $matchedImages = [];
        
        // Extract base ID (remove M/L suffix if present)
        $baseId = $productId;
        if (preg_match('/^(.+)([ML])$/', $productId, $matches)) {
            $baseId = $matches[1];
        }
        
        // Case-insensitive search patterns
        $searchPatterns = [
            $productId,
            $baseId,
            strtolower($productId),
            strtolower($baseId),
            strtoupper($productId),
            strtoupper($baseId)
        ];
        
        foreach ($searchPatterns as $pattern) {
            if (isset($this->foundImages[$pattern])) {
                $matchedImages = array_merge($matchedImages, $this->foundImages[$pattern]);
            }
            
            // Also check for partial matches (case-insensitive)
            foreach ($this->foundImages as $imageName => $paths) {
                if (strcasecmp($imageName, $pattern) === 0 && !in_array($paths[0], $matchedImages)) {
                    $matchedImages = array_merge($matchedImages, $paths);
                }
            }
        }
        
        // Remove duplicates and return
        return array_unique($matchedImages);
    }
    
    public function updateDatabase() {
        echo "\nUpdating database with actual image files...\n";
        
        // Get all products from database
        $stmt = $this->pdo->query("SELECT product_id FROM catalog_products ORDER BY product_id");
        $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updatedCount = 0;
        $noImagesCount = 0;
        
        foreach ($products as $productId) {
            $images = $this->findImagesForProduct($productId);
            
            if (empty($images)) {
                $imageValue = 'no images found';
                $noImagesCount++;
            } else {
                $imageValue = implode(',', $images);
                $updatedCount++;
            }
            
            // Update the database
            $sql = "UPDATE catalog_products SET image_files = ? WHERE product_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$imageValue, $productId]);
            
            if ($updatedCount % 50 == 0 && $updatedCount > 0) {
                echo "Updated $updatedCount products with images...\n";
            }
        }
        
        echo "\n=== UPDATE COMPLETE ===\n";
        echo "Products with images: $updatedCount\n";
        echo "Products without images: $noImagesCount\n";
        echo "Total processed: " . count($products) . "\n";
    }
    
    public function showImageStats() {
        echo "\n=== IMAGE STATISTICS ===\n";
        
        // Show sample matches
        $stmt = $this->pdo->query("
            SELECT product_id, image_files 
            FROM catalog_products 
            WHERE image_files != 'no images found' 
            LIMIT 10
        ");
        
        echo "\nSample products with images:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $imageList = strlen($row['image_files']) > 60 ? 
                substr($row['image_files'], 0, 60) . '...' : 
                $row['image_files'];
            echo "  {$row['product_id']}: $imageList\n";
        }
        
        // Show count by image status
        $stmt = $this->pdo->query("
            SELECT 
                CASE 
                    WHEN image_files = 'no images found' THEN 'No Images'
                    ELSE 'Has Images'
                END as status,
                COUNT(*) as count
            FROM catalog_products 
            GROUP BY status
        ");
        
        echo "\nImage status summary:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['status']}: {$row['count']}\n";
        }
        
        // Show products without images by category
        echo "\nProducts without images by category:\n";
        $stmt = $this->pdo->query("
            SELECT category, COUNT(*) as count 
            FROM catalog_products 
            WHERE image_files = 'no images found'
            GROUP BY category 
            ORDER BY count DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['category']}: {$row['count']}\n";
        }
    }
}

// Execute the image update
$updater = new ImageUpdater();
$updater->updateDatabase();
$updater->showImageStats();

?>