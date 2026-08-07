<?php
/**
 * Sync Retailers from JSON to XML
 * This script updates the XML file with all data from the JSON file
 */

require_once 'auth.php';
requireAdmin();

echo "<!DOCTYPE html>\n<html><head><title>Retailer Sync</title></head><body>\n";
echo "<h1>Syncing Retailers: JSON → XML</h1>\n";
echo "<pre>\n";

try {
    // File paths
    $jsonPath = '../retailers.json';
    $secureJsonPath = '/var/www/data/retailers.json';
    $xmlPath = '../retailers.xml';
    
    // Find JSON file
    $dataPath = file_exists($secureJsonPath) ? $secureJsonPath : $jsonPath;
    
    if (!file_exists($dataPath)) {
        throw new Exception("JSON file not found at: $dataPath");
    }
    
    echo "📁 Loading JSON data from: $dataPath\n";
    
    // Load JSON data
    $jsonData = json_decode(file_get_contents($dataPath), true);
    if ($jsonData === null) {
        throw new Exception("Failed to parse JSON file");
    }
    
    echo "✅ Loaded " . count($jsonData) . " retailers from JSON\n\n";
    
    // Create or load XML
    if (file_exists($xmlPath)) {
        echo "📁 Loading existing XML file: $xmlPath\n";
        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            throw new Exception("Failed to parse existing XML file");
        }
        echo "✅ Loaded existing XML with " . count($xml->retailer) . " retailers\n\n";
    } else {
        echo "🆕 Creating new XML file: $xmlPath\n";
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><retailers></retailers>');
    }
    
    // Clear existing retailers in XML
    $existingCount = count($xml->retailer ?? []);
    if ($existingCount > 0) {
        echo "🗑️ Clearing $existingCount existing retailers from XML\n";
        unset($xml->retailer);
    }
    
    // Add all JSON retailers to XML
    echo "📝 Adding retailers to XML:\n";
    foreach ($jsonData as $index => $retailer) {
        $retailerElement = $xml->addChild('retailer');
        
        // Use array index + 1 as ID if no ID exists
        $id = $retailer['ID'] ?? $retailer['id'] ?? ($index + 1);
        $retailerElement->addAttribute('id', $id);
        
        // Add all fields
        $retailerElement->addChild('name', htmlspecialchars($retailer['name'] ?? '', ENT_XML1));
        $retailerElement->addChild('street', htmlspecialchars($retailer['address'] ?? $retailer['street'] ?? '', ENT_XML1));
        $retailerElement->addChild('city', htmlspecialchars($retailer['city'] ?? '', ENT_XML1));
        $retailerElement->addChild('province', htmlspecialchars($retailer['province'] ?? '', ENT_XML1));
        $retailerElement->addChild('postal_code', htmlspecialchars($retailer['postal_code'] ?? '', ENT_XML1));
        $retailerElement->addChild('country', htmlspecialchars($retailer['country'] ?? 'Canada', ENT_XML1));
        $retailerElement->addChild('phone', htmlspecialchars($retailer['phone'] ?? '', ENT_XML1));
        $retailerElement->addChild('email', htmlspecialchars($retailer['email'] ?? '', ENT_XML1));
        $retailerElement->addChild('website', htmlspecialchars($retailer['website'] ?? '', ENT_XML1));
        $retailerElement->addChild('lat', $retailer['lat'] ?? '50');
        $retailerElement->addChild('lng', $retailer['lng'] ?? '-100');
        
        echo "   ✓ {$retailer['name']} (ID: $id) - {$retailer['city']}, {$retailer['province']}\n";
    }
    
    echo "\n💾 Saving XML file with " . count($jsonData) . " retailers...\n";
    
    // Format and save XML
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    
    if ($dom->save($xmlPath)) {
        echo "✅ Successfully saved XML file: $xmlPath\n";
        echo "📊 Final XML contains " . count($jsonData) . " retailers\n\n";
        
        // Verify the saved file
        $verifyXml = simplexml_load_file($xmlPath);
        if ($verifyXml && count($verifyXml->retailer) === count($jsonData)) {
            echo "✅ Verification successful: XML file contains correct number of retailers\n";
            
            // Show some statistics
            $geocoded = 0;
            $missing_coords = 0;
            $provinces = [];
            
            foreach ($verifyXml->retailer as $retailer) {
                $lat = (float)$retailer->lat;
                $lng = (float)$retailer->lng;
                
                if ($lat && $lng && !($lat == 50 && $lng == -100)) {
                    $geocoded++;
                } else {
                    $missing_coords++;
                }
                
                $province = (string)$retailer->province;
                if ($province) {
                    $provinces[$province] = ($provinces[$province] ?? 0) + 1;
                }
            }
            
            echo "\n📈 Statistics:\n";
            echo "   • Total retailers: " . count($verifyXml->retailer) . "\n";
            echo "   • Geocoded: $geocoded\n";
            echo "   • Missing coordinates: $missing_coords\n";
            echo "   • Provinces/States: " . count($provinces) . "\n";
            echo "   • Top provinces: " . implode(', ', array_keys(array_slice($provinces, 0, 5, true))) . "\n";
            
        } else {
            echo "⚠️ Warning: Verification failed - XML may not have saved correctly\n";
        }
        
        // Log the action
        if (function_exists('logAdminAction')) {
            logAdminAction('RETAILERS_SYNCED_JSON_TO_XML', [
                'total_retailers' => count($jsonData),
                'source' => $dataPath,
                'destination' => $xmlPath
            ]);
        }
        
    } else {
        throw new Exception("Failed to save XML file: $xmlPath");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Sync completed successfully!\n";
echo "\n<a href='retailer_management.php'>→ Go to Retailer Management</a> | ";
echo "<a href='retailer_geocoding.php'>→ Go to Geocoding Tool</a> | ";
echo "<a href='index.php'>→ Back to Admin</a>\n";

echo "</pre>\n</body></html>";
?>