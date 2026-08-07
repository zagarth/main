<?php
// Corp Collection Specific Detail Template
// Handles corporate jewelry and accessories

// This template is included by unified_detail.php for corp collection items

// Corp-specific features - typically simpler than bands
$showConfigurator = false; // Corp items usually don't need complex configurators

// Corp-specific price calculation
function getCorpPrice($category, $filename, $basePrice = 400) {
    $price = $basePrice;
    
    // Corporate items typically have material-based pricing
    if (strpos($filename, 'Gold') !== false) {
        $price += 300;
    }
    if (strpos($filename, 'Silver') !== false) {
        $price += 100;
    }
    if (strpos($filename, 'Premium') !== false) {
        $price += 200;
    }
    
    return $price;
}

// Calculate corp-specific price range
$itemBasePrice = getCorpPrice($foundCategory, $mainVariant['file'], $config['priceRange'][0]);
$itemMaxPrice = $itemBasePrice + 400;
$priceRange = '$' . number_format($itemBasePrice) . ' - $' . number_format($itemMaxPrice);

// Corp-specific features
$corpFeatures = [
    'Professional design suitable for business environments',
    'High-quality materials and construction',
    'Corporate logo customization available',
    'Bulk ordering options for companies',
    'Professional presentation and packaging'
];
?>

<!-- Corp Collection Specific Content -->
<div class="collection-specific-content corp-content">
    
    <!-- Corporate Introduction -->
    <div class="corp-introduction">
        <h3>Corporate Collection</h3>
        <p>Professional jewelry designed for corporate environments, combining elegance with business-appropriate styling.</p>
    </div>
    
    <!-- Corporate Features -->
    <div class="corp-features-section">
        <h3>Corporate Features</h3>
        <div class="feature-grid">
            <?php foreach ($corpFeatures as $feature): ?>
            <div class="feature-item">
                <span class="feature-icon">🏢</span>
                <span class="feature-text"><?php echo htmlspecialchars($feature); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Corporate Services -->
    <div class="corporate-services">
        <h4>Corporate Services</h4>
        <div class="services-grid">
            <div class="service-item">
                <h5>Custom Branding</h5>
                <p>Add your company logo or branding elements to create unique corporate gifts.</p>
            </div>
            <div class="service-item">
                <h5>Bulk Orders</h5>
                <p>Special pricing and handling for large corporate orders and employee recognition programs.</p>
            </div>
            <div class="service-item">
                <h5>Executive Gifts</h5>
                <p>Premium options suitable for executive gifts and high-level corporate presentations.</p>
            </div>
        </div>
    </div>
</div>

<!-- Corp-specific styles -->
<style>
.corp-content .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.corp-content .feature-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: linear-gradient(145deg, #f0f8ff 0%, #e6f3ff 100%);
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.corp-content .feature-icon {
    font-size: 18px;
    margin-right: 12px;
}

.corp-content .corporate-services {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.corp-content .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.corp-content .service-item {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.corp-content .service-item h5 {
    color: #007bff;
    margin-bottom: 10px;
}

.corp-content .service-item p {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.corp-introduction {
    text-align: center;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(145deg, #f0f8ff 0%, #e0f0ff 100%);
    border-radius: 8px;
}

.corp-introduction h3 {
    color: #007bff;
    margin-bottom: 10px;
    font-size: 24px;
}

.corp-introduction p {
    color: #6c757d;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}
</style>