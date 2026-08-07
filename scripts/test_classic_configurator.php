<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classic Bands Configurator Test</title>
    <link rel="stylesheet" href="css/configurator.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .instructions {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1>🔧 Classic Bands Configurator Test</h1>
        <p class="instructions">
            Testing the new Classic Bands configurator with plain_grid type and XML-based width options.
            Select a style group, then choose a width to see the configurator in action.
        </p>
        <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
            <strong>Test Products:</strong>
            <button onclick="loadProduct('400M')" style="margin: 5px;">400M (Tiffany)</button>
            <button onclick="loadProduct('600RM')" style="margin: 5px;">600RM (Rectangular)</button>
            <button onclick="loadProduct('5T18L')" style="margin: 5px;">5T18L (Tiffany Comfort)</button>
            <button onclick="loadProduct('S400RL')" style="margin: 5px;">S400RL (Rectangular Lightweight)</button>
            <button onclick="loadProduct('500TM')" style="margin: 5px;">500TM (Premium)</button>
        </div>
    </div>

    <!-- Configurator container -->
    <div id="product-configurator"></div>

    <!-- Include jQuery and configurator -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/configurator.js"></script>
    
    <script>
        console.log('🚀 Initializing Classic Bands Configurator...');
        
        let configurator;
        
        // Function to load configurator with specific product and category
        function loadProduct(productId, category) {
            category = category || 'plain'; // default to plain
            console.log('Loading product:', productId, 'category:', category);
            
            const container = document.getElementById('product-configurator');
            container.innerHTML = ''; // Clear existing
            
            // Set data attributes on the container for the configurator
            container.setAttribute('data-collection', 'bands');
            container.setAttribute('data-product-id', productId);
            container.setAttribute('data-category', category);
            container.setAttribute('data-base-price', '450');
            container.setAttribute('data-product-name', getCategoryName(category) + ' Wedding Band');
            
            // Set the config path based on category
            const configPath = getConfigPath(category);
            container.setAttribute('data-config-path', configPath);
            
            configurator = new ProductConfigurator('product-configurator');
            configurator.init();
        }
        
        function getCategoryName(category) {
            const names = {
                'plain': 'Classic',
                'celtic': 'Celtic',
                'cultural': 'Cultural', 
                'fancy': 'Fancy'
            };
            return names[category] || 'Classic';
        }
        
        function getConfigPath(category) {
            return 'bands_php/' + category + '_configurator.json';
        }
        
        // Initialize configurator when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check for productId and category in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('productId') || '400M'; // Default test product
            const category = urlParams.get('category') || 'plain'; // Default category
            
            loadProduct(productId, category);
            console.log('✅ Configurator initialized with product:', productId, 'category:', category);
        });
    </script>
</body>
</html>
