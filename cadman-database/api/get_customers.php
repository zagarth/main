<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../includes/db_config.php';

try {
    $pdo = getDBConnection();
    $search = $_GET['search'] ?? '';
    $q = $_GET['q'] ?? ''; // For autocomplete search
    $limit = min((int)($_GET['limit'] ?? 50), 50); // Max 50 results
    $customerCode = $_GET['code'] ?? null;
    $clientId = $_GET['client_id'] ?? null;
    
    if ($clientId) {
        // Lookup by client_id
        $stmt = $pdo->prepare("
            SELECT client_id, customer_code, business_name, contact_name, city, province, 
                   phone, email, terms, discount_percent, price_level 
            FROM clients 
            WHERE client_id = :id AND status = 'Active'
            LIMIT 1
        ");
        $stmt->execute([':id' => $clientId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $customer ? [$customer] : []]);
        
    } elseif ($customerCode) {
        // Lookup by customer code
        $stmt = $pdo->prepare("
            SELECT client_id, customer_code, business_name, contact_name, city, province, 
                   phone, email, terms, discount_percent, price_level 
            FROM clients 
            WHERE customer_code = :code AND status = 'Active'
            LIMIT 1
        ");
        $stmt->execute([':code' => $customerCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $customer ? [$customer] : []]);
        
    } elseif ($q && strlen($q) >= 2) {
        // Autocomplete search for store names
        $searchPattern = '%' . $q . '%';
        $startsPattern = $q . '%';
        $exactPattern = $q;

        $stmt = $pdo->prepare("
            SELECT client_id, customer_code, business_name, contact_name, city, province, 
                   phone, email, terms, discount_percent, price_level 
            FROM clients 
            WHERE status = 'Active' 
            AND (business_name LIKE ?
                 OR customer_code LIKE ?
                 OR contact_name LIKE ?)
            ORDER BY 
                CASE 
                    WHEN customer_code LIKE ? THEN 1
                    WHEN business_name LIKE ? THEN 2
                    WHEN business_name LIKE ? THEN 3
                    ELSE 4
                END,
                business_name ASC
            LIMIT " . (int) $limit
        );

        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $exactPattern, $startsPattern, $searchPattern]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'customers' => $customers,
            'data' => $customers, // Keep original format for compatibility
            'count' => count($customers),
            'searchTerm' => $q
        ]);
        
    } else {
        // General search customers (original functionality)
        $sql = "
            SELECT client_id, customer_code, business_name, contact_name, city, province, 
                   phone, email, terms, discount_percent, price_level 
            FROM clients 
            WHERE status = 'Active'
        ";
        
        if ($search) {
            $sql .= " AND (customer_code LIKE :s1 OR business_name LIKE :s2 OR contact_name LIKE :s3 OR city LIKE :s4 OR phone LIKE :s5)";
        }
        
        $sql .= " ORDER BY business_name LIMIT " . $limit;
        
        $stmt = $pdo->prepare($sql);
        if ($search) {
            $searchParam = "%$search%";
            $stmt->execute([
                ':s1' => $searchParam,
                ':s2' => $searchParam,
                ':s3' => $searchParam,
                ':s4' => $searchParam,
                ':s5' => $searchParam
            ]);
        } else {
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
