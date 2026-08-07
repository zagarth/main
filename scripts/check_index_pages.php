<?php
// Script to check index pages and find which ones need to be added to database

// Database connection  
exec('sudo mysql -e "CREATE USER IF NOT EXISTS \'scanner\'@\'localhost\' IDENTIFIED BY \'scan123\'; GRANT ALL PRIVILEGES ON CadmanClients.* TO \'scanner\'@\'localhost\'; FLUSH PRIVILEGES;" 2>/dev/null');

$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Get list of index pages
    $indexPages = [
        'index_page_10k-crosses_01.pdf',
        'index_page_10K-LOCKETS_01.pdf',
        'index_page_bracelets_01.pdf',
        'index_page_EMBLEMATIC_01.pdf',
        'index_page_engagementsets_01.pdf',
        'index_page_gents-rings_01.pdf',
        'index_page_ladiesstone-001.pdf',
        'index_page_ladiesstone-002.pdf',
        'index_page_medical_01.pdf',
        'index_page_mens-jewellry_01.pdf',
        'index_page_mother-001.pdf',
        'index_page_pendants-earrings_01.pdf',
        'index_page_signets_01.pdf',
        'index_page_ster-crosses_01.pdf',
        'index_page_STER-LOCKETS_01.pdf',
        'index_page_wedding_01.pdf',
        'index_page_wedding_02.pdf',
        'index_page_wedding_03.pdf'
    ];
    
    echo "Checking index pages for missing products...\n\n";
    
    foreach ($indexPages as $pdfFile) {
        echo "Checking $pdfFile...\n";
        
        // Check if this index page already has products in database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_products WHERE pdf_file = ?");
        $stmt->execute([$pdfFile]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "  ✓ Index page already has $count products in database\n";
            continue;
        }
        
        // Extract text from PDF
        $pdfPath = "Cadman_catalog/$pdfFile";
        if (!file_exists($pdfPath)) {
            echo "  ✗ PDF file not found: $pdfPath\n";
            continue;
        }
        
        $output = shell_exec("pdftotext '$pdfPath' - 2>/dev/null");
        
        if (empty($output)) {
            echo "  ✗ Could not extract text from PDF\n";
            continue;
        }
        
        // Look for product patterns in index pages
        $patterns = [
            '/\b(\d{1,3}[A-Z]{1,4})\b/',                 // 2BMC, 123M, etc.
            '/\b([A-Z]{1,3}\d{1,4}[A-Z]?)\b/',           // MK56D, C125, etc.
            '/\b([A-Z]{1,2}\d{1,3})\b/',                 // WK26, C21, etc.
            '/\b(#[A-Z]{2,4})\b/',                       // #AAA patterns
            '/\b([A-Z]+\d+[A-Z]*)\b/',                   // General alphanumeric
        ];
        
        $foundProducts = [];
        
        // Extract potential product IDs
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $output, $matches)) {
                foreach ($matches[1] as $match) {
                    // Skip common false positives
                    if (in_array(strtoupper($match), ['PDF', 'PAGE', 'SIZE', 'GOLD', 'SILVER', 'MM', 'CM', 'IN', 'KT', 'CT', 'LBS', 'OZ', 'GM', 'GR'])) {
                        continue;
                    }
                    
                    // Valid length check
                    if (strlen($match) >= 2 && strlen($match) <= 8) {
                        $foundProducts[] = strtoupper($match);
                    }
                }
            }
        }
        
        if (empty($foundProducts)) {
            echo "  ✗ No valid product IDs found\n";
            continue;
        }
        
        // Check first few products to see if any exist in database
        $foundProducts = array_unique($foundProducts);
        
        // Filter out page references (number+letter combos like 23A, 11B, etc)
        $actualProducts = [];
        foreach ($foundProducts as $product) {
            // Skip if it looks like a page reference (1-50 + letters)
            if (preg_match('/^([1-4]?\d|50)[A-Za-z]+$/i', $product)) {
                continue; // This is likely a page reference, not a product
            }
            $actualProducts[] = $product;
        }
        
        if (empty($actualProducts)) {
            echo "  ✗ No actual product IDs found (only page references)\n";
            continue;
        }
        
        $firstProduct = $actualProducts[0];
        
        echo "  📋 Found products: " . implode(', ', array_slice($actualProducts, 0, 5)) . (count($actualProducts) > 5 ? '...' : '') . "\n";
        echo "  🔍 Testing first product: $firstProduct\n";
        
        // Check if this product exists in database
        $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE UPPER(product_id) = ? LIMIT 1");
        $stmt->execute([$firstProduct]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo "  ⚠️ Product $firstProduct already exists in database - INDEX PAGE IS MISSING!\n";
        } else {
            echo "  ❌ Product $firstProduct NOT in database - INDEX PAGE NEEDS TO BE ADDED!\n";
        }
        
        echo "\n";
    }
    
    echo "Done checking index pages.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>