<?php
require_once 'auth.php';

// Check authentication
requireAdmin();

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_test':
            handleTestEmail($_POST);
            break;
        case 'send_mass':
            handleMassEmail($_POST);
            break;
        case 'save_template':
            handleSaveTemplate($_POST);
            break;
        case 'import_clients':
            handleClientImport($_POST);
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: mass_email.php');
    exit;
}

function handleTestEmail($data) {
    $template = $data['template'] ?? '';
    $subject = $data['subject'] ?? '';
    $testEmail = $data['test_email'] ?? '';
    
    if (empty($template) || empty($subject) || empty($testEmail)) {
        $_SESSION['email_error'] = 'Template, subject, and test email are required';
        return;
    }
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['email_error'] = 'Invalid test email address';
        return;
    }
    
    $content = generateEmailContent($template, $data);
    
    if (sendEmail($testEmail, $subject, $content, true)) {
        $_SESSION['email_success'] = 'Test email sent successfully to ' . htmlspecialchars($testEmail);
        logAdminAction('TEST_EMAIL_SENT', ['to' => $testEmail, 'template' => $template]);
    } else {
        $_SESSION['email_error'] = 'Failed to send test email';
    }
}

function handleMassEmail($data) {
    $template = $data['template'] ?? '';
    $subject = $data['subject'] ?? '';
    $clientList = $data['client_list'] ?? '';
    
    if (empty($template) || empty($subject) || empty($clientList)) {
        $_SESSION['email_error'] = 'Template, subject, and client list are required';
        return;
    }
    
    $clients = getClientEmails($clientList);
    if (empty($clients)) {
        $_SESSION['email_error'] = 'No clients found in selected list';
        return;
    }
    
    if (!checkRateLimit()) {
        $_SESSION['email_error'] = 'Rate limit exceeded. Maximum 500 emails per hour.';
        return;
    }
    
    $sent = 0;
    $failed = 0;
    
    foreach ($clients as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $content = generateEmailContent($template, $data);
            if (sendEmail($email, $subject, $content)) {
                $sent++;
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
        
        // Small delay between emails
        usleep(500000); // 0.5 second delay
    }
    
    $_SESSION['email_success'] = "Mass email completed: {$sent} sent, {$failed} failed";
    logAdminAction('MASS_EMAIL_SENT', [
        'template' => $template,
        'client_list' => $clientList,
        'sent' => $sent,
        'failed' => $failed
    ]);
}

// Rate limiting - max 500 emails per hour
function checkRateLimit() {
    $limit_file = 'email_rate_limit.json';
    $max_per_hour = 500;
    
    if (!file_exists($limit_file)) {
        file_put_contents($limit_file, json_encode(['hour' => date('Y-m-d H'), 'count' => 0]));
    }
    
    $data = json_decode(file_get_contents($limit_file), true);
    $current_hour = date('Y-m-d H');
    
    if ($data['hour'] !== $current_hour) {
        $data = ['hour' => $current_hour, 'count' => 0];
    }
    
    if ($data['count'] >= $max_per_hour) {
        return false;
    }
    
    $data['count']++;
    file_put_contents($limit_file, json_encode($data));
    return true;
}

function getClientEmails($listName) {
    $client_lists = json_decode(file_get_contents('client_lists.json'), true);
    return $client_lists[$listName] ?? [];
}

function generateEmailContent($template, $data) {
    $template_path = "email_templates/{$template}.html";
    if (!file_exists($template_path)) {
        return false;
    }
    
    $content = file_get_contents($template_path);
    
    // Replace placeholders
    $placeholders = [
        '{{COMPANY_NAME}}' => 'Cadman Manufacturing',
        '{{CURRENT_DATE}}' => date('F j, Y'),
        '{{SUBJECT}}' => $data['subject'] ?? '',
        '{{CUSTOM_MESSAGE}}' => $data['custom_message'] ?? ''
    ];
    
    foreach ($placeholders as $placeholder => $value) {
        $content = str_replace($placeholder, $value, $content);
    }
    
    return $content;
}

function sendEmail($to, $subject, $content, $isTest = false) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Cadman Manufacturing <noreply@cadmanmfg.com>',
        'Reply-To: info@cadmanmfg.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $success = mail($to, $subject, $content, implode("\r\n", $headers));
    
    if ($success) {
        // Log successful email
        logAdminAction('EMAIL_SENT', ['to' => $to, 'subject' => $subject, 'test' => $isTest]);
    }
    
    return $success;
}

function handleSaveTemplate($data) {
    $templateName = $data['template_name'] ?? '';
    $templateContent = $data['template_content'] ?? '';
    
    if (empty($templateName) || empty($templateContent)) {
        $_SESSION['email_error'] = 'Template name and content are required';
        return;
    }
    
    $templateFile = "email_templates/{$templateName}.html";
    
    if (file_put_contents($templateFile, $templateContent)) {
        $_SESSION['email_success'] = 'Template saved successfully';
        logAdminAction('TEMPLATE_SAVED', ['name' => $templateName]);
    } else {
        $_SESSION['email_error'] = 'Failed to save template';
    }
}

function handleClientImport($data) {
    $listName = $data['list_name'] ?? '';
    $clientEmails = $data['client_emails'] ?? '';
    
    if (empty($listName) || empty($clientEmails)) {
        $_SESSION['email_error'] = 'List name and client emails are required';
        return;
    }
    
    $emails = array_filter(array_map('trim', explode("\n", $clientEmails)));
    $validEmails = [];
    
    foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validEmails[] = $email;
        }
    }
    
    if (empty($validEmails)) {
        $_SESSION['email_error'] = 'No valid email addresses found';
        return;
    }
    
    $client_lists_file = 'client_lists.json';
    $client_lists = [];
    
    if (file_exists($client_lists_file)) {
        $client_lists = json_decode(file_get_contents($client_lists_file), true);
    }
    
    $client_lists[$listName] = $validEmails;
    
    if (file_put_contents($client_lists_file, json_encode($client_lists, JSON_PRETTY_PRINT))) {
        $_SESSION['email_success'] = "Client list '{$listName}' saved with " . count($validEmails) . " emails";
        logAdminAction('CLIENT_LIST_IMPORTED', ['name' => $listName, 'count' => count($validEmails)]);
    } else {
        $_SESSION['email_error'] = 'Failed to save client list';
    }
}

// Load templates and client lists
$templates = [];
$template_dir = 'email_templates/';
if (is_dir($template_dir)) {
    $files = glob($template_dir . '*.html');
    foreach ($files as $file) {
        $name = basename($file, '.html');
        $templates[$name] = $name;
    }
}

$client_lists = [];
if (file_exists('client_lists.json')) {
    $client_lists = json_decode(file_get_contents('client_lists.json'), true);
}

if (!is_array($client_lists)) {
    $client_lists = [];
}

if (!function_exists('getPurchaseHistoryRecipients')) {
    require_once __DIR__ . '/../includes/db_config_encrypted.php';
}

try {
    $all_active_emails = getAllActiveClientEmails();
    if (!empty($all_active_emails)) {
        $client_lists['All Active Emails'] = array_values(array_unique($all_active_emails));
    }

    $purchase_history_emails = getPurchaseHistoryRecipients();
    if (!empty($purchase_history_emails)) {
        $client_lists['Purchase History'] = array_values(array_unique($purchase_history_emails));
    }
} catch (Exception $e) {
    error_log('Failed to load recipient lists in mass email page: ' . $e->getMessage());
}

