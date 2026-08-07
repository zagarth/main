<?php
/**
 * BandsGrouper — collapses individual catalog_products band rows into
 * pattern/series/size groups using the authoritative XML chart files.
 *
 *  - celtic / cultural : grouped by <pattern> → multiple widths → M/L
 *  - plain             : grouped by <series>  → multiple widths → M/L
 *  - fancy             : flat <band> rows     → one card per base_id, M/L
 *
 * Each returned group has:
 *   group_key       (string)   stable identifier (slug)
 *   display_name    (string)   pattern/series/product name
 *   description     (string)   marketing line
 *   variants        (array)    [{ width, base_id, m_id, l_id }, …]
 *   canonical_pid   (string)   product_id to link to (first M or first base)
 *   images          (array)    discovered image filenames (filesystem)
 *   image_dir       (string)   absolute /web path to image dir for src building
 *   category        (string)   db category enum
 */
final class BandsGrouper
{
    /** Sub-category (filesystem dir) for each DB category enum. */
    private const DIR_MAP = [
        'celtic_bands' => 'celtic',
        'fancy_bands'  => 'fancy',
        'plain_bands'  => 'plain',
        'claddagh_bands' => 'claddagh',
        // cultural lives only on disk; not in main 5 parents
    ];

    private const XML_MAP = [
        'celtic_bands' => 'celtic_bands_mapping.xml',
        'fancy_bands'  => 'fancy_bands_mapping.xml',
        'plain_bands'  => 'plain_bands_mapping.xml',
    ];

    private static array $cache = [];

    /** Is this DB category one of the band collections? */
    public static function isBandCategory(string $category): bool
    {
        return isset(self::DIR_MAP[$category]);
    }

    /** Return all groups for a band category, in display order. */
    public static function getGroups(string $category): array
    {
        if (!self::isBandCategory($category)) return [];
        if (isset(self::$cache[$category])) return self::$cache[$category];

        $root = dirname(__DIR__, 2);
        $xml = null;
        if (isset(self::XML_MAP[$category])) {
            $xmlPath = $root . '/bands_php/' . self::XML_MAP[$category];
            if (is_file($xmlPath)) {
                $xml = @simplexml_load_file($xmlPath) ?: null;
            }
        }

        switch ($category) {
            case 'celtic_bands':
                if (!$xml) return self::$cache[$category] = [];
                $groups = self::parsePatternXml($xml, $category, 'Celtic Band');
                break;
            case 'plain_bands':
                if (!$xml) return self::$cache[$category] = [];
                $groups = self::parseSeriesXml($xml, $category);
                break;
            case 'fancy_bands':
                if (!$xml) return self::$cache[$category] = [];
                $groups = self::parseFlatXml($xml, $category);
                break;
            case 'claddagh_bands':
                $groups = self::parseDirectoryBands($category);
                break;
            default:
                $groups = [];
        }

        // attach images
        foreach ($groups as &$g) {
            $g['images']    = self::scanImagesForGroup($g);
            $g['image_dir'] = '/bands_php/images/' . self::DIR_MAP[$category];
        }
        unset($g);

        return self::$cache[$category] = $groups;
    }

    /** Build one-card-per-base groups directly from filenames in a category dir. */
    private static function parseDirectoryBands(string $category): array
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/bands_php/images/' . self::DIR_MAP[$category];
        if (!is_dir($dir)) {
            return [];
        }

        $groups = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'tif', 'tiff'], true)) {
                continue;
            }
            $stem = strtoupper((string)pathinfo($f, PATHINFO_FILENAME));
            if ($stem === '') continue;

            $stem = preg_replace('/(?:[_-](?:ALT|VIEW|ART)\d*|[._-]\d+)$/i', '', $stem) ?? $stem;
            $base = preg_replace('/[ML]$/', '', $stem) ?? $stem;
            $base = trim((string)$base);
            if ($base === '') continue;

            if (!isset($groups[$base])) {
                $groups[$base] = [
                    'group_key'     => self::slug($base),
                    'display_name'  => $base,
                    'description'   => 'Claddagh band',
                    'variants'      => [[
                        'width'   => '',
                        'base_id' => $base,
                        'm_id'    => null,
                        'l_id'    => null,
                    ]],
                    'canonical_pid' => $stem,
                    'category'      => $category,
                ];
            }

            if (preg_match('/M$/', $stem)) {
                $groups[$base]['variants'][0]['m_id'] = $stem;
            }
            if (preg_match('/L$/', $stem)) {
                $groups[$base]['variants'][0]['l_id'] = $stem;
            }

            $canonical = $groups[$base]['variants'][0]['m_id']
                ?: $groups[$base]['variants'][0]['l_id']
                ?: $base;
            $groups[$base]['canonical_pid'] = $canonical;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($groups);
    }

    /** celtic_bands_mapping.xml + cultural_bands_mapping.xml structure. */
    private static function parsePatternXml(SimpleXMLElement $xml, string $cat, string $defaultDesc): array
    {
        $out = [];
        foreach ($xml->pattern as $p) {
            $name = (string)$p['name'];
            if ($name === '') continue;
            $variants = [];
            foreach ($p->band as $b) {
                $width = (string)$b['width'];
                $pid   = trim((string)$b->product_id);
                if ($pid === '') continue;
                $genders = [];
                foreach ($b->available_genders->gender ?? [] as $g) {
                    $genders[] = (string)$g;
                }
                if (empty($genders)) $genders = ['M', 'L'];
                $variants[] = [
                    'width'   => $width,
                    'base_id' => $pid,
                    'm_id'    => in_array('M', $genders, true) ? $pid . 'M' : null,
                    'l_id'    => in_array('L', $genders, true) ? $pid . 'L' : null,
                ];
            }
            if (!$variants) continue;
            $first = $variants[0];
            $canonical = $first['m_id'] ?: $first['l_id'] ?: $first['base_id'];
            $out[] = [
                'group_key'     => self::slug($name),
                'display_name'  => $name,
                'description'   => trim((string)$p->description) ?: $defaultDesc,
                'variants'      => $variants,
                'canonical_pid' => $canonical,
                'category'      => $cat,
            ];
        }
        return $out;
    }

    /** plain_bands_mapping.xml — series → bands. */
    private static function parseSeriesXml(SimpleXMLElement $xml, string $cat): array
    {
        $out = [];
        foreach ($xml->series as $s) {
            $name = (string)$s['name'];
            $thickness = (string)$s['thickness'];
            $variants = [];
            foreach ($s->band as $b) {
                $width  = (string)$b['width'];
                $base   = trim((string)$b->base_id);
                $m      = trim((string)$b->product_id_m);
                $l      = trim((string)$b->product_id_l);
                if ($base === '' && $m === '' && $l === '') continue;
                $variants[] = [
                    'width'   => $width,
                    'base_id' => $base ?: preg_replace('/[ML]$/', '', $m ?: $l),
                    'm_id'    => $m ?: null,
                    'l_id'    => $l ?: null,
                ];
            }
            if (!$variants) continue;
            $first = $variants[0];
            $canonical = $first['m_id'] ?: $first['l_id'] ?: $first['base_id'];
            $desc = trim((string)$s->description);
            if ($desc === '' && $thickness !== '') {
                $desc = "{$thickness} profile, multiple widths available";
            }
            $out[] = [
                'group_key'     => self::slug($name),
                'display_name'  => $name,
                'description'   => $desc ?: 'Classic plain band series',
                'variants'      => $variants,
                'canonical_pid' => $canonical,
                'category'      => $cat,
            ];
        }
        return $out;
    }

    /** fancy_bands_mapping.xml — flat list of <band>. One card per band. */
    private static function parseFlatXml(SimpleXMLElement $xml, string $cat): array
    {
        $out = [];
        foreach ($xml->band as $b) {
            $width = (string)$b['width'];
            $diamonds = (string)$b['diamonds'];
            $base = trim((string)$b->base_id);
            $m    = trim((string)$b->product_id_m);
            $l    = trim((string)$b->product_id_l);
            if ($base === '' && $m === '' && $l === '') continue;
            $base = $base ?: preg_replace('/[ML]$/', '', $m ?: $l);
            $canonical = $m ?: $l ?: $base;
            $desc = $width;
            if ($diamonds && $diamonds !== '0') $desc .= " · {$diamonds} diamonds";
            $out[] = [
                'group_key'     => self::slug($base),
                'display_name'  => $base,
                'description'   => $desc ?: 'Fancy band',
                'variants'      => [[
                    'width'   => $width,
                    'base_id' => $base,
                    'm_id'    => $m ?: null,
                    'l_id'    => $l ?: null,
                ]],
                'canonical_pid' => $canonical,
                'category'      => $cat,
            ];
        }
        return $out;
    }

    /** Scan filesystem for image files matching this group's variants. */
    private static function scanImagesForGroup(array $group): array
    {
        $root  = dirname(__DIR__, 2);
        $dir   = $root . '/bands_php/images/' . self::DIR_MAP[$group['category']];
        $thumb = $root . '/bands_php/thumbs/images/' . self::DIR_MAP[$group['category']];
        if (!is_dir($dir)) return [];

        // Build base-id set from variants (case-insensitive)
        $bases = [];
        foreach ($group['variants'] as $v) {
            if (!empty($v['base_id'])) $bases[strtoupper($v['base_id'])] = true;
        }
        if (!$bases) return [];

        $matched = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) continue;
            $stem = strtoupper(pathinfo($f, PATHINFO_FILENAME));
            // Strip alt/view/art suffixes for base comparison
            $stripped = preg_replace('/(_ALT\d*|-ALT\d*|_VIEW\d*|-VIEW\d*|_ART\d*|-ART\d*)$/', '', $stem);
            // Strip trailing M/L
            $stripped = preg_replace('/[ML]$/', '', $stripped);
            if (isset($bases[$stripped])) {
                $matched[] = $f;
            }
        }

        // Prefer thumbs when available
        usort($matched, function ($a, $b) {
            $aMain = !preg_match('/(_alt\d*|-alt\d*|_view\d*|-view\d*|_art\d*|-art\d*)/i', $a);
            $bMain = !preg_match('/(_alt\d*|-alt\d*|_view\d*|-view\d*|_art\d*|-art\d*)/i', $b);
            if ($aMain !== $bMain) return $aMain ? -1 : 1;
            return strcmp($a, $b);
        });

        return $matched;
    }

    /** Resolve a thumb url for a given image filename within a category. */
    public static function thumbUrl(string $category, string $filename): string
    {
        if (!isset(self::DIR_MAP[$category])) return '';
        $dir = self::DIR_MAP[$category];
        $root  = dirname(__DIR__, 2);
        $thumb = $root . '/bands_php/thumbs/images/' . $dir . '/' . $filename;
        if (is_file($thumb)) {
            return '/bands_php/thumbs/images/' . $dir . '/' . $filename;
        }
        return '/bands_php/images/' . $dir . '/' . $filename;
    }

    private static function slug(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        return trim($s, '-');
    }
}
