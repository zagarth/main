<?php
/**
 * Merge all retailer data into the main database
 */

echo "Merging Canadian and US retailer data...\n";

// Load existing US retailers
$usRetailers = json_decode(file_get_contents('retailers.json'), true);
echo "Found " . count($usRetailers) . " US retailers\n";

// Load Canadian retailers
$canadianRetailers = json_decode(file_get_contents('canadian_retailers.json'), true);
echo "Found " . count($canadianRetailers) . " Canadian retailers\n";

// Combine all retailers
$allRetailers = array_merge($usRetailers, $canadianRetailers);
echo "Total retailers: " . count($allRetailers) . "\n";

// Save combined JSON
file_put_contents('retailers.json', json_encode($allRetailers, JSON_PRETTY_PRINT));
echo "Saved combined retailers to retailers.json\n";

// Create combined XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;
$root = $xml->createElement('retailers');
$xml->appendChild($root);

foreach ($allRetailers as $retailer) {
    $retailerElement = $xml->createElement('retailer');
    $retailerElement->setAttribute('id', $retailer['ID']);
    
    // Basic info
    $retailerElement->appendChild($xml->createElement('name', htmlspecialchars($retailer['name'])));
    
    // Address
    $address = $xml->createElement('address');
    $address->appendChild($xml->createElement('street', htmlspecialchars($retailer['street'])));
    $address->appendChild($xml->createElement('city', htmlspecialchars($retailer['city'])));
    $address->appendChild($xml->createElement('state', htmlspecialchars($retailer['state'])));
    $address->appendChild($xml->createElement('postal_code', htmlspecialchars($retailer['postal_code'])));
    $address->appendChild($xml->createElement('country', htmlspecialchars($retailer['country'])));
    $retailerElement->appendChild($address);
    
    // Contact
    $contact = $xml->createElement('contact');
    $contact->appendChild($xml->createElement('phone', htmlspecialchars($retailer['phone'])));
    $contact->appendChild($xml->createElement('email', htmlspecialchars($retailer['email'])));
    $contact->appendChild($xml->createElement('website', htmlspecialchars($retailer['website'])));
    $retailerElement->appendChild($contact);
    
    // Location
    $location = $xml->createElement('location');
    $location->appendChild($xml->createElement('latitude', $retailer['lat']));
    $location->appendChild($xml->createElement('longitude', $retailer['lng']));
    $retailerElement->appendChild($location);
    
    // Hours
    $hours = $xml->createElement('hours');
    foreach ($retailer['hours'] as $day => $time) {
        $hours->appendChild($xml->createElement($day, htmlspecialchars($time)));
    }
    $retailerElement->appendChild($hours);
    
    // Specialties
    $specialties = $xml->createElement('specialties');
    foreach ($retailer['specialties'] as $specialty) {
        $specialties->appendChild($xml->createElement('specialty', htmlspecialchars($specialty)));
    }
    $retailerElement->appendChild($specialties);
    
    // Services
    $services = $xml->createElement('services');
    foreach ($retailer['services'] as $service) {
        $services->appendChild($xml->createElement('service', htmlspecialchars($service)));
    }
    $retailerElement->appendChild($services);
    
    // Status
    $retailerElement->appendChild($xml->createElement('status', 'active'));
    $retailerElement->appendChild($xml->createElement('added_date', date('Y-m-d')));
    
    $root->appendChild($retailerElement);
}

$xml->save('retailers.xml');
echo "Saved combined retailers to retailers.xml\n";

// Show breakdown by country
$usBased = array_filter($allRetailers, function($r) { return $r['country'] === 'USA'; });
$canadaBased = array_filter($allRetailers, function($r) { return $r['country'] === 'Canada'; });

echo "\nBreakdown by country:\n";
echo "- USA: " . count($usBased) . " retailers\n";
echo "- Canada: " . count($canadaBased) . " retailers\n";

// Test the API
echo "\nTesting API endpoint...\n";
$testRetailers = json_decode(file_get_contents('http://localhost/homesite/get_retailers.php') ?: 
                            shell_exec('php get_retailers.php'), true);
if ($testRetailers) {
    echo "API working! Returns " . count($testRetailers) . " retailers\n";
} else {
    echo "API test failed\n";
}

echo "\nProcess complete! All retailer data merged.\n";
?>
