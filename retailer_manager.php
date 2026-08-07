<?php
/**
 * Retailer Data Fetcher and XML Manager
 * Fetches retailer data from cadmanmfg.com/retailers and manages local XML file
 */

class RetailerManager {
    private $xmlFile;
    private $retailers;
    private $dom;

    public function __construct($xmlFile = 'retailers.xml') {
        $this->xmlFile = $xmlFile;
        $this->loadXML();
    }

    private function loadXML() {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        
        if (file_exists($this->xmlFile)) {
            $this->dom->load($this->xmlFile);
        } else {
            // Create new XML structure
            $root = $this->dom->createElement('retailers');
            $this->dom->appendChild($root);
        }
    }

    /**
     * Fetch retailer data from the web (simulate the cadmanmfg.com data)
     */
    public function fetchRetailerData() {
        // Since we can't directly fetch from cadmanmfg.com in this context,
        // I'll provide a method to manually add sample retailer data
        // You can replace this with actual web scraping or API calls
        
        echo "Fetching retailer data...\n";
        
        // Sample retailer data structure (replace with actual data)
        $sampleRetailers = [
            [
                'id' => 'ret_001',
                'name' => 'Diamond Dreams Jewelry',
                'street' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'phone' => '+1-212-555-0123',
                'email' => 'info@diamonddreams.com',
                'website' => 'https://www.diamonddreams.com',
                'latitude' => '40.7128',
                'longitude' => '-74.0060',
                'specialties' => ['Wedding Bands', 'Engagement Rings', 'Custom Design'],
                'services' => ['Custom Design', 'Repair Services', 'Appraisals'],
                'hours' => [
                    'monday' => '9:00 AM - 6:00 PM',
                    'tuesday' => '9:00 AM - 6:00 PM',
                    'wednesday' => '9:00 AM - 6:00 PM',
                    'thursday' => '9:00 AM - 6:00 PM',
                    'friday' => '9:00 AM - 6:00 PM',
                    'saturday' => '10:00 AM - 4:00 PM',
                    'sunday' => 'Closed'
                ]
            ],
            [
                'id' => 'ret_002',
                'name' => 'Golden Gate Jewelers',
                'street' => '456 Market Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94102',
                'country' => 'USA',
                'phone' => '+1-415-555-0456',
                'email' => 'sales@goldengatejewelers.com',
                'website' => 'https://www.goldengatejewelers.com',
                'latitude' => '37.7749',
                'longitude' => '-122.4194',
                'specialties' => ['Celtic Jewelry', 'Wedding Bands', 'Corporate Gifts'],
                'services' => ['Custom Engraving', 'Jewelry Cleaning', 'Sizing'],
                'hours' => [
                    'monday' => '10:00 AM - 7:00 PM',
                    'tuesday' => '10:00 AM - 7:00 PM',
                    'wednesday' => '10:00 AM - 7:00 PM',
                    'thursday' => '10:00 AM - 8:00 PM',
                    'friday' => '10:00 AM - 8:00 PM',
                    'saturday' => '9:00 AM - 6:00 PM',
                    'sunday' => '12:00 PM - 5:00 PM'
                ]
            ],
            [
                'id' => 'ret_003',
                'name' => 'Windy City Gems',
                'street' => '789 Michigan Avenue',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60611',
                'country' => 'USA',
                'phone' => '+1-312-555-0789',
                'email' => 'contact@windycitygems.com',
                'website' => 'https://www.windycitygems.com',
                'latitude' => '41.8781',
                'longitude' => '-87.6298',
                'specialties' => ['Stoneset Jewelry', 'Family Collections', 'Accessories'],
                'services' => ['Custom Design', 'Repair Services', 'Consultation'],
                'hours' => [
                    'monday' => '9:00 AM - 6:00 PM',
                    'tuesday' => '9:00 AM - 6:00 PM',
                    'wednesday' => '9:00 AM - 6:00 PM',
                    'thursday' => '9:00 AM - 7:00 PM',
                    'friday' => '9:00 AM - 7:00 PM',
                    'saturday' => '10:00 AM - 5:00 PM',
                    'sunday' => 'Closed'
                ]
            ]
        ];

        return $sampleRetailers;
    }

    /**
     * Add a retailer to the XML file
     */
    public function addRetailer($retailerData) {
        $root = $this->dom->getElementsByTagName('retailers')->item(0);
        
        // Create retailer element
        $retailer = $this->dom->createElement('retailer');
        $retailer->setAttribute('id', $retailerData['id']);
        
        // Basic info
        $name = $this->dom->createElement('name', htmlspecialchars($retailerData['name']));
        $retailer->appendChild($name);
        
        // Address
        $address = $this->dom->createElement('address');
        $address->appendChild($this->dom->createElement('street', htmlspecialchars($retailerData['street'])));
        $address->appendChild($this->dom->createElement('city', htmlspecialchars($retailerData['city'])));
        $address->appendChild($this->dom->createElement('state', htmlspecialchars($retailerData['state'])));
        $address->appendChild($this->dom->createElement('postal_code', htmlspecialchars($retailerData['postal_code'])));
        $address->appendChild($this->dom->createElement('country', htmlspecialchars($retailerData['country'])));
        $retailer->appendChild($address);
        
        // Contact
        $contact = $this->dom->createElement('contact');
        $contact->appendChild($this->dom->createElement('phone', htmlspecialchars($retailerData['phone'])));
        $contact->appendChild($this->dom->createElement('email', htmlspecialchars($retailerData['email'])));
        $contact->appendChild($this->dom->createElement('website', htmlspecialchars($retailerData['website'])));
        $retailer->appendChild($contact);
        
        // Location coordinates
        $location = $this->dom->createElement('location');
        $location->appendChild($this->dom->createElement('latitude', $retailerData['latitude']));
        $location->appendChild($this->dom->createElement('longitude', $retailerData['longitude']));
        $retailer->appendChild($location);
        
        // Hours
        $hours = $this->dom->createElement('hours');
        foreach ($retailerData['hours'] as $day => $time) {
            $hours->appendChild($this->dom->createElement($day, htmlspecialchars($time)));
        }
        $retailer->appendChild($hours);
        
        // Specialties
        $specialties = $this->dom->createElement('specialties');
        foreach ($retailerData['specialties'] as $specialty) {
            $specialties->appendChild($this->dom->createElement('specialty', htmlspecialchars($specialty)));
        }
        $retailer->appendChild($specialties);
        
        // Services
        $services = $this->dom->createElement('services');
        foreach ($retailerData['services'] as $service) {
            $services->appendChild($this->dom->createElement('service', htmlspecialchars($service)));
        }
        $retailer->appendChild($services);
        
        // Metadata
        $retailer->appendChild($this->dom->createElement('status', 'active'));
        $retailer->appendChild($this->dom->createElement('added_date', date('Y-m-d')));
        
        $root->appendChild($retailer);
        
        echo "Added retailer: {$retailerData['name']}\n";
    }

    /**
     * Save the XML file
     */
    public function saveXML() {
        $result = $this->dom->save($this->xmlFile);
        if ($result) {
            echo "XML file saved successfully: {$this->xmlFile}\n";
        } else {
            echo "Error saving XML file\n";
        }
        return $result;
    }

    /**
     * Convert XML to JSON for JavaScript consumption
     */
    public function exportToJSON($jsonFile = 'retailers.json') {
        $retailers = [];
        $retailerNodes = $this->dom->getElementsByTagName('retailer');
        
        foreach ($retailerNodes as $retailerNode) {
            $retailer = [
                'ID' => $retailerNode->getAttribute('id'),
                'name' => $retailerNode->getElementsByTagName('name')->item(0)->nodeValue,
                'street' => $retailerNode->getElementsByTagName('street')->item(0)->nodeValue,
                'city' => $retailerNode->getElementsByTagName('city')->item(0)->nodeValue,
                'state' => $retailerNode->getElementsByTagName('state')->item(0)->nodeValue,
                'postal_code' => $retailerNode->getElementsByTagName('postal_code')->item(0)->nodeValue,
                'country' => $retailerNode->getElementsByTagName('country')->item(0)->nodeValue,
                'phone' => $retailerNode->getElementsByTagName('phone')->item(0)->nodeValue,
                'email' => $retailerNode->getElementsByTagName('email')->item(0)->nodeValue,
                'website' => $retailerNode->getElementsByTagName('website')->item(0)->nodeValue,
                'lat' => floatval($retailerNode->getElementsByTagName('latitude')->item(0)->nodeValue),
                'lng' => floatval($retailerNode->getElementsByTagName('longitude')->item(0)->nodeValue),
                'specialties' => [],
                'services' => [],
                'hours' => []
            ];
            
            // Get specialties
            $specialtyNodes = $retailerNode->getElementsByTagName('specialty');
            foreach ($specialtyNodes as $specialtyNode) {
                $retailer['specialties'][] = $specialtyNode->nodeValue;
            }
            
            // Get services
            $serviceNodes = $retailerNode->getElementsByTagName('service');
            foreach ($serviceNodes as $serviceNode) {
                $retailer['services'][] = $serviceNode->nodeValue;
            }
            
            // Get hours
            $hoursNode = $retailerNode->getElementsByTagName('hours')->item(0);
            if ($hoursNode) {
                foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                    $dayNode = $hoursNode->getElementsByTagName($day)->item(0);
                    if ($dayNode) {
                        $retailer['hours'][$day] = $dayNode->nodeValue;
                    }
                }
            }
            
            $retailers[] = $retailer;
        }
        
        $json = json_encode($retailers, JSON_PRETTY_PRINT);
        file_put_contents($jsonFile, $json);
        
        echo "Exported " . count($retailers) . " retailers to JSON: {$jsonFile}\n";
        return $retailers;
    }

    /**
     * List all retailers
     */
    public function listRetailers() {
        $retailerNodes = $this->dom->getElementsByTagName('retailer');
        echo "Total retailers: " . $retailerNodes->length . "\n\n";
        
        foreach ($retailerNodes as $retailerNode) {
            $id = $retailerNode->getAttribute('id');
            $name = $retailerNode->getElementsByTagName('name')->item(0)->nodeValue;
            $city = $retailerNode->getElementsByTagName('city')->item(0)->nodeValue;
            $state = $retailerNode->getElementsByTagName('state')->item(0)->nodeValue;
            echo "- $id: $name ($city, $state)\n";
        }
    }

    /**
     * Update get_retailers.php to serve JSON data
     */
    public function updateGetRetailersScript() {
        $script = '<?php
/**
 * Get Retailers API Endpoint
 * Returns retailer data in JSON format for the location search
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$retailersFile = "retailers.json";

if (!file_exists($retailersFile)) {
    http_response_code(404);
    echo json_encode(["error" => "Retailers data not found"]);
    exit;
}

$retailers = json_decode(file_get_contents($retailersFile), true);

if ($retailers === null) {
    http_response_code(500);
    echo json_encode(["error" => "Error reading retailers data"]);
    exit;
}

// Filter by query parameters if provided
$filtered = $retailers;

if (isset($_GET["state"]) && !empty($_GET["state"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return strtolower($retailer["state"]) === strtolower($_GET["state"]);
    });
}

if (isset($_GET["city"]) && !empty($_GET["city"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return stripos($retailer["city"], $_GET["city"]) !== false;
    });
}

if (isset($_GET["specialty"]) && !empty($_GET["specialty"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return in_array($_GET["specialty"], $retailer["specialties"]);
    });
}

echo json_encode(array_values($filtered));
?>';

        file_put_contents('get_retailers.php', $script);
        echo "Updated get_retailers.php script\n";
    }
}

// Main execution
if ($argc > 1) {
    $action = $argv[1];
} else {
    $action = 'help';
}

$manager = new RetailerManager();

switch ($action) {
    case 'fetch':
        echo "Fetching and adding retailer data...\n";
        $retailers = $manager->fetchRetailerData();
        foreach ($retailers as $retailer) {
            $manager->addRetailer($retailer);
        }
        $manager->saveXML();
        $manager->exportToJSON();
        $manager->updateGetRetailersScript();
        echo "Retailer data import complete!\n";
        break;
        
    case 'export':
        echo "Exporting retailers to JSON...\n";
        $manager->exportToJSON();
        $manager->updateGetRetailersScript();
        break;
        
    case 'list':
        $manager->listRetailers();
        break;
        
    case 'add':
        echo "Interactive retailer addition not implemented yet.\n";
        echo "Use the fetch command to add sample data.\n";
        break;
        
    default:
        echo "Retailer Data Manager\n";
        echo "====================\n\n";
        echo "Usage: php retailer_manager.php [action]\n\n";
        echo "Actions:\n";
        echo "  fetch  - Fetch sample retailer data and populate XML\n";
        echo "  export - Export XML data to JSON format\n";
        echo "  list   - List all retailers in the database\n";
        echo "  add    - Add a new retailer (interactive)\n";
        echo "\nExample:\n";
        echo "  php retailer_manager.php fetch\n";
        break;
}
?>
