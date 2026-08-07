<?php
/**
 * SESSION DEBUG — temporary test page, DELETE before production
 * Visit after logging in at /admin/login.php to confirm session vars survive
 */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Quick auth check using the same logic we'll use in index.php
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
              && isset($_SESSION['role'])
              && in_array($_SESSION['role'], ['admin', 'business'], true);

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Session Debug</title>
<style>
body { font-family: monospace; background: #1a1a1a; color: #00ff88; padding: 30px; }
table { border-collapse: collapse; width: 100%; max-width: 700px; }
td, th { border: 1px solid #444; padding: 8px 14px; }
th { background: #333; color: #FFD700; text-align: left; }
.yes { color: #00ff88; font-weight: bold; }
.no  { color: #ff4444; font-weight: bold; }
</style>
</head>
<body>
<h2>Session Debug</h2>
<p>Price gate check: <span class="<?= $isLoggedIn ? 'yes' : 'no' ?>"><?= $isLoggedIn ? 'LOGGED IN — would see prices' : 'NOT LOGGED IN — would see lock' ?></span></p>

<h3>$_SESSION contents</h3>
<?php if (empty($_SESSION)): ?>
  <p class="no">No session data. Not logged in, or session expired.</p>
<?php else: ?>
<table>
<tr><th>Key</th><th>Value</th></tr>
<?php foreach ($_SESSION as $k => $v): ?>
<tr>
  <td><?= htmlspecialchars($k) ?></td>
  <td><?= htmlspecialchars(is_string($v) ? (strlen($v) > 60 ? substr($v,0,57).'...' : $v) : json_encode($v)) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<hr style="border-color:#444; margin-top:30px;">
<p style="color:#666; font-size:0.8em;">⚠️ DELETE session_debug.php before going live.</p>
</body>
</html>
