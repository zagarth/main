<?php
// Universal Detail Template for All Collections
// This template handles bands, family, accessories, corp, engagement, and other collections

session_start();
require_once __DIR__ . '/includes/InputValidator.php';

include __DIR__ . '/navigation.php';
include __DIR__ . '/topButton.php'; 
include __DIR__ . '/image_loader_v2.php';

// Validate and sanitize input parameters
$collection = InputValidator::validateCollection($_GET['collection'] ?? '');
$itemId = InputValidator::validateProductId($_GET['id'] ?? '');
$specifiedCategory = InputValidator::validateCategory($_GET['category'] ?? '');

// Redirect if invalid parameters
if ($collection === false || $itemId === false) {
    header('Location: index.php');
    exit;
}

// Collection configuration mapping
$collectionConfig = [
    'bands' => [
        'path' => 'bands_php',
        'name' => 'Wedding Bands',
        'backLink' => 'Bands.php',
        'categories' => ['celtic', 'cultural', 'fancy', 'plain'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [500, 1800],
        'icon' => '💍'
    ],
    'family' => [
        'path' => 'family_php',
        'name' => 'Family Collection',
        'backLink' => 'Family.php',
        'categories' => ['mother', 'father', 'daughter'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [150, 2800],
        'icon' => '👨‍👩‍👧‍👦'
    ],
    'accessories' => [
        'path' => 'accessories_php',
        'name' => 'Accessories Collection',
        'backLink' => 'Accessories.php',
        'categories' => ['crosses', 'idents', 'earrings'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [185, 535],
        'icon' => '✨'
    ],
    'corp' => [
        'path' => 'corp_php',
        'name' => 'Corporate Collection',
        'backLink' => 'Corp.php',
        'categories' => ['awards', 'executive', 'military', 'specialty', 'standard'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [325, 725],
        'icon' => '🏆'
    ],
    'engagement' => [
        'path' => 'Engagement_php',
        'name' => 'Engagement Collection',
        'backLink' => 'Engagement.php',
        'categories' => ['bridal'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [800, 5000],
        'icon' => '💎'
    ],
    'frontline_workers' => [
        'path' => 'Frontline_Workers_php',
        'name' => 'Frontline Workers Collection',
        'backLink' => 'Essential_Workers.php',
        'categories' => ['firefighter', 'clinical_services'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [200, 800],
        'icon' => '�'
    ],
        'school' => [
        'path' => 'school_php',
        'name' => 'School Collection',
        'backLink' => 'School.php',
        'categories' => ['bands', 'crest_tops', 'shoulders'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [150, 800],
        'icon' => '🎓'
    ],
    'signet' => [
        'path' => 'signet_php',
        'name' => 'Signet Collection',
        'backLink' => 'Signet.php',
        'categories' => ['crest_top', 'jewel_top'],
        'categoryPath' => 'images/{category}',
        'priceRange' => [300, 1500],
        'icon' => '🔖'
    ]
];

// Validate collection exists
if (!isset($collectionConfig[$collection])) {
    header('Location: index.php');
    exit;
}

$config = $collectionConfig[$collection];

// Function to get base name (remove variant suffixes and M/L gender suffixes)
function getUnifiedBaseName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    // Remove various variant patterns
    $name = preg_replace('/_alt\d*$/', '', $name);
    $name = preg_replace('/-alt\d*$/', '', $name);
    $name = preg_replace('/_(view\d*|art\d*)$/', '', $name);
    $name = preg_replace('/-(view\d*|art\d*)$/', '', $name);
    // Remove M/L gender suffixes to match bands grouping
    $name = preg_replace('/[ML]$/', '', $name);
    return $name;
}

// Function to create display name from filename
function createUnifiedDisplayName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[_-]/', ' ', $name);
    $name = preg_replace('/alt\d+/', '', $name);
    $name = trim($name);
    return ucwords($name);
}

// Function to find all variants for an item
function findItemVariants($collection, $config, $itemId, $specifiedCategory = null) {
    $variants = [];
    $foundCategory = null;
    $basePath = __DIR__ . '/' . $config['path'];
    
    // If category is specified, search only in that category
    $categoriesToSearch = $specifiedCategory ? [$specifiedCategory] : $config['categories'];
    
    // Handle different category path patterns
    foreach ($categoriesToSearch as $category) {
        $categoryPath = str_replace('{category}', ucfirst($category), $config['categoryPath']);
        $fullPath = $basePath . '/' . $categoryPath;
        
        // Handle special cases for different collections
        if ($collection === 'accessories') {
            switch ($category) {
                case 'crosses':
                    $fullPath = $basePath . '/images/Crosses_and_Lockets';
                    break;
                case 'idents':
                    $fullPath = $basePath . '/images/Idents';
                    break;
                case 'earrings':
                    $fullPath = $basePath . '/images/Pendant_earrings';
                    break;
            }
        } elseif ($collection === 'corp') {
            // Corp uses organized categories
            switch ($category) {
                case 'awards':
                    $fullPath = $basePath . '/images/awards';
                    break;
                case 'executive':
                    $fullPath = $basePath . '/images/executive';
                    break;
                case 'military':
                    $fullPath = $basePath . '/images/military';
                    break;
                case 'specialty':
                    $fullPath = $basePath . '/images/specialty';
                    break;
                case 'standard':
                default:
                    $fullPath = $basePath . '/images';
                    break;
            }
        } elseif ($collection === 'bands') {
            // Bands uses lowercase category names
            $categoryPath = str_replace('{category}', $category, $config['categoryPath']);
            $fullPath = $basePath . '/' . $categoryPath;
        } elseif ($collection === 'family') {
            // Family uses uppercase category names  
            $categoryPath = str_replace('{category}', ucfirst($category), $config['categoryPath']);
            $fullPath = $basePath . '/' . $categoryPath;
        } elseif ($collection === 'signet') {
            // Signet has special directory names
            if ($category === 'jewel_top') {
                $fullPath = $basePath . '/images/jewel top';
            } else {
                $categoryPath = str_replace('{category}', $category, $config['categoryPath']);
                $fullPath = $basePath . '/' . $categoryPath;
            }
        } elseif ($collection === 'engagement') {
            // Engagement uses organized category structure
            if (in_array($category, ['MK_series', 'MM_series', 'WM_series'])) {
                $fullPath = $basePath . '/images/' . $category;
            } else {
                // Legacy support for old bridal category
                $categoryPath = str_replace('{category}', ucfirst($category), $config['categoryPath']);
                $fullPath = $basePath . '/' . $categoryPath;
            }
        } elseif ($collection === 'frontline_workers') {
            // Frontline workers uses specific directory names
            if ($category === 'firefighter') {
                $fullPath = $basePath . '/images/Firefighter';
            } elseif ($category === 'clinical_services') {
                $fullPath = $basePath . '/images/clinical_services';
            } else {
                $categoryPath = str_replace('{category}', ucfirst($category), $config['categoryPath']);
                $fullPath = $basePath . '/' . $categoryPath;
            }
        } elseif ($collection === 'school') {
            // School uses uppercase category names with underscores
            $categoryName = ucfirst(str_replace('_', '_', $category));
            if ($category === 'crest_tops') {
                $categoryName = 'Crest_tops';
            } elseif ($category === 'bands') {
                $categoryName = 'Bands';
            } elseif ($category === 'shoulders') {
                $categoryName = 'Shoulders';
            }
            $categoryPath = str_replace('{category}', $categoryName, $config['categoryPath']);
            $fullPath = $basePath . '/' . $categoryPath;
        } elseif ($collection === 'school') {
            // School has special directory names
            if ($category === 'bands') {
                $fullPath = $basePath . '/images/Bands';
            } elseif ($category === 'crest_tops') {
                $fullPath = $basePath . '/images/Crest_tops';
            } elseif ($category === 'shoulders') {
                $fullPath = $basePath . '/images/Shoulders';
            } else {
                $categoryPath = str_replace('{category}', ucfirst($category), $config['categoryPath']);
                $fullPath = $basePath . '/' . $categoryPath;
            }
        }
        
        if (is_dir($fullPath)) {
            $files = scandir($fullPath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'tif', 'tiff'])) {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    
                    // Check if this file matches our item ID
                    // First remove variant suffixes (_alt1, _view1, etc.) but keep M/L
                    $cleanFileName = preg_replace('/_alt\d*$/', '', $fileName);
                    $cleanFileName = preg_replace('/-alt\d*$/', '', $cleanFileName);
                    $cleanFileName = preg_replace('/_(view\d*|art\d*)$/', '', $cleanFileName);
                    $cleanFileName = preg_replace('/-(view\d*|art\d*)$/', '', $cleanFileName);
                    
                    // Now check if it matches our itemId exactly, OR if removing M/L makes it match
                    $baseWithoutGender = preg_replace('/[ML]$/', '', $cleanFileName);
                    $itemIdWithoutGender = preg_replace('/[ML]$/', '', $itemId);
                    
                    if ($cleanFileName === $itemId || $baseWithoutGender === $itemIdWithoutGender) {
                        if (!$foundCategory) {
                            $foundCategory = $category;
                        }
                        // Only include variants from the first category found
                        if ($category === $foundCategory) {
                            $relativePath = str_replace(__DIR__ . '/', '', $fullPath);
                            $variants[] = [
                                'file' => $file,
                                'path' => $relativePath . '/' . $file,
                                'thumbPath' => str_replace('/images/', '/thumbs/images/', $relativePath) . '/' . $file,
                                'category' => $category,
                                'isMain' => !preg_match('/_alt\d*|_view\d*|_art\d*|-alt\d*|-view\d*|-art\d*/', $file)
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // Sort variants to have main image first
    usort($variants, function($a, $b) {
        if ($a['isMain'] && !$b['isMain']) return -1;
        if (!$a['isMain'] && $b['isMain']) return 1;
        return strcmp($a['file'], $b['file']);
    });
    
    return ['variants' => $variants, 'category' => $foundCategory];
}

// Collection-specific functions
function getCollectionCategory($collection, $category, $filename, $itemId) {
    switch ($collection) {
        case 'bands':
            return getBandsCategory($category, $itemId);
        case 'family':
            return getFamilyCategory($category, $filename, $itemId);
        case 'accessories':
            return getAccessoriesCategory($category);
        case 'corp':
            return getCorpCategory($filename);
        case 'engagement':
            return getEngagementCategory($category);
        case 'school':
            return getSchoolCategory($category);
        case 'signet':
            return getSignetCategory($category);
        case 'frontline_workers':
            return getFrontlineWorkersCategory($category);
        default:
            return ['type' => 'jewelry', 'description' => 'Premium jewelry piece'];
    }
}

function getBandsCategory($category, $itemId = '') {
    // Remove M/L suffix to get base product ID
    $baseId = preg_replace('/[ML]$/', '', $itemId);
    
    $descriptions = [
        'celtic' => 'Traditional Celtic design with intricate knotwork and heritage patterns.',
        'cultural' => 'Cultural design celebrating diverse traditions and artistic styles.',
        'fancy' => 'Elegant fancy design with sophisticated styling and premium finishes.',
        'plain' => 'Classic plain design featuring timeless simplicity and clean lines.'
    ];
    
    // Add specific thickness information for plain bands based on product ID
    if ($category === 'plain' && !empty($baseId)) {
        $plainDescription = $descriptions['plain'];
        
        // Check for specific series
        if (in_array($baseId, ['3001', '4001', '5001', '6001'])) {
            $plainDescription .= '<br><br><strong>Lightweight Series:</strong> 1.0mm thick profile for enhanced comfort and modern styling.';
        } elseif (preg_match('/\d+R$/', $baseId)) {
            // Rectangular series (ends with R)
            if (preg_match('/^S/', $baseId)) {
                $plainDescription .= '<br><br><strong>Ultra-thin Rectangular:</strong> 1.0mm thick with sharp contemporary profile. Lightweight design up to size 12 only.';
            } else {
                $plainDescription .= '<br><br><strong>Rectangular Profile:</strong> 1.5mm thick with sharp contemporary edges for modern styling.';
            }
        } else {
            // Standard Tiffany series
            $plainDescription .= '<br><br><strong>Classic Tiffany Profile:</strong> 1.5mm thick with traditional rounded comfort fit design.';
        }
        
        $descriptions['plain'] = $plainDescription;
    }
    
    return [
        'type' => 'wedding band',
        'description' => $descriptions[$category] ?? 'Premium wedding band crafted with exceptional quality.',
        'display_name' => ucfirst($category)
    ];
}

function getFamilyCategory($category, $filename, $itemId) {
    // Determine jewelry type from filename/ID
    if (preg_match('/ring|band/i', $filename . $itemId)) {
        $type = 'ring';
    } elseif (preg_match('/pendant|necklace/i', $filename . $itemId)) {
        $type = 'pendant';
    } elseif (preg_match('/earring/i', $filename . $itemId)) {
        $type = 'earrings';
    } elseif (preg_match('/bracelet/i', $filename . $itemId)) {
        $type = 'bracelet';
    } else {
        $type = 'jewelry';
    }
    
    $descriptions = [
        'mother' => 'Elegant jewelry designed to celebrate and honor mothers with timeless beauty.',
        'father' => 'Distinguished pieces combining masculine strength with refined elegance.',
        'daughter' => 'Delicate and charming pieces perfect for daughters with age-appropriate designs.'
    ];
    
    return [
        'type' => $type,
        'description' => $descriptions[$category] ?? 'Beautiful family jewelry piece.',
        'display_name' => ucfirst($category)
    ];
}

function getAccessoriesCategory($category) {
    $descriptions = [
        'crosses' => 'Beautiful cross or locket with traditional and contemporary design elements.',
        'idents' => 'Professional identification piece for business and formal settings.',
        'earrings' => 'Elegant pendant earring with sophisticated styling.'
    ];
    
    $displayNames = [
        'crosses' => 'Crosses & Lockets',
        'idents' => 'Identification Pieces', 
        'earrings' => 'Pendant Earrings'
    ];
    
    return [
        'type' => $category === 'earrings' ? 'earrings' : 'accessory',
        'description' => $descriptions[$category] ?? 'Premium jewelry accessory.',
        'display_name' => $displayNames[$category] ?? ucfirst($category)
    ];
}

function getCorpCategory($filename) {
    $filename = strtolower($filename);
    if (strpos($filename, 'military') !== false || strpos($filename, 'service') !== false) {
        return [
            'type' => 'military award', 
            'description' => 'Military service recognition and honor piece for service members.',
            'display_name' => 'Military Service'
        ];
    } elseif (preg_match('/sa\d+/i', $filename) || strpos($filename, 'executive') !== false) {
        return [
            'type' => 'executive award', 
            'description' => 'Premium executive recognition piece and leadership award.',
            'display_name' => 'Executive Collection'
        ];
    } elseif (strpos($filename, 'cj') !== false || strpos($filename, 'specialty') !== false) {
        return [
            'type' => 'specialty item', 
            'description' => 'Custom specialty corporate item and unique recognition piece.',
            'display_name' => 'Specialty Items'
        ];
    } else {
        return [
            'type' => 'corporate award', 
            'description' => 'Distinguished corporate achievement and recognition award.',
            'display_name' => 'Corporate Awards'
        ];
    }
}

function getEngagementCategory($category) {
    $descriptions = [
        'MK_series' => 'Our flagship engagement ring collection featuring classic and contemporary designs with brilliant diamonds.',
        'MM_series' => 'Premium marquise and specialty cut engagement rings for the discerning bride.',
        'WM_series' => 'Complete bridal sets with matching engagement and wedding bands for perfect harmony.',
        'bridal' => 'Exquisite bridal engagement ring collection featuring stunning designs for your special moment.' // Legacy support
    ];
    
    $displayNames = [
        'MK_series' => 'MK Collection',
        'MM_series' => 'MM Collection', 
        'WM_series' => 'Wedding Sets',
        'bridal' => 'Bridal Collection' // Legacy support
    ];
    
    return [
        'type' => 'engagement ring',
        'description' => $descriptions[$category] ?? 'Beautiful engagement ring designed for your special moment.',
        'display_name' => $displayNames[$category] ?? ucfirst($category)
    ];
}

function getSchoolCategory($category) {
    $descriptions = [
        'bands' => 'School band jewelry celebrating musical achievement and ensemble participation.',
        'crest_tops' => 'School crest jewelry featuring institution emblems and academic pride.',
        'shoulders' => 'School shoulder insignia and ceremonial pieces for academic distinction.'
    ];
    
    $displayNames = [
        'bands' => 'School Bands',
        'crest_tops' => 'Crest Tops',
        'shoulders' => 'Shoulder Insignia'
    ];
    
    return [
        'type' => 'school jewelry',
        'description' => $descriptions[$category] ?? 'School jewelry celebrating academic achievement.',
        'display_name' => $displayNames[$category] ?? ucfirst(str_replace('_', ' ', $category))
    ];
}

function getSignetCategory($category) {
    $descriptions = [
        'crest_top' => 'Classic crest top signet ring with traditional styling and timeless design.',
        'jewel_top' => 'Jewel top signet ring featuring gemstone accents and elegant design.',
        'custom' => 'Custom signet ring designed to your exact specifications.'
    ];
    
    // Map category keys to display names
    $displayNames = [
        'crest_top' => 'Crest Top',
        'jewel_top' => 'Jewel Top',
        'custom' => 'Custom'
    ];
    
    $displayName = $displayNames[$category] ?? ucfirst(str_replace('_', ' ', $category));
    
    return [
        'type' => 'signet ring',
        'description' => $descriptions[$category] ?? 'Premium signet ring with personalized design.',
        'display_name' => $displayName
    ];
}

function getFrontlineWorkersCategory($category) {
    $descriptions = [
        'firefighter' => 'Professional firefighter jewelry honoring those who serve and protect our communities.',
        'clinical_services' => 'Healthcare professional jewelry celebrating clinical workers and medical staff.'
    ];
    
    $displayNames = [
        'firefighter' => 'Firefighter',
        'clinical_services' => 'Clinical Services'
    ];
    
    return [
        'type' => 'professional jewelry',
        'description' => $descriptions[$category] ?? 'Professional jewelry honoring frontline workers.',
        'display_name' => $displayNames[$category] ?? ucfirst(str_replace('_', ' ', $category))
    ];
}

function calculatePrice($collection, $config, $category, $filename) {
    $baseRange = $config['priceRange'];
    $minPrice = $baseRange[0];
    $maxPrice = $baseRange[1];
    
    // Adjust based on special features in filename
    if (preg_match('/gold|TG/i', $filename)) {
        $minPrice += 150;
        $maxPrice += 300;
    }
    if (preg_match('/diamond|TGD/i', $filename)) {
        $minPrice += 200;
        $maxPrice += 500;
    }
    
    // Collection-specific adjustments
    switch ($collection) {
        case 'family':
            $multipliers = ['mother' => 1.15, 'father' => 1.10, 'daughter' => 0.90];
            $mult = $multipliers[$category] ?? 1.0;
            $minPrice = round($minPrice * $mult);
            $maxPrice = round($maxPrice * $mult);
            break;
        case 'corp':
            if (strpos(strtolower($filename), 'executive') !== false) {
                $minPrice += 200;
                $maxPrice += 400;
            }
            break;
    }
    
    return sprintf('$%d - $%s', $minPrice, number_format($maxPrice));
}

// Find variants for this item
$result = findItemVariants($collection, $config, $itemId, $specifiedCategory);
$variants = $result['variants'];
$foundCategory = $result['category'];

// Override config for Celtic items to show "Celtic Bands" instead of "Wedding Bands"
if ($foundCategory === 'celtic') {
    $config['name'] = 'Celtic Bands';
    $config['backLink'] = 'Celtic.php';
} elseif ($foundCategory === 'cultural') {
    $config['name'] = 'Heritage Wedding Bands';
    $config['backLink'] = 'Bands.php';
} elseif ($foundCategory === 'fancy') {
    $config['name'] = 'Designer Wedding Bands';
} elseif ($foundCategory === 'plain') {
    $config['name'] = 'Classic Wedding Bands';
}

if (empty($variants)) {
    header('Location: ' . $config['backLink']);
    exit;
}

// Get item details
$mainVariant = $variants[0];
$displayName = createUnifiedDisplayName($mainVariant['file']);
$categoryInfo = getCollectionCategory($collection, $foundCategory, $mainVariant['file'], $itemId);
$priceRange = calculatePrice($collection, $config, $foundCategory, $mainVariant['file']);

// Check if thumbnail exists, fall back to main image
$mainThumbPath = __DIR__ . '/' . $mainVariant['thumbPath'];
if (!file_exists($mainThumbPath)) {
    $mainVariant['thumbPath'] = $mainVariant['path'];
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="css/configurator.css?v=<?php echo time(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="<?php echo $displayName; ?>, <?php echo $config['name']; ?>, <?php echo $categoryInfo['type']; ?>, Cadman Manufacturing" />
<meta name="description" content="<?php echo htmlspecialchars($displayName); ?> - <?php echo htmlspecialchars($categoryInfo['description']); ?> - Price range: <?php echo $priceRange; ?>" />
<link rel="icon" sizes="" href="favicon.ico">
<title><?php echo htmlspecialchars($displayName); ?> - <?php echo htmlspecialchars($config['name']); ?> - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
<script src="js/configurator.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <?php renderNavigation($collection); ?>
    <?php renderTopButton(); ?>
    
    <div class="item-detail-container">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <a href="<?php echo $config['backLink']; ?>"><?php echo htmlspecialchars($config['name']); ?></a> > 
            <span><?php echo htmlspecialchars($displayName); ?></span>
        </div>
        
        <!-- Main Detail Content -->
        <div class="detail-content centered-images">
            <!-- Image Gallery Section -->
            <div class="image-gallery centered">
                <div class="main-image-container">
                    <img id="mainImage" src="<?php echo $mainVariant['thumbPath']; ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="main-image">
                </div>
                
                <?php if (count($variants) > 1): ?>
                <!-- Thumbnail Gallery - Alternative Views Below -->
                <div class="thumbnail-container">
                    <?php if (count($variants) <= 3): ?>
                        <!-- Simple grid for 3 or fewer thumbnails -->
                        <div class="thumbnail-gallery centered-grid">
                            <?php foreach ($variants as $index => $variant): ?>
                                <?php
                                $thumbPath = __DIR__ . '/' . $variant['thumbPath'];
                                if (!file_exists($thumbPath)) {
                                    $variant['thumbPath'] = $variant['path'];
                                }
                                ?>
                                <img src="<?php echo $variant['thumbPath']; ?>" 
                                     alt="<?php echo htmlspecialchars($displayName); ?> - View <?php echo $index + 1; ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo $variant['thumbPath']; ?>', this)"
                                     data-index="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Carousel for more than 3 thumbnails -->
                        <div class="thumbnail-carousel-container">
                            <div class="thumbnail-carousel" id="thumbnailCarousel">
                                <?php foreach ($variants as $index => $variant): ?>
                                    <?php
                                    $thumbPath = __DIR__ . '/' . $variant['thumbPath'];
                                    if (!file_exists($thumbPath)) {
                                        $variant['thumbPath'] = $variant['path'];
                                    }
                                    ?>
                                    <img src="<?php echo $variant['thumbPath']; ?>" 
                                         alt="<?php echo htmlspecialchars($displayName); ?> - View <?php echo $index + 1; ?>" 
                                         class="thumbnail carousel-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeMainImage('<?php echo $variant['thumbPath']; ?>', this)"
                                         data-index="<?php echo $index; ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="item-info">
                <div class="collection-header">
                    <div class="collection-badge">
                        <span class="collection-icon"><?php echo $config['icon']; ?></span>
                        <?php echo $config['name']; ?>
                    </div>
                </div>
                
                <h1 class="item-title"><?php echo htmlspecialchars($displayName); ?></h1>
                <div class="item-subtitle">Model: <?php echo strtoupper($itemId); ?> | Type: <?php echo ucfirst($categoryInfo['type']); ?> | Category: <?php echo $categoryInfo['display_name'] ?? ucfirst($foundCategory); ?></div>
                
                <div class="price-section">
                    <div class="price-range"><?php echo $priceRange; ?></div>
                    <div class="price-note">*Final price varies by material and customization options</div>
                </div>
                
                <div class="item-description">
                    <h3>About This Piece</h3>
                    <p><?php echo $categoryInfo['description']; ?></p>
                    
                    <?php if ($foundCategory === 'fancy' || $foundCategory === 'designer'): ?>
                    <?php
                    // Load designer band specifications from JSON
                    $designerConfigFile = __DIR__ . '/bands_php/fancy_configurator.json';
                    if (file_exists($designerConfigFile)) {
                        $designerConfig = json_decode(file_get_contents($designerConfigFile), true);
                        $baseProductId = preg_replace('/[ML]$/i', '', $itemId);
                        
                        if (isset($designerConfig['products'][$baseProductId])) {
                            $productInfo = $designerConfig['products'][$baseProductId];
                            echo '<div class="designer-specs">';
                            echo '<p><strong>Band Width:</strong> ' . htmlspecialchars($productInfo['width']) . '</p>';
                            if ($productInfo['diamonds'] !== 'None') {
                                echo '<p><strong>Diamonds:</strong> ' . htmlspecialchars($productInfo['diamonds']) . '</p>';
                            }
                            if (isset($productInfo['note'])) {
                                echo '<p class="product-note"><em>' . htmlspecialchars($productInfo['note']) . '</em></p>';
                            }
                            echo '</div>';
                        }
                    }
                    ?>
                    <?php endif; ?>
                    
                    <?php if ($foundCategory === 'celtic' || $foundCategory === 'cultural'): ?>
                    <?php
                    // Load Celtic/Cultural band specifications from XML
                    $xmlFile = __DIR__ . '/' . ($foundCategory === 'celtic' ? 'celtic_bands_mapping.xml' : 'cultural_bands_mapping.xml');
                    if (file_exists($xmlFile)) {
                        $xml = simplexml_load_file($xmlFile);
                        $baseProductId = preg_replace('/[ML]$/i', '', $itemId);
                        
                        // Search for the pattern containing this product ID
                        foreach ($xml->pattern as $pattern) {
                            foreach ($pattern->band as $band) {
                                if ((string)$band->product_id === $baseProductId) {
                                    echo '<div class="celtic-specs">';
                                    echo '<p><strong>Pattern:</strong> ' . htmlspecialchars((string)$pattern['name']) . '</p>';
                                    echo '<p><strong>Width:</strong> ' . htmlspecialchars((string)$band['width']) . '</p>';
                                    echo '<p><strong>Heritage:</strong> ' . htmlspecialchars((string)$pattern->heritage) . '</p>';
                                    if (!empty((string)$pattern->description)) {
                                        echo '<p><strong>Design:</strong> ' . htmlspecialchars((string)$pattern->description) . '</p>';
                                    }
                                    if (!empty((string)$pattern->cultural_significance)) {
                                        echo '<p class="cultural-note"><em>' . htmlspecialchars((string)$pattern->cultural_significance) . '</em></p>';
                                    }
                                    echo '</div>';
                                    break 2; // Exit both foreach loops
                                }
                            }
                        }
                    }
                    ?>
                    <?php endif; ?>
                    
                    <?php if (count($variants) > 1): ?>
                    <p><strong>Multiple Views:</strong> Click the thumbnails above to see different angles and details of this <?php echo $categoryInfo['type']; ?> design.</p>
                    <?php endif; ?>
                </div>
                
                <?php if (in_array($collection, ['bands', 'engagement', 'family'])): ?>
                <!-- Product Configurator -->
                <div id="product-configurator" 
                     data-collection="<?php echo htmlspecialchars($collection); ?>"
                     data-product-id="<?php echo htmlspecialchars($itemId); ?>"
                     data-category="<?php echo htmlspecialchars($foundCategory); ?>"
                     data-base-price="<?php echo htmlspecialchars($config['priceRange'][0]); ?>"
                     data-product-name="<?php echo htmlspecialchars($displayName); ?>"
                     data-base-product-id="<?php echo htmlspecialchars(preg_replace('/[ML]$/', '', $itemId)); ?>">
                    <div class="configurator-loading"></div>
                </div>
                <?php else: ?>
                <!-- Static Features for Other Collections -->
                <div class="item-features">
                    <h3>Features & Specifications</h3>
                    <ul class="feature-list">
                        <li><span>Model:</span> <span><?php echo strtoupper($itemId); ?></span></li>
                        <li><span>Collection:</span> <span><?php echo $config['name']; ?></span></li>
                        <li><span>Category:</span> <span><?php echo $categoryInfo['display_name'] ?? ucfirst($foundCategory); ?></span></li>
                        <li><span>Type:</span> <span><?php echo ucfirst($categoryInfo['type']); ?></span></li>
                        <li><span>Available Views:</span> <span><?php echo count($variants); ?> different angles</span></li>
                        <li><span>Materials:</span> <span>Multiple options available</span></li>
                        <li><span>Finishes:</span> <span>Various finishes available</span></li>
                        <li><span>Customization:</span> <span>Available upon request</span></li>
                        <li><span>Quality:</span> <span>Handcrafted with premium materials</span></li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="add-to-cart-btn"
                            data-item-id="<?php echo htmlspecialchars($itemId); ?>"
                            data-collection="<?php echo htmlspecialchars($collection); ?>"
                            data-category="<?php echo htmlspecialchars($foundCategory); ?>"
                            data-name="<?php echo htmlspecialchars($displayName); ?>"
                            data-price-range="<?php echo htmlspecialchars($priceRange); ?>"
                            data-image="<?php echo htmlspecialchars($mainVariant['thumbPath']); ?>">
                        🛒 Add to Cart
                    </button>
                    <a href="#contact" class="btn btn-primary">Request Quote</a>
                    <a href="#consultation" class="btn btn-secondary">Design Consultation</a>
                    <a href="<?php echo $config['backLink']; ?>" class="btn btn-secondary">View All <?php echo $config['name']; ?></a>
                </div>
            </div>
        </div>
        
        <!-- Collection-Specific Content -->
        <?php
        // Include collection-specific template if it exists
        $collectionTemplate = __DIR__ . "/templates/{$collection}_detail.php";
        if (file_exists($collectionTemplate)) {
            include $collectionTemplate;
        }
        ?>
    </div>

    <style>
    /* Thumbnail Carousel Styles */
    .thumbnail-carousel-container {
        position: relative;
        width: 100%;
        max-width: 320px; /* Width for exactly 3 thumbnails + gaps */
        margin: 0 auto;
        overflow: hidden;
    }
    
    .thumbnail-carousel {
        display: flex;
        overflow-x: auto;
        scroll-behavior: smooth;
        gap: 10px;
        padding: 10px 40px;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
        scroll-snap-type: x mandatory;
    }
    
    .thumbnail-carousel::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
    
    .carousel-thumbnail {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
        border-radius: 8px;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        object-fit: cover;
        scroll-snap-align: start;
    }
    
    .carousel-thumbnail:hover {
        transform: scale(1.05);
        border-color: #b8860b;
    }
    
    .carousel-thumbnail.active {
        border-color: #b8860b;
        box-shadow: 0 0 10px rgba(184, 134, 11, 0.5);
    }
    
    /* Opacity fade for edge thumbnails */
    .carousel-thumbnail.fade-left {
        opacity: 0.3;
        transition: opacity 0.3s ease;
    }
    
    .carousel-thumbnail.fade-right {
        opacity: 0.3;
        transition: opacity 0.3s ease;
    }
    
    .carousel-thumbnail.fade-center {
        opacity: 1;
        transition: opacity 0.3s ease;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .thumbnail-carousel-container {
            max-width: 260px; /* Adjusted for mobile */
        }
        
        .carousel-thumbnail {
            width: 60px;
            height: 60px;
        }
        
        .thumbnail-carousel {
            padding: 10px 30px;
            gap: 8px;
        }
    }
    
    /* Existing thumbnail gallery styles for 3 or fewer items */
    .thumbnail-gallery.centered-grid {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .thumbnail-gallery .thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        object-fit: cover;
    }
    
    .thumbnail-gallery .thumbnail:hover {
        transform: scale(1.05);
        border-color: #b8860b;
    }
    
    .thumbnail-gallery .thumbnail.active {
        border-color: #b8860b;
        box-shadow: 0 0 10px rgba(184, 134, 11, 0.5);
    }
    </style>

    <script>
    function changeMainImage(imageSrc, thumbnailElement) {
        const mainImage = document.getElementById('mainImage');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        // Update main image with fade effect
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = imageSrc;
            mainImage.style.opacity = '1';
        }, 150);
        
        // Update active thumbnail
        thumbnails.forEach(thumb => thumb.classList.remove('active'));
        if (thumbnailElement) {
            thumbnailElement.classList.add('active');
        }
    }

    // Keyboard navigation for variants
    document.addEventListener('keydown', function(e) {
        const thumbnails = document.querySelectorAll('.thumbnail');
        const activeThumbnail = document.querySelector('.thumbnail.active');
        
        if (thumbnails.length <= 1) return;
        
        let currentIndex = Array.from(thumbnails).indexOf(activeThumbnail);
        
        if (e.key === 'ArrowLeft' && currentIndex > 0) {
            e.preventDefault();
            thumbnails[currentIndex - 1].click();
        } else if (e.key === 'ArrowRight' && currentIndex < thumbnails.length - 1) {
            e.preventDefault();
            thumbnails[currentIndex + 1].click();
        }
    });
    
    // Thumbnail Carousel functionality
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('thumbnailCarousel');
        if (carousel) {
            let isScrolling = false;
            let scrollTimeout;
            
            // Auto-scroll functionality
            function startAutoScroll() {
                if (isScrolling) return;
                
                const thumbnailWidth = 80 + 10; // thumbnail width + gap
                const currentScroll = carousel.scrollLeft;
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;
                
                if (currentScroll >= maxScroll) {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: thumbnailWidth, behavior: 'smooth' });
                }
            }
            
            // Start auto-scroll
            let autoScrollInterval = setInterval(startAutoScroll, 3000);
            
            // Pause on hover/touch
            carousel.addEventListener('mouseenter', () => {
                clearInterval(autoScrollInterval);
                isScrolling = true;
            });
            
            carousel.addEventListener('mouseleave', () => {
                isScrolling = false;
                autoScrollInterval = setInterval(startAutoScroll, 3000);
            });
            
            // Pause on touch/drag
            carousel.addEventListener('touchstart', () => {
                clearInterval(autoScrollInterval);
                isScrolling = true;
            });
            
            carousel.addEventListener('touchend', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    isScrolling = false;
                    autoScrollInterval = setInterval(startAutoScroll, 3000);
                }, 2000);
            });
            
            // Pause during manual scroll
            carousel.addEventListener('scroll', () => {
                clearInterval(autoScrollInterval);
                isScrolling = true;
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    isScrolling = false;
                    autoScrollInterval = setInterval(startAutoScroll, 3000);
                }, 1500);
            });
            
            // Handle carousel thumbnail clicks
            const carouselThumbnails = carousel.querySelectorAll('.carousel-thumbnail');
            carouselThumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    const imageSrc = this.src;
                    changeMainImage(imageSrc, this);
                    
                    // Update active state for carousel thumbnails
                    carouselThumbnails.forEach(thumb => thumb.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Function to update thumbnail opacity based on position
            function updateThumbnailOpacity() {
                const containerRect = carousel.getBoundingClientRect();
                const containerCenter = containerRect.left + containerRect.width / 2;
                
                carouselThumbnails.forEach(thumbnail => {
                    const thumbRect = thumbnail.getBoundingClientRect();
                    const thumbCenter = thumbRect.left + thumbRect.width / 2;
                    const distanceFromCenter = Math.abs(thumbCenter - containerCenter);
                    
                    // Remove existing fade classes
                    thumbnail.classList.remove('fade-left', 'fade-right', 'fade-center');
                    
                    // Determine position relative to center
                    if (distanceFromCenter <= 50) {
                        // Center thumbnail (fully visible)
                        thumbnail.classList.add('fade-center');
                    } else if (thumbCenter < containerCenter) {
                        // Left side thumbnail
                        thumbnail.classList.add('fade-left');
                    } else {
                        // Right side thumbnail
                        thumbnail.classList.add('fade-right');
                    }
                });
            }
            
            // Update opacity on scroll
            carousel.addEventListener('scroll', updateThumbnailOpacity);
            
            // Initial opacity update
            updateThumbnailOpacity();
        }
    });
    
    // Smooth scroll for action buttons
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    </script>
    
    <script>
    // Initialize cart system
    let cadmanCart;
    document.addEventListener('DOMContentLoaded', function() {
        cadmanCart = new CadmanCart();
        
        // Initialize product configurator if present
        const configuratorElement = document.getElementById('product-configurator');
        if (configuratorElement) {
            console.log('Initializing product configurator...');
            const configurator = new ProductConfigurator(configuratorElement);
            configurator.init();
        }
    });
    </script>

    <?php 
    include 'footer.php'; 
    renderFooter('detail');
    ?>
</body>
</html>