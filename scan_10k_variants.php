<?php
// Scan all index pages to find 10K variants and their page assignments

echo "SCANNING INDEX PAGES FOR 10K VARIANTS\n";
echo "======================================\n\n";

// Find all index page PDFs
$indexPages = glob('Cadman_catalog/index_page_*.pdf');

$allVariants = [];

foreach ($indexPages as $pdfFile) {
    $basename = basename($pdfFile);
    echo "Processing $basename...\n";
    
    // Extract text from PDF
    $textCommand = "pdftotext \"$pdfFile\" - 2>/dev/null";
    $text = shell_exec($textCommand);
    
    if (empty($text)) {
        echo "  ✗ Could not extract text from $basename\n";
        continue;
    }
    
    // Find all lines with 10K variants and their page references
    $lines = explode("\n", $text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Match format like "14EMC-10K 25A" or "P120-10K . . 25A"
        if (preg_match('/^([A-Z0-9-]+10K)\s+.*?([0-9A-Z]+A)$/', $line, $matches)) {
            $variantId = $matches[1];
            $pageRef = $matches[2];
            
            if (!isset($allVariants[$variantId])) {
                $allVariants[$variantId] = [];
            }
            
            $allVariants[$variantId][] = [
                'page' => $pageRef,
                'index_file' => $basename
            ];
            
            echo "  Found: $variantId -> page $pageRef\n";
        }
    }
}

// Generate report
$report = "10K VARIANT PAGE ASSIGNMENTS REPORT\n";
$report .= "===================================\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

$report .= "SUMMARY:\n";
$report .= "Found " . count($allVariants) . " unique 10K variants across " . count($indexPages) . " index pages\n\n";

$report .= "DETAILED LISTINGS:\n";
$report .= "==================\n\n";

foreach ($allVariants as $variantId => $occurrences) {
    $report .= "$variantId:\n";
    
    if (count($occurrences) > 1) {
        $report .= "  *** APPEARS ON MULTIPLE PAGES ***\n";
    }
    
    foreach ($occurrences as $occurrence) {
        $report .= "  - Page {$occurrence['page']} (from {$occurrence['index_file']})\n";
    }
    $report .= "\n";
}

// Check for duplicates
$report .= "MULTIPLE PAGE OCCURRENCES:\n";
$report .= "==========================\n";

$duplicateCount = 0;
foreach ($allVariants as $variantId => $occurrences) {
    if (count($occurrences) > 1) {
        $duplicateCount++;
        $pages = array_column($occurrences, 'page');
        $report .= "$variantId appears on pages: " . implode(', ', $pages) . "\n";
    }
}

if ($duplicateCount === 0) {
    $report .= "No 10K variants found on multiple pages.\n";
} else {
    $report .= "\nTotal variants appearing on multiple pages: $duplicateCount\n";
}

// Save report
file_put_contents('10k_variants_page_analysis.txt', $report);

echo "\n" . $report;
echo "\nReport saved to: 10k_variants_page_analysis.txt\n";
?>