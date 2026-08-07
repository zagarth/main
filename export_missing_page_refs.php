<?php
// CSV Export for Products without Page References
// Updated: 824 products (reduced from 825 after auto-fixes applied)
require_once 'includes/db_config_readonly.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="products_missing_page_refs.csv"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

try {
    $pdo = getReadOnlyDBConnection();
    
    // Get products that have names but no page references
    $stmt = $pdo->query("
        SELECT 
            product_id,
            product_name,
            category,
            subcategory,
            style,
            width_mm,
            profile,
            series,
            gender_variant
        FROM catalog_products 
        WHERE product_name IS NOT NULL 
        AND product_name != ''
        AND (page_reference IS NULL OR page_reference = '')
        ORDER BY category, product_id
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'Product ID',
        'Product Name', 
        'Category',
        'Subcategory',
        'Style',
        'Width (mm)',
        'Profile',
        'Series',
        'Gender Variant',
        'Page Reference (to fill)'
    ]);
    
    // Add data rows
    foreach ($products as $product) {
        fputcsv($output, [
            $product['product_id'],
            $product['product_name'],
            $product['category'],
            $product['subcategory'] ?? '',
            $product['style'] ?? '',
            $product['width_mm'] ?? '',
            $product['profile'] ?? '',
            $product['series'] ?? '',
            $product['gender_variant'] ?? '',
            '' // Empty column for page reference to be filled
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // If there's an error, output error message instead
    header('Content-Type: text/plain');
    echo "Error generating CSV: " . $e->getMessage();
}
?>