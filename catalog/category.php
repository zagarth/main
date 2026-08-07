<?php
/**
 * Collection listing — /{parent}/{collection}/
 * Routed from .htaccess. For band collections, uses BandsGrouper
 * to collapse M/L size variants and width-variants into pattern/series cards
 * (matches the legacy Bands.php layout & XML chart data).
 */

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/school_shoulder_customizer.php';

$parentSlug     = sanitize_seg($_GET['parent']     ?? '');
$collectionSlug = sanitize_seg($_GET['collection'] ?? '');
$catalog = new CatalogQuery();
$collection = $catalog->findCollection($parentSlug, $collectionSlug);

if (!$collection) {
    http_response_code(404);
    catalog_render_top(['title' => 'Not Found | Cadman']);
    echo '<div class="catalog-empty"><h1>Collection not found</h1></div>';
    catalog_render_bottom();
    exit;
}

$dbCategories = $collection['db'] ?? [];
$isBands = false;
$primaryCat = '';
foreach ($dbCategories as $dbCat) {
    if (BandsGrouper::isBandCategory($dbCat)) { $isBands = true; $primaryCat = $dbCat; break; }
}

$bc = (new Breadcrumbs())
    ->add('Home', '/')
    ->add($collection['_parent']['label'], $collection['_parent']['url'])
    ->add($collection['label']);

$title = $collection['label'] . ' | Cadman Manufacturing';

/* ============================================================
 * BANDS PATH — pattern/series grouped cards via XML chart
 * ============================================================ */
if ($isBands) {
    $groups = BandsGrouper::getGroups($primaryCat);
    $totalGroups = count($groups);
    $desc = "Browse Cadman's {$collection['label']} — {$totalGroups} pattern" . ($totalGroups === 1 ? '' : 's') . " in 10K, 14K, 18K gold and sterling.";

    catalog_render_top([
        'title'       => $title,
        'description' => $desc,
        'canonical'   => catalog_canonical($collection['url']),
    ], $bc);
    ?>
    <header class="bands-header">
        <div class="collection-header">
            <h1><?= htmlspecialchars($collection['label']) ?> Collection</h1>
            <p><?= (int)$totalGroups ?> design<?= $totalGroups === 1 ? '' : 's' ?> &mdash; each available in multiple widths and sizes.</p>
        </div>
    </header>

    <?php if (!$groups): ?>
        <div class="catalog-empty"><p>No products found.</p></div>
    <?php else: ?>
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
        <?php foreach ($groups as $g):
            $imageDir = $g['image_dir'];
            $images   = $g['images'];
            $canonicalProduct = $catalog->getProduct($g['canonical_pid']);
            $pdfFile  = $canonicalProduct['pdf_file']      ?? '';
            $pageRef  = $canonicalProduct['page_reference'] ?? '';
            $pdfUrl   = $canonicalProduct ? catalog_pdf_url($canonicalProduct) : '';
            // Treat the 'fancy.pdf' placeholder as no-PDF so the card falls
            // through to the per-category "Image coming soon" SVG.
            $hasRealPdf = $pdfFile && strtolower($pdfFile) !== 'fancy.pdf'
                          && strtolower((string)$pageRef) !== 'fancy';
            $hasImage = !empty($images);
            $mainImg  = $hasImage
                ? BandsGrouper::thumbUrl($g['category'], $images[0])
                : ($hasRealPdf
                    ? '/assets/placeholders/pdf_available.svg'
                    : '/assets/placeholders/' . $primaryCat . '.svg');
            if ($canonicalProduct) {
                $detailUrl = $catalog->productUrl($canonicalProduct, $collection);
            } else {
                $detailUrl = $collection['url'] . $g['canonical_pid'] . '/';
            }
            $variantsJson = json_encode($images);
            $widths = array_values(array_filter(array_unique(array_column($g['variants'], 'width'))));
            // Series base IDs (e.g. 3T18 / 4T18 / 5T18) — strip any trailing
            // M/L gender suffix so card shows the bare model number.
            $seriesIds = [];
            foreach ($g['variants'] as $v) {
                $bid = trim((string)($v['base_id'] ?? ''));
                if ($bid === '') continue;
                $bid = preg_replace('/[ML]$/i', '', $bid);
                if ($bid !== '' && !in_array($bid, $seriesIds, true)) {
                    $seriesIds[] = $bid;
                }
            }
            $displayTitle = trim((string)($g['display_name'] ?? ''));
            if ($displayTitle !== '') {
                $titleParts = preg_split('/[-_]/', $displayTitle, 2);
                if (isset($titleParts[1])) {
                    $rhsTitle = trim((string)$titleParts[1]);
                    if ($rhsTitle !== '') {
                        $displayTitle = $rhsTitle;
                    }
                }
            }
            if ($displayTitle === '') {
                $displayTitle = (string)($g['canonical_pid'] ?? '');
            }
        ?>
            <div class="item jewelry-item paginated-item"
                 data-category="<?= htmlspecialchars($g['category']) ?>"
                 data-base-name="<?= htmlspecialchars($g['group_key']) ?>">

                                <a href="<?= htmlspecialchars($detailUrl) ?>" class="rotating-image-container"
                   data-variants='<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>'
                   data-image-dir="<?= htmlspecialchars($imageDir) ?>"
                   data-current-variant="0"
                   aria-label="<?= htmlspecialchars($g['display_name']) ?>">
                    <img src="<?= htmlspecialchars($mainImg) ?>"
                         alt="<?= htmlspecialchars($g['display_name']) ?>"
                         class="rotating-image"
                         loading="lazy">
                    <?php if (count($images) > 1): ?>
                        <div class="rotation-indicator">▶</div>
                    <?php endif; ?>
                </a>
                <?php if (!$hasImage && $pdfUrl): ?>
                    <a class="related-pdf-corner" href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener" aria-label="View catalog PDF for <?= htmlspecialchars($g['display_name']) ?>">📄 PDF</a>
                <?php endif; ?>

                <div class="item-info">
                    <h3 class="item-title">
                        <a href="<?= htmlspecialchars($detailUrl) ?>" style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars($displayTitle) ?>
                        </a>
                    </h3>
                    <p class="item-description"><?= htmlspecialchars($g['description']) ?></p>
                    <?php if (count($seriesIds) > 1): ?>
                        <p class="item-description item-series"><strong>Series:</strong> <?= htmlspecialchars(implode(' / ', $seriesIds)) ?></p>
                    <?php endif; ?>
                    <?php if ($widths): ?>
                        <p class="item-description"><strong>Widths:</strong> <?= htmlspecialchars(implode(' / ', $widths)) ?></p>
                    <?php endif; ?>

                    <div class="item-actions">
                        <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php include __DIR__ . '/../includes/pagination_controls.php'; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.CatalogPagination) CatalogPagination.init();
    });
    </script>
    <?php endif; ?>
    <?php
    catalog_render_bottom();
    exit;
}

/* ============================================================
 * NON-BANDS PATH — generic per-row listing
 * ============================================================ */
$facets = [];
if (!empty($_GET['subcategory'])) {
    $facets['subcategory'] = sanitize_seg($_GET['subcategory']);
} elseif (($collection['_parent']['slug'] ?? '') === 'personal-family') {
    if (($collection['slug'] ?? '') === 'family') {
        $facets['subcategory'] = 'family';
    } elseif (($collection['slug'] ?? '') === 'mother-daughter') {
        $facets['subcategory'] = 'mother_daughter';
    }
}
if (!empty($_GET['series'])) {
    $facets['series'] = sanitize_seg($_GET['series'], 100);
}

$listOrder = (($collection['slug'] ?? '') === 'mother-daughter') ? 'images_first' : 'name';
$products = $catalog->getProducts($collection, array_merge($facets, ['order' => $listOrder]));
$total    = $catalog->countProducts($collection, $facets);
$subcats  = $catalog->getSubcategories($collection);

$isWeddingEngagement = (($collection['_parent']['slug'] ?? '') === 'wedding'
    && ($collection['slug'] ?? '') === 'engagement');

if ($isWeddingEngagement && $products) {
    // Engagement has DB rows for alt/view/art variants. Collapse them into one card.
    $normalizeEngagementKey = static function (string $pid): string {
        $pid = trim($pid);
        return (string)preg_replace('/(?:[-_](?:alt|view|art)\d*)$/i', '', $pid);
    };

    $groups = [];
    foreach ($products as $row) {
        $pid = trim((string)($row['product_id'] ?? ''));
        if ($pid === '') {
            continue;
        }
        $key = $normalizeEngagementKey($pid);
        if ($key === '') {
            $key = $pid;
        }
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $row;
    }

    $groupedProducts = [];
    foreach ($groups as $basePid => $rows) {
        $canonical = $rows[0];
        foreach ($rows as $candidate) {
            if (strcasecmp((string)($candidate['product_id'] ?? ''), $basePid) === 0) {
                $canonical = $candidate;
                break;
            }
        }

        $variantFiles = [];
        $variantImageDir = '';
        foreach ($rows as $variantRow) {
            $storedPath = trim((string)($variantRow['image_files'] ?? ''));
            if ($storedPath === '' || strcasecmp($storedPath, 'no images found') === 0) {
                continue;
            }
            $storedPath = str_replace('\\', '/', $storedPath);
            $file = basename($storedPath);
            if ($file === '' || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $file)) {
                continue;
            }
            if ($variantImageDir === '') {
                $dir = trim(dirname($storedPath), '/.');
                if ($dir !== '') {
                    $variantImageDir = '/' . $dir;
                }
            }
            $variantFiles[] = $file;
        }

        $variantRank = static function (string $file, string $basePid): int {
            $stem = (string)pathinfo($file, PATHINFO_FILENAME);
            if (strcasecmp($stem, $basePid) === 0) {
                return 0;
            }
            if (preg_match('/(?:[-_]alt\d*)$/i', $stem)) {
                return 1;
            }
            if (preg_match('/(?:[-_]view\d*)$/i', $stem)) {
                return 2;
            }
            if (preg_match('/(?:[-_]art\d*)$/i', $stem)) {
                return 3;
            }
            return 4;
        };

        $variantFiles = array_values(array_unique($variantFiles));
        usort($variantFiles, static function (string $a, string $b) use ($variantRank, $basePid): int {
            $ra = $variantRank($a, $basePid);
            $rb = $variantRank($b, $basePid);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            return strcasecmp($a, $b);
        });

        $canonical['product_id'] = $basePid;
        $canonical['_variant_files'] = $variantFiles;
        $canonical['_variant_image_dir'] = $variantImageDir;
        $groupedProducts[] = $canonical;
    }

    usort($groupedProducts, static function (array $a, array $b): int {
        return strcasecmp((string)($a['product_id'] ?? ''), (string)($b['product_id'] ?? ''));
    });

    $products = $groupedProducts;
    $total = count($products);
}

$isPidPrimaryCollection = static function (array $row, array $collection): bool {
    $category = (string)($row['category'] ?? '');
    if (in_array($category, ['signets', 'gents_rings', 'corporate', 'emblematic', 'professional', 'idents', 'lockets'], true)) {
        return true;
    }

    if (($collection['slug'] ?? '') === 'mother-daughter') {
        return (($row['category'] ?? '') === 'family');
    }

    if (($collection['_parent']['slug'] ?? '') === 'wedding' && ($collection['slug'] ?? '') === 'engagement') {
        return true;
    }

    return (($row['category'] ?? '') === 'family')
        && (($row['subcategory'] ?? '') === 'mother_daughter')
        && (($collection['slug'] ?? '') === 'family');
};

$desc = "Browse Cadman's " . strtolower($collection['label']) . " — " .
        $total . " design" . ($total === 1 ? '' : 's') . ".";

catalog_render_top([
    'title'       => $title,
    'description' => $desc,
    'canonical'   => catalog_canonical($collection['url']),
], $bc);

$activeSub = $facets['subcategory'] ?? '';
$isSchoolParent = (($collection['_parent']['slug'] ?? '') === 'school');
$isSchoolShoulders = (($collection['_parent']['slug'] ?? '') === 'school' && ($collection['slug'] ?? '') === 'shoulders');
$schoolCollectionSlug = (string)($collection['slug'] ?? '');
$schoolFilters = [
    ['slug' => 'bands', 'label' => 'Bands', 'url' => '/school/bands/'],
    ['slug' => 'crest-tops', 'label' => 'Crest Tops', 'url' => '/school/crest-tops/'],
    ['slug' => 'shoulders', 'label' => 'Shoulders', 'url' => '/school/shoulders/'],
];
?>

<header class="bands-header">
    <div class="collection-header">
        <h1><?= htmlspecialchars($collection['label']) ?> Collection</h1>
        <p><?= (int)$total ?> design<?= $total === 1 ? '' : 's' ?> available</p>
    </div>
</header>

<?php
if ($isSchoolParent):
?>
<div class="category-filter">
    <a href="/school/" class="filter-btn">All</a>
    <?php foreach ($schoolFilters as $schoolFilter): ?>
        <a href="<?= htmlspecialchars($schoolFilter['url']) ?>" class="filter-btn <?= $schoolCollectionSlug === $schoolFilter['slug'] ? 'active' : '' ?>">
            <?= htmlspecialchars($schoolFilter['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php
endif;

if ($isSchoolShoulders) {
    renderSchoolShoulderCustomizer(['visible' => true]);
    renderSchoolShoulderCustomizerScript([
        'image_base' => '/school_php/images/Shoulders',
        'auto_init'  => true,
    ]);
    catalog_render_bottom();
    exit;
}
?>

<?php if (!$isSchoolParent && count($subcats) > 1): ?>
<div class="category-filter">
    <a href="<?= htmlspecialchars($collection['url']) ?>" class="filter-btn <?= $activeSub === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($subcats as $s):
        $sub = $s['subcategory'];
        $label = $LABELS[$sub] ?? ucwords(str_replace('_', ' ', $sub));
        $href = $collection['url'] . '?subcategory=' . urlencode($sub);
    ?>
        <a href="<?= htmlspecialchars($href) ?>" class="filter-btn <?= $sub === $activeSub ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$products): ?>
    <div class="catalog-empty"><p>No products found.</p></div>
<?php else: ?>
<div class="gallery-container">
    <div class="gallery-grid" id="jewelry-gallery">
    <?php foreach ($products as $p):
        $url   = $catalog->productUrl($p, $collection);
        $price = $catalog->calculatePrice($p['product_id']);
        $img = $catalog->getProductImage($p);
        $variants = $p['_variant_files'] ?? [];
        $variantsJson = $variants ? json_encode($variants) : '';
        $variantImageDir = (string)($p['_variant_image_dir'] ?? '');
        if ($variantImageDir === '' && $variants) {
            $imgPath = (string)(parse_url($img, PHP_URL_PATH) ?? '');
            $imgPath = str_replace('\\', '/', $imgPath);
            $imgDir = trim(dirname($imgPath), '/.');
            if ($imgDir !== '') {
                $variantImageDir = '/' . $imgDir;
            }
        }
        $imgIsPdfPlaceholder = str_ends_with($img, '/pdf_available.svg');
        $pdfUrl  = catalog_pdf_url($p);
$cardTitle = '';
        if ($isPidPrimaryCollection($p, $collection)) {
            $cardTitle = trim((string)($p['product_id'] ?? ''));
        } else {
            $cardTitle = trim((string)($p['product_name'] ?? ''));
            if ($cardTitle !== '') {
                $titleParts = preg_split('/[-_]/', $cardTitle, 2);
                if (isset($titleParts[1])) {
                    $rhsTitle = trim((string)$titleParts[1]);
                    if ($rhsTitle !== '') {
                        $cardTitle = $rhsTitle;
                    }
                }
            }
        }
        if ($cardTitle === '') {
            $cardTitle = (string)($p['product_id'] ?? '');
        }
        if ($isSchoolParent) {
            $normalizedCardTitle = strtolower(trim($cardTitle));
            if (in_array($normalizedCardTitle, ['jewelry item', 'item'], true)) {
                $cardTitle = trim((string)($p['product_id'] ?? ''));
            }
        }
    ?>
        <div class="item jewelry-item paginated-item" data-category="<?= htmlspecialchars($p['category']) ?>">
            <a href="<?= htmlspecialchars($url) ?>" class="rotating-image-container"
               <?php if (count($variants) > 1 && $variantsJson !== '' && $variantImageDir !== ''): ?>
               data-variants='<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>'
               data-image-dir="<?= htmlspecialchars($variantImageDir) ?>"
               data-current-variant="0"
               <?php endif; ?>>
                <img src="<?= htmlspecialchars($img) ?>"
                     alt="<?= htmlspecialchars($p['product_name'] ?: $p['product_id']) ?>"
                     class="rotating-image" loading="lazy">
                <?php if (count($variants) > 1): ?>
                    <div class="rotation-indicator">▶</div>
                <?php endif; ?>
            </a>
            <?php if ($imgIsPdfPlaceholder && $pdfUrl): ?>
                <a class="related-pdf-corner" href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener" aria-label="View catalog PDF for <?= htmlspecialchars($p['product_id']) ?>">📄 PDF</a>
            <?php endif; ?>
            <div class="item-info">
                <h3 class="item-title">
                    <a href="<?= htmlspecialchars($url) ?>" style="color:inherit;text-decoration:none;">
                        <?= htmlspecialchars($cardTitle) ?>
                    </a>
                </h3>
                <p class="item-description"><?= htmlspecialchars($p['product_id']) ?><?= !empty($p['width_mm']) ? ' · ' . htmlspecialchars($p['width_mm']) . 'mm' : '' ?></p>
                <?php if ($price !== null): ?>
                    <div class="price">$<?= number_format($price, 2) ?></div>
                <?php endif; ?>
                <div class="item-actions">
                    <a href="<?= htmlspecialchars($url) ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/../includes/pagination_controls.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.CatalogPagination) CatalogPagination.init();
});
</script>
<?php endif; ?>

<?php catalog_render_bottom(); ?>
