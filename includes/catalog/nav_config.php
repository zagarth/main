<?php
/**
 * CatalogNav configuration — single source of truth for the 5 nav parents.
 *
 * Real DB enum categories:
 *   plain_bands(739) school(212) other(197) family(188) celtic_bands(130)
 *   lockets(107) crosses(98) engagement(96) ladies_jewelry(85) signets(65)
 *   fancy_bands(61) gents_rings(60) pendants(46) medical(33) corporate(28)
 *   emblematic(27) bracelets(26) professional(20) mens_jewelry(11) wedding(6) idents(4)
 *
 * 'other' (197 rows) is intentionally NOT in main nav — surfaced via footer only.
 */

return [
    'parents' => [
        [
            'slug'  => 'wedding',
            'label' => 'Wedding',
            'url'   => '/wedding/',
            'children' => [
                ['slug' => 'bands',       'label' => 'Wedding Bands',    'url' => '/wedding/bands/',       'db' => ['plain_bands', 'wedding']],
                ['slug' => 'fancy-bands', 'label' => 'Fancy Bands',      'url' => '/wedding/fancy-bands/', 'db' => ['fancy_bands']],
                ['slug' => 'engagement',  'label' => 'Engagement Rings', 'url' => '/wedding/engagement/',  'db' => ['engagement']],
            ],
        ],
        [
            'slug'  => 'celtic',
            'label' => 'Celtic',
            'url'   => '/celtic/',
            'children' => [
                ['slug' => 'bands', 'label' => 'Celtic Bands', 'url' => '/celtic/bands/', 'db' => ['celtic_bands']],
                // Keep claddagh_bands first so BandsGrouper directory-mode powers the card grid,
                // but include real DB categories so router/product membership checks pass.
                ['slug' => 'claddagh', 'label' => 'Claddagh', 'url' => '/celtic/claddagh/', 'db' => ['claddagh_bands', 'celtic_bands', 'plain_bands']],
            ],
        ],
        [
            'slug'  => 'personal-family',
            'label' => 'Personal & Family',
            'url'   => '/personal-family/',
            'children' => [
                ['slug' => 'family',          'label' => 'Family Rings',     'url' => '/personal-family/family/',          'db' => ['family']],
                ['slug' => 'mother-daughter', 'label' => 'Mother & Daughter','url' => '/personal-family/mother-daughter/', 'db' => ['family'], 'subcategory_filter' => 'mother_daughter'],
                ['slug' => 'ladies-jewelry',  'label' => 'Ladies Jewelry',   'url' => '/personal-family/ladies-jewelry/',  'db' => ['ladies_jewelry']],
                ['slug' => 'gents-rings',     'label' => 'Gents Rings',      'url' => '/personal-family/gents-rings/',     'db' => ['gents_rings']],
                ['slug' => 'signets',         'label' => 'Signets',          'url' => '/personal-family/signets/',         'db' => ['signets']],
                ['slug' => 'mens-jewelry',    'label' => "Men's Jewelry",    'url' => '/personal-family/mens-jewelry/',    'db' => ['mens_jewelry']],
            ],
        ],
        [
            'slug'  => 'corporate-service',
            'label' => 'Corporate & Service',
            'url'   => '/corporate-service/',
            'children' => [
                ['slug' => 'corporate',     'label' => 'Corporate Awards', 'url' => '/corporate-service/corporate/',     'db' => ['corporate']],
                ['slug' => 'emblematic',    'label' => 'Emblematic',       'url' => '/corporate-service/emblematic/',    'db' => ['emblematic']],
                ['slug' => 'medical',       'label' => 'Medical',          'url' => '/corporate-service/medical/',       'db' => ['medical']],
                ['slug' => 'professional',  'label' => 'Professional',     'url' => '/corporate-service/professional/',  'db' => ['professional']],
                ['slug' => 'identification','label' => 'Identification',   'url' => '/corporate-service/identification/','db' => ['idents']],
            ],
        ],
        [
            // School is its own top-level — mirrors the legacy School.php
            // three-section layout (Bands / Crest Tops / Shoulders).
            // DB has 212 school rows already split by image path; an
            // UPDATE backfills the subcategory column so the standard
            // catalog/category.php grid works for bands and crest-tops.
            // /school/shoulders/ is special-cased to surface the
            // interactive shoulder customizer from School.php.
            'slug'  => 'school',
            'label' => 'School',
            'url'   => '/school/',
            'children' => [
                ['slug' => 'bands',      'label' => 'School Bands', 'url' => '/school/bands/',      'db' => ['school'], 'subcategory_filter' => 'bands'],
                ['slug' => 'crest-tops', 'label' => 'Crest Tops',   'url' => '/school/crest-tops/', 'db' => ['school'], 'subcategory_filter' => 'crest_tops'],
                ['slug' => 'shoulders',  'label' => 'Shoulders',    'url' => '/school/shoulders/',  'db' => ['school'], 'subcategory_filter' => 'shoulders'],
            ],
        ],
        [
            'slug'  => 'accessories',
            'label' => 'Accessories',
            'url'   => '/accessories/',
            'children' => [
                ['slug' => 'lockets',   'label' => 'Lockets',   'url' => '/accessories/lockets/',   'db' => ['lockets']],
                ['slug' => 'crosses',   'label' => 'Crosses',   'url' => '/accessories/crosses/',   'db' => ['crosses']],
                ['slug' => 'pendants',  'label' => 'Pendants',  'url' => '/accessories/pendants/',  'db' => ['pendants']],
                ['slug' => 'bracelets', 'label' => 'Bracelets', 'url' => '/accessories/bracelets/', 'db' => ['bracelets']],
                ['slug' => 'identity-bracelets', 'label' => 'Identity Bracelets', 'url' => '/accessories/identity-bracelets/', 'db' => ['idents']],
                ['slug' => 'medical-alerts', 'label' => 'Medical Alerts', 'url' => '/accessories/medical-alerts/', 'db' => ['medical']],
            ],
        ],
    ],

    // Right-side links (rendered after the parents, outside any dropdown)
    'links' => [
        ['label' => 'About',   'url' => '/about/'],
        ['label' => 'Catalog', 'url' => '/catalog_direct.php', 'icon' => '📖'],
        ['label' => 'Contact', 'url' => '#contact', 'onclick' => "openContactModalWithTracking(event, 'main_nav'); return false;"],
    ],
];
