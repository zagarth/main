<?php
/**
 * Auto-fix Gender Variant Page References
 * Finds L/M product pairs where one has a proper page reference and copies it to the other
 */

require_once 'includes/db_config_readonly.php';

// This script will analyze what CAN be fixed, but won't actually update anything yet
// Run with ?execute=1 to actually perform the updates

$dryRun = !isset($_GET['execute']);

try {
    $pdo = getReadOnlyDBConnection();
    
    echo "GENDER VARIANT PAGE REFERENCE AUTO-FIX ANALYSIS\n";
    echo "===============================================\n\n";
    
    if ($dryRun) {
        echo "🔍 DRY RUN MODE - No changes will be made\n";
        echo "Add ?execute=1 to actually perform updates\n\n";
    } else {
        echo "⚠️  EXECUTE MODE - Changes will be applied!\n\n";
    }
    
    // Find L/M pairs where one has proper page reference and other doesn't
    // Handles both numeric patterns (200L/200M) and T-patterns (4T18L/4T18M)
    $stmt = $pdo->query("
        SELECT 
            l.product_id as l_product,
            l.page_reference as l_page,
            l.pdf_file as l_pdf,
            m.product_id as m_product,
            m.page_reference as m_page,
            m.pdf_file as m_pdf,
            l.product_name
        FROM catalog_products l
        JOIN catalog_products m ON SUBSTRING(l.product_id, 1, LENGTH(l.product_id) - 1) = SUBSTRING(m.product_id, 1, LENGTH(m.product_id) - 1)
        WHERE l.product_id REGEXP '^[0-9]+T?[0-9]*L$'
        AND m.product_id REGEXP '^[0-9]+T?[0-9]*M$'
        ORDER BY l.product_id
    ");
    
    $pairs = $stmt->fetchAll();
    $fixableCount = 0;
    $fixes = [];
    
    foreach ($pairs as $pair) {
        $lHasProperPage = $pair['l_page'] && !in_array($pair['l_page'], ['plain', 'fancy', 'celtic', '']);
        $mHasProperPage = $pair['m_page'] && !in_array($pair['m_page'], ['plain', 'fancy', 'celtic', '']);
        
        $sourceProduct = null;
        $targetProduct = null;
        $pageToSet = null;
        $pdfToSet = null;
        $reason = '';
        
        // Case 1: L has proper page, M doesn't (or has invalid page)
        if ($lHasProperPage && !$mHasProperPage) {
            $sourceProduct = $pair['l_product'];
            $targetProduct = $pair['m_product'];
            $pageToSet = $pair['l_page'];
            $pdfToSet = $pair['l_pdf'];
            $reason = $pair['m_page'] ? "replace invalid '{$pair['m_page']}'" : "add missing page";
        }
        // Case 2: M has proper page, L doesn't (or has invalid page)
        elseif ($mHasProperPage && !$lHasProperPage) {
            $sourceProduct = $pair['m_product'];
            $targetProduct = $pair['l_product'];
            $pageToSet = $pair['m_page'];
            $pdfToSet = $pair['m_pdf'];
            $reason = $pair['l_page'] ? "replace invalid '{$pair['l_page']}'" : "add missing page";
        }
        
        if ($sourceProduct && $targetProduct) {
            $fixableCount++;
            
            echo "✅ Fix #{$fixableCount}: Copy {$sourceProduct} page '{$pageToSet}' → {$targetProduct} ({$reason})\n";
            echo "   Product: {$pair['product_name']}\n";
            
            $fixes[] = [
                'target' => $targetProduct,
                'page' => $pageToSet,
                'pdf' => $pdfToSet,
                'source' => $sourceProduct,
                'reason' => $reason
            ];
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SUMMARY:\n";
    echo "Total L/M pairs analyzed: " . count($pairs) . "\n";
    echo "Can be auto-fixed: {$fixableCount}\n";
    
    if ($fixableCount > 0 && !$dryRun) {
        echo "\n⚡ EXECUTING FIXES...\n";
        
        // Get writable database connection for updates
        require_once 'includes/db_config.php';
        $updatePdo = getDBConnection();
        
        $successCount = 0;
        foreach ($fixes as $fix) {
            try {
                $updateStmt = $updatePdo->prepare("
                    UPDATE catalog_products 
                    SET page_reference = ?, pdf_file = ?
                    WHERE product_id = ?
                ");
                
                $result = $updateStmt->execute([$fix['page'], $fix['pdf'], $fix['target']]);
                
                if ($result) {
                    echo "✅ Updated {$fix['target']} → page {$fix['page']}\n";
                    $successCount++;
                } else {
                    echo "❌ Failed to update {$fix['target']}\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error updating {$fix['target']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n🎉 COMPLETED: {$successCount} of {$fixableCount} fixes applied successfully!\n";
    } elseif ($fixableCount > 0) {
        echo "\n🔗 To execute these fixes, visit: " . basename(__FILE__) . "?execute=1\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>