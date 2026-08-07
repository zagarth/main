<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../../includes/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['settings'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $updateSql = "UPDATE system_settings 
                  SET setting_value = :value, updated_by = :user_id 
                  WHERE setting_key = :key";
    $stmt = $pdo->prepare($updateSql);
    
    $updated = [];
    $userId = $_SESSION['user_id'];
    
    foreach ($input['settings'] as $key => $value) {
        // Convert value to string for storage
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            $value = strval($value);
        }
        
        $result = $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':user_id' => $userId
        ]);
        
        if ($stmt->rowCount() > 0) {
            $updated[] = $key;
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings updated successfully',
        'updated_count' => count($updated),
        'updated_settings' => $updated
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
