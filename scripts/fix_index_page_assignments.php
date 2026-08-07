<?php
// Script to fix products from index pages with correct page assignments

// Database connection
$host = 'localhost';
$dbname = 'CadmanClients';  
$username = 'scanner';
$password = 'scan123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Fixing products from index pages with correct page assignments...\n\n";

    // Index pages to process
    $indexPages = [
        'index_page_medical_01.pdf'
    ];

    foreach ($indexPages as $pdfFile) {
        echo "Processing $pdfFile...\n";
        
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

        // Extract product lines with page references
        $lines = explode("\n", $output);
        $productData = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Match format like "2BMC . . . . . . 26A" or "14EMC-10K 25A"
            if (preg_match('/^([A-Z0-9-]+)\s+.*?([0-9A-Z]+A)$/', $line, $matches)) {
                $productId = $matches[1];
                $pageRef = $matches[2];
                $pageFile = "page_" . $pageRef . ".pdf";
                
                $productData[] = [
                    'product_id' => $productId,
                    'page_file' => $pageFile,
                    'page_reference' => $pageRef
                ];
            }
        }
        
        echo "  📋 Found " . count($productData) . " products with page references\n";
        
        // Determine category from filename
        $category = 'other';
        if (strpos($pdfFile, 'medical') !== false) $category = 'medical';
        
        $updated = 0;
        $added = 0;
        $skipped = 0;
        
        foreach ($productData as $product) {
            $productId = $product['product_id'];
            $pageFile = $product['page_file'];
            $pageRef = $product['page_reference'];
            
            // Check if product exists with wrong pdf_file (from index page)
            $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE UPPER(product_id) = ? AND pdf_file = ? LIMIT 1");
            $stmt->execute([strtoupper($productId), $pdfFile]);
            $existingRow = $stmt->fetch();
            
            if ($existingRow) {
                // Update existing product with correct page file
                $updateStmt = $pdo->prepare("
                    UPDATE catalog_products 
                    SET pdf_file = ?, page_reference = ?, special_notes = 'Corrected page assignment from index'
                    WHERE UPPER(product_id) = ?
                ");
                $updateStmt->execute([$pageFile, $pageRef, strtoupper($productId)]);
                echo "  ✓ Updated $productId: $pdfFile -> $pageFile (page $pageRef)\n";
                $updated++;
            } else {
                // Check if product exists elsewhere
                $stmt = $pdo->prepare("SELECT product_id FROM catalog_products WHERE UPPER(product_id) = ? LIMIT 1");
                $stmt->execute([strtoupper($productId)]);
                if ($stmt->fetch()) {
                    $skipped++;
                    continue;
                }
                
                // Add new product with correct page file
                $stmt = $pdo->prepare("
                    INSERT INTO catalog_products 
                    (product_id, pdf_file, page_reference, category, pattern, style, special_notes, 
                     product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                     series, white_gold_available, base_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $success = $stmt->execute([
                    $productId,              // product_id
                    $pageFile,              // pdf_file (correct page)
                    $pageRef,               // page_reference
                    $category,              // category
                    null,                   // pattern
                    null,                   // style
                    'Added from index page with correct page assignment', // special_notes
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
                    echo "  ✓ Added $productId to correct page: $pageFile (page $pageRef)\n";
                    $added++;
                }
            }
        }
        
        echo "  Summary: Updated $updated, Added $added, Skipped $skipped\n\n";
    }
    
    echo "Done fixing products from index pages.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>