<?php
/**
 * Import Clients from JSON to Database
 * Reads canadian_retailers.json and imports into clients table
 */

// Database configuration
$db_config = [
    'host' => 'localhost',
    'database' => 'CadmanClients',
    'username' => 'cadman_admin',
    'password' => 'Admin2025!Cadman'
];

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to database successfully\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Read JSON file
$json_file = __DIR__ . '/../canadian_retailers.json';
if (!file_exists($json_file)) {
    die("❌ JSON file not found: $json_file\n");
}

$json_data = file_get_contents($json_file);
$retailers = json_decode($json_data, true);

if (!$retailers) {
    die("❌ Failed to parse JSON file\n");
}

echo "📊 Found " . count($retailers) . " retailers to import\n\n";

// Prepare insert statement
$sql = "INSERT INTO clients (
    business_name, address, city, province, postal_code, country,
    phone, email, website, latitude, longitude, client_type, status, notes
) VALUES (
    :business_name, :address, :city, :province, :postal_code, :country,
    :phone, :email, :website, :latitude, :longitude, :client_type, :status, :notes
)";

$stmt = $pdo->prepare($sql);

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($retailers as $retailer) {
    try {
        // Build address from street
        $address = $retailer['street'] ?? '';
        
        // Build notes from specialties and services
        $notes = [];
        if (!empty($retailer['specialties'])) {
            $notes[] = 'Specialties: ' . implode(', ', $retailer['specialties']);
        }
        if (!empty($retailer['services'])) {
            $notes[] = 'Services: ' . implode(', ', $retailer['services']);
        }
        $notes_text = implode(' | ', $notes);
        
        // Execute insert
        $stmt->execute([
            ':business_name' => $retailer['name'] ?? 'Unknown',
            ':address' => $address,
            ':city' => $retailer['city'] ?? '',
            ':province' => $retailer['state'] ?? '',
            ':postal_code' => $retailer['postal_code'] ?? '',
            ':country' => $retailer['country'] ?? 'Canada',
            ':phone' => $retailer['phone'] ?? '',
            ':email' => $retailer['email'] ?? '',
            ':website' => $retailer['website'] ?? '',
            ':latitude' => $retailer['lat'] ?? null,
            ':longitude' => $retailer['lng'] ?? null,
            ':client_type' => 'Retailer',
            ':status' => 'Active',
            ':notes' => $notes_text
        ]);
        
        $imported++;
        echo "✓ Imported: {$retailer['name']}\n";
        
    } catch (PDOException $e) {
        $errors++;
        echo "✗ Error importing {$retailer['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📈 Import Summary:\n";
echo "   ✅ Successfully imported: $imported\n";
echo "   ⏭️  Skipped: $skipped\n";
echo "   ❌ Errors: $errors\n";
echo str_repeat("=", 50) . "\n";
?>
