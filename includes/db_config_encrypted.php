<?php
/**
 * Database Configuration - Encrypted Credentials Version
 * Uses encrypted configuration file for database credentials
 */

// Load encrypted configuration
require_once __DIR__ . '/config_loader.php';

// Database configuration - from encrypted config
// Using CLIENT_DB_* prefix to avoid conflicts with e-commerce DB_*
define('DB_HOST', $_ENV['CLIENT_DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['CLIENT_DB_NAME'] ?? 'CadmanClients');
define('DB_CHARSET', 'utf8mb4');

// Viewer credentials (read-only) - used for authentication
define('DB_VIEWER_USER', $_ENV['CLIENT_DB_VIEWER_USER'] ?? '');
define('DB_VIEWER_PASS', $_ENV['CLIENT_DB_VIEWER_PASS'] ?? '');

// Admin credentials (full access) - used after verification for write operations
define('DB_ADMIN_USER', $_ENV['CLIENT_DB_USER'] ?? '');
define('DB_ADMIN_PASS', $_ENV['CLIENT_DB_PASS'] ?? '');

/**
 * Get PDO database connection with read-only viewer credentials
 * Used for login verification (minimal privileges)
 */
function getViewerConnection() {
    try {
        if (empty(DB_VIEWER_USER) || empty(DB_VIEWER_PASS)) {
            error_log("Viewer credentials not loaded from encrypted config");
            throw new PDOException("Database configuration error");
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_VIEWER_USER, DB_VIEWER_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Viewer connection failed: " . $e->getMessage());
        throw new PDOException("Database connection error");
    }
}

/**
 * Get PDO database connection with admin credentials
 * Used for write operations after user is verified
 */
function getAdminConnection() {
    try {
        if (empty(DB_ADMIN_USER) || empty(DB_ADMIN_PASS)) {
            error_log("Admin credentials not loaded from encrypted config");
            throw new PDOException("Database configuration error");
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_ADMIN_USER, DB_ADMIN_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Admin connection failed: " . $e->getMessage());
        throw new PDOException("Database connection error");
    }
}

/**
 * Backward compatibility - defaults to admin connection
 * @deprecated Use getViewerConnection() or getAdminConnection() explicitly
 */
function getDBConnection() {
    return getAdminConnection();
}

/**
 * Verify user credentials
 * Uses READ-ONLY viewer credentials for security
 */
function verifyUser($username, $password) {
    $pdo = getViewerConnection();  // Use viewer (read-only) credentials
    $stmt = $pdo->prepare("
        SELECT u.*, c.business_name, c.city, c.province 
        FROM users u
        LEFT JOIN clients c ON u.client_id = c.client_id
        WHERE (u.username = :username OR u.email = :email) AND u.status = 'active'
    ");
    $stmt->execute([
        ':username' => $username,
        ':email' => $username,
    ]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Don't return the password hash to the session
        unset($user['password_hash']);
        return $user;
    }
    
    return false;
}

/**
 * Update user's last login timestamp
 * Uses ADMIN credentials for write operation (called after verification)
 */
function updateLastLogin($user_id) {
    try {
        $pdo = getAdminConnection();  // Use admin credentials for writes
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $user_id]);
    } catch (Exception $e) {
        error_log("Failed to update last login for user $user_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 * Uses viewer (read-only) credentials
 */
function getUserById($user_id) {
    $pdo = getViewerConnection();  // Read-only
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.client_id, u.username, u.email, u.role, u.status, 
               u.created_at, u.last_login, u.force_password_change, c.* 
        FROM users u
        LEFT JOIN clients c ON u.client_id = c.client_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch();
}

/**
 * Get orders for a client
 * Uses viewer (read-only) credentials
 */
function getClientOrders($client_id, $limit = 50) {
    $pdo = getViewerConnection();  // Read-only

    $clientStmt = $pdo->prepare("SELECT customer_code FROM clients WHERE client_id = :client_id LIMIT 1");
    $clientStmt->execute([':client_id' => (int) $client_id]);
    $client = $clientStmt->fetch();
    $customerCode = trim((string) ($client['customer_code'] ?? ''));

    $timeline = [];

    $currentStmt = $pdo->prepare("
        SELECT
            o.order_id AS order_id,
            o.order_number AS order_number,
            o.order_date AS order_date,
            COALESCE(o.status, 'pending') AS status,
            o.total_amount AS total_amount,
            COALESCE(o.currency, 'CAD') AS currency,
            o.tracking_number AS tracking_number,
            'current' AS source
        FROM orders o
        WHERE o.client_id = :client_id
        ORDER BY o.order_date DESC, o.order_id DESC
    ");
    $currentStmt->execute([':client_id' => (int) $client_id]);
    foreach ($currentStmt->fetchAll() as $row) {
        $timeline[] = $row;
    }

    if ($customerCode !== '') {
        $legacyStmt = $pdo->prepare("
            SELECT
                MIN(sh.sale_id) AS order_id,
                sh.invoice_number AS order_number,
                MIN(sh.transaction_date) AS order_date,
                'completed' AS status,
                SUM(sh.amount) AS total_amount,
                'CAD' AS currency,
                '' AS tracking_number,
                'legacy' AS source
            FROM sales_history sh
            WHERE sh.client_id = :client_id
               OR (sh.client_id IS NULL AND sh.customer_code = :customer_code)
            GROUP BY sh.invoice_number, sh.customer_code
            ORDER BY MIN(sh.transaction_date) DESC, MIN(sh.sale_id) DESC
        ");
        $legacyStmt->execute([
            ':client_id' => (int) $client_id,
            ':customer_code' => $customerCode,
        ]);

        foreach ($legacyStmt->fetchAll() as $row) {
            $timeline[] = $row;
        }
    }

    usort($timeline, function ($a, $b) {
        $dateA = (string) ($a['order_date'] ?? '');
        $dateB = (string) ($b['order_date'] ?? '');

        if ($dateA !== $dateB) {
            return strcmp($dateB, $dateA);
        }

        return ((int) ($b['order_id'] ?? 0)) <=> ((int) ($a['order_id'] ?? 0));
    });

    return array_slice($timeline, 0, (int) $limit);
}

/**
 * Get order details with items
 * Uses viewer (read-only) credentials
 */
function getOrderDetails($order_id, $client_id = null) {
    $pdo = getViewerConnection();  // Read-only

    // Build query with optional client_id check for security
    $sql = "SELECT o.*, ol.*, c.business_name
            FROM orders o
            LEFT JOIN order_lines ol ON o.order_id = ol.order_id
            LEFT JOIN clients c ON o.client_id = c.client_id
            WHERE o.order_id = :order_id";

    if ($client_id !== null) {
        $sql .= " AND o.client_id = :client_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);

    if ($client_id !== null) {
        $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Create new user account
 * Uses ADMIN credentials for write operation
 */
function createUser($client_id, $username, $email, $password, $role = 'business') {
    $pdo = getAdminConnection();  // Admin credentials for writes
    
    // Validate username doesn't exist
    $check = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
    $check->execute([':username' => $username]);
    if ($check->fetch()) {
        throw new Exception("Username already exists");
    }
    
    // Validate email doesn't exist
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        throw new Exception("Email already exists");
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (client_id, username, email, password_hash, role, status, created_at)
        VALUES (:client_id, :username, :email, :password_hash, :role, 'active', NOW())
    ");
    
    return $stmt->execute([
        ':client_id' => $client_id,
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);
}

/**
 * Update user password
 * Uses ADMIN credentials for write operation
 */
function updateUserPassword($user_id, $new_password) {
    $pdo = getAdminConnection();  // Admin credentials for writes
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = :password_hash 
        WHERE user_id = :user_id
    ");
    
    return $stmt->execute([
        ':user_id' => $user_id,
        ':password_hash' => $password_hash
    ]);
}

/**
 * Get all clients (admin function)
 * Uses viewer (read-only) credentials
 */
function getAllClients($search = '', $limit = 100, $offset = 0) {
    $pdo = getViewerConnection();  // Read-only
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM users WHERE client_id = c.client_id) as user_count,
            (SELECT COUNT(*) FROM orders WHERE client_id = c.client_id) as order_count
            FROM clients c";
    
    if (!empty($search)) {
        $sql .= " WHERE c.business_name LIKE :search 
                  OR c.city LIKE :search 
                  OR c.province LIKE :search";
    }
    
    $sql .= " ORDER BY c.business_name LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get all users (admin function)
 * Uses viewer (read-only) credentials
 */
function getAllUsers($role = null, $limit = 100) {
    $pdo = getViewerConnection();  // Read-only
    
    $sql = "SELECT u.*, c.business_name, c.city, c.province 
            FROM users u
            LEFT JOIN clients c ON u.client_id = c.client_id";
    
    if ($role) {
        $sql .= " WHERE u.role = :role";
    }
    
    $sql .= " ORDER BY u.created_at DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    
    if ($role) {
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get valid email addresses for all active clients.
 * Uses viewer (read-only) credentials and deduplicates the results.
 */
function getAllActiveClientEmails() {
    try {
        $pdo = getViewerConnection();

        $stmt = $pdo->prepare(
            "SELECT DISTINCT c.email
             FROM clients c
             WHERE c.email IS NOT NULL
               AND TRIM(c.email) <> ''
               AND c.status = 'Active'
             ORDER BY c.email"
        );

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $emails = [];
        foreach ($rows as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    } catch (Exception $e) {
        error_log('Failed to get all active client emails: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get valid email addresses for active clients with purchase history.
 * Uses viewer (read-only) credentials and deduplicates the results.
 */
function getPurchaseHistoryRecipients() {
    try {
        $pdo = getViewerConnection();

        $stmt = $pdo->prepare(
            "SELECT DISTINCT c.email
             FROM clients c
             WHERE c.email IS NOT NULL
               AND TRIM(c.email) <> ''
               AND c.status = 'Active'
               AND (
                   EXISTS (
                       SELECT 1
                       FROM orders o
                       WHERE o.client_id = c.client_id
                   )
                   OR EXISTS (
                       SELECT 1
                       FROM sales_history sh
                       WHERE sh.client_id = c.client_id
                   )
               )
             ORDER BY c.email"
        );

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $emails = [];
        foreach ($rows as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    } catch (Exception $e) {
        error_log('Failed to get purchase history recipients: ' . $e->getMessage());
        return [];
    }
}
?>
