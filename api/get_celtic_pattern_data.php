<?php
/**
 * Celtic/Cultural Pattern Data API
 * Returns pattern and width data from Celtic/Cultural XML mapping for a specific product
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Get parameters
    $productId = $_GET['product_id'] ?? '';
    $category = $_GET['category'] ?? 'celtic';
    
    if (empty($productId)) {
        throw new Exception('Product ID is required');
    }
    
    // Load the appropriate XML file
    $xmlFile = __DIR__ . "/../bands_php/{$category}_bands_mapping.xml";
    
    if (!file_exists($xmlFile)) {
        throw new Exception("XML mapping file not found: {$category}_bands_mapping.xml");
    }
    
    $xml = simplexml_load_file($xmlFile);
    if (!$xml) {
        throw new Exception('Failed to load XML file');
    }
    
    // Remove M/L suffix from product ID to find base product
    $baseProductId = preg_replace('/[ML]$/', '', $productId);
    
    // Search for the pattern that contains this product ID
    $foundPattern = null;
    $foundWidth = null;
    
    foreach ($xml->pattern as $pattern) {
        $patternName = (string)$pattern['name'];
        $patternSymbol = (string)$pattern['symbol'];
        
        foreach ($pattern->band as $band) {
            $bandProductId = (string)$band->product_id;
            $bandWidth = (string)$band['width'];
            
            if ($bandProductId === $baseProductId) {
                $foundPattern = [
                    'name' => $patternName,
                    'symbol' => $patternSymbol,
                    'description' => (string)$pattern->description,
                    'heritage' => (string)$pattern->heritage
                ];
                
                // Get all width options for this pattern
                $widthOptions = [];
                foreach ($pattern->band as $widthBand) {
                    $widthProductId = (string)$widthBand->product_id;
                    $widthValue = (string)$widthBand['width'];
                    
                    // Determine price modifier based on width (larger = more expensive)
                    $priceModifier = 0;
                    if ($widthValue === '7.5mm') {
                        $priceModifier = 75;
                    } elseif ($widthValue === '6.5mm') {
                        $priceModifier = 50;
                    } // 5.5mm stays at 0
                    
                    $widthOptions[] = [
                        'width' => $widthValue,
                        'product_id' => $widthProductId,
                        'price_modifier' => $priceModifier,
                        'available_genders' => ['M', 'L'] // All Celtic patterns support both genders
                    ];
                }
                
                $foundPattern['width_options'] = $widthOptions;
                break 2; // Break out of both loops
            }
        }
    }
    
    if (!$foundPattern) {
        throw new Exception("Pattern not found for product ID: {$productId}");
    }
    
    // Return the pattern data
    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'base_product_id' => $baseProductId,
        'category' => $category,
        'pattern' => $foundPattern
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>