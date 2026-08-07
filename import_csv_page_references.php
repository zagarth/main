<?php
/**
 * Import Page References from Enhanced CSV to Database
 * This script reads the enhanced CSV file and updates the database with verified page references
 */

require_once 'includes/db_config.php';

echo "CSV PAGE REFERENCE IMPORT TOOL\n";
echo "==============================\n";

// Check if CSV file exists
$csvFiles = glob("products_missing_page_refs_enhanced_*.csv");
if (empty($csvFiles)) {
    echo "❌ ERROR: No enhanced CSV files found!\n";
    echo "Please run enhance_csv_with_index_data.php first\n";
    exit(1);
}

// Use the most recent CSV file
$csvFile = end($csvFiles);
echo "📄 Using CSV file: $csvFile\n\n";

if (!file_exists($csvFile)) {
    echo "❌ ERROR: CSV file not found: $csvFile\n";
    exit(1);
}

try {
    $pdo = getDBConnection();
    
    // Read CSV file
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("Could not open CSV file");
    }
    
    // Read header row to get column positions
    $headers = fgetcsv($handle);
    
    // Find column positions
    $columnMap = [];
    foreach ($headers as $index => $header) {
        $columnMap[strtolower(trim($header))] = $index;
    }
    
    // Verify required columns exist
    $requiredColumns = ['product id', 'suggested page reference'];
    foreach ($requiredColumns as $col) {
        if (!isset($columnMap[$col])) {
            throw new Exception("Required column '$col' not found in CSV");
        }
    }
    
    echo "📋 CSV Columns found:\n";
    foreach ($columnMap as $col => $index) {
        echo "   $col (column $index)\n";
    }
    echo "\n";
    
    $updates = 0;
    $skipped = 0;
    $errors = 0;
    // Determine if this is a dry run - check both GET parameter and command line args
    $dryRun = !isset($_GET['execute']) && !in_array('execute', $argv ?? []);
    
    if ($dryRun) {
        echo "🔍 DRY RUN MODE - No changes will be made\n";
        echo "Add ?execute=1 to actually perform updates\n\n";
    } else {
        echo "⚠️  EXECUTE MODE - Changes will be applied!\n\n";
    }
    
    // Prepare update statement
    $updateStmt = $pdo->prepare("
        UPDATE catalog_products 
        SET page_reference = ?, pdf_file = ?, updated_at = NOW()
        WHERE product_id = ?
    ");
    
    // Process each row
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) < count($headers)) {
            continue; // Skip incomplete rows
        }
        
        $productId = trim($row[$columnMap['product id']]);
        $suggestedPageRef = trim($row[$columnMap['suggested page reference']]);
        $suggestedPdfFile = isset($columnMap['suggested pdf file']) ? trim($row[$columnMap['suggested pdf file']]) : '';
        $confidence = isset($columnMap['confidence level']) ? trim($row[$columnMap['confidence level']]) : '';
        
        // Skip if no suggested page reference
        if (empty($suggestedPageRef)) {
            $skipped++;
            continue;
        }
        
        // Evaluate confidence - accept HIGH, or MEDIUM if product ID pattern matches page reference
        if (strpos($confidence, 'HIGH') === false) {
            if (strpos($confidence, 'MEDIUM') !== false) {
                // For MEDIUM confidence, check if product ID pattern logically matches page reference
                $productIdMatch = false;
                
                // Extract numeric part from product ID for pattern matching
                if (preg_match('/(\d+)/', $productId, $idMatches)) {
                    $productNumeric = $idMatches[1];
                    
                    // Check if page reference contains or relates to this numeric pattern
                    if (preg_match('/(\d+)/', $suggestedPageRef, $pageMatches)) {
                        $pageNumeric = $pageMatches[1];
                        
                        // If product ID number appears in page reference, it's likely valid
                        if ($productNumeric == $pageNumeric || 
                            strpos($suggestedPageRef, $productNumeric) !== false ||
                            // For emblematic items (4911-4913 → 12A), check category-based patterns
                            (strpos($confidence, 'category patterns') !== false && !empty($suggestedPageRef))) {
                            $productIdMatch = true;
                        }
                    }
                }
                
                if (!$productIdMatch) {
                    echo "⏭️  Skipping $productId - MEDIUM confidence without clear ID pattern match ($confidence)\n";
                    $skipped++;
                    continue;
                }
                
                echo "📋 $productId → MEDIUM confidence but product ID pattern validated\n";
            } else {
                echo "⏭️  Skipping $productId - Low confidence ($confidence)\n";
                $skipped++;
                continue;
            }
        }
        
        echo "✅ $productId → Page: $suggestedPageRef";
        if (!empty($suggestedPdfFile)) {
            echo " (PDF: $suggestedPdfFile)";
        }
        echo " - $confidence\n";
        
        if (!$dryRun) {
            try {
                $updateStmt->execute([$suggestedPageRef, $suggestedPdfFile, $productId]);
                $updates++;
            } catch (PDOException $e) {
                echo "❌ Error updating $productId: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            $updates++; // Count for dry run
        }
    }
    
    fclose($handle);
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SUMMARY:\n";
    echo "========\n";
    
    if ($dryRun) {
        echo "🔍 DRY RUN RESULTS:\n";
        echo "Would update: $updates products\n";
        echo "Would skip: $skipped products\n";
        echo "\n🔗 To execute these updates, visit: " . $_SERVER['SCRIPT_NAME'] . "?execute=1\n";
    } else {
        echo "⚡ EXECUTION RESULTS:\n";
        echo "✅ Successfully updated: $updates products\n";
        echo "⏭️  Skipped: $skipped products\n";
        echo "❌ Errors: $errors products\n";
        
        if ($updates > 0) {
            echo "\n🎉 Database updated successfully!\n";
            echo "📊 Products with page references added: $updates\n";
            
            // Get new missing count
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM catalog_products 
                WHERE product_name IS NOT NULL 
                AND product_name != '' 
                AND (page_reference IS NULL OR page_reference = '')
            ");
            $result = $stmt->fetch();
            $newMissingCount = $result['count'];
            $originalCount = 824; // From before
            $reduction = $originalCount - $newMissingCount;
            
            echo "📉 Products still missing page references: $newMissingCount (reduced by $reduction)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>