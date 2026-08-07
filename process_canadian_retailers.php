<?php
/**
 * Simple Canadian Retailer Processor
 * Parse and create XML directly
 */

// Parse the retailer info file
$content = file_get_contents('list_retailers_info');
$lines = explode("\n", $content);

// Initialize XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;
$root = $xml->createElement('retailers');
$xml->appendChild($root);

$retailers = [];
$count = 0;

// Parse retailers
$i = 0;
while ($i < count($lines)) {
    $line = trim($lines[$i]);
    if (empty($line)) {
        $i++;
        continue;
    }
    
    // Check if this looks like a store name
    if (preg_match('/\b(jewel|gold|diamond|watch|gem|ltd|limited|inc)\b/i', $line) && 
        !preg_match('/^\d+/', $line) && 
        !preg_match('/\([0-9]{3}\)/', $line)) {
        
        $retailer = ['name' => $line];
        $i++;
        
        // Get street address
        if ($i < count($lines) && !empty(trim($lines[$i]))) {
            $retailer['street'] = trim($lines[$i]);
            $i++;
        }
        
        // Get city/province/postal
        if ($i < count($lines) && !empty(trim($lines[$i]))) {
            $addressLine = trim($lines[$i]);
            if (preg_match('/([A-Z]\d[A-Z]\s*\d[A-Z]\d)/', $addressLine, $matches)) {
                $postalCode = $matches[1];
                $remaining = trim(str_replace($matches[1], '', $addressLine));
                $parts = explode(',', $remaining);
                $retailer['city'] = isset($parts[0]) ? trim($parts[0]) : '';
                $retailer['province'] = isset($parts[1]) ? trim($parts[1]) : '';
                $retailer['postal_code'] = $postalCode;
            } else {
                $parts = explode(',', $addressLine);
                $retailer['city'] = isset($parts[0]) ? trim($parts[0]) : '';
                $retailer['province'] = isset($parts[1]) ? trim($parts[1]) : '';
                $retailer['postal_code'] = '';
            }
            $i++;
        }
        
        // Get phone
        if ($i < count($lines) && preg_match('/\([0-9]{3}\)/', trim($lines[$i]))) {
            $retailer['phone'] = trim($lines[$i]);
            $i++;
        } else {
            $retailer['phone'] = '';
        }
        
        // Only add if we have meaningful data
        if (!empty($retailer['name']) && !empty($retailer['city'])) {
            $retailers[] = $retailer;
            $count++;
        }
    } else {
        $i++;
    }
}

echo "Parsed $count Canadian retailers\n";

// Create XML entries
foreach ($retailers as $index => $retailer) {
    $retailerElement = $xml->createElement('retailer');
    $retailerElement->setAttribute('id', 'can_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT));
    
    // Basic info
    $retailerElement->appendChild($xml->createElement('name', htmlspecialchars($retailer['name'])));
    
    // Address
    $address = $xml->createElement('address');
    $address->appendChild($xml->createElement('street', htmlspecialchars($retailer['street'] ?? '')));
    $address->appendChild($xml->createElement('city', htmlspecialchars($retailer['city'] ?? '')));
    $address->appendChild($xml->createElement('state', htmlspecialchars($retailer['province'] ?? '')));
    $address->appendChild($xml->createElement('postal_code', htmlspecialchars($retailer['postal_code'] ?? '')));
    $address->appendChild($xml->createElement('country', 'Canada'));
    $retailerElement->appendChild($address);
    
    // Contact
    $contact = $xml->createElement('contact');
    $phone = isset($retailer['phone']) ? preg_replace('/[^0-9]/', '', $retailer['phone']) : '';
    if (strlen($phone) == 10) {
        $phone = '+1-' . substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
    }
    $contact->appendChild($xml->createElement('phone', htmlspecialchars($phone)));
    $contact->appendChild($xml->createElement('email', ''));
    $contact->appendChild($xml->createElement('website', ''));
    $retailerElement->appendChild($contact);
    
    // Location (placeholder coordinates)
    $location = $xml->createElement('location');
    $location->appendChild($xml->createElement('latitude', '50.0'));
    $location->appendChild($xml->createElement('longitude', '-100.0'));
    $retailerElement->appendChild($location);
    
    // Hours
    $hours = $xml->createElement('hours');
    $defaultHours = [
        'monday' => '9:00 AM - 6:00 PM',
        'tuesday' => '9:00 AM - 6:00 PM',
        'wednesday' => '9:00 AM - 6:00 PM',
        'thursday' => '9:00 AM - 6:00 PM',
        'friday' => '9:00 AM - 6:00 PM',
        'saturday' => '10:00 AM - 4:00 PM',
        'sunday' => 'Closed'
    ];
    foreach ($defaultHours as $day => $time) {
        $hours->appendChild($xml->createElement($day, $time));
    }
    $retailerElement->appendChild($hours);
    
    // Specialties
    $specialties = $xml->createElement('specialties');
    $defaultSpecialties = ['Wedding Bands', 'Custom Jewelry', 'Engagement Rings'];
    foreach ($defaultSpecialties as $specialty) {
        $specialties->appendChild($xml->createElement('specialty', $specialty));
    }
    $retailerElement->appendChild($specialties);
    
    // Services
    $services = $xml->createElement('services');
    $defaultServices = ['Custom Design', 'Repair Services', 'Jewelry Cleaning'];
    foreach ($defaultServices as $service) {
        $services->appendChild($xml->createElement('service', $service));
    }
    $retailerElement->appendChild($services);
    
    // Status
    $retailerElement->appendChild($xml->createElement('status', 'active'));
    $retailerElement->appendChild($xml->createElement('added_date', date('Y-m-d')));
    
    $root->appendChild($retailerElement);
}

// Save XML
$xml->save('canadian_retailers.xml');
echo "Saved $count retailers to canadian_retailers.xml\n";

// Create JSON
$jsonRetailers = [];
foreach ($retailers as $index => $retailer) {
    $jsonRetailers[] = [
        'ID' => 'can_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
        'name' => $retailer['name'],
        'street' => $retailer['street'] ?? '',
        'city' => $retailer['city'] ?? '',
        'state' => $retailer['province'] ?? '',
        'postal_code' => $retailer['postal_code'] ?? '',
        'country' => 'Canada',
        'phone' => isset($retailer['phone']) ? $retailer['phone'] : '',
        'email' => '',
        'website' => '',
        'lat' => 50.0,  // Placeholder
        'lng' => -100.0, // Placeholder
        'specialties' => ['Wedding Bands', 'Custom Jewelry', 'Engagement Rings'],
        'services' => ['Custom Design', 'Repair Services', 'Jewelry Cleaning'],
        'hours' => [
            'monday' => '9:00 AM - 6:00 PM',
            'tuesday' => '9:00 AM - 6:00 PM',
            'wednesday' => '9:00 AM - 6:00 PM',
            'thursday' => '9:00 AM - 6:00 PM',
            'friday' => '9:00 AM - 6:00 PM',
            'saturday' => '10:00 AM - 4:00 PM',
            'sunday' => 'Closed'
        ]
    ];
}

file_put_contents('canadian_retailers.json', json_encode($jsonRetailers, JSON_PRETTY_PRINT));
echo "Saved $count retailers to canadian_retailers.json\n";

echo "\nFirst 5 retailers:\n";
for ($i = 0; $i < min(5, count($retailers)); $i++) {
    $r = $retailers[$i];
    echo "- {$r['name']} ({$r['city']}, {$r['province']})\n";
}

echo "\nProcess complete!\n";
?>
