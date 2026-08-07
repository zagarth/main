<?php
/**
 * Create user accounts for all retailers
 * Username: email if exists, otherwise last 7 digits of phone
 * Password: last 3 digits of postal code + last 4 digits of phone
 */

require_once __DIR__ . '/../includes/db_config_encrypted.php';

echo "Creating user accounts for retailers...\n\n";

$pdo = getAdminConnection();

// Get all clients
$clients = $pdo->query("
    SELECT client_id, business_name, email, phone, postal_code 
    FROM clients 
    ORDER BY client_id
")->fetchAll();

echo "Found " . count($clients) . " clients\n\n";

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($clients as $client) {
    $clientId = $client['client_id'];
    $businessName = $client['business_name'];
    $email = trim($client['email'] ?? '');
    $phone = trim($client['phone'] ?? '');
    $postalCode = trim($client['postal_code'] ?? '');
    
    // Determine username
    $username = null;
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Use email as username
        $username = strtolower($email);
    } elseif (!empty($phone)) {
        // Use last 7 digits of phone
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) >= 7) {
            $username = substr($cleanPhone, -7);
        }
    }
    
    // Determine password
    $password = null;
    $postalLast3 = '';
    $phoneLast4 = '';
    
    if (!empty($postalCode)) {
        $cleanPostal = preg_replace('/[^A-Z0-9]/i', '', $postalCode);
        $postalLast3 = substr($cleanPostal, -3);
    }
    
    if (!empty($phone)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $phoneLast4 = substr($cleanPhone, -4);
    }
    
    if (!empty($postalLast3) && !empty($phoneLast4)) {
        $password = $postalLast3 . $phoneLast4;
    }
    
    // Skip if we don't have both username and password
    if (empty($username) || empty($password)) {
        echo "SKIP [{$clientId}] $businessName - ";
        if (empty($username)) echo "No valid username (no email or phone) ";
        if (empty($password)) echo "No valid password (missing postal or phone) ";
        echo "\n";
        $skipped++;
        continue;
    }
    
    // Create user account
    try {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (client_id, username, email, password_hash, role, status)
            VALUES (:client_id, :username, :email, :password_hash, 'business', 'active')
        ");
        
        $stmt->execute([
            'client_id' => $clientId,
            'username' => $username,
            'email' => !empty($email) ? $email : $username . '@placeholder.local',
            'password_hash' => $passwordHash
        ]);
        
        $created++;
        echo "✓ [{$clientId}] $businessName\n";
        echo "   Username: $username\n";
        echo "   Password: $password\n\n";
        
    } catch (PDOException $e) {
        $errors++;
        echo "ERROR [{$clientId}] $businessName: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== USER CREATION COMPLETE ===\n";
echo "Created: $created user accounts\n";
echo "Skipped: $skipped (missing data)\n";
echo "Errors: $errors\n";

// Verify
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'business'")->fetchColumn();
echo "\nTotal business users in database: $totalUsers\n";

// Show sample
echo "\nSample users:\n";
$sample = $pdo->query("
    SELECT u.username, u.email, c.business_name, c.city, c.province 
    FROM users u 
    JOIN clients c ON u.client_id = c.client_id 
    WHERE u.role = 'business'
    LIMIT 5
")->fetchAll();

foreach ($sample as $row) {
    echo "  {$row['username']} - {$row['business_name']} ({$row['city']}, {$row['province']})\n";
}
