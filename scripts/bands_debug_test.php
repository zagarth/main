<!DOCTYPE html>
<html>
<head>
<title>Bands Test - No JS</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; }
.item { border: 1px solid #ccc; margin: 10px; padding: 15px; display: block; }
.item img { max-width: 200px; height: auto; display: block; margin: 10px 0; }
.debug { background: #f0f0f0; padding: 10px; margin: 5px 0; font-family: monospace; }
</style>
</head>
<body>
<h1>Bands Test - No JavaScript</h1>

<?php
// Test directory access and image generation
$categories = [
    'celtic' => [
        'path' => 'bands_php/images/celtic',
        'display_name' => 'Celtic Bands'
    ],
    'cultural' => [
        'path' => 'bands_php/images/cultural',
        'display_name' => 'Cultural Bands'
    ],
    'fancy' => [
        'path' => 'bands_php/images/fancy',
        'display_name' => 'Fancy Bands'
    ],
    'plain' => [
        'path' => 'bands_php/images/plain',
        'display_name' => 'Plain Bands'
    ]
];

function getBandsBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/_alt\d*$/', '', $name);
    $name = preg_replace('/-alt\d*$/', '', $name);
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    return $name;
}

function groupBandsImagesByBaseName($directory) {
    $images = [];
    echo "<div class='debug'>Checking directory: $directory</div>";
    
    if (is_dir($directory)) {
        echo "<div class='debug'>Directory exists and is readable</div>";
        $files = scandir($directory);
        $grouped = [];
        $imageCount = 0;
        
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $imageCount++;
                $baseName = getBandsBaseName($file);
                
                if (!isset($grouped[$baseName])) {
                    $grouped[$baseName] = [];
                }
                $grouped[$baseName][] = $file;
            }
        }
        
        echo "<div class='debug'>Found $imageCount image files, grouped into " . count($grouped) . " unique items</div>";
        
        foreach ($grouped as $baseName => &$variants) {
            usort($variants, function($a, $b) {
                $aIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $a);
                $bIsMain = !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $b);
                
                if ($aIsMain && !$bIsMain) return -1;
                if (!$aIsMain && $bIsMain) return 1;
                return strcmp($a, $b);
            });
            
            $images[] = [
                'baseName' => $baseName,
                'mainImage' => $variants[0],
                'variants' => $variants,
                'variantCount' => count($variants)
            ];
        }
    } else {
        echo "<div class='debug' style='color: red;'>Directory does NOT exist or is not readable: $directory</div>";
    }
    return $images;
}

$totalItems = 0;
foreach ($categories as $categoryKey => $category) {
    echo "<h2>{$category['display_name']}</h2>";
    
    $categoryImages = groupBandsImagesByBaseName($category['path']);
    
    $itemsInCategory = 0;
    foreach ($categoryImages as $item) {
        $itemsInCategory++;
        $totalItems++;
        
        // Show first 3 items per category
        if ($itemsInCategory <= 3) {
            $mainImagePath = $category['path'] . '/' . $item['mainImage'];
            $thumbPath = str_replace('/images/', '/thumbs/images/', $mainImagePath);
            
            if (!file_exists($thumbPath)) {
                $thumbPath = $mainImagePath;
            }
            
            echo "<div class='item'>";
            echo "<h3>{$item['baseName']}</h3>";
            echo "<div class='debug'>Main Image: {$item['mainImage']}</div>";
            echo "<div class='debug'>Full Path: $mainImagePath</div>";
            echo "<div class='debug'>Thumb Path: $thumbPath</div>";
            echo "<div class='debug'>Image Exists: " . (file_exists($thumbPath) ? 'YES' : 'NO') . "</div>";
            echo "<div class='debug'>Variants: {$item['variantCount']}</div>";
            
            if (file_exists($thumbPath)) {
                echo "<img src='$thumbPath' alt='{$item['baseName']}' style='border: 2px solid green;'>";
            } else {
                echo "<div style='border: 2px solid red; padding: 20px; background: #ffe6e6;'>IMAGE NOT FOUND: $thumbPath</div>";
            }
            echo "</div>";
        }
    }
    
    echo "<div class='debug'>Total items in $categoryKey: " . count($categoryImages) . "</div>";
}

echo "<h2>Summary</h2>";
echo "<div class='debug'>Total items across all categories: $totalItems</div>";
?>

</body>
</html>
