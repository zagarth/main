<?php
/**
 * Import AR-EXP01.csv Customer Data into Clients Table
 * Replaces existing clients with complete AR customer database
 */

require_once '../includes/db_config.php';

set_time_limit(300); // 5 minutes

$csvFile = __DIR__ . '/AR-EXP01.csv';

if (!file_exists($csvFile)) {
    die("Error: AR-EXP01.csv not found at: $csvFile\n");
}

echo "Starting AR customer import...\n";
echo "Backup location: backups/CadmanClients_backup_*.sql\n\n";

try {
    $pdo = getDBConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Clear existing clients (since AR is the master source)
    echo "Clearing existing clients...\n";
    $pdo->exec("DELETE FROM clients");
    $pdo->exec("ALTER TABLE clients AUTO_INCREMENT = 1");
    
    // Open CSV file
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("Could not open CSV file");
    }
    
    // Read header
    $header = fgetcsv($handle, 0, '|');
    
    // Prepare insert statement
    $sql = "INSERT INTO clients (
        customer_code, business_name, contact_name, address, city, province, 
        postal_code, country, phone, email, terms, discount_percent, price_level, 
        client_type, status, notes
    ) VALUES (
        :customer_code, :business_name, :contact_name, :address, :city, :province,
        :postal_code, :country, :phone, :email, :terms, :discount_percent, :price_level,
        'Retailer', :status, :notes
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    while (($row = fgetcsv($handle, 0, '|')) !== false) {
        if (count($row) < 13) {
            $skipped++;
            continue;
        }
        
        // Parse CSV fields
        // CUST|NAME|ADDR2|ADDR3|CITY-PROV|ZIP|PST|CONTACT|PHONE|TERMS|DISC|PRICE|EMAIL
        $customerCode = trim($row[0]);
        $name = trim($row[1]);
        $addr2 = trim($row[2]);
        $addr3 = trim($row[3]);
        $cityProv = trim($row[4]);
        $postalCode = trim($row[6]); // ZIP is at index 6, PST at 5
        $contact = trim($row[7]);
        $phone = trim($row[8]);
        $terms = trim($row[9]);
        $discount = floatval($row[10]);
        $priceLevel = intval($row[11]);
        $email = trim($row[12]);
        
        // Skip records with no name and no address
        if (empty($name) && empty($addr2) && empty($cityProv)) {
            $skipped++;
            continue;
        }
        
        // Clean up name (remove asterisks, extra spaces)
        $name = preg_replace('/\*+/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        // If still empty, use customer code
        if (empty($name)) {
            $name = $customerCode;
        }
        
        // Parse city and province from CITY-PROV field
        // Examples: "ESTEVAN, SASK.", "WINNIPEG, MAN", "EDMONTON, ALTA."
        $city = '';
        $province = '';
        
        if (!empty($cityProv)) {
            // Split on comma
            $parts = explode(',', $cityProv);
            if (count($parts) >= 2) {
                $city = trim($parts[0]);
                $province = trim($parts[1]);
                
                // Clean up province abbreviations
                $province = str_replace('.', '', $province);
                
                // Convert full province names to standard abbreviations
                $provinceMap = [
                    'SASK' => 'SK', 'SASKATCHEWAN' => 'SK',
                    'MAN' => 'MB', 'MANITOBA' => 'MB',
                    'ALTA' => 'AB', 'ALBERTA' => 'AB',
                    'B.C' => 'BC', 'BRITISH COLUMBIA' => 'BC',
                    'ONT' => 'ON', 'ONTARIO' => 'ON',
                    'QUE' => 'QC', 'QUEBEC' => 'QC',
                    'N.S' => 'NS', 'NOVA SCOTIA' => 'NS',
                    'N.B' => 'NB', 'NEW BRUNSWICK' => 'NB',
                    'P.E.I' => 'PE', 'PRINCE EDWARD ISLAND' => 'PE',
                    'NFLD' => 'NL', 'NEWFOUNDLAND' => 'NL',
                    'YUKON' => 'YT', 'N.W.T' => 'NT', 'NUNAVUT' => 'NU'
                ];
                
                $provinceUpper = strtoupper($province);
                foreach ($provinceMap as $old => $new) {
                    if (strpos($provinceUpper, $old) !== false) {
                        $province = $new;
                        break;
                    }
                }
            } else {
                // No comma, might be just city
                $city = trim($cityProv);
            }
        }
        
        // Build full address (ADDR2 is usually street, ADDR3 might be additional)
        $address = $addr2;
        if (!empty($addr3)) {
            $address .= ($address ? ', ' : '') . $addr3;
        }
        
        // Determine status (active if has proper data, otherwise inactive)
        $status = (!empty($address) && !empty($city)) ? 'Active' : 'Inactive';
        
        // Create notes from original city-prov field for reference
        $notes = "Original location: $cityProv";
        
        try {
            $stmt->execute([
                ':customer_code' => $customerCode,
                ':business_name' => $name,
                ':contact_name' => $contact,
                ':address' => $address,
                ':city' => $city,
                ':province' => $province,
                ':postal_code' => $postalCode,
                ':country' => 'Canada',
                ':phone' => $phone,
                ':email' => $email,
                ':terms' => $terms ?: null,
                ':discount_percent' => $discount,
                ':price_level' => $priceLevel ?: 1,
                ':status' => $status,
                ':notes' => $notes
            ]);
            
            $imported++;
            
            if ($imported % 100 == 0) {
                echo "Imported $imported customers...\n";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Error importing $customerCode ($name): " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n=== Import Complete ===\n";
    echo "Successfully imported: $imported customers\n";
    echo "Skipped (incomplete): $skipped records\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nError details:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - $error\n";
        }
        if (count($errors) > 10) {
            echo "  ... and " . (count($errors) - 10) . " more\n";
        }
    }
    
    // Show sample of imported data
    echo "\n=== Sample Imported Customers ===\n";
    $stmt = $pdo->query("
        SELECT customer_code, business_name, city, province, terms, discount_percent, price_level, status 
        FROM clients 
        ORDER BY customer_code 
        LIMIT 5
    ");
    
    while ($row = $stmt->fetch()) {
        echo sprintf(
            "%s | %s | %s, %s | Terms: %s | Disc: %.2f%% | Price Level: %d | %s\n",
            str_pad($row['customer_code'], 8),
            str_pad($row['business_name'], 30),
            $row['city'],
            $row['province'],
            $row['terms'] ?: 'N/A',
            $row['discount_percent'],
            $row['price_level'],
            $row['status']
        );
    }
    
    echo "\n✓ Import successful!\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
