<?php
// Bands Collection Specific Detail Template
// Handles Celtic, fancy, plain, and cultural band configurations

// This template is included by unified_detail.php for bands collection items
// Note: Configurator is handled by unified_detail.php, this template only adds collection-specific content

// Bands-specific price calculation
function getBandsPrice($category, $filename, $basePrice = 500) {
    $price = $basePrice;
    
    // Category modifiers
    switch ($category) {
        case 'celtic':
            $price += 150;
            break;
        case 'fancy':
            $price += 200;
            break;
        case 'cultural':
            $price += 100;
            break;
        case 'plain':
            $price += 0;
            break;
    }
    
    // Material and stone modifiers
    if (strpos($filename, 'Diamond') !== false || strpos($filename, 'C58') !== false) {
        $price += 400;
    }
    if (strpos($filename, 'Ruby') !== false || strpos($filename, 'Emerald') !== false || strpos($filename, 'Sapphire') !== false) {
        $price += 300;
    }
    
    return $price;
}

// Calculate bands-specific price range
$itemBasePrice = getBandsPrice($foundCategory, $mainVariant['file'], $config['priceRange'][0]);
$itemMaxPrice = $itemBasePrice + 500; // Price range variation
$priceRange = '$' . number_format($itemBasePrice) . ' - $' . number_format($itemMaxPrice);

// Bands-specific features based on category
$categoryFeatures = [
    'celtic' => [
        'Traditional Celtic knotwork designs',
        'Available in multiple widths and patterns',
        'Mens and Ladies sizing available',
        'Premium metals and finishes',
        'Customizable pattern selection'
    ],
    'fancy' => [
        'Sophisticated decorative elements',
        'Premium gemstone options',
        'Artistic surface textures',
        'Enhanced craftsmanship details',
        'Luxury finish options'
    ],
    'cultural' => [
        'Heritage-inspired designs',
        'Cultural motif integration',
        'Traditional craftsmanship methods',
        'Symbolic pattern elements',
        'Custom cultural adaptations'
    ],
    'plain' => [
        'Classic timeless design',
        'Clean lines and smooth finish',
        'Multiple width options',
        'Perfect for everyday wear',
        'Excellent base for engraving'
    ]
];

$currentFeatures = $categoryFeatures[$foundCategory] ?? $categoryFeatures['plain'];

// Bands-specific metadata
$categoryDescriptions = [
    'celtic' => 'Traditional Celtic wedding bands with intricate knotwork and heritage patterns, perfect for couples seeking timeless Irish and Scottish symbolism.',
    'fancy' => 'Sophisticated wedding bands featuring decorative elements, premium stones, and artistic details for couples who appreciate luxurious craftsmanship.',
    'cultural' => 'Heritage-inspired wedding bands incorporating traditional motifs and cultural symbols, celebrating diverse traditions and backgrounds.',
    'plain' => 'Classic wedding bands with clean, timeless designs that focus on quality craftsmanship and enduring style.'
];

$categoryDescription = $categoryDescriptions[$foundCategory] ?? $categoryDescriptions['plain'];
?>

<!-- Bands Collection Specific Content -->
<div class="collection-specific-content bands-content">
    
    <!-- Enhanced Product Features for Bands -->
    <div class="bands-features-section">
        <h3>Product Features</h3>
        <div class="feature-grid">
            <?php foreach ($currentFeatures as $feature): ?>
            <div class="feature-item">
                <span class="feature-icon">✓</span>
                <span class="feature-text"><?php echo htmlspecialchars($feature); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Bands-specific Care Instructions -->
    <div class="care-instructions">
        <h4>Care Instructions for <?php echo ucfirst($foundCategory); ?> Bands</h4>
        <ul>
            <li>Clean regularly with warm soapy water</li>
            <li>Avoid harsh chemicals and abrasives</li>
            <?php if ($foundCategory === 'celtic'): ?>
            <li>Pay special attention to intricate knotwork details</li>
            <li>Professional cleaning recommended for deep pattern cleaning</li>
            <?php elseif ($foundCategory === 'fancy'): ?>
            <li>Handle gemstone settings with extra care</li>
            <li>Professional inspection recommended annually</li>
            <?php endif; ?>
            <li>Store in a soft cloth or jewelry box</li>
            <li>Remove during heavy activities</li>
        </ul>
    </div>
    
    <!-- Sizing Information -->
    <div class="sizing-information">
        <h4>Ring Sizing Guide</h4>
        <p>Our bands are available in standard US ring sizes. 
        <?php if ($foundCategory === 'celtic'): ?>
        Celtic bands with M suffix start at size 10 (mens), while L suffix start at size 6.5 (ladies).
        <?php endif; ?>
        Professional sizing recommended for optimal fit.</p>
        <a href="#" class="sizing-guide-link">View Complete Sizing Chart</a>
    </div>
</div>

<!-- Bands-specific styles -->
<style>
.bands-content .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.bands-content .feature-item {
    display: flex;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.bands-content .feature-icon {
    color: #28a745;
    font-weight: bold;
    margin-right: 10px;
}

.bands-content .care-instructions,
.bands-content .sizing-information {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.bands-content .care-instructions ul {
    margin: 10px 0;
    padding-left: 20px;
}

.bands-content .sizing-guide-link {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.bands-content .sizing-guide-link:hover {
    text-decoration: underline;
}

.configurator-introduction {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
}

.configurator-introduction h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.configurator-introduction p {
    color: #6c757d;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}
</style>