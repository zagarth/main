<?php
// Quick test script to check if unified_detail.php can find band variants

include __DIR__ . '/unified_detail.php';

// Test the functions directly
echo "Testing getUnifiedBaseName function:\n";
echo "5296M.png -> " . getUnifiedBaseName('5296M.png') . "\n";
echo "5296L.png -> " . getUnifiedBaseName('5296L.png') . "\n";
echo "5296.png -> " . getUnifiedBaseName('5296.png') . "\n";

echo "\nTesting findItemVariants for Celtic product 5296:\n";
$config = [
    'path' => 'bands_php',
    'categories' => ['celtic', 'cultural', 'fancy', 'plain'],
    'categoryPath' => 'images/{category}'
];

$result = findItemVariants('bands', $config, '5296');
echo "Found " . count($result['variants']) . " variants\n";
echo "Category: " . $result['category'] . "\n";

if (!empty($result['variants'])) {
    foreach ($result['variants'] as $variant) {
        echo "- " . $variant['file'] . " in " . $variant['category'] . "\n";
    }
} else {
    echo "No variants found!\n";
}
?>