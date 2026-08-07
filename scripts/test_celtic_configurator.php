<?php
// Simple test page for Celtic configurator

// Simulate a Celtic product context - using Celtic link pattern
$productId = "5212M";
$category = "celtic";
$baseProductId = "5212";
$productName = "Celtic Link Band - 5.5mm";
$basePrice = 550;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Celtic Configurator Test</title>
    <link rel="stylesheet" href="css/configurator.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f8f9fa;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Celtic Configurator Test</h1>
        
        <div class="product-info">
            <strong>Test Product:</strong> <?php echo $productId; ?> - <?php echo $productName; ?><br>
            <strong>Category:</strong> <?php echo $category; ?><br>
            <strong>Base ID:</strong> <?php echo $baseProductId; ?><br>
            <strong>Base Price:</strong> $<?php echo $basePrice; ?>
        </div>

        <!-- Product Configurator Container -->
        <div class="product-configurator" 
             data-product-id="<?php echo $productId; ?>"
             data-category="<?php echo $category; ?>"
             data-base-product-id="<?php echo $baseProductId; ?>"
             data-product-name="<?php echo $productName; ?>"
             data-base-price="<?php echo $basePrice; ?>"
             data-collection="celtic">
            
            <div class="configurator-loading">
                <p>Loading configurator...</p>
            </div>
        </div>
        
        <!-- Summary Panel -->
        <div class="configuration-summary" style="margin-top: 30px;">
            <h3>Your Configuration</h3>
            <div class="summary-content">
                <p>Configure your Celtic ring above to see summary here.</p>
            </div>
        </div>
    </div>

    <script src="js/configurator.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('========================================');
            console.log('Celtic Configurator Test - Two-Tone Feature');
            console.log('========================================');
            console.log('Initializing Celtic configurator test...');
            console.log('Product ID: <?php echo $productId; ?>');
            console.log('Category: <?php echo $category; ?>');
            console.log('Base Price: $<?php echo $basePrice; ?>');
            
            // Initialize the configurator
            const configuratorElement = document.querySelector('.product-configurator');
            if (configuratorElement) {
                console.log('✓ Configurator element found');
                console.log('  Data attributes:', {
                    productId: configuratorElement.dataset.productId,
                    category: configuratorElement.dataset.category,
                    baseProductId: configuratorElement.dataset.baseProductId,
                    basePrice: configuratorElement.dataset.basePrice,
                    collection: configuratorElement.dataset.collection
                });
                
                const configurator = new ProductConfigurator(configuratorElement);
                
                // Add event listener to check when config is loaded
                setTimeout(() => {
                    if (configurator.config) {
                        console.log('\n✅ Configuration loaded successfully');
                        console.log('Collection:', configurator.config.collection);
                        console.log('Base Price:', configurator.config.data?.base_price);
                        
                        // Check for two-tone option
                        const twoTone = configurator.config.data?.options?.two_tone;
                        if (twoTone) {
                            console.log('\n🎨 TWO-TONE OPTION FOUND:');
                            console.log('  Type:', twoTone.type);
                            console.log('  Label:', twoTone.label);
                            console.log('  Required:', twoTone.required);
                            console.log('  Price Modifier:', '$' + twoTone.options?.enabled?.price_modifier);
                            console.log('  Primary Metal Options:', twoTone.options?.primary_metal?.options?.length);
                            console.log('  Secondary Metal Options:', twoTone.options?.secondary_metal?.options?.length);
                        } else {
                            console.log('\n❌ Two-tone option NOT found in config');
                        }
                        
                        // Check for antiquing option
                        const antiquing = configurator.config.data?.options?.antiquing;
                        if (antiquing) {
                            console.log('\n⚫ ANTIQUING OPTION FOUND:');
                            console.log('  Type:', antiquing.type);
                            console.log('  Label:', antiquing.label);
                            console.log('  Price Modifier:', '$' + antiquing.options?.[1]?.price_modifier);
                        }
                        
                        // Check pattern grid
                        const patterns = configurator.config.data?.options?.cultural_pattern_width?.grid_layout?.rows;
                        if (patterns) {
                            console.log('\n📐 PATTERN GRID:');
                            console.log('  Total patterns:', patterns.length);
                            console.log('  First 5 patterns:', patterns.slice(0, 5).map(p => p.pattern).join(', '));
                        }
                    } else {
                        console.log('\n❌ Configuration not loaded yet');
                    }
                }, 1000);
                
                configurator.init();
            } else {
                console.log('❌ Configurator element NOT found');
            }
        });
    </script>
</body>
</html>