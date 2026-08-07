<?php
/**
 * Backfill missing family image_files in catalog_products from direct filename matches.
 *
 * Usage:
 *   php scripts/backfill_family_image_files.php
 *   php scripts/backfill_family_image_files.php --apply
 *   php scripts/backfill_family_image_files.php --product=2280
 *   php scripts/backfill_family_image_files.php --csv=/tmp/family_backfill.csv
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

$opts = getopt('', ['apply', 'product::', 'csv::']);
$apply = isset($opts['apply']);
$onlyProduct = isset($opts['product']) ? trim((string)$opts['product']) : '';
$csvPath = isset($opts['csv']) ? trim((string)$opts['csv']) : '';

$allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$extensionRank = array_flip($allowedExtensions);

$searchRoots = array_values(array_filter([
    realpath(__DIR__ . '/../family_php/images') ?: null,
    realpath(__DIR__ . '/../family_php/thumbs/images') ?: null,
]));

/**
 * Prefer full-size family image folders over thumbs for DB linking.
 */
function pathRank(string $absolutePath): int
{
    $p = str_replace('\\', '/', $absolutePath);
    return str_contains($p, '/family_php/thumbs/images/') ? 1 : 0;
}

/**
 * Convert absolute path under project root into web-relative path used by image_files.
 */
function toRelativeWebPath(string $absolutePath, string $projectRoot): string
{
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $normalized = str_replace('\\', '/', $absolutePath);
    $normalized = preg_replace('#/\./#', '/', $normalized) ?? $normalized;
    if (str_starts_with($normalized, $projectRoot . '/')) {
        return substr($normalized, strlen($projectRoot) + 1);
    }
    return ltrim($normalized, '/');
}

function storedImagePathExists(?string $storedPath, string $projectRoot): bool
{
    $p = trim((string)$storedPath);
    if ($p === '' || strtolower($p) === 'no images found') {
        return false;
    }
    $abs = rtrim($projectRoot, '/') . '/' . ltrim($p, '/');
    return is_file($abs);
}

try {
    $pdo = getDBConnection();
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('Could not resolve project root.');
    }

    echo "=== Family image backfill (direct matches only) ===\n";
    echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN\n";
    if ($onlyProduct !== '') {
        echo "Filter: product_id = {$onlyProduct}\n";
    }

    // Build direct-match index by base filename (stem) from family images + thumbs.
    $index = []; // key: strtoupper(stem) => best relative path

    foreach ($searchRoots as $root) {
        if (!is_dir($root)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower($fileInfo->getExtension());
            if (!isset($extensionRank[$ext])) {
                continue;
            }

            $stem = (string)$fileInfo->getBasename('.' . $fileInfo->getExtension());
            if ($stem === '') {
                continue;
            }

            // Direct match only: ignore alt/variant filenames.
            if (preg_match('/([_-](alt|view|art)\d*|[_.-]\d+)$/i', $stem)) {
                continue;
            }

            $key = strtoupper($stem);
            $abs = $fileInfo->getRealPath() ?: $fileInfo->getPathname();
            $rel = toRelativeWebPath($abs, $projectRoot);

            $candidate = [
                'path' => $rel,
                'path_rank' => pathRank($abs),
                'ext_rank' => $extensionRank[$ext],
            ];

            if (!isset($index[$key])) {
                $index[$key] = $candidate;
                continue;
            }

            $current = $index[$key];
            $replace = false;

            if ($candidate['path_rank'] < $current['path_rank']) {
                $replace = true;
            } elseif ($candidate['path_rank'] === $current['path_rank']) {
                if ($candidate['ext_rank'] < $current['ext_rank']) {
                    $replace = true;
                } elseif ($candidate['ext_rank'] === $current['ext_rank'] && strcmp($candidate['path'], $current['path']) < 0) {
                    $replace = true;
                }
            }

            if ($replace) {
                $index[$key] = $candidate;
            }
        }
    }

    echo 'Indexed direct family images: ' . count($index) . "\n";

    $baseCodeByCatalogPid = [];
    $baseRows = $pdo->query("SELECT product_id, base_code FROM products")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($baseRows as $br) {
        $catalogPid = strtoupper(trim((string)($br['product_id'] ?? '')));
        $baseCode = strtoupper(trim((string)($br['base_code'] ?? '')));
        if ($catalogPid === '' || $baseCode === '') continue;
        if (!isset($baseCodeByCatalogPid[$catalogPid])) {
            $baseCodeByCatalogPid[$catalogPid] = $baseCode;
        }
    }

        $sql = "SELECT product_id, image_files, has_images
            FROM catalog_products
            WHERE category = 'family'";
    $params = [];

    if ($onlyProduct !== '') {
        $sql .= ' AND product_id = ?';
        $params[] = $onlyProduct;
    }

    $sql .= ' ORDER BY product_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Family rows in scope: ' . count($rows) . "\n";

    $matches = [];
    $unmatched = [];
    $skippedExisting = 0;

    foreach ($rows as $row) {
        $pid = (string)$row['product_id'];
        if (storedImagePathExists((string)($row['image_files'] ?? ''), $projectRoot)) {
            $skippedExisting++;
            continue;
        }
        $key = strtoupper($pid);
        $baseKey = $baseCodeByCatalogPid[$key] ?? '';

        if (isset($index[$key]['path'])) {
            $matches[] = [
                'product_id' => $pid,
                'old_image_files' => (string)($row['image_files'] ?? ''),
                'old_has_images' => (int)($row['has_images'] ?? 0),
                'new_image_files' => $index[$key]['path'],
                'match_type' => 'direct_product_id',
            ];
        } elseif ($baseKey !== '' && isset($index[$baseKey]['path'])) {
            $matches[] = [
                'product_id' => $pid,
                'old_image_files' => (string)($row['image_files'] ?? ''),
                'old_has_images' => (int)($row['has_images'] ?? 0),
                'new_image_files' => $index[$baseKey]['path'],
                'match_type' => 'via_base_code',
            ];
        } else {
            $unmatched[] = $pid;
        }
    }

    echo 'Direct matches found: ' . count($matches) . "\n";
    echo 'No direct file match: ' . count($unmatched) . "\n";
    echo 'Skipped (existing valid image path): ' . $skippedExisting . "\n";

    if ($csvPath !== '') {
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0775, true);
        }
        $fh = fopen($csvPath, 'w');
        if ($fh !== false) {
            fputcsv($fh, ['product_id', 'old_image_files', 'old_has_images', 'new_image_files', 'match_type']);
            foreach ($matches as $m) {
                fputcsv($fh, [$m['product_id'], $m['old_image_files'], $m['old_has_images'], $m['new_image_files'], $m['match_type']]);
            }
            fclose($fh);
            echo "Wrote mapping CSV: {$csvPath}\n";
        }
    }

    $sampleLimit = min(15, count($matches));
    if ($sampleLimit > 0) {
        echo "\nSample matches:\n";
        for ($i = 0; $i < $sampleLimit; $i++) {
            $m = $matches[$i];
            echo '  ' . $m['product_id'] . ' -> ' . $m['new_image_files'] . ' (' . $m['match_type'] . ")\n";
        }
    }

    // Explicitly surface the 2280 example when present in scope/index.
    $has2280 = false;
    foreach ($matches as $m) {
        if (strcasecmp($m['product_id'], '2280') === 0) {
            echo "\n2280 match: " . $m['new_image_files'] . "\n";
            $has2280 = true;
            break;
        }
    }
    if (!$has2280 && isset($index['2280'])) {
        echo "\n2280 indexed image exists but row was not in update scope: " . $index['2280']['path'] . "\n";
    }

    if (!$apply) {
        echo "\nDry-run complete. Re-run with --apply to persist updates.\n";
        exit(0);
    }

    if (count($matches) === 0) {
        echo "\nNo matches to apply.\n";
        exit(0);
    }

    $runId = date('Ymd_His');
    $backupTable = 'catalog_products_family_image_backup_' . $runId;

    $pdo->beginTransaction();

    $pdo->exec(
        "CREATE TABLE {$backupTable} AS
         SELECT product_id, image_files, has_images
         FROM catalog_products
         WHERE 1 = 0"
    );

    $backupInsert = $pdo->prepare(
        "INSERT INTO {$backupTable} (product_id, image_files, has_images)
         VALUES (?, ?, ?)"
    );

    foreach ($matches as $m) {
        $backupInsert->execute([
            $m['product_id'],
            $m['old_image_files'] === '' ? null : $m['old_image_files'],
            $m['old_has_images'],
        ]);
    }

    $update = $pdo->prepare(
        "UPDATE catalog_products
         SET image_files = ?, has_images = 1
         WHERE category = 'family'
           AND product_id = ?
            AND (image_files IS NULL OR image_files = '' OR image_files = 'no images found' OR image_files = ? OR image_files = ?)"
    );

    $applied = 0;
    foreach ($matches as $m) {
        $old = trim((string)$m['old_image_files']);
        $update->execute([$m['new_image_files'], $m['product_id'], $old, $old === '' ? null : $old]);
        $applied += $update->rowCount();
    }

    $pdo->commit();

    echo "\nApply complete. Rows updated: {$applied}\n";
    echo "Backup table: {$backupTable}\n";
    echo "Rollback example:\n";
    echo "  UPDATE catalog_products cp JOIN {$backupTable} b USING(product_id)\n";
    echo "  SET cp.image_files = b.image_files, cp.has_images = b.has_images;\n";

    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
