<?php
/**
 * Dynamic sitemap.xml — emits every SEO-friendly catalog URL plus
 * static content pages. Routed from /sitemap.xml via .htaccess.
 *
 * URL tiers:
 *   - homepage + static pages (about, contact, faq, catalog index)
 *   - parent landings   (/wedding/, /school/, …)
 *   - collection pages  (/wedding/bands/, /school/bands/, …)
 *   - product detail    (/wedding/bands/{id}-{slug})
 *
 * Cached in memory only — Apache serves directly each request.
 * Output is small enough (~2k URLs) to stay well under the 50k/50MB limit.
 */

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/catalog/CatalogQuery.php';
require_once __DIR__ . '/includes/catalog/Breadcrumbs.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // the sitemap itself shouldn't be indexed

$BASE = 'https://www.cadmanmfg.com';
$today = date('Y-m-d');

$urls = [];

// ---------- Static pages ----------
$static = [
    ['/',                  '1.0', 'weekly'],
    ['/about/',            '0.7', 'monthly'],
    ['/contact_form.php',  '0.6', 'monthly'],
    ['/FAQ.php',           '0.5', 'monthly'],
    ['/catalog_direct.php','0.8', 'weekly'],
];
foreach ($static as [$path, $pri, $freq]) {
    $urls[] = ['loc' => $BASE . $path, 'priority' => $pri, 'changefreq' => $freq, 'lastmod' => $today];
}

// ---------- Catalog ----------
try {
    $catalog = new CatalogQuery();
    $nav = $catalog->navConfig();

    foreach ($nav['parents'] as $parent) {
        // Parent landing
        $urls[] = [
            'loc'        => $BASE . $parent['url'],
            'priority'   => '0.9',
            'changefreq' => 'weekly',
            'lastmod'    => $today,
        ];

        foreach ($parent['children'] as $child) {
            $child['_parent'] = ['slug' => $parent['slug'], 'label' => $parent['label'], 'url' => $parent['url']];

            // Collection listing
            $urls[] = [
                'loc'        => $BASE . $child['url'],
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => $today,
            ];

            // Individual products
            $offset = 0;
            $page = 500;
            while (true) {
                $rows = $catalog->getProducts($child, ['limit' => $page, 'offset' => $offset]);
                if (!$rows) break;
                foreach ($rows as $p) {
                    $urls[] = [
                        'loc'        => $BASE . $catalog->productUrl($p, $child),
                        'priority'   => '0.6',
                        'changefreq' => 'monthly',
                        'lastmod'    => $today,
                    ];
                }
                if (count($rows) < $page) break;
                $offset += $page;
            }
        }
    }
} catch (Throwable $e) {
    // Fail soft — still emit the static portion so the file is valid XML.
    error_log('sitemap.php error: ' . $e->getMessage());
}

// ---------- Emit ----------
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
