<?php
/**
 * Correct family subcategory assignments after the broad mother_daughter backfill.
 *
 * Rules:
 * - Mother-only image stem match    => set subcategory = 'family'
 * - Daughter-only image stem match  => keep subcategory = 'mother_daughter'
 * - Mother-and-Daughter dual match  => keep subcategory = 'mother_daughter'
 *
 * Usage:
 *   php scripts/correct_family_mother_daughter_subcategory.php
 *   php scripts/correct_family_mother_daughter_subcategory.php --apply
 *   php scripts/correct_family_mother_daughter_subcategory.php --product=F2530
 *   php scripts/correct_family_mother_daughter_subcategory.php --csv=/tmp/family_mother_daughter_correction.csv
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

function normalizeFamilyStemForCorrection(string $filename): string
{
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    if (preg_match('/^(.+?)(?:[_-](?:alt|view|art)\d*|[._-]\d+)$/i', $stem, $m)) {
        $stem = $m[1];
    }
    return strtoupper(trim($stem));
}

try {
    $pdo = getDBConnection();

    echo "=== Family mother_daughter correction ===\n";
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
            $stem = normalizeFamilyStemForCorrection($file);
            if ($stem === '') continue;
            $stems[$stem] = true;
        }

        $stemsByDir[$label] = $stems;
        echo $label . ' indexed stems: ' . count($stems) . "\n";
    }

        $sql = "SELECT product_id, product_name, subcategory, image_files
            FROM catalog_products
            WHERE category = 'family'
                            AND (subcategory = 'mother_daughter' OR subcategory IS NULL OR subcategory = '')";
    $params = [];

    if ($onlyProduct !== '') {
        $sql .= ' AND product_id = ?';
        $params[] = $onlyProduct;
    }

    $sql .= ' ORDER BY product_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'family rows in correction scope: ' . count($rows) . "\n";

    $motherOnly = [];
    $daughterOnly = [];
    $dual = [];
    $unmatched = [];

    foreach ($rows as $row) {
        $pid = strtoupper(trim((string)$row['product_id']));
        $hasMother = isset($stemsByDir['Mother'][$pid]);
        $hasDaughter = isset($stemsByDir['Daughter'][$pid]);

        $record = [
            'product_id' => (string)$row['product_id'],
            'product_name' => (string)($row['product_name'] ?? ''),
            'old_subcategory' => (string)($row['subcategory'] ?? ''),
            'image_files' => (string)($row['image_files'] ?? ''),
            'matched_folders' => implode(',', array_values(array_filter([
                $hasMother ? 'Mother' : null,
                $hasDaughter ? 'Daughter' : null,
            ]))),
        ];

        if ($hasMother && !$hasDaughter) {
            $record['new_subcategory'] = 'family';
            $motherOnly[] = $record;
        } elseif (!$hasMother && $hasDaughter) {
            $record['new_subcategory'] = 'mother_daughter';
            $daughterOnly[] = $record;
        } elseif ($hasMother && $hasDaughter) {
            $record['new_subcategory'] = 'mother_daughter';
            $dual[] = $record;
        } else {
            $record['new_subcategory'] = 'mother_daughter';
            $unmatched[] = $record;
        }
    }

    $toUpdate = $motherOnly;

    echo 'Mother-only (will set to family): ' . count($motherOnly) . "\n";
    echo 'Daughter-only (unchanged): ' . count($daughterOnly) . "\n";
    echo 'Dual match (unchanged): ' . count($dual) . "\n";
    echo 'Unmatched (unchanged): ' . count($unmatched) . "\n";

    if ($csvPath !== '') {
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0775, true);
        }
        $fh = fopen($csvPath, 'w');
        if ($fh !== false) {
            fputcsv($fh, ['product_id', 'product_name', 'old_subcategory', 'image_files', 'matched_folders', 'new_subcategory', 'action']);
            foreach (array_merge($motherOnly, $daughterOnly, $dual, $unmatched) as $m) {
                $action = $m['new_subcategory'] === 'family' ? 'set_family' : 'keep';
                fputcsv($fh, [$m['product_id'], $m['product_name'], $m['old_subcategory'], $m['image_files'], $m['matched_folders'], $m['new_subcategory'] ?? '', $action]);
            }
            fclose($fh);
            echo "Wrote correction CSV: {$csvPath}\n";
        }
    }

    $sampleLimit = min(20, count($motherOnly));
    if ($sampleLimit > 0) {
        echo "\nSample Mother-only corrections:\n";
        for ($i = 0; $i < $sampleLimit; $i++) {
            $m = $motherOnly[$i];
            echo '  ' . $m['product_id'] . ' [' . $m['matched_folders'] . "] -> family\n";
        }
    }

    if (!$apply) {
        echo "\nDry-run complete. Re-run with --apply to persist corrections.\n";
        exit(0);
    }

    if (!$toUpdate) {
        echo "\nNo Mother-only corrections to apply.\n";
        exit(0);
    }

    $runId = date('Ymd_His');
    $backupTable = 'family_subcat_fix_backup_' . $runId;

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
    foreach ($toUpdate as $m) {
        $backupInsert->execute([$m['product_id'], $m['old_subcategory'] === '' ? null : $m['old_subcategory']]);
    }

    $update = $pdo->prepare(
        "UPDATE catalog_products
                 SET subcategory = 'family'
         WHERE category = 'family'
           AND product_id = ?
                     AND (subcategory = 'mother_daughter' OR subcategory IS NULL OR subcategory = '')"
    );

    $applied = 0;
    foreach ($toUpdate as $m) {
        $update->execute([$m['product_id']]);
        $applied += $update->rowCount();
    }

    $pdo->commit();

    echo "\nApply complete. Rows corrected: {$applied}\n";
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