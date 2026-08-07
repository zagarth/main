<?php
/**
 * Catalog Search Class
 * Reusable search functionality for product catalog across multiple pages
 */

class CatalogSearch {
    private $pdo;
    private $searchIndex;
    
    public function __construct() {
        $this->pdo = $this->getDatabaseConnection();
        $this->searchIndex = $this->initializeSearchIndex();
    }
    
    /**
     * Get database connection for catalog searches
     */
    private function getDatabaseConnection() {
        try {
            require_once __DIR__ . '/../includes/db_config_readonly.php';
            return getReadOnlyDBConnection();
        } catch (PDOException $e) {
            error_log('Catalog database connection failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Initialize search index for catalog sections from database
     */
    private function initializeSearchIndex() {
        try {
            $stmt = $this->pdo->query("
                SELECT category, section_name, index_file, keywords 
                FROM index_pages 
                WHERE active = 1
                ORDER BY sort_order, category, section_name
            ");
            $indexData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $searchIndex = [];
            
            foreach ($indexData as $row) {
                $category = $row['category'];
                
                // Initialize category if not exists
                if (!isset($searchIndex[$category])) {
                    $searchIndex[$category] = [
                        'keywords' => [],
                        'indexes' => [],
                        'content_pages' => [], // Will be populated from product database
                        'description' => ucfirst($category) . ' jewelry and accessories'
                    ];
                }
                
                // Parse keywords (stored as comma-separated)
                $keywords = explode(',', $row['keywords']);
                $keywords = array_map('trim', $keywords);
                $searchIndex[$category]['keywords'] = array_unique(array_merge(
                    $searchIndex[$category]['keywords'], 
                    $keywords
                ));
                
                // Add index file
                $searchIndex[$category]['indexes'][] = $row['index_file'];
            }
            
            return $searchIndex;
            
        } catch (PDOException $e) {
            // Fallback to minimal search index if database fails
            error_log("Database error in initializeSearchIndex: " . $e->getMessage());
            return [
                'rings' => [
                    'keywords' => ['ring', 'rings', 'wedding', 'engagement'],
                    'indexes' => [],
                    'content_pages' => [],
                    'description' => 'Rings and wedding jewelry'
                ]
            ];
        }
    }
    
    /**
     * Main search function that handles all types of searches
     */
    public function search($searchTerm) {
        if (empty($searchTerm)) {
            return ['error' => 'Search term cannot be empty'];
        }
        
        // Sanitize input
        $searchTerm = trim($searchTerm);
        
        // Length limit
        if (strlen($searchTerm) > 50) {
            $searchTerm = substr($searchTerm, 0, 50);
        }
        
        // Character whitelist: alphanumeric, basic symbols, spaces
        $searchTerm = preg_replace('/[^a-zA-Z0-9\s\-_\.#]/', '', $searchTerm);
        
        // Final cleanup
        $searchTerm = trim($searchTerm);
        
        if (empty($searchTerm)) {
            return ['error' => 'Invalid search term'];
        }
        
        // Try database product search first
        $databaseResults = $this->searchProductDatabase($searchTerm);
        
        // Try intelligent catalog search
        $catalogResults = $this->searchCatalogIntelligent($searchTerm);
        
        return [
            'search_term' => $searchTerm,
            'database_details' => $databaseResults,
            'catalog_details' => $catalogResults,
            'has_results' => !empty($databaseResults) || !empty($catalogResults)
        ];
    }
    
    /**
     * Search database for product by product ID
     */
    public function searchProductDatabase($searchTerm) {
        if (!$this->pdo) return [];
        
        $results = [];
        
        // Clean the search term - remove common prefixes/suffixes and normalize case
        $cleanTerm = strtoupper(trim($searchTerm));
        $cleanTerm = preg_replace('/^(PRODUCT|ITEM|#|NO\.?)/i', '', $cleanTerm);
        $cleanTerm = trim($cleanTerm);
        
        try {
            // Check for exact page reference match first (highest priority)
            // Handles page format: 1-50 followed by A-EEE (like 23A, 12r, 3eee)
            if (preg_match('/^([1-4]?\d|50)[A-Za-z]{1,3}$/i', $cleanTerm)) {
                $stmt = $this->pdo->prepare(
                    "SELECT DISTINCT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(page_reference) = UPPER(?) 
                     AND pdf_file IS NOT NULL
                     LIMIT 1"
                );
                $stmt->execute([$cleanTerm]);
                $pageMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($pageMatches)) {
                    // Return direct page navigation result
                    return [
                        'page_reference_exact' => $pageMatches,
                        'direct_page_navigation' => true
                    ];
                }
            }

            // Search for exact product ID match (prioritize those with PDF files)
            $stmt = $this->pdo->prepare(
                "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                        product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                        series, white_gold_available, base_price, has_images, image_files
                 FROM catalog_products 
                 WHERE UPPER(product_id) = UPPER(?) 
                 ORDER BY (pdf_file IS NOT NULL) DESC"
            );
            $stmt->execute([$cleanTerm]);
            $exactMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($exactMatches)) {
                $results['exact'] = $exactMatches;
                
                // Search for variants of the exact match (like 10K versions)
                $this->searchVariants($cleanTerm, $results);
                
                // If we found an exact match with a PDF file, don't do additional searches
                $hasExactMatchWithPdf = false;
                foreach ($exactMatches as $match) {
                    if (!empty($match['pdf_file'])) {
                        $hasExactMatchWithPdf = true;
                        break;
                    }
                }
                // Return early if we have a good exact match
                if ($hasExactMatchWithPdf) {
                    return $results;
                }
            }
            
            // Search for specific regex patterns (high priority)
            $this->searchRegexPatterns($cleanTerm, $results);
            
            // Search for suffix patterns (like "MAS", "HM", etc.)
            if (strlen($cleanTerm) >= 2 && preg_match('/^[A-Z]+$/i', $cleanTerm)) {
                // Look for products that end with the search term (suffix matching)
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price
                     FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?)
                     AND pdf_file IS NOT NULL
                     ORDER BY LENGTH(product_id), product_id
                     LIMIT 15"
                );
                $stmt->execute(['%' . $cleanTerm, $cleanTerm]);
                $suffixMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($suffixMatches)) {
                    $results['suffix'] = $suffixMatches;
                }
            }
            
            // Search for partial product ID matches with stricter logic
            if (strlen($cleanTerm) >= 3) {
                // Look for products that start with the search term (most relevant)
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?)
                     AND pdf_file IS NOT NULL
                     ORDER BY LENGTH(product_id), product_id
                     LIMIT 5"
                );
                $stmt->execute([$cleanTerm . '%', $cleanTerm]);
                $startsWithMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($startsWithMatches)) {
                    $results['starts_with'] = $startsWithMatches;
                    // If we found good starts-with matches with PDF files, don't show confusing partial results
                    $hasGoodStartsWithMatch = false;
                    foreach ($startsWithMatches as $match) {
                        if (!empty($match['pdf_file'])) {
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
                    $stmt = $this->pdo->prepare(
                        "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                                product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                                series, white_gold_available, base_price, has_images, image_files
                         FROM catalog_products 
                         WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?) 
                         AND UPPER(product_id) NOT LIKE UPPER(?)
                         ORDER BY (pdf_file IS NOT NULL) DESC, product_id
                         LIMIT 15"
                    );
                    $stmt->execute([$prefix . '%', $cleanTerm, $cleanTerm . '%']); // Exclude exact match and starts-with matches
                    $partialMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($partialMatches)) {
                        $results['partial'] = $partialMatches;
                    }
                }
            } elseif (strlen($cleanTerm) <= 3 && preg_match('/^\d+/', $cleanTerm, $matches)) {
                // Only for very short terms like "4", "10", "700", use the old logic
                $numberPart = $matches[0];
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?)
                     ORDER BY (pdf_file IS NOT NULL) DESC, product_id
                     LIMIT 15"
                );
                $stmt->execute([$numberPart . '%', $cleanTerm]);
                $partialMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($partialMatches)) {
                    $results['partial'] = $partialMatches;
                }
            }
            
            // Search by product name (prioritized over category search)
            if (strlen($cleanTerm) >= 3) {
                // Exact product name search first
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(product_name) = UPPER(?) AND pdf_file IS NOT NULL
                     ORDER BY product_id
                     LIMIT 5"
                );
                $stmt->execute([$cleanTerm]);
                $exactNameMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($exactNameMatches)) {
                    $results['product_name_exact'] = $exactNameMatches;
                } else {
                    // Partial product name search (fuzzy matching)
                    $stmt = $this->pdo->prepare(
                        "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                                product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                                series, white_gold_available, base_price, has_images, image_files
                         FROM catalog_products 
                         WHERE UPPER(product_name) LIKE UPPER(?) AND pdf_file IS NOT NULL
                         ORDER BY 
                             CASE 
                                 WHEN UPPER(product_name) LIKE UPPER(?) THEN 1  -- starts with
                                 WHEN UPPER(product_name) LIKE UPPER(?) THEN 2  -- contains
                                 ELSE 3
                             END,
                             product_id
                         LIMIT 8"
                    );
                    $stmt->execute(['%' . $cleanTerm . '%', $cleanTerm . '%', '%' . $cleanTerm . '%']);
                    $partialNameMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($partialNameMatches)) {
                        $results['product_name_partial'] = $partialNameMatches;
                    }
                }
            }

            // Search by category if search term matches category (case-insensitive)
            if (strlen($cleanTerm) >= 4 && empty($results['product_name_exact']) && empty($results['product_name_partial'])) { 
                // Only do category search if no product name matches found
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(category) LIKE UPPER(?) AND pdf_file IS NOT NULL
                     ORDER BY product_id
                     LIMIT 10"
                );
                $stmt->execute(['%' . $cleanTerm . '%']);
                $categoryMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
     * Search for products within a specific category
     * Used by collection pages for filtered searches
     */
    public function searchByCategory($searchTerm, $category = null) {
        if (!$this->pdo) return [];
        
        $results = [];
        
        // Clean the search term
        $cleanTerm = strtoupper(trim($searchTerm));
        $cleanTerm = preg_replace('/^(PRODUCT|ITEM|#|NO\.?)/i', '', $cleanTerm);
        $cleanTerm = trim($cleanTerm);
        
        try {
            // Base query with image fields included
            $baseSelect = "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                          product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                          series, white_gold_available, base_price, has_images, image_files";
            
            // Build WHERE clause for category filter
            $categoryClause = "";
            $categoryParam = [];
            if ($category) {
                $categoryClause = "AND UPPER(category) = UPPER(?) ";
                $categoryParam = [$category];
            }
            
            // 1. Exact product ID match
            $stmt = $this->pdo->prepare(
                "$baseSelect FROM catalog_products 
                 WHERE UPPER(product_id) = UPPER(?) $categoryClause
                 LIMIT 1"
            );
            $stmt->execute(array_merge([$cleanTerm], $categoryParam));
            $exactMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($exactMatches)) {
                $results['exact'] = $exactMatches;
                // Return immediately for exact matches
                return $this->flattenSearchResults($results);
            }
            
            // 2. Product ID starts with search term
            if (strlen($cleanTerm) >= 2) {
                $stmt = $this->pdo->prepare(
                    "$baseSelect FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) $categoryClause
                     ORDER BY LENGTH(product_id), product_id
                     LIMIT 10"
                );
                $stmt->execute(array_merge([$cleanTerm . '%'], $categoryParam));
                $startsWithMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($startsWithMatches)) {
                    $results['starts_with'] = $startsWithMatches;
                }
            }
            
            // 3. Pattern name search (for "Celtic Coils", etc.)
            if (strlen($cleanTerm) >= 4) {
                $stmt = $this->pdo->prepare(
                    "$baseSelect FROM catalog_products 
                     WHERE (UPPER(pattern) LIKE UPPER(?) OR UPPER(product_name) LIKE UPPER(?)) $categoryClause
                     ORDER BY product_id
                     LIMIT 10"
                );
                $searchPattern = '%' . $cleanTerm . '%';
                $stmt->execute(array_merge([$searchPattern, $searchPattern], $categoryParam));
                $patternMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($patternMatches)) {
                    $results['pattern'] = $patternMatches;
                }
            }
            
            // 4. Partial product ID match (contains)
            if (strlen($cleanTerm) >= 3 && empty($results['starts_with'])) {
                $stmt = $this->pdo->prepare(
                    "$baseSelect FROM catalog_products 
                     WHERE UPPER(product_id) LIKE UPPER(?) AND UPPER(product_id) != UPPER(?) $categoryClause
                     ORDER BY LENGTH(product_id), product_id
                     LIMIT 8"
                );
                $stmt->execute(array_merge(['%' . $cleanTerm . '%', $cleanTerm], $categoryParam));
                $partialMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($partialMatches)) {
                    $results['partial'] = $partialMatches;
                }
            }
            
        } catch (PDOException $e) {
            error_log('Category search failed: ' . $e->getMessage());
        }
        
        return $this->flattenSearchResults($results);
    }
    
    /**
     * Flatten search results into a single array for easier processing
     */
    private function flattenSearchResults($results) {
        $flattened = [];
        $seenIds = [];
        
        // Priority order: exact, starts_with, pattern, partial
        $priorityOrder = ['exact', 'starts_with', 'pattern', 'partial'];
        
        foreach ($priorityOrder as $type) {
            if (isset($results[$type])) {
                foreach ($results[$type] as $result) {
                    if (!in_array($result['product_id'], $seenIds)) {
                        $flattened[] = $result;
                        $seenIds[] = $result['product_id'];
                    }
                }
            }
        }
        
        return $flattened;
    }
    
    /**
     * Search for specific regex patterns in product IDs
     */
    private function searchRegexPatterns($searchTerm, &$results) {
        if (!$this->pdo) return;
        
        $patterns = [];
        
        // #AAA pattern (hash + 3 letters)
        if (preg_match('/^#[A-Za-z]{3}$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^#[A-Z]{3}$'";
        }
        
        // AA## pattern (2 letters + 2 numbers)
        if (preg_match('/^[A-Za-z]{2}[0-9]{2}$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^[A-Z]{2}[0-9]{2}$'";
        }
        
        // AA##A pattern (2 letters + 2 numbers + 1 letter)
        if (preg_match('/^[A-Za-z]{2}[0-9]{2}[A-Za-z]$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^[A-Z]{2}[0-9]{2}[A-Z]$'";
        }
        
        // AA##AA pattern (2 letters + 2 numbers + 2 letters)
        if (preg_match('/^[A-Za-z]{2}[0-9]{2}[A-Za-z]{2}$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^[A-Z]{2}[0-9]{2}[A-Z]{2}$'";
        }
        
        // Mixed alphanumeric patterns (like "2bmc", "13EMC", etc.) - Enhanced patterns
        if (preg_match('/^[0-9]+[A-Za-z]+$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^[0-9]+[A-Z]+$'";
        }
        
        // General alphanumeric patterns (letters + numbers + optional letters)
        if (preg_match('/^[A-Za-z]+[0-9]+[A-Za-z]*$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) REGEXP '^[A-Z]+[0-9]+[A-Z]*$'";
        }
        
        // Product ID with dash variants (like "P120-10K" when searching "P120")
        if (preg_match('/^[A-Za-z0-9]+$/i', $searchTerm)) {
            $patterns[] = "UPPER(product_id) LIKE UPPER('" . $searchTerm . "-%')";
        }
        
        // Execute pattern searches
        foreach ($patterns as $pattern) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE {$pattern} AND UPPER(product_id) = UPPER(?)
                     AND pdf_file IS NOT NULL
                     ORDER BY product_id
                     LIMIT 10"
                );
                $stmt->execute([$searchTerm]);
                $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($matches)) {
                    if (!isset($results['regex_patterns'])) {
                        $results['regex_patterns'] = [];
                    }
                    $results['regex_patterns'] = array_merge($results['regex_patterns'], $matches);
                }
            } catch (PDOException $e) {
                error_log("Regex pattern search error: " . $e->getMessage());
            }
        }
    }

    /**
     * Search for variants of a base product (like 10K versions)
     */
    private function searchVariants($searchTerm, &$results) {
        if (!$this->pdo) return;
        
        try {
            // Look for common variants: -10K, -14K, -18K, etc.
            $variantPatterns = [
                $searchTerm . '-10K',
                $searchTerm . '-14K', 
                $searchTerm . '-18K',
                $searchTerm . '10K'  // For cases like DT98SM10K
            ];
            
            $foundVariants = [];
            
            foreach ($variantPatterns as $variantPattern) {
                $stmt = $this->pdo->prepare(
                    "SELECT product_id, pdf_file, page_reference, category, pattern, style, special_notes,
                            product_name, subcategory, width_mm, profile, diamond_count, gender_variant, 
                            series, white_gold_available, base_price, has_images, image_files
                     FROM catalog_products 
                     WHERE UPPER(style) LIKE UPPER(?)
                     AND pdf_file IS NOT NULL
                     ORDER BY product_id"
                );
                $stmt->execute([$variantPattern]);
                $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($matches)) {
                    $foundVariants = array_merge($foundVariants, $matches);
                }
            }
            
            if (!empty($foundVariants)) {
                $results['variants'] = $foundVariants;
            }
            
        } catch (PDOException $e) {
            error_log("Variant search error: " . $e->getMessage());
        }
    }
    
    /**
     * Search catalog using intelligent keyword matching with database integration
     * Returns format compatible with original catalog_direct.php JavaScript
     */
    public function searchCatalogIntelligent($searchTerm) {
        $searchTerm = strtolower(trim($searchTerm));
        
        // First check for direct page number search (higher priority than product search)
        // Updated regex to NOT match letters before numbers (avoid matching F25 as page 25)
        if (preg_match('/^(?:page[\s_]*)?(\d+)([a-z]{1,3})?$/i', $searchTerm, $matches)) {
            $pageNum = $matches[1];
            $pageLetter = isset($matches[2]) ? strtolower($matches[2]) : '';
            
            // Validate page number range (1-50)
            if ($pageNum < 1 || $pageNum > 50) {
                return [];
            }
            
            // Escape for regex patterns
            $pageNumEscaped = preg_quote($pageNum, '/');
            $pageLetterEscaped = preg_quote($pageLetter, '/');
            
            $catalogDir = './Cadman_catalog/';
            $files = scandir($catalogDir);
            $pageFiles = [];
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                    if ($pageLetter) {
                        $pattern = "/page_?0?{$pageNumEscaped}[_]?{$pageLetterEscaped}\.pdf/i";
                        if (preg_match($pattern, $file)) {
                            $pageFiles[] = $file;
                        }
                    } else {
                        $pattern = "/page_?0?{$pageNumEscaped}[a-z]*\.pdf/i";
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
        
        // Check if this looks like a product ID (numbers + letters) - but only for longer terms
        if (preg_match('/^\d+[a-z]+$/i', $searchTerm) && strlen($searchTerm) >= 3) {
            $productCode = strtoupper($searchTerm);
            
            // Try database lookup for exact match
            $databaseResults = $this->searchProductDatabase($productCode);
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
        
        // Try database search for product numbers
        $databaseResults = $this->searchProductDatabase($searchTerm);
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
                    case 'starts_with':
                        $descriptions[] = "Products starting with: " . implode(', ', array_slice(array_column($matches, 'product_id'), 0, 5)) . (count($matches) > 5 ? ' (+' . (count($matches) - 5) . ' more)' : '');
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
        
        // Keyword-based section search - return as structured object
        foreach ($this->searchIndex as $section => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (strpos($searchTerm, $keyword) !== false) {
                    // Return structured format that includes index files separately
                    return [
                        'type' => 'keyword_match',
                        'section' => $section,
                        'files' => array_merge($data['indexes'], $data['content_pages']),
                        'indexes' => $data['indexes'],
                        'content_pages' => $data['content_pages'],
                        'description' => $data['description'],
                        'search_term' => $searchTerm,
                        'matched_keyword' => $keyword
                    ];
                }
            }
        }
        
        // Return empty array if no results found
        return [];
    }
    
    /**
     * Generate HTML for search results display
     */
    public function generateSearchResultsHTML($searchResults, $options = []) {
        $content = '';
        $hasExactMatch = false;
        
        if (empty($searchResults) || !$searchResults['has_results']) {
            return '<p style="color: #dc3545;">No results found for your search.</p>';
        }
        
        $result = $searchResults;
        $searchTerm = $result['search_term'];
        
        // Check for exact matches
        if (isset($result['database_details']['exact']) && !empty($result['database_details']['exact'])) {
            $hasExactMatch = true;
            $content .= '<p style="color: #28a745; font-weight: bold;">✅ Found exact product: ' . $result['database_details']['exact'][0]['product_id'] . '</p>';
        } else {
            $content .= '<p style="color: #dc3545; font-weight: bold;">❌ Exact product "' . strtoupper($searchTerm) . '" not found. Showing similar products:</p>';
        }
        
        // Group products by PDF file and show them together
        if (isset($result['database_details']) && !empty($result['database_details'])) {
            $content .= $this->generateDatabaseResultsHTML($result['database_details'], $hasExactMatch);
        }
        
        // Show catalog section results if no database results
        if (isset($result['catalog_details']) && !empty($result['catalog_details']) && empty($result['database_details'])) {
            $content .= $this->generateCatalogResultsHTML($result['catalog_details']);
        }
        
        return $content;
    }
    
    /**
     * Generate HTML for database search results
     */
    private function generateDatabaseResultsHTML($databaseResults, $hasExactMatch) {
        $content = '';
        
        // First, collect all products and group by PDF file
        $productsByPdf = [];
        
        // Process in priority order: exact first, then others
        $priorityOrder = ['exact', 'starts_with', 'partial', 'category', 'specifications'];
        
        foreach ($priorityOrder as $resultType) {
            if (isset($databaseResults[$resultType])) {
                foreach ($databaseResults[$resultType] as $product) {
                    if ($product['pdf_file']) {
                        if (!isset($productsByPdf[$product['pdf_file']])) {
                            $productsByPdf[$product['pdf_file']] = [
                                'exact' => [],
                                'starts_with' => [],
                                'partial' => [],
                                'category' => [],
                                'specifications' => []
                            ];
                        }
                        $productsByPdf[$product['pdf_file']][$resultType][] = $product;
                    }
                }
            }
        }
        
        $content .= '<div class="search-results">';
        if ($hasExactMatch) {
            $content .= '<h3>📄 Catalog Pages with Your Products</h3>';
        } else {
            $content .= '<h3>📄 Catalog Pages with Similar Products</h3>';
        }
        
        // Show each PDF with its products, prioritizing those with exact matches
        $sortedPdfs = array_keys($productsByPdf);
        usort($sortedPdfs, function($a, $b) use ($productsByPdf) {
            // PDFs with exact matches come first
            $aExact = count($productsByPdf[$a]['exact']);
            $bExact = count($productsByPdf[$b]['exact']);
            if ($aExact > 0 && $bExact === 0) return -1;
            if ($aExact === 0 && $bExact > 0) return 1;
            return 0;
        });
        
        foreach ($sortedPdfs as $pdfFile) {
            $productGroups = $productsByPdf[$pdfFile];
            $content .= $this->generatePdfGroupHTML($pdfFile, $productGroups, $hasExactMatch, $priorityOrder);
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Generate HTML for a single PDF group
     */
    private function generatePdfGroupHTML($pdfFile, $productGroups, $hasExactMatch, $priorityOrder) {
        $pageLabel = str_replace('.pdf', '', $pdfFile);
        $pageLabel = str_replace('page_', 'Page ', $pageLabel);
        $pageLabel = strtoupper($pageLabel);
        $sectionKey = $this->getSectionKeyForFile($pdfFile);
        
        // Highlight exact match pages differently
        $borderColor = count($productGroups['exact']) > 0 ? '#28a745' : '#ddd';
        $bgColor = count($productGroups['exact']) > 0 ? '#f8fff9' : '#f8f9fa';
        
        $content = '<div style="background: ' . $bgColor . '; border: 2px solid ' . $borderColor . '; border-radius: 12px; margin: 20px 0; padding: 20px;">';
        $content .= '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
        $content .= '<a href="?section=' . $sectionKey . '" style="text-decoration: none; color: #333;">';
        
        if (count($productGroups['exact']) > 0) {
            $content .= '<h3 style="margin: 0; color: #28a745;">🎯 ' . $pageLabel . ' - Your Exact Product Here!</h3>';
        } else {
            $content .= '<h3 style="margin: 0; color: #2c5aa0;">📄 ' . $pageLabel . ' - Similar Products</h3>';
        }
        
        $content .= '</a>';
        $content .= '</div>';
        
        // Show products in priority order
        foreach ($priorityOrder as $matchType) {
            $products = $productGroups[$matchType] ?? [];
            if (!empty($products)) {
                $typeLabel = $this->getMatchTypeLabel($matchType);
                $content .= $this->generateProductGroupHTML($products, $matchType, $typeLabel, $hasExactMatch);
            }
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Generate HTML for a group of products
     */
    private function generateProductGroupHTML($products, $matchType, $typeLabel, $hasExactMatch) {
        $content = '<div style="margin-bottom: 15px;">';
        $content .= '<div style="font-weight: bold; color: #555; margin-bottom: 8px;">' . $typeLabel . ':</div>';
        $content .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 8px;">';
        
        // Show fewer similar products if we have exact matches
        $maxShow = ($matchType === 'exact') ? 5 : ($hasExactMatch ? 6 : 12);
        
        foreach (array_slice($products, 0, $maxShow) as $product) {
            $bgClass = $matchType === 'exact' ? 'white; border: 2px solid #28a745' : 'white; border: 1px solid #ccc';
            $content .= '<div style="background: ' . $bgClass . '; border-radius: 6px; padding: 10px;">';
            $content .= '<div style="font-weight: bold; color: #FFD700; font-size: 0.95em;">' . htmlspecialchars($product['product_id']) . '</div>';
            $content .= '<div style="color: #666; font-size: 0.8em;">Category: ' . htmlspecialchars($product['category']) . '</div>';
            if (!empty(trim($product['style']))) {
                $content .= '<div style="color: #666; font-size: 0.8em;">Style: ' . htmlspecialchars($product['style']) . '</div>';
            }
            $content .= '</div>';
        }
        
        $content .= '</div>';
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Generate HTML for catalog section results
     */
    private function generateCatalogResultsHTML($catalogResults) {
        $content = '<div class="catalog-section-results">';
        $content .= '<h3>📚 Catalog Section: ' . htmlspecialchars($catalogResults['description']) . '</h3>';
        
        if (!empty($catalogResults['files'])) {
            $content .= '<div class="section-files">';
            foreach ($catalogResults['files'] as $file) {
                $pageLabel = str_replace('.pdf', '', $file);
                $pageLabel = str_replace(['page_', 'index_page_'], ['Page ', 'Index: '], $pageLabel);
                $content .= '<div class="file-item" style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">';
                $content .= '<strong>' . htmlspecialchars($pageLabel) . '</strong>';
                $content .= '</div>';
            }
            $content .= '</div>';
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Get match type label for display
     */
    private function getMatchTypeLabel($matchType) {
        $labels = [
            'exact' => '🎯 Exact Match',
            'starts_with' => '🔤 Name Match',
            'partial' => '🔍 Similar Products',
            'category' => '📂 Same Category',
            'specifications' => '📋 Specification Match'
        ];
        
        return $labels[$matchType] ?? '📄 Related Products';
    }
    
    /**
     * Get section key for PDF file
     */
    private function getSectionKeyForFile($pdfFile) {
        // This would need to be implemented based on your specific section mapping logic
        // For now, return a generic section
        if (strpos($pdfFile, 'page_') === 0) {
            $pageNum = preg_replace('/page_(\d+)[a-z]?\.pdf/i', '$1', $pdfFile);
            return 'page_' . $pageNum;
        }
        
        return 'general';
    }
    
    /**
     * Handle AJAX search requests
     */
    public function handleAjaxRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
            // Accept both 'term' (from catalog_direct.php) and 'search_term' (for other uses)
            $searchTerm = $_POST['term'] ?? $_POST['search_term'] ?? '';
            
            if (empty($searchTerm)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Search term is required']);
                exit;
            }
            
            // Use the full search functionality (database + catalog)
            $searchResults = $this->search($searchTerm);
            
            // Check for errors from sanitization
            if (isset($searchResults['error'])) {
                header('Content-Type: application/json');
                echo json_encode($searchResults);
                exit;
            }
            
            // Check for database results first (prioritized)
            if (!empty($searchResults['database_details'])) {
                $databaseResults = $searchResults['database_details'];
                
                // Check for direct page navigation (page reference exact match)
                if (isset($databaseResults['direct_page_navigation']) && $databaseResults['direct_page_navigation'] === true) {
                    if (isset($databaseResults['page_reference_exact']) && !empty($databaseResults['page_reference_exact'])) {
                        $pageMatch = $databaseResults['page_reference_exact'][0];
                        $result = [
                            'type' => 'direct_page',
                            'files' => [$pageMatch['pdf_file']],
                            'page_reference' => $pageMatch['page_reference'],
                            'description' => 'Direct page navigation to ' . $pageMatch['page_reference'],
                            'search_term' => $searchTerm
                        ];
                        
                        header('Content-Type: application/json');
                        echo json_encode($result);
                        exit;
                    }
                }
                
                // Convert database results to format expected by catalog_direct.php JavaScript
                $files = [];
                $matchTypes = ['exact', 'starts_with', 'partial', 'product_name_exact', 'product_name_partial', 'category'];
                
                foreach ($matchTypes as $matchType) {
                    if (isset($databaseResults[$matchType])) {
                        foreach ($databaseResults[$matchType] as $product) {
                            if (!empty($product['pdf_file']) && !in_array($product['pdf_file'], $files)) {
                                $files[] = $product['pdf_file'];
                            }
                        }
                    }
                }
                
                if (!empty($files)) {
                    $result = [
                        'type' => 'database_match',
                        'files' => $files,
                        'description' => '',
                        'search_term' => $searchTerm,
                        'database_details' => $databaseResults,
                        'catalog_details' => $searchResults['catalog_details'] // Include catalog results too
                    ];
                    
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit;
                }
            }
            
            // Fall back to catalog intelligent search if no database results
            $catalogResults = $searchResults['catalog_details'];
            
            header('Content-Type: application/json');
            echo json_encode($catalogResults);
            exit;
        }
    }
}