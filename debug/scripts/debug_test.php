<!DOCTYPE html>
<html>
<head>
    <title>Debug Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>
<body>
    <h1>Debug Test Page</h1>
    <div>This should display if the page loads</div>
    <div id="test-map" style="height: 300px; width: 100%;"></div>
    
    <script>
        console.log('TEST: Basic JavaScript works');
        console.log('TEST: jQuery available:', typeof jQuery);
        console.log('TEST: Leaflet available:', typeof L);
        
        jQuery(document).ready(function($) {
            console.log('TEST: jQuery ready works');
            
            // Test PHP JSON embedding
            var testData = <?php echo json_encode(['test' => 'data', 'count' => 123]); ?>;
            console.log('TEST: PHP JSON embedding works:', testData);
            
            // Test basic map creation
            try {
                console.log('TEST: Creating test map...');
                var map = L.map('test-map').setView([45, -75], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([45, -75]).addTo(map).bindPopup('Test marker');
                console.log('TEST: Map created successfully');
            } catch (error) {
                console.error('TEST: Map creation failed:', error);
            }
        });
    </script>
</body>
</html>
