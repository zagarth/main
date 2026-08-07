<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Bands.php Debug Test</h2>";

// Test directory access
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
    echo "<p>Checking directory: $directory</p>";
    
    if (is_dir($directory)) {
        echo "<p>Directory exists!</p>";
        $files = scandir($directory);
        $grouped = [];
        
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $baseName = getBandsBaseName($file);
                
                if (!isset($grouped[$baseName])) {
                    $grouped[$baseName] = [];
                }
                $grouped[$baseName][] = $file;
            }
        }
        
        echo "<p>Found " . count($grouped) . " unique items in $directory</p>";
        
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
        echo "<p>Directory does NOT exist: $directory</p>";
    }
    return $images;
}

$allItems = [];
$totalItems = 0;

foreach ($categories as $categoryKey => $category) {
    $categoryPath = $category['path'];
    $categoryImages = groupBandsImagesByBaseName($categoryPath);
    
    foreach ($categoryImages as $item) {
        $allItems[] = [
            'category' => $categoryKey,
            'categoryPath' => $categoryPath,
            'baseName' => $item['baseName'],
            'mainImage' => $item['mainImage'],
            'variants' => $item['variants'],
            'variantCount' => $item['variantCount']
        ];
        $totalItems++;
    }
}

echo "<h3>Results:</h3>";
echo "<p>Total items found: $totalItems</p>";

if ($totalItems > 0) {
    echo "<h4>First 5 items:</h4>";
    for ($i = 0; $i < min(5, count($allItems)); $i++) {
        $item = $allItems[$i];
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>Category:</strong> " . $item['category'] . "<br>";
        echo "<strong>Base Name:</strong> " . $item['baseName'] . "<br>";
        echo "<strong>Main Image:</strong> " . $item['mainImage'] . "<br>";
        echo "<strong>Variants:</strong> " . $item['variantCount'] . "<br>";
        echo "<strong>Full Path:</strong> " . $item['categoryPath'] . '/' . $item['mainImage'] . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>No items found! Check directory paths and permissions.</p>";
}
?>
