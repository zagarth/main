<?php
/**
 * Test script to verify the configurator API is serving updated data
 */

echo "Testing Plain Bands Configurator API\n";
echo "====================================\n\n";

// Test the API endpoint
$api_url = "http://localhost/homesite/api/get_configurator_config.php?collection=plain&_t=" . time();
echo "Testing URL: $api_url\n\n";

$response = @file_get_contents($api_url);

if ($response === false) {
    echo "❌ ERROR: Could not fetch API response\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERROR: Invalid JSON response\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
    exit(1);
}

echo "✅ API Response received successfully\n\n";

// Check if we have the expected structure
if (!isset($data['data']['options']['style_and_width']['grid_layout']['series'])) {
    echo "❌ ERROR: Expected data structure not found\n";
    print_r($data);
    exit(1);
}

$series = $data['data']['options']['style_and_width']['grid_layout']['series'];
echo "📊 Found " . count($series) . " series\n\n";

// Test specific width values for 200R series
$found_200r = false;
$found_updated_format = false;

foreach ($series as $serie) {
    if ($serie['id'] === 'rectangular_standard') {
        echo "🔍 Testing Rectangular Standard series:\n";
        foreach ($serie['products'] as $product) {
            if ($product['base_id'] === '200R') {
                $found_200r = true;
                echo "   Found 200R: width = '" . $product['width'] . "'\n";
                
                if ($product['width'] === '200R 2mm') {
                    $found_updated_format = true;
                    echo "   ✅ CORRECT: Shows new format '200R 2mm'\n";
                } else {
                    echo "   ❌ WRONG: Expected '200R 2mm', got '" . $product['width'] . "'\n";
                }
            }
            if ($product['base_id'] === '400R') {
                echo "   Found 400R: width = '" . $product['width'] . "'\n";
                if ($product['width'] === '400R 4mm') {
                    echo "   ✅ CORRECT: Shows new format '400R 4mm'\n";
                } else {
                    echo "   ❌ WRONG: Expected '400R 4mm', got '" . $product['width'] . "'\n";
                }
            }
        }
        break;
    }
}

echo "\n📝 Test Results:\n";
echo "================\n";

if ($found_200r && $found_updated_format) {
    echo "✅ SUCCESS: API is serving updated width format (Product ID + Width)\n";
    echo "✅ The configurator should now display '200R 2mm', '400R 4mm', etc.\n";
} else {
    echo "❌ FAILED: API is still serving old format\n";
    if (!$found_200r) {
        echo "   - Could not find 200R product\n";
    }
    if (!$found_updated_format) {
        echo "   - Width format not updated\n";
    }
}

echo "\n🌐 To test in browser:\n";
echo "1. Open: http://localhost/homesite/Bands.php\n";
echo "2. Click on any plain band (like 'Tiffany-Standard' or 'Rectangular-Standard')\n";
echo "3. Look at the width selection - should show 'ProductID Width' format\n";
echo "4. If not updated, clear browser cache and try again\n";

?>