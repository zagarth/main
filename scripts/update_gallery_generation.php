<?php
/**
 * Update Collection Gallery Generation Code
 * This script updates all collection pages to use renderJewelryItem() from image_loader_v2.php
 */

echo "Gallery Generation Updater\n";
echo "==========================\n\n";

$collectionPages = [
    'Bands.php',
    'School.php', 
    'Corp.php',
    'Signet.php',
    'Accessories.php',
    'Family.php',
    'Engagement.php',
    'Ladys_Stoneset.php'
];

foreach ($collectionPages as $page) {
    echo "Processing $page...\n";
    
    if (!file_exists($page)) {
        echo "  SKIP: File not found\n";
        continue;
    }
    
    $content = file_get_contents($page);
    $originalContent = $content;
    
    // Replace the echo statements in gallery generation
    // Look for the specific gallery item pattern
    $pattern = '/echo \'<div class="jewelry-item" data-category="\' \. \$categoryKey \. \'">\';[\s\S]*?echo \'<\/div>\';/';
    
    if (preg_match($pattern, $content, $matches)) {
        echo "  FOUND: Gallery generation code\n";
        
        // Replace with the new renderJewelryItem call
        $replacement = 'echo renderJewelryItem($image, $imagePath, "school", $categoryKey, $displayName, $price, $detailUrl, getCategoryIcon($categoryKey));';
        
        $content = preg_replace($pattern, $replacement, $content);
        
        if ($content !== $originalContent) {
            if (file_put_contents($page, $content)) {
                echo "  UPDATED: Replaced gallery generation with renderJewelryItem()\n";
            } else {
                echo "  ERROR: Could not write to $page\n";
            }
        } else {
            echo "  NO CHANGE: Pattern matched but no replacement made\n";
        }
    } else {
        echo "  NOT FOUND: Gallery generation pattern, trying manual approach...\n";
        
        // Try to find and replace the echo blocks manually
        $oldBlock = "echo '<div class=\"jewelry-item\" data-category=\"' . \$categoryKey . '\">';
                    echo '<div class=\"school-icon\">' . \$icon . '</div>';
                    echo '<img src=\"' . \$thumbPath . '\" alt=\"' . \$displayName . '\" loading=\"lazy\">';
                    echo '<div class=\"item-info\">';
                    echo '<h3>' . \$displayName . ' - ' . strtoupper(pathinfo(\$image, PATHINFO_FILENAME)) . '</h3>';
                    echo '<p>' . \$category['description'] . '</p>';
                    echo '<div class=\"item-price\">Starting at \$' . \$price . '</div>';
                    echo '<a href=\"' . \$detailUrl . '\" class=\"view-details-btn\">View Details</a>';
                    echo '</div>';
                    echo '</div>';";
        
        $newBlock = "echo renderJewelryItem(\$image, \$imagePath, \"school\", \$categoryKey, \$displayName, \$price, \$detailUrl, getCategoryIcon(\$categoryKey));";
        
        if (strpos($content, "echo '<div class=\"jewelry-item\" data-category=\"'") !== false) {
            echo "  FOUND: Manual pattern detected\n";
            // Will need to do manual replacement in next step
        }
    }
}

echo "\n==========================\n";
echo "Gallery generation updates complete!\n";
?>
