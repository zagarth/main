<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Restoring Essential Workers PHP Pages ===\n";

// Define the photo directory
$photoDir = "photos/Essential workers/";
$phpDir = "essential_workers_php/";

// Ensure the PHP directory exists
if (!is_dir($phpDir)) {
    mkdir($phpDir, 0755, true);
}

// Clear the directory first
$files = glob($phpDir . '*.php');
foreach ($files as $file) {
    unlink($file);
}

// Scan for all image files
$imageFiles = [];
if (is_dir($photoDir)) {
    $files = scandir($photoDir);
    foreach ($files as $file) {
        if (preg_match('/\.(png|jpg|jpeg|tif|gif)$/i', $file) && $file !== '.' && $file !== '..') {
            $imageFiles[] = $file;
        }
    }
}

echo "Found " . count($imageFiles) . " image files in Essential workers directory\n";

// Group images by base name - FIXED to handle both - and _ separators
$itemGroups = [];
foreach ($imageFiles as $file) {
    $baseName = pathinfo($file, PATHINFO_FILENAME);
    
    // Remove various alternate suffixes - handle both - and _ separators
    $rootName = preg_replace('/_alt\d+$/', '', $baseName);
    $rootName = preg_replace('/-alt\d+$/', '', $rootName);
    $rootName = preg_replace('/_view\d+$/', '', $rootName);
    $rootName = preg_replace('/_art\d+$/', '', $rootName);
    
    // Handle special cases
    if (strpos($baseName, 'one final') !== false) {
        $rootName = 'one_final';
    }
    
    if (!isset($itemGroups[$rootName])) {
        $itemGroups[$rootName] = [];
    }
    
    $itemGroups[$rootName][] = [
        'filename' => $file,
        'basename' => $baseName,
        'isMain' => !preg_match('/(_alt\d+|-alt\d+|_view\d+)$/', $baseName)
    ];
}

echo "Grouped into " . count($itemGroups) . " unique items\n";

// Working template for individual item pages - restored from backup
$pageTemplate = '<?php
$itemName = basename($_SERVER[\'PHP_SELF\'], \'.php\');
$allImages = [];

// Scan the Essential workers directory to find images for this item
$baseDir = "../photos/Essential workers/";
if (is_dir($baseDir)) {
    $items = scandir($baseDir);
    $filtered = array_diff($items, [\'.\', \'..\', \'thumb\']);
    
    foreach ($filtered as $item) {
        $fullPath = $baseDir . $item;
        if (is_file($fullPath) && preg_match(\'/\.(png|jpg|jpeg|gif|tif)$/i\', $item)) {
            $itemNameFromFile = pathinfo($item, PATHINFO_FILENAME);
            $rootName = preg_replace(\'/_alt\d+$/\', \'\', $itemNameFromFile);
            $rootName = preg_replace(\'/-alt\d+$/\', \'\', $rootName);
            $rootName = preg_replace(\'/_view\d+$/\', \'\', $rootName);
            $rootName = preg_replace(\'/_art\d+$/\', \'\', $rootName);
            
            // Handle special naming cases
            if (strpos($itemNameFromFile, \'one final\') !== false) {
                $rootName = \'one_final\';
            }
            
            // Convert item name for comparison
            $currentItemRoot = preg_replace(\'/_alt\d+$/\', \'\', $itemName);
            $currentItemRoot = preg_replace(\'/-alt\d+$/\', \'\', $currentItemRoot);
            
            // If this image belongs to our item
            if (strtolower($rootName) === strtolower($currentItemRoot)) {
                $webPath = $baseDir . $item;
                $thumbPath = str_replace(\'photos/Essential workers/\', \'photos/Essential workers/thumb/\', $webPath);
                
                // Check if it\'s the main image (no _alt or -alt suffix)
                $isMain = !preg_match(\'/_alt\d+\./\', $item) && !preg_match(\'/-alt\d+\./\', $item) && !preg_match(\'/_view\d+\./\', $item);
                
                $allImages[] = [
                    \'path\' => $webPath,
                    \'thumbPath\' => $thumbPath,
                    \'filename\' => $item,
                    \'isMain\' => $isMain
                ];
            }
        }
    }
}

// Sort images so main image is first
usort($allImages, function($a, $b) {
    if ($a[\'isMain\']) return -1;
    if ($b[\'isMain\']) return 1;
    return strcmp($a[\'filename\'], $b[\'filename\']);
});
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="../styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0066CC" />
<title>Cadman Mfg - <?php echo htmlspecialchars($itemName); ?></title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../modernizrjw.js" type="text/javascript"></script>
<script type="text/javascript">
// Working image gallery functionality
let currentImageIndex = 0;
let rotationTimer;
let isUserInteracting = false;

window.switchMainImage = function(imagePath, thumbElement) {
    const mainImg = document.getElementById(\'main-product-img\');
    if (mainImg) {
        mainImg.src = imagePath;
    }
    
    // Update thumbnail selection
    document.querySelectorAll(\'.gallery-thumb\').forEach((thumb, index) => {
        thumb.style.borderColor = \'#ccc\';
        thumb.classList.remove(\'selected\');
        if (thumb === thumbElement) {
            currentImageIndex = index;
        }
    });
    
    thumbElement.style.borderColor = \'#0066CC\';
    thumbElement.classList.add(\'selected\');
    
    // User interaction - pause auto rotation temporarily
    isUserInteracting = true;
    setTimeout(() => {
        isUserInteracting = false;
    }, 5000);
};

window.nextImage = function() {
    const thumbs = document.querySelectorAll(\'.gallery-thumb\');
    if (thumbs.length > 1) {
        const nextIndex = (currentImageIndex + 1) % thumbs.length;
        thumbs[nextIndex].click();
    }
};

window.prevImage = function() {
    const thumbs = document.querySelectorAll(\'.gallery-thumb\');
    if (thumbs.length > 1) {
        const prevIndex = (currentImageIndex - 1 + thumbs.length) % thumbs.length;
        thumbs[prevIndex].click();
    }
};

// Auto-rotation functionality
function startAutoRotation() {
    const thumbs = document.querySelectorAll(\'.gallery-thumb\');
    if (thumbs.length > 1) {
        rotationTimer = setInterval(() => {
            if (!isUserInteracting) {
                nextImage();
            }
        }, 3000);
    }
}

// Keyboard navigation
document.addEventListener(\'keydown\', function(e) {
    if (e.key === \'ArrowLeft\') {
        prevImage();
    } else if (e.key === \'ArrowRight\') {
        nextImage();
    }
});

// Mobile menu toggle
function toggleMobileMenu() {
    $("#nav").slideToggle(300);
}

// Top button functionality
window.addEventListener(\'scroll\', function() {
    const topBtn = document.getElementById(\'topBtn\');
    if (topBtn) {
        if (window.pageYOffset > 200) {
            topBtn.style.display = \'block\';
        } else {
            topBtn.style.display = \'none\';
        }
    }
}, { passive: true });

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: \'smooth\'
    });
}

$(document).ready(function() {
    startAutoRotation();
    
    // Pause rotation on hover
    $(\'.product-image-gallery\').hover(
        function() { isUserInteracting = true; },
        function() { setTimeout(() => { isUserInteracting = false; }, 2000); }
    );
});
</script>
<style>
#topBtn {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99;
    border: none;
    outline: none;
    background-color: rgba(0, 102, 204, 0.8);
    color: white;
    cursor: pointer;
    padding: 0;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 24px;
    transition: opacity 0.3s;
}

#topBtn:hover {
    background-color: rgba(0, 102, 204, 1);
}

#topBtn a {
    color: white;
    text-decoration: none;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-product-image {
    max-width: 100%;
    height: auto;
    margin: 20px auto;
    display: block;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.product-image-gallery {
    margin: 20px auto;
    text-align: center;
    max-width: 600px;
}

.gallery-thumbnails {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
    justify-content: center;
    align-items: center;
}

.gallery-thumb {
    border: 2px solid #ccc;
    padding: 5px;
    cursor: pointer;
    border-radius: 5px;
    transition: border-color 0.3s;
}

.gallery-thumb.selected {
    border-color: #0066CC;
}

.gallery-thumb img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 3px;
}

@media (max-width: 768px) {
    .gallery-thumb img {
        width: 60px;
        height: 45px;
    }
}
</style>
</head>
<body>
    <?php include \'../navigation.php\'; renderNavigation(\'essential_workers\'); ?>
    
    <div id="topBtn">
        <a href="#" onclick="scrollToTop(); return false;" aria-label="Back to top">↑</a>
    </div>

    <div id="wrapper">
        <div class="textbox">
            <h1><?php echo htmlspecialchars($itemName); ?></h1>
            <p>Collection: Essential Workers</p>
            
            <?php if (!empty($allImages)): ?>
                <img src="<?php echo htmlspecialchars($allImages[0][\'path\']); ?>" alt="<?php echo htmlspecialchars($itemName); ?>" class="main-product-image" id="main-product-img">

                <?php if (count($allImages) > 1): ?>
                <div class="product-image-gallery">
                    <h3 style="text-align: center; margin-bottom: 15px; color: #333;">
                        Additional Views 
                        <small style="font-size: 12px; color: #666;">(Click to switch • Auto-rotating)</small>
                    </h3>
                    <div class="gallery-thumbnails">
                        <?php foreach ($allImages as $index => $image): ?>
                            <div class="gallery-thumb <?php echo $index === 0 ? \'selected\' : \'\'; ?>" 
                                 onclick="switchMainImage(\'<?php echo htmlspecialchars($image[\'path\']); ?>\', this)">
                                <?php 
                                $thumbSrc = file_exists(str_replace(\'../\', \'\', $image[\'thumbPath\'])) ? $image[\'thumbPath\'] : $image[\'path\'];
                                ?>
                                <img src="<?php echo htmlspecialchars($thumbSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($itemName); ?> - View <?php echo $index + 1; ?>" 
                                     title="<?php echo htmlspecialchars($itemName); ?> - View <?php echo $index + 1; ?>">
                                <div style="font-size: 10px; text-align: center; margin-top: 3px; color: #666;"><?php echo $index + 1; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button onclick="prevImage()" style="margin: 5px; padding: 8px 12px; background: #0066CC; color: white; border: none; border-radius: 3px; cursor: pointer;">← Previous</button>
                        <button onclick="nextImage()" style="margin: 5px; padding: 8px 12px; background: #0066CC; color: white; border: none; border-radius: 3px; cursor: pointer;">Next →</button>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No images found for this item.</p>
            <?php endif; ?>

            <p>This Essential Workers design showcases exceptional craftsmanship and attention to detail. Each piece is carefully crafted to meet the highest standards of quality and style, honoring those who serve our communities. Contact us for more information about <?php echo htmlspecialchars($itemName); ?>.</p>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="../Essential_Workers.php" style="padding: 10px 20px; background: #0066CC; color: white; text-decoration: none; border-radius: 5px;">← Back to Essential Workers Gallery</a>
            </div>
        </div>
    </div>
</body>
</html>';

// Generate pages for each item group
$generatedCount = 0;
foreach ($itemGroups as $rootName => $images) {
    // Sort images within each group
    usort($images, function($a, $b) {
        if ($a['isMain']) return -1;
        if ($b['isMain']) return 1;
        return strcmp($a['filename'], $b['filename']);
    });
    
    // Create main page
    $mainPageName = $phpDir . $rootName . '.php';
    file_put_contents($mainPageName, $pageTemplate);
    $generatedCount++;
    echo "Created: $mainPageName\n";
    
    // Create alternate pages if they exist
    $altCount = 0;
    foreach ($images as $image) {
        if (!$image['isMain']) {
            $altCount++;
            $altPageName = $phpDir . $rootName . '_alt' . $altCount . '.php';
            
            // Use same template for alternate pages - they will auto-detect their images
            file_put_contents($altPageName, $pageTemplate);
            $generatedCount++;
            echo "Created: $altPageName\n";
        }
    }
}

echo "\n=== RESTORATION COMPLETE ===\n";
echo "Generated $generatedCount PHP pages for " . count($itemGroups) . " unique items\n";
echo "Essential Workers collection restored with working image rotation!\n";

// Show pcw301B grouping specifically
echo "\npcw301B grouping:\n";
if (isset($itemGroups['pcw301B'])) {
    foreach ($itemGroups['pcw301B'] as $img) {
        echo "- " . $img['filename'] . " (main: " . ($img['isMain'] ? 'yes' : 'no') . ")\n";
    }
}
?>
