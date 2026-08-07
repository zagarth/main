<?php
/**
 * Cleanup Temporary Invoices
 * Deletes invoice files from temp_invoices directory
 */

header('Content-Type: application/json');

$tempDir = __DIR__ . '/temp_invoices';

if (!is_dir($tempDir)) {
    echo json_encode(['success' => false, 'error' => 'Temp directory does not exist']);
    exit;
}

$deleteAll = isset($_GET['all']) && $_GET['all'] === 'true';
$olderThanHours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;

$deleted = 0;
$failed = 0;
$total = 0;

$files = glob($tempDir . '/invoice_*.pdf');
$total = count($files);

foreach ($files as $file) {
    if ($deleteAll) {
        // Delete all files
        if (unlink($file)) {
            $deleted++;
        } else {
            $failed++;
        }
    } else {
        // Delete files older than specified hours
        $fileAge = time() - filemtime($file);
        $ageHours = $fileAge / 3600;
        
        if ($ageHours > $olderThanHours) {
            if (unlink($file)) {
                $deleted++;
            } else {
                $failed++;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'failed' => $failed,
    'total' => $total,
    'remaining' => $total - $deleted - $failed
]);
