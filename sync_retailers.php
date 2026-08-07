<?php
/**
 * Simple Retailer Sync Script - CLI version
 * Syncs all retailers from JSON to XML
 */

echo "🔄 Syncing Retailers: JSON → XML\n";
echo "================================\n\n";

try {
    // File paths
    $jsonPath = 'retailers.json';
    $secureJsonPath = '/var/www/data/retailers.json';
    $xmlPath = 'retailers.xml';
    
    // Find JSON file
    $dataPath = file_exists($secureJsonPath) ? $secureJsonPath : $jsonPath;
    
    if (!file_exists($dataPath)) {
        throw new Exception("JSON file not found");
    }
    
    echo "📁 Loading JSON data from: $dataPath\n";
    
    // Load JSON data
    $jsonData = json_decode(file_get_contents($dataPath), true);
    if ($jsonData === null) {
        throw new Exception("Failed to parse JSON file");
    }
    
    echo "✅ Loaded " . count($jsonData) . " retailers from JSON\n\n";
    
    // Create new XML
    echo "🆕 Creating new XML file: $xmlPath\n";
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><retailers></retailers>');
    
    // Add all JSON retailers to XML
    echo "📝 Adding retailers to XML...\n";
    foreach ($jsonData as $index => $retailer) {
        $retailerElement = $xml->addChild('retailer');
        
        // Use array index + 1 as ID
        $id = $index + 1;
        $retailerElement->addAttribute('id', $id);
        
        // Add all fields with proper escaping
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
        
        if ($index % 50 == 0) {
            echo "   Processed " . ($index + 1) . " retailers...\n";
        }
    }
    
    echo "\n💾 Saving XML file with " . count($jsonData) . " retailers...\n";
    
    // Format and save XML
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    
    if ($dom->save($xmlPath)) {
        echo "✅ Successfully saved XML file: $xmlPath\n";
        
        // Verify the saved file
        $verifyXml = simplexml_load_file($xmlPath);
        if ($verifyXml) {
            echo "✅ Verification successful: XML file contains " . count($verifyXml->retailer) . " retailers\n";
            
            // Show statistics
            $geocoded = 0;
            $missing_coords = 0;
            
            foreach ($verifyXml->retailer as $retailer) {
                $lat = (float)$retailer->lat;
                $lng = (float)$retailer->lng;
                
                if ($lat && $lng && !($lat == 50 && $lng == -100)) {
                    $geocoded++;
                } else {
                    $missing_coords++;
                }
            }
            
            echo "\n📈 Statistics:\n";
            echo "   • Total retailers: " . count($verifyXml->retailer) . "\n";
            echo "   • Geocoded: $geocoded\n";
            echo "   • Missing coordinates: $missing_coords\n";
            echo "   • Progress: " . round(($geocoded / count($verifyXml->retailer)) * 100, 1) . "%\n";
            
        } else {
            echo "⚠️ Warning: Could not verify XML file\n";
        }
        
    } else {
        throw new Exception("Failed to save XML file");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Sync completed successfully!\n";
echo "Now both JSON and XML files contain the same retailer data.\n";
?>