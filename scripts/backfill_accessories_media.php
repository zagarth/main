<?php
/**
 * Backfill accessories-related media fields in catalog_products.
 *
 * Scope:
 * - categories: lockets, crosses, pendants, bracelets, idents, medical
 * - image_files/has_images: filled from direct filename matches in accessories image dirs
 * - pdf_file: filled when missing and resolvable from page_reference or current catalog PDFs
 *
 * Usage:
 *   php scripts/backfill_accessories_media.php
 *   php scripts/backfill_accessories_media.php --apply
 *   php scripts/backfill_accessories_media.php --product=10TGD
 *   php scripts/backfill_accessories_media.php --csv=/tmp/accessories_media_dryrun.csv
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

$opts = getopt('', ['apply', 'product::', 'csv::']);
$apply = isset($opts['apply']);
$onlyProduct = isset($opts['product']) ? trim((string)$opts['product']) : '';
$csvPath = isset($opts['csv']) ? trim((string)$opts['csv']) : '';

$targetCategories = ['lockets', 'crosses', 'pendants', 'bracelets', 'idents', 'medical'];
$allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$extensionRank = array_flip($allowedExtensions);

$searchRoots = array_values(array_filter([
    realpath(__DIR__ . '/../accessories_php/images/Crosses_and_Lockets') ?: null,
    realpath(__DIR__ . '/../accessories_php/thumbs/images/Crosses_and_Lockets') ?: null,
    realpath(__DIR__ . '/../accessories_php/images/Pendant_earrings') ?: null,
    realpath(__DIR__ . '/../accessories_php/thumbs/images/Pendant_earrings') ?: null,
    realpath(__DIR__ . '/../accessories_php/images/Idents') ?: null,
    realpath(__DIR__ . '/../accessories_php/thumbs/images/Idents') ?: null,
]));

function accessoriesPathRank(string $absolutePath): int
{
    $p = str_replace('\\', '/', $absolutePath);
    return str_contains($p, '/accessories_php/thumbs/images/') ? 1 : 0;
}

function toRelativeAccessoriesPath(string $absolutePath, string $projectRoot): string
{
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $normalized = str_replace('\\', '/', $absolutePath);
    $normalized = preg_replace('#/\./#', '/', $normalized) ?? $normalized;
    if (str_starts_with($normalized, $projectRoot . '/')) {
        return substr($normalized, strlen($projectRoot) + 1);
    }
    return ltrim($normalized, '/');
}

function storedAccessoriesPathExists(?string $storedPath, string $projectRoot): bool
{
    $p = trim((string)$storedPath);
    if ($p === '' || strtolower($p) === 'no images found') {
        return false;
    }
    $abs = rtrim($projectRoot, '/') . '/' . ltrim($p, '/');
    return is_file($abs);
}

function normalizeAccessoriesStem(string $stem): string
{
    if (preg_match('/^(.+?)(?:[_-](?:alt|view|art)\d*|[._-]\d+)$/i', $stem, $m)) {
        $stem = $m[1];
    }
    return strtoupper(trim($stem));
}

function buildPdfIndex(string $catalogRoot): array
{
    $index = [];
    if (!is_dir($catalogRoot)) {
        return $index;
    }
    foreach (scandir($catalogRoot) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $catalogRoot . '/' . $file;
        if (!is_file($path) || !preg_match('/\.pdf$/i', $file)) {
            continue;
        }
        $base = strtoupper((string)pathinfo($file, PATHINFO_FILENAME));
        if ($base === '') {
            continue;
        }
        $index[$base] = $file;
    }
    return $index;
}

try {
    $pdo = getDBConnection();
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('Could not resolve project root.');
    }

    echo "=== Accessories media backfill ===\n";
    echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN\n";
    if ($onlyProduct !== '') {
        echo "Filter: product_id = {$onlyProduct}\n";
    }

    $imageIndex = [];
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
            $key = normalizeAccessoriesStem($stem);
            if ($key === '') {
                continue;
            }
            $abs = $fileInfo->getRealPath() ?: $fileInfo->getPathname();
            $rel = toRelativeAccessoriesPath($abs, $projectRoot);
            $candidate = [
                'path' => $rel,
                'path_rank' => accessoriesPathRank($abs),
                'ext_rank' => $extensionRank[$ext],
            ];
            if (!isset($imageIndex[$key])) {
                $imageIndex[$key] = $candidate;
                continue;
            }
            $current = $imageIndex[$key];
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
                $imageIndex[$key] = $candidate;
            }
        }
    }
    echo 'Indexed accessories images: ' . count($imageIndex) . "\n";

    $pdfIndex = buildPdfIndex($projectRoot . '/Cadman_catalog');
    echo 'Indexed catalog PDFs: ' . count($pdfIndex) . "\n";

    $placeholders = implode(',', array_fill(0, count($targetCategories), '?'));
    $sql = "SELECT product_id, category, image_files, has_images, page_reference, pdf_file
            FROM catalog_products
            WHERE category IN ($placeholders)";
    $params = $targetCategories;
    if ($onlyProduct !== '') {
        $sql .= ' AND product_id = ?';
        $params[] = $onlyProduct;
    }
    $sql .= ' ORDER BY category, product_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Rows in scope: ' . count($rows) . "\n";

    $changes = [];
    $skippedValidImage = 0;
    $imageMatches = 0;
    $pdfMatches = 0;
    $unmatched = 0;

    foreach ($rows as $row) {
        $pid = (string)$row['product_id'];
        $key = normalizeAccessoriesStem($pid);
        $oldImage = (string)($row['image_files'] ?? '');
        $oldPdf = (string)($row['pdf_file'] ?? '');
        $newImage = null;
        $newPdf = null;
        $actions = [];

        $hasValidImage = storedAccessoriesPathExists($oldImage, $projectRoot);
        if ($hasValidImage) {
            $skippedValidImage++;
        } elseif (isset($imageIndex[$key]['path'])) {
            $newImage = $imageIndex[$key]['path'];
            $actions[] = 'image';
            $imageMatches++;
        }

        if (trim($oldPdf) === '') {
            $pageReference = strtoupper(trim((string)($row['page_reference'] ?? '')));
            if ($pageReference !== '' && isset($pdfIndex[$pageReference])) {
                $newPdf = $pdfIndex[$pageReference];
                $actions[] = 'pdf';
                $pdfMatches++;
            }
        }

        if ($actions) {
            $changes[] = [
                'product_id' => $pid,
                'category' => (string)$row['category'],
                'old_image_files' => $oldImage,
                'old_has_images' => (int)($row['has_images'] ?? 0),
                'new_image_files' => $newImage,
                'old_pdf_file' => $oldPdf,
                'new_pdf_file' => $newPdf,
                'actions' => implode(',', $actions),
            ];
        } elseif (!$hasValidImage) {
            $unmatched++;
        }
    }

    echo 'Rows with media changes: ' . count($changes) . "\n";
    echo 'Image matches found: ' . $imageMatches . "\n";
    echo 'PDF matches found: ' . $pdfMatches . "\n";
    echo 'Skipped (existing valid image path): ' . $skippedValidImage . "\n";
    echo 'No image/PDF match found: ' . $unmatched . "\n";

    if ($csvPath !== '') {
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0775, true);
        }
        $fh = fopen($csvPath, 'w');
        if ($fh !== false) {
            fputcsv($fh, ['product_id', 'category', 'old_image_files', 'old_has_images', 'new_image_files', 'old_pdf_file', 'new_pdf_file', 'actions']);
            foreach ($changes as $c) {
                fputcsv($fh, [$c['product_id'], $c['category'], $c['old_image_files'], $c['old_has_images'], $c['new_image_files'], $c['old_pdf_file'], $c['new_pdf_file'], $c['actions']]);
            }
            fclose($fh);
            echo "Wrote mapping CSV: {$csvPath}\n";
        }
    }

    $sampleLimit = min(20, count($changes));
    if ($sampleLimit > 0) {
        echo "\nSample changes:\n";
        for ($i = 0; $i < $sampleLimit; $i++) {
            $c = $changes[$i];
            echo '  ' . $c['product_id'] . ' [' . $c['category'] . '] ' . $c['actions'];
            if ($c['new_image_files'] !== null) {
                echo ' image=' . $c['new_image_files'];
            }
            if ($c['new_pdf_file'] !== null) {
                echo ' pdf=' . $c['new_pdf_file'];
            }
            echo "\n";
        }
    }

    if (!$apply) {
        echo "\nDry-run complete. Re-run with --apply to persist updates.\n";
        exit(0);
    }

    if (!$changes) {
        echo "\nNo media changes to apply.\n";
        exit(0);
    }

    $runId = date('Ymd_His');
    $backupTable = 'acc_media_fix_backup_' . $runId;

    $pdo->exec(
        "CREATE TABLE {$backupTable} AS
         SELECT product_id, image_files, has_images, pdf_file
         FROM catalog_products
         WHERE 1 = 0"
    );

    $pdo->beginTransaction();

    $backupInsert = $pdo->prepare(
        "INSERT INTO {$backupTable} (product_id, image_files, has_images, pdf_file)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($changes as $c) {
        $backupInsert->execute([
            $c['product_id'],
            $c['old_image_files'] === '' ? null : $c['old_image_files'],
            $c['old_has_images'],
            $c['old_pdf_file'] === '' ? null : $c['old_pdf_file'],
        ]);
    }

    $update = $pdo->prepare(
        "UPDATE catalog_products
         SET image_files = COALESCE(?, image_files),
             has_images = CASE WHEN ? IS NOT NULL THEN 1 ELSE has_images END,
             pdf_file = COALESCE(?, pdf_file)
         WHERE product_id = ?
           AND category IN ($placeholders)"
    );

    $applied = 0;
    foreach ($changes as $c) {
        $params = [
            $c['new_image_files'],
            $c['new_image_files'],
            $c['new_pdf_file'],
            $c['product_id'],
            ...$targetCategories,
        ];
        $update->execute($params);
        $applied += $update->rowCount();
    }

    $pdo->commit();

    echo "\nApply complete. Rows updated: {$applied}\n";
    echo "Backup table: {$backupTable}\n";
    echo "Rollback example:\n";
    echo "  UPDATE catalog_products cp JOIN {$backupTable} b USING(product_id)\n";
    echo "  SET cp.image_files = b.image_files, cp.has_images = b.has_images, cp.pdf_file = b.pdf_file;\n";

    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
