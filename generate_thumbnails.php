<?php
/**
 * Thumbnail Generator Script
 * Scans all *_php directories for images and creates 240x240 thumbnails
 */

// Configuration
$thumbnailSize = 240;
$supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$quality = 85; // JPEG quality (1-100)

// Function to create thumbnail
function createThumbnail($sourcePath, $destPath, $size, $quality = 85) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Create source image resource based on type
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
    
    // Calculate dimensions to maintain aspect ratio
    $aspectRatio = $sourceWidth / $sourceHeight;
    
    if ($aspectRatio > 1) {
        // Landscape
        $thumbWidth = $size;
        $thumbHeight = $size / $aspectRatio;
    } else {
        // Portrait or square
        $thumbWidth = $size * $aspectRatio;
        $thumbHeight = $size;
    }
    
    // Create thumbnail canvas
    $thumbnail = imagecreatetruecolor($size, $size);
    
    // Set background color (white)
    $white = imagecolorallocate($thumbnail, 255, 255, 255);
    imagefill($thumbnail, 0, 0, $white);
    
    // Calculate positioning to center the image
    $offsetX = ($size - $thumbWidth) / 2;
    $offsetY = ($size - $thumbHeight) / 2;
    
    // Handle transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    // Resize and copy the image
    imagecopyresampled(
        $thumbnail, $sourceImage,
        $offsetX, $offsetY, 0, 0,
        $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight
    );
    
    // Save thumbnail based on original format
    $success = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $success = imagejpeg($thumbnail, $destPath, $quality);
            break;
        case 'image/png':
            $success = imagepng($thumbnail, $destPath);
            break;
        case 'image/gif':
            $success = imagegif($thumbnail, $destPath);
            break;
        case 'image/webp':
            $success = imagewebp($thumbnail, $destPath, $quality);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    return $success;
}

// Function to scan directory for images
function scanForImages($directory, $supportedFormats) {
    $images = [];
    
    if (!is_dir($directory)) {
        return $images;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $supportedFormats)) {
                $images[] = $file->getPathname();
            }
        }
    }
    
    return $images;
}

// Main execution
function generateThumbnails() {
    global $thumbnailSize, $supportedFormats, $quality;
    
    $baseDir = __DIR__;
    $phpDirectories = [];
    $processedCount = 0;
    $errorCount = 0;
    
    // Find all directories ending with '_php'
    $directories = glob($baseDir . '/*_php', GLOB_ONLYDIR);
    
    if (empty($directories)) {
        echo "No *_php directories found.\n";
        return;
    }
    
    echo "Found " . count($directories) . " PHP directories to process:\n";
    
    foreach ($directories as $phpDir) {
        $dirName = basename($phpDir);
        echo "\nProcessing directory: $dirName\n";
        echo str_repeat("-", 50) . "\n";
        
        // Create thumbs directory
        $thumbsDir = $phpDir . '/thumbs';
        if (!file_exists($thumbsDir)) {
            if (!mkdir($thumbsDir, 0755, true)) {
                echo "ERROR: Could not create thumbs directory: $thumbsDir\n";
                continue;
            }
            echo "Created thumbs directory: $thumbsDir\n";
        }
        
        // Scan for images
        $images = scanForImages($phpDir, $supportedFormats);
        
        if (empty($images)) {
            echo "No images found in $dirName\n";
            continue;
        }
        
        echo "Found " . count($images) . " images to process\n";
        
        foreach ($images as $imagePath) {
            $fileName = basename($imagePath);
            $relativePath = str_replace($phpDir . '/', '', $imagePath);
            
            // Skip if image is already in thumbs directory
            if (strpos($relativePath, 'thumbs/') === 0) {
                continue;
            }
            
            // Create subdirectory structure in thumbs if needed
            $relativeDir = dirname($relativePath);
            if ($relativeDir !== '.') {
                $thumbSubDir = $thumbsDir . '/' . $relativeDir;
                if (!file_exists($thumbSubDir)) {
                    mkdir($thumbSubDir, 0755, true);
                }
            }
            
            // Generate thumbnail path
            $thumbPath = $thumbsDir . '/' . $relativePath;
            
            // Skip if thumbnail already exists
            if (file_exists($thumbPath)) {
                echo "SKIP: Thumbnail already exists for $fileName\n";
                continue;
            }
            
            // Create thumbnail
            echo "Processing: $fileName... ";
            
            if (createThumbnail($imagePath, $thumbPath, $thumbnailSize, $quality)) {
                echo "SUCCESS\n";
                $processedCount++;
            } else {
                echo "FAILED\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "Processed: $processedCount images\n";
    echo "Errors: $errorCount images\n";
    echo "Total directories: " . count($directories) . "\n";
    echo str_repeat("=", 60) . "\n";
}

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    die("ERROR: GD extension is not loaded. Please enable GD extension in PHP.\n");
}

// Run the script
if (php_sapi_name() === 'cli') {
    // Command line execution
    echo "Thumbnail Generator Script\n";
    echo "=========================\n\n";
    generateThumbnails();
} else {
    // Web execution
    echo "<pre>";
    echo "Thumbnail Generator Script\n";
    echo "=========================\n\n";
    generateThumbnails();
    echo "</pre>";
}
?>
