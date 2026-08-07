<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bands Collection - Test</title>
</head>
<body>
    <h1>Bands Collection Test</h1>
    
    <?php
    // Test basic PHP functionality
    echo "<p>PHP is working!</p>";
    
    // Test image loading
    function getImagesFromDirectory($directory) {
        $images = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $images[] = $file;
                }
            }
        }
        return $images;
    }
    
    $testDir = 'bands_php/images/celtic';
    $images = getImagesFromDirectory($testDir);
    echo "<p>Found " . count($images) . " images in {$testDir}</p>";
    
    // Show first few images
    $nonAltImages = array_filter($images, function($image) {
        return strpos($image, '_alt') === false;
    });
    
    echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px;'>";
    foreach (array_slice($nonAltImages, 0, 6) as $image) {
        $imagePath = $testDir . '/' . $image;
        $thumbPath = str_replace('bands_php/images/', 'bands_php/thumbs/images/', $imagePath);
        
        if (!file_exists($thumbPath)) {
            $thumbPath = $imagePath;
        }
        
        echo "<div style='border: 1px solid #ccc; padding: 10px; text-align: center;'>";
        echo "<img src='{$thumbPath}' alt='{$image}' style='width: 200px; height: 200px; object-fit: contain;'>";
        echo "<p>" . pathinfo($image, PATHINFO_FILENAME) . "</p>";
        echo "</div>";
    }
    echo "</div>";
    ?>
</body>
</html>
