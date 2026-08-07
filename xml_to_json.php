<?php
/**
 * XML to JSON API endpoint
 * Reads retailer data from XML and returns clean JSON
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

try {
    // Load XML file
    $xmlFile = "retailers.xml";
    if (!file_exists($xmlFile)) {
        http_response_code(404);
        echo json_encode(["error" => "XML file not found"]);
        exit;
    }
    
    $xml = simplexml_load_file($xmlFile);
    if ($xml === false) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to parse XML"]);
        exit;
    }
    
    $retailers = [];
    
    foreach ($xml->retailer as $retailer) {
        // Build combined address string
        $addressParts = [];
        if (!empty((string)$retailer->address->street)) {
            $addressParts[] = (string)$retailer->address->street;
        }
        if (!empty((string)$retailer->address->city)) {
            $addressParts[] = (string)$retailer->address->city;
        }
        if (!empty((string)$retailer->address->state)) {
            $addressParts[] = (string)$retailer->address->state;
        }
        if (!empty((string)$retailer->address->postal_code)) {
            $addressParts[] = (string)$retailer->address->postal_code;
        }
        $combinedAddress = implode(', ', $addressParts);
        
        $data = [
            "ID" => (string)$retailer['id'],
            "name" => (string)$retailer->name,
            "address" => $combinedAddress, // Combined address for map display
            "street" => (string)$retailer->address->street,
            "city" => (string)$retailer->address->city,
            "state" => (string)$retailer->address->state,
            "province" => (string)$retailer->address->state, // Alias for province
            "postal_code" => (string)$retailer->address->postal_code,
            "country" => (string)$retailer->address->country,
            "phone" => (string)$retailer->contact->phone,
            "email" => (string)$retailer->contact->email,
            "website" => (string)$retailer->contact->website,
            "lat" => (float)$retailer->location->latitude,
            "lng" => (float)$retailer->location->longitude,
            "specialties" => [],
            "services" => [],
            "hours" => []
        ];
        
        // Parse specialties
        if (isset($retailer->specialties)) {
            foreach ($retailer->specialties->specialty as $specialty) {
                $data["specialties"][] = (string)$specialty;
            }
        }
        
        // Parse services
        if (isset($retailer->services)) {
            foreach ($retailer->services->service as $service) {
                $data["services"][] = (string)$service;
            }
        }
        
        // Parse hours
        if (isset($retailer->hours)) {
            foreach ($retailer->hours->children() as $day => $time) {
                $data["hours"][$day] = (string)$time;
            }
        }
        
        $retailers[] = $data;
    }
    
    echo json_encode($retailers, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}
?>
