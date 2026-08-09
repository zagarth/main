<?php
/**
 * User Dashboard
 * Business users can view their information and orders
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db_config.php';

$sessionTimeoutSeconds = 3600;
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

if ((time() - $_SESSION['last_activity']) > $sessionTimeoutSeconds) {
    session_destroy();
    header('Location: /admin/login.php?session_expired=1');
    exit;
}

$_SESSION['last_activity'] = time();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Redirect admins to admin panel
if ($_SESSION['role'] === 'admin') {
    header('Location: /admin/index.php');
    exit;
}

// Get user and client information
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

// Get orders
$orders = getClientOrders($user['client_id']);

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Cadman Manufacturing</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        /* Password change modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        .modal-box h2 { color: #2c3e50; margin-bottom: 8px; font-size: 1.3rem; }
        .modal-box p  { color: #7f8c8d; margin-bottom: 24px; font-size: 0.95rem; }
        .modal-box input {
            width: 100%; padding: 10px 14px; margin-bottom: 14px;
            border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; box-sizing: border-box;
        }
        .modal-box input:focus { outline: none; border-color: #667eea; }
        .modal-btn {
            width: 100%; padding: 11px; background: #667eea; color: white;
            border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600;
        }
        .modal-btn:hover { background: #5a67d8; }
        .modal-error { color: #c0392b; font-size: 0.9rem; margin-bottom: 10px; min-height: 1.2em; }
        .modal-skip { display: block; text-align: center; margin-top: 12px; color: #95a5a6; font-size: 0.88rem; cursor: pointer; }
        .modal-skip:hover { color: #7f8c8d; }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-banner {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-banner h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            color: #7f8c8d;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .invoice-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .invoice-btn:hover {
            background: #4f46e5;
        }

        .invoice-status {
            margin-top: 12px;
            color: #6366f1;
            font-size: 0.95em;
            min-height: 1.2em;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏭 Cadman Manufacturing Portal</h1>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="sessionCountdown" style="font-size: 14px; color: rgba(255,255,255,0.95); background: rgba(255,255,255,0.15); padding: 7px 12px; border-radius: 20px; min-width: 54px; text-align: center;">60:00</span>
                <a href="/" style="color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; border: 1px solid rgba(255,255,255,0.4); padding: 7px 16px; border-radius: 20px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">← Home</a>
                <a href="#" onclick="document.getElementById('pwModal').classList.add('active'); return false;" style="color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; border: 1px solid rgba(255,255,255,0.4); padding: 7px 16px; border-radius: 20px;">🔑 Change Password</a>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-banner">
            <h2>Welcome back, <?php echo htmlspecialchars($user['business_name'] ?: $user['username']); ?>!</h2>
            <p>View your account information and manage your orders below.</p>
        </div>
        
        <div class="stat-cards">
            <div class="stat-card">
                <div class="number"><?php echo count($orders); ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $pending = array_filter($orders, fn($o) => $o['status'] === 'pending');
                    echo count($pending);
                    ?>
                </div>
                <div class="label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $total = array_sum(array_column($orders, 'total_amount'));
                    echo '$' . number_format($total, 2);
                    ?>
                </div>
                <div class="label">Total Value</div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>📋 Business Information</h3>
                <div class="info-row">
                    <span class="info-label">Business Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['business_name'] ?: 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['customer_code'] ?: 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($user['city'] ? "{$user['city']}, {$user['province']}" : 'N/A'); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['address'] ?: 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #27ae60; font-weight: bold;">Active</span>
                </div>
            </div>
            
            <div class="card">
                <h3>📊 Account Details</h3>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Type:</span>
                    <span class="info-value">Business User</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value">
                        <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'First login'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>📦 Recent Orders</h3>
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>No orders found. Start shopping to place your first order!</p>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php $statusClass = strtolower((string)($order['status'] ?? 'pending')); ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars(ucfirst($statusClass)); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format((float)($order['total_amount'] ?? 0), 2); ?> <?php echo htmlspecialchars($order['currency'] ?? 'CAD'); ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span><?php echo htmlspecialchars((($order['tracking_number'] ?? '') ?: (($order['source'] ?? 'current') === 'legacy' ? $order['order_number'] : '-'))); ?></span>
                                        <?php if (($order['source'] ?? 'current') === 'legacy'): ?>
                                            <span class="status status-completed" style="background:#e0f2fe;color:#0369a1;">Legacy</span>
                                        <?php endif; ?>
                                        <button type="button" class="invoice-btn" data-order-id="<?php echo (int)$order['order_id']; ?>" data-source="<?php echo htmlspecialchars($order['source'] ?? 'current'); ?>" data-order-number="<?php echo htmlspecialchars($order['order_number'] ?? ''); ?>">Reprint</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="invoiceStatus" class="invoice-status"></div>
            <?php endif; ?>
        </div>
    </div>
    <div id="pwModal" class="modal-overlay<?php echo ($user['force_password_change'] ?? 0) ? ' active' : ''; ?>">
        <div class="modal-box">
            <h2>🔑 <?php echo ($user['force_password_change'] ?? 0) ? 'Password Change Required' : 'Change Password'; ?></h2>
            <p><?php echo ($user['force_password_change'] ?? 0) ? 'Your password has been reset. Please choose a new password to continue.' : 'Enter your current and new password below.'; ?></p>
            <div id="pwError" class="modal-error"></div>
            <form id="pwForm">
                <input type="password" id="pwCurrent" placeholder="Current password" autocomplete="current-password" required>
                <input type="password" id="pwNew" placeholder="New password (min 8 characters)" autocomplete="new-password" required minlength="8">
                <input type="password" id="pwConfirm" placeholder="Confirm new password" autocomplete="new-password" required>
                <button type="submit" class="modal-btn">Update Password</button>
            </form>
            <?php if (!($user['force_password_change'] ?? 0)): ?>
            <span class="modal-skip" onclick="document.getElementById('pwModal').classList.remove('active')">Cancel</span>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const sessionTimeoutSeconds = 3600;
        let sessionTimeRemaining = sessionTimeoutSeconds;

        function updateSessionCountdown() {
            const countdownEl = document.getElementById('sessionCountdown');
            if (!countdownEl) {
                return;
            }

            const minutes = Math.floor(sessionTimeRemaining / 60);
            const seconds = sessionTimeRemaining % 60;
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        function resetSessionTimer() {
            sessionTimeRemaining = sessionTimeoutSeconds;
            updateSessionCountdown();
        }

        setInterval(function() {
            if (sessionTimeRemaining > 0) {
                sessionTimeRemaining--;
                updateSessionCountdown();
            }
        }, 1000);

        document.addEventListener('mousemove', resetSessionTimer);
        document.addEventListener('keydown', resetSessionTimer);
        document.addEventListener('click', function(event) {
            const button = event.target.closest('.invoice-btn');
            if (!button) {
                return;
            }

            event.preventDefault();
            const orderId = button.getAttribute('data-order-id');
            const source = button.getAttribute('data-source') || 'current';
            const orderNumber = button.getAttribute('data-order-number') || '';
            const statusEl = document.getElementById('invoiceStatus');
            if (statusEl) {
                statusEl.textContent = 'Opening invoice preview...';
            }

            const token = Math.random().toString(36).slice(2) + Date.now().toString(36);
            window.__pendingInvoiceCleanup = { orderId, token };
            window.open('reprint_invoice.php?order_id=' + encodeURIComponent(orderId) + '&token=' + encodeURIComponent(token) + '&source=' + encodeURIComponent(source) + '&order_number=' + encodeURIComponent(orderNumber), '_blank');
        });

        window.addEventListener('beforeunload', function() {
            const cleanup = window.__pendingInvoiceCleanup;
            if (!cleanup) {
                return;
            }

            const url = 'reprint_invoice.php?cleanup=1&order_id=' + encodeURIComponent(cleanup.orderId) + '&token=' + encodeURIComponent(cleanup.token);
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url);
            }
        });

        <?php if ($user['force_password_change'] ?? 0): ?>
        // Block Escape and clicks outside modal until password is changed
        const forceChange = true;
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && forceChange) e.stopImmediatePropagation();
        }, true);
        document.getElementById('pwModal').addEventListener('click', function(e) {
            if (e.target === this && forceChange) e.stopPropagation();
        });
        <?php endif; ?>

        document.getElementById('pwForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const errEl = document.getElementById('pwError');
            errEl.textContent = '';
            const current = document.getElementById('pwCurrent').value;
            const next    = document.getElementById('pwNew').value;
            const confirm = document.getElementById('pwConfirm').value;
            if (next !== confirm) { errEl.textContent = 'New passwords do not match.'; return; }
            if (next.length < 8)  { errEl.textContent = 'Password must be at least 8 characters.'; return; }
            const res = await fetch('change_password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({current_password: current, new_password: next})
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('pwModal').classList.remove('active');
                document.getElementById('pwError').textContent = '';
            } else {
                errEl.textContent = data.error || 'Password update failed.';
            }
        });
    </script>
</body>
</html>
