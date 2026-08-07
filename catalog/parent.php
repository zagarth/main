<?php
/**
 * Parent landing page — /{parent}/
 * Routed from .htaccess: ^(wedding|celtic|personal-family|corporate-service|accessories)/?$
 * Uses the same .gallery-grid / .jewelry-item chrome as collection pages
 * so the visual treatment is consistent across the catalog.
 */

require __DIR__ . '/_bootstrap.php';

$parentSlug = sanitize_seg($_GET['parent'] ?? '');
$catalog = new CatalogQuery();
$parent = $catalog->findParent($parentSlug);

if (!$parent) {
    http_response_code(404);
    catalog_render_top(['title' => 'Not Found | Cadman']);
    echo '<div class="catalog-empty"><h1>Page not found</h1><p>The collection you requested does not exist.</p></div>';
    catalog_render_bottom();
    exit;
}

$bc = (new Breadcrumbs())
    ->add('Home', '/')
    ->add($parent['label']);

$title = $parent['label'] . ' Collection | Cadman Manufacturing';
$desc  = "Browse Cadman's " . strtolower($parent['label']) . " collection: " .
         implode(', ', array_column($parent['children'], 'label')) . '.';

/**
 * Pick a representative image for a child collection.
 * Prefers BandsGrouper output (XML-driven, real bands_php image),
 * then first product with image_files, then category placeholder.
 */
function parent_tile_image(CatalogQuery $catalog, array $child): string {
    $dbCats = $child['db'] ?? [];
    foreach ($dbCats as $dbCat) {
        if (BandsGrouper::isBandCategory($dbCat)) {
            foreach (BandsGrouper::getGroups($dbCat) as $g) {
                if (!empty($g['images'])) {
                    return BandsGrouper::thumbUrl($g['category'], $g['images'][0]);
                }
            }
        }
    }
    $products = $catalog->getProducts($child, [], 30);
    foreach ($products as $p) {
        $imgFile = $p['image_files'] ?? '';
        if ($imgFile && $imgFile !== 'no images found' && strpos($imgFile, '/') !== false) {
            return '/' . ltrim($imgFile, '/');
        }
    }
    $cat = $dbCats[0] ?? 'other';
    return '/assets/placeholders/' . $cat . '.svg';
}

catalog_render_top([
    'title'       => $title,
    'description' => $desc,
    'canonical'   => catalog_canonical($parent['url']),
], $bc);
?>

<header class="bands-header">
    <div class="collection-header">
        <h1><?= htmlspecialchars($parent['label']) ?> Collection</h1>
        <p><?= htmlspecialchars($desc) ?></p>
    </div>
</header>

<div class="gallery-container">
    <div class="gallery-grid gallery-grid--parent">
    <?php foreach ($parent['children'] as $c):
        $count = $catalog->countProducts($c);
        $dbCats = $c['db'] ?? [];
        foreach ($dbCats as $dbCat) {
            if (BandsGrouper::isBandCategory($dbCat)) {
                $count = count(BandsGrouper::getGroups($dbCat));
                break;
            }
        }
        $img   = parent_tile_image($catalog, $c);
    ?>
        <div class="item jewelry-item">
            <a href="<?= htmlspecialchars($c['url']) ?>" class="rotating-image-container"
               aria-label="<?= htmlspecialchars($c['label']) ?>">
                <img src="<?= htmlspecialchars($img) ?>"
                     alt="<?= htmlspecialchars($c['label']) ?>"
                     class="rotating-image" loading="lazy">
            </a>
            <div class="item-info">
                <h3 class="item-title">
                    <a href="<?= htmlspecialchars($c['url']) ?>" style="color:inherit;text-decoration:none;">
                        <?= htmlspecialchars($c['label']) ?>
                    </a>
                </h3>
                <p class="item-description"><?= (int)$count ?> design<?= $count === 1 ? '' : 's' ?> available</p>
                <div class="item-actions">
                    <a href="<?= htmlspecialchars($c['url']) ?>" class="btn btn-primary">Browse Collection</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php catalog_render_bottom(); ?>
