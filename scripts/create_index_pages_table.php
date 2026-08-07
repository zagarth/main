<?php
/**
 * Create Index Pages Table and Populate with Filesystem Data
 * This will fix the index page mapping issue by creating a proper database table
 */

require_once 'includes/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "CREATING INDEX PAGES TABLE\n";
    echo "==========================\n\n";
    
    // Create the index_pages table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS index_pages (
        index_id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL,
        index_file VARCHAR(255) NOT NULL,
        section_name VARCHAR(100),
        keywords TEXT,
        sort_order INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_category_file (category, index_file),
        INDEX idx_category (category),
        INDEX idx_active (active)
    )";
    
    $pdo->exec($createTableSQL);
    echo "✅ Created index_pages table\n\n";
    
    // Index files from filesystem with their likely category mappings
    $indexMappings = [
        ['category' => 'crosses', 'index_file' => 'index_page_10k-crosses_01.pdf', 'section_name' => '10K Crosses', 'keywords' => 'cross,crosses,10k,gold', 'sort_order' => 10],
        ['category' => 'crosses', 'index_file' => 'index_page_ster-crosses_01.pdf', 'section_name' => 'Sterling Crosses', 'keywords' => 'cross,crosses,sterling,silver', 'sort_order' => 11],
        ['category' => 'lockets', 'index_file' => 'index_page_10K-LOCKETS_01.pdf', 'section_name' => '10K Lockets', 'keywords' => 'locket,lockets,10k,gold', 'sort_order' => 20],
        ['category' => 'lockets', 'index_file' => 'index_page_STER-LOCKETS_01.pdf', 'section_name' => 'Sterling Lockets', 'keywords' => 'locket,lockets,sterling,silver', 'sort_order' => 21],
        ['category' => 'bracelets', 'index_file' => 'index_page_bracelets_01.pdf', 'section_name' => 'Bracelets', 'keywords' => 'bracelet,bracelets,bangle,bangles,wrist,chain', 'sort_order' => 30],
        ['category' => 'emblematic', 'index_file' => 'index_page_EMBLEMATIC_01.pdf', 'section_name' => 'Emblematic Jewelry', 'keywords' => 'emblematic,emblem,masonic,lodge,organization', 'sort_order' => 40],
        ['category' => 'engagement', 'index_file' => 'index_page_engagementsets_01.pdf', 'section_name' => 'Engagement Sets', 'keywords' => 'engagement,wedding,bridal,set,sets,ring,rings', 'sort_order' => 50],
        ['category' => 'gents_rings', 'index_file' => 'index_page_gents-rings_01.pdf', 'section_name' => 'Gents Rings', 'keywords' => 'gents,mens,men,ring,rings,masculine', 'sort_order' => 60],
        ['category' => 'ladies_jewelry', 'index_file' => 'index_page_ladiesstone-001.pdf', 'section_name' => 'Ladies Stone Jewelry 1', 'keywords' => 'ladies,women,stone,gem,jewelry', 'sort_order' => 70],
        ['category' => 'ladies_jewelry', 'index_file' => 'index_page_ladiesstone-002.pdf', 'section_name' => 'Ladies Stone Jewelry 2', 'keywords' => 'ladies,women,stone,gem,jewelry', 'sort_order' => 71],
        ['category' => 'medical', 'index_file' => 'index_page_medical_01.pdf', 'section_name' => 'Medical Alert Jewelry', 'keywords' => 'medical,alert,emergency,health,id', 'sort_order' => 80],
        ['category' => 'mens_jewelry', 'index_file' => 'index_page_mens-jewellry_01.pdf', 'section_name' => 'Mens Jewelry', 'keywords' => 'mens,men,jewelry,masculine,chains,pendants', 'sort_order' => 90],
        ['category' => 'family', 'index_file' => 'index_page_mother-001.pdf', 'section_name' => 'Mothers & Family', 'keywords' => 'mother,mothers,mom,family,birthstone,children', 'sort_order' => 100],
        ['category' => 'pendants', 'index_file' => 'index_page_pendants-earrings_01.pdf', 'section_name' => 'Pendants & Earrings', 'keywords' => 'pendant,pendants,earring,earrings,necklace,charm', 'sort_order' => 110],
        ['category' => 'signets', 'index_file' => 'index_page_signets_01.pdf', 'section_name' => 'Signet Rings', 'keywords' => 'signet,signets,ring,rings,initial,monogram', 'sort_order' => 120],
        ['category' => 'wedding', 'index_file' => 'index_page_wedding_01.pdf', 'section_name' => 'Wedding Bands 1', 'keywords' => 'wedding,band,bands,marriage,bridal', 'sort_order' => 130],
        ['category' => 'wedding', 'index_file' => 'index_page_wedding_02.pdf', 'section_name' => 'Wedding Bands 2', 'keywords' => 'wedding,band,bands,marriage,bridal', 'sort_order' => 131],
        ['category' => 'wedding', 'index_file' => 'index_page_wedding_03.pdf', 'section_name' => 'Wedding Bands 3', 'keywords' => 'wedding,band,bands,marriage,bridal', 'sort_order' => 132]
    ];
    
    // Insert the index mappings
    $insertSQL = "INSERT INTO index_pages (category, index_file, section_name, keywords, sort_order) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertSQL);
    
    $successCount = 0;
    foreach ($indexMappings as $mapping) {
        try {
            $stmt->execute([
                $mapping['category'],
                $mapping['index_file'],
                $mapping['section_name'],
                $mapping['keywords'],
                $mapping['sort_order']
            ]);
            echo "✅ Added: {$mapping['section_name']} → {$mapping['index_file']}\n";
            $successCount++;
        } catch (PDOException $e) {
            echo "❌ Failed to add {$mapping['index_file']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SUMMARY:\n";
    echo "Successfully added: {$successCount} index page mappings\n";
    
    // Show the created table contents
    echo "\nINDEX PAGES TABLE CONTENTS:\n";
    $stmt = $pdo->query("SELECT category, section_name, index_file FROM index_pages ORDER BY sort_order");
    $results = $stmt->fetchAll();
    
    foreach ($results as $result) {
        echo "{$result['category']} → {$result['section_name']} → {$result['index_file']}\n";
    }
    
    echo "\n🎉 Index pages table created and populated successfully!\n";
    echo "Next step: Update CatalogSearch class to use this table instead of hardcoded values.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>