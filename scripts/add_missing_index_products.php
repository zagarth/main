<?php
// Script to add missing products from index pages to database

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
    
    // Index pages that need products added to database
    $missingIndexPages = [
        'index_page_bracelets_01.pdf',
        'index_page_EMBLEMATIC_01.pdf', 
        'index_page_ladiesstone-002.pdf',
        'index_page_medical_01.pdf',
        'index_page_mens-jewellry_01.pdf',
        'index_page_pendants-earrings_01.pdf',
        'index_page_ster-crosses_01.pdf',
        'index_page_STER-LOCKETS_01.pdf'
    ];
    
    echo "Adding missing products from index pages...\n\n";
    
    foreach ($missingIndexPages as $pdfFile) {
        echo "Processing $pdfFile...\n";
        
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
        
        // Extract product patterns
        $patterns = [
            '/^([A-Z0-9-]+).*?[0-9A-Z]+A$/m',           // Line-based pattern for index pages
            '/([A-Z0-9-]+)\s*\..*?[0-9A-Z]+A/',         // Flexible dots pattern
            '/\b(\d{1,4}[A-Z]{1,4})\b/',                 // 2BMC, 123M, etc.
            '/\b([A-Z]{1,3}\d{1,4}[A-Z]?)\b/',           // MK56D, C125, etc.
            '/\b([A-Z]{1,2}\d{1,4})\b/',                 // WK26, C21, etc.
            '/\b(#[A-Z]{2,4})\b/',                       // #AAA patterns
            '/\b([A-Z]+\d+[A-Z]*)\b/',                   // General patterns
        ];
        
        $foundProducts = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $output, $matches)) {
                foreach ($matches[1] as $match) {
                    // Skip common false positives
                    if (in_array(strtoupper($match), ['PDF', 'PAGE', 'SIZE', 'GOLD', 'SILVER', 'MM', 'CM', 'IN', 'KT', 'CT', 'LBS', 'OZ', 'GM', 'GR', 'QTY', 'STD', 'MIN', 'MAX'])) {
                        continue;
                    }
                    
                    // Skip page references (number+letter like 23A)
                    if (preg_match('/^([1-4]?\d|50)[A-Za-z]+$/i', $match)) {
                        continue;
                    }
                    
                    // Valid length check
                    if (strlen($match) >= 2 && strlen($match) <= 10) {
                        $foundProducts[] = strtoupper($match);
                    }
                }
            }
        }
        
        if (empty($foundProducts)) {
            echo "  ✗ No valid products found\n";
            continue;
        }
        
        $foundProducts = array_unique($foundProducts);
        echo "  📋 Found " . count($foundProducts) . " unique products\n";
        
        // Determine category from filename
        $category = 'other';
        if (strpos($pdfFile, 'medical') !== false) $category = 'medical';
        elseif (strpos($pdfFile, 'wedding') !== false) $category = 'wedding';
        elseif (strpos($pdfFile, 'engagement') !== false) $category = 'engagement';
        elseif (strpos($pdfFile, 'bracelet') !== false) $category = 'bracelets';
        elseif (strpos($pdfFile, 'cross') !== false) $category = 'crosses';
        elseif (strpos($pdfFile, 'locket') !== false) $category = 'lockets';
        elseif (strpos($pdfFile, 'signet') !== false) $category = 'signets';
        elseif (strpos($pdfFile, 'gents') !== false) $category = 'gents_rings';
        elseif (strpos($pdfFile, 'mens') !== false) $category = 'mens_jewelry';
        elseif (strpos($pdfFile, 'pendant') !== false) $category = 'pendants';
        elseif (strpos($pdfFile, 'emblematic') !== false) $category = 'emblematic';
        
        $added = 0;
        $skipped = 0;
        
        foreach ($foundProducts as $productId) {
            // Check if product already exists
            $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE UPPER(product_id) = ? LIMIT 1");
            $stmt->execute([$productId]);
            if ($stmt->fetch()) {
                $skipped++;
                continue;
            }
            
            // Add product to database
            $stmt = $pdo->prepare("
                INSERT INTO catalog_products 
                (product_id, pdf_file, page_reference, category, pattern, style, special_notes, 
                 product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                 series, white_gold_available, base_price)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $productId,              // product_id
                $pdfFile,               // pdf_file
                null,                   // page_reference (index pages don't have page refs)
                $category,              // category
                null,                   // pattern
                null,                   // style
                'Auto-added from index page scan', // special_notes
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
                $added++;
            }
        }
        
        echo "  ✓ Added $added new products, skipped $skipped existing products\n\n";
    }
    
    echo "Done adding missing products from index pages.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>