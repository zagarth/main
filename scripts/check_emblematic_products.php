<?php
// Check emblematic products status in database
require_once __DIR__ . '/includes/db_config.php';

// Get database connection 
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

// All 43 products found in emblematic index
$emblematic_products = [
    '4911', '4912', '4913', 'BN1', 'C553MAS', 'C58MAS', 'C64MAS', 'CT15', 'EMR31M', 
    'FF3', 'FF4', 'FF5', 'LPN1', 'LPN30', 'LPN301B', 'LPN31M', 'PA1', 'PAR14M', 
    'PAR17B', 'PCW31M', 'RN1', 'RN3', 'S14M', 'S16DM', 'S17B', 'S180M', 'S185M', 
    'S20M', 'S240L', 'S24M', 'S25M', 'S26M', 'S301DB', 'S30HM', 'S31HM', 'S32HM', 
    'S330B', 'S33HM', 'S36HM', 'S38M', 'S46M', 'S6RM', 'VET14M'
];

echo "Checking status of " . count($emblematic_products) . " emblematic products...\n\n";

$in_database = [];
$missing_products = [];
$wrong_page_ref = [];
$correct_page_ref = [];

foreach ($emblematic_products as $product_id) {
    $check_sql = "SELECT product_id, page_reference, source FROM catalog_products WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $in_database[] = $product_id;
        
        $current_page = $row['page_reference'];
        if ($current_page == 'page_12a') {
            $wrong_page_ref[] = $product_id;
        } elseif (in_array($current_page, ['page_09a', 'page_09aa', 'page_09b', 'page_09c'])) {
            $correct_page_ref[] = $product_id;
        } else {
            $wrong_page_ref[] = $product_id . " (currently: $current_page)";
        }
    } else {
        $missing_products[] = $product_id;
    }
}

echo "=== EMBLEMATIC PRODUCTS STATUS ===\n";
echo "Total products in emblematic index: " . count($emblematic_products) . "\n";
echo "Found in database: " . count($in_database) . "\n";
echo "Missing from database: " . count($missing_products) . "\n";
echo "Have wrong page references: " . count($wrong_page_ref) . "\n";
echo "Have correct page references: " . count($correct_page_ref) . "\n\n";

if (!empty($missing_products)) {
    echo "MISSING FROM DATABASE (" . count($missing_products) . "):\n";
    foreach ($missing_products as $product) {
        echo "  - $product\n";
    }
    echo "\n";
}

if (!empty($wrong_page_ref)) {
    echo "WRONG PAGE REFERENCES (" . count($wrong_page_ref) . "):\n";
    foreach ($wrong_page_ref as $product) {
        echo "  - $product\n";
    }
    echo "\n";
}

if (!empty($correct_page_ref)) {
    echo "CORRECT PAGE REFERENCES (" . count($correct_page_ref) . "):\n";
    foreach ($correct_page_ref as $product) {
        echo "  - $product\n";
    }
}

// Close connection
$pdo = null;
?>