<?php
// Script to generate all Ladys Stoneset detail pages

// Define collections and their details
$gemsItems = [
    '1898' => ['price' => 1250, 'ext' => 'png'],
    '1904' => ['price' => 1250, 'ext' => 'png'], 
    '1970' => ['price' => 1250, 'ext' => 'png'],
    '2002' => ['price' => 1550, 'ext' => 'png'],
    '2239' => ['price' => 1550, 'ext' => 'png'],
    '2241' => ['price' => 1550, 'ext' => 'png'],
    '2280' => ['price' => 1550, 'ext' => 'jpg'],
    '2281' => ['price' => 1550, 'ext' => 'jpg'],
    '2283' => ['price' => 1550, 'ext' => 'jpg'],
    '2285D' => ['price' => 1550, 'ext' => 'png'],
    'C297' => ['price' => 1750, 'ext' => 'jpg'],
    'C71' => ['price' => 1450, 'ext' => 'jpg'],
    'C72' => ['price' => 1450, 'ext' => 'jpg']
];

$pearlsItems = [
    '1902C' => ['price' => 950, 'ext' => 'png'],
    'C223C_chocolatepearl_72dpi' => ['price' => 1150, 'ext' => 'jpg']
];

// Function to create detail page content
function createDetailPage($itemName, $category, $price, $ext) {
    $categoryDisplay = ($category === 'gems') ? 'Gemstones' : 'Pearls';
    $categoryPath = ucfirst($category);
    $description = ($category === 'gems') 
        ? 'Beautiful rings and jewelry featuring precious and semi-precious gemstones'
        : 'Elegant pearl jewelry showcasing lustrous cultured and natural pearls';
    
    $displayName = str_replace('_', ' ', $itemName);
    $displayName = ucwords($displayName);
    
    $content = <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="{$itemName} {$category} ring, ladies jewelry, precious stones, Cadman Manufacturing" />
<link rel="icon" sizes="" href="../favicon.ico">
<title>{$itemName} - Lady's Stoneset Collection | Cadman Manufacturing</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include '../navigation.php'; renderNavigation('ladys_stoneset'); ?>
    <?php include '../topButton.php'; renderTopButton(); ?>
    
    <?php
    // Define item information
    \$itemName = '{$itemName}';
    \$category = '{$category}';
    \$displayName = '{$displayName}';
    \$price = {$price};
    \$categoryDisplay = '{$categoryDisplay}';
    \$description = '{$description}';
    
    // Image paths
    \$mainImage = '{$categoryPath}/{$itemName}.{$ext}';
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
    
    \$relatedImages = getRelatedImages('{$categoryPath}', '{$itemName}.{$ext}');
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
                         class="thumbnail<?php echo (\$relatedImage === '{$itemName}.{$ext}') ? ' active' : ''; ?>"
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
    
    return $content;
}

// Create all gems detail pages
foreach ($gemsItems as $itemName => $details) {
    $filename = "/var/www/html/homesite/ladys_stoneset_php/ladys_stoneset_php_{$itemName}_detail.php";
    $content = createDetailPage($itemName, 'gems', $details['price'], $details['ext']);
    file_put_contents($filename, $content);
    echo "Created: $filename\n";
}

// Create all pearls detail pages
foreach ($pearlsItems as $itemName => $details) {
    $filename = "/var/www/html/homesite/ladys_stoneset_php/ladys_stoneset_php_{$itemName}_detail.php";
    $content = createDetailPage($itemName, 'pearls', $details['price'], $details['ext']);
    file_put_contents($filename, $content);
    echo "Created: $filename\n";
}

echo "\nAll Ladys Stoneset detail pages created successfully!\n";
?>
