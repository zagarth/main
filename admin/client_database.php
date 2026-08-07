<?php
/**
 * Client Database Admin Panel
 * View and manage client information
 */

session_start();

// Simple authentication (enhance this with proper auth system)
$admin_password = 'CadmanAdmin2025!';
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
    } else {
        $login_error = 'Invalid password';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Database configuration
$db_config = [
    'host' => 'localhost',
    'database' => 'CadmanClients',
    'username' => 'cadman_viewer', // Read-only user for viewing
    'password' => 'View2025!Cadman'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Database - Cadman Manufacturing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .login-form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            max-width: 400px;
            margin: 100px auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .login-form h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .login-form button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .login-form button:hover {
            background: #5568d3;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .filters input, .filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .filters button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #95a5a6;
        }
        
        .coordinates {
            font-family: monospace;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <?php if (!$is_authenticated): ?>
        <!-- Login Form -->
        <div class="login-form">
            <h2>🔐 Client Database Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter admin password" required autofocus>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Admin Panel -->
        <div class="container">
            <div class="header">
                <h1>📊 Client Database - Cadman Manufacturing</h1>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
            
            <?php
            // Connect to database
            try {
                $pdo = new PDO(
                    "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
                    $db_config['username'],
                    $db_config['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Get statistics
                $total_clients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
                $active_clients = $pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'Active'")->fetchColumn();
                $provinces = $pdo->query("SELECT COUNT(DISTINCT province) FROM clients")->fetchColumn();
                $cities = $pdo->query("SELECT COUNT(DISTINCT city) FROM clients")->fetchColumn();
                
                // Get filter parameters
                $search = $_GET['search'] ?? '';
                $province_filter = $_GET['province'] ?? '';
                $status_filter = $_GET['status'] ?? '';
                
                // Build query
                $where = [];
                $params = [];
                
                if ($search) {
                    $where[] = "(business_name LIKE :search OR city LIKE :search OR address LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                if ($province_filter) {
                    $where[] = "province = :province";
                    $params[':province'] = $province_filter;
                }
                
                if ($status_filter) {
                    $where[] = "status = :status";
                    $params[':status'] = $status_filter;
                }
                
                $where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
                
                $sql = "SELECT * FROM clients $where_clause ORDER BY business_name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get unique provinces for filter
                $provinces_list = $pdo->query("SELECT DISTINCT province FROM clients ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);
                
            } catch (PDOException $e) {
                echo '<div class="error">Database Error: ' . $e->getMessage() . '</div>';
                $clients = [];
            }
            ?>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Clients</h3>
                    <div class="number"><?php echo number_format($total_clients); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Clients</h3>
                    <div class="number"><?php echo number_format($active_clients); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Provinces</h3>
                    <div class="number"><?php echo $provinces; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Cities</h3>
                    <div class="number"><?php echo $cities; ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search by name, city, or address..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="province">
                        <option value="">All Provinces</option>
                        <?php foreach ($provinces_list as $prov): ?>
                            <option value="<?php echo htmlspecialchars($prov); ?>" <?php echo $province_filter === $prov ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit">Filter</button>
                </form>
            </div>
            
            <!-- Clients Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>City</th>
                            <th>Province</th>
                            <th>Phone</th>
                            <th>Coordinates</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #95a5a6;">
                                    No clients found. Run the import script to populate the database.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($client['business_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($client['city']); ?></td>
                                    <td><?php echo htmlspecialchars($client['province']); ?></td>
                                    <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                    <td class="coordinates">
                                        <?php if ($client['latitude'] && $client['longitude']): ?>
                                            <?php echo number_format($client['latitude'], 6); ?>, <?php echo number_format($client['longitude'], 6); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status-<?php echo strtolower($client['status']); ?>">
                                        <?php echo htmlspecialchars($client['status']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
