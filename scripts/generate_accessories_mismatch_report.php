<?php
/**
 * Generate accessories media mismatch reports for manual mapping review.
 *
 * This script does not update the database. It produces CSV files that show:
 * 1) category-level summary
 * 2) rows missing valid image paths with PDF resolvability hints
 * 3) ranked candidate image mappings for each unmatched product
 *
 * Usage:
 *   php scripts/generate_accessories_mismatch_report.php
 *   php scripts/generate_accessories_mismatch_report.php --categories=lockets,crosses,pendants,bracelets,medical
 *   php scripts/generate_accessories_mismatch_report.php --out-dir=scripts
 *   php scripts/generate_accessories_mismatch_report.php --max-candidates=8
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

$opts = getopt('', ['categories::', 'out-dir::', 'max-candidates::']);

$defaultCategories = ['lockets', 'crosses', 'pendants', 'bracelets', 'idents', 'medical'];
$categories = $defaultCategories;
if (isset($opts['categories']) && trim((string)$opts['categories']) !== '') {
    $categories = array_values(array_filter(array_map(
        static fn(string $v): string => strtolower(trim($v)),
        explode(',', (string)$opts['categories'])
    )));
    if (!$categories) {
        $categories = $defaultCategories;
    }
}

$outDir = isset($opts['out-dir']) && trim((string)$opts['out-dir']) !== ''
    ? trim((string)$opts['out-dir'])
    : (__DIR__);

$maxCandidates = isset($opts['max-candidates']) ? (int)$opts['max-candidates'] : 5;
if ($maxCandidates < 1) {
    $maxCandidates = 1;
}
if ($maxCandidates > 20) {
    $maxCandidates = 20;
}

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

function pathToRelative(string $absolutePath, string $projectRoot): string
{
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $normalized = str_replace('\\', '/', $absolutePath);
    $normalized = preg_replace('#/\./#', '/', $normalized) ?? $normalized;
    if (str_starts_with($normalized, $projectRoot . '/')) {
        return substr($normalized, strlen($projectRoot) + 1);
    }
    return ltrim($normalized, '/');
}

function pathSourceRank(string $absolutePath): int
{
    $p = str_replace('\\', '/', $absolutePath);
    return str_contains($p, '/accessories_php/thumbs/images/') ? 1 : 0;
}

function pathSourceLabel(string $absolutePath): string
{
    $p = str_replace('\\', '/', $absolutePath);
    if (str_contains($p, '/accessories_php/thumbs/images/')) {
        return 'thumbs';
    }
    return 'images';
}

function pathGroupLabel(string $absolutePath): string
{
    $p = str_replace('\\', '/', $absolutePath);
    if (str_contains($p, '/Crosses_and_Lockets/')) {
        return 'crosses_lockets';
    }
    if (str_contains($p, '/Pendant_earrings/')) {
        return 'pendant_earrings';
    }
    if (str_contains($p, '/Idents/')) {
        return 'idents';
    }
    return 'other';
}

function normalizeStem(string $stem): string
{
    if (preg_match('/^(.+?)(?:[_-](?:alt|view|art)\d*|[._-]\d+)$/i', $stem, $m)) {
        $stem = $m[1];
    }
    return strtoupper(trim($stem));
}

function alnumKey(string $value): string
{
    $v = strtoupper($value);
    $v = preg_replace('/[^A-Z0-9]/', '', $v) ?? '';
    return trim($v);
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
        if ($base !== '') {
            $index[$base] = $file;
        }
    }
    return $index;
}

function bestSingleCandidate(array $candidates): ?array
{
    if (!$candidates) {
        return null;
    }
    usort($candidates, static function (array $a, array $b): int {
        if ($a['source_rank'] !== $b['source_rank']) {
            return $a['source_rank'] <=> $b['source_rank'];
        }
        if ($a['ext_rank'] !== $b['ext_rank']) {
            return $a['ext_rank'] <=> $b['ext_rank'];
        }
        return strcmp((string)$a['path'], (string)$b['path']);
    });
    return $candidates[0];
}

function candidateKeysFromAlnum(string $alnum): array
{
    $keys = [];
    $add = static function (string $k) use (&$keys): void {
        $k = trim($k);
        if ($k === '' || strlen($k) < 2) {
            return;
        }
        if (!in_array($k, $keys, true)) {
            $keys[] = $k;
        }
    };

    $add($alnum);
    $add((string)(preg_replace('/[A-Z]+$/', '', $alnum) ?? ''));
    $add((string)(preg_replace('/^[A-Z]+/', '', $alnum) ?? ''));

    return $keys;
}

function keepAllowedGroups(array $candidates, array $allowedGroups): array
{
    if (!$allowedGroups) {
        return $candidates;
    }
    return array_values(array_filter($candidates, static function (array $c) use ($allowedGroups): bool {
        return in_array((string)($c['group'] ?? ''), $allowedGroups, true);
    }));
}

try {
    $pdo = getDBConnection();
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('Could not resolve project root.');
    }

    if (!is_dir($outDir)) {
        mkdir($outDir, 0775, true);
    }

    $timestamp = date('Ymd_His');
    $summaryCsv = rtrim($outDir, '/') . '/accessories_mismatch_summary_' . $timestamp . '.csv';
    $unmatchedCsv = rtrim($outDir, '/') . '/accessories_mismatch_unmatched_' . $timestamp . '.csv';
    $candidatesCsv = rtrim($outDir, '/') . '/accessories_mismatch_candidates_' . $timestamp . '.csv';

    echo "=== Accessories mismatch report ===\n";
    echo 'Categories: ' . implode(', ', $categories) . "\n";
    echo 'Output dir: ' . $outDir . "\n";

    $imageByNorm = [];
    $imageByAlnum = [];
    $allStemRows = [];

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

            $norm = normalizeStem($stem);
            $alnum = alnumKey($norm);
            if ($norm === '' || $alnum === '') {
                continue;
            }

            $abs = $fileInfo->getRealPath() ?: $fileInfo->getPathname();
            $candidate = [
                'raw_stem' => $stem,
                'norm_stem' => $norm,
                'alnum_stem' => $alnum,
                'path' => pathToRelative($abs, $projectRoot),
                'source_rank' => pathSourceRank($abs),
                'source' => pathSourceLabel($abs),
                'group' => pathGroupLabel($abs),
                'ext' => $ext,
                'ext_rank' => $extensionRank[$ext],
            ];

            $imageByNorm[$norm][] = $candidate;
            $imageByAlnum[$alnum][] = $candidate;
            $allStemRows[] = $candidate;
        }
    }

    $pdfIndex = buildPdfIndex($projectRoot . '/Cadman_catalog');

    echo 'Indexed image stems (normalized): ' . count($imageByNorm) . "\n";
    echo 'Indexed catalog PDFs: ' . count($pdfIndex) . "\n";

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql = "SELECT product_id, category, image_files, has_images, page_reference, pdf_file
            FROM catalog_products
            WHERE category IN ($placeholders)
            ORDER BY category, product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($categories);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Rows in scope: ' . count($rows) . "\n";

    $summary = [];
    foreach ($categories as $cat) {
        $summary[$cat] = [
            'category' => $cat,
            'total_rows' => 0,
            'valid_image_rows' => 0,
            'missing_image_rows' => 0,
            'exact_norm_rows' => 0,
            'exact_alnum_rows' => 0,
            'fuzzy_candidate_rows' => 0,
            'pdf_resolvable_rows' => 0,
            'pdf_unresolvable_rows' => 0,
        ];
    }

    $unmatchedRows = [];
    $candidateRows = [];

    $allAlnumKeys = array_keys($imageByAlnum);

    $allowedGroupsByCategory = [
        'crosses' => ['crosses_lockets'],
        'lockets' => ['crosses_lockets'],
        'pendants' => ['pendant_earrings'],
        'bracelets' => ['crosses_lockets', 'pendant_earrings'],
        'idents' => ['idents'],
        'medical' => ['idents', 'crosses_lockets', 'pendant_earrings'],
    ];

    foreach ($rows as $row) {
        $cat = (string)$row['category'];
        if (!isset($summary[$cat])) {
            continue;
        }

        $summary[$cat]['total_rows']++;

        $pid = strtoupper(trim((string)$row['product_id']));
        $normPid = normalizeStem($pid);
        $alnumPid = alnumKey($normPid);
        $currentImage = (string)($row['image_files'] ?? '');
        $currentPdf = (string)($row['pdf_file'] ?? '');
        $pageReference = strtoupper(trim((string)($row['page_reference'] ?? '')));
        $validImage = storedPathExists($currentImage, $projectRoot);

        if ($validImage) {
            $summary[$cat]['valid_image_rows']++;
            continue;
        }

        $summary[$cat]['missing_image_rows']++;

        $pdfResolvable = false;
        $pdfResolution = '';
        if ($currentPdf !== '' && is_file($projectRoot . '/Cadman_catalog/' . $currentPdf)) {
            $pdfResolvable = true;
            $pdfResolution = 'existing_pdf_file';
        } elseif ($pageReference !== '' && isset($pdfIndex[$pageReference])) {
            $pdfResolvable = true;
            $pdfResolution = 'page_reference_pdf';
        }

        if ($pdfResolvable) {
            $summary[$cat]['pdf_resolvable_rows']++;
        } else {
            $summary[$cat]['pdf_unresolvable_rows']++;
        }

        $ranked = [];

        $allowedGroups = $allowedGroupsByCategory[$cat] ?? [];

        if ($normPid !== '' && isset($imageByNorm[$normPid])) {
            $best = bestSingleCandidate(keepAllowedGroups($imageByNorm[$normPid], $allowedGroups));
            if ($best !== null) {
                $ranked[] = [
                    'match_tier' => 'exact_norm',
                    'confidence' => 'high',
                    'score' => 100,
                    'candidate' => $best,
                ];
                $summary[$cat]['exact_norm_rows']++;
            }
        }

        $alnumKeys = candidateKeysFromAlnum($alnumPid);
        $addedExactAlnum = false;
        foreach ($alnumKeys as $ak) {
            if (!isset($imageByAlnum[$ak])) {
                continue;
            }
            $best = bestSingleCandidate(keepAllowedGroups($imageByAlnum[$ak], $allowedGroups));
            if ($best === null) {
                continue;
            }
            $already = false;
            foreach ($ranked as $r) {
                if (($r['candidate']['path'] ?? '') === ($best['path'] ?? '')) {
                    $already = true;
                    break;
                }
            }
            if ($already) {
                continue;
            }
            $ranked[] = [
                'match_tier' => 'exact_alnum',
                'confidence' => 'high',
                'score' => $ak === $alnumPid ? 95 : 90,
                'candidate' => $best,
            ];
            $addedExactAlnum = true;
            if (count($ranked) >= $maxCandidates) {
                break;
            }
        }
        if ($addedExactAlnum) {
            $summary[$cat]['exact_alnum_rows']++;
        }

        $allowFuzzy = (bool)preg_match('/[A-Z]/', $alnumPid) && strlen($alnumPid) >= 3;
        if (count($ranked) < $maxCandidates && $alnumPid !== '' && $allowFuzzy) {
            $fuzzy = [];
            foreach ($allAlnumKeys as $kRaw) {
                $k = (string)$kRaw;
                if ($k === '' || strlen($k) < 2) {
                    continue;
                }
                $groupCandidates = keepAllowedGroups($imageByAlnum[$k] ?? [], $allowedGroups);
                if (!$groupCandidates) {
                    continue;
                }
                $dist = levenshtein($alnumPid, $k);
                $maxLen = max(strlen($alnumPid), strlen($k));
                if ($maxLen === 0) {
                    continue;
                }
                $ratio = 1 - ($dist / $maxLen);

                if ($dist <= 2 || $ratio >= 0.72 || str_contains($k, $alnumPid) || str_contains($alnumPid, $k)) {
                    $best = bestSingleCandidate($groupCandidates);
                    if ($best === null) {
                        continue;
                    }
                    $fuzzy[] = [
                        'match_tier' => 'fuzzy',
                        'confidence' => $dist <= 1 ? 'medium' : 'low',
                        'score' => (int)round($ratio * 80),
                        'distance' => $dist,
                        'candidate' => $best,
                    ];
                }
            }

            usort($fuzzy, static function (array $a, array $b): int {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }
                return ($a['distance'] ?? 99) <=> ($b['distance'] ?? 99);
            });

            foreach ($fuzzy as $f) {
                $exists = false;
                foreach ($ranked as $r) {
                    if (($r['candidate']['path'] ?? '') === ($f['candidate']['path'] ?? '')) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    continue;
                }
                $ranked[] = $f;
                if (count($ranked) >= $maxCandidates) {
                    break;
                }
            }
        }

        if ($ranked) {
            $summary[$cat]['fuzzy_candidate_rows']++;
        }

        $reason = 'no_image_stem_match';
        if ($ranked) {
            $reason = 'candidate_matches_found';
        }
        if (!$pdfResolvable) {
            if ($pageReference === '') {
                $reason .= ';missing_page_reference';
            } else {
                $reason .= ';no_pdf_for_page_reference';
            }
        }

        $unmatchedRows[] = [
            'product_id' => $pid,
            'category' => $cat,
            'current_image_files' => $currentImage,
            'current_has_images' => (string)($row['has_images'] ?? '0'),
            'current_pdf_file' => $currentPdf,
            'page_reference' => $pageReference,
            'pdf_resolvable' => $pdfResolvable ? 'yes' : 'no',
            'pdf_resolution' => $pdfResolution,
            'normalized_product_key' => $normPid,
            'alnum_product_key' => $alnumPid,
            'candidate_count' => (string)count($ranked),
            'reason' => $reason,
        ];

        $rank = 0;
        foreach ($ranked as $r) {
            $rank++;
            $cand = $r['candidate'];
            $candidateRows[] = [
                'product_id' => $pid,
                'category' => $cat,
                'candidate_rank' => (string)$rank,
                'match_tier' => (string)($r['match_tier'] ?? ''),
                'confidence' => (string)($r['confidence'] ?? ''),
                'score' => (string)($r['score'] ?? ''),
                'candidate_norm_key' => (string)($cand['norm_stem'] ?? ''),
                'candidate_alnum_key' => (string)($cand['alnum_stem'] ?? ''),
                'candidate_path' => (string)($cand['path'] ?? ''),
                'candidate_source' => (string)($cand['source'] ?? ''),
                'candidate_group' => (string)($cand['group'] ?? ''),
                'candidate_ext' => (string)($cand['ext'] ?? ''),
            ];
        }
    }

    $fhSummary = fopen($summaryCsv, 'w');
    if ($fhSummary === false) {
        throw new RuntimeException('Could not write summary CSV: ' . $summaryCsv);
    }
    fputcsv($fhSummary, [
        'category',
        'total_rows',
        'valid_image_rows',
        'missing_image_rows',
        'exact_norm_rows',
        'exact_alnum_rows',
        'fuzzy_candidate_rows',
        'pdf_resolvable_rows',
        'pdf_unresolvable_rows',
    ]);
    foreach ($categories as $cat) {
        fputcsv($fhSummary, $summary[$cat]);
    }
    fclose($fhSummary);

    $fhUnmatched = fopen($unmatchedCsv, 'w');
    if ($fhUnmatched === false) {
        throw new RuntimeException('Could not write unmatched CSV: ' . $unmatchedCsv);
    }
    fputcsv($fhUnmatched, [
        'product_id',
        'category',
        'current_image_files',
        'current_has_images',
        'current_pdf_file',
        'page_reference',
        'pdf_resolvable',
        'pdf_resolution',
        'normalized_product_key',
        'alnum_product_key',
        'candidate_count',
        'reason',
    ]);
    foreach ($unmatchedRows as $u) {
        fputcsv($fhUnmatched, $u);
    }
    fclose($fhUnmatched);

    $fhCandidates = fopen($candidatesCsv, 'w');
    if ($fhCandidates === false) {
        throw new RuntimeException('Could not write candidates CSV: ' . $candidatesCsv);
    }
    fputcsv($fhCandidates, [
        'product_id',
        'category',
        'candidate_rank',
        'match_tier',
        'confidence',
        'score',
        'candidate_norm_key',
        'candidate_alnum_key',
        'candidate_path',
        'candidate_source',
        'candidate_group',
        'candidate_ext',
    ]);
    foreach ($candidateRows as $c) {
        fputcsv($fhCandidates, $c);
    }
    fclose($fhCandidates);

    $totalMissing = count($unmatchedRows);
    $withCandidates = 0;
    foreach ($unmatchedRows as $u) {
        if ((int)$u['candidate_count'] > 0) {
            $withCandidates++;
        }
    }

    echo "\nGenerated files:\n";
    echo '  Summary:   ' . $summaryCsv . "\n";
    echo '  Unmatched: ' . $unmatchedCsv . "\n";
    echo '  Candidates:' . $candidatesCsv . "\n";
    echo "\nRows missing valid image path: {$totalMissing}\n";
    echo "Rows with at least one candidate: {$withCandidates}\n";
    echo "Rows with no candidates: " . ($totalMissing - $withCandidates) . "\n";

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
