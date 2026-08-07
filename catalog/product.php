<?php
/**
 * Product detail page — /{parent}/{collection}/{id}-{slug}
 * Entry: router.php after slug verification; also direct entry from .htaccess
 * 4-segment rule (for series/subcat-scoped product URLs in future).
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/catalog/ProductPricingProfile.php';
require_once __DIR__ . '/../includes/catalog/PlainBandPayloadBuilder.php';

$parentSlug     = sanitize_seg($_GET['parent']     ?? '');
$collectionSlug = sanitize_seg($_GET['collection'] ?? '');
$rawIdSlug = (string)($_GET['id_slug'] ?? '');
$idSlug = substr((string)(preg_replace('/[^A-Za-z0-9_-]+/', '', $rawIdSlug) ?? ''), 0, 140);

$catalog = new CatalogQuery();
$collection = $catalog->findCollection($parentSlug, $collectionSlug);
if (!$collection || $idSlug === '') {
    http_response_code(404);
    catalog_render_top(['title' => 'Not Found | Cadman']);
    echo '<div class="catalog-empty"><h1>Product not found</h1></div>';
    catalog_render_bottom();
    exit;
}

[$pid] = array_pad(explode('-', $idSlug, 2), 2, '');
$pid = substr((string)(preg_replace('/[^A-Za-z0-9_-]+/', '', (string)$pid) ?? ''), 0, 32);
$product = $catalog->getProduct($pid);

if (!$product) {
    http_response_code(404);
    catalog_render_top(['title' => 'Product Not Found | Cadman']);
    echo '<div class="catalog-empty"><h1>Product not found</h1><p><a href="' . htmlspecialchars($collection['url']) . '">Back to ' . htmlspecialchars($collection['label']) . '</a></p></div>';
    catalog_render_bottom();
    exit;
}

$canonicalUrl  = $catalog->productUrl($product, $collection);
$image         = $catalog->getProductImage($product);
$price         = $catalog->calculatePrice($product['product_id']);
$relatedItems  = $catalog->getRelatedProducts($product, 12);

$isPlainBands = (($product['category'] ?? '') === 'plain_bands');
$plainPayload = null;
$plainConfiguratorOptions = null;
$plainPriceByMetal = [];
$plainDefaultKarat = '950_silver';

if ($isPlainBands) {
    $plainPayload = PlainBandPayloadBuilder::build(getDBConnection(), $product);
    if (is_array($plainPayload)) {
        $plainConfiguratorOptions = is_array($plainPayload['configurator_options'] ?? null)
            ? $plainPayload['configurator_options']
            : null;
        $plainDefaultKarat = (string)($plainPayload['default_karat'] ?? '950_silver');
        $plainPriceByMetal = is_array($plainPayload['price_by_metal'] ?? null)
            ? $plainPayload['price_by_metal']
            : [];
        if (($plainPayload['base_price'] ?? null) !== null) {
            $price = (float)$plainPayload['base_price'];
        }
    }
}

// Generic per-metal pricing for all non-plain-band categories.
// ProductPricingProfile::build() queries the same products + product_variants
// tables that PricingCalculator uses, so prices are always live.
$genericPriceByMetal = [];
$basicMetalPriceList = [];
$genericDefaultKarat = 'STER';
$genericHasTT        = false;

if (!$isPlainBands && defined('SHOW_PRICING') && SHOW_PRICING) {
    $pricingProfile      = ProductPricingProfile::build(getDBConnection(), $product['product_id'], '950_silver', false);
    $genericPriceByMetal = $pricingProfile['price_by_metal'];
    if ($pricingProfile['base_price'] !== null) {
        $price = (float)$pricingProfile['base_price'];
    }
    // Prefer silver as the default displayed metal; fall back to 10K then first key.
    if (!empty($genericPriceByMetal)) {
        if (isset($genericPriceByMetal['STER']))      $genericDefaultKarat = 'STER';
        elseif (isset($genericPriceByMetal['10K']))   $genericDefaultKarat = '10K';
        else $genericDefaultKarat = array_key_first($genericPriceByMetal);
    }
    // Detect two-tone capability from DB (any *TT variant present).
    foreach (array_keys($genericPriceByMetal) as $_mk) {
        if (str_ends_with($_mk, 'TT')) { $genericHasTT = true; break; }
    }

    // Baseline metal pricing display for quick comparison.
    $basicMetalCandidates = [
        'STER' => ['STER'],
        '10K'  => ['10K', '10KTT'],
        '14K'  => ['14K', '14KTT'],
        '18K'  => ['18K', '18KTT'],
    ];
    foreach ($basicMetalCandidates as $label => $keys) {
        foreach ($keys as $k) {
            if (isset($genericPriceByMetal[$k])) {
                $basicMetalPriceList[$label] = (float)$genericPriceByMetal[$k];
                break;
            }
        }
    }
}

$isQuoteOnly = defined('SHOW_PRICING') && SHOW_PRICING && $price === null;
if ($isPlainBands && is_array($plainPayload)) {
    $isQuoteOnly = (bool)($plainPayload['quote_only'] ?? $isQuoteOnly);
}

$isPidPrimaryCollection = static function (array $row, array $collection): bool {
    $category = (string)($row['category'] ?? '');
    if (in_array($category, ['signets', 'gents_rings', 'corporate', 'emblematic', 'professional', 'idents', 'lockets'], true)) {
        return true;
    }

    if (($collection['slug'] ?? '') === 'mother-daughter') {
        return (($row['category'] ?? '') === 'family');
    }

    return (($row['category'] ?? '') === 'family')
        && (($row['subcategory'] ?? '') === 'mother_daughter')
        && (($collection['slug'] ?? '') === 'family');
};

$formatDisplayName = static function (?string $rawName, string $fallbackId): string {
    $name = trim((string)$rawName);
    if ($name !== '') {
        $parts = preg_split('/[-_]/', $name, 2);
        if (is_array($parts) && isset($parts[1])) {
            $right = trim((string)$parts[1]);
            if ($right !== '') {
                $name = $right;
            }
        }
    }
    return $name !== '' ? $name : $fallbackId;
};
$displayProductName = $isPidPrimaryCollection($product, $collection)
    ? (string)($product['product_id'] ?? '')
    : $formatDisplayName((string)($product['product_name'] ?? ''), (string)$product['product_id']);
if ($displayProductName === '') {
    $displayProductName = $formatDisplayName((string)($product['product_name'] ?? ''), (string)$product['product_id']);
}

$bc = (new Breadcrumbs())
    ->add('Home', '/')
    ->add($collection['_parent']['label'], $collection['_parent']['url'])
    ->add($collection['label'], $collection['url'])
    ->add($displayProductName);

$title = $displayProductName . ' | ' . $collection['label'] . ' | Cadman';
$desc  = trim($displayProductName . ' (' . $product['product_id'] . '). ' .
              ($product['pattern'] ? $product['pattern'] . ' pattern. ' : '') .
              ($product['width_mm'] ? $product['width_mm'] . 'mm width. ' : '') .
              'Available in 10K, 14K, 18K gold and sterling silver. Hand-crafted by Cadman Manufacturing.');

// JSON-LD Product
$canonicalAbs = catalog_canonical($canonicalUrl);
$jsonLd = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $displayProductName,
    'sku'         => $product['product_id'],
    'category'    => $collection['label'],
    'image'       => catalog_canonical($image),
    'description' => $desc,
    'brand'       => ['@type' => 'Brand', 'name' => 'Cadman Manufacturing'],
];
if ($price !== null) {
    $jsonLd['offers'] = [
        '@type'         => 'Offer',
        'price'         => number_format($price, 2, '.', ''),
        'priceCurrency' => 'CAD',
        'availability'  => 'https://schema.org/InStock',
        'url'           => $canonicalAbs,
    ];
}

// Open Graph + Twitter Card meta tags
$ogImageAbs   = catalog_canonical($image);
$ogTitle      = $displayProductName;
$ogDescShort  = mb_substr($desc, 0, 200);
$ogTags  = '<meta property="og:type" content="product">' . "\n";
$ogTags .= '<meta property="og:site_name" content="Cadman Manufacturing">' . "\n";
$ogTags .= '<meta property="og:title" content="' . htmlspecialchars($ogTitle, ENT_QUOTES) . '">' . "\n";
$ogTags .= '<meta property="og:description" content="' . htmlspecialchars($ogDescShort, ENT_QUOTES) . '">' . "\n";
$ogTags .= '<meta property="og:url" content="' . htmlspecialchars($canonicalAbs, ENT_QUOTES) . '">' . "\n";
$ogTags .= '<meta property="og:image" content="' . htmlspecialchars($ogImageAbs, ENT_QUOTES) . '">' . "\n";
if ($price !== null) {
    $ogTags .= '<meta property="product:price:amount" content="' . number_format($price, 2, '.', '') . '">' . "\n";
    $ogTags .= '<meta property="product:price:currency" content="CAD">' . "\n";
}
$ogTags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
$ogTags .= '<meta name="twitter:title" content="' . htmlspecialchars($ogTitle, ENT_QUOTES) . '">' . "\n";
$ogTags .= '<meta name="twitter:description" content="' . htmlspecialchars($ogDescShort, ENT_QUOTES) . '">' . "\n";
$ogTags .= '<meta name="twitter:image" content="' . htmlspecialchars($ogImageAbs, ENT_QUOTES) . '">' . "\n";

$extraHead = $ogTags
           . '<script type="application/ld+json">'
           . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
           . '</script>';

catalog_render_top([
    'title'       => $title,
    'description' => $desc,
    'canonical'   => $canonicalAbs,
    'extra_head'  => $extraHead,
], $bc);
?>

<article class="product-detail">
    <div class="gallery">
        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['product_name'] ?: $product['product_id']) ?>" loading="eager">
    </div>
    <div class="info">
        <h1><?= htmlspecialchars($displayProductName) ?></h1>
        <p class="pid">Product ID: <?= htmlspecialchars($product['product_id']) ?></p>

        <?php if ($price !== null): ?>
            <div class="price-block">
                <span class="price" id="catalog-detail-price">$<?= number_format($price, 2) ?></span>
                <span style="margin-left:.5rem;color:var(--cat-muted);font-size:.875rem;">CAD</span>
            </div>
        <?php elseif ($isQuoteOnly): ?>
            <div class="price-block" style="display:grid;gap:.65rem;align-items:start;">
                <div style="font-weight:600;color:#8B4513;">Pricing available by quote for this item.</div>
                <button id="catalog-quote-button" type="button" style="justify-self:start;background:#8B4513;color:#fff;border:0;border-radius:.45rem;padding:.65rem 1rem;cursor:pointer;">
                    Call for a Quote
                </button>
            </div>
        <?php else: ?>
            <div class="price-block price-locked">
                <a href="/admin/login.php">Log in</a> to see business pricing.
            </div>
        <?php endif; ?>

        <?php if (!$isPlainBands && !empty($basicMetalPriceList)): ?>
            <div class="metal-price-list" style="margin:.9rem 0 1.1rem;display:grid;gap:.35rem;max-width:22rem;">
                <div style="font-weight:600;color:#444;">Metal Pricing</div>
                <?php foreach ($basicMetalPriceList as $metalLabel => $metalPrice): ?>
                    <div style="display:flex;justify-content:space-between;gap:1rem;border:1px solid #e6e6e6;border-radius:.4rem;padding:.45rem .65rem;background:#fafafa;">
                        <span><?= htmlspecialchars($metalLabel) ?></span>
                        <strong>$<?= number_format((float)$metalPrice, 2) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($isPlainBands && $price !== null && !empty($plainConfiguratorOptions['karat_level']['options']) && is_array($plainConfiguratorOptions['karat_level']['options'])): ?>
            <div class="detail-configurator" style="margin:1rem 0;display:grid;gap:.5rem;max-width:22rem;">
                <label for="detail-karat-select" style="font-weight:600;">Metal</label>
                <select id="detail-karat-select" style="padding:.55rem .7rem;border:1px solid #d3d8de;border-radius:.4rem;background:#fff;">
                    <?php foreach ($plainConfiguratorOptions['karat_level']['options'] as $opt):
                        if (!is_array($opt) || empty($opt['id']) || empty($opt['name'])) continue;
                        $selected = ((string)$opt['id'] === $plainDefaultKarat) ? ' selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars((string)$opt['id']) ?>"<?= $selected ?>><?= htmlspecialchars((string)$opt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php
        // Generic metal selector for all non-plain-band products.
        // Only rendered when SHOW_PRICING is active and the DB has >1 metal variant.
        $genericMetalLabels = [
            'STER'  => '.950 Silver',
            '10K'   => '10K Gold',
            '14K'   => '14K Gold',
            '18K'   => '18K Gold',
            '10KTT' => '10K Two-Tone Gold',
            '14KTT' => '14K Two-Tone Gold',
            '18KTT' => '18K Two-Tone Gold',
        ];
        // Karat options = non-TT keys only (TT state comes from color selects).
        $genericKaratOptions = array_filter($genericPriceByMetal, fn($_, $k) => !str_ends_with($k, 'TT'), ARRAY_FILTER_USE_BOTH);
        $showGenericConfigurator = !$isPlainBands && $price !== null && count($genericPriceByMetal) > 1;
        $colorStyles = 'padding:.55rem .7rem;border:1px solid #d3d8de;border-radius:.4rem;background:#fff;width:100%;';
        ?>
        <?php if ($showGenericConfigurator): ?>
            <div class="detail-configurator" id="detail-generic-configurator" style="margin:1rem 0;display:grid;gap:.75rem;max-width:22rem;">

                <?php if (count($genericKaratOptions) > 1): ?>
                <div>
                    <label for="detail-karat-select" style="display:block;font-weight:600;margin-bottom:.35rem;">Metal / Purity</label>
                    <select id="detail-karat-select" style="<?= $colorStyles ?>">
                        <?php foreach (array_keys($genericKaratOptions) as $mk):
                            $lbl      = $genericMetalLabels[$mk] ?? $mk;
                            $selected = ($mk === $genericDefaultKarat) ? ' selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($mk) ?>"<?= $selected ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($genericHasTT): ?>
                <div id="detail-pattern-metal-group" style="display:none;">
                    <label for="detail-pattern-metal" style="display:block;font-weight:600;margin-bottom:.35rem;">Pattern Metal</label>
                    <p style="font-size:.85rem;color:#666;margin:0 0 .4rem;">Metal colour for the decorative pattern detail</p>
                    <select id="detail-pattern-metal" style="<?= $colorStyles ?>">
                        <option value="yellow">Yellow Gold</option>
                        <option value="white">White Gold (+$25)</option>
                        <option value="rose">Rose Gold (+$15)</option>
                    </select>
                </div>
                <div id="detail-band-metal-group" style="display:none;">
                    <label for="detail-band-metal" style="display:block;font-weight:600;margin-bottom:.35rem;">Band Metal</label>
                    <p style="font-size:.85rem;color:#666;margin:0 0 .4rem;">Metal colour for the band body &mdash; different from Pattern Metal adds a two-tone surcharge</p>
                    <select id="detail-band-metal" style="<?= $colorStyles ?>">
                        <option value="yellow">Yellow Gold</option>
                        <option value="white">White Gold</option>
                        <option value="rose">Rose Gold</option>
                    </select>
                </div>
                <?php elseif (!empty(array_filter(array_keys($genericKaratOptions), fn($k) => $k !== 'STER'))): ?>
                <?php /* Single-metal gold products: one colour picker */ ?>
                <div id="detail-metal-color-group" style="display:none;">
                    <label for="detail-metal-color" style="display:block;font-weight:600;margin-bottom:.35rem;">Metal Color</label>
                    <select id="detail-metal-color" style="<?= $colorStyles ?>">
                        <option value="yellow">Yellow Gold</option>
                        <option value="white">White Gold (+$25)</option>
                        <option value="rose">Rose Gold (+$15)</option>
                    </select>
                </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <dl class="specs">
            <?php foreach ([
                'pattern'        => 'Pattern',
                'style'          => 'Style',
                'series'         => 'Series',
                'width_mm'       => 'Width (mm)',
                'thickness_mm'   => 'Thickness (mm)',
                'gender_variant' => 'Gender',
                'page_reference' => 'Catalog page',
                'subcategory'    => 'Category',
            ] as $k => $label):
                if (!empty($product[$k])):
                    $val = $product[$k];
                    if ($k === 'subcategory') $val = $LABELS[$val] ?? ucwords(str_replace('_', ' ', $val));
            ?>
                    <dt><?= $label ?></dt>
                    <dd><?= htmlspecialchars((string)$val) ?></dd>
            <?php   endif;
            endforeach; ?>
        </dl>

        <?php if (!empty($product['special_notes'])): ?>
            <p><?= htmlspecialchars($product['special_notes']) ?></p>
        <?php endif; ?>
    </div>
</article>

<?php if ($relatedItems): ?>
<section class="related-products">
    <h2>Related Designs</h2>
    <div class="catalog-grid">
    <?php foreach ($relatedItems as $r):
        $rImg = $catalog->getProductImage($r);
        $rUrl = $catalog->productUrl($r, $collection);
        $rPdfUrl   = catalog_pdf_url($r);
        $rDisplayName = $isPidPrimaryCollection($r, $collection)
            ? (string)($r['product_id'] ?? '')
            : $formatDisplayName((string)($r['product_name'] ?? ''), (string)$r['product_id']);
        if ($rDisplayName === '') {
            $rDisplayName = $formatDisplayName((string)($r['product_name'] ?? ''), (string)$r['product_id']);
        }
        // If thumbnail is PDF placeholder, keep main click on item page
        // and expose a dedicated corner PDF action.
        $rPdfMode  = $rPdfUrl && str_ends_with($rImg, '/pdf_available.svg');
    ?>
        <article class="catalog-card">
            <a href="<?= htmlspecialchars($rUrl) ?>" class="related-card-main-link">
                <div class="thumb" style="background-image:url('<?= htmlspecialchars($rImg) ?>');" role="img" aria-label="<?= htmlspecialchars($r['product_name'] ?? $r['product_id']) ?>"></div>
                <div class="info">
                    <p class="name"><?= htmlspecialchars($rDisplayName) ?></p>
                    <p class="meta"><?= htmlspecialchars($r['product_id']) ?><?= $r['width_mm'] ? ' · ' . htmlspecialchars($r['width_mm']) . 'mm' : '' ?></p>
                </div>
            </a>
            <?php if ($rPdfMode): ?>
                <a class="related-pdf-corner" href="<?= htmlspecialchars($rPdfUrl) ?>" target="_blank" rel="noopener" aria-label="Download catalog PDF for <?= htmlspecialchars($r['product_id']) ?>">📄 PDF</a>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($isQuoteOnly): ?>
<script>
(function() {
    var button = document.getElementById('catalog-quote-button');
    if (!button) return;
    var productName = <?= json_encode($displayProductName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    button.addEventListener('click', function() {
        if (typeof openContactModalWithTracking === 'function') {
            openContactModalWithTracking('Catalog Product Detail', 'Quote Request - ' + productName, '');
        }
    });
})();
</script>
<?php endif; ?>

<?php if ($isPlainBands && $price !== null && !empty($plainPriceByMetal)): ?>
<script>
(function() {
    var map = <?= json_encode($plainPriceByMetal, JSON_UNESCAPED_SLASHES) ?>;
    var select = document.getElementById('detail-karat-select');
    var priceEl = document.getElementById('catalog-detail-price');
    if (!select || !priceEl || !map || typeof map !== 'object') return;

    function toMetalType(karatId) {
        if (!karatId) return null;
        var raw = String(karatId);
        var id = raw.trim().toLowerCase();
        if (id === '950_silver' || id === 'ster' || id === 'sterling') return 'STER';
        if (id === '10k' || id === '10') return '10K';
        if (id === '14k' || id === '14') return '14K';
        if (id === '18k' || id === '18') return '18K';

        // Tolerate values like "14K Gold" or "18k_yellow".
        if (id.indexOf('10k') !== -1) return '10K';
        if (id.indexOf('14k') !== -1) return '14K';
        if (id.indexOf('18k') !== -1) return '18K';
        return null;
    }

    function updatePrice() {
        var metalType = toMetalType(select.value);
        if (!metalType || typeof map[metalType] === 'undefined') return;
        var value = Number(map[metalType]);
        if (!Number.isFinite(value)) return;
        priceEl.textContent = '$' + value.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    select.addEventListener('change', updatePrice);
    updatePrice();
})();
</script>
<?php endif; ?>

<?php if ($showGenericConfigurator): ?>
<script>
(function() {
    var map     = <?= json_encode($genericPriceByMetal, JSON_UNESCAPED_SLASHES) ?>;
    var hasTT   = <?= $genericHasTT ? 'true' : 'false' ?>;
    var priceEl = document.getElementById('catalog-detail-price');
    if (!priceEl || !map || typeof map !== 'object') return;

    var karatSel       = document.getElementById('detail-karat-select');
    var patternMetSel  = document.getElementById('detail-pattern-metal');
    var bandMetSel     = document.getElementById('detail-band-metal');
    var metalColorSel  = document.getElementById('detail-metal-color');

    // Groups to show/hide based on karat selection
    var patternGroup   = document.getElementById('detail-pattern-metal-group');
    var bandGroup      = document.getElementById('detail-band-metal-group');
    var colorGroup     = document.getElementById('detail-metal-color-group');

    // Convert karat select value (e.g. 'STER', '10K') to canonical metal key,
    // factoring in two-tone state.
    function resolveMetalKey(karatKey, isTwoTone) {
        if (!karatKey) return null;
        var k = String(karatKey).toUpperCase();
        if (k === 'STER') return 'STER';
        if (k === '10K')  return isTwoTone ? '10KTT' : '10K';
        if (k === '14K')  return isTwoTone ? '14KTT' : '14K';
        if (k === '18K')  return isTwoTone ? '18KTT' : '18K';
        return k;
    }

    function applyVisibility() {
        var isGold = karatSel && karatSel.value !== 'STER';
        if (patternGroup) patternGroup.style.display = isGold ? '' : 'none';
        if (bandGroup)    bandGroup.style.display    = isGold ? '' : 'none';
        if (colorGroup)   colorGroup.style.display   = isGold ? '' : 'none';
    }

    function updatePrice() {
        applyVisibility();
        var karatKey   = karatSel ? karatSel.value : null;
        var isTwoTone  = hasTT && patternMetSel && bandMetSel
                         && patternMetSel.value !== bandMetSel.value;
        var metalKey   = resolveMetalKey(karatKey, isTwoTone);
        if (!metalKey) return;

        var basePrice = typeof map[metalKey] !== 'undefined'
            ? Number(map[metalKey])
            : (isTwoTone && typeof map[metalKey.replace('TT', '')] !== 'undefined'
                ? Number(map[metalKey.replace('TT', '')]) + 150  // TT surcharge when variant absent
                : null);
        if (basePrice === null || !Number.isFinite(basePrice)) return;

        // Add colour surcharges (white gold +$25, rose gold +$15) from whichever
        // colour selects are visible.
        var surcharge = 0;
        var colorSurcharges = { 'white': 25, 'rose': 15 };
        [patternMetSel, metalColorSel].forEach(function(sel) {
            if (sel && sel.offsetParent !== null) {   // only if visible
                surcharge += colorSurcharges[sel.value] || 0;
            }
        });

        var total = basePrice + surcharge;
        if (total < 0) total = 0;
        priceEl.textContent = '$' + total.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    [karatSel, patternMetSel, bandMetSel, metalColorSel].forEach(function(sel) {
        if (sel) sel.addEventListener('change', updatePrice);
    });
    updatePrice(); // set initial state
})();
</script>
<?php endif; ?>

<?php catalog_render_bottom(); ?>
