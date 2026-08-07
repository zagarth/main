<?php
function renderAdminHeader($title = 'Admin Portal', $additionalScripts = [], $additionalStyles = []) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Cadman Manufacturing</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <?php foreach ($additionalStyles as $style): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($style); ?>">
    <?php endforeach; ?>
    <script src="session-manager.js"></script>
    <?php foreach ($additionalScripts as $script): ?>
    <script src="<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
</head>
<body>
<?php
}

function renderAdminFooter() {
    ?>
</body>
</html>
<?php
}

function renderAdminNavigation($currentPage = '') {
    ?>
<div class="admin-navigation">
    <div class="admin-nav-header">
        <h2>🏭 Cadman Admin</h2>
        <div class="admin-user-info">
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
            <a href="?logout=1" class="logout-btn">🚪 Logout</a>
        </div>
    </div>
    <nav class="admin-nav-menu">
        <a href="index.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            📊 Dashboard
        </a>
        <a href="retailer_geocoding.php" class="<?php echo $currentPage === 'geocoding' ? 'active' : ''; ?>">
            🗺️ Retailer Geocoding
        </a>
        <a href="add_retailer.php" class="<?php echo $currentPage === 'add_retailer' ? 'active' : ''; ?>">
            ➕ Add Retailer
        </a>
        <a href="view_logs.php" class="<?php echo $currentPage === 'logs' ? 'active' : ''; ?>">
            📋 View Logs
        </a>
    </nav>
</div>
<?php
}
?>