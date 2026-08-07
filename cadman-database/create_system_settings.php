<?php
/**
 * Create system_settings table and populate with defaults
 * Run this once to set up global system settings
 */

require_once '../includes/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Creating system_settings table...\n";
    
    // Create table
    $createTable = "
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value VARCHAR(255) NOT NULL,
            setting_type ENUM('string', 'number', 'boolean') DEFAULT 'string',
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTable);
    echo "✓ Table created\n";
    
    // Insert default settings
    echo "Inserting default settings...\n";
    
    $settings = [
        ['gold_price', '7300.00', 'number', 'Current gold price per troy ounce ($/oz)'],
        ['labor_rate', '28.00', 'number', 'Labor rate per hour ($/hr)'],
        ['sterling_gf', '130.00', 'number', 'Sterling gold factor for cost calculations'],
        ['base_margin', '8.00', 'number', 'Base margin percentage applied to all products (%)'],
        ['company_name', 'Cadman Manufacturing', 'string', 'Company name'],
        ['company_code', 'CADMAN', 'string', 'Company code identifier'],
        ['sales_tax_rate', '0.00', 'number', 'Default sales tax rate (%)'],
        ['fiscal_year_start', '01-01', 'string', 'Fiscal year start date (MM-DD)']
    ];
    
    $insertSql = "
        INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
        VALUES (:key, :value, :type, :description)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type),
            description = VALUES(description)
    ";
    
    $stmt = $pdo->prepare($insertSql);
    
    foreach ($settings as $setting) {
        $stmt->execute([
            ':key' => $setting[0],
            ':value' => $setting[1],
            ':type' => $setting[2],
            ':description' => $setting[3]
        ]);
        echo "  ✓ {$setting[0]} = {$setting[1]}\n";
    }
    
    echo "\n✅ System settings table created and populated successfully!\n";
    echo "\nCurrent settings:\n";
    
    $results = $pdo->query("SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key");
    foreach ($results as $row) {
        echo "  • {$row['setting_key']}: {$row['setting_value']} - {$row['description']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
