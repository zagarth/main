<?php
// Process the provided product list to check and update database

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=CadmanClients",
        "cadman_admin",
        "Admin2025!Cadman",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Parse the provided data into an array
    $product_data = [
        '1208' => 'page_10c',
        '1210' => 'page_10c',
        '1869' => 'page_10b',
        '1877' => 'page_10b',
        '1879' => 'page_10c',
        '1890' => 'page_10c',
        '1895' => 'page_10c',
        '1916' => 'page_10c',
        '1919' => 'page_10c',
        '1921' => 'page_10c',
        '1923' => 'page_10c',
        '1924' => 'page_10c',
        '1925' => 'page_10c',
        '1928' => 'page_10c',
        '1930' => 'page_10b',
        '1932' => 'page_10c',
        '1934' => 'page_10b',
        '1938' => 'page_10c',
        '1945' => 'page_10b',
        '1951' => 'page_10c',
        '1967' => 'page_10b',
        '1987' => 'page_10b',
        '2009' => 'page_10c',
        '2010' => 'page_10c',
        '2027' => 'page_10b',
        '2028' => 'page_10b',
        '2029' => 'page_10b',
        '2030' => 'page_10b',
        '2070' => 'page_10b',
        '2077' => 'page_10b',
        '2078' => 'page_10c',
        '2079' => 'page_10c',
        '2080' => 'page_10c',
        '2273' => 'page_10b',
        '2274' => 'page_10b',
        '2275' => 'page_10b',
        '2276' => 'page_10c',
        '2277' => 'page_10b',
        'BR-FPHH' => 'page_10d',
        'C297' => 'page_10b',
        'F134' => 'page_10a',
        'F1376' => 'page_10a',
        'F1379' => 'page_10a',
        'F138' => 'page_10a',
        'F140' => 'page_10',
        'F2507' => 'page_10',
        'F2513' => 'page_10',
        'F2518' => 'page_10',
        'F2519' => 'page_10',
        'F2520' => 'page_10',
        'F2523' => 'page_10',
        'F2524' => 'page_10',
        'F2526' => 'page_10a',
        'F2530' => 'page_10',
        'F2531' => 'page_10a',
        'F2532' => 'page_10',
        'F2536' => 'page_10a',
        'F2537' => 'page_10',
        'F2538' => 'page_10a',
        'F2539' => 'page_10a',
        'F2541' => 'page_10a',
        'F2543' => 'page_10a',
        'F2544' => 'page_10',
        'F2545' => 'page_10a',
        'F2547' => 'page_10a',
        'F2550' => 'page_10a',
        'F2552' => 'page_10',
        'F2553' => 'page_10a',
        'F2554' => 'page_10',
        'F2556' => 'page_10',
        'F2557' => 'page_10',
        'F2558' => 'page_10a',
        'F2559' => 'page_10a',
        'F2560' => 'page_10a',
        'F2561' => 'page_10a',
        'F2562' => 'page_10a',
        'F2563' => 'page_10',
        'F2564' => 'page_10a',
        'F2565' => 'page_10a',
        'F2566' => 'page_10',
        'F2567' => 'page_10',
        'F2568' => 'page_10',
        'F2569' => 'page_10a',
        'F2570' => 'page_10a',
        'F2571' => 'page_10a',
        'F2572' => 'page_10',
        'F2573' => 'page_10a',
        'F2574' => 'page_10',
        'F2575' => 'page_10',
        'F2576' => 'page_10a',
        'F2577' => 'page_10a',
        'F2579' => 'page_10',
        'F2580' => 'page_10',
        'F2581' => 'page_10',
        'F2582' => 'page_10a',
        'F400T' => 'page_10',
        'F6TI8FCL' => 'page_10',
        'F6T18FCM' => 'page_10',
        'FC51' => 'page_10d',
        'FC65' => 'page_10d',
        'FC66' => 'page_10d',
        'FPH' => 'page_10d',
        'FPH8' => 'page_10d',
        'FPHH' => 'page_10d',
        'FPO' => 'page_10d',
        'FPR' => 'page_10d',
        'FPT' => 'page_10d'
    ];
    
    echo "=== PROCESSING PAGE 10 SERIES PRODUCTS ===\n";
    echo "Total products to process: " . count($product_data) . "\n\n";
    
    $found_count = 0;
    $updated_count = 0;
    $added_count = 0;
    $already_correct = 0;
    
    foreach ($product_data as $product_id => $page_reference) {
        // Check if product exists
        $stmt = $pdo->prepare("SELECT product_id, page_reference FROM catalog_products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $found_count++;
            
            if ($existing['page_reference'] === $page_reference) {
                echo "✓ {$product_id}: Already correct ({$page_reference})\n";
                $already_correct++;
            } else {
                // Update with correct page reference
                $old_page = $existing['page_reference'] ?: 'NULL';
                $update_stmt = $pdo->prepare("UPDATE catalog_products SET page_reference = ?, pdf_file = ? WHERE product_id = ?");
                $update_stmt->execute([$page_reference, $page_reference . '.pdf', $product_id]);
                echo "✓ {$product_id}: Updated '{$old_page}' → '{$page_reference}'\n";
                $updated_count++;
            }
        } else {
            // Add missing product
            $insert_stmt = $pdo->prepare("INSERT INTO catalog_products (product_id, page_reference, pdf_file) VALUES (?, ?, ?)");
            $insert_stmt->execute([$product_id, $page_reference, $page_reference . '.pdf']);
            echo "+ {$product_id}: Added to {$page_reference}\n";
            $added_count++;
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Products found in database: {$found_count}\n";
    echo "Already correct: {$already_correct}\n";
    echo "Updated: {$updated_count}\n";
    echo "Added: {$added_count}\n";
    echo "Total processed: " . count($product_data) . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>