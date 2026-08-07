<?php
/**
 * Import Inventory and Bill of Materials to MySQL
 * Creates tables and imports IC-EXP01.csv and BM-EXP01.csv
 */

ini_set('memory_limit', '512M');
set_time_limit(300);

echo "Starting Inventory and BOM import...\n\n";

// Database connection - use root via socket auth
$dbname = 'CadmanClients';
try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Create inventory table
echo "Creating inventory table...\n";
$pdo->exec("DROP TABLE IF EXISTS inventory");
$pdo->exec("
    CREATE TABLE inventory (
        inventory_id INT AUTO_INCREMENT PRIMARY KEY,
        part_number VARCHAR(50) NOT NULL,
        description VARCHAR(255),
        class VARCHAR(10),
        cost DECIMAL(10,2),
        material_cost DECIMAL(10,2),
        metal_hi VARCHAR(10),
        metal_lo VARCHAR(10),
        group_code VARCHAR(10),
        gold_grams DECIMAL(10,3),
        gold_cost DECIMAL(10,2),
        sterling_grams DECIMAL(10,3),
        sterling_cost DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_part (part_number),
        INDEX idx_class (class),
        INDEX idx_metal (metal_hi, metal_lo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✓ Inventory table created\n\n";

// Create bill of materials table
echo "Creating bill_of_materials table...\n";
$pdo->exec("DROP TABLE IF EXISTS bill_of_materials");
$pdo->exec("
    CREATE TABLE bill_of_materials (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        item_code VARCHAR(50) NOT NULL,
        component_part VARCHAR(50) NOT NULL,
        class VARCHAR(10),
        quantity DECIMAL(10,3),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item (item_code),
        INDEX idx_component (component_part),
        INDEX idx_item_component (item_code, component_part)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✓ Bill of Materials table created\n\n";

// Import Inventory (IC-EXP01.csv)
echo "Importing inventory data from IC-EXP01.csv...\n";
$csvFile = __DIR__ . '/IC-EXP01.csv';
if (!file_exists($csvFile)) {
    die("ERROR: IC-EXP01.csv not found\n");
}

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle, 0, '|'); // Skip header
$inventoryCount = 0;
$inventoryErrors = 0;

$stmt = $pdo->prepare("
    INSERT INTO inventory (
        part_number, description, class, cost, material_cost,
        metal_hi, metal_lo, group_code, gold_grams, gold_cost,
        sterling_grams, sterling_cost
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

while (($row = fgetcsv($handle, 0, '|')) !== false) {
    if (count($row) < 12) continue;
    
    try {
        $stmt->execute([
            trim($row[0]),  // part_number
            trim($row[1]),  // description
            trim($row[2]),  // class
            floatval($row[3]),  // cost
            floatval($row[4]),  // material_cost
            trim($row[5]),  // metal_hi
            trim($row[6]),  // metal_lo
            trim($row[7]),  // group_code
            floatval($row[8]),  // gold_grams
            floatval($row[9]),  // gold_cost
            floatval($row[10]), // sterling_grams
            floatval($row[11])  // sterling_cost
        ]);
        $inventoryCount++;
        
        if ($inventoryCount % 100 == 0) {
            echo "  Imported $inventoryCount inventory items...\r";
        }
    } catch (PDOException $e) {
        $inventoryErrors++;
        if ($inventoryErrors <= 5) {
            echo "Error on row: " . $e->getMessage() . "\n";
        }
    }
}
fclose($handle);
echo "\n✓ Imported $inventoryCount inventory items ($inventoryErrors errors)\n\n";

// Import Bill of Materials (BM-EXP01.csv)
echo "Importing bill of materials data from BM-EXP01.csv...\n";
$csvFile = __DIR__ . '/BM-EXP01.csv';
if (!file_exists($csvFile)) {
    die("ERROR: BM-EXP01.csv not found\n");
}

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle, 0, '|'); // Skip header
$bomCount = 0;
$bomErrors = 0;

$stmt = $pdo->prepare("
    INSERT INTO bill_of_materials (
        item_code, component_part, class, quantity
    ) VALUES (?, ?, ?, ?)
");

while (($row = fgetcsv($handle, 0, '|')) !== false) {
    if (count($row) < 4) continue;
    
    try {
        $stmt->execute([
            trim($row[0]),  // item_code
            trim($row[1]),  // component_part
            trim($row[2]),  // class
            floatval($row[3])  // quantity
        ]);
        $bomCount++;
        
        if ($bomCount % 1000 == 0) {
            echo "  Imported $bomCount BOM records...\r";
        }
    } catch (PDOException $e) {
        $bomErrors++;
        if ($bomErrors <= 5) {
            echo "Error on row: " . $e->getMessage() . "\n";
        }
    }
}
fclose($handle);
echo "\n✓ Imported $bomCount BOM records ($bomErrors errors)\n\n";

// Summary
echo "═══════════════════════════════════════\n";
echo "Import Complete!\n";
echo "═══════════════════════════════════════\n";
echo "Inventory Items:  $inventoryCount\n";
echo "BOM Records:      $bomCount\n";
echo "Total Records:    " . ($inventoryCount + $bomCount) . "\n";
echo "═══════════════════════════════════════\n";
