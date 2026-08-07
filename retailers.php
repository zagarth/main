<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

$retailers_raw = file_get_contents(__DIR__ . '/canadian_retailers.json');
$retailers_all = json_decode($retailers_raw, true) ?? [];

$province_names = [
    'AB' => 'Alberta',
    'BC' => 'British Columbia',
    'MB' => 'Manitoba',
    'NB' => 'New Brunswick',
    'NF' => 'Newfoundland and Labrador',
    'NS' => 'Nova Scotia',
    'NT' => 'Northwest Territories',
    'NU' => 'Nunavut',
    'ON' => 'Ontario',
    'PE' => 'Prince Edward Island',
    'QC' => 'Quebec',
    'SK' => 'Saskatchewan',
    'YT' => 'Yukon',
];

$retailers = array_filter($retailers_all, function($r) use ($province_names) {
    return !empty($r['name']) && !empty($r['city']) && isset($province_names[strtoupper($r['state'] ?? '')]);
});

$retailers = array_map(function($r) {
    $r['state'] = strtoupper($r['state']);
    return $r;
}, $retailers);

$by_province = [];
foreach ($retailers as $r) {
    $by_province[$r['state']][] = $r;
}
ksort($by_province);

$total = count($retailers);

$ld_items = [];
$pos = 1;
foreach ($retailers as $r) {
    $item = [
        '@type'    => 'ListItem',
        'position' => $pos++,
        'item'     => [
            '@type'   => 'JewelryStore',
            'name'    => $r['name'],
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $r['street'] ?? '',
                'addressLocality' => $r['city'],
                'addressRegion'   => $r['state'],
                'postalCode'      => $r['postal_code'] ?? '',
                'addressCountry'  => 'CA',
            ],
        ],
    ];
    if (!empty($r['phone'])) {
        $item['item']['telephone'] = $r['phone'];
    }
    $ld_items[] = $item;
}

$json_ld = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => 'Cadman Manufacturing Authorized Retailers in Canada',
    'description'     => 'Full directory of authorized Cadman Manufacturing jewellery retailers across Canada.',
    'numberOfItems'   => $total,
    'itemListElement' => $ld_items,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0066CC" />
<meta name="description" content="Find an authorized Cadman Manufacturing jewellery retailer near you. <?php echo $total; ?> locations across Canada including Ontario, British Columbia, Alberta, and more." />
<link rel="canonical" href="https://cadmanmfg.com/retailers.php" />
<link rel="icon" sizes="" href="/favicon.ico">
<title>Authorized Retailers - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>

<!-- Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:url" content="https://cadmanmfg.com/retailers.php" />
<meta property="og:title" content="Authorized Retailers – Cadman Manufacturing" />
<meta property="og:description" content="Find an authorized Cadman Manufacturing jewellery retailer near you. <?php echo $total; ?> locations across Canada." />
<meta property="og:image" content="https://cadmanmfg.com/PNG/logo.png" />

<script type="application/ld+json">
<?php echo $json_ld; ?>
</script>

<style>
#retailers-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.retailers-hero {
    text-align: center;
    margin-bottom: 40px;
}

.retailers-hero h1 {
    color: #FFD700;
    font-size: 2.4em;
    margin-bottom: 12px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.retailers-hero p {
    color: rgba(255,255,255,0.75);
    font-size: 1.1em;
    max-width: 680px;
    margin: 0 auto 12px;
    line-height: 1.7;
}

.search-bar-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 36px;
}

#retailer-search {
    width: 100%;
    max-width: 500px;
    padding: 12px 18px;
    border-radius: 30px;
    border: 2px solid rgba(255,215,0,0.4);
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-size: 1em;
    outline: none;
    transition: border-color 0.3s;
}

#retailer-search::placeholder { color: rgba(255,255,255,0.5); }
#retailer-search:focus { border-color: #FFD700; }

.province-section {
    margin-bottom: 44px;
}

.province-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(255,215,0,0.3);
}

.province-heading h2 {
    color: #FFD700;
    font-size: 1.5em;
    margin: 0;
}

.province-count {
    background: rgba(255,215,0,0.15);
    color: #FFD700;
    border: 1px solid rgba(255,215,0,0.3);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.82em;
    font-weight: 600;
}

.retailers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}

.retailer-card {
    background: rgba(255,255,255,0.93);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    transition: transform 0.2s, box-shadow 0.2s;
    border-top: 3px solid #FFD700;
}

.retailer-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
}

.retailer-name {
    font-size: 1em;
    font-weight: 700;
    color: #222;
    margin: 0 0 8px;
    line-height: 1.3;
}

.retailer-address {
    font-size: 0.85em;
    color: #555;
    margin-bottom: 10px;
    line-height: 1.5;
}

.retailer-phone {
    display: inline-block;
    font-size: 0.85em;
    color: #0066CC;
    text-decoration: none;
    font-weight: 600;
}

.retailer-phone:hover { text-decoration: underline; }

.retailer-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.retailer-tag {
    background: rgba(0,102,204,0.08);
    color: #0066CC;
    border: 1px solid rgba(0,102,204,0.2);
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.72em;
    font-weight: 500;
}

#no-results {
    display: none;
    text-align: center;
    padding: 50px 20px;
    color: rgba(255,255,255,0.6);
    font-size: 1.1em;
}

.back-link {
    display: inline-block;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 0.9em;
    margin-bottom: 30px;
    transition: color 0.2s;
}

.back-link:hover { color: #FFD700; }

@media (max-width: 600px) {
    .retailers-hero h1 { font-size: 1.8em; }
    .retailers-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
    <?php include 'includes/search_modal.php'; ?>
    <?php include 'navigation.php'; renderNavigation(''); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>

    <div id="wrapper">
        <div id="retailers-wrapper">

            <a href="/" class="back-link">&#8592; Back to Home</a>

            <div class="retailers-hero">
                <h1>&#127978; Authorized Retailers</h1>
                <p>Find an authorized Cadman Manufacturing retailer near you. Our trusted partners carry authentic Cadman jewellery and provide expert service across Canada.</p>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.9em;"><?php echo $total; ?> retailers in <?php echo count($by_province); ?> provinces &amp; territories</p>
            </div>

            <div class="search-bar-wrap">
                <input type="search" id="retailer-search" placeholder="Search by name, city, or province&#8230;" autocomplete="off" aria-label="Search retailers">
            </div>

            <div id="retailers-list">
                <?php foreach ($by_province as $code => $stores): ?>
                <section class="province-section" data-province="<?php echo htmlspecialchars($code); ?>">
                    <div class="province-heading">
                        <h2><?php echo htmlspecialchars($province_names[$code]); ?></h2>
                        <span class="province-count"><?php echo count($stores); ?></span>
                    </div>
                    <div class="retailers-grid">
                        <?php foreach ($stores as $r): ?>
                        <div class="retailer-card"
                             data-name="<?php echo htmlspecialchars(strtolower($r['name'])); ?>"
                             data-city="<?php echo htmlspecialchars(strtolower($r['city'])); ?>"
                             data-province="<?php echo htmlspecialchars(strtolower($province_names[$code])); ?>">

                            <p class="retailer-name"><?php echo htmlspecialchars($r['name']); ?></p>

                            <p class="retailer-address">
                                <?php if (!empty($r['street'])): ?>
                                    <?php echo htmlspecialchars($r['street']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($r['city']); ?>, <?php echo htmlspecialchars($code); ?>
                                <?php if (!empty($r['postal_code'])): ?>
                                    &nbsp;<?php echo htmlspecialchars($r['postal_code']); ?>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($r['phone'])): ?>
                            <a class="retailer-phone" href="tel:<?php echo preg_replace('/[^+\d]/', '', $r['phone']); ?>">
                                <?php echo htmlspecialchars($r['phone']); ?>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($r['specialties'])): ?>
                            <div class="retailer-tags">
                                <?php foreach (array_slice($r['specialties'], 0, 3) as $tag): ?>
                                <span class="retailer-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>

            <div id="no-results">No retailers found matching your search.</div>

        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    (function () {
        var input = document.getElementById('retailer-search');
        var noResults = document.getElementById('no-results');

        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var anyVisible = false;

            document.querySelectorAll('.province-section').forEach(function (section) {
                var sectionVisible = false;
                section.querySelectorAll('.retailer-card').forEach(function (card) {
                    var match = !q
                        || card.dataset.name.includes(q)
                        || card.dataset.city.includes(q)
                        || card.dataset.province.includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) sectionVisible = true;
                });
                section.style.display = sectionVisible ? '' : 'none';
                if (sectionVisible) anyVisible = true;
            });

            noResults.style.display = anyVisible ? 'none' : 'block';
        });
    })();
    </script>
</body>
</html>
