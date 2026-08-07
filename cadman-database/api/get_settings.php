<?php
/**
 * System Settings API - Get all or specific settings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $key = $_GET['key'] ?? null;
    
    if ($key) {
        // Get specific setting
        $sql = "SELECT setting_key, setting_value, setting_type, description, updated_at 
                FROM system_settings 
                WHERE setting_key = :key";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Setting not found']);
            exit;
        }
        
        // Convert value based on type
        if ($result['setting_type'] === 'number') {
            $result['setting_value'] = floatval($result['setting_value']);
        } elseif ($result['setting_type'] === 'boolean') {
            $result['setting_value'] = $result['setting_value'] === 'true' || $result['setting_value'] === '1';
        }
        
        echo json_encode([
            'success' => true,
            'setting' => $result
        ]);
    } else {
        // Get all settings
        $sql = "SELECT setting_key, setting_value, setting_type, description, updated_at 
                FROM system_settings 
                ORDER BY setting_key";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert values and create key-value object
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            if ($row['setting_type'] === 'number') {
                $value = floatval($value);
            } elseif ($row['setting_type'] === 'boolean') {
                $value = $value === 'true' || $value === '1';
            }
            
            $settings[$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type'],
                'description' => $row['description'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($settings),
            'settings' => $settings
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
