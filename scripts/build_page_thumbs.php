<?php
/**
 * Build per-page thumbnail cache from /Cadman_catalog/*.pdf
 *
 * Output: /assets/page_thumbs/page-{N}.jpg  (N = integer page reference)
 * Source: /Cadman_catalog/page_{NN}{letter}.pdf
 *
 * For pages with multiple variants (e.g. 07a, 07b, 07c), the first variant
 * found wins. Existing thumbnails are skipped unless --force is passed.
 *
 * Requires: pdftoppm (poppler-utils), convert (ImageMagick) — both verified
 * on the prod server.
 *
 * Usage:
 *   php scripts/build_page_thumbs.php
 *   php scripts/build_page_thumbs.php --force
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$force = in_array('--force', $argv, true);

$catalogDir = realpath(__DIR__ . '/../Cadman_catalog');
$outDir     = realpath(__DIR__ . '/..') . '/assets/page_thumbs';

if (!$catalogDir || !is_dir($catalogDir)) {
    fwrite(STDERR, "Catalog dir not found: $catalogDir\n");
    exit(1);
}
if (!is_dir($outDir) && !mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Could not create $outDir\n");
    exit(1);
}

$pdfs = glob($catalogDir . '/page_*.pdf');
sort($pdfs); // ensures page_07a comes before page_07b, etc.

$built = 0; $skipped = 0; $failed = 0;
$seen = [];

foreach ($pdfs as $pdf) {
    $base = basename($pdf);
    if (!preg_match('/^page_(\d+)[a-z]?\.pdf$/i', $base, $m)) continue;
    $pageNum = (int)$m[1];
    if (isset($seen[$pageNum])) { $skipped++; continue; } // first variant wins
    $seen[$pageNum] = true;

    $out = "$outDir/page-$pageNum.jpg";
    if (!$force && is_file($out) && filesize($out) > 0) { $skipped++; continue; }

    // pdftoppm -> PPM via stdout -> convert -> JPG
    // 150 DPI gives ~1200px tall, good for product hero; convert resizes to 600px.
    $tmpPrefix = sys_get_temp_dir() . '/cadthumb_' . posix_getpid();
    $cmd = sprintf(
        'pdftoppm -r 120 -jpeg -f 1 -l 1 %s %s 2>&1',
        escapeshellarg($pdf),
        escapeshellarg($tmpPrefix)
    );
    exec($cmd, $log, $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "pdftoppm failed for $base: " . implode("\n", $log) . "\n");
        $failed++;
        @array_map('unlink', glob($tmpPrefix . '*'));
        continue;
    }

    $produced = glob($tmpPrefix . '*.jpg');
    if (!$produced) {
        fwrite(STDERR, "No image produced for $base\n");
        $failed++;
        continue;
    }
    $tmpImg = $produced[0];

    // Resize to max 600x600 with ImageMagick, decent quality.
    $cmd2 = sprintf(
        'convert %s -resize 600x600\> -strip -quality 82 %s 2>&1',
        escapeshellarg($tmpImg),
        escapeshellarg($out)
    );
    exec($cmd2, $log2, $rc2);
    @unlink($tmpImg);
    if ($rc2 !== 0 || !is_file($out)) {
        fwrite(STDERR, "convert failed for $base: " . implode("\n", $log2) . "\n");
        $failed++;
        continue;
    }

    echo "  built page-$pageNum.jpg from $base\n";
    $built++;
}

echo "\nDone. built=$built skipped=$skipped failed=$failed total_pdfs=" . count($pdfs) . "\n";
