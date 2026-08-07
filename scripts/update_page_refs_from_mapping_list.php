<?php
/**
 * Update catalog page references from a curated mapping list.
 *
 * Usage:
 *   php scripts/update_page_refs_from_mapping_list.php
 *   php scripts/update_page_refs_from_mapping_list.php --apply
 *
 * Notes:
 * - Default mode is DRY-RUN (no database writes).
 * - Matching is product_id only (case-insensitive).
 * - Applies page_reference, pdf_file, and has_pdf_page = 1.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_config.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$isApply = in_array('--apply', $argv, true);
$previewLimit = 40;

$rawMappings = <<<'MAP'
788DM & L — 4B
DF5T01M & L — 3
7TOORM & L — 1A
DF6T01M & L — 3
7T01CM & L — 3A
DF7T01M & L — 3
7T10M & 7T11L — 2E
DF8T01M & L — 3
7T18CM & 5T18CL — 1A
S120M & L — 4A
7T18M & L — 1A
S1T40M & L — 4A
7T20M & 7T21L — 2A
S1T44M & L — 2A
7T22M & 7T23L — 2B
S200RL — 1B
7T28M & 7T29L — 2B
S300RM & L — 1B
7T38M & L — 2F
S400RM & L — 1B
7T40M & L — 2F
S500RM & L — 1B
7T42M & 7T43L — 2B
S600RM & L — 1B
7T44M & L — 3A
W99M & L — 6A
7T46M & L — 3A
WCT25M & L — 2A
7T52M & L — 2A
WCT26M & L — 2A
7T58M & 7T59L — 2B
WE29M & L — 4B
7T62M & L — 2F
WU70M & L — 6A
7T74M & L — 2F
800M & L — 1B
800RM & L — 1B
864DM & L — 4A
8TOORM & L — 1A
8T01BM & L — 3A
8T04M & L — 2A
8T14M & L — 2F
8T18M & L — 1A
8T38M & L — 3B
8T42M & L — 3B
8T96M & L — 5A
8T98M & L — 5A
900M & L — 1B
914M & 914L — 4A
940M & L — 5A
944M & L — 4A
946M & L — 4A
9P46M & L — 4A
9T04M & L — 5A
9T06M & L — 5A
9T08M & L — 5A
9T10M & L — 5A
9T12M & L — 5A
D3T58M & L — 3G
D4T01M & L — 3
D4T14M & D4T15L — 3G
D4T16M & D4T17L — 3G
D4T80M & D4T81L — 3G
D4T94M & D4T95L — 3H
D5T01M & L — 3
D6T01M & L — 3
D7T01M & L — 3
D8T01M & L — 3
DF4T01M & L — 3
MAP;

function parsePageCode(string $pageCode): array
{
    $clean = strtoupper(trim($pageCode));
    if (!preg_match('/^(\d+)([A-Z]?)$/', $clean, $m)) {
        throw new InvalidArgumentException("Invalid page code: {$pageCode}");
    }

    $num = str_pad($m[1], 2, '0', STR_PAD_LEFT);
    $suffix = strtolower($m[2] ?? '');
    $pageReference = 'page_' . $num . $suffix;

    return [
        'page_code' => $clean,
        'page_reference' => $pageReference,
        'pdf_file' => $pageReference . '.pdf',
    ];
}

function expandCodes(string $codesPart): array
{
    $parts = array_values(array_filter(array_map('trim', explode('&', $codesPart)), static function ($v) {
        return $v !== '';
    }));

    if (count($parts) === 2) {
        $left = strtoupper($parts[0]);
        $right = strtoupper($parts[1]);

        if ($right === 'L' && preg_match('/M$/', $left)) {
            return [$left, substr($left, 0, -1) . 'L'];
        }

        return [$left, $right];
    }

    return [strtoupper(trim($codesPart))];
}

function parseMappings(string $raw): array
{
    $lines = preg_split('/\R/', $raw) ?: [];
    $expanded = [];
    $inputLineCount = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $inputLineCount++;

        if (!preg_match('/^(.+?)\s*[\x{2014}\x{2013}-]\s*([0-9]+[A-Za-z]?)$/u', $line, $m)) {
            throw new InvalidArgumentException("Could not parse mapping line: {$line}");
        }

        $codesPart = trim($m[1]);
        $pageCode = trim($m[2]);
        $page = parsePageCode($pageCode);
        $codes = expandCodes($codesPart);

        foreach ($codes as $code) {
            $normalizedCode = strtoupper(trim($code));
            $expanded[$normalizedCode] = [
                'code' => $normalizedCode,
                'page_code' => $page['page_code'],
                'new_page_reference' => $page['page_reference'],
                'new_pdf_file' => $page['pdf_file'],
                'new_has_pdf_page' => 1,
                'source_line' => $line,
            ];
        }
    }

    return [$inputLineCount, array_values($expanded)];
}

try {
    [$inputLineCount, $targets] = parseMappings($rawMappings);

    $pdo = getDBConnection();
    $findStmt = $pdo->prepare(
        "SELECT product_id, page_reference, pdf_file, has_pdf_page
         FROM catalog_products
         WHERE LOWER(product_id) = LOWER(?)
         LIMIT 1"
    );

    $updateStmt = $pdo->prepare(
        "UPDATE catalog_products
         SET page_reference = ?,
             pdf_file = ?,
             has_pdf_page = 1,
             updated_at = NOW()
         WHERE product_id = ?"
    );

    $rows = [];
    $missing = [];
    $counts = [
        'input_lines' => $inputLineCount,
        'expanded_ids' => count($targets),
        'matched' => 0,
        'missing' => 0,
        'unchanged' => 0,
        'would_update' => 0,
        'updated' => 0,
    ];

    foreach ($targets as $target) {
        $findStmt->execute([$target['code']]);
        $current = $findStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            $counts['missing']++;
            $missing[] = $target['code'];
            $rows[] = [
                'code' => $target['code'],
                'page_code' => $target['page_code'],
                'old_page_reference' => null,
                'new_page_reference' => $target['new_page_reference'],
                'old_pdf_file' => null,
                'new_pdf_file' => $target['new_pdf_file'],
                'old_has_pdf_page' => null,
                'new_has_pdf_page' => 1,
                'status' => 'missing',
            ];
            continue;
        }

        $counts['matched']++;
        $oldHasPdfPage = isset($current['has_pdf_page']) ? (int) $current['has_pdf_page'] : 0;
        $isChanged =
            ((string) ($current['page_reference'] ?? '') !== $target['new_page_reference']) ||
            ((string) ($current['pdf_file'] ?? '') !== $target['new_pdf_file']) ||
            ($oldHasPdfPage !== 1);

        $status = $isChanged ? 'would_update' : 'unchanged';
        if ($status === 'would_update') {
            $counts['would_update']++;
        } else {
            $counts['unchanged']++;
        }

        $rows[] = [
            'code' => $target['code'],
            'page_code' => $target['page_code'],
            'old_page_reference' => $current['page_reference'],
            'new_page_reference' => $target['new_page_reference'],
            'old_pdf_file' => $current['pdf_file'],
            'new_pdf_file' => $target['new_pdf_file'],
            'old_has_pdf_page' => $oldHasPdfPage,
            'new_has_pdf_page' => 1,
            'status' => $status,
            'db_product_id' => $current['product_id'],
        ];
    }

    if ($isApply) {
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($row['status'] !== 'would_update') {
                    continue;
                }

                $updateStmt->execute([
                    $row['new_page_reference'],
                    $row['new_pdf_file'],
                    $row['db_product_id'],
                ]);
                $counts['updated']++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    echo "\n=== " . ($isApply ? 'APPLY MODE' : 'DRY-RUN MODE') . " ===\n";
    echo "Input mapping lines: {$counts['input_lines']}\n";
    echo "Expanded unique product IDs: {$counts['expanded_ids']}\n";
    echo "Matched rows: {$counts['matched']}\n";
    echo "Missing IDs: {$counts['missing']}\n";
    echo "Unchanged rows: {$counts['unchanged']}\n";
    echo "Would-update rows: {$counts['would_update']}\n";
    if ($isApply) {
        echo "Updated rows: {$counts['updated']}\n";
    }

    echo "\n=== PREVIEW (first {$previewLimit}) ===\n";
    printf(
        "%-10s %-6s %-14s %-14s %-16s %-16s %-7s %-7s %-12s\n",
        'code',
        'page',
        'old_ref',
        'new_ref',
        'old_pdf',
        'new_pdf',
        'old_pdf?',
        'new_pdf?',
        'status'
    );
    echo str_repeat('-', 120) . "\n";

    foreach (array_slice($rows, 0, $previewLimit) as $row) {
        printf(
            "%-10s %-6s %-14s %-14s %-16s %-16s %-7s %-7s %-12s\n",
            $row['code'],
            $row['page_code'],
            (string) ($row['old_page_reference'] ?? '-'),
            $row['new_page_reference'],
            (string) ($row['old_pdf_file'] ?? '-'),
            $row['new_pdf_file'],
            (string) ($row['old_has_pdf_page'] ?? '-'),
            (string) $row['new_has_pdf_page'],
            $row['status']
        );
    }

    if (!empty($missing)) {
        echo "\n=== MISSING CODES (first 60) ===\n";
        foreach (array_slice($missing, 0, 60) as $code) {
            echo "- {$code}\n";
        }
        if (count($missing) > 60) {
            echo "... and " . (count($missing) - 60) . " more\n";
        }
    }

    if (!$isApply) {
        echo "\nNo changes were written. Run with --apply to persist updates.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
