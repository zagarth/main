<?php
/**
 * CatalogQuery — data layer for SEO catalog pages.
 *
 * Wraps catalog_products + the existing PricingCalculator. Centralises:
 *   - collection → DB category mapping
 *   - product fetch with slug-aware canonical URLs
 *   - listing queries with subcategory / series facets
 *   - 3-tier image fallback (image_files → page PDF thumb → category SVG placeholder)
 *   - server-side price calculation (reuses the FIELD-ordered metal_type query
 *     from get_product_modal_data.php)
 */

require_once __DIR__ . '/../db_config.php';

class CatalogQuery
{
    private PDO $pdo;
    /** @var array Cached nav_config */
    private array $navConfig;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getDBConnection();
        $this->navConfig = require __DIR__ . '/nav_config.php';
    }

    // ---------- Slug helpers ----------

    public static function slugify(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return substr(trim($s, '-'), 0, 100);
    }

    /** Resolve a /{parent}/{collection}/ pair to its child config row, or null. */
    public function findCollection(string $parentSlug, string $collectionSlug): ?array
    {
        foreach ($this->navConfig['parents'] as $p) {
            if ($p['slug'] !== $parentSlug) continue;
            foreach ($p['children'] as $c) {
                if ($c['slug'] === $collectionSlug) {
                    $c['_parent'] = ['slug' => $p['slug'], 'label' => $p['label'], 'url' => $p['url']];
                    return $c;
                }
            }
        }
        return null;
    }

    public function findParent(string $parentSlug): ?array
    {
        foreach ($this->navConfig['parents'] as $p) {
            if ($p['slug'] === $parentSlug) return $p;
        }
        return null;
    }

    public function navConfig(): array { return $this->navConfig; }

    // ---------- Listing queries ----------

    /**
     * Get products in a collection.
     * @param array $collection  row from nav_config['parents'][]['children'][]
     * @param array $filters     ['subcategory' => string|null, 'series' => string|null,
     *                            'limit' => int, 'offset' => int, 'order' => 'name'|'page']
     */
    public function getProducts(array $collection, array $filters = []): array
    {
        $cats = $collection['db'] ?? [];
        if (!$cats) return [];

        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $where = ["category IN ($placeholders)"];
        $params = array_values($cats);

        $subFilter = $filters['subcategory'] ?? ($collection['subcategory_filter'] ?? null);
        if ($subFilter !== null && $subFilter !== '') {
            $where[] = 'subcategory = ?';
            $params[] = $subFilter;
        }
        if (!empty($filters['series'])) {
            $where[] = 'series = ?';
            $params[] = $filters['series'];
        }

        $order = match ($filters['order'] ?? 'name') {
            'page' => 'page_reference ASC, product_id ASC',
            'images_first' => "CASE
                WHEN image_files IS NOT NULL AND image_files != '' AND image_files != 'no images found' THEN 0
                ELSE 1
            END ASC, product_name ASC, product_id ASC",
            default => 'product_name ASC, product_id ASC',
        };

        $limit  = max(1, min(200, (int)($filters['limit'] ?? 60)));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT product_id, product_name, slug, category, subcategory, series,
                       pattern, style, width_mm, gender_variant,
                       has_images, image_files, pdf_file, page_reference, base_price
                FROM catalog_products
                WHERE " . implode(' AND ', $where) . "
                ORDER BY $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countProducts(array $collection, array $filters = []): int
    {
        $cats = $collection['db'] ?? [];
        if (!$cats) return 0;
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $where = ["category IN ($placeholders)"];
        $params = array_values($cats);

        $subFilter = $filters['subcategory'] ?? ($collection['subcategory_filter'] ?? null);
        if ($subFilter !== null && $subFilter !== '') {
            $where[] = 'subcategory = ?';
            $params[] = $subFilter;
        }
        if (!empty($filters['series'])) {
            $where[] = 'series = ?';
            $params[] = $filters['series'];
        }

        $sql = "SELECT COUNT(*) FROM catalog_products WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Distinct subcategory facets present in a collection. */
    public function getSubcategories(array $collection): array
    {
        $cats = $collection['db'] ?? [];
        if (!$cats) return [];
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $sql = "SELECT subcategory, COUNT(*) AS n
                FROM catalog_products
                WHERE category IN ($placeholders)
                  AND subcategory IS NOT NULL AND subcategory != ''
                GROUP BY subcategory
                ORDER BY n DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($cats));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Distinct series facets present in a collection. */
    public function getSeries(array $collection): array
    {
        $cats = $collection['db'] ?? [];
        if (!$cats) return [];
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $sql = "SELECT series, COUNT(*) AS n
                FROM catalog_products
                WHERE category IN ($placeholders)
                  AND series IS NOT NULL AND series != ''
                GROUP BY series
                ORDER BY n DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($cats));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------- Single product ----------

    public function getProduct(string $productId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM catalog_products WHERE product_id = ? LIMIT 1"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Series siblings (other widths/variants). */
    public function getRelatedProducts(array $product, int $limit = 12): array
    {
        if (empty($product['series'])) return [];
        $stmt = $this->pdo->prepare(
            "SELECT product_id, product_name, slug, width_mm, has_images, image_files, page_reference, pdf_file, category, subcategory
             FROM catalog_products
             WHERE series = ? AND category = ? AND product_id != ?
             ORDER BY CAST(width_mm AS DECIMAL(6,2)), product_id
             LIMIT $limit"
        );
        $stmt->execute([$product['series'], $product['category'], $product['product_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------- Image resolution ----------

    /**
     * 3-tier fallback: image_files → PDF page thumb (if cached) → category SVG placeholder.
     * Returns an absolute web path that is safe to drop into <img src="">.
     */
    public function getProductImage(array $product): string
    {
        // Tier 1: image_files (may be comma list, may be relative to docroot)
        if (!empty($product['image_files']) && $product['image_files'] !== 'no images found') {
            $first = trim(explode(',', $product['image_files'])[0]);
            $first = ltrim($first, '/');
            if ($first && is_file(__DIR__ . '/../../' . $first)) {
                $ext = strtolower((string)pathinfo($first, PATHINFO_EXTENSION));
                if (!in_array($ext, ['tif', 'tiff'], true)) {
                    return '/' . $first;
                }

                // TIFF sources are not consistently browser-renderable.
                // Try same-stem web-safe siblings before falling back.
                $dir = dirname($first);
                $stem = (string)pathinfo($first, PATHINFO_FILENAME);
                foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $safeExt) {
                    $candidate = ($dir === '.' ? '' : ($dir . '/')) . $stem . '.' . $safeExt;
                    if (is_file(__DIR__ . '/../../' . $candidate)) {
                        return '/' . $candidate;
                    }
                    $candidateUpper = ($dir === '.' ? '' : ($dir . '/')) . $stem . '.' . strtoupper($safeExt);
                    if (is_file(__DIR__ . '/../../' . $candidateUpper)) {
                        return '/' . $candidateUpper;
                    }
                }
            }
        }

        // Family fallback: many base rows have no image_files but matching files
        // exist under family_php/images/Mother with the same product ID stem.
        if (($product['category'] ?? '') === 'family') {
            $pid = trim((string)($product['product_id'] ?? ''));
            if ($pid !== '') {
                $motherDirAbs = __DIR__ . '/../../family_php/images/Mother';
                $motherDirWeb = '/family_php/images/Mother';

                if (is_dir($motherDirAbs)) {
                    $extensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
                    $pidEscaped = preg_quote($pid, '/');

                    // Exact basename first (case-insensitive extension).
                    foreach ($extensions as $ext) {
                        $exact = $motherDirAbs . '/' . $pid . '.' . $ext;
                        if (is_file($exact)) {
                            return $motherDirWeb . '/' . $pid . '.' . $ext;
                        }
                        $exactUpper = $motherDirAbs . '/' . $pid . '.' . strtoupper($ext);
                        if (is_file($exactUpper)) {
                            return $motherDirWeb . '/' . $pid . '.' . strtoupper($ext);
                        }
                    }

                    // Then deterministic variant match: PID_*, PID-*, PID alt variants.
                    static $motherDirListing = null;
                    if ($motherDirListing === null) {
                        $scan = scandir($motherDirAbs);
                        $motherDirListing = is_array($scan) ? $scan : [];
                    }

                    $matches = [];
                    foreach ($motherDirListing as $name) {
                        if ($name === '.' || $name === '..') continue;
                        if (!preg_match('/\\.(png|jpe?g|webp|gif)$/i', $name)) continue;
                        if (!preg_match('/^' . $pidEscaped . '([_-].+)?\\.(png|jpe?g|webp|gif)$/i', $name)) continue;
                        $matches[] = $name;
                    }

                    if ($matches) {
                        natsort($matches);
                        $firstMatch = (string)array_values($matches)[0];
                        return $motherDirWeb . '/' . $firstMatch;
                    }
                }
            }
        }

        // Tier 2: cached PDF page thumbnail
        if (!empty($product['page_reference'])) {
            $page = (int)$product['page_reference'];
            if ($page > 0) {
                $thumb = "/assets/page_thumbs/page-$page.jpg";
                if (is_file(__DIR__ . '/../..' . $thumb)) return $thumb;
            }
        }

        // Tier 3: PDF page exists in catalog — show "View in catalog" icon
        if (!empty($product['pdf_file'])) {
            // Fancy bands haven't been mapped to real catalog pages yet
            // (pdf_file == 'fancy.pdf' is a placeholder; no such file exists).
            // Fall through to Tier 4 so the category "image coming soon"
            // placeholder is used instead of the PDF icon.
            $pdfName = strtolower(trim((string)$product['pdf_file']));
            $pageRef = strtolower(trim((string)($product['page_reference'] ?? '')));
            if ($pdfName !== 'fancy.pdf' && $pageRef !== 'fancy') {
                return '/assets/placeholders/pdf_available.svg';
            }
        }

        // Tier 4: per-category SVG placeholder ("Image coming soon")
        $cat = $product['category'] ?? 'other';
        $svg = "/assets/placeholders/{$cat}.svg";
        if (is_file(__DIR__ . '/../..' . $svg)) return $svg;
        return '/assets/placeholders/default.svg';
    }

    // ---------- Pricing ----------

    /**
     * Server-side price calculation — must mirror get_product_modal_data.php exactly.
     * Returns null on miss / when SHOW_PRICING is false.
     */
    public function calculatePrice(string $productId): ?float
    {
        if (!defined('SHOW_PRICING') || !SHOW_PRICING) return null;

        $calcPath = __DIR__ . '/../../cadman-database/php/PricingCalculator.php';
        if (!is_file($calcPath)) return null;
        require_once $calcPath;

        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.labor_hours, p.stone_cost, p.star_cost, p.stone_setting_cost,
                        p.markup_percent, pv.gold_grams, pv.sterling_grams,
                        pv.material_cost, pv.metal_type
                 FROM products p
                 LEFT JOIN product_variants pv ON pv.product_id = p.product_id
                 WHERE p.base_code = ?
                 ORDER BY FIELD(pv.metal_type, 'STER','GF','10K','10KTT','14K','18K') ASC,
                          pv.variant_id ASC
                 LIMIT 1"
            );
            $stmt->execute([$productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;

            $metalType = $row['metal_type'] ?? 'STER';
            $karat = preg_replace('/TT$/', '', $metalType);
            if (!in_array($karat, ['10K', '14K', '18K'], true)) $karat = '10K';

            $calc = new PricingCalculator($this->pdo);
            $result = $calc->calculatePrice([
                'goldGrams'        => (float)($row['gold_grams'] ?? 0),
                'karat'            => $karat,
                'sterlingGrams'    => (float)($row['sterling_grams'] ?? 0),
                'laborHours'       => (float)($row['labor_hours'] ?? 0),
                'materialCost'     => (float)($row['material_cost'] ?? 0),
                'stoneCost'        => (float)($row['stone_cost'] ?? 0),
                'starCost'         => (float)($row['star_cost'] ?? 0),
                'stoneSettingCost' => (float)($row['stone_setting_cost'] ?? 0),
            ], (float)($row['markup_percent'] ?? 50));

            return ($result['roundedPrice'] ?? 0) > 0 ? (float)$result['roundedPrice'] : null;
        } catch (Throwable $e) {
            error_log('CatalogQuery::calculatePrice error for ' . $productId . ': ' . $e->getMessage());
            return null;
        }
    }

    // ---------- URL canonicalization ----------

    /**
     * Build canonical product URL: /{parent}/{collection}/{id}-{slug}
     * If $product is in a series collection layout, caller can override.
     */
    public function productUrl(array $product, array $collection): string
    {
        $parent = $collection['_parent']['slug'] ?? '';
        $coll   = $collection['slug'] ?? '';
        $slug   = $product['slug'] ?: self::slugify($product['product_name'] ?? $product['product_id']);
        return "/{$parent}/{$coll}/" . urlencode($product['product_id']) . "-" . $slug;
    }
}
