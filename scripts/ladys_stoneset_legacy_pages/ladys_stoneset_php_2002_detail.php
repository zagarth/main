<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="2002 gems ring, ladies jewelry, precious stones, Cadman Manufacturing" />
<link rel="icon" sizes="" href="../favicon.ico">
<title>2002 - Lady's Stoneset Collection | Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include '../navigation.php'; renderNavigation('ladys_stoneset'); ?>
    <?php include '../topButton.php'; renderTopButton(); ?>
    
    <?php
    // Define item information
    $itemName = '2002';
    $category = 'gems';
    $displayName = '2002';
    $price = 1550;
    $categoryDisplay = 'Gemstones';
    $description = 'Beautiful rings and jewelry featuring precious and semi-precious gemstones';
    
    // Image paths
    $mainImage = 'Gems/2002.png';
    $imagePath = $mainImage;
    
    // Function to get related images (alternate views)
    function getRelatedImages($directory, $baseFilename) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            $baseName = pathinfo($baseFilename, PATHINFO_FILENAME);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'png' || pathinfo($file, PATHINFO_EXTENSION) === 'jpg') {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileName === $baseName || strpos($fileName, $baseName . '_alt') === 0) {
                        $images[] = $file;
                    }
                }
            }
        }
        return $images;
    }
    
    $relatedImages = getRelatedImages('Gems', '2002.png');
    ?>
    
    <div class="item-detail-container">
        <a href="../Ladys_Stoneset.php" class="back-link">← Back to Lady's Stoneset Collection</a>
        
        <div class="item-detail-grid">
            <!-- Image Section -->
            <div class="item-images">
                <img src="<?php echo $imagePath; ?>" alt="<?php echo $displayName; ?>" class="main-image" id="main-image">
                
                <?php if (count($relatedImages) > 1): ?>
                <div class="thumbnail-gallery">
                    <?php foreach ($relatedImages as $relatedImage): ?>
                    <img src="Gems/<?php echo $relatedImage; ?>" 
                         alt="<?php echo $displayName; ?>" 
                         class="thumbnail<?php echo ($relatedImage === '2002.png') ? ' active' : ''; ?>"
                         onclick="changeMainImage('Gems/<?php echo $relatedImage; ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Info Section -->
            <div class="item-info">
                <div class="item-title"><?php echo $displayName; ?></div>
                <div class="item-subtitle"><?php echo $categoryDisplay; ?> - <?php echo strtoupper($itemName); ?></div>
                <div class="item-price">Starting at $<?php echo number_format($price); ?></div>
                
                <div class="item-description">
                    <?php echo $description; ?>. This exquisite piece represents the finest in ladies' jewelry craftsmanship and timeless elegance. 
                    Each item is carefully crafted to meet our high standards of quality and sophistication.
                </div>
                
                <div class="item-features">
                    <h3 style="margin-top: 0; color: #333;">Product Features</h3>
                    <ul class="feature-list">
                        <li><span>Material</span><span><?php echo ($category === 'gems') ? 'Precious Metal with Gemstone' : 'Precious Metal with Pearl'; ?></span></li>
                        <li><span>Category</span><span><?php echo $categoryDisplay; ?></span></li>
                        <li><span>Style</span><span><?php echo ($category === 'gems') ? 'Gemstone Setting' : 'Pearl Setting'; ?></span></li>
                        <li><span>Customization</span><span>Available</span></li>
                        <li><span>Warranty</span><span>Lifetime</span></li>
                    </ul>
                </div>
                
                <div class="action-buttons">
                    <a href="#" class="btn btn-primary" onclick="openContactModalWithTracking('Product Detail', 'Quote Request - <?php echo $displayName; ?>', 'I would like to request a quote for <?php echo $displayName; ?> (Item ID: <?php echo $itemName; ?>) from the Lady\'s Stoneset collection.')">Get Quote</a>
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
                    <h4 style="color: #8B008B; margin-bottom: 10px;"><?php echo ($category === 'gems') ? 'Premium Gemstones' : 'Lustrous Pearls'; ?></h4>
                    <p style="color: #666; font-size: 14px;"><?php echo ($category === 'gems') ? 'Carefully selected precious and semi-precious stones for exceptional beauty.' : 'Cultured and natural pearls selected for their exceptional luster and quality.'; ?></p>
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
    $(document).ready(function() {
        $('.main-image').on('load', function() {
            $(this).css('opacity', '0').animate({opacity: 1}, 300);
        });
        
        // Initialize with fade-in effect
        $('.item-detail-container').css('opacity', '0').animate({opacity: 1}, 500);
    });
    </script>
</body>
</html>