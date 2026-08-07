<?php
/**
 * Convert JSON back to XML format
 */

$jsonData = file_get_contents('retailers.json');
$retailers = json_decode($jsonData, true);

$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$root = $xml->createElement('retailers');
$xml->appendChild($root);

foreach ($retailers as $retailerData) {
    $retailer = $xml->createElement('retailer');
    $retailer->setAttribute('id', $retailerData['ID']);
    
    // Name
    $name = $xml->createElement('name', htmlspecialchars($retailerData['name']));
    $retailer->appendChild($name);
    
    // Address
    $address = $xml->createElement('address');
    $address->appendChild($xml->createElement('street', htmlspecialchars($retailerData['street'])));
    $address->appendChild($xml->createElement('city', htmlspecialchars($retailerData['city'])));
    $address->appendChild($xml->createElement('state', htmlspecialchars($retailerData['state'])));
    $address->appendChild($xml->createElement('postal_code', htmlspecialchars($retailerData['postal_code'])));
    $address->appendChild($xml->createElement('country', htmlspecialchars($retailerData['country'])));
    $retailer->appendChild($address);
    
    // Contact
    $contact = $xml->createElement('contact');
    $contact->appendChild($xml->createElement('phone', htmlspecialchars($retailerData['phone'])));
    $contact->appendChild($xml->createElement('email', htmlspecialchars($retailerData['email'])));
    $contact->appendChild($xml->createElement('website', htmlspecialchars($retailerData['website'])));
    $retailer->appendChild($contact);
    
    // Location
    $location = $xml->createElement('location');
    $location->appendChild($xml->createElement('latitude', $retailerData['lat']));
    $location->appendChild($xml->createElement('longitude', $retailerData['lng']));
    $retailer->appendChild($location);
    
    // Specialties
    if (!empty($retailerData['specialties'])) {
        $specialties = $xml->createElement('specialties');
        foreach ($retailerData['specialties'] as $specialty) {
            $specialties->appendChild($xml->createElement('specialty', htmlspecialchars($specialty)));
        }
        $retailer->appendChild($specialties);
    }
    
    // Services
    if (!empty($retailerData['services'])) {
        $services = $xml->createElement('services');
        foreach ($retailerData['services'] as $service) {
            $services->appendChild($xml->createElement('service', htmlspecialchars($service)));
        }
        $retailer->appendChild($services);
    }
    
    // Hours
    if (!empty($retailerData['hours'])) {
        $hours = $xml->createElement('hours');
        foreach ($retailerData['hours'] as $day => $time) {
            $hours->appendChild($xml->createElement($day, htmlspecialchars($time)));
        }
        $retailer->appendChild($hours);
    }
    
    $root->appendChild($retailer);
}

$xml->save('retailers.xml');
echo "XML file created successfully with " . count($retailers) . " retailers\n";
?>
