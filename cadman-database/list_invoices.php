<?php
/**
 * List Temporary Invoice Files
 */

header('Content-Type: application/json');

$tempDir = __DIR__ . '/temp_invoices';

if (!is_dir($tempDir)) {
    echo json_encode(['success' => false, 'error' => 'Temp directory does not exist', 'files' => []]);
    exit;
}

$files = glob($tempDir . '/invoice_*.pdf');
$fileList = [];
$totalSize = 0;

foreach ($files as $filepath) {
    $filename = basename($filepath);
    $filesize = filesize($filepath);
    $totalSize += $filesize;
    $filemtime = filemtime($filepath);
    $ageSeconds = time() - $filemtime;
    
    // Format age
    if ($ageSeconds < 60) {
        $ageStr = $ageSeconds . 's ago';
    } elseif ($ageSeconds < 3600) {
        $ageStr = floor($ageSeconds / 60) . 'm ago';
    } elseif ($ageSeconds < 86400) {
        $ageStr = floor($ageSeconds / 3600) . 'h ago';
    } else {
        $ageStr = floor($ageSeconds / 86400) . 'd ago';
    }
    
    $fileList[] = [
        'name' => $filename,
        'size' => $filesize,
        'sizeFormatted' => formatBytes($filesize),
        'modified' => date('Y-m-d H:i:s', $filemtime),
        'age' => $ageStr,
        'ageSeconds' => $ageSeconds
    ];
}

// Sort by newest first
usort($fileList, function($a, $b) {
    return $b['ageSeconds'] <=> $a['ageSeconds'];
});

echo json_encode([
    'success' => true,
    'files' => $fileList,
    'count' => count($fileList),
    'totalSize' => formatBytes($totalSize)
]);

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
