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
        $path = str_replace(['../', '..\\', '\\'], '', $path);
        
        // Ensure path is within base directory
        $realPath = realpath($this->basePath . '/' . ltrim($path, '/'));
        
        if ($realPath === false || strpos($realPath, $this->basePath) !== 0) {
            error_log('Potential path traversal attempt: ' . $path);
            return false;
        }
        
        return $realPath;
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
                return $validatedThumbPath;
            }
        }
        
        return $cleanImagePath; // Fallback to original
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
    
    private function checkRateLimit($identifier = null) {
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
        
        // Check if limit exceeded (max 20 requests per minute)
        if (count(self::$requestCounts[$identifier]) >= 20) {
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
?>