<?php
/**
 * One-time migration: add `slug` column to catalog_products and backfill.
 * Idempotent — safe to re-run.
 *
 * Usage:  php scripts/migrate_add_slug_column.php
 */

require_once __DIR__ . '/../includes/db_config.php';

$pdo = getDBConnection();

echo "=== catalog_products slug migration ===\n";

// 1. Add column if missing
$colExists = $pdo->query(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'catalog_products'
       AND COLUMN_NAME = 'slug'"
)->fetchColumn();

if (!$colExists) {
    echo "Adding `slug` VARCHAR(120) column...\n";
    $pdo->exec("ALTER TABLE catalog_products ADD COLUMN slug VARCHAR(120) NULL AFTER product_name");
    $pdo->exec("ALTER TABLE catalog_products ADD INDEX idx_slug (slug)");
    echo "  Column + index created.\n";
} else {
    echo "Column `slug` already exists. Skipping schema change.\n";
}

// 2. Backfill rows where slug is NULL or empty
echo "\nBackfilling slugs...\n";

function slugify(string $s): string {
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return substr($s, 0, 100);
}

$stmt = $pdo->query("SELECT product_id, product_name FROM catalog_products
                     WHERE slug IS NULL OR slug = ''");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("UPDATE catalog_products SET slug = ? WHERE product_id = ?");
$count = 0;
foreach ($rows as $r) {
    $name = $r['product_name'] ?: $r['product_id'];
    $slug = slugify($name) ?: slugify($r['product_id']);
    $update->execute([$slug, $r['product_id']]);
    $count++;
}

echo "  Backfilled $count rows.\n";

// 3. Stats
$total = $pdo->query("SELECT COUNT(*) FROM catalog_products")->fetchColumn();
$nonNull = $pdo->query("SELECT COUNT(*) FROM catalog_products WHERE slug IS NOT NULL AND slug != ''")->fetchColumn();
echo "\nFinal: $nonNull / $total rows have slugs.\n";
echo "Done.\n";
