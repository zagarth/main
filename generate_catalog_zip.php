<?php
/**
 * Generate Complete Catalog ZIP File
 * Creates a downloadable ZIP archive of all catalog PDFs
 */

// Increase execution time for large ZIP creation
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

$catalogDir = __DIR__ . '/Cadman_catalog/';
$zipFileName = 'Cadman_Complete_Catalog_' . date('Y-m-d') . '.zip';
$zipFilePath = __DIR__ . '/' . $zipFileName;

// Check if catalog directory exists
if (!is_dir($catalogDir)) {
    die("Error: Catalog directory not found at: {$catalogDir}");
}

// Create new ZIP archive
$zip = new ZipArchive();

if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Error: Unable to create ZIP file");
}

// Get all PDF files from catalog directory
$files = glob($catalogDir . '*.pdf');

if (empty($files)) {
    $zip->close();
    die("Error: No PDF files found in catalog directory");
}

// Sort files for organized ZIP structure
sort($files);

$addedCount = 0;
$failedFiles = [];

// Organize files by category
$indexes = [];
$contentPages = [];
$other = [];

foreach ($files as $filePath) {
    $fileName = basename($filePath);
    $fileNameNoExt = pathinfo($fileName, PATHINFO_FILENAME);
    
    // Categorize files
    if (strpos($fileNameNoExt, 'index') !== false) {
        $indexes[] = ['path' => $filePath, 'name' => $fileName];
    } elseif (preg_match('/page_?(\d+[a-z]*)/', $fileNameNoExt)) {
        $contentPages[] = ['path' => $filePath, 'name' => $fileName];
    } else {
        $other[] = ['path' => $filePath, 'name' => $fileName];
    }
}

// Add files to ZIP in organized folders
// 1. Add index files to "Indexes" folder
foreach ($indexes as $file) {
    $zipPath = 'Indexes/' . $file['name'];
    if ($zip->addFile($file['path'], $zipPath)) {
        $addedCount++;
    } else {
        $failedFiles[] = $file['name'];
    }
}

// 2. Add content pages to "Content_Pages" folder
foreach ($contentPages as $file) {
    $zipPath = 'Content_Pages/' . $file['name'];
    if ($zip->addFile($file['path'], $zipPath)) {
        $addedCount++;
    } else {
        $failedFiles[] = $file['name'];
    }
}

// 3. Add other files to "Special_Collections" folder
foreach ($other as $file) {
    $zipPath = 'Special_Collections/' . $file['name'];
    if ($zip->addFile($file['path'], $zipPath)) {
        $addedCount++;
    } else {
        $failedFiles[] = $file['name'];
    }
}

// Add a README file to the ZIP
$readme = "Cadman Manufacturing Complete Catalog\n";
$readme .= "======================================\n\n";
$readme .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$readme .= "Total Files: {$addedCount}\n\n";
$readme .= "Folder Structure:\n";
$readme .= "  - Indexes/           Product category indexes\n";
$readme .= "  - Content_Pages/     Main catalog pages (numbered)\n";
$readme .= "  - Special_Collections/ Celtic and special collections\n\n";
$readme .= "For more information, visit https://cadmanmfg.com\n";
$readme .= "Contact: info@cadmanmfg.com | (519) 688-2121\n";

$zip->addFromString('README.txt', $readme);

// Close the ZIP file
$zip->close();

// Get file size
$fileSize = filesize($zipFilePath);
$fileSizeMB = round($fileSize / (1024 * 1024), 2);

// Output results
echo "✅ Catalog ZIP created successfully!\n\n";
echo "File: {$zipFileName}\n";
echo "Location: {$zipFilePath}\n";
echo "Size: {$fileSizeMB} MB\n";
echo "Files added: {$addedCount}\n";
echo "  - Indexes: " . count($indexes) . "\n";
echo "  - Content Pages: " . count($contentPages) . "\n";
echo "  - Special Collections: " . count($other) . "\n";

if (!empty($failedFiles)) {
    echo "\n⚠️  Failed to add " . count($failedFiles) . " files:\n";
    foreach ($failedFiles as $failed) {
        echo "  - {$failed}\n";
    }
}

echo "\n📦 ZIP file ready for download!\n";
?>
