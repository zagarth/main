<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Family Collection Test - Cadman Manufacturing</title>
</head>
<body>
    <h1>Family Collection Test</h1>
    
    <?php
    include 'image_loader_v2.php';
    
    echo "<!-- PHP is working -->\n";
    
    // Test if functions exist
    if (function_exists('groupImagesByBaseName')) {
        echo "<!-- groupImagesByBaseName function exists -->\n";
        
        $categories = [
            'mother' => [
                'path' => 'family_php/images/Mother',
                'display_name' => 'Mother\'s Collection'
            ]
        ];
        
        foreach ($categories as $categoryKey => $category) {
            echo "<!-- Processing category: $categoryKey -->\n";
            $groupedImages = groupImagesByBaseName($category['path']);
            echo "<!-- Found " . count($groupedImages) . " groups -->\n";
            
            foreach ($groupedImages as $baseName => $imageVariants) {
                echo "<!-- Item: $baseName with " . count($imageVariants) . " variants -->\n";
                $mainImage = $imageVariants[0];
                echo '<div class="jewelry-item">';
                echo '<h3>' . $baseName . '</h3>';
                echo '<img src="' . $category['path'] . '/' . $mainImage . '" alt="' . $baseName . '">';
                echo '</div>';
            }
        }
    } else {
        echo "<!-- groupImagesByBaseName function NOT found -->\n";
    }
    ?>
    
</body>
</html>
