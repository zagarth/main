<?php
/**
 * Report-first claddagh image matcher for catalog_products.
 *
 * Default mode is DRY-RUN (no DB writes).
 *
 * Behavior:
 * - Matches files under bands_php/images/claddagh to product_id keys.
 * - Prefers exact product_id match; falls back to base->gender (e.g. 5634 -> 5634M/5634L).
 * - Never overwrites rows that already point to a valid existing file.
 * - Flags conflicts and continues (soft-continue).
 * - Emits summary and optional CSV report.
 *
 * Usage:
 *   php scripts/backfill_claddagh_images.php
 *   php scripts/backfill_claddagh_images.php --csv=/tmp/claddagh_audit.csv
 *   php scripts/backfill_claddagh_images.php --product=5634L
 *   php scripts/backfill_claddagh_images.php --apply
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

$opts = getopt('', ['apply', 'csv::', 'product::']);
$apply = isset($opts['apply']);
$csvPath = isset($opts['csv']) ? trim((string)$opts['csv']) : '';
$onlyProduct = isset($opts['product']) ? strtoupper(trim((string)$opts['product'])) : '';

$allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'tif', 'tiff'];

function extensionRank(string $path): int
{
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'png' => 0,
        'jpg', 'jpeg' => 1,
        'webp' => 2,
        'gif' => 3,
        'tif', 'tiff' => 9,
        default => 10,
    };
}

function isWebSafeImagePath(string $path): bool
{
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true);
}

function normalizeCladdaghStem(string $stem): string
{
    $s = strtoupper(trim($stem));
    $s = preg_replace('/(?:[_-](?:ALT|VIEW|ART)\d*|[._-]\d+)$/i', '', $s) ?? $s;
    return $s;
}

function storedPathExists(?string $storedPath, string $projectRoot): bool
{
    $p = trim((string)$storedPath);
    if ($p === '' || strtolower($p) === 'no images found') {
        return false;
    }
    $abs = rtrim($projectRoot, '/') . '/' . ltrim($p, '/');
    return is_file($abs);
}

function toRelativePath(string $absolutePath, string $projectRoot): string
{
    $root = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $norm = str_replace('\\', '/', $absolutePath);
    if (str_starts_with($norm, $root . '/')) {
        return substr($norm, strlen($root) + 1);
    }
    return ltrim($norm, '/');
}

function pathRank(string $relativePath): int
{
    $p = strtolower(str_replace('\\', '/', $relativePath));
    // Prefer full image path over thumbs if both exist.
    return str_contains($p, '/thumbs/') ? 1 : 0;
}

try {
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('Unable to resolve project root.');
    }

    $sourceDir = realpath($projectRoot . '/bands_php/images/claddagh');
    if ($sourceDir === false || !is_dir($sourceDir)) {
        throw new RuntimeException('Missing source directory: bands_php/images/claddagh');
    }

    $pdo = getDBConnection();

    echo "=== Claddagh image matcher ===\n";
    echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN\n";
    if ($onlyProduct !== '') {
        echo "Filter product: {$onlyProduct}\n";
    }

    $filesByKey = [];
    $totalFiles = 0;

    foreach (scandir($sourceDir) ?: [] as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $abs = $sourceDir . '/' . $f;
        if (!is_file($abs)) {
            continue;
        }
        $ext = strtolower((string)pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            continue;
        }

        $totalFiles++;
        $stem = (string)pathinfo($f, PATHINFO_FILENAME);
        $key = normalizeCladdaghStem($stem);
        if ($key === '') {
            continue;
        }

        $rel = toRelativePath($abs, $projectRoot);
        $filesByKey[$key][] = [
            'file' => $f,
            'path' => $rel,
            'rank' => pathRank($rel),
            'ext_rank' => extensionRank($rel),
        ];
    }

    echo 'Scanned files: ' . $totalFiles . "\n";
    echo 'Unique keys: ' . count($filesByKey) . "\n";

    $records = [];
    $summary = [
        'matched_rows' => 0,
        'would_update' => 0,
        'skipped_existing' => 0,
        'conflicts' => 0,
        'unsupported_format' => 0,
        'unmatched_files' => 0,
    ];

    $exactStmt = $pdo->prepare('SELECT product_id, category, image_files, has_images FROM catalog_products WHERE UPPER(product_id)=?');
    $genderStmt = $pdo->prepare('SELECT product_id, category, image_files, has_images FROM catalog_products WHERE UPPER(product_id) IN (?, ?)');

    foreach ($filesByKey as $key => $candidates) {
        usort($candidates, static function (array $a, array $b): int {
            if ($a['rank'] !== $b['rank']) {
                return $a['rank'] <=> $b['rank'];
            }
            if (($a['ext_rank'] ?? 99) !== ($b['ext_rank'] ?? 99)) {
                return ($a['ext_rank'] ?? 99) <=> ($b['ext_rank'] ?? 99);
            }
            return strcmp($a['path'], $b['path']);
        });

        $chosen = $candidates[0]['path'] ?? null;
        $candidateCount = count($candidates);

        $exactStmt->execute([$key]);
        $rows = $exactStmt->fetchAll(PDO::FETCH_ASSOC);
        $matchType = 'exact';

        if (!$rows) {
            $genderStmt->execute([$key . 'M', $key . 'L']);
            $rows = $genderStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $matchType = 'base_to_gender';
            }
        }

        if (!$rows) {
            $summary['unmatched_files']++;
            $records[] = [
                'product_id' => '',
                'category' => '',
                'old_image_files' => '',
                'old_has_images' => '',
                'new_image_files' => $chosen ?? '',
                'match_key' => $key,
                'match_type' => 'unmatched_file',
                'candidate_count' => (string)$candidateCount,
                'action' => 'UNMATCHED_FILE',
            ];
            continue;
        }

        foreach ($rows as $row) {
            $pid = strtoupper((string)$row['product_id']);
            if ($onlyProduct !== '' && $pid !== $onlyProduct) {
                continue;
            }

            $summary['matched_rows']++;
            $oldImage = (string)($row['image_files'] ?? '');
            $oldHasImages = (string)($row['has_images'] ?? '0');
            $hasValidExisting = storedPathExists($oldImage, $projectRoot);

            $action = 'WOULD_UPDATE';
            if ($hasValidExisting) {
                $action = 'SKIPPED_EXISTING';
                $summary['skipped_existing']++;
            } elseif ($chosen === null || !isWebSafeImagePath($chosen)) {
                $action = 'UNSUPPORTED_FORMAT';
                $summary['unsupported_format']++;
            } elseif ($candidateCount > 1) {
                // Deterministic tie-break: format rank, then thumb preference,
                // then lexical path ordering (already applied in usort).
                $action = 'WOULD_UPDATE_TIEBREAK';
                $summary['would_update']++;
            } else {
                $summary['would_update']++;
            }

            $records[] = [
                'product_id' => $pid,
                'category' => (string)$row['category'],
                'old_image_files' => $oldImage,
                'old_has_images' => $oldHasImages,
                'new_image_files' => $chosen ?? '',
                'match_key' => $key,
                'match_type' => $matchType,
                'candidate_count' => (string)$candidateCount,
                'action' => $action,
            ];
        }
    }

    echo "\nSummary:\n";
    foreach ($summary as $k => $v) {
        echo '  ' . $k . ': ' . $v . "\n";
    }

    if ($csvPath !== '') {
        $dir = dirname($csvPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fh = fopen($csvPath, 'w');
        if ($fh === false) {
            throw new RuntimeException('Unable to open CSV path: ' . $csvPath);
        }

        fputcsv($fh, [
            'product_id',
            'category',
            'old_image_files',
            'old_has_images',
            'new_image_files',
            'match_key',
            'match_type',
            'candidate_count',
            'action',
        ]);

        foreach ($records as $r) {
            fputcsv($fh, [
                $r['product_id'],
                $r['category'],
                $r['old_image_files'],
                $r['old_has_images'],
                $r['new_image_files'],
                $r['match_key'],
                $r['match_type'],
                $r['candidate_count'],
                $r['action'],
            ]);
        }

        fclose($fh);
        echo "CSV report: {$csvPath}\n";
    }

    if (!$apply) {
        echo "\nDry-run complete. No database writes performed.\n";
        exit(0);
    }

    // APPLY mode still does not overwrite valid existing paths or conflicts.
    $updates = array_values(array_filter($records, static function (array $r): bool {
        return in_array($r['action'], ['WOULD_UPDATE', 'WOULD_UPDATE_TIEBREAK'], true)
            && $r['product_id'] !== '';
    }));

    if (!$updates) {
        echo "\nNo safe updates to apply.\n";
        exit(0);
    }

    $runId = date('Ymd_His');
    $backupTable = 'claddagh_image_backup_' . $runId;

    $pdo->exec(
        "CREATE TABLE {$backupTable} AS
         SELECT product_id, image_files, has_images
         FROM catalog_products
         WHERE 1 = 0"
    );

    $pdo->beginTransaction();

    $backupStmt = $pdo->prepare("INSERT INTO {$backupTable} (product_id, image_files, has_images) VALUES (?, ?, ?)");
    $updateStmt = $pdo->prepare('UPDATE catalog_products SET image_files=?, has_images=1 WHERE product_id=?');

    $applied = 0;
    foreach ($updates as $u) {
        $pid = $u['product_id'];

        $select = $pdo->prepare('SELECT image_files, has_images FROM catalog_products WHERE product_id=? LIMIT 1');
        $select->execute([$pid]);
        $current = $select->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            continue;
        }

        if (storedPathExists((string)($current['image_files'] ?? ''), $projectRoot)) {
            continue;
        }

        $backupStmt->execute([
            $pid,
            (string)($current['image_files'] ?? ''),
            (int)($current['has_images'] ?? 0),
        ]);

        $updateStmt->execute([$u['new_image_files'], $pid]);
        $applied += $updateStmt->rowCount();
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
