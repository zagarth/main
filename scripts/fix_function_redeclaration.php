<?php
/**
 * Fix Function Redeclaration in Collection Pages
 * This script removes duplicate function definitions and ensures proper include of image_loader_v2.php
 */

$collection_pages = [
    'Corp.php',
    'Signet.php', 
    'Accessories.php',
    'Family.php',
    'Engagement.php',
    'Ladys_Stoneset.php'
];

echo "Fixing Function Redeclaration Issues\n";
echo "====================================\n\n";

foreach ($collection_pages as $page) {
    if (!file_exists($page)) {
        echo "SKIP: $page (file not found)\n";
        continue;
    }
    
    echo "Processing: $page\n";
    
    $content = file_get_contents($page);
    $originalContent = $content;
    
    // Check if the page has function redeclaration issues
    if (strpos($content, 'function getImagesFromDirectory') !== false && 
        strpos($content, 'image_loader_v2.php') !== false) {
        
        // Remove duplicate function definitions  
        $pattern = '/\/\*\*[\s\S]*?function getImagesFromDirectory[\s\S]*?function generatePrice[\s\S]*?}\s*}/';
        $content = preg_replace($pattern, '', $content);
        
        // Ensure proper include
        $content = str_replace(
            '// Temporarily commented out: include \'image_loader_v2.php\';',
            'include \'image_loader_v2.php\';',
            $content
        );
        
        if ($content !== $originalContent) {
            if (file_put_contents($page, $content)) {
                echo "  FIXED: Removed duplicate functions and fixed include\n";
            } else {
                echo "  ERROR: Could not write to $page\n";
            }
        } else {
            echo "  OK: No changes needed\n";
        }
    } else {
        echo "  OK: No function redeclaration issues\n";
    }
    
    // Check PHP syntax
    $output = [];
    $return_code = 0;
    exec("php -l $page 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "  SYNTAX: OK\n";
    } else {
        echo "  SYNTAX ERROR: " . implode(' ', $output) . "\n";
    }
    
    echo "\n";
}

echo "Function redeclaration fix complete!\n";
?>
