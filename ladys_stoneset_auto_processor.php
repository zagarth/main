<?php
/**
 * Auto-Processing System for Ladys Stoneset Collection
 * 
 * This script will:
 * 1. Scan for new images in Gems/ and Pearls/ directories
 * 2. Generate thumbnails automatically
 * 3. Create detail pages for new items
 * 4. Update the collection dynamically
 */

class LadysStonesetAutoProcessor {
    
    private $baseDir;
    private $thumbnailSize = 240;
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $quality = 85;
    
    public function __construct($baseDir = null) {
        $this->baseDir = $baseDir ?: dirname(__FILE__);
    }
    
    /**
     * Main processing function
     */
    public function processNewImages($verbose = true) {
        if ($verbose) {
            echo "Ladys Stoneset Auto-Processor\n";
            echo "============================\n\n";
        }
        
        $results = [
            'thumbnails_created' => 0,
            'detail_pages_created' => 0,
            'errors' => 0
        ];
        
        // Process Gems directory
        $results = $this->processCategory('Gems', 'gems', $results, $verbose);
        
        // Process Pearls directory
        $results = $this->processCategory('Pearls', 'pearls', $results, $verbose);
        
        if ($verbose) {
            $this->printSummary($results);
        }
        
        return $results;
    }
    
    /**
     * Process a specific category (Gems or Pearls)
     */
    private function processCategory($categoryDir, $categoryKey, $results, $verbose) {
        $categoryPath = $this->baseDir . '/ladys_stoneset_php/' . $categoryDir;
        $thumbsPath = $this->baseDir . '/ladys_stoneset_php/thumbs/' . $categoryDir;
        
        if (!is_dir($categoryPath)) {
            if ($verbose) echo "Directory not found: $categoryPath\n";
            return $results;
        }
        
        if ($verbose) {
            echo "Processing $categoryDir category...\n";
            echo str_repeat("-", 40) . "\n";
        }
        
        // Ensure thumbs directory exists
        if (!file_exists($thumbsPath)) {
            mkdir($thumbsPath, 0755, true);
            if ($verbose) echo "Created thumbs directory: $thumbsPath\n";
        }
        
        // Scan for images
        $images = $this->scanCategoryForImages($categoryPath);
        
        foreach ($images as $imagePath) {
            $fileName = basename($imagePath);
            $itemName = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            // Skip alternate versions for main processing
            if (preg_match('/_alt\d+/', $itemName)) {
                continue;
            }
            
            // Check if thumbnail exists
            $thumbPath = $thumbsPath . '/' . $fileName;
            $thumbnailNeeded = !file_exists($thumbPath);
            
            // Check if detail page exists
            $detailPagePath = $this->baseDir . '/ladys_stoneset_php/ladys_stoneset_php_' . $itemName . '_detail.php';
            $detailPageNeeded = !file_exists($detailPagePath);
            
            if ($thumbnailNeeded || $detailPageNeeded) {
                if ($verbose) echo "Processing new item: $fileName\n";
                
                // Create thumbnail if needed
                if ($thumbnailNeeded) {
                    if ($this->createThumbnail($imagePath, $thumbPath)) {
                        $results['thumbnails_created']++;
                        if ($verbose) echo "  ✓ Thumbnail created\n";
                    } else {
                        $results['errors']++;
                        if ($verbose) echo "  ✗ Thumbnail failed\n";
                    }
                }
                
                // Create detail page if needed
                if ($detailPageNeeded) {
                    if ($this->createDetailPage($itemName, $categoryKey, $extension)) {
                        $results['detail_pages_created']++;
                        if ($verbose) echo "  ✓ Detail page created\n";
                    } else {
                        $results['errors']++;
                        if ($verbose) echo "  ✗ Detail page failed\n";
                    }
                }
                
                if ($verbose) echo "\n";
            }
        }
        
        return $results;
    }
    
    /**
     * Scan category directory for images
     */
    private function scanCategoryForImages($directory) {
        $images = [];
        
        if (!is_dir($directory)) {
            return $images;
        }
        
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $this->supportedFormats)) {
                $images[] = $directory . '/' . $file;
            }
        }
        
        return $images;
    }
    
    /**
     * Create thumbnail for an image
     */
    private function createThumbnail($sourcePath, $destPath) {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Create source image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Calculate dimensions
        $aspectRatio = $sourceWidth / $sourceHeight;
        $size = $this->thumbnailSize;
        
        if ($aspectRatio > 1) {
            $thumbWidth = $size;
            $thumbHeight = $size / $aspectRatio;
        } else {
            $thumbWidth = $size * $aspectRatio;
            $thumbHeight = $size;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefill($thumbnail, 0, 0, $white);
        
        $offsetX = ($size - $thumbWidth) / 2;
        $offsetY = ($size - $thumbHeight) / 2;
        
        // Handle transparency
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        // Resize and copy
        imagecopyresampled(
            $thumbnail, $sourceImage,
            $offsetX, $offsetY, 0, 0,
            $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight
        );
        
        // Save thumbnail
        $success = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $success = imagejpeg($thumbnail, $destPath, $this->quality);
                break;
            case 'image/png':
                $success = imagepng($thumbnail, $destPath);
                break;
            case 'image/gif':
                $success = imagegif($thumbnail, $destPath);
                break;
            case 'image/webp':
                $success = imagewebp($thumbnail, $destPath, $this->quality);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $success;
    }
    
    /**
     * Create detail page for an item
     */
    private function createDetailPage($itemName, $categoryKey, $extension) {
        $categoryDisplay = ($categoryKey === 'gems') ? 'Gemstones' : 'Pearls';
        $categoryPath = ucfirst($categoryKey);
        $description = ($categoryKey === 'gems') 
            ? 'Beautiful rings and jewelry featuring precious and semi-precious gemstones'
            : 'Elegant pearl jewelry showcasing lustrous cultured and natural pearls';
        
        // Auto-generate price based on patterns
        $price = $this->calculatePrice($itemName, $categoryKey);
        
        $displayName = str_replace(['_', '-'], ' ', $itemName);
        $displayName = ucwords($displayName);
        
        $content = $this->generateDetailPageContent($itemName, $categoryKey, $categoryDisplay, $categoryPath, $description, $price, $displayName, $extension);
        
        $filename = $this->baseDir . '/ladys_stoneset_php/ladys_stoneset_php_' . $itemName . '_detail.php';
        
        return file_put_contents($filename, $content) !== false;
    }
    
    /**
     * Calculate price based on item name and category
     */
    private function calculatePrice($itemName, $categoryKey) {
        if ($categoryKey === 'gems') {
            $basePrice = 1250;
            
            // Premium for 2000+ series
            if (preg_match('/^2\d{3}/', $itemName)) {
                $basePrice = 1550;
            }
            
            // Custom series premium
            if (strpos($itemName, 'C') === 0) {
                $basePrice = 1450;
                // Special premium for C297
                if ($itemName === 'C297') {
                    $basePrice = 1750;
                }
            }
            
        } else { // pearls
            $basePrice = 950;
            
            // Premium for custom pieces
            if (strpos($itemName, 'C') === 0) {
                $basePrice = 1150;
            }
        }
        
        return $basePrice;
    }
    
    /**
     * Generate the complete detail page content
     */
    private function generateDetailPageContent($itemName, $categoryKey, $categoryDisplay, $categoryPath, $description, $price, $displayName, $extension) {
        return <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="{$itemName} {$categoryKey} ring, ladies jewelry, precious stones, Cadman Manufacturing" />
<link rel="icon" sizes="" href="../favicon.ico">
<title>{$itemName} - Lady's Stoneset Collection | Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include '../navigation.php'; renderNavigation('ladys_stoneset'); ?>
    <?php include '../topButton.php'; renderTopButton(); ?>
    
    <?php
    // Define item information
    \$itemName = '{$itemName}';
    \$category = '{$categoryKey}';
    \$displayName = '{$displayName}';
    \$price = {$price};
    \$categoryDisplay = '{$categoryDisplay}';
    \$description = '{$description}';
    
    // Image paths
    \$mainImage = '{$categoryPath}/{$itemName}.{$extension}';
    \$imagePath = \$mainImage;
    
    // Function to get related images (alternate views)
    function getRelatedImages(\$directory, \$baseFilename) {
        \$images = [];
        if (is_dir(\$directory)) {
            \$files = scandir(\$directory);
            \$baseName = pathinfo(\$baseFilename, PATHINFO_FILENAME);
            
            foreach (\$files as \$file) {
                if (pathinfo(\$file, PATHINFO_EXTENSION) === 'png' || pathinfo(\$file, PATHINFO_EXTENSION) === 'jpg') {
                    \$fileName = pathinfo(\$file, PATHINFO_FILENAME);
                    if (\$fileName === \$baseName || strpos(\$fileName, \$baseName . '_alt') === 0) {
                        \$images[] = \$file;
                    }
                }
            }
        }
        return \$images;
    }
    
    \$relatedImages = getRelatedImages('{$categoryPath}', '{$itemName}.{$extension}');
    ?>
    
    <div class="item-detail-container">
        <a href="../Ladys_Stoneset.php" class="back-link">← Back to Lady's Stoneset Collection</a>
        
        <div class="item-detail-grid">
            <!-- Image Section -->
            <div class="item-images">
                <img src="<?php echo \$imagePath; ?>" alt="<?php echo \$displayName; ?>" class="main-image" id="main-image">
                
                <?php if (count(\$relatedImages) > 1): ?>
                <div class="thumbnail-gallery">
                    <?php foreach (\$relatedImages as \$relatedImage): ?>
                    <img src="{$categoryPath}/<?php echo \$relatedImage; ?>" 
                         alt="<?php echo \$displayName; ?>" 
                         class="thumbnail<?php echo (\$relatedImage === '{$itemName}.{$extension}') ? ' active' : ''; ?>"
                         onclick="changeMainImage('{$categoryPath}/<?php echo \$relatedImage; ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Info Section -->
            <div class="item-info">
                <div class="item-title"><?php echo \$displayName; ?></div>
                <div class="item-subtitle"><?php echo \$categoryDisplay; ?> - <?php echo strtoupper(\$itemName); ?></div>
                <div class="item-price">Starting at \$<?php echo number_format(\$price); ?></div>
                
                <div class="item-description">
                    <?php echo \$description; ?>. This exquisite piece represents the finest in ladies' jewelry craftsmanship and timeless elegance. 
                    Each item is carefully crafted to meet our high standards of quality and sophistication.
                </div>
                
                <div class="item-features">
                    <h3 style="margin-top: 0; color: #333;">Product Features</h3>
                    <ul class="feature-list">
                        <li><span>Material</span><span><?php echo (\$category === 'gems') ? 'Precious Metal with Gemstone' : 'Precious Metal with Pearl'; ?></span></li>
                        <li><span>Category</span><span><?php echo \$categoryDisplay; ?></span></li>
                        <li><span>Style</span><span><?php echo (\$category === 'gems') ? 'Gemstone Setting' : 'Pearl Setting'; ?></span></li>
                        <li><span>Customization</span><span>Available</span></li>
                        <li><span>Warranty</span><span>Lifetime</span></li>
                    </ul>
                </div>
                
                <div class="action-buttons">
                    <a href="#formtable" class="btn btn-primary">Get Quote</a>
                    <a href="../Ladys_Stoneset.php" class="btn btn-secondary">View More Items</a>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-top: 40px;">
            <h3 style="color: #333; margin-bottom: 15px;">About Our Lady's Stoneset Collection</h3>
            <p style="line-height: 1.6; color: #666; margin-bottom: 20px;">
                Our Lady's Stoneset Collection celebrates the essence of feminine beauty through carefully selected gemstones and lustrous pearls. 
                Each piece is designed to enhance your natural elegance with sophisticated craftsmanship and timeless style. 
                We offer extensive customization options including gemstone selection, metal choices, and setting styles.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;"><?php echo (\$category === 'gems') ? 'Premium Gemstones' : 'Lustrous Pearls'; ?></h4>
                    <p style="color: #666; font-size: 14px;"><?php echo (\$category === 'gems') ? 'Carefully selected precious and semi-precious stones for exceptional beauty.' : 'Cultured and natural pearls selected for their exceptional luster and quality.'; ?></p>
                </div>
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;">Expert Craftsmanship</h4>
                    <p style="color: #666; font-size: 14px;">Traditional techniques combined with modern precision for lasting quality.</p>
                </div>
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;">Custom Options</h4>
                    <p style="color: #666; font-size: 14px;">Available in various metals and settings to match your personal style.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function changeMainImage(src, thumbnail) {
        document.getElementById('main-image').src = src;
        
        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
    
    // Add image loading animation
    \$(document).ready(function() {
        \$('.main-image').on('load', function() {
            \$(this).css('opacity', '0').animate({opacity: 1}, 300);
        });
        
        // Initialize with fade-in effect
        \$('.item-detail-container').css('opacity', '0').animate({opacity: 1}, 500);
    });
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Print processing summary
     */
    private function printSummary($results) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "PROCESSING SUMMARY:\n";
        echo "Thumbnails created: " . $results['thumbnails_created'] . "\n";
        echo "Detail pages created: " . $results['detail_pages_created'] . "\n";
        echo "Errors: " . $results['errors'] . "\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    /**
     * Get current collection status
     */
    public function getCollectionStatus() {
        $status = [
            'gems' => [],
            'pearls' => []
        ];
        
        // Check Gems
        $gemsPath = $this->baseDir . '/ladys_stoneset_php/Gems';
        if (is_dir($gemsPath)) {
            $images = $this->scanCategoryForImages($gemsPath);
            foreach ($images as $imagePath) {
                $fileName = basename($imagePath);
                $itemName = pathinfo($fileName, PATHINFO_FILENAME);
                
                if (preg_match('/_alt\d+/', $itemName)) continue;
                
                $thumbExists = file_exists($this->baseDir . '/ladys_stoneset_php/thumbs/Gems/' . $fileName);
                $detailExists = file_exists($this->baseDir . '/ladys_stoneset_php/ladys_stoneset_php_' . $itemName . '_detail.php');
                
                $status['gems'][$itemName] = [
                    'image' => $fileName,
                    'thumbnail' => $thumbExists,
                    'detail_page' => $detailExists,
                    'ready' => $thumbExists && $detailExists
                ];
            }
        }
        
        // Check Pearls
        $pearlsPath = $this->baseDir . '/ladys_stoneset_php/Pearls';
        if (is_dir($pearlsPath)) {
            $images = $this->scanCategoryForImages($pearlsPath);
            foreach ($images as $imagePath) {
                $fileName = basename($imagePath);
                $itemName = pathinfo($fileName, PATHINFO_FILENAME);
                
                if (preg_match('/_alt\d+/', $itemName)) continue;
                
                $thumbExists = file_exists($this->baseDir . '/ladys_stoneset_php/thumbs/Pearls/' . $fileName);
                $detailExists = file_exists($this->baseDir . '/ladys_stoneset_php/ladys_stoneset_php_' . $itemName . '_detail.php');
                
                $status['pearls'][$itemName] = [
                    'image' => $fileName,
                    'thumbnail' => $thumbExists,
                    'detail_page' => $detailExists,
                    'ready' => $thumbExists && $detailExists
                ];
            }
        }
        
        return $status;
    }
}

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    die("ERROR: GD extension is not loaded. Please enable GD extension in PHP.\n");
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Command line execution
    $processor = new LadysStonesetAutoProcessor();
    $processor->processNewImages(true);
} elseif (isset($_GET['action'])) {
    // Web API execution
    $processor = new LadysStonesetAutoProcessor();
    
    switch ($_GET['action']) {
        case 'process':
            header('Content-Type: application/json');
            $results = $processor->processNewImages(false);
            echo json_encode($results);
            break;
            
        case 'status':
            header('Content-Type: application/json');
            $status = $processor->getCollectionStatus();
            echo json_encode($status);
            break;
            
        default:
            echo "<pre>";
            $processor->processNewImages(true);
            echo "</pre>";
    }
} else {
    // Default web execution
    echo "<pre>";
    $processor = new LadysStonesetAutoProcessor();
    $processor->processNewImages(true);
    echo "</pre>";
    
    echo "<br><br>";
    echo "<h3>API Endpoints:</h3>";
    echo "<ul>";
    echo "<li><a href='?action=process'>Process New Images (JSON)</a></li>";
    echo "<li><a href='?action=status'>Check Collection Status (JSON)</a></li>";
    echo "</ul>";
}
?>
