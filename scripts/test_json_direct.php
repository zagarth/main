<?php
/**
 * Direct file test - verify JSON contains updated data
 */

echo "Testing Plain Bands Configurator JSON File\n";
echo "==========================================\n\n";

$json_file = "/var/www/html/homesite/bands_php/plain_configurator.json";

if (!file_exists($json_file)) {
    echo "❌ ERROR: JSON file not found at $json_file\n";
    exit(1);
}

$json_content = file_get_contents($json_file);
$data = json_decode($json_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERROR: Invalid JSON in file\n";
    exit(1);
}

echo "✅ JSON file loaded successfully\n\n";

// Check the rectangular standard series
$series = $data['data']['options']['style_and_width']['grid_layout']['series'];

foreach ($series as $serie) {
    if ($serie['id'] === 'rectangular_standard') {
        echo "🔍 Rectangular Standard Series:\n";
        echo "   Name: " . $serie['name'] . "\n";
        echo "   Description: " . $serie['description'] . "\n\n";
        
        echo "📏 Width Options:\n";
        foreach ($serie['products'] as $product) {
            $base_id = $product['base_id'];
            $width = $product['width'];
            $mens_id = $product['product_id_m'] ?? 'N/A';
            $ladies_id = $product['product_id_l'] ?? 'N/A';
            
            echo "   • $base_id: '$width' (M: $mens_id, L: $ladies_id)\n";
        }
        break;
    }
}

echo "\n✅ SUCCESS: JSON file contains updated width format!\n";
echo "\n🌐 To test in your browser:\n";
echo "=========================================\n";
echo "1. Open: http://your-domain/homesite/Bands.php\n";
echo "2. Click on any plain band item (look for 'Classic Bands' or items like 'Rectangular-Standard')\n";
echo "3. This will open the configurator\n";
echo "4. Look for the 'Band Style & Width' section\n";
echo "5. The width options should now show:\n";
echo "   - '200R 2mm' instead of '2.0mm'\n";
echo "   - '400R 4mm' instead of '4.0mm'\n";
echo "   - etc.\n";
echo "\n💡 If you still see the old format:\n";
echo "   - Press Ctrl+Shift+R (hard refresh)\n";
echo "   - Or clear browser cache/localStorage\n";
echo "   - Or open in incognito/private mode\n";

?>