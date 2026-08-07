<?php
/**
 * Generate a list of remaining retailers for manual geocoding
 */

$retailers = json_decode(file_get_contents('retailers.json'), true);
$remaining = [];

foreach ($retailers as $retailer) {
    if ($retailer['lat'] == 50 && $retailer['lng'] == -100) {
        $remaining[] = $retailer;
    }
}

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>Manual Geocoding Helper - Remaining Retailers</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".retailer { border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9; }\n";
echo ".retailer h3 { margin: 0 0 10px 0; color: #333; }\n";
echo ".address { color: #666; margin: 5px 0; }\n";
echo ".search-link { background: #4CAF50; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block; }\n";
echo ".coords { margin: 10px 0; }\n";
echo ".coords input { width: 100px; margin: 0 5px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>Manual Geocoding Helper</h1>\n";
echo "<p>Remaining retailers to geocode: " . count($remaining) . "</p>\n";
echo "<p>For each retailer, search online and enter the coordinates if found.</p>\n";

$count = 0;
foreach ($remaining as $retailer) {
    $count++;
    echo "<div class='retailer'>\n";
    echo "<h3>{$count}. {$retailer['name']}</h3>\n";
    echo "<div class='address'><strong>Street:</strong> {$retailer['street']}</div>\n";
    echo "<div class='address'><strong>City:</strong> {$retailer['city']}</div>\n";
    echo "<div class='address'><strong>Province:</strong> {$retailer['province']}</div>\n";
    echo "<div class='address'><strong>Postal:</strong> {$retailer['postal_code']}</div>\n";
    
    // Create search links
    $businessName = urlencode($retailer['name']);
    $fullAddress = urlencode($retailer['address']);
    
    echo "<div style='margin: 10px 0;'>\n";
    echo "<a href='https://www.google.com/search?q={$businessName}' target='_blank' class='search-link'>Google Search Business</a>\n";
    echo "<a href='https://www.google.com/maps/search/{$fullAddress}' target='_blank' class='search-link'>Google Maps</a>\n";
    echo "<a href='https://www.yellowpages.ca/search/si/1/{$businessName}' target='_blank' class='search-link'>Yellow Pages</a>\n";
    echo "</div>\n";
    
    echo "<div class='coords'>\n";
    echo "<strong>Coordinates:</strong> \n";
    echo "Lat: <input type='text' id='lat_{$retailer['ID']}' placeholder='Enter latitude'> \n";
    echo "Lng: <input type='text' id='lng_{$retailer['ID']}' placeholder='Enter longitude'> \n";
    echo "<button onclick=\"updateCoords('{$retailer['ID']}')\">Update</button>\n";
    echo "</div>\n";
    
    echo "</div>\n";
}

echo "<script>\n";
echo "function updateCoords(id) {\n";
echo "  const lat = document.getElementById('lat_' + id).value;\n";
echo "  const lng = document.getElementById('lng_' + id).value;\n";
echo "  if (lat && lng) {\n";
echo "    fetch('update_coords.php', {\n";
echo "      method: 'POST',\n";
echo "      headers: {'Content-Type': 'application/json'},\n";
echo "      body: JSON.stringify({id: id, lat: lat, lng: lng})\n";
echo "    }).then(response => response.text()).then(data => {\n";
echo "      alert('Updated coordinates for ' + id);\n";
echo "    });\n";
echo "  }\n";
echo "}\n";
echo "</script>\n";

echo "</body>\n</html>\n";
?>
