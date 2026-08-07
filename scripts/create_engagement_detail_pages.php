<?php
// Script to create detail pages for all new engagement rings
// Groups alternative images automatically

$engagement_images = [
    'MK2207' => ['MK2207.png', 'MK2207_alt1.png', 'MK2207_alt2.png'],
    'MK42' => ['MK42.png', 'MK42_alt1.png', 'MK42_alt2.png'],
    'MK5606' => ['MK5606.png', 'MK5606_alt1.png'],
    'MK56' => ['MK56.png', 'MK56_alt1.png', 'MK56_alt2.png'],
    'mk56D' => ['mk56D.png', 'mk56D-alt1.png', 'mk56D-alt2.png'],
    'mk58' => ['mk58.png', 'mk58-alt1.png', 'mk58-alt2.png'],
    'MK59DB' => ['MK59DB.png', 'MK59DB-alt1.png', 'MK59DB-alt2.png', 'MK59DB-alt3.png'],
    'mk6IDA' => ['mk6IDA.png', 'mk6IDA-alt1.png', 'mk6IDA-alt3.png'],
    'MK75' => ['MK75.png', 'mk75-alt1.png', 'MK75-alt2.png'],
    'mk79' => ['mk79.png', 'mk79-alt1.png', 'mk79-alt2.png']
];

// Function to create thumbnail if it doesn't exist
function createThumbnail($sourcePath, $thumbPath) {
    if (!file_exists($thumbPath) && file_exists($sourcePath)) {
        echo "Creating thumbnail: $thumbPath\n";
        copy($sourcePath, $thumbPath);
        return true;
    }
    return false;
}

// Function to generate detail page content
function generateDetailPage($itemCode, $imageFiles) {
    $displayName = strtoupper($itemCode);
    $price = rand(800, 2500); // Random price for now
    
    $content = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="' . $itemCode . ' engagement ring, diamond rings, bridal jewelry, Cadman Manufacturing" />
<link rel="icon" sizes="" href="../favicon.ico">
<title>' . $displayName . ' - Engagement Rings | Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include \'../navigation.php\'; renderNavigation(\'engagement\'); ?>
    <?php include \'../topButton.php\'; renderTopButton(); ?>
    
    <?php
    // Define item information
    $itemName = \'' . $itemCode . '\';
    $category = \'engagement\';
    $displayName = \'' . $displayName . '\';
    $price = ' . $price . ';
    $categoryDisplay = \'Engagement Rings\';
    $description = \'Symbol of eternal love. Our engagement rings feature exceptional diamonds and precious metals, crafted to celebrate your special moment.\';
    
    // Image paths
    $mainImage = \'images/Bridal/' . $imageFiles[0] . '\';
    $imagePath = $mainImage;
    
    // Function to get related images (alternate views)
    function getRelatedImages($directory, $baseFilename) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $baseName = pathinfo($baseFilename, PATHINFO_FILENAME);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === \'png\' || pathinfo($file, PATHINFO_EXTENSION) === \'jpg\') {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileName === $baseName || strpos($fileName, $baseName . \'_alt\') === 0 || strpos($fileName, $baseName . \'-alt\') === 0) {
                        $images[] = $file;
                    }
                }
            }
        }
        return $images;
    }
    
    $relatedImages = getRelatedImages(\'images/Bridal\', \'' . $imageFiles[0] . '\');
    ?>
    
    <div class="item-detail-container">
        <div class="item-detail-content">
            <div class="item-images-section">
                <div class="main-image-container">
                    <img id="mainImage" src="<?php echo $imagePath; ?>" alt="<?php echo $displayName; ?>" class="main-image">
                </div>
                
                <?php if (count($relatedImages) > 1): ?>
                <div class="thumbnail-gallery">
                    <?php foreach ($relatedImages as $index => $relatedImage): ?>
                        <img src="images/Bridal/<?php echo $relatedImage; ?>" 
                             alt="<?php echo $displayName; ?> view <?php echo $index + 1; ?>" 
                             class="thumbnail <?php echo $index === 0 ? \'active\' : \'\'; ?>"
                             onclick="changeMainImage(\'images/Bridal/<?php echo $relatedImage; ?>\', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="item-info-section">
                <h1 class="item-title"><?php echo $displayName; ?></h1>
                <p class="item-category"><?php echo $categoryDisplay; ?></p>
                <div class="item-price">Starting at $<?php echo number_format($price); ?></div>
                
                <div class="item-description">
                    <p><?php echo $description; ?></p>
                    <p>This exquisite ' . $displayName . ' engagement ring showcases exceptional craftsmanship and timeless elegance, perfect for marking the beginning of your forever journey together.</p>
                </div>
                
                <div class="item-specifications">
                    <h3>Features & Specifications</h3>
                    <ul>
                        <li>Premium diamond setting</li>
                        <li>Available in multiple precious metals</li>
                        <li>Customizable sizing options</li>
                        <li>Lifetime craftsmanship warranty</li>
                        <li>Ethically sourced materials</li>
                    </ul>
                </div>
                
                <div class="item-actions">
                    <a href="../contact_form.php?item=' . urlencode($displayName) . '" class="btn btn-primary">Request Information</a>
                    <a href="../Engagement.php" class="btn btn-secondary">← Back to Collection</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function changeMainImage(newSrc, thumbnail) {
        document.getElementById(\'mainImage\').src = newSrc;
        
        // Remove active class from all thumbnails
        document.querySelectorAll(\'.thumbnail\').forEach(thumb => {
            thumb.classList.remove(\'active\');
        });
        
        // Add active class to clicked thumbnail
        if (thumbnail) {
            thumbnail.classList.add(\'active\');
        }
    }
    </script>

    <?php include \'../footer.php\'; ?>
</body>
</html>';

    return $content;
}

echo "Creating engagement ring detail pages...\n\n";

foreach ($engagement_images as $itemCode => $imageFiles) {
    $filename = "engagement_php_{$itemCode}_detail.php";
    $filepath = "Engagement_php/$filename";
    
    // Check if file already exists
    if (file_exists($filepath)) {
        echo "Skipping $filename (already exists)\n";
        continue;
    }
    
    // Create thumbnails for all images
    foreach ($imageFiles as $imageFile) {
        $sourcePath = "Engagement_php/images/Bridal/$imageFile";
        $thumbPath = "Engagement_php/thumbs/images/Bridal/$imageFile";
        
        // Create thumbnail directory if it doesn't exist
        $thumbDir = dirname($thumbPath);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        createThumbnail($sourcePath, $thumbPath);
    }
    
    // Generate and save detail page
    $content = generateDetailPage($itemCode, $imageFiles);
    
    if (file_put_contents($filepath, $content)) {
        echo "✓ Created: $filename\n";
        echo "  - Main image: {$imageFiles[0]}\n";
        echo "  - Alt images: " . (count($imageFiles) > 1 ? implode(', ', array_slice($imageFiles, 1)) : 'none') . "\n\n";
    } else {
        echo "✗ Failed to create: $filename\n\n";
    }
}

echo "Detail page creation complete!\n";
?>
