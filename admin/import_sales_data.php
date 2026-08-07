<?php
/**
 * Import Sales Data from SA-EXP01.csv
 * Creates sales_transactions table and imports all sales history
 */

require_once '../includes/db_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting sales data import...\n\n";
    
    // Create sales_transactions table
    echo "Creating sales_transactions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sales_transactions (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_code VARCHAR(20) NOT NULL,
            item_code VARCHAR(20) NOT NULL,
            invoice_date DATE NOT NULL,
            invoice_number VARCHAR(20) NOT NULL,
            description VARCHAR(50),
            ship_date DATE,
            selling_price DECIMAL(10,2) DEFAULT 0.00,
            quantity INT DEFAULT 1,
            cost DECIMAL(10,2) DEFAULT 0.00,
            sales_rep VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_code),
            INDEX idx_item (item_code),
            INDEX idx_invoice (invoice_number),
            INDEX idx_invoice_date (invoice_date),
            INDEX idx_ship_date (ship_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Table created\n\n";
    
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM sales_transactions");
    $existingCount = $stmt->fetchColumn();
    
    if ($existingCount > 0) {
        echo "⚠️  Table already contains $existingCount records.\n";
        echo "Do you want to:\n";
        echo "  1. Skip import (keep existing data)\n";
        echo "  2. Clear and reimport all data\n";
        echo "  3. Append new data\n";
        echo "\nChoice (1-3): ";
        
        $choice = trim(fgets(STDIN));
        
        if ($choice == '2') {
            echo "\nClearing existing data...\n";
            $pdo->exec("TRUNCATE TABLE sales_transactions");
            echo "✓ Cleared\n\n";
        } elseif ($choice == '1') {
            echo "\nSkipping import. Exiting.\n";
            exit(0);
        }
    }
    
    // Read CSV file
    $csvFile = '/var/www/html/homesite/cadman-database/SA-EXP01.csv';
    
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }
    
    echo "Reading CSV file: $csvFile\n";
    $fileContents = file_get_contents($csvFile);
    $lines = explode("\n", trim($fileContents));
    $totalLines = count($lines);
    
    echo "Found $totalLines sales records\n\n";
    
    // Prepare insert statement
    $insertStmt = $pdo->prepare("
        INSERT INTO sales_transactions (
            customer_code, item_code, invoice_date, invoice_number,
            description, ship_date, selling_price, quantity, cost, sales_rep
        ) VALUES (
            :customer_code, :item_code, :invoice_date, :invoice_number,
            :description, :ship_date, :selling_price, :quantity, :cost, :sales_rep
        )
    ");
    
    // Begin transaction for faster bulk insert
    $pdo->beginTransaction();
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    echo "Importing sales data...\n";
    
    foreach ($lines as $lineNum => $line) {
        if (empty(trim($line))) {
            continue;
        }
        
        $fields = explode('|', $line);
        
        if (count($fields) < 10) {
            $skipped++;
            $errors[] = "Line " . ($lineNum + 1) . ": Invalid format (only " . count($fields) . " fields)";
            continue;
        }
        
        // Parse fields
        $customerCode = trim($fields[0]);
        $itemCode = trim($fields[1]);
        $invoiceDateStr = trim($fields[2]);
        $invoiceNumber = trim($fields[3]);
        $description = trim($fields[4]);
        $shipDateStr = trim($fields[5]);
        $sellingPrice = floatval(trim($fields[6]));
        $quantity = intval(trim($fields[7]));
        $cost = floatval(trim($fields[8]));
        $salesRep = trim($fields[9]);
        
        // Convert dates from YYYYMMDD to YYYY-MM-DD
        $invoiceDate = null;
        if (strlen($invoiceDateStr) == 8) {
            $invoiceDate = substr($invoiceDateStr, 0, 4) . '-' . 
                          substr($invoiceDateStr, 4, 2) . '-' . 
                          substr($invoiceDateStr, 6, 2);
        }
        
        $shipDate = null;
        if (strlen($shipDateStr) == 8) {
            $shipDate = substr($shipDateStr, 0, 4) . '-' . 
                       substr($shipDateStr, 4, 2) . '-' . 
                       substr($shipDateStr, 6, 2);
        }
        
        try {
            $insertStmt->execute([
                ':customer_code' => $customerCode,
                ':item_code' => $itemCode,
                ':invoice_date' => $invoiceDate,
                ':invoice_number' => $invoiceNumber,
                ':description' => $description,
                ':ship_date' => $shipDate,
                ':selling_price' => $sellingPrice,
                ':quantity' => $quantity,
                ':cost' => $cost,
                ':sales_rep' => $salesRep
            ]);
            
            $imported++;
            
            if ($imported % 500 == 0) {
                echo "  Imported $imported / $totalLines records...\n";
            }
            
        } catch (PDOException $e) {
            $skipped++;
            $errors[] = "Line " . ($lineNum + 1) . ": " . $e->getMessage();
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║           SALES DATA IMPORT COMPLETE                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "✓ Successfully imported: $imported records\n";
    
    if ($skipped > 0) {
        echo "⚠ Skipped: $skipped records\n";
    }
    
    // Show statistics
    echo "\n--- Sales Statistics ---\n";
    
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(DISTINCT customer_code) as unique_customers,
            COUNT(DISTINCT item_code) as unique_items,
            COUNT(DISTINCT invoice_number) as unique_invoices,
            SUM(selling_price * quantity) as total_sales,
            MIN(invoice_date) as first_sale,
            MAX(invoice_date) as last_sale
        FROM sales_transactions
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Total Transactions: " . number_format($stats['total_transactions']) . "\n";
    echo "Unique Customers: " . number_format($stats['unique_customers']) . "\n";
    echo "Unique Items Sold: " . number_format($stats['unique_items']) . "\n";
    echo "Unique Invoices: " . number_format($stats['unique_invoices']) . "\n";
    echo "Total Sales: $" . number_format($stats['total_sales'], 2) . "\n";
    echo "Date Range: {$stats['first_sale']} to {$stats['last_sale']}\n";
    
    // Show top customers
    echo "\n--- Top 10 Customers by Sales ---\n";
    $topCustomers = $pdo->query("
        SELECT 
            customer_code,
            COUNT(*) as transaction_count,
            SUM(selling_price * quantity) as total_spent
        FROM sales_transactions
        GROUP BY customer_code
        ORDER BY total_spent DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topCustomers as $i => $customer) {
        printf("%2d. %-10s  %4d transactions  $%10s\n", 
            $i + 1,
            $customer['customer_code'],
            $customer['transaction_count'],
            number_format($customer['total_spent'], 2)
        );
    }
    
    // Show errors if any
    if (count($errors) > 0 && count($errors) <= 20) {
        echo "\n--- Errors ---\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
    } elseif (count($errors) > 20) {
        echo "\n--- First 20 Errors ---\n";
        for ($i = 0; $i < 20; $i++) {
            echo "  • {$errors[$i]}\n";
        }
        echo "  ... and " . (count($errors) - 20) . " more errors\n";
    }
    
    echo "\n✓ Import complete!\n\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
