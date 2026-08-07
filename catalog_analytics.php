<?php
/**
 * Catalog Analytics Handler
 * Tracks PDF views, device info, and caching performance
 */

require_once __DIR__ . '/includes/db_config_encrypted.php';

class CatalogAnalytics {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getAdminConnection(); // Use admin connection for write operations
    }
    
    /**
     * Log a PDF view with device and optional location data
     */
    public function logPDFView($pdfFilename, $sectionName = null, $additionalData = []) {
        try {
            // Get device info
            $deviceInfo = $this->detectDevice();
            
            // Get geolocation if available
            $geoData = $this->getGeolocation();
            
            // Prepare data
            $data = array_merge([
                'pdf_filename' => $pdfFilename,
                'section_name' => $sectionName,
                'device_type' => $deviceInfo['type'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'screen_resolution' => $additionalData['screen_resolution'] ?? null,
                'connection_speed' => $additionalData['connection_speed'] ?? 'unknown',
                'cache_requested' => $additionalData['cache_requested'] ?? false,
                'cache_successful' => $additionalData['cache_successful'] ?? false,
                'load_time_ms' => $additionalData['load_time_ms'] ?? null,
                'ip_address' => $this->getClientIP()
            ], $geoData);
            
            // Check if entry exists
            $stmt = $this->pdo->prepare("
                SELECT id, view_count FROM catalog_analytics 
                WHERE pdf_filename = ? AND DATE(last_accessed) = CURDATE()
                LIMIT 1
            ");
            $stmt->execute([$pdfFilename]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $stmt = $this->pdo->prepare("
                    UPDATE catalog_analytics 
                    SET view_count = view_count + 1,
                        last_accessed = CURRENT_TIMESTAMP,
                        device_type = ?,
                        user_agent = ?,
                        screen_resolution = COALESCE(?, screen_resolution),
                        connection_speed = ?,
                        cache_requested = ?,
                        cache_successful = ?,
                        load_time_ms = COALESCE(?, load_time_ms)
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['device_type'], $data['user_agent'], $data['screen_resolution'],
                    $data['connection_speed'], $data['cache_requested'] ? 1 : 0, $data['cache_successful'] ? 1 : 0,
                    $data['load_time_ms'], $existing['id']
                ]);
            } else {
                // Insert new record
                $stmt = $this->pdo->prepare("
                    INSERT INTO catalog_analytics (
                        pdf_filename, section_name, device_type, user_agent, screen_resolution,
                        connection_speed, cache_requested, cache_successful, load_time_ms,
                        country_code, region, city, timezone, ip_address
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['pdf_filename'], $data['section_name'], $data['device_type'],
                    $data['user_agent'], $data['screen_resolution'], $data['connection_speed'],
                    $data['cache_requested'] ? 1 : 0, $data['cache_successful'] ? 1 : 0, $data['load_time_ms'],
                    $data['country_code'], $data['region'], $data['city'], $data['timezone'], $data['ip_address']
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Analytics error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get popular PDFs for smart preloading
     */
    public function getPopularPDFs($limit = 10, $deviceType = null) {
        $sql = "SELECT pdf_filename, section_name, total_views, avg_load_time 
                FROM catalog_analytics_summary";
        $params = [];
        
        if ($deviceType) {
            $sql = "SELECT pdf_filename, section_name, SUM(view_count) as total_views, 
                           AVG(load_time_ms) as avg_load_time
                    FROM catalog_analytics 
                    WHERE device_type = ? 
                    GROUP BY pdf_filename 
                    ORDER BY total_views DESC";
            $params[] = $deviceType;
        }
        
        $sql .= " LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get analytics dashboard data
     */
    public function getDashboardData() {
        $data = [];
        
        // Total views
        $stmt = $this->pdo->query("SELECT SUM(view_count) as total_views FROM catalog_analytics");
        $data['total_views'] = $stmt->fetchColumn() ?: 0;
        
        // Device breakdown
        $stmt = $this->pdo->query("
            SELECT device_type, SUM(view_count) as views 
            FROM catalog_analytics 
            GROUP BY device_type 
            ORDER BY views DESC
        ");
        $data['device_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Popular sections
        $stmt = $this->pdo->query("
            SELECT section_name, SUM(view_count) as views 
            FROM catalog_analytics 
            WHERE section_name IS NOT NULL 
            GROUP BY section_name 
            ORDER BY views DESC 
            LIMIT 10
        ");
        $data['popular_sections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cache performance
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(CASE WHEN cache_requested = 1 THEN 1 END) as cache_requests,
                COUNT(CASE WHEN cache_successful = 1 THEN 1 END) as cache_successes,
                AVG(load_time_ms) as avg_load_time
            FROM catalog_analytics
        ");
        $data['cache_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Geographic data
        $stmt = $this->pdo->query("
            SELECT country_code, COUNT(*) as views 
            FROM catalog_analytics 
            WHERE country_code IS NOT NULL 
            GROUP BY country_code 
            ORDER BY views DESC 
            LIMIT 10
        ");
        $data['geographic'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Simple device detection
     */
    private function detectDevice() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/(tablet|ipad)/i', $userAgent)) {
            return ['type' => 'tablet'];
        } elseif (preg_match('/(mobile|phone|android|iphone)/i', $userAgent)) {
            return ['type' => 'mobile'];
        } else {
            return ['type' => 'desktop'];
        }
    }
    
    /**
     * Get geolocation data (using ip-api.com free service)
     */
    private function getGeolocation() {
        $ip = $this->getClientIP();
        
        // Skip local/private IPs
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return [
                'country_code' => null,
                'region' => null, 
                'city' => null,
                'timezone' => null
            ];
        }
        
        try {
            $context = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,city,timezone", false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    return [
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['region'] ?? null,
                        'city' => $data['city'] ?? null,
                        'timezone' => $data['timezone'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            // Fail silently, geolocation is optional
        }
        
        return [
            'country_code' => null,
            'region' => null,
            'city' => null,
            'timezone' => null
        ];
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
}

// AJAX endpoint for logging analytics
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_analytics') {
    header('Content-Type: application/json');
    
    $analytics = new CatalogAnalytics();
    $success = $analytics->logPDFView(
        $_POST['pdf_filename'] ?? '',
        $_POST['section_name'] ?? null,
        [
            'screen_resolution' => $_POST['screen_resolution'] ?? null,
            'connection_speed' => $_POST['connection_speed'] ?? 'unknown',
            'cache_requested' => filter_var($_POST['cache_requested'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'cache_successful' => filter_var($_POST['cache_successful'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'load_time_ms' => (int)($_POST['load_time_ms'] ?? 0) ?: null
        ]
    );
    
    echo json_encode(['success' => $success]);
    exit;
}

// AJAX endpoint for dashboard data
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'dashboard') {
    header('Content-Type: application/json');
    
    $analytics = new CatalogAnalytics();
    $dashboardData = $analytics->getDashboardData();
    
    // Add popular PDFs for preloading suggestions
    $dashboardData['popular_pdfs'] = $analytics->getPopularPDFs(10);
    
    echo json_encode($dashboardData);
    exit;
}

// AJAX endpoint for popular PDFs (for preloading)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'popular_pdfs') {
    header('Content-Type: application/json');
    
    $analytics = new CatalogAnalytics();
    $deviceType = $_GET['device'] ?? null;
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 20))); // Between 5-50 PDFs
    
    $popularPDFs = $analytics->getPopularPDFs($limit, $deviceType);
    
    echo json_encode(['popular_pdfs' => $popularPDFs]);
    exit;
}
?>