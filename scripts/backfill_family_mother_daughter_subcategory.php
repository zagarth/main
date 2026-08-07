<?php
/**
 * Backfill mother_daughter subcategory for family rows with empty subcategory
 * whose product_id matches Mother/Daughter image folders.
 *
 * Usage:
 *   php scripts/backfill_family_mother_daughter_subcategory.php
 *   php scripts/backfill_family_mother_daughter_subcategory.php --apply
 *   php scripts/backfill_family_mother_daughter_subcategory.php --product=2080
 *   php scripts/backfill_family_mother_daughter_subcategory.php --csv=/tmp/family_mother_daughter_dryrun.csv
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

$opts = getopt('', ['apply', 'product::', 'csv::']);
$apply = isset($opts['apply']);
$onlyProduct = isset($opts['product']) ? trim((string)$opts['product']) : '';
$csvPath = isset($opts['csv']) ? trim((string)$opts['csv']) : '';

$searchDirs = [
    'Mother' => realpath(__DIR__ . '/../family_php/images/Mother') ?: null,
    'Daughter' => realpath(__DIR__ . '/../family_php/images/Daughter') ?: null,
];

function normalizeFamilyStem(string $filename): string
{
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    if (preg_match('/^(.+?)(?:[_-](?:alt|view|art)\d*|[._-]\d+)$/i', $stem, $m)) {
        $stem = $m[1];
    }
    return strtoupper(trim($stem));
}

try {
    $pdo = getDBConnection();

    echo "=== Family mother_daughter subcategory backfill ===\n";
    echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN\n";
    if ($onlyProduct !== '') {
        echo "Filter: product_id = {$onlyProduct}\n";
    }

    $stemsByDir = [];
    foreach ($searchDirs as $label => $dir) {
        if (!$dir || !is_dir($dir)) {
            continue;
        }

        $stems = [];
        $entries = scandir($dir);
        if (!is_array($entries)) {
            continue;
        }

        foreach ($entries as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            if (!is_file($path)) continue;
            if (!preg_match('/\.(png|jpe?g|webp|gif|tiff?)$/i', $file)) continue;
            $stem = normalizeFamilyStem($file);
            if ($stem === '') continue;
            $stems[$stem] = true;
        }

        $stemsByDir[$label] = $stems;
        echo $label . ' indexed stems: ' . count($stems) . "\n";
    }

        $sql = "SELECT product_id, product_name, subcategory, image_files
            FROM catalog_products
            WHERE category = 'family'
              AND (subcategory IS NULL OR subcategory = '')";
    $params = [];

    if ($onlyProduct !== '') {
        $sql .= ' AND product_id = ?';
        $params[] = $onlyProduct;
    }

    $sql .= ' ORDER BY product_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Family rows with empty subcategory in scope: ' . count($rows) . "\n";

    $matches = [];
    $unmatched = [];

    foreach ($rows as $row) {
        $pid = strtoupper(trim((string)$row['product_id']));
        $folders = [];
        foreach ($stemsByDir as $label => $stems) {
            if (isset($stems[$pid])) {
                $folders[] = $label;
            }
        }

        if ($folders) {
            $matches[] = [
                'product_id' => (string)$row['product_id'],
                'old_subcategory' => (string)($row['subcategory'] ?? ''),
                'image_files' => (string)($row['image_files'] ?? ''),
                'matched_folders' => implode(',', $folders),
                'new_subcategory' => 'mother_daughter',
            ];
        } else {
            $unmatched[] = (string)$row['product_id'];
        }
    }

    echo 'Folder matches found: ' . count($matches) . "\n";
    echo 'No folder match: ' . count($unmatched) . "\n";

    if ($csvPath !== '') {
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0775, true);
        }
        $fh = fopen($csvPath, 'w');
        if ($fh !== false) {
            fputcsv($fh, ['product_id', 'old_subcategory', 'image_files', 'matched_folders', 'new_subcategory']);
            foreach ($matches as $m) {
                fputcsv($fh, [$m['product_id'], $m['old_subcategory'], $m['image_files'], $m['matched_folders'], $m['new_subcategory']]);
            }
            fclose($fh);
            echo "Wrote mapping CSV: {$csvPath}\n";
        }
    }

    $sampleLimit = min(20, count($matches));
    if ($sampleLimit > 0) {
        echo "\nSample matches:\n";
        for ($i = 0; $i < $sampleLimit; $i++) {
            $m = $matches[$i];
            echo '  ' . $m['product_id'] . ' -> ' . $m['new_subcategory'] . ' [' . $m['matched_folders'] . "]\n";
        }
    }

    if (!$apply) {
        echo "\nDry-run complete. Re-run with --apply to persist updates.\n";
        exit(0);
    }

    if (!$matches) {
        echo "\nNo matches to apply.\n";
        exit(0);
    }

    $runId = date('Ymd_His');
    $backupTable = 'catalog_products_family_subcategory_backup_' . $runId;

    $pdo->exec(
        "CREATE TABLE {$backupTable} AS
         SELECT product_id, subcategory
         FROM catalog_products
         WHERE 1 = 0"
    );

    $pdo->beginTransaction();

    $backupInsert = $pdo->prepare(
        "INSERT INTO {$backupTable} (product_id, subcategory)
         VALUES (?, ?)"
    );
    foreach ($matches as $m) {
        $backupInsert->execute([$m['product_id'], $m['old_subcategory'] === '' ? null : $m['old_subcategory']]);
    }

    $update = $pdo->prepare(
        "UPDATE catalog_products
         SET subcategory = 'mother_daughter'
                 WHERE category = 'family'
                     AND product_id = ?
                     AND (subcategory IS NULL OR subcategory = '')"
    );

    $applied = 0;
    foreach ($matches as $m) {
        $update->execute([$m['product_id']]);
        $applied += $update->rowCount();
    }

    $pdo->commit();

    echo "\nApply complete. Rows updated: {$applied}\n";
    echo "Backup table: {$backupTable}\n";
    echo "Rollback example:\n";
    echo "  UPDATE catalog_products cp JOIN {$backupTable} b USING(product_id)\n";
    echo "  SET cp.subcategory = b.subcategory;\n";

    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}