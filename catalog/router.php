<?php
/**
 * 3-segment router — /{parent}/{collection}/{seg}/
 * Decides:
 *   - If seg parses as "{product_id}-{slug}" and product_id resolves → product page
 *   - If seg parses as a bare product_id → product page (will 301 to canonical)
 *   - If seg matches a known subcategory or series in this collection → filtered listing
 *   - Else → 404
 */

require __DIR__ . '/_bootstrap.php';

$parentSlug     = sanitize_seg($_GET['parent']     ?? '');
$collectionSlug = sanitize_seg($_GET['collection'] ?? '');
$rawSeg = (string)($_GET['seg'] ?? '');
$seg = substr((string)(preg_replace('/[^A-Za-z0-9_-]+/', '', $rawSeg) ?? ''), 0, 120);

$catalog = new CatalogQuery();
$collection = $catalog->findCollection($parentSlug, $collectionSlug);
if (!$collection || $seg === '') {
    http_response_code(404);
    catalog_render_top(['title' => 'Not Found | Cadman']);
    echo '<div class="catalog-empty"><h1>Not found</h1></div>';
    catalog_render_bottom();
    exit;
}

// Attempt to resolve as a product
$candidatePid = null;
$candidateSlug = null;
if (strpos($seg, '-') !== false) {
    [$candidatePid, $candidateSlug] = explode('-', $seg, 2);
} else {
    $candidatePid = $seg;
}
$candidatePid = substr((string)(preg_replace('/[^A-Za-z0-9_-]+/', '', (string)$candidatePid) ?? ''), 0, 32);

if ($candidatePid !== '') {
    $product = $catalog->getProduct($candidatePid);
    if ($product) {
        // Verify product belongs to this collection
        $collectionCats = $collection['db'] ?? [];
        if (in_array($product['category'], $collectionCats, true)) {
            $canonicalSlug = $product['slug'] ?: CatalogQuery::slugify($product['product_name'] ?? $product['product_id']);
            $canonicalSeg = $candidatePid . '-' . $canonicalSlug;
            if ($seg !== $canonicalSeg) {
                header('Location: ' . $collection['url'] . $canonicalSeg, true, 301);
                exit;
            }
            // Render product
            $_GET['parent']     = $parentSlug;
            $_GET['collection'] = $collectionSlug;
            $_GET['id_slug']    = $canonicalSeg;
            require __DIR__ . '/product.php';
            exit;
        }
    }
}

// Try as subcategory facet
$subcats = $catalog->getSubcategories($collection);
foreach ($subcats as $s) {
    if (strtolower($s['subcategory']) === strtolower($seg)) {
        $_GET['parent']      = $parentSlug;
        $_GET['collection']  = $collectionSlug;
        $_GET['subcategory'] = $s['subcategory'];
        require __DIR__ . '/category.php';
        exit;
    }
}

// Try as series facet (case-insensitive)
$seriesList = $catalog->getSeries($collection);
foreach ($seriesList as $s) {
    if (strtolower(CatalogQuery::slugify($s['series'])) === strtolower($seg)) {
        $_GET['parent']     = $parentSlug;
        $_GET['collection'] = $collectionSlug;
        $_GET['series']     = $s['series'];
        require __DIR__ . '/category.php';
        exit;
    }
}

http_response_code(404);
catalog_render_top(['title' => 'Not Found | Cadman']);
echo '<div class="catalog-empty"><h1>Not found</h1><p>"' . htmlspecialchars($seg) . '" did not match a product or subcategory in ' . htmlspecialchars($collection['label']) . '.</p><p><a href="' . htmlspecialchars($collection['url']) . '">Back to ' . htmlspecialchars($collection['label']) . '</a></p></div>';
catalog_render_bottom();
