<?php
require_once 'auth.php';
requireAdmin();

// Enable error reporting to catch any issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard Dropdown</title>
</head>
<body>
    <h1>Debug Dropdown Generation</h1>
    
    <h2>Testing the exact code from dashboard.php:</h2>
    <select>
        <option value="">Choose a collection...</option>
        <?php
        echo "<!-- Starting PHP execution -->\n";
        
        // Load actual categories from catalog_products table
        try {
            echo "<!-- About to require db_config -->\n";
            require_once __DIR__ . '/../includes/db_config.php';
            echo "<!-- DB config loaded -->\n";
            
            $pdo = getDBConnection();
            echo "<!-- DB connection established -->\n";
            
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as item_count 
                FROM catalog_products 
                WHERE has_images = 1 AND category IS NOT NULL 
                AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
                AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
                GROUP BY category 
                ORDER BY item_count DESC, category
            ");
            echo "<!-- Query executed -->\n";
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<!-- Found " . count($categories) . " categories -->\n";
            
            $categoryNames = [
                'celtic_bands' => 'Celtic Bands',
                'plain_bands' => 'Plain Bands', 
                'fancy_bands' => 'Fancy Bands',
                'family' => 'Family Collection',
                'engagement' => 'Engagement Rings',
                'school' => 'School Collection',
                'corporate' => 'Corporate Collection',
                'professional' => 'Professional Collection',
                'crosses' => 'Crosses',
                'lockets' => 'Lockets',
                'signets' => 'Signet Rings',
                'gents_rings' => 'Gents Rings',
                'ladies_jewelry' => 'Ladies Jewelry'
            ];
            
            echo "<!-- Starting option generation -->\n";
            foreach ($categories as $cat) {
                $displayName = $categoryNames[$cat['category']] ?? ucfirst(str_replace('_', ' ', $cat['category']));
                echo '<option value="' . htmlspecialchars($cat['category']) . '">' . 
                     htmlspecialchars($displayName) . ' (' . $cat['item_count'] . ' items)</option>' . "\n";
            }
            echo "<!-- Option generation complete -->\n";
            
        } catch (Exception $e) {
            echo '<option value="">Database error - contact admin</option>' . "\n";
            echo "<!-- Exception: " . htmlspecialchars($e->getMessage()) . " -->\n";
        }
        
        echo "<!-- PHP execution complete -->\n";
        ?>
    </select>

    <h2>View Page Source to see HTML comments with debug info</h2>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>