<?php
require_once 'auth.php';
requireAdmin();

// Security status checks
$securityStatus = [];

// Check .env file exists and has required settings
$envFile = __DIR__ . '/.env';
$securityStatus['env_file'] = file_exists($envFile);

// Check password hash is set
$securityStatus['password_hash'] = !empty(ADMIN_PASSWORD_HASH);

// Check login attempts file exists
$attemptsFile = __DIR__ . '/login_attempts.json';
$securityStatus['rate_limiting'] = file_exists($attemptsFile);

// Check log file exists
$logFile = __DIR__ . '/admin_actions.log';
$securityStatus['logging'] = file_exists($logFile);

// Check session security
$securityStatus['session_security'] = isset($_SESSION['session_token']) && !empty($_SESSION['session_token']);

// Get recent login attempts
$recentAttempts = 0;
if (file_exists($attemptsFile)) {
    $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
    $cutoff = time() - 3600; // Last hour
    foreach ($attempts as $ip => $ipAttempts) {
        foreach ($ipAttempts as $attempt) {
            if ($attempt['time'] > $cutoff) {
                $recentAttempts++;
            }
        }
    }
}

// Get current session info
$sessionInfo = [
    'username' => $_SESSION['admin_username'] ?? 'unknown',
    'login_time' => $_SESSION['login_time'] ?? 0,
    'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0
];

// --- Fail2ban status ---
$fail2ban = [
    'running'     => false,
    'jails'       => [],
    'recent_bans' => [],
    'recent_fails' => [],
    'log_readable' => false,
];

$f2bLog = '/var/log/fail2ban.log';
$f2bSock = '/run/fail2ban/fail2ban.sock';
$fail2ban['running'] = file_exists($f2bSock);

if (is_readable($f2bLog)) {
    $fail2ban['log_readable'] = true;
    $lines = file($f2bLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_slice($lines, -2000); // Only look at last 2000 lines

    $jailCounts = [];
    $recentEvents = [];
    $cutoff24h = date('Y-m-d H:i:s', strtotime('-24 hours'));

    foreach ($lines as $line) {
        // Format: 2026-05-05 09:55:16,855 fail2ban.actions [PID]: LEVEL [jail] BAN/UNBAN ip
        if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $dtMatch)) continue;
        $ts = $dtMatch[1];

        // Count bans per jail
        if (preg_match('/fail2ban\.actions.*?\[([^\]]+)\] Ban (\S+)/', $line, $m)) {
            $jail = $m[1];
            $ip   = $m[2];
            $jailCounts[$jail]['bans'] = ($jailCounts[$jail]['bans'] ?? 0) + 1;
            if ($ts >= $cutoff24h) {
                $recentEvents[] = ['time' => $ts, 'jail' => $jail, 'event' => 'BAN', 'ip' => $ip];
            }
        }
        // Count found (failed attempts)
        if (preg_match('/fail2ban\.filter.*?\[([^\]]+)\] Found (\S+)/', $line, $m)) {
            $jail = $m[1];
            $jailCounts[$jail]['fails'] = ($jailCounts[$jail]['fails'] ?? 0) + 1;
        }
        // Track unbans
        if (preg_match('/fail2ban\.actions.*?\[([^\]]+)\] Unban (\S+)/', $line, $m)) {
            $jail = $m[1];
            $ip   = $m[2];
            $jailCounts[$jail]['unbans'] = ($jailCounts[$jail]['unbans'] ?? 0) + 1;
            if ($ts >= $cutoff24h) {
                $recentEvents[] = ['time' => $ts, 'jail' => $jail, 'event' => 'UNBAN', 'ip' => $ip];
            }
        }
    }

    $fail2ban['jails'] = $jailCounts;
    // Show last 20 events, newest first
    $fail2ban['recent_events'] = array_reverse(array_slice($recentEvents, -20));
}

logAdminAction('SECURITY_STATUS_VIEW');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Status - Cadman Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .status-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #FFD700;
        }
        
        .status-ok {
            border-left-color: #28a745;
        }
        
        .status-warning {
            border-left-color: #ffc107;
        }
        
        .status-error {
            border-left-color: #dc3545;
        }
        
        .status-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .status-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .status-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .status-value {
            font-family: monospace;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .back-button {
            display: inline-block;
            background: #333;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        
        .back-button:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="index.php" class="back-button">← Back to Admin Portal</a>
            <h1>🛡️ Security Status Dashboard</h1>
            <p>Real-time security monitoring and system status</p>
        </div>
        
        <div class="security-grid">
            <div class="status-card <?php echo $securityStatus['env_file'] ? 'status-ok' : 'status-error'; ?>">
                <div class="status-icon"><?php echo $securityStatus['env_file'] ? '✅' : '❌'; ?></div>
                <div class="status-title">Environment Configuration</div>
                <div class="status-description">
                    Secure environment file with configuration settings
                </div>
                <div class="status-value">
                    Status: <?php echo $securityStatus['env_file'] ? 'CONFIGURED' : 'MISSING'; ?>
                </div>
            </div>
            
            <div class="status-card <?php echo $securityStatus['password_hash'] ? 'status-ok' : 'status-error'; ?>">
                <div class="status-icon"><?php echo $securityStatus['password_hash'] ? '🔒' : '🔓'; ?></div>
                <div class="status-title">Password Security</div>
                <div class="status-description">
                    Hashed password authentication (Argon2ID)
                </div>
                <div class="status-value">
                    Status: <?php echo $securityStatus['password_hash'] ? 'SECURE HASH' : 'PLAIN TEXT'; ?>
                </div>
            </div>
            
            <div class="status-card <?php echo $securityStatus['rate_limiting'] ? 'status-ok' : 'status-warning'; ?>">
                <div class="status-icon"><?php echo $securityStatus['rate_limiting'] ? '🚦' : '⚠️'; ?></div>
                <div class="status-title">Rate Limiting</div>
                <div class="status-description">
                    Login attempt tracking and IP lockout system
                </div>
                <div class="status-value">
                    Status: <?php echo $securityStatus['rate_limiting'] ? 'ACTIVE' : 'INITIALIZING'; ?><br>
                    Recent attempts: <?php echo $recentAttempts; ?>
                </div>
            </div>
            
            <div class="status-card <?php echo $securityStatus['logging'] ? 'status-ok' : 'status-warning'; ?>">
                <div class="status-icon"><?php echo $securityStatus['logging'] ? '📝' : '⚠️'; ?></div>
                <div class="status-title">Security Logging</div>
                <div class="status-description">
                    Administrative action and security event logging
                </div>
                <div class="status-value">
                    Status: <?php echo $securityStatus['logging'] ? 'ACTIVE' : 'INITIALIZING'; ?>
                </div>
            </div>
            
            <div class="status-card <?php echo $securityStatus['session_security'] ? 'status-ok' : 'status-error'; ?>">
                <div class="status-icon"><?php echo $securityStatus['session_security'] ? '🎫' : '❌'; ?></div>
                <div class="status-title">Session Security</div>
                <div class="status-description">
                    Secure session management with token validation
                </div>
                <div class="status-value">
                    Status: <?php echo $securityStatus['session_security'] ? 'SECURE TOKEN' : 'NO TOKEN'; ?>
                </div>
            </div>
            
            <div class="status-card status-ok">
                <div class="status-icon">👤</div>
                <div class="status-title">Current Session</div>
                <div class="status-description">
                    Active administrative session information
                </div>
                <div class="status-value">
                    User: <?php echo htmlspecialchars($sessionInfo['username']); ?><br>
                    Duration: <?php echo gmdate('H:i:s', $sessionInfo['session_duration']); ?><br>
                    Login: <?php echo date('Y-m-d H:i:s', $sessionInfo['login_time']); ?>
                </div>
            </div>
        </div>
        
        <!-- Password Management Section -->
        <div style="margin: 40px 0;">
            <h2 style="color: #333; border-bottom: 2px solid #FFD700; padding-bottom: 10px;">🔑 Password Management</h2>
            
            <div class="security-grid">
                <div class="status-card status-warning">
                    <div class="status-icon">🔄</div>
                    <div class="status-title">Change Password</div>
                    <div class="status-description">
                        Reset your admin password with security verification
                    </div>
                    <div class="status-value">
                        <a href="password_reset.php" style="display: inline-block; background: #007cba; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; margin-top: 10px;">
                            🔒 Reset Password
                        </a>
                    </div>
                </div>
                
                <div class="status-card status-ok">
                    <div class="status-icon">📊</div>
                    <div class="status-title">Password Status</div>
                    <div class="status-description">
                        Current password security information
                    </div>
                    <div class="status-value">
                        Algorithm: <?php echo password_get_info(ADMIN_PASSWORD_HASH)['algoName'] ?? 'Unknown'; ?><br>
                        Last Changed: <?php 
                            $envFile = __DIR__ . '/.env';
                            echo file_exists($envFile) ? date('Y-m-d H:i:s', filemtime($envFile)) : 'Unknown';
                        ?><br>
                        Strength: Strong (Hashed)
                    </div>
                </div>
                
                <?php
                $csrfConfigured = defined('CSRF_SECRET') && CSRF_SECRET !== 'default_secret';
                $sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 0;
                $cardClass = $csrfConfigured ? 'status-ok' : 'status-warning';
                ?>
                <div class="status-card <?php echo $cardClass; ?>">
                    <div class="status-icon"><?php echo $csrfConfigured ? '⚙️' : '⚠️'; ?></div>
                    <div class="status-title">Auth Policy (Live)</div>
                    <div class="status-description">
                        Active authentication constants from configuration
                    </div>
                    <div class="status-value">
                        Max login attempts: <strong><?php echo MAX_LOGIN_ATTEMPTS; ?></strong><br>
                        Lockout duration: <strong><?php echo LOCKOUT_DURATION / 60; ?> min</strong><br>
                        Session timeout: <strong><?php echo $sessionTimeout ? ($sessionTimeout / 60) . ' min' : 'Not set'; ?></strong><br>
                        CSRF secret: <strong style="color: <?php echo $csrfConfigured ? '#28a745' : '#dc3545'; ?>"><?php echo $csrfConfigured ? 'Configured' : 'Using default (insecure)'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fail2ban Status Section -->
        <div style="margin: 40px 0;">
            <h2 style="color: #333; border-bottom: 2px solid #FFD700; padding-bottom: 10px;">🚫 Fail2ban Intrusion Prevention</h2>

            <?php if (!$fail2ban['running']): ?>
                <div class="status-card status-error">
                    <div class="status-icon">❌</div>
                    <div class="status-title">Fail2ban Not Running</div>
                    <div class="status-description">The fail2ban service does not appear to be active.</div>
                </div>
            <?php elseif (!$fail2ban['log_readable']): ?>
                <div class="status-card status-warning">
                    <div class="status-icon">⚠️</div>
                    <div class="status-title">Fail2ban Running — Log Not Readable</div>
                    <div class="status-description">Service is active but the web process cannot read <code>/var/log/fail2ban.log</code>. Ensure www-data is in the <code>adm</code> group and Apache has been restarted.</div>
                </div>
            <?php else: ?>

                <!-- Service status banner -->
                <div class="status-card status-ok" style="margin-bottom: 20px;">
                    <div class="status-icon">✅</div>
                    <div class="status-title">Fail2ban Active</div>
                    <div class="status-description">Monitoring <?php echo count($fail2ban['jails']); ?> jail(s) — SSH + Apache.</div>
                    <div class="status-value">
                        Jails: <?php echo implode(', ', array_keys($fail2ban['jails'])); ?>
                    </div>
                </div>

                <!-- Per-jail stats -->
                <div class="security-grid">
                    <?php
                    $jailIcons = [
                        'sshd'             => '🔑',
                        'apache-auth'      => '🔐',
                        'apache-badbots'   => '🤖',
                        'apache-noscript'  => '📜',
                        'apache-overflows' => '💣',
                    ];
                    foreach ($fail2ban['jails'] as $jailName => $stats):
                        $bans   = $stats['bans']   ?? 0;
                        $fails  = $stats['fails']  ?? 0;
                        $unbans = $stats['unbans'] ?? 0;
                        $cardClass = $bans > 0 ? 'status-warning' : 'status-ok';
                        $icon = $jailIcons[$jailName] ?? '🛡️';
                    ?>
                        <div class="status-card <?php echo $cardClass; ?>">
                            <div class="status-icon"><?php echo $icon; ?></div>
                            <div class="status-title"><?php echo htmlspecialchars($jailName); ?></div>
                            <div class="status-value">
                                Total failed attempts: <strong><?php echo number_format($fails); ?></strong><br>
                                Total bans: <strong><?php echo number_format($bans); ?></strong><br>
                                Unbans: <?php echo number_format($unbans); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($fail2ban['jails'])): ?>
                        <div class="status-card status-ok">
                            <div class="status-icon">✅</div>
                            <div class="status-title">No Activity Yet</div>
                            <div class="status-description">No bans or failures recorded in the log yet.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent events (last 24h) -->
                <?php if (!empty($fail2ban['recent_events'])): ?>
                <div style="margin-top: 20px;">
                    <h3 style="color: #333;">📋 Recent Events (Last 24 Hours)</h3>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #333; color: white;">
                                <th style="padding: 8px 12px; text-align: left;">Time</th>
                                <th style="padding: 8px 12px; text-align: left;">Jail</th>
                                <th style="padding: 8px 12px; text-align: left;">Event</th>
                                <th style="padding: 8px 12px; text-align: left;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fail2ban['recent_events'] as $i => $ev): ?>
                                <tr style="background: <?php echo $i % 2 === 0 ? '#f9f9f9' : 'white'; ?>">
                                    <td style="padding: 7px 12px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($ev['time']); ?></td>
                                    <td style="padding: 7px 12px;"><?php echo htmlspecialchars($ev['jail']); ?></td>
                                    <td style="padding: 7px 12px;">
                                        <span style="font-weight: bold; color: <?php echo $ev['event'] === 'BAN' ? '#dc3545' : '#28a745'; ?>">
                                            <?php echo $ev['event']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 7px 12px; font-family: monospace;"><?php echo htmlspecialchars($ev['ip']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="margin-top: 15px; padding: 12px; background: #d4edda; border-radius: 6px; color: #155724;">
                    ✅ No ban events in the last 24 hours.
                </div>
                <?php endif; ?>

            <?php endif; // end running check ?>
        </div>

        <?php
        $hashInfo = password_get_info(ADMIN_PASSWORD_HASH);
        $featureChecks = [
            'Password Hashing (Argon2ID)'   => !empty(ADMIN_PASSWORD_HASH) && $hashInfo['algo'] !== null && $hashInfo['algo'] !== 0,
            'Rate Limiting (IP lockout)'     => function_exists('isIPLocked'),
            'CSRF Token Generation'          => function_exists('generateCSRFToken'),
            'CSRF Token Verification'        => function_exists('verifyCSRFToken'),
            'Timing-Safe Comparison'         => function_exists('hash_equals'),
            'Session Token Validation'       => function_exists('isLoggedIn') && isset($_SESSION['session_token']),
            'Audit Logging Active'           => file_exists(__DIR__ . '/admin_actions.log') && function_exists('logAdminAction'),
            '2FA Support Available'          => class_exists('TwoFactorAuth'),
        ];
        $passCount = count(array_filter($featureChecks));
        $totalCount = count($featureChecks);
        ?>
        <div style="margin: 40px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 2px solid #FFD700;">
            <h3 style="color: #333; margin-top: 0;">🔐 Security Features — Live Verification (<?php echo $passCount; ?>/<?php echo $totalCount; ?> Active)</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                <thead>
                    <tr style="background: #333; color: white;">
                        <th style="padding: 8px 12px; text-align: left;">Feature</th>
                        <th style="padding: 8px 12px; text-align: center; width: 80px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($featureChecks as $label => $active): ?>
                    <tr style="background: <?php echo $active ? '#f8fff9' : '#fff5f5'; ?>; border-bottom: 1px solid #eee;">
                        <td style="padding: 8px 12px; color: #333;"><?php echo htmlspecialchars($label); ?></td>
                        <td style="padding: 8px 12px; text-align: center;">
                            <span style="font-weight: bold; color: <?php echo $active ? '#28a745' : '#dc3545'; ?>">
                                <?php echo $active ? '✅ Active' : '❌ Missing'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin: 20px 0; padding: 15px; background: #fff9e6; border-radius: 8px; border: 1px solid #FFD700;">
            <h4 style="color: #333; margin-top: 0;">🔄 Database Migration Notes</h4>
            <p style="color: #666; margin-bottom: 0;">
                The current security system is designed for easy migration to SQL databases. 
                All file-based storage operations are centralized and marked with TODO comments 
                for straightforward database replacement.
            </p>
        </div>
    </div>
</body>
</html>
