<?php
// Require authentication for all processor actions except status checks
if (!isset($_GET['action']) || $_GET['action'] !== 'status') {
    require_once 'auth.php';
    requireAdmin();
}

/**
 * Universal Collection Processor for Cadman Manufacturing
 * Automated content management system for all jewelry collections
 */

class UniversalCollectionProcessor {
    
    private $baseDir;
    private $thumbnailSize = 240;
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $quality = 85;
    
    // Excluded collections/directories that should not be processed
    private $excludedCollections = ['shoulders', 'classicshoulders'];
    
    // Collection configurations
    private $collections = [
        'accessories' => [
            'name' => 'Accessories',
            'directory' => 'accessories_php',
            'categories' => [
                'images/Crosses_and_Lockets' => ['display' => 'Crosses & Lockets', 'base_price' => 225],
                'images/Idents' => ['display' => 'Idents', 'base_price' => 275],
                'images/Pendant_earrings' => ['display' => 'Pendant Earrings', 'base_price' => 325]
            ],
            'description' => 'Beautiful accessories to complement your jewelry collection',
            'navigation_key' => 'accessories',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'bands' => [
            'name' => 'Bands',
            'directory' => 'bands_php',
            'categories' => [
                'images/celtic' => ['display' => 'Celtic', 'base_price' => 825],
                'images/cultural' => ['display' => 'Cultural', 'base_price' => 725],
                'images/fancy' => ['display' => 'Fancy', 'base_price' => 925],
                'images/plain' => ['display' => 'Plain', 'base_price' => 625]
            ],
            'description' => 'Wedding bands featuring exquisite craftsmanship and timeless designs',
            'navigation_key' => 'bands',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'corp' => [
            'name' => 'Corporate',
            'directory' => 'corp_php',
            'categories' => [
                'images' => ['display' => 'Corporate Jewelry', 'base_price' => 375]
            ],
            'description' => 'Corporate awards and recognition jewelry',
            'navigation_key' => 'corp',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'engagement' => [
            'name' => 'Engagement',
            'directory' => 'Engagement_php',
            'categories' => [
                'images/MK_series' => ['display' => 'MK Series', 'base_price' => 2850],
                'images/MM_series' => ['display' => 'MM Series', 'base_price' => 3250],
                'images/WM_series' => ['display' => 'WM Series', 'base_price' => 3650]
            ],
            'description' => 'Exquisite engagement rings featuring brilliant diamonds',
            'navigation_key' => 'engagement',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'family' => [
            'name' => 'Family',
            'directory' => 'family_php',
            'categories' => [
                'images/Mother' => ['display' => 'Mother', 'base_price' => 425],
                'images/Father' => ['display' => 'Father', 'base_price' => 475],
                'images/Daughter' => ['display' => 'Daughter', 'base_price' => 375]
            ],
            'description' => 'Beautiful family jewelry celebrating the bonds that matter most',
            'navigation_key' => 'family',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'ladys_stoneset' => [
            'name' => 'Lady\'s Stoneset',
            'directory' => 'ladys_stoneset_php',
            'categories' => [
                'Gems' => ['display' => 'Gemstones', 'base_price' => 1250],
                'Pearls' => ['display' => 'Pearls', 'base_price' => 950]
            ],
            'description' => 'Ladies\' jewelry featuring precious gemstones and lustrous pearls',
            'navigation_key' => 'ladys_stoneset',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
        'school' => [
            'name' => 'School',
            'directory' => 'school_php',
            'categories' => [
                'Bands' => ['display' => 'School Bands', 'base_price' => 425],
                'Crest_tops' => ['display' => 'Crest Tops', 'base_price' => 475]
            ],
            'description' => 'Academic achievement jewelry for schools and institutions',
            'navigation_key' => 'school',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ],
                'signet' => [
            'name' => 'Signet',
            'directory' => 'signet_php',
            'categories' => [
                'images/crest_top' => ['display' => 'Crest Top', 'base_price' => 425],
                'images/deep_carved' => ['display' => 'Deep Carved', 'base_price' => 475],
                'images/raised' => ['display' => 'Raised', 'base_price' => 525]
            ],
            'description' => 'Custom signet rings and personalized seals',
            'navigation_key' => 'signet',
            'uses_unified_detail' => true // Uses dynamic unified_detail.php script
        ]
    ];
    
    public function __construct($baseDir = null) {
        $this->baseDir = $baseDir ?: dirname(__FILE__);
    }
    
    /**
     * Check if a collection should be excluded from processing
     */
    private function isCollectionExcluded($collectionKey) {
        return in_array($collectionKey, $this->excludedCollections) || 
               in_array(strtolower($collectionKey), $this->excludedCollections) ||
               in_array($collectionKey . '_php', $this->excludedCollections) ||
               in_array(strtolower($collectionKey . '_php'), $this->excludedCollections);
    }
    
    /**
     * Process all collections or a specific collection
     */
    public function processCollections($collectionName = null, $verbose = true) {
        if ($verbose) {
            echo "Universal Collection Auto-Processor\n";
            echo "===================================\n\n";
        }
        
        $results = [
            'collections_processed' => 0,
            'thumbnails_created' => 0,
            'detail_pages_created' => 0,
            'errors' => 0
        ];
        
        $collectionsToProcess = $collectionName ? [$collectionName => $this->collections[$collectionName]] : $this->collections;
        
        foreach ($collectionsToProcess as $key => $config) {
            if (!isset($this->collections[$key])) {
                if ($verbose) echo "Unknown collection: $key\n";
                continue;
            }
            
            // Check if collection is excluded from processing
            if ($this->isCollectionExcluded($key)) {
                if ($verbose) echo "Skipping excluded collection: {$config['name']} ($key)\n";
                continue;
            }
            
            $results = $this->processCollection($key, $config, $results, $verbose);
            $results['collections_processed']++;
        }
        
        if ($verbose) {
            $this->printSummary($results);
        }
        
        return $results;
    }
    
    /**
     * Process a specific collection
     */
    private function processCollection($collectionKey, $config, $results, $verbose) {
        $collectionPath = $this->baseDir . '/' . $config['directory'];
        
        if (!is_dir($collectionPath)) {
            if ($verbose) echo "Collection directory not found: {$config['directory']}\n";
            return $results;
        }
        
        if ($verbose) {
            echo "Processing {$config['name']} collection...\n";
            echo str_repeat("-", 50) . "\n";
        }
        
        foreach ($config['categories'] as $categoryPath => $categoryConfig) {
            $fullCategoryPath = $collectionPath . '/' . $categoryPath;
            
            if (!is_dir($fullCategoryPath)) {
                if ($verbose) echo "Category directory not found: $categoryPath\n";
                continue;
            }
            
            // Ensure thumbs directory exists
            $thumbsPath = $this->getThumbsPath($collectionPath, $categoryPath);
            if (!file_exists($thumbsPath)) {
                mkdir($thumbsPath, 0755, true);
                if ($verbose) echo "Created thumbs directory: $thumbsPath\n";
            }
            
            // Process images in this category
            $images = $this->scanCategoryForImages($fullCategoryPath);
            
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
                
                // Check if detail page exists (skip for collections using unified detail scripts)
                $detailPageNeeded = false;
                if (!isset($config['uses_unified_detail']) || !$config['uses_unified_detail']) {
                    $detailPagePath = $collectionPath . '/' . $config['directory'] . '_' . $itemName . '_detail.php';
                    $detailPageNeeded = !file_exists($detailPagePath);
                }
                
                if ($thumbnailNeeded || $detailPageNeeded) {
                    if ($verbose) echo "Processing: {$config['name']} - $fileName\n";
                    
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
                    
                    // Create detail page if needed (only for non-unified collections)
                    if ($detailPageNeeded) {
                        if ($this->createDetailPage($collectionKey, $config, $categoryPath, $categoryConfig, $itemName, $extension)) {
                            $results['detail_pages_created']++;
                            if ($verbose) echo "  ✓ Detail page created\n";
                        } else {
                            $results['errors']++;
                            if ($verbose) echo "  ✗ Detail page failed\n";
                        }
                    } elseif (isset($config['uses_unified_detail']) && $config['uses_unified_detail'] && $verbose) {
                        echo "  ◦ Using unified detail script (no individual page needed)\n";
                    }
                }
            }
        }
        
        if ($verbose) echo "\n";
        return $results;
    }
    
    /**
     * Get the appropriate thumbs path for a category
     */
    private function getThumbsPath($collectionPath, $categoryPath) {
        $thumbsBase = $collectionPath . '/thumbs';
        
        // Handle different path structures
        if (strpos($categoryPath, 'images/') === 0) {
            // For paths like 'images/Bridal', 'images/Mother'
            return $thumbsBase . '/' . $categoryPath;
        } else {
            // For simple category names like 'Gems', 'Pearls'
            return $thumbsBase . '/' . $categoryPath;
        }
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
    private function createDetailPage($collectionKey, $collectionConfig, $categoryPath, $categoryConfig, $itemName, $extension) {
        $price = $this->calculatePrice($collectionKey, $categoryConfig['base_price'], $itemName);
        $displayName = $this->createDisplayName($itemName);
        
        $content = $this->generateDetailPageContent(
            $collectionKey,
            $collectionConfig,
            $categoryPath,
            $categoryConfig,
            $itemName,
            $displayName,
            $extension,
            $price
        );
        
        $filename = $this->baseDir . '/' . $collectionConfig['directory'] . '/' . $collectionConfig['directory'] . '_' . $itemName . '_detail.php';
        
        return file_put_contents($filename, $content) !== false;
    }
    
    /**
     * Calculate price based on collection and item patterns
     */
    private function calculatePrice($collectionKey, $basePrice, $itemName) {
        // Collection-specific pricing rules
        switch ($collectionKey) {
            case 'ladys_stoneset':
                if (preg_match('/^2\d{3}/', $itemName)) {
                    return $basePrice + 300; // Premium for 2000+ series
                }
                if (strpos($itemName, 'C') === 0) {
                    if ($itemName === 'C297') return $basePrice + 500;
                    return $basePrice + 200; // Custom pieces
                }
                break;
                
            case 'engagement':
                if (stripos($itemName, 'mm') === 0) {
                    return $basePrice + 500; // Marquise cuts
                }
                if (stripos($itemName, 'wm') === 0) {
                    return $basePrice + 300; // Wedding sets
                }
                break;
                
            case 'signet':
                if (strpos($itemName, 'Diamond') !== false || strpos($itemName, 'C58') !== false) {
                    return $basePrice + 500;
                }
                if (strpos($itemName, 'Ruby') !== false || strpos($itemName, 'Emerald') !== false) {
                    return $basePrice + 350;
                }
                break;
                
            case 'family':
                if (strpos($itemName, 'F') === 0) {
                    return $basePrice + 75; // Father items premium
                }
                break;
        }
        
        return $basePrice;
    }
    
    /**
     * Create display name from filename
     */
    private function createDisplayName($filename) {
        $name = str_replace(['_', '-'], ' ', $filename);
        $name = preg_replace('/alt\d+/', '', $name);
        $name = trim($name);
        return ucwords($name);
    }
    
    /**
     * Generate complete detail page content
     */
    private function generateDetailPageContent($collectionKey, $collectionConfig, $categoryPath, $categoryConfig, $itemName, $displayName, $extension, $price) {
        $collectionName = $collectionConfig['name'];
        $categoryDisplay = $categoryConfig['display'];
        $description = $collectionConfig['description'];
        $navigationKey = $collectionConfig['navigation_key'];
        
        // Build image path
        $imagePath = $categoryPath . '/' . $itemName . '.' . $extension;
        
        return <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="{$itemName} {$collectionKey}, {$categoryDisplay}, Cadman Manufacturing" />
<link rel="icon" sizes="" href="../favicon.ico">
<title>{$itemName} - {$collectionName} Collection | Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include '../navigation.php'; renderNavigation('{$navigationKey}'); ?>
    <?php include '../topButton.php'; renderTopButton(); ?>
    
    <?php
    // Define item information
    \$itemName = '{$itemName}';
    \$category = '{$collectionKey}';
    \$displayName = '{$displayName}';
    \$price = {$price};
    \$categoryDisplay = '{$categoryDisplay}';
    \$description = '{$description}';
    
    // Image paths
    \$mainImage = '{$imagePath}';
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
    
    \$categoryDir = dirname('{$imagePath}');
    \$relatedImages = getRelatedImages(\$categoryDir, '{$itemName}.{$extension}');
    ?>
    
    <div class="item-detail-container">
        <a href="../{$collectionName}.php" class="back-link">← Back to {$collectionName} Collection</a>
        
        <div class="item-detail-grid">
            <!-- Image Section -->
            <div class="item-images">
                <img src="<?php echo \$imagePath; ?>" alt="<?php echo \$displayName; ?>" class="main-image" id="main-image">
                
                <?php if (count(\$relatedImages) > 1): ?>
                <div class="thumbnail-gallery">
                    <?php foreach (\$relatedImages as \$relatedImage): ?>
                    <img src="<?php echo \$categoryDir . '/' . \$relatedImage; ?>" 
                         alt="<?php echo \$displayName; ?>" 
                         class="thumbnail<?php echo (\$relatedImage === '{$itemName}.{$extension}') ? ' active' : ''; ?>"
                         onclick="changeMainImage('<?php echo \$categoryDir . '/' . \$relatedImage; ?>', this)">
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
                    <?php echo \$description; ?>. This exquisite piece represents the finest in jewelry craftsmanship and timeless elegance. 
                    Each item is carefully crafted to meet our high standards of quality and sophistication.
                </div>
                
                <div class="item-features">
                    <h3 style="margin-top: 0; color: #333;">Product Features</h3>
                    <ul class="feature-list">
                        <li><span>Category</span><span><?php echo \$categoryDisplay; ?></span></li>
                        <li><span>Collection</span><span>{$collectionName}</span></li>
                        <li><span>Material</span><span>Premium Quality</span></li>
                        <li><span>Customization</span><span>Available</span></li>
                        <li><span>Warranty</span><span>Lifetime</span></li>
                    </ul>
                </div>
                
                <div class="action-buttons">
                    <a href="#formtable" class="btn btn-primary">Get Quote</a>
                    <a href="../{$collectionName}.php" class="btn btn-secondary">View More Items</a>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-top: 40px;">
            <h3 style="color: #333; margin-bottom: 15px;">About Our {$collectionName} Collection</h3>
            <p style="line-height: 1.6; color: #666; margin-bottom: 20px;">
                {$description}. Each piece is designed with attention to detail and crafted using traditional techniques 
                combined with modern precision. We offer extensive customization options to create jewelry that reflects 
                your unique style and personality.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;">Quality Craftsmanship</h4>
                    <p style="color: #666; font-size: 14px;">Expert artisans use time-honored techniques for exceptional quality.</p>
                </div>
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;">Custom Options</h4>
                    <p style="color: #666; font-size: 14px;">Personalization available to match your individual preferences.</p>
                </div>
                <div>
                    <h4 style="color: #8B008B; margin-bottom: 10px;">Lifetime Value</h4>
                    <p style="color: #666; font-size: 14px;">Built to last with comprehensive warranty and service support.</p>
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
     * Get status of all collections
     */
    public function getAllCollectionsStatus() {
        $status = [];
        
        foreach ($this->collections as $key => $config) {
            $status[$key] = $this->getCollectionStatus($key, $config);
        }
        
        return $status;
    }
    
    /**
     * Get status of a specific collection
     */
    public function getCollectionStatus($collectionKey, $config) {
        $collectionPath = $this->baseDir . '/' . $config['directory'];
        $status = [
            'name' => $config['name'],
            'categories' => [],
            'totals' => [
                'items' => 0,
                'ready' => 0,
                'missing_thumbs' => 0,
                'missing_details' => 0
            ]
        ];
        
        if (!is_dir($collectionPath)) {
            return $status;
        }
        
        foreach ($config['categories'] as $categoryPath => $categoryConfig) {
            $fullCategoryPath = $collectionPath . '/' . $categoryPath;
            
            if (!is_dir($fullCategoryPath)) {
                continue;
            }
            
            $categoryStatus = [];
            $images = $this->scanCategoryForImages($fullCategoryPath);
            
            foreach ($images as $imagePath) {
                $fileName = basename($imagePath);
                $itemName = pathinfo($fileName, PATHINFO_FILENAME);
                
                if (preg_match('/_alt\d+/', $itemName)) continue;
                
                $thumbsPath = $this->getThumbsPath($collectionPath, $categoryPath);
                $thumbExists = file_exists($thumbsPath . '/' . $fileName);
                $detailExists = file_exists($collectionPath . '/' . $config['directory'] . '_' . $itemName . '_detail.php');
                
                $categoryStatus[$itemName] = [
                    'image' => $fileName,
                    'thumbnail' => $thumbExists,
                    'detail_page' => $detailExists,
                    'ready' => $thumbExists && $detailExists
                ];
                
                $status['totals']['items']++;
                if ($thumbExists && $detailExists) $status['totals']['ready']++;
                if (!$thumbExists) $status['totals']['missing_thumbs']++;
                if (!$detailExists) $status['totals']['missing_details']++;
            }
            
            $status['categories'][$categoryPath] = [
                'display_name' => $categoryConfig['display'],
                'items' => $categoryStatus
            ];
        }
        
        return $status;
    }
    
    /**
     * Print processing summary
     */
    private function printSummary($results) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "UNIVERSAL PROCESSING SUMMARY:\n";
        echo "Collections processed: " . $results['collections_processed'] . "\n";
        echo "Thumbnails created: " . $results['thumbnails_created'] . "\n";
        echo "Detail pages created: " . $results['detail_pages_created'] . "\n";
        echo "Errors: " . $results['errors'] . "\n";
        echo str_repeat("=", 60) . "\n";
    }
    
    /**
     * Get list of available collections
     */
    public function getCollectionsList() {
        return array_keys($this->collections);
    }
}

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    die("ERROR: GD extension is not loaded. Please enable GD extension in PHP.\n");
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Command line execution
    $processor = new UniversalCollectionProcessor();
    
    if (isset($argv[1]) && $argv[1] !== '') {
        // Process specific collection
        if (in_array($argv[1], $processor->getCollectionsList())) {
            $processor->processCollections($argv[1], true);
        } else {
            echo "Unknown collection: {$argv[1]}\n";
            echo "Available collections: " . implode(', ', $processor->getCollectionsList()) . "\n";
        }
    } else {
        // Process all collections
        $processor->processCollections(null, true);
    }
    
} elseif (isset($_GET['action'])) {
    // Web API execution
    $processor = new UniversalCollectionProcessor();
    
    switch ($_GET['action']) {
        case 'process':
            header('Content-Type: application/json');
            $collection = $_GET['collection'] ?? null;
            $results = $processor->processCollections($collection, false);
            echo json_encode($results);
            break;
            
        case 'status':
            header('Content-Type: application/json');
            $collection = $_GET['collection'] ?? null;
            if ($collection) {
                $status = $processor->getCollectionStatus($collection, $processor->collections[$collection] ?? []);
            } else {
                $status = $processor->getAllCollectionsStatus();
            }
            echo json_encode($status);
            break;
            
        case 'collections':
            header('Content-Type: application/json');
            echo json_encode($processor->getCollectionsList());
            break;
            
        default:
            echo "<pre>";
            $processor->processCollections(null, true);
            echo "</pre>";
    }
} else {
    // Default web execution - skip if included from dashboard
    if (!isset($_GET['__dashboard_include__'])) {
        echo "<pre>";
        $processor = new UniversalCollectionProcessor();
        $processor->processCollections(null, true);
        echo "</pre>";
        
        echo "<br><br>";
        echo "<h3>API Endpoints:</h3>";
        echo "<ul>";
        echo "<li><a href='?action=process'>Process All Collections (JSON)</a></li>";
        echo "<li><a href='?action=status'>Check All Collections Status (JSON)</a></li>";
        echo "<li><a href='?action=collections'>List Available Collections (JSON)</a></li>";
        echo "<li><a href='?action=process&collection=family'>Process Family Collection Only (JSON)</a></li>";
        echo "<li><a href='?action=status&collection=accessories'>Check Accessories Status (JSON)</a></li>";
        echo "</ul>";
    }
}
?>
