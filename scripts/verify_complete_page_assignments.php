<?php
// Verify and update all page 9A, 9AA, 9B, 9C products according to user's definitive list

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=CadmanClients",
        "cadman_admin",
        "Admin2025!Cadman",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // User's definitive product lists
    $page_assignments = [
        'page_09a' => [
            'Firefighters', 'RN1', 'RN3', 'RN301B', 'RN31M', 'RNA5', 'RNA9', 
            'RPN1', 'LPN1', 'PA1', 'CNA3', 'PCW301B', 'PCW31M', 'CT15', 
            'P-LPN1', 'P-PCW310B', 'P-RN1', 'P-RPN1', 'HCA1'
        ],
        'page_09aa' => [
            'HSW301B', 'LNA1', 'LPN301B', 'LPN31M', 'MLT1', 'PAR14M', 'PAR17B', 
            'BSN3', 'VET14M', 'VET15L', 'P-BN1', 'CCA301B', 'CNA301B', 'CRW301B', 
            'P-FF3', 'P-FF4', 'P-FF5', 'EMR31M', 'EMT1', 'EMT31M', 'Paramedic'
        ],
        'page_09b' => [
            '1108MAS', '1116MAS', '1119MAS', '1231MAS', '231T MAS', '433MAS', 
            '436MAS', '4911', '4912', '4913', '8GMAS', 'C553MAS', 'C58MAS', 'C64MAS'
        ],
        'page_09c' => [
            'S33HM', 'S36HM', 'S38M', 'S46M', 'S6RM', 'SA12M', 'S14M', 'S16DM', 
            'S17B', 'S180M', 'S185M', 'S19B', 'S20M', 'S240L', 'S24M', 'S25M', 
            'S26M', 'S301DB', 'S30HM', 'S31HM', 'S32HM', 'S330B'
        ]
    ];
    
    echo "=== VERIFYING AND UPDATING PAGE ASSIGNMENTS ===\n\n";
    
    $total_products = 0;
    $updated_count = 0;
    $correct_count = 0;
    $missing_count = 0;
    $missing_products = [];
    
    foreach ($page_assignments as $page => $products) {
        echo "Checking {$page} products:\n";
        echo str_repeat("-", 40) . "\n";
        
        foreach ($products as $product_id) {
            $total_products++;
            
            // Check if product exists in database
            $stmt = $pdo->prepare("SELECT product_id, page_reference FROM catalog_products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['page_reference'] === $page) {
                    echo "✓ {$product_id}: Already correct ({$page})\n";
                    $correct_count++;
                } else {
                    // Update to correct page
                    $old_page = $result['page_reference'] ?: 'NULL';
                    $update_stmt = $pdo->prepare("UPDATE catalog_products SET page_reference = ? WHERE product_id = ?");
                    $update_stmt->execute([$page, $product_id]);
                    echo "✓ {$product_id}: Updated from '{$old_page}' → '{$page}'\n";
                    $updated_count++;
                }
            } else {
                echo "✗ {$product_id}: NOT FOUND in database (should be {$page})\n";
                $missing_count++;
                $missing_products[] = ['product' => $product_id, 'page' => $page];
            }
        }
        echo "\n";
    }
    
    echo "=== VERIFICATION SUMMARY ===\n";
    echo "Total products checked: {$total_products}\n";
    echo "Already correct: {$correct_count}\n";
    echo "Successfully updated: {$updated_count}\n";
    echo "Missing from database: {$missing_count}\n\n";
    
    if (!empty($missing_products)) {
        echo "=== MISSING PRODUCTS ===\n";
        foreach ($missing_products as $missing) {
            echo "- {$missing['product']} (should be on {$missing['page']})\n";
        }
        echo "\n";
    }
    
    // Now check for any products currently on these pages that SHOULDN'T be there
    echo "=== CHECKING FOR INCORRECTLY ASSIGNED PRODUCTS ===\n";
    $all_correct_products = array_merge(...array_values($page_assignments));
    
    $stmt = $pdo->prepare("SELECT product_id, page_reference FROM catalog_products WHERE page_reference IN ('page_09a', 'page_09aa', 'page_09b', 'page_09c')");
    $stmt->execute();
    $current_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $incorrect_assignments = [];
    foreach ($current_assignments as $assignment) {
        if (!in_array($assignment['product_id'], $all_correct_products)) {
            $incorrect_assignments[] = $assignment;
            echo "⚠ {$assignment['product_id']}: Currently assigned to {$assignment['page_reference']} but NOT in user's list\n";
        }
    }
    
    if (empty($incorrect_assignments)) {
        echo "✓ No incorrectly assigned products found!\n";
    }
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>