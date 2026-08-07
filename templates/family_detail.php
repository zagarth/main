<?php
// Family Collection Specific Detail Template
// Handles mother, father, daughter, and other family jewelry

// This template is included by unified_detail.php for family collection items

// Family-specific configurator setup (family items typically don't need complex configurators)
$showConfigurator = false; // Family items usually have simpler options

// Family-specific price calculation
function getFamilyPrice($category, $filename, $basePrice = 300) {
    $price = $basePrice;
    
    // Category modifiers
    switch ($category) {
        case 'mother':
            $price += 100;
            break;
        case 'father':
            $price += 50;
            break;
        case 'daughter':
            $price += 75;
            break;
    }
    
    // Material and design modifiers
    if (strpos($filename, 'Gold') !== false) {
        $price += 200;
    }
    if (strpos($filename, 'Diamond') !== false || strpos($filename, 'Stone') !== false) {
        $price += 150;
    }
    
    return $price;
}

// Calculate family-specific price range
$itemBasePrice = getFamilyPrice($foundCategory, $mainVariant['file'], $config['priceRange'][0]);
$itemMaxPrice = $itemBasePrice + 300; // Price range variation
$priceRange = '$' . number_format($itemBasePrice) . ' - $' . number_format($itemMaxPrice);

// Family-specific features based on category
$categoryFeatures = [
    'mother' => [
        'Elegant designs celebrating motherhood',
        'Birthstone customization options',
        'Sentimental engravings available',
        'Comfortable everyday wear',
        'Gift presentation included'
    ],
    'father' => [
        'Masculine design elements',
        'Durable construction for daily wear',
        'Professional and personal styling',
        'Engraving options for personalization',
        'Quality materials and finishes'
    ],
    'daughter' => [
        'Delicate and youthful designs',
        'Age-appropriate styling',
        'Growing room considerations',
        'Safe materials and construction',
        'Beautiful gift presentation'
    ]
];

$currentFeatures = $categoryFeatures[$foundCategory] ?? $categoryFeatures['mother'];

// Family-specific metadata
$categoryDescriptions = [
    'mother' => 'Beautiful jewelry pieces designed to celebrate the special bond between mothers and their families, featuring elegant designs and meaningful symbolism.',
    'father' => 'Distinctive jewelry for fathers that combines masculine appeal with sentimental value, perfect for celebrating paternal love and family bonds.',
    'daughter' => 'Charming jewelry pieces designed especially for daughters, featuring delicate designs that celebrate the precious father-daughter or mother-daughter relationship.'
];

$categoryDescription = $categoryDescriptions[$foundCategory] ?? $categoryDescriptions['mother'];

// Family-specific gift recommendations
$giftOccasions = [
    'mother' => ['Mother\'s Day', 'Birthday', 'Anniversary', 'Christmas', 'Push Present'],
    'father' => ['Father\'s Day', 'Birthday', 'Anniversary', 'Graduation', 'Retirement'],
    'daughter' => ['Birthday', 'Graduation', 'Sweet 16', 'Christmas', 'First Communion']
];

$occasions = $giftOccasions[$foundCategory] ?? $giftOccasions['mother'];
?>

<!-- Family Collection Specific Content -->
<div class="collection-specific-content family-content">
    
    <!-- Family Category Introduction -->
    <div class="family-introduction">
        <h3><?php echo ucfirst($foundCategory); ?> Collection</h3>
        <p><?php echo $categoryDescription; ?></p>
    </div>
    
    <!-- Enhanced Product Features for Family -->
    <div class="family-features-section">
        <h3>Product Features</h3>
        <div class="feature-grid">
            <?php foreach ($currentFeatures as $feature): ?>
            <div class="feature-item">
                <span class="feature-icon">💝</span>
                <span class="feature-text"><?php echo htmlspecialchars($feature); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Gift Occasion Suggestions -->
    <div class="gift-occasions">
        <h4>Perfect Gift Occasions</h4>
        <div class="occasions-list">
            <?php foreach ($occasions as $occasion): ?>
            <span class="occasion-tag"><?php echo htmlspecialchars($occasion); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Personalization Options -->
    <div class="personalization-options">
        <h4>Personalization Available</h4>
        <div class="personalization-grid">
            <div class="personalization-item">
                <h5>Engraving</h5>
                <p>Add names, dates, or special messages to make this piece truly unique.</p>
            </div>
            <div class="personalization-item">
                <h5>Birthstones</h5>
                <p>Incorporate family birthstones for a personal touch that celebrates each family member.</p>
            </div>
            <div class="personalization-item">
                <h5>Custom Presentation</h5>
                <p>Beautiful gift boxes and cards available for special occasions.</p>
            </div>
        </div>
    </div>
    
    <!-- Family Care Instructions -->
    <div class="care-instructions">
        <h4>Care Instructions for Family Jewelry</h4>
        <ul>
            <li>Clean gently with soft cloth and mild soap</li>
            <li>Store in provided jewelry box or soft pouch</li>
            <li>Avoid contact with perfumes and lotions</li>
            <?php if ($foundCategory === 'daughter'): ?>
            <li>Supervise young children during wear</li>
            <li>Check for loose parts regularly</li>
            <?php endif; ?>
            <li>Professional cleaning available upon request</li>
            <li>Inspect settings periodically for security</li>
        </ul>
    </div>
</div>

<!-- Family-specific styles -->
<style>
.family-content .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.family-content .feature-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: linear-gradient(145deg, #fff5f5 0%, #ffe8e8 100%);
    border-radius: 8px;
    border-left: 4px solid #ff6b6b;
}

.family-content .feature-icon {
    font-size: 18px;
    margin-right: 12px;
}

.family-content .gift-occasions {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.family-content .occasions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.family-content .occasion-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.family-content .personalization-options {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.family-content .personalization-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.family-content .personalization-item {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.family-content .personalization-item h5 {
    color: #2c3e50;
    margin-bottom: 8px;
}

.family-content .personalization-item p {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.family-content .care-instructions {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.family-content .care-instructions ul {
    margin: 10px 0;
    padding-left: 20px;
}

.family-introduction {
    text-align: center;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(145deg, #ffeef8 0%, #f8e8ff 100%);
    border-radius: 8px;
}

.family-introduction h3 {
    color: #8e44ad;
    margin-bottom: 10px;
    font-size: 24px;
}

.family-introduction p {
    color: #6c757d;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}
</style>