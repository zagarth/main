<?php
// Test the getUnifiedBaseName function directly

function getUnifiedBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    // Remove various variant patterns
    $name = preg_replace('/_alt\d*$/', '', $name);
    $name = preg_replace('/-alt\d*$/', '', $name);
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    // Remove M/L gender suffixes to match bands grouping
    $name = preg_replace('/[ML]$/', '', $name);
    return $name;
}

echo "Testing getUnifiedBaseName function:\n";
echo "5296M.png -> '" . getUnifiedBaseName('5296M.png') . "'\n";
echo "5296L.png -> '" . getUnifiedBaseName('5296L.png') . "'\n";
echo "5296.png -> '" . getUnifiedBaseName('5296.png') . "'\n";
echo "5310M_alt1.png -> '" . getUnifiedBaseName('5310M_alt1.png') . "'\n";

// Check what files exist in celtic directory
echo "\nFiles in celtic directory:\n";
$celticPath = '/var/www/html/homesite/bands_php/images/celtic';
if (is_dir($celticPath)) {
    $files = scandir($celticPath);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
            $baseName = getUnifiedBaseName($file);
            echo "File: $file -> Base: '$baseName'\n";
        }
    }
} else {
    echo "Celtic directory not found\n";
}
?>