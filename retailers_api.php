<?php
/**
 * Retailers API endpoint
 * Reads retailer data directly from CadmanClients database using read-only connection
 * Updated to use secure read-only database access
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Load read-only database configuration
require_once 'includes/db_config_readonly.php';

try {
    // Validate read-only connection security
    validateReadOnlyConnection();
    
    // Get read-only database connection
    $pdo = getReadOnlyDBConnection();
    
    // Query all active retailers from database using read-only connection
    $stmt = $pdo->prepare("
        SELECT 
            client_id,
            business_name,
            contact_name,
            address,
            city,
            province,
            postal_code,
            country,
            phone,
            email,
            website,
            latitude,
            longitude,
            status,
            notes,
            created_at,
            updated_at
        FROM clients 
        WHERE client_type = 'Retailer' 
        AND status = 'Active'
        ORDER BY business_name ASC
    ");
    
    $stmt->execute();
    $dbRetailers = $stmt->fetchAll();
    
    $retailers = [];
    
    // Convert database format to expected API format
    foreach ($dbRetailers as $retailer) {
        // Skip retailers with missing business names
        if (empty($retailer['business_name'])) {
            continue;
        }
        
        // Build combined address string (same format as before)
        $addressParts = [];
        if (!empty($retailer['address'])) {
            $addressParts[] = $retailer['address'];
        }
        if (!empty($retailer['city'])) {
            $addressParts[] = $retailer['city'];
        }
        if (!empty($retailer['province'])) {
            $addressParts[] = $retailer['province'];
        }
        if (!empty($retailer['postal_code'])) {
            $addressParts[] = $retailer['postal_code'];
        }
        $combinedAddress = implode(', ', $addressParts);
        
        // Format coordinates as floats
        $lat = $retailer['latitude'] ? (float)$retailer['latitude'] : 0.0;
        $lng = $retailer['longitude'] ? (float)$retailer['longitude'] : 0.0;
        
        $apiData = [
            "ID" => $retailer['client_id'],
            "name" => $retailer['business_name'],
            "address" => $combinedAddress, // Combined address for map display
            "street" => $retailer['address'] ?? '',
            "city" => $retailer['city'] ?? '',
            "state" => $retailer['province'] ?? '', // Use province as state for compatibility
            "province" => $retailer['province'] ?? '',
            "postal_code" => $retailer['postal_code'] ?? '',
            "country" => $retailer['country'] ?? 'Canada',
            "phone" => $retailer['phone'] ?? '',
            "email" => $retailer['email'] ?? '',
            "website" => $retailer['website'] ?? '',
            "lat" => $lat,
            "lng" => $lng,
            "contact_name" => $retailer['contact_name'] ?? '',
            "status" => $retailer['status'],
            "notes" => $retailer['notes'] ?? '',
            "created_at" => $retailer['created_at'],
            "updated_at" => $retailer['updated_at'],
            // Legacy fields for compatibility with existing map code
            "specialties" => [], // Could be populated from notes or separate table in future
            "services" => [],   // Could be populated from notes or separate table in future
            "hours" => []       // Could be populated from separate table in future
        ];
        
        $retailers[] = $apiData;
    }
    
    // Add debug information in development
    $debugInfo = [
        "total_retailers" => count($retailers),
        "data_source" => "CadmanClients database (read-only)",
        "query_time" => date('Y-m-d H:i:s'),
        "database_connection" => "read-only validated"
    ];
    
    // Return retailers data with debug info as HTTP headers for development
    header("X-Debug-Total-Count: " . $debugInfo["total_retailers"]);
    header("X-Debug-Data-Source: " . $debugInfo["data_source"]);
    header("X-Debug-Query-Time: " . $debugInfo["query_time"]);
    header("X-Debug-Connection-Type: read-only");
    
    echo json_encode($retailers, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Read-only database error in retailers_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed", 
        "message" => "Unable to retrieve retailer data from read-only database",
        "debug" => "Check read-only database connection and credentials"
    ]);
} catch (Exception $e) {
    error_log("General error in retailers_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Server error", 
        "message" => $e->getMessage()
    ]);
}
?>