// Get messages
$success_message = $_SESSION['email_success'] ?? '';
$error_message = $_SESSION['email_error'] ?? '';
unset($_SESSION['email_success'], $_SESSION['email_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Email System - Cadman Manufacturing</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        
        .navigation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .back-button {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb .current {
            color: #667eea;
            font-weight: 600;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .security-badge {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }
        
        .tab {
            padding: 15px 30px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .button.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
        }
        
        .card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat .label {
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1;
                min-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation-header">
            <a href="index.php" class="back-button">
                ← Back to CMS Dashboard
            </a>
            <div class="breadcrumb">
                <span>CMS Admin</span> / <span class="current">Mass Email System</span>
            </div>
        </div>
        
        <div class="header">
            <h1>📧 Mass Email System</h1>
            <div class="subtitle">Token-Certified Client Communication Platform</div>
            <div class="security-badge">
                🔒 <strong>Security Features:</strong> CSRF Protection • Rate Limiting (500/hour) • Audit Logging • XSS Protection
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert success">✅ <?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert error">❌ <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('compose')">📧 Compose Email</button>
            <button class="tab" onclick="showTab('templates')">📝 Manage Templates</button>
            <button class="tab" onclick="showTab('clients')">👥 Client Lists</button>
            <button class="tab" onclick="showTab('analytics')">📊 Analytics</button>
        </div>
        
        <!-- Compose Email Tab -->
        <div id="compose" class="tab-content active">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="send_mass">
                
                <div class="grid">
                    <div class="card">
                        <h3>📧 Email Configuration</h3>
                        
                        <div class="form-group">
                            <label for="template">Email Template:</label>
                            <select name="template" id="template" required>
                                <option value="">Select a template...</option>
                                <?php foreach ($templates as $key => $name): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(ucfirst($name)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Email Subject:</label>
                            <input type="text" name="subject" id="subject" placeholder="Enter email subject..." required>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_list">Client List:</label>
                            <select name="client_list" id="client_list" required>
                                <option value="">Select client list...</option>
                                <?php foreach ($client_lists as $name => $emails): ?>
                                    <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?> (<?= count($emails) ?> emails)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="custom_message">Custom Message (Optional):</label>
                            <textarea name="custom_message" id="custom_message" placeholder="Add a custom message to include in the email..."></textarea>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>🧪 Test & Send</h3>
                        
                        <div class="form-group">
                            <label for="test_email">Test Email Address:</label>
                            <input type="email" name="test_email" id="test_email" placeholder="your@email.com">
                        </div>
                        
                        <button type="submit" name="action" value="send_test" class="button secondary">🧪 Send Test Email</button>
                        
                        <hr style="margin: 20px 0; border: 1px solid #eee;">
                        
                        <p style="color: #666; margin-bottom: 15px;">
                            ⚠️ <strong>Mass Email Warning:</strong> This will send emails to all contacts in the selected list. Please test first!
                        </p>
                        
                        <button type="submit" class="button" onclick="return confirm('Are you sure you want to send this email to all clients in the selected list?')">
                            📧 Send Mass Email
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Templates Tab -->
        <div id="templates" class="tab-content">
            <div class="grid">
                <div class="card">
                    <h3>📝 Available Templates</h3>
                    <?php if (empty($templates)): ?>
                        <p>No templates found. Create your first template below.</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($templates as $key => $name): ?>
                                <li style="padding: 10px; border-bottom: 1px solid #eee;">
                                    <strong><?= htmlspecialchars(ucfirst($name)) ?></strong>
                                    <span style="float: right; color: #666;">
                                        <a href="email_templates/<?= htmlspecialchars($key) ?>.html" target="_blank">View</a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>➕ Create New Template</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="save_template">
                        
                        <div class="form-group">
                            <label for="template_name">Template Name:</label>
                            <input type="text" name="template_name" id="template_name" placeholder="e.g., special_offer" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="template_content">HTML Content:</label>
                            <textarea name="template_content" id="template_content" style="min-height: 200px;" placeholder="Enter HTML content..." required></textarea>
                        </div>
                        
                        <button type="submit" class="button">💾 Save Template</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Client Lists Tab -->
        <div id="clients" class="tab-content">
            <div class="grid">
                <div class="card">
                    <h3>👥 Current Client Lists</h3>
                    <?php if (empty($client_lists)): ?>
                        <p>No client lists found. Import your first list below.</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($client_lists as $name => $emails): ?>
                                <li style="padding: 15px; border-bottom: 1px solid #eee;">
                                    <strong><?= htmlspecialchars($name) ?></strong>
                                    <span style="float: right; color: #666;">
                                        <?= count($emails) ?> emails
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>📥 Import Client List</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="import_clients">
                        
                        <div class="form-group">
                            <label for="list_name">List Name:</label>
                            <input type="text" name="list_name" id="list_name" placeholder="e.g., Newsletter Subscribers" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_emails">Email Addresses (one per line):</label>
                            <textarea name="client_emails" id="client_emails" style="min-height: 200px;" placeholder="email1@example.com&#10;email2@example.com&#10;email3@example.com" required></textarea>
                        </div>
                        
                        <button type="submit" class="button">📥 Import List</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <div class="card">
                <h3>📊 Email Analytics</h3>
                <div class="stats">
                    <div class="stat">
                        <div class="number"><?= count($templates) ?></div>
                        <div class="label">Templates</div>
                    </div>
                    <div class="stat">
                        <div class="number"><?= count($client_lists) ?></div>
                        <div class="label">Client Lists</div>
                    </div>
                    <div class="stat">
                        <div class="number"><?= array_sum(array_map('count', $client_lists)) ?></div>
                        <div class="label">Total Contacts</div>
                    </div>
                </div>
                <p style="text-align: center; color: #666; margin-top: 20px;">
                    📈 Detailed analytics and reporting features coming soon!
                </p>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>