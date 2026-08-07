<?php
/**
 * Import retailers from XML file to CadmanClients database
 */

require_once __DIR__ . '/../includes/db_config_encrypted.php';

echo "Starting XML import...\n";

// Load the XML file
$xmlFile = __DIR__ . '/../retailers.xml';
if (!file_exists($xmlFile)) {
    die("ERROR: retailers.xml not found at $xmlFile\n");
}

$xml = simplexml_load_file($xmlFile);
if (!$xml) {
    die("ERROR: Failed to parse XML file\n");
}

echo "XML file loaded successfully\n";
echo "Found " . count($xml->retailer) . " retailers in XML\n";

// Get admin connection for write operations
$pdo = getAdminConnection();

// Clear existing clients
echo "\nClearing existing clients...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE clients");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "Existing clients cleared\n";

// Prepare insert statement
$stmt = $pdo->prepare("
    INSERT INTO clients (
        business_name, contact_name, address, city, province, postal_code, 
        country, phone, email, website, latitude, longitude, 
        client_type, status
    ) VALUES (
        :business_name, :contact_name, :address, :city, :province, :postal_code,
        :country, :phone, :email, :website, :latitude, :longitude,
        :client_type, :status
    )
");

$imported = 0;
$errors = 0;

// Import each retailer
foreach ($xml->retailer as $retailer) {
    try {
        // Extract data from XML
        $data = [
            'business_name' => (string)$retailer->name ?: null,
            'contact_name' => null, // Not in XML
            'address' => (string)$retailer->street ?: null,
            'city' => (string)$retailer->city ?: null,
            'province' => (string)$retailer->province ?: null,
            'postal_code' => (string)$retailer->postal_code ?: null,
            'country' => (string)$retailer->country ?: 'Canada',
            'phone' => (string)$retailer->phone ?: null,
            'email' => (string)$retailer->email ?: null,
            'website' => (string)$retailer->website ?: null,
            'latitude' => !empty((string)$retailer->lat) ? (float)$retailer->lat : null,
            'longitude' => !empty((string)$retailer->lng) ? (float)$retailer->lng : null,
            'client_type' => 'Retailer',
            'status' => 'Active'
        ];
        
        $stmt->execute($data);
        $imported++;
        
        if ($imported % 50 == 0) {
            echo "Imported $imported retailers...\n";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "ERROR importing {$retailer->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== IMPORT COMPLETE ===\n";
echo "Successfully imported: $imported retailers\n";
echo "Errors: $errors\n";

// Verify import
$count = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
echo "Total clients in database: $count\n";

// Show sample
echo "\nSample of imported data:\n";
$sample = $pdo->query("SELECT client_id, business_name, city, province, country FROM clients LIMIT 5")->fetchAll();
foreach ($sample as $row) {
    echo "  [{$row['client_id']}] {$row['business_name']} - {$row['city']}, {$row['province']}, {$row['country']}\n";
}
