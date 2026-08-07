<?php
/**
 * Bronze Color Processor for Item 5001M
 * Modifies the rose gold image to have a more bronze appearance
 */

// Configuration
$sourceImage = '/var/www/html/homesite/bands_php/images/plain/5001M_rose.png';
$sourceThumb = '/var/www/html/homesite/bands_php/thumbs/images/plain/5001M_rose.png';
$backupImage = '/var/www/html/homesite/bands_php/images/plain/5001M_rose_backup.png';
$backupThumb = '/var/www/html/homesite/bands_php/thumbs/images/plain/5001M_rose_backup.png';

// Bronze color adjustment parameters
$bronzeConfig = [
    'red_boost' => 1.08,        // Reduced red boost to prevent pink
    'green_boost' => 1.02,      // Slight green boost for warmth
    'blue_reduce' => 0.75,      // Reduce blue significantly
    'saturation' => 1.1,        // Increase saturation
    'brightness' => 0.95,       // Slightly darken
    'contrast' => 1.05          // Slight contrast boost
];

function processImageToBronze($sourcePath, $targetPath, $backupPath, $config) {
    // Create backup first
    if (file_exists($sourcePath)) {
        copy($sourcePath, $backupPath);
        echo "Created backup: $backupPath\n";
    } else {
        echo "Error: Source image not found: $sourcePath\n";
        return false;
    }
    
    // Load the image
    $image = imagecreatefrompng($sourcePath);
    if (!$image) {
        echo "Error: Could not load PNG image: $sourcePath\n";
        return false;
    }
    
    // Enable alpha blending
    imagealphablending($image, false);
    imagesavealpha($image, true);
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    echo "Processing image: {$width}x{$height}\n";
    
    // Process each pixel
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgba = imagecolorat($image, $x, $y);
            
            // Extract RGBA components
            $alpha = ($rgba & 0x7F000000) >> 24;
            $red = ($rgba & 0xFF0000) >> 16;
            $green = ($rgba & 0x00FF00) >> 8;
            $blue = $rgba & 0x0000FF;
            
            // Skip transparent pixels
            if ($alpha == 127) continue;
            
            // Calculate pixel brightness to identify highlights
            $brightness = ($red + $green + $blue) / 3;
            $isHighlight = $brightness > 180;
            $isMidTone = $brightness > 100 && $brightness <= 180;
            $isShadow = $brightness <= 100;
            
            // Different processing for different areas
            if ($isHighlight) {
                // For bright highlights (edges), reduce pink and add warm bronze
                $newRed = min(255, $red * 0.95);  // Reduce red to avoid pink
                $newGreen = min(255, $green * 1.05); // Slight green boost for warmth
                $newBlue = max(0, $blue * 0.8);     // Reduce blue for warmth
                
                // Add bronze warmth to highlights
                $newRed = min(255, $newRed + 15);
                $newGreen = min(255, $newGreen + 10);
                $newBlue = max(0, $newBlue - 5);
                
            } else if ($isMidTone) {
                // Main band area - apply stronger bronze effect
                $newRed = min(255, $red * $config['red_boost']);
                $newGreen = min(255, $green * $config['green_boost']);
                $newBlue = max(0, $blue * $config['blue_reduce']);
                
                // Enhance bronze tones for main band
                $bronzeFactor = ($red + $green) / 400;
                $newRed = min(255, $newRed + (40 * $bronzeFactor));
                $newGreen = min(255, $newGreen + (25 * $bronzeFactor));
                $newBlue = max(0, $newBlue - (20 * $bronzeFactor));
                
            } else {
                // Shadow areas - subtle bronze with darker tones
                $newRed = min(255, $red * 1.1);
                $newGreen = min(255, $green * 1.0);
                $newBlue = max(0, $blue * 0.7);
            }
            
            // Apply brightness and contrast
            $newRed = min(255, max(0, ($newRed * $config['brightness'] - 128) * $config['contrast'] + 128));
            $newGreen = min(255, max(0, ($newGreen * $config['brightness'] - 128) * $config['contrast'] + 128));
            $newBlue = min(255, max(0, ($newBlue * $config['brightness'] - 128) * $config['contrast'] + 128));
            
            // Create new color
            $newColor = imagecolorallocatealpha($image, 
                (int)$newRed, 
                (int)$newGreen, 
                (int)$newBlue, 
                $alpha
            );
            
            imagesetpixel($image, $x, $y, $newColor);
        }
        
        // Progress indicator
        if ($x % 50 == 0) {
            $progress = round(($x / $width) * 100);
            echo "Progress: {$progress}%\r";
        }
    }
    
    echo "\nSaving processed image...\n";
    
    // Save the processed image
    if (imagepng($image, $targetPath)) {
        echo "Successfully saved: $targetPath\n";
        imagedestroy($image);
        return true;
    } else {
        echo "Error: Could not save processed image: $targetPath\n";
        imagedestroy($image);
        return false;
    }
}

function restoreFromBackup($backupPath, $targetPath) {
    if (file_exists($backupPath)) {
        copy($backupPath, $targetPath);
        echo "Restored from backup: $targetPath\n";
        return true;
    }
    return false;
}

// Main execution
echo "Bronze Color Processor for Item 5001M\n";
echo "=====================================\n\n";

// Check if images exist
if (!file_exists($sourceImage)) {
    die("Error: Main image not found: $sourceImage\n");
}

if (!file_exists($sourceThumb)) {
    die("Error: Thumbnail not found: $sourceThumb\n");
}

// Process command line arguments
$action = $argv[1] ?? 'process';

switch ($action) {
    case 'process':
        echo "Processing main image to bronze...\n";
        if (processImageToBronze($sourceImage, $sourceImage, $backupImage, $bronzeConfig)) {
            echo "Main image processed successfully!\n\n";
        }
        
        echo "Processing thumbnail to bronze...\n";
        if (processImageToBronze($sourceThumb, $sourceThumb, $backupThumb, $bronzeConfig)) {
            echo "Thumbnail processed successfully!\n\n";
        }
        
        echo "Bronze processing complete!\n";
        echo "Backups saved as:\n";
        echo "- $backupImage\n";
        echo "- $backupThumb\n";
        break;
        
    case 'restore':
        echo "Restoring from backups...\n";
        restoreFromBackup($backupImage, $sourceImage);
        restoreFromBackup($backupThumb, $sourceThumb);
        echo "Restoration complete!\n";
        break;
        
    case 'preview':
        echo "Current image info:\n";
        if (file_exists($sourceImage)) {
            $info = getimagesize($sourceImage);
            echo "Main image: {$info[0]}x{$info[1]}\n";
        }
        if (file_exists($sourceThumb)) {
            $info = getimagesize($sourceThumb);
            echo "Thumbnail: {$info[0]}x{$info[1]}\n";
        }
        if (file_exists($backupImage)) {
            echo "Backup exists: $backupImage\n";
        }
        if (file_exists($backupThumb)) {
            echo "Backup exists: $backupThumb\n";
        }
        break;
        
    default:
        echo "Usage: php bronze_5001M_processor.php [action]\n";
        echo "Actions:\n";
        echo "  process  - Apply bronze effect to images (default)\n";
        echo "  restore  - Restore original images from backup\n";
        echo "  preview  - Show current image information\n";
        break;
}

?>
