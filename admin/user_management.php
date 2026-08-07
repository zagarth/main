<?php
/**
 * User Management
 * Admin panel to create and manage business user accounts
 */

session_start();
require_once __DIR__ . '/../includes/db_config.php';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $client_id = $_POST['client_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $email && $password && $client_id) {
        try {
            if (createUser($client_id, $username, $email, $password, 'business')) {
                $message = "✅ User account created successfully for $username";
            } else {
                $error = "❌ Failed to create user account";
            }
        } catch (PDOException $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    } else {
        $error = "❌ Please fill in all required fields";
    }
}

// Get all clients without user accounts
$clients_without_users = $pdo->query("
    SELECT c.* 
    FROM clients c
    LEFT JOIN users u ON c.client_id = u.client_id
    WHERE u.user_id IS NULL
    ORDER BY c.business_name
")->fetchAll();

// Get all business users
$users = $pdo->query("
    SELECT u.*, c.business_name, c.city, c.province
    FROM users u
    LEFT JOIN clients c ON u.client_id = c.client_id
    WHERE u.role = 'business'
    ORDER BY u.created_at DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Cadman Manufacturing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: white;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #0f5132;
        }
        
        .message.error {
            background: #f8d7da;
            color: #842029;
            border-left: 4px solid #842029;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input, select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
        }
        
        td {
            padding: 12px;
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
        
        .status-pending {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👥 User Management</h1>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>➕ Create New User Account</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="client_id">Select Business Client *</label>
                        <select name="client_id" id="client_id" required>
                            <option value="">Choose a client...</option>
                            <?php foreach ($clients_without_users as $client): ?>
                                <option value="<?php echo $client['client_id']; ?>">
                                    <?php echo htmlspecialchars($client['business_name']); ?> 
                                    (<?php echo htmlspecialchars($client['city'] . ', ' . $client['province']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                </div>
                
                <button type="submit" name="create_user" class="btn">Create User Account</button>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Existing User Accounts</h2>
            <?php if (empty($users)): ?>
                <p style="color: #95a5a6; text-align: center; padding: 40px;">
                    No user accounts created yet. Create your first account above.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Business</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['business_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['city'] ? "{$user['city']}, {$user['province']}" : 'N/A'); ?></td>
                                <td class="status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
