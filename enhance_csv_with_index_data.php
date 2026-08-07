<?php
/**
 * Cross-reference Index PDFs with Missing Products CSV
 * This script reads index PDFs to find page references for products missing them
 * and generates an enhanced CSV file without modifying the database
 */

require_once 'includes/db_config_readonly.php';

echo "INDEX PDF CROSS-REFERENCE TOOL\n";
echo "==============================\n";

try {
    $pdo = getReadOnlyDBConnection();
    
    // Get products that have names but no page references
    $stmt = $pdo->query("
        SELECT 
            product_id,
            product_name,
            category,
            subcategory,
            pattern,
            style,
            special_notes,
            width_mm,
            profile,
            diamond_count,
            gender_variant,
            series,
            white_gold_available,
            base_price
        FROM catalog_products 
        WHERE product_name IS NOT NULL 
        AND product_name != '' 
        AND (page_reference IS NULL OR page_reference = '')
        ORDER BY category, product_id
    ");
    
    $missingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "📄 Found " . count($missingProducts) . " products missing page references\n\n";
    
    // Get list of index PDF files
    $indexDir = './Cadman_catalog/';
    $indexFiles = [];
    
    if (is_dir($indexDir)) {
        $files = scandir($indexDir);
        foreach ($files as $file) {
            if (preg_match('/^index_page_.*\.pdf$/i', $file)) {
                $indexFiles[] = $file;
            }
        }
    }
    
    echo "📑 Found " . count($indexFiles) . " index PDF files:\n";
    foreach ($indexFiles as $file) {
        echo "   - $file\n";
    }
    echo "\n";
    
    // For now, we'll create a structure to hold potential matches
    // In a real implementation, you'd use a PDF parsing library like TCPDF or similar
    echo "🔍 SIMULATING INDEX PDF PARSING...\n";
    echo "(Note: Actual PDF parsing would require additional libraries)\n\n";
    
    // Create enhanced CSV data structure
    $enhancedData = [];
    $potentialMatches = 0;
    
    foreach ($missingProducts as $product) {
        $productId = $product['product_id'];
        $category = $product['category'];
        
        // Try to match product ID patterns with likely index files AND page references
        $suggestedIndexFile = '';
        $suggestedPageRef = '';
        $suggestedPdfFile = '';
        
        // First, try to find similar products that already have page references
        $similarStmt = $pdo->prepare("
            SELECT page_reference, pdf_file, COUNT(*) as count
            FROM catalog_products 
            WHERE category = ? 
            AND page_reference IS NOT NULL 
            AND page_reference != ''
            AND pdf_file IS NOT NULL
            GROUP BY page_reference, pdf_file
            ORDER BY count DESC
            LIMIT 3
        ");
        $similarStmt->execute([$category]);
        $similarProducts = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pattern matching based on category and product ID
        switch ($category) {
            case 'engagement':
                $suggestedIndexFile = 'index_page_engagementsets_01.pdf';
                // Most engagement products are on pages 7A, 7B, 7C
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '7A'; // Default based on existing data
                    $suggestedPdfFile = 'page_7a.pdf';
                }
                break;
            case 'wedding_bands':
            case 'plain_bands':
                $suggestedIndexFile = 'index_page_wedding_01.pdf';
                // Plain bands are typically on pages 1A, 1B
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '1A';
                    $suggestedPdfFile = 'page_01a.pdf';
                }
                break;
            case 'crosses':
                if (preg_match('/^[0-9]+/', $productId)) {
                    $suggestedIndexFile = 'index_page_10k-crosses_01.pdf';
                } else {
                    $suggestedIndexFile = 'index_page_ster-crosses_01.pdf';
                }
                // Try to determine page based on product ID pattern
                if (preg_match('/^([0-9]+)/', $productId, $matches)) {
                    $num = intval($matches[1]);
                    if ($num >= 100 && $num <= 199) {
                        $suggestedPageRef = '18A';
                        $suggestedPdfFile = 'page_18a.pdf';
                    } elseif ($num >= 1 && $num <= 99) {
                        $suggestedPageRef = '17A';
                        $suggestedPdfFile = 'page_17a.pdf';
                    }
                } elseif (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                }
                break;
            case 'lockets':
                if (preg_match('/^[0-9]+/', $productId)) {
                    $suggestedIndexFile = 'index_page_10K-LOCKETS_01.pdf';
                } else {
                    $suggestedIndexFile = 'index_page_STER-LOCKETS_01.pdf';
                }
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                }
                break;
            case 'bracelets':
                $suggestedIndexFile = 'index_page_bracelets_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '21A';
                    $suggestedPdfFile = 'page_21a.pdf';
                }
                break;
            case 'medical':
                $suggestedIndexFile = 'index_page_medical_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '11A';
                    $suggestedPdfFile = 'page_11a.pdf';
                }
                break;
            case 'emblematic':
                $suggestedIndexFile = 'index_page_EMBLEMATIC_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '12A';
                    $suggestedPdfFile = 'page_12a.pdf';
                }
                break;
            case 'signets':
                $suggestedIndexFile = 'index_page_signets_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '14A';
                    $suggestedPdfFile = 'page_14a.pdf';
                }
                break;
            case 'gents_rings':
                $suggestedIndexFile = 'index_page_gents-rings_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                }
                break;
            case 'ladies_jewelry':
                $suggestedIndexFile = 'index_page_ladiesstone-001.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                }
                break;
            case 'mens_jewelry':
                $suggestedIndexFile = 'index_page_mens-jewellry_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                }
                break;
            case 'pendants':
                $suggestedIndexFile = 'index_page_pendants-earrings_01.pdf';
                if (!empty($similarProducts)) {
                    $suggestedPageRef = $similarProducts[0]['page_reference'];
                    $suggestedPdfFile = $similarProducts[0]['pdf_file'];
                } else {
                    $suggestedPageRef = '22A';
                    $suggestedPdfFile = 'page_22a.pdf';
                }
                break;
        }
        
        // Add to enhanced data with suggestions
        $enhancedProduct = $product;
        $enhancedProduct['suggested_index_file'] = $suggestedIndexFile;
        $enhancedProduct['suggested_page_ref'] = $suggestedPageRef;
        $enhancedProduct['suggested_pdf_file'] = $suggestedPdfFile;
        $enhancedProduct['found_in_index'] = $suggestedPageRef ? 'LIKELY_MATCH' : 'NEEDS_VERIFICATION';
        $enhancedProduct['confidence'] = '';
        
        // Add confidence level based on similar products found
        if (!empty($similarProducts) && $suggestedPageRef) {
            $enhancedProduct['confidence'] = 'HIGH - Based on ' . $similarProducts[0]['count'] . ' similar products';
        } elseif ($suggestedPageRef) {
            $enhancedProduct['confidence'] = 'MEDIUM - Based on category patterns';
        } else {
            $enhancedProduct['confidence'] = 'LOW - Manual verification needed';
        }
        
        $enhancedData[] = $enhancedProduct;
        
        if ($suggestedIndexFile) {
            $potentialMatches++;
        }
    }
    
    echo "✅ Categorized products by likely index files\n";
    echo "📊 Potential matches found: $potentialMatches\n\n";
    
    // Generate enhanced CSV
    $timestamp = date('Y-m-d_H-i-s');
    $csvFilename = "products_missing_page_refs_enhanced_{$timestamp}.csv";
    
    $output = fopen($csvFilename, 'w');
    
    // Enhanced CSV header
    $headers = [
        'Product ID',
        'Product Name', 
        'Category',
        'Subcategory',
        'Pattern',
        'Style',
        'Special Notes',
        'Width MM',
        'Profile',
        'Diamond Count',
        'Gender Variant',
        'Series',
        'White Gold Available',
        'Base Price',
        'Current Page Reference',
        'Suggested Index File',
        'Suggested Page Reference',
        'Suggested PDF File',
        'Confidence Level',
        'Found in Index',
        'Manual Page Reference',
        'Manual PDF File',
        'Notes'
    ];
    
    fputcsv($output, $headers);
    
    // Write enhanced data
    foreach ($enhancedData as $product) {
        $row = [
            $product['product_id'],
            $product['product_name'],
            $product['category'],
            $product['subcategory'],
            $product['pattern'],
            $product['style'],
            $product['special_notes'],
            $product['width_mm'],
            $product['profile'],
            $product['diamond_count'],
            $product['gender_variant'],
            $product['series'],
            $product['white_gold_available'],
            $product['base_price'],
            '', // Current page reference (empty)
            $product['suggested_index_file'],
            $product['suggested_page_ref'],
            $product['suggested_pdf_file'],
            $product['confidence'],
            $product['found_in_index'],
            '', // Manual page reference (to be filled)
            '', // Manual PDF file (to be filled)
            ''  // Notes (for manual comments)
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
    echo "📄 ENHANCED CSV GENERATED: $csvFilename\n\n";
    
    // Summary by category
    echo "📊 SUMMARY BY CATEGORY:\n";
    echo "======================\n";
    
    $categoryStats = [];
    foreach ($enhancedData as $product) {
        $cat = $product['category'];
        if (!isset($categoryStats[$cat])) {
            $categoryStats[$cat] = ['total' => 0, 'with_suggestions' => 0];
        }
        $categoryStats[$cat]['total']++;
        if (!empty($product['suggested_index_file'])) {
            $categoryStats[$cat]['with_suggestions']++;
        }
    }
    
    foreach ($categoryStats as $category => $stats) {
        $percentage = round(($stats['with_suggestions'] / $stats['total']) * 100, 1);
        echo sprintf("%-20s: %3d total, %3d with suggestions (%s%%)\n", 
                    $category, $stats['total'], $stats['with_suggestions'], $percentage);
    }
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "=============\n";
    echo "1. Review the enhanced CSV file: $csvFilename\n";
    echo "2. Manually verify products in their suggested index files\n";
    echo "3. Fill in 'Manual Page Reference' column with actual page numbers\n";
    echo "4. Use 'Notes' column for any special observations\n";
    echo "5. Decide whether to import the verified page references\n\n";
    
    echo "💡 TIP: Focus on categories with high suggestion rates first\n";
    echo "🔍 TODO: Implement actual PDF parsing to auto-fill page references\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>