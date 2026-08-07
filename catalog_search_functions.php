<?php
/**
 * Catalog Search Functions
 */

/**
 * Database connection helper
 */
function getCatalogDatabaseConnection() {
    try {
        require_once __DIR__ . '/includes/db_config.php';
        return getDBConnection();
    } catch (PDOException $e) {
        error_log('Catalog database connection failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Search database for product by product ID
 */
function searchProductDatabase($searchTerm) {
    $pdo = getCatalogDatabaseConnection();
    if (!$pdo) return [];
    
    $results = [];
    
    // Clean the search term - remove common prefixes/suffixes and normalize case
    $cleanTerm = strtoupper(trim($searchTerm));
    $cleanTerm = preg_replace('/^(PRODUCT|ITEM|#|NO\.?)/i', '', $cleanTerm);
    $cleanTerm = trim($cleanTerm);
    
    try {
        // Search for exact product ID match (prioritize those with page references)
        // Using UPPER() to ensure case-insensitive search
        $stmt = $pdo->prepare(
            "SELECT product_id, product_name, page_reference, category, subcategory, source, pdf_file 
             FROM catalog_products 
             WHERE UPPER(product_id) = UPPER(?) 
             ORDER BY (page_reference IS NOT NULL) DESC, page_reference"
        );
        $stmt->execute([$cleanTerm]);
        $exactMatches = $stmt->fetchAll();
        
        if (!empty($exactMatches)) {
            $results['exact'] = $exactMatches;
            // If we found an exact match with a page reference, don't do additional searches
            $hasExactMatchWithPageRef = false;
            foreach ($exactMatches as $match) {
                if (!empty($match['page_reference'])) {
                    $hasExactMatchWithPageRef = true;
                    break;
                }
            }
            // Return early if we have a good exact match
            if ($hasExactMatchWithPageRef) {
                return $results;
            }
        }
        
        // Search for partial product ID matches with stricter logic
        // First, check if search term closely matches any product IDs (prioritize closer matches)
        if (strlen($cleanTerm) >= 3) {
            // Look for products that start with the search term (most relevant)
            $stmt = $pdo->prepare(
                "SELECT product_id, product_name, page_reference, category, subcategory, source, pdf_file 
                 FROM catalog_products 
                 WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?)
                 AND page_reference IS NOT NULL
                 ORDER BY LENGTH(product_id), product_id
                 LIMIT 5"
            );
            $stmt->execute([$cleanTerm . '%', $cleanTerm]);
            $startsWithMatches = $stmt->fetchAll();
            
            if (!empty($startsWithMatches)) {
                $results['starts_with'] = $startsWithMatches;
                // If we found good starts-with matches with page references, don't show confusing partial results
                $hasGoodStartsWithMatch = false;
                foreach ($startsWithMatches as $match) {
                    if (!empty($match['page_reference'])) {
                        $hasGoodStartsWithMatch = true;
                        break;
                    }
                }
                // Return early if we have a good starts-with match
                if ($hasGoodStartsWithMatch) {
                    return $results;
                }
            }
            
            // Then do broader partial matching only if we need more results
            if (preg_match('/^(\d+[A-Z]*)/', $cleanTerm, $matches) && strlen($matches[1]) < strlen($cleanTerm)) {
                // For terms like "4T72", "100D", use a broader prefix only if we're not already matching well
                $prefix = $matches[1];
                $stmt = $pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes 
                     FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?) 
                     AND UPPER(product_id) NOT LIKE UPPER(?)
                     ORDER BY (pdf_file IS NOT NULL) DESC, product_id
                     LIMIT 15"
                );
                $stmt->execute([$prefix . '%', $cleanTerm, $cleanTerm . '%']); // Exclude exact match and starts-with matches
                $partialMatches = $stmt->fetchAll();
                
                if (!empty($partialMatches)) {
                    $results['partial'] = $partialMatches;
                }
            }
        } elseif (strlen($cleanTerm) <= 3 && preg_match('/^\d+/', $cleanTerm, $matches)) {
            // Only for very short terms like "4", "10", "700", use the old logic
            $numberPart = $matches[0];
            $stmt = $pdo->prepare(
                "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes 
                 FROM catalog_products 
                 WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?)
                 ORDER BY (pdf_file IS NOT NULL) DESC, product_id
                 LIMIT 15"
            );
            $stmt->execute([$numberPart . '%', $cleanTerm]);
            $partialMatches = $stmt->fetchAll();
            
            if (!empty($partialMatches)) {
                $results['partial'] = $partialMatches;
            }
        }
        
        // Search by category if search term matches category (case-insensitive)
        if (strlen($cleanTerm) >= 4) { // Only do category search for longer terms
            $stmt = $pdo->prepare(
                "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes 
                 FROM catalog_products 
                 WHERE UPPER(category) LIKE UPPER(?) AND pdf_file IS NOT NULL
                 ORDER BY product_id
                 LIMIT 10"
            );
            $stmt->execute(['%' . $cleanTerm . '%']);
            $categoryMatches = $stmt->fetchAll();
            
            if (!empty($categoryMatches)) {
                $results['category'] = $categoryMatches;
            }
        }
        
    } catch (PDOException $e) {
        error_log('Database search failed: ' . $e->getMessage());
    }
    
    return $results;
}

/**
 * Create searchable catalog index from index PDFs
 */
function getCatalogSearchIndex() {
    return [
        // Wedding & Engagement
        'wedding' => [
            'keywords' => ['wedding', 'engagement', 'bridal', 'bride', 'groom', 'marriage', 'ceremony'],
            'indexes' => ['index_page_wedding_01.pdf', 'index_page_wedding_02.pdf', 'index_page_wedding_03.pdf', 'index_page_engagementsets_01.pdf'],
            'content_pages' => ['page_01a.pdf', 'page_01b.pdf', 'page_02a.pdf', 'page_02b.pdf', 'page_02c.pdf', 'page_02d.pdf', 'page_03a.pdf', 'page_03b.pdf', 'page_03c.pdf', 'page_03d.pdf'],
            'description' => 'Wedding bands, engagement rings, and bridal sets'
        ],
        
        // Men's Jewelry
        'mens' => [
            'keywords' => ['mens', 'men', 'gents', 'gentlemen', 'male', 'masculine', 'guys'],
            'indexes' => ['index_page_gents-rings_01.pdf', 'index_page_mens-jewellry_01.pdf', 'index_page_signets_01.pdf'],
            'content_pages' => ['page_06a.pdf', 'page_06b.pdf', 'page_06c.pdf', 'page_07a.pdf', 'page_07b.pdf'],
            'description' => 'Men\'s rings, gents jewelry, and signet rings'
        ],
        
        // Ladies Stone Set
        'ladies' => [
            'keywords' => ['ladies', 'women', 'female', 'stone', 'stones', 'gems', 'diamond', 'birthstone'],
            'indexes' => ['index_page_ladiesstone-001.pdf', 'index_page_ladiesstone-002.pdf'],
            'content_pages' => ['page_08a.pdf', 'page_08b.pdf', 'page_08c.pdf', 'page_09a.pdf', 'page_09b.pdf'],
            'description' => 'Ladies stone-set rings and jewelry'
        ],
        
        // Religious & Spiritual
        'religious' => [
            'keywords' => ['cross', 'crosses', 'religious', 'christian', 'faith', 'spiritual', 'crucifix'],
            'indexes' => ['index_page_10k-crosses_01.pdf', 'index_page_ster-crosses_01.pdf'],
            'content_pages' => ['page_23a.pdf', 'page_23b.pdf', 'page_23c.pdf'],
            'description' => 'Religious jewelry, crosses, and spiritual items'
        ],
        
        // Lockets & Keepsakes
        'lockets' => [
            'keywords' => ['locket', 'lockets', 'keepsake', 'memory', 'photo', 'picture', 'memorial'],
            'indexes' => ['index_page_10K-LOCKETS_01.pdf', 'index_page_STER-LOCKETS_01.pdf'],
            'content_pages' => ['page_10a.pdf', 'page_10b.pdf', 'page_10c.pdf'],
            'description' => 'Lockets, keepsake jewelry, and memorial pieces'
        ],
        
        // Pendants & Earrings
        'pendants' => [
            'keywords' => ['pendant', 'pendants', 'earring', 'earrings', 'necklace', 'charm'],
            'indexes' => ['index_page_pendants-earrings_01.pdf'],
            'content_pages' => ['page_22a.pdf', 'page_22b.pdf', 'page_22c.pdf'],
            'description' => 'Pendants, earrings, and neck wear'
        ],
        
        // Bracelets
        'bracelets' => [
            'keywords' => ['bracelet', 'bracelets', 'bangle', 'bangles', 'wrist', 'chain'],
            'indexes' => ['index_page_bracelets_01.pdf'],
            'content_pages' => ['page_21a.pdf', 'page_21b.pdf'],
            'description' => 'Bracelets, bangles, and wrist jewelry'
        ],
        
        // Medical & Emblematic
        'medical' => [
            'keywords' => ['medical', 'alert', 'emergency', 'emblematic', 'emblem', 'masonic', 'lodge'],
            'indexes' => ['index_page_medical_01.pdf', 'index_page_EMBLEMATIC_01.pdf'],
            'content_pages' => ['page_11a.pdf', 'page_11b.pdf', 'page_12a.pdf'],
            'description' => 'Medical alert and emblematic jewelry'
        ],
        
        // Mother's Jewelry
        'mothers' => [
            'keywords' => ['mother', 'mothers', 'mom', 'family', 'birthstone', 'children'],
            'indexes' => ['index_page_mother-001.pdf'],
            'content_pages' => ['page_15a.pdf', 'page_15b.pdf'],
            'description' => 'Mother\'s rings and family jewelry'
        ]
    ];
}

/**
 * Search catalog using intelligent keyword matching with database integration
 */
function searchCatalogIntelligent($searchTerm) {
    $searchIndex = getCatalogSearchIndex();
    $results = [];
    $searchTerm = strtolower(trim($searchTerm));
    
    // First check if this looks like a page number pattern (prioritize over product IDs)
    if (preg_match('/^(?:page[\s_]*)?(\d+)([a-z]{1,3})?$/i', $searchTerm, $matches)) {
        $pageNum = $matches[1];
        $pageLetter = isset($matches[2]) ? strtolower($matches[2]) : '';
        
        $catalogDir = './Cadman_catalog/';
        $files = scandir($catalogDir);
        $pageFiles = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                // Match patterns like: page_01a.pdf, page_21B.pdf, page_03.pdf, page_06L.pdf (case-insensitive)
                if ($pageLetter) {
                    // Specific letter search: "9d" should match "page_09d.pdf" or "page_9d.pdf"
                    $pattern = "/^page_?0?{$pageNum}[_]?{$pageLetter}\.pdf$/i";
                    if (preg_match($pattern, $file)) {
                        $pageFiles[] = $file;
                    }
                } else {
                    // General page search: "9" should match "page_09d.pdf", "page_9A.pdf", etc.
                    $pattern = "/^page_?0?{$pageNum}[a-z]*\.pdf$/i";
                    if (preg_match($pattern, $file)) {
                        $pageFiles[] = $file;
                    }
                }
            }
        }
        
        if (!empty($pageFiles)) {
            // Sort files to put main page first (without letter), then alphabetically
            usort($pageFiles, function($a, $b) use ($pageNum) {
                $aBase = preg_match("/^page_?{$pageNum}\.pdf$/i", $a);
                $bBase = preg_match("/^page_?{$pageNum}\.pdf$/i", $b);
                if ($aBase && !$bBase) return -1;
                if ($bBase && !$aBase) return 1;
                return strcasecmp($a, $b);
            });
            
            return [
                'type' => 'direct_page',
                'files' => $pageFiles,
                'description' => $pageLetter ? "Page {$pageNum}" . strtoupper($pageLetter) : "Page {$pageNum} series",
                'search_term' => $searchTerm
            ];
        }
    }
    
    // Then check if this looks like a product ID (numbers + letters)
    if (preg_match('/^\d+[a-z]*$/i', $searchTerm)) {
        $productCode = strtoupper($searchTerm);
        
        // Try database lookup for exact match
        $databaseResults = searchProductDatabase($productCode);
        if (!empty($databaseResults)) {
            $dbFiles = [];
            foreach ($databaseResults as $resultType => $matches) {
                foreach ($matches as $product) {
                    if ($product['pdf_file'] && !in_array($product['pdf_file'], $dbFiles)) {
                        $dbFiles[] = $product['pdf_file'];
                    }
                }
            }
            
            if (!empty($dbFiles)) {
                return [
                    'type' => 'direct_page',
                    'files' => array_unique($dbFiles),
                    'description' => "Product {$productCode}",
                    'search_term' => $searchTerm
                ];
            }
        }
    }
    
    // First, try database search for product numbers
    $databaseResults = searchProductDatabase($searchTerm);
    if (!empty($databaseResults)) {
        // Convert database results to catalog format
        $dbFiles = [];
        $descriptions = [];
        
        foreach ($databaseResults as $resultType => $matches) {
            foreach ($matches as $product) {
                if ($product['pdf_file'] && !in_array($product['pdf_file'], $dbFiles)) {
                    $dbFiles[] = $product['pdf_file'];
                }
            }
            
            switch ($resultType) {
                case 'exact':
                    $descriptions[] = "Exact product match: " . implode(', ', array_column($matches, 'product_id'));
                    break;
                case 'partial':
                    $descriptions[] = "Related products: " . implode(', ', array_slice(array_column($matches, 'product_id'), 0, 5)) . (count($matches) > 5 ? ' (+' . (count($matches) - 5) . ' more)' : '');
                    break;
                case 'category':
                    $descriptions[] = "Category \"" . $matches[0]['category'] . "\": " . count($matches) . " products";
                    break;
                case 'specifications':
                    $descriptions[] = "Specification match: " . count($matches) . " products";
                    break;
            }
        }
        
        if (!empty($dbFiles)) {
            return [
                'type' => 'database_match',
                'files' => array_unique($dbFiles),
                'description' => implode('; ', $descriptions),
                'search_term' => $searchTerm,
                'database_details' => $databaseResults
            ];
        }
    }
    
    // First, try database search for product numbers
    $databaseResults = searchProductDatabase($searchTerm);
    if (!empty($databaseResults)) {
        // Convert database results to catalog format
        $dbFiles = [];
        $descriptions = [];
        
        foreach ($databaseResults as $resultType => $matches) {
            foreach ($matches as $product) {
                if ($product['pdf_file'] && !in_array($product['pdf_file'], $dbFiles)) {
                    $dbFiles[] = $product['pdf_file'];
                }
            }
            
            switch ($resultType) {
                case 'exact':
                    $descriptions[] = "Exact product match: " . implode(', ', array_column($matches, 'product_id'));
                    break;
                case 'partial':
                    $descriptions[] = "Related products: " . implode(', ', array_slice(array_column($matches, 'product_id'), 0, 5)) . (count($matches) > 5 ? ' (+' . (count($matches) - 5) . ' more)' : '');
                    break;
                case 'category':
                    $descriptions[] = "Category \"" . $matches[0]['category'] . "\": " . count($matches) . " products";
                    break;
                case 'specifications':
                    $descriptions[] = "Specification match: " . count($matches) . " products";
                    break;
            }
        }
        
        if (!empty($dbFiles)) {
            return [
                'type' => 'database_match',
                'files' => array_unique($dbFiles),
                'description' => implode('; ', $descriptions),
                'search_term' => $searchTerm,
                'database_details' => $databaseResults
            ];
        }
    }
    
    // Enhanced page number search with better pattern matching (case-insensitive)
    if (preg_match('/(?:page[\s_]*)?(\d+)([a-z]{1,3})?/i', $searchTerm, $matches)) {
        $pageNum = $matches[1];
        $pageLetter = isset($matches[2]) ? strtolower($matches[2]) : '';
        
        $catalogDir = './Cadman_catalog/';
        $files = scandir($catalogDir);
        $pageFiles = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                // Match patterns like: page_01a.pdf, page_21B.pdf, page_03.pdf, page_06L.pdf (case-insensitive)
                if ($pageLetter) {
                    // Specific letter search: "7d" should match "page_07d.pdf" or "page_7D.pdf"
                    $pattern = "/page_?0?{$pageNum}[_]?{$pageLetter}\.pdf/i";
                    if (preg_match($pattern, $file)) {
                        $pageFiles[] = $file;
                    }
                } else {
                    // General page search: "7" should match "page_07d.pdf", "page_7A.pdf", etc.
                    $pattern = "/page_?0?{$pageNum}[a-z]*\.pdf/i";
                    if (preg_match($pattern, $file)) {
                        $pageFiles[] = $file;
                    }
                }
            }
        }
        
        if (!empty($pageFiles)) {
            // Sort files to put main page first (without letter), then alphabetically
            usort($pageFiles, function($a, $b) use ($pageNum) {
                $aBase = preg_match("/page_?{$pageNum}\.pdf/i", $a);
                $bBase = preg_match("/page_?{$pageNum}\.pdf/i", $b);
                
                if ($aBase && !$bBase) return -1;
                if (!$aBase && $bBase) return 1;
                return strcasecmp($a, $b);
            });
            
            return [
                'type' => 'direct_page',
                'files' => $pageFiles,
                'description' => "Page {$pageNum}" . ($pageLetter ? $pageLetter : '') . " content",
                'search_term' => $searchTerm
            ];
        }
    }
    
    // Product number search - look for patterns like 5666M, 1234L, etc.
    if (preg_match('/\b(\d{3,5}[a-z]{1,3})\b/i', $searchTerm, $matches)) {
        $productCode = strtoupper($matches[1]);
        
        // First try database lookup
        $databaseResults = searchProductDatabase($productCode);
        if (!empty($databaseResults)) {
            $dbFiles = [];
            foreach ($databaseResults as $resultType => $matches) {
                foreach ($matches as $product) {
                    if ($product['pdf_file'] && !in_array($product['pdf_file'], $dbFiles)) {
                        $dbFiles[] = $product['pdf_file'];
                    }
                }
            }
            
            if (!empty($dbFiles)) {
                return [
                    'type' => 'direct_page',
                    'files' => array_unique($dbFiles),
                    'description' => "Product {$productCode}",
                    'search_term' => $searchTerm
                ];
            }
        }
        
        // Fallback to hardcoded mappings if database doesn't have the product
        $productMappings = [
            // Wedding bands typically start with certain numbers
            '5666M' => ['page_03a.pdf', 'page_03b.pdf'],
            '5000' => ['page_01a.pdf', 'page_01b.pdf'],
            '6000' => ['page_06a.pdf', 'page_06b.pdf'],
            '7000' => ['page_07a.pdf', 'page_07b.pdf'],
            '8000' => ['page_08a.pdf', 'page_08b.pdf'],
            '9000' => ['page_09a.pdf', 'page_09b.pdf'],
            '1000' => ['page_10a.pdf', 'page_10b.pdf'],
        ];
        
        // Check for exact product match
        if (isset($productMappings[$productCode])) {
            return [
                'type' => 'direct_page',
                'files' => $productMappings[$productCode],
                'description' => "Product {$productCode}",
                'search_term' => $searchTerm
            ];
        }
        
        // Check for product series patterns (first 1-2 digits)
        $series = substr($productCode, 0, 2);
        $seriesPages = [
            '50' => ['page_03a.pdf', 'page_03b.pdf'], // 5000 series wedding
            '51' => ['page_03c.pdf', 'page_03d.pdf'],
            '52' => ['page_03e.pdf', 'page_03ee.pdf', 'page_03eee.pdf', 'page_03g.pdf'],
            '60' => ['page_06a.pdf', 'page_06b.pdf'], // 6000 series mens
            '61' => ['page_06c.pdf', 'page_06d.pdf'],
            '70' => ['page_07a.pdf', 'page_07b.pdf'], // 7000 series
            '80' => ['page_08a.pdf', 'page_08b.pdf'], // 8000 series ladies
            '90' => ['page_09a.pdf', 'page_09b.pdf'], // 9000 series
            '10' => ['page_10a.pdf', 'page_10b.pdf'], // 1000 series
        ];
        
        if (isset($seriesPages[$series])) {
            return [
                'type' => 'direct_page',
                'files' => $seriesPages[$series],
                'description' => "Product series {$series}xx (possible location for {$productCode})",
                'search_term' => $searchTerm
            ];
        }
    }
    
    // Keyword search through categories (case-insensitive)
    foreach ($searchIndex as $category => $data) {
        foreach ($data['keywords'] as $keyword) {
            if (stripos($searchTerm, $keyword) !== false) {  // stripos for case-insensitive search
                $results[] = [
                    'category' => $category,
                    'relevance' => (strlen($keyword) / strlen($searchTerm)) * 100,
                    'data' => $data
                ];
            }
        }
        
        // Also check category name (case-insensitive)
        if (stripos($searchTerm, $category) !== false) {
            $results[] = [
                'category' => $category,
                'relevance' => 90,
                'data' => $data
            ];
        }
    }
    
    // Sort by relevance
    usort($results, function($a, $b) {
        return $b['relevance'] - $a['relevance'];
    });
    
    return $results;
}