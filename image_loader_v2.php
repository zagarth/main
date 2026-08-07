<?php
/**
 * Secure Image Loader Functions - v3.0
 * Enhanced with security controls and performance optimization
 */

class SecureImageLoader {
    private $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    private $basePath = '';
    private $maxImages = 100;
    private $cache = [];
    
    public function __construct($basePath = '') {
        $this->basePath = realpath($basePath ?: __DIR__);
        if ($this->basePath === false) {
            throw new InvalidArgumentException('Invalid base path');
        }
    }
    
    /**
     * Secure directory scanner with path validation
     */
    public function getImagesFromDirectory($directory) {
        // Validate and sanitize directory path
        $cleanDir = $this->validatePath($directory);
        if (!$cleanDir) {
            return [];
        }
        
        // Check cache first
        $cacheKey = 'images_' . md5($cleanDir);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $images = [];
        if (!is_dir($cleanDir) || !is_readable($cleanDir)) {
            return $images;
        }
        
        try {
            $iterator = new DirectoryIterator($cleanDir);
            $count = 0;
            
            foreach ($iterator as $fileInfo) {
                if ($count >= $this->maxImages) break;
                
                if ($fileInfo->isFile() && $this->isValidImageFile($fileInfo)) {
                    $images[] = $fileInfo->getFilename();
                    $count++;
                }
            }
            
            natsort($images);
            $images = array_values($images);
            
            // Cache results
            $this->cache[$cacheKey] = $images;
            
        } catch (Exception $e) {
            error_log('Image loading error: ' . $e->getMessage());
        }
        
        return $images;
    }
    
    /**
     * Validate file paths to prevent path traversal
     */
    private function validatePath($path) {
        // Remove any attempts at directory traversal
        $cleanPath = str_replace(['../', '..\\', '\\'], '', $path);
        
        // Build full path from base
        if ($this->is_absolute_path($cleanPath)) {
            $fullPath = $cleanPath;
        } else {
            $fullPath = $this->basePath . '/' . ltrim($cleanPath, '/');
        }
        
        $realPath = realpath($fullPath);
        
        // If path doesn't exist yet, check if parent directory is valid
        if ($realPath === false) {
            $parentDir = dirname($fullPath);
            $realParentPath = realpath($parentDir);
            if ($realParentPath && strpos($realParentPath, $this->basePath) === 0) {
                // Parent is valid, return the full constructed path
                return $fullPath;
            }
            // Only log actual security violations, not missing directories
            if (strpos($path, '..') !== false) {
                error_log('Security: Invalid path attempt: ' . $path);
            }
            return false;
        }
        
        // Ensure resolved path is within base directory
        if (strpos($realPath, $this->basePath) !== 0) {
            error_log('Security: Path outside base directory: ' . $path);
            return false;
        }
        
        return $realPath;
    }
    
    /**
     * Check if path is absolute
     */
    private function is_absolute_path($path) {
        return (isset($path[0]) && $path[0] === '/') || 
               (isset($path[1]) && $path[1] === ':' && isset($path[2]) && $path[2] === '\\');
    }
    
    /**
     * Validate image file with security checks
     */
    private function isValidImageFile($fileInfo) {
        $filename = $fileInfo->getFilename();
        
        // Skip hidden files
        if ($filename[0] === '.') {
            return false;
        }
        
        // Check extension
        $extension = strtolower($fileInfo->getExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            return false;
        }
        
        // Skip thumbnail and alternate files
        if (preg_match('/[_-](alt\d*|thumb|thumbnail)/', $filename)) {
            return false;
        }
        
        // Validate filename characters (alphanumeric, dash, underscore only)
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            error_log('Invalid filename detected: ' . $filename);
            return false;
        }
        
        return true;
    }
    
    /**
     * Secure thumbnail path resolution
     */
    public function getThumbnailPath($imagePath, $category = '') {
        $cleanImagePath = $this->validatePath($imagePath);
        if (!$cleanImagePath) {
            return null;
        }
        
        $category = preg_replace('/[^a-zA-Z0-9_-]/', '', $category); // Sanitize category
        $pathInfo = pathinfo($cleanImagePath);
        $filename = $pathInfo['basename'];
        
        // Secure thumbnail search paths
        $thumbnailPaths = [
            $this->basePath . '/thumbs/images/' . $category . '/' . $filename,
            $this->basePath . '/images/thumbnails/' . $category . '/' . $filename,
            $pathInfo['dirname'] . '/thumbs/' . $filename
        ];
        
        foreach ($thumbnailPaths as $thumbPath) {
            $validatedThumbPath = $this->validatePath($thumbPath);
            if ($validatedThumbPath && file_exists($validatedThumbPath)) {
                return str_replace($this->basePath, '', $validatedThumbPath);
            }
        }
        
        return str_replace($this->basePath, '', $cleanImagePath); // Fallback to original
    }
    
    /**
     * Secure price generation with input validation
     */
    public function generatePrice($category, $filename) {
        // Sanitize inputs
        $category = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $category));
        $filename = basename($filename); // Remove any path components
        
        $basePrices = [
            'bands' => 850,
            'school' => 750,
            'corp' => 900,
            'signet' => 1200,
            'accessories' => 450,
            'family' => 650,
            'engagement' => 2850
        ];
        
        $basePrice = $basePrices[$category] ?? 750;
        
        // Simple price modifiers (reduced complexity for security)
        $filename = strtolower($filename);
        if (strpos($filename, 'gold') !== false) $basePrice *= 1.3;
        if (strpos($filename, 'platinum') !== false) $basePrice *= 1.5;
        if (strpos($filename, 'diamond') !== false) $basePrice += 500;
        
        return round($basePrice / 50) * 50;
    }
    
    /**
     * Rate limiting for image operations
     */
    private static $requestCounts = [];
    
    public function checkRateLimit($identifier = null) {
        $identifier = $identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $currentTime = time();
        
        if (!isset(self::$requestCounts[$identifier])) {
            self::$requestCounts[$identifier] = [];
        }
        
        // Remove old entries (older than 1 minute)
        self::$requestCounts[$identifier] = array_filter(
            self::$requestCounts[$identifier],
            function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) < 60;
            }
        );
        
        // Check if limit exceeded (max 50 requests per minute for normal use)
        if (count(self::$requestCounts[$identifier]) >= 50) {
            error_log('Rate limit exceeded for: ' . $identifier);
            return false;
        }
        
        self::$requestCounts[$identifier][] = $currentTime;
        return true;
    }
}

// Legacy function wrappers for backward compatibility
function getImagesFromDirectory($directory) {
    static $loader = null;
    if ($loader === null) {
        $loader = new SecureImageLoader();
    }
    
    if (!$loader->checkRateLimit()) {
        return [];
    }
    
    return $loader->getImagesFromDirectory($directory);
}

function getThumbnailPath($imagePath, $category) {
    static $loader = null;
    if ($loader === null) {
        $loader = new SecureImageLoader();
    }
    
    return $loader->getThumbnailPath($imagePath, $category);
}

function generatePrice($category, $filename) {
    static $loader = null;
    if ($loader === null) {
        $loader = new SecureImageLoader();
    }
    
    return $loader->generatePrice($category, $filename);
}

// Maintain original function signatures for compatibility
function getBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/_alt\d*$/', '', $name);
    $name = preg_replace('/-alt\d*$/', '', $name);
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    return $name;
}

function groupImagesByBaseName($directory) {
    $images = getImagesFromDirectory($directory);
    $grouped = [];
    
    foreach ($images as $file) {
        $baseName = getBaseName($file);
        
        if (!isset($grouped[$baseName])) {
            $grouped[$baseName] = [];
        }
        $grouped[$baseName][] = $file;
    }
    
    foreach ($grouped as $baseName => $variants) {
        usort($variants, function($a, $b) {
            $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $a);
            $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $b);
            
            if ($aIsMain && !$bIsMain) return -1;
            if (!$aIsMain && $bIsMain) return 1;
            return 0;
        });
        $grouped[$baseName] = $variants;
    }
    
    return $grouped;
}

function createDisplayName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/^(img_|image_|photo_|pic_)/', '', $name);
    $name = preg_replace('/(_img|_image|_photo|_pic)$/', '', $name);
    $name = preg_replace('/[_-]+/', ' ', $name);
    $name = preg_replace('/\s+alt\d*\s*/', ' ', $name);
    $name = trim($name);
    return ucwords(strtolower($name));
}

function getCategoryIcon($category) {
    $icons = [
        'bands' => '💍',
        'school' => '🎓',
        'corp' => '🏢',
        'signet' => '📜',
        'accessories' => '✨',
        'family' => '👨‍👩‍👧‍👦',
        'engagement' => '💎',
        'bridal' => '👰',
        'gems' => '💎',
        'pearls' => '🐚',
        'celtic' => '☘️',
        'antique' => '🏛️',
        'contemporary' => '🔷',
        'vintage' => '📿'
    ];
    
    return $icons[$category] ?? '💍';
}

function renderJewelryItem($imagePath, $category, $displayName, $description) {
    $filename = basename($imagePath);
    $price = generatePrice($category, $filename);
    $thumbnailPath = getThumbnailPath($imagePath, $category);
    
    echo '<div class="jewelry-item" data-category="' . htmlspecialchars($category) . '">';
    echo '<img src="' . htmlspecialchars($thumbnailPath) . '" alt="' . htmlspecialchars($displayName) . '" loading="lazy">';
    echo '<div class="item-info">';
    echo '<h3>' . htmlspecialchars($displayName) . '</h3>';
    echo '<p>' . htmlspecialchars($description) . '</p>';
    echo '<div class="item-price">Starting at $' . number_format($price) . '</div>';
    echo '<a href="#formtable" class="view-details-btn">Request Quote</a>';
    echo '</div>';
    echo '</div>';
}
?>
