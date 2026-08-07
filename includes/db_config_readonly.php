<?php
/**
 * Read-Only Database Configuration
 * For public-facing APIs that only need SELECT access
 */

// Read-only database configuration
define('DB_HOST_READONLY', 'localhost');
define('DB_NAME_READONLY', 'CadmanClients');
define('DB_USER_READONLY', 'cadman_viewer');
define('DB_PASS_READONLY', 'View2025!Cadman');
define('DB_CHARSET_READONLY', 'utf8mb4');

/**
 * Get read-only PDO database connection
 * Only allows SELECT operations
 */
function getReadOnlyDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST_READONLY . ";dbname=" . DB_NAME_READONLY . ";charset=" . DB_CHARSET_READONLY;
        $pdo = new PDO($dsn, DB_USER_READONLY, DB_PASS_READONLY, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Read-only database connection failed: " . $e->getMessage());
        die("Database connection error. Please contact support.");
    }
}

/**
 * Validate that connection is truly read-only (for security)
 * This function tests if write operations are blocked
 */
function validateReadOnlyConnection() {
    try {
        $pdo = getReadOnlyDBConnection();
        
        // Test if user can perform INSERT (should fail)
        $testStmt = $pdo->prepare("INSERT INTO clients (business_name) VALUES ('__TEST__')");
        $testStmt->execute();
        
        // If we get here, the connection is NOT read-only
        throw new Exception("SECURITY ERROR: Database connection allows write operations!");
        
    } catch (PDOException $e) {
        // This is expected - write operations should fail
        if (strpos($e->getMessage(), 'INSERT command denied') !== false || 
            strpos($e->getMessage(), 'Access denied') !== false) {
            return true; // Read-only validation passed
        } else {
            throw new Exception("Unexpected database error during read-only validation: " . $e->getMessage());
        }
    }
}
?>