<?php
require_once 'auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal Admin Test</title>
</head>
<body>
    <h1>Minimal Admin Test</h1>
    
    <!-- Test 1: Basic PHP -->
    <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <!-- Test 2: Database Connection -->
    <p>Database test:
    <?php
    try {
        echo "Step 1: About to require db_config...";
        require_once __DIR__ . '/../includes/db_config.php';
        echo "Step 2: db_config loaded...";
        $pdo = getDBConnection();
        echo "Step 3: ✅ Connected";
    } catch (Exception $e) {
        echo "❌ Exception: " . htmlspecialchars($e->getMessage());
    } catch (Error $e) {
        echo "❌ Error: " . htmlspecialchars($e->getMessage());
    } catch (Throwable $e) {
        echo "❌ Throwable: " . htmlspecialchars($e->getMessage());
    }
    ?>
    </p>
    
    <!-- Test 3: Simple Dropdown -->
    <p>Simple dropdown test:</p>
    <select>
        <option value="">Choose...</option>
        <?php
        try {
            require_once __DIR__ . '/../includes/db_config.php';
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT DISTINCT category FROM catalog_products WHERE category IS NOT NULL LIMIT 5");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($categories as $cat) {
                echo '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
            }
        } catch (Exception $e) {
            echo '<option value="">Error: ' . htmlspecialchars($e->getMessage()) . '</option>';
        }
        ?>
    </select>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>