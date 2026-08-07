<?php
// Script to check missing pages and add first product if page is not in database

// Database connection
$host = 'localhost';
$dbname = 'CadmanClients';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    'page_03eee.pdf', 'page_03ee.pdf', 'page_03.pdf', 'page_06L.pdf',
    'page_07c.pdf', 'page_07d.pdf', 'page_07e.pdf', 'page_07f.pdf',
    'page_08a.pdf', 'page_08b.pdf', 'page_08c.pdf', 'page_08d.pdf',
    'page_09aa.pdf', 'page_09a.pdf', 'page_09b.pdf', 'page_09c.pdf',
    'page_10a.pdf', 'page_10b.pdf', 'page_10c.pdf', 'page_10.pdf',
    'page_11b.pdf', 'page_11c.pdf', 'page_11d.pdf', 'page_11e.pdf',
    'page_11g.pdf', 'page_11r.pdf', 'page_20a.pdf', 'page_21B.pdf',
    'page_22a.pdf', 'page_22b.pdf', 'page_22c.pdf', 'page_22.pdf',
    'page_23B.pdf', 'page_24A.pdf', 'page_24B.pdf', 'page_25a.pdf',
    'page_26A.pdf', 'page_27A.pdf', 'page_27B.pdf', 'page_33G.pdf',
    'page_34N.pdf', 'page_35N.pdf'
];

    ]);
    
    // Get list of missing pages
    $missingPages = [
    
    echo "Checking missing pages...\n\n";
    
    foreach ($missingPages as $pdfFile) {
        echo "Checking $pdfFile...\n";
        
        // Check if this page already has products in database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_products WHERE pdf_file = ?");
        $stmt->execute([$pdfFile]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "  ✓ Page already has $count products in database, skipping\n";
            continue;
        }
        
        // Extract first product from PDF using pdftotext
        $pdfPath = "Cadman_catalog/$pdfFile";
        if (!file_exists($pdfPath)) {
            echo "  ✗ PDF file not found: $pdfPath\n";
            continue;
        }
        
        // Extract text from PDF
        $output = shell_exec("pdftotext '$pdfPath' -");
        
        if (empty($output)) {
            echo "  ✗ Could not extract text from PDF\n";
            continue;
        }
        
        // Look for product patterns - common product ID formats
        $patterns = [
            '/\b([A-Z]{1,3}\d{1,4}[A-Z]?)\b/',           // MK56D, C125, etc.
            '/\b(\d{1,4}[A-Z]{1,3})\b/',                 // 2BMC, 123M, etc.
            '/\b([A-Z]{1,2}\d{1,3})\b/',                 // WK26, C21, etc.
            '/\b(\d{1,4}[A-Z]?\d*[A-Z]*)\b/',           // 106D, 2274, etc.
            '/\b(#[A-Z]{2,4})\b/',                       // #AAA patterns
        ];
        
        $firstProduct = null;
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $output, $matches)) {
                foreach ($matches[1] as $match) {
                    // Skip common words/false positives
                    if (in_array(strtoupper($match), ['PDF', 'PAGE', 'SIZE', 'GOLD', 'SILVER', 'MM', 'CM', 'IN'])) {
                        continue;
                    }
                    
                    // Check if this looks like a valid product ID
                    if (strlen($match) >= 2 && strlen($match) <= 8) {
                        $firstProduct = strtoupper($match);
                        break 2;
                    }
                }
            }
        }
        
        if (!$firstProduct) {
            echo "  ✗ No valid product ID found in PDF\n";
            continue;
        }
        
        // Check if this product already exists in database
        $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE UPPER(product_id) = ? LIMIT 1");
        $stmt->execute([$firstProduct]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo "  ℹ Product $firstProduct already exists in database, skipping\n";
            continue;
        }
        
        // Extract page reference from filename
        $pageRef = '';
        if (preg_match('/page_(\d+[a-z]*)\.pdf/i', $pdfFile, $pageMatches)) {
            $pageRef = strtoupper($pageMatches[1]);
        }
        
        // Add the product to database
        $stmt = $pdo->prepare("
            INSERT INTO catalog_products 
            (product_id, pdf_file, page_reference, category, pattern, style, special_notes, 
             product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
             series, white_gold_available, base_price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $firstProduct,           // product_id
            $pdfFile,               // pdf_file
            $pageRef,               // page_reference
            'scanned',              // category
            null,                   // pattern
            null,                   // style
            'Auto-added from missing page scan', // special_notes
            null,                   // product_name
            null,                   // subcategory
            null,                   // width_mm
            null,                   // profile
            null,                   // diamond_count
            null,                   // gender_variant
            null,                   // series
            0,                      // white_gold_available
            null                    // base_price
        ]);
        
        if ($success) {
            echo "  ✓ Added product $firstProduct for page $pageRef ($pdfFile)\n";
        } else {
            echo "  ✗ Failed to add product $firstProduct\n";
        }
    }
    
    echo "\nDone checking missing pages.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>