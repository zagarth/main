<?php
/**
 * Parse Retailer Data from list_retailers_info
 * Extracts retailer information and adds to XML database
 */

require_once 'retailer_manager.php';

class RetailerParser {
    public $manager;
    private $geocodingApiKey = null; // You would need a Google Geocoding API key for coordinates
    
    public function __construct() {
        $this->manager = new RetailerManager();
    }
    
    /**
     * Parse the retailer info file and extract data
     */
    public function parseRetailerFile($filename = 'list_retailers_info') {
        if (!file_exists($filename)) {
            echo "Error: File $filename not found\n";
            return false;
        }
        
        $content = file_get_contents($filename);
        $retailers = $this->extractRetailers($content);
        
        echo "Parsed " . count($retailers) . " retailers from file\n";
        return $retailers;
    }
    
    /**
     * Extract retailer data from the text content
     */
    private function extractRetailers($content) {
        $retailers = [];
        $lines = explode("\n", $content);
        
        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                $i++;
                continue;
            }
            
            // Each retailer entry consists of:
            // 1. Store name
            // 2. Street address
            // 3. City, Province, Postal Code
            // 4. Phone number
            
            if ($this->looksLikeStoreName($line)) {
                $retailer = ['name' => $line];
                $i++;
                
                // Get street address (next line)
                if ($i < count($lines)) {
                    $retailer['street'] = trim($lines[$i]);
                    $i++;
                }
                
                // Get city/province/postal (next line)
                if ($i < count($lines)) {
                    $address = $this->parseCanadianAddress(trim($lines[$i]));
                    $retailer = array_merge($retailer, $address);
                    $i++;
                }
                
                // Get phone (next line)
                if ($i < count($lines)) {
                    $phone = trim($lines[$i]);
                    if ($this->isPhoneNumber($phone)) {
                        $retailer['phone'] = $phone;
                        $i++;
                    } else {
                        $retailer['phone'] = '';
                    }
                }
                
                // Complete the retailer data
                $retailers[] = $this->completeRetailerData($retailer);
            } else {
                $i++;
            }
        }
        
        return $retailers;
    }
    
    /**
     * Check if a line looks like a store name
     */
    private function looksLikeStoreName($line) {
        // Store names typically:
        // - Don't start with numbers
        // - Contain words like "Jewel", "Gold", etc.
        // - Don't look like addresses or phone numbers
        // - Don't contain postal codes
        
        if ($this->isPhoneNumber($line)) return false;
        if (preg_match('/[A-Z]\d[A-Z]\s*\d[A-Z]\d/', $line)) return false; // Has postal code
        if (preg_match('/^\d+\s/', $line)) return false; // Starts with street number
        
        // Look for jewelry-related words or "Ltd", "Limited", etc.
        $jewelryWords = ['jewel', 'gold', 'diamond', 'gem', 'watch', 'ring', 'ltd', 'limited', 'inc'];
        $lowerLine = strtolower($line);
        
        foreach ($jewelryWords as $word) {
            if (strpos($lowerLine, $word) !== false) {
                return true;
            }
        }
        
        // If it contains common business suffixes, it's likely a store name
        if (preg_match('/\b(ltd|limited|inc|corp|company|jewel|gold|diamond)\b/i', $line)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a line is a phone number
     */
    private function isPhoneNumber($line) {
        return preg_match('/^\(?\d{3}\)?\s*[-\.\s]?\d{3}[-\.\s]?\d{4}$/', $line);
    }
    
    /**
     * Parse Canadian address format: City, Province, Postal Code
     */
    private function parseCanadianAddress($addressLine) {
        $city = '';
        $province = '';
        $postalCode = '';
        
        // Extract postal code first (Canadian format: A1A 1A1)
        if (preg_match('/([A-Z]\d[A-Z]\s*\d[A-Z]\d)/', $addressLine, $matches)) {
            $postalCode = $matches[1];
            $remaining = trim(str_replace($matches[1], '', $addressLine));
            
            // Split remaining by comma
            $parts = explode(',', $remaining);
            if (count($parts) >= 2) {
                $city = trim($parts[0]);
                $province = trim($parts[1]);
            } else if (count($parts) == 1) {
                $city = trim($parts[0]);
            }
        } else {
            // No postal code found, try to split by comma
            $parts = explode(',', $addressLine);
            if (count($parts) >= 2) {
                $city = trim($parts[0]);
                $province = trim($parts[1]);
            }
        }
        
        return [
            'city' => $city,
            'province' => $province,
            'postal_code' => $postalCode
        ];
    }
    
    /**
     * Complete retailer data with defaults and formatting
     */
    private function completeRetailerData($retailer) {
        return [
            'id' => $this->generateId($retailer['name'] ?? 'unknown'),
            'name' => $retailer['name'] ?? '',
            'street' => $retailer['street'] ?? '',
            'city' => $retailer['city'] ?? '',
            'state' => $retailer['province'] ?? '',
            'postal_code' => $retailer['postal_code'] ?? '',
            'country' => 'Canada',
            'phone' => $this->formatPhone($retailer['phone'] ?? ''),
            'email' => '', // Not available in source data
            'website' => '', // Not available in source data
            'latitude' => '0', // Would need geocoding
            'longitude' => '0', // Would need geocoding
            'specialties' => ['Wedding Bands', 'Custom Jewelry', 'Engagement Rings'], // Default
            'services' => ['Custom Design', 'Repair Services', 'Jewelry Cleaning'], // Default
            'hours' => $this->getDefaultHours()
        ];
    }
    
    /**
     * Generate a unique ID for the retailer
     */
    private function generateId($name) {
        $clean = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        return 'ret_' . strtolower(substr($clean, 0, 15)) . '_' . rand(100, 999);
    }
    
    /**
     * Format phone number
     */
    private function formatPhone($phone) {
        if (empty($phone)) return '';
        
        // Extract just the numbers
        $numbers = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as Canadian number
        if (strlen($numbers) == 10) {
            return '+1-' . substr($numbers, 0, 3) . '-' . substr($numbers, 3, 3) . '-' . substr($numbers, 6, 4);
        }
        
        return $phone; // Return original if can't format
    }
    
    /**
     * Get default business hours
     */
    private function getDefaultHours() {
        return [
            'monday' => '9:00 AM - 6:00 PM',
            'tuesday' => '9:00 AM - 6:00 PM',
            'wednesday' => '9:00 AM - 6:00 PM',
            'thursday' => '9:00 AM - 6:00 PM',
            'friday' => '9:00 AM - 6:00 PM',
            'saturday' => '10:00 AM - 4:00 PM',
            'sunday' => 'Closed'
        ];
    }
    
    /**
     * Add all parsed retailers to the XML database
     */
    public function addRetailersToDatabase($retailers) {
        $count = 0;
        foreach ($retailers as $retailer) {
            if (!empty($retailer['name']) && !empty($retailer['city'])) {
                $this->manager->addRetailer($retailer);
                $count++;
                
                if ($count % 50 == 0) {
                    echo "Added $count retailers...\n";
                }
            }
        }
        
        echo "Added total of $count retailers to database\n";
        return $count;
    }
}

// Main execution
echo "Retailer Data Parser\n";
echo "===================\n\n";

$action = $argv[1] ?? 'parse';

switch ($action) {
    case 'parse':
        echo "Parsing retailer data from list_retailers_info...\n";
        $parser = new RetailerParser();
        $retailers = $parser->parseRetailerFile('list_retailers_info');
        
        if ($retailers) {
            echo "Adding retailers to XML database...\n";
            $count = $parser->addRetailersToDatabase($retailers);
            
            echo "Saving XML file...\n";
            $parser->manager->saveXML();
            
            echo "Exporting to JSON...\n";
            $parser->manager->exportToJSON();
            
            echo "Updating API script...\n";
            $parser->manager->updateGetRetailersScript();
            
            echo "\nProcess complete!\n";
            echo "Total retailers processed: $count\n";
        }
        break;
        
    case 'test':
        echo "Testing parser on first 10 entries...\n";
        $parser = new RetailerParser();
        $retailers = $parser->parseRetailerFile('list_retailers_info');
        
        if ($retailers) {
            $testRetailers = array_slice($retailers, 0, 10);
            foreach ($testRetailers as $retailer) {
                echo "Name: {$retailer['name']}\n";
                echo "Address: {$retailer['street']}, {$retailer['city']}, {$retailer['state']} {$retailer['postal_code']}\n";
                echo "Phone: {$retailer['phone']}\n";
                echo "---\n";
            }
        }
        break;
        
    default:
        echo "Usage: php parse_retailers.php [action]\n";
        echo "Actions:\n";
        echo "  parse - Parse and add all retailers to database\n";
        echo "  test  - Parse and display first 10 retailers without saving\n";
        break;
}
?>
