<?php
/**
 * Round labor_hours in products with safety controls.
 *
 * Rules:
 * - fractional part > 0.2 and <= 0.5 => round to .5
 * - fractional part >= 0.6 and < 1.0 => round to next whole number
 * - all other rows remain unchanged
 *
 * Safety:
 * - dry-run by default
 * - on --apply, creates a timestamped snapshot table before updates
 * - writes happen in a transaction
 *
 * Usage:
 *   php scripts/round_labor_hours.php
 *   php scripts/round_labor_hours.php --apply
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/../includes/db_config.php';

$apply = in_array('--apply', $argv, true);

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection error: " . $e->getMessage() . "\n");
    exit(2);
}

try {
    // Baseline counts and examples before any writes
    $countsSql = "SELECT
        SUM(CASE WHEN labor_hours IS NOT NULL AND (labor_hours - FLOOR(labor_hours)) > 0.2 AND (labor_hours - FLOOR(labor_hours)) <= 0.5 THEN 1 ELSE 0 END) AS to_half,
        SUM(CASE WHEN labor_hours IS NOT NULL AND (labor_hours - FLOOR(labor_hours)) >= 0.6 AND (labor_hours - FLOOR(labor_hours)) < 1.0 THEN 1 ELSE 0 END) AS to_whole,
        COUNT(*) AS total_items,
        SUM(CASE WHEN labor_hours IS NULL OR labor_hours = 0 THEN 1 ELSE 0 END) AS null_or_zero
    FROM products";

    $counts = $pdo->query($countsSql)->fetch(PDO::FETCH_ASSOC);

    echo "Labor Hours Rounding Plan\n";
    echo str_repeat('=', 60) . "\n";
    echo "to .5 bucket (>0.2 and <=0.5): " . (int)$counts['to_half'] . "\n";
    echo "to next whole (>=0.6 and <1.0): " . (int)$counts['to_whole'] . "\n";
    echo "total products: " . (int)$counts['total_items'] . "\n";
    echo "null/zero labor_hours: " . (int)$counts['null_or_zero'] . "\n";

    $sampleHalf = $pdo->query("SELECT base_code, labor_hours FROM products WHERE labor_hours IS NOT NULL AND (labor_hours - FLOOR(labor_hours)) > 0.2 AND (labor_hours - FLOOR(labor_hours)) <= 0.5 ORDER BY base_code LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $sampleWhole = $pdo->query("SELECT base_code, labor_hours FROM products WHERE labor_hours IS NOT NULL AND (labor_hours - FLOOR(labor_hours)) >= 0.6 AND (labor_hours - FLOOR(labor_hours)) < 1.0 ORDER BY base_code LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    echo "\nSample rows (to .5):\n";
    foreach ($sampleHalf as $row) {
        echo "  {$row['base_code']} => {$row['labor_hours']}\n";
    }

    echo "\nSample rows (to next whole):\n";
    foreach ($sampleWhole as $row) {
        echo "  {$row['base_code']} => {$row['labor_hours']}\n";
    }

    if (!$apply) {
        echo "\nMode: DRY-RUN (no changes written). Use --apply to execute.\n";
        exit(0);
    }

    // Snapshot products table before modifying
    $backupTable = 'products_backup_labor_round_' . date('Ymd_His');
    $pdo->exec("CREATE TABLE `$backupTable` AS SELECT * FROM products");

    $pdo->beginTransaction();

    // Apply the rounding rules in one statement
    $updateSql = "UPDATE products
                  SET labor_hours = CASE
                      WHEN labor_hours IS NULL THEN labor_hours
                      WHEN (labor_hours - FLOOR(labor_hours)) > 0.2
                       AND (labor_hours - FLOOR(labor_hours)) <= 0.5
                          THEN FLOOR(labor_hours) + 0.5
                      WHEN (labor_hours - FLOOR(labor_hours)) >= 0.6
                       AND (labor_hours - FLOOR(labor_hours)) < 1.0
                          THEN FLOOR(labor_hours) + 1.0
                      ELSE labor_hours
                  END";

    $affected = $pdo->exec($updateSql);

    $postCounts = $pdo->query($countsSql)->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo "\nMode: APPLY\n";
    echo "Backup table created: $backupTable\n";
    echo "Rows touched by update statement: " . (int)$affected . "\n";
    echo "Remaining to .5 bucket after apply: " . (int)$postCounts['to_half'] . "\n";
    echo "Remaining to whole bucket after apply: " . (int)$postCounts['to_whole'] . "\n";

    echo "\nRecovery (if needed):\n";
    echo "  START TRANSACTION;\n";
    echo "  TRUNCATE TABLE products;\n";
    echo "  INSERT INTO products SELECT * FROM `$backupTable`;\n";
    echo "  COMMIT;\n";

} catch (Throwable $e) {
    if ($apply && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
