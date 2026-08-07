<?php
/**
 * Explore database structure and content
 */

require_once __DIR__ . '/includes/db_config_encrypted.php';

try {
    $conn = getAdminConnection();
    
    echo "🗄️  Database Structure Analysis\n";
    echo "===============================\n\n";
    
    // Get all tables in the database
    echo "📋 All Tables in Database:\n";
    echo "-------------------------\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n📊 Table Statistics:\n";
    echo "-------------------\n";
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "  $table: $count rows\n";
    }
    
    // Focus on product-related tables
    echo "\n🔍 Product-Related Tables:\n";
    echo "-------------------------\n";
    
    $productTables = array_filter($tables, function($table) {
        return stripos($table, 'product') !== false || 
               stripos($table, 'catalog') !== false ||
               stripos($table, 'item') !== false ||
               stripos($table, 'jewelry') !== false;
    });
    
    foreach ($productTables as $table) {
        echo "\n📄 Table: $table\n";
        echo str_repeat("-", strlen($table) + 10) . "\n";
        
        // Get table structure
        $stmt = $conn->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns:\n";
        foreach ($columns as $column) {
            $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $key = $column['Key'] ? " ({$column['Key']})" : '';
            $default = $column['Default'] !== null ? " DEFAULT '{$column['Default']}'" : '';
            echo "  - {$column['Field']}: {$column['Type']} $null$key$default\n";
        }
        
        // Get sample data
        echo "\nSample Data (first 3 rows):\n";
        $stmt = $conn->query("SELECT * FROM `$table` LIMIT 3");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($samples)) {
            foreach ($samples as $i => $row) {
                echo "  Row " . ($i + 1) . ":\n";
                foreach ($row as $col => $val) {
                    $displayVal = strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val;
                    echo "    $col: '$displayVal'\n";
                }
                echo "\n";
            }
        } else {
            echo "  (No data)\n\n";
        }
    }
    
    // Check for any other interesting tables
    echo "\n🔍 Other Potentially Relevant Tables:\n";
    echo "------------------------------------\n";
    
    $otherTables = array_filter($tables, function($table) {
        return !in_array($table, ['catalog_products']) && 
               (stripos($table, 'image') !== false ||
                stripos($table, 'collection') !== false ||
                stripos($table, 'category') !== false ||
                stripos($table, 'index') !== false);
    });
    
    foreach ($otherTables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        
        echo "📄 $table ($count rows)\n";
        
        // Quick sample
        $stmt = $conn->query("SELECT * FROM `$table` LIMIT 2");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($samples)) {
            $firstRow = $samples[0];
            echo "  Sample columns: " . implode(', ', array_keys($firstRow)) . "\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>