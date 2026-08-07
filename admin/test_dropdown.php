<?php
require_once 'auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dropdown Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        select { padding: 10px; margin: 10px; min-width: 300px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Carousel Dropdown Debug Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Static Options</h2>
        <select>
            <option value="">Static Test</option>
            <option value="test1">Test Option 1</option>
            <option value="test2">Test Option 2</option>
        </select>
        <p>If you see static options above, basic HTML rendering works.</p>
    </div>
    
    <div class="test-section">
        <h2>Test 2: PHP Generated Options</h2>
        <select id="carouselCollectionSelect">
            <option value="">Choose a collection...</option>
            <?php
            // Load actual categories from catalog_products table
            try {
                require_once __DIR__ . '/../includes/db_config.php';
                $pdo = getDBConnection();
                $stmt = $pdo->query("
                    SELECT category, COUNT(*) as item_count 
                    FROM catalog_products 
                    WHERE has_images = 1 AND category IS NOT NULL 
                    AND product_id NOT LIKE '%_alt%' AND product_id NOT LIKE '%_view%' AND product_id NOT LIKE '%_art%'
                    AND product_id NOT LIKE '% copy%' AND product_id NOT LIKE '%backup%'
                    GROUP BY category 
                    ORDER BY item_count DESC, category
                ");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
                
                foreach ($categories as $cat) {
                    $displayName = $categoryNames[$cat['category']] ?? ucfirst(str_replace('_', ' ', $cat['category']));
                    echo '<option value="' . htmlspecialchars($cat['category']) . '">' . 
                         htmlspecialchars($displayName) . ' (' . $cat['item_count'] . ' items)</option>';
                }
                
                echo "<p>Generated " . count($categories) . " options from database.</p>";
                
            } catch (Exception $e) {
                echo '<option value="">Database error - contact admin</option>';
                echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </select>
        <p>If you see collection options above, PHP generation works.</p>
    </div>
    
    <div class="test-section">
        <h2>Test 3: JavaScript Test</h2>
        <button onclick="testJS()">Test JavaScript</button>
        <p id="jsResult">Click button to test JavaScript</p>
        
        <script>
            function testJS() {
                document.getElementById('jsResult').innerHTML = 'JavaScript is working! Selected value: ' + document.getElementById('carouselCollectionSelect').value;
            }
        </script>
    </div>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>