<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Wedding bands, Celtic rings, jewelry bands, Cadman Manufacturing, wedding rings" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Bands Collection - Cadman Manufacturing</title>
<script src="../js/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('bands'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    
    <!-- Collection Header -->
    <div class="bands-header">
        <div class="collection-header">
            <h1>Bands Collection</h1>
            <p>Discover our complete collection of wedding bands and rings. From traditional Celtic designs to modern contemporary styles, each piece is crafted with precision and designed to symbolize your eternal love and personal style.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
        <div class="category-filter">
        <h3>Filter by Category</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Items</button>
        <button class="filter-btn" onclick="filterItems('celtic')">Celtic</button>
        <button class="filter-btn" onclick="filterItems('cultural')">Cultural</button>
        <button class="filter-btn" onclick="filterItems('fancy')">Fancy</button>
        <button class="filter-btn" onclick="filterItems('plain')">Plain</button>
    </div>
    
    <!-- Gallery Container -->
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
            <!-- Featured Plain Bands - Top of Gallery -->
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/300Rm.png" alt="Men's Classic Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Classic Gold Band - 300RM</h3>
                    <p>Traditional men's 14K gold band with comfort fit</p>
                    <div class="item-price">Starting at $775</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-classic-gold-300RM')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/3001M.png" alt="Wide Men's Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Wide Men's Gold Band - 3001M</h3>
                    <p>Bold men's gold wedding band with substantial feel</p>
                    <div class="item-price">Starting at $895</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('wide-mens-gold-3001M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/3T18M.png" alt="Men's Platinum Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Platinum Wedding Band - 3T18M</h3>
                    <p>Durable platinum band designed for everyday wear</p>
                    <div class="item-price">Starting at $1,425</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-platinum-3T18M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/4001L.png" alt="Ladies Comfort Fit Band" loading="lazy">
                <div class="item-info">
                    <h3>Ladies Comfort Fit Band - 4001L</h3>
                    <p>Elegant ladies band with superior comfort design</p>
                    <div class="item-price">Starting at $725</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('ladies-comfort-4001L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/4T00RM.png" alt="Men's Rose Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Rose Gold Band - 4T00RM</h3>
                    <p>Contemporary men's rose gold with warm appeal</p>
                    <div class="item-price">Starting at $975</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-rose-gold-4T00RM')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/5001M.png" alt="Men's Wide Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Wide Wedding Band - 5001M</h3>
                    <p>Substantial men's band for those who prefer bold styling</p>
                    <div class="item-price">Starting at $1,025</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-wide-5001M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/550TM.png" alt="Men's White Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's White Gold Band - 550TM</h3>
                    <p>Classic men's white gold with polished finish</p>
                    <div class="item-price">Starting at $925</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-white-gold-550TM')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/5T00RM.png" alt="Men's Rose Gold Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Rose Gold Wedding Band - 5T00RM</h3>
                    <p>Premium rose gold band with sophisticated styling</p>
                    <div class="item-price">Starting at $1,075</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-rose-gold-5T00RM')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/5T18M.png" alt="Men's Premium Platinum Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Premium Platinum Band - 5T18M</h3>
                    <p>High-quality platinum with exceptional durability</p>
                    <div class="item-price">Starting at $1,525</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-premium-platinum-5T18M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/600TM.png" alt="Men's Traditional Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Traditional Gold Band - 600TM</h3>
                    <p>Timeless men's gold design with classic appeal</p>
                    <div class="item-price">Starting at $825</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-traditional-600TM')">View Details</a>
                </div>
            </div>
            
            <!-- Celtic Collection Items -->
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5310L.png" alt="Celtic Knot Ring" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Knot Ring - 5310L</h3>
                    <p>Traditional endless knot symbolizing eternal love</p>
                    <div class="item-price">Starting at $925</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-knot-ring-5310L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5312L.png" alt="Celtic Band" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Band - 5312L</h3>
                    <p>Intricate Celtic knotwork pattern</p>
                    <div class="item-price">Starting at $875</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-band-5312L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5424L.png" alt="Celtic Spiral Band" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Spiral Band - 5424L</h3>
                    <p>Ancient spiral design representing life's journey</p>
                    <div class="item-price">Starting at $775</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-spiral-5424L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5636L.png" alt="Celtic Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Wedding Band - 5636L</h3>
                    <p>Elegant knotwork for your eternal bond</p>
                    <div class="item-price">Starting at $1,150</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-wedding-5636L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5854L.png" alt="Celtic Heritage Ring" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Heritage Ring - 5854L</h3>
                    <p>Traditional design with modern craftsmanship</p>
                    <div class="item-price">Starting at $825</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-heritage-5854L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5310M.png" alt="Celtic Knot Band - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Knot Band - 5310M</h3>
                    <p>Men's Celtic knotwork design with bold styling</p>
                    <div class="item-price">Starting at $975</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-knot-5310M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5312M.png" alt="Celtic Wedding Band - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Wedding Band - 5312M</h3>
                    <p>Men's Celtic pattern with traditional motifs</p>
                    <div class="item-price">Starting at $925</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-wedding-5312M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="celtic">
                <div class="celtic-pattern">🍀</div>
                <img src="bands_php/images/celtic/5424M.png" alt="Celtic Spiral Band - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Celtic Spiral Band - 5424M</h3>
                    <p>Men's spiral design representing eternal journey</p>
                    <div class="item-price">Starting at $825</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('celtic-spiral-5424M')">View Details</a>
                </div>
            </div>
            
            <!-- Cultural Collection Items -->
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5410M.png" alt="Trinity Knot Ring" loading="lazy">
                <div class="item-info">
                    <h3>Trinity Knot Ring - 5410M</h3>
                    <p>Sacred triquetra representing mind, body, spirit</p>
                    <div class="item-price">Starting at $650</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('trinity-knot-5410M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">�</div>
                <img src="bands_php/images/cultural/5462L.png" alt="Cultural Heritage Band" loading="lazy">
                <div class="item-info">
                    <h3>Cultural Heritage Band - 5462L</h3>
                    <p>Meaningful symbols from ancient traditions</p>
                    <div class="item-price">Starting at $785</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('cultural-heritage-5462L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5590L.png" alt="Traditional Cultural Ring" loading="lazy">
                <div class="item-info">
                    <h3>Traditional Cultural Ring - 5590L</h3>
                    <p>Time-honored patterns with cultural significance</p>
                    <div class="item-price">Starting at $695</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('traditional-cultural-5590L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5664L.png" alt="Heritage Symbol Band" loading="lazy">
                <div class="item-info">
                    <h3>Heritage Symbol Band - 5664L</h3>
                    <p>Cultural symbols crafted with precision</p>
                    <div class="item-price">Starting at $745</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('heritage-symbol-5664L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5424M.png" alt="Cultural Spiral Band - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Cultural Spiral Band - 5424M</h3>
                    <p>Men's cultural spiral design with deep meaning</p>
                    <div class="item-price">Starting at $795</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('cultural-spiral-5424M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5462M.png" alt="Cultural Heritage Band - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Cultural Heritage Band - 5462M</h3>
                    <p>Men's heritage design with traditional elements</p>
                    <div class="item-price">Starting at $835</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('cultural-heritage-5462M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5590M.png" alt="Traditional Cultural Ring - Men's" loading="lazy">
                <div class="item-info">
                    <h3>Traditional Cultural Ring - 5590M</h3>
                    <p>Men's traditional pattern with cultural significance</p>
                    <div class="item-price">Starting at $745</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('traditional-cultural-5590M')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="cultural">
                <div class="celtic-pattern">🌍</div>
                <img src="bands_php/images/cultural/5690M.png" alt="Cultural Heritage Design" loading="lazy">
                <div class="item-info">
                    <h3>Cultural Heritage Design - 5690M</h3>
                    <p>Distinctive cultural motifs with modern appeal</p>
                    <div class="item-price">Starting at $695</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('cultural-heritage-5690M')">View Details</a>
                </div>
            </div>
            
            <!-- Fancy Collection Items -->
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/2291.png" alt="Textured Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Textured Gold Band - 2291</h3>
                    <p>Unique hammered texture finish on 18K gold</p>
                    <div class="item-price">Starting at $950</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('textured-gold-band-2291')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/6T64L.png" alt="Diamond Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Diamond Wedding Band - 6T64L</h3>
                    <p>Elegant band featuring channel-set diamonds</p>
                    <div class="item-price">Starting at $1,850</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('diamond-wedding-band-6T64L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/7T38L.png" alt="Two-Tone Band" loading="lazy">
                <div class="item-info">
                    <h3>Two-Tone Band - 7T38L</h3>
                    <p>Modern design combining white and yellow gold</p>
                    <div class="item-price">Starting at $1,100</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('two-tone-band-7T38L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/5758L.png" alt="Textured Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Textured Wedding Band - 5758L</h3>
                    <p>Sophisticated textured pattern design</p>
                    <div class="item-price">Starting at $925</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('textured-wedding-band-5758L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/8T14L.png" alt="Diamond Channel Band" loading="lazy">
                <div class="item-info">
                    <h3>Diamond Channel Band - 8T14L</h3>
                    <p>Brilliant diamonds in elegant channel setting</p>
                    <div class="item-price">Starting at $2,150</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('diamond-channel-band-8T14L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/1T026L.png" alt="Platinum Fancy Band" loading="lazy">
                <div class="item-info">
                    <h3>Platinum Fancy Band - 1T026L</h3>
                    <p>Luxurious platinum with decorative elements</p>
                    <div class="item-price">Starting at $1,475</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('platinum-fancy-1T026L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/1T028L.png" alt="Designer Platinum Band" loading="lazy">
                <div class="item-info">
                    <h3>Designer Platinum Band - 1T028L</h3>
                    <p>Contemporary platinum design with artistic flair</p>
                    <div class="item-price">Starting at $1,525</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('designer-platinum-1T028L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/1T030L.png" alt="Luxury Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Luxury Wedding Band - 1T030L</h3>
                    <p>Premium crafted band with sophisticated styling</p>
                    <div class="item-price">Starting at $1,395</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('luxury-wedding-1T030L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/5771L.png" alt="Decorative Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Decorative Gold Band - 5771L</h3>
                    <p>Ornate gold design with intricate detailing</p>
                    <div class="item-price">Starting at $1,075</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('decorative-gold-5771L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="fancy">
                <div class="celtic-pattern">💎</div>
                <img src="bands_php/images/fancy/5777L.png" alt="Elegant Fancy Band" loading="lazy">
                <div class="item-info">
                    <h3>Elegant Fancy Band - 5777L</h3>
                    <p>Sophisticated design with premium finishes</p>
                    <div class="item-price">Starting at $1,125</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('elegant-fancy-5777L')">View Details</a>
                </div>
            </div>
            
            <!-- Plain Collection Items -->
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/300RL.png" alt="Classic Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Classic Gold Band - 300RL</h3>
                    <p>Timeless 14K gold wedding band with polished finish</p>
                    <div class="item-price">Starting at $850</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('classic-gold-band-300RL')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/4T18L.png" alt="Modern Platinum Band" loading="lazy">
                <div class="item-info">
                    <h3>Modern Platinum Band - 4T18L</h3>
                    <p>Contemporary platinum design with brushed finish</p>
                    <div class="item-price">Starting at $1,250</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('modern-platinum-band-4T18L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/550TL.png" alt="Classic White Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>Classic White Gold Band - 550TL</h3>
                    <p>Traditional 14K white gold with high polish</p>
                    <div class="item-price">Starting at $875</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('classic-white-gold-band-550TL')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/5T18L.png" alt="Platinum Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Platinum Wedding Band - 5T18L</h3>
                    <p>Pure platinum band with contemporary styling</p>
                    <div class="item-price">Starting at $1,375</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('platinum-wedding-5T18L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/3T18L.png" alt="Classic Platinum Band" loading="lazy">
                <div class="item-info">
                    <h3>Classic Platinum Band - 3T18L</h3>
                    <p>Traditional platinum wedding band with clean lines</p>
                    <div class="item-price">Starting at $1,195</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('classic-platinum-3T18L')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/4T00RL.png" alt="Rose Gold Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Rose Gold Wedding Band - 4T00RL</h3>
                    <p>Elegant rose gold with warm, romantic appeal</p>
                    <div class="item-price">Starting at $925</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('rose-gold-wedding-4T00RL')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/200RM.png" alt="Men's Classic Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Classic Band - 200RM</h3>
                    <p>Traditional men's wedding band with timeless appeal</p>
                    <div class="item-price">Starting at $795</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-classic-200RM')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/400TL.png" alt="White Gold Band" loading="lazy">
                <div class="item-info">
                    <h3>White Gold Band - 400TL</h3>
                    <p>Classic white gold with polished finish</p>
                    <div class="item-price">Starting at $895</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('white-gold-400TL')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="plain">
                <div class="celtic-pattern">⭕</div>
                <img src="bands_php/images/plain/500TM.png" alt="Men's Gold Wedding Band" loading="lazy">
                <div class="item-info">
                    <h3>Men's Gold Wedding Band - 500TM</h3>
                    <p>Men's traditional gold band with comfortable fit</p>
                    <div class="item-price">Starting at $945</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('mens-gold-500TM')">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Wedding Bands Heritage Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto; color: #333;">
        <h2 style="color: #2c2c2c; margin-bottom: 15px;">Wedding Bands & Heritage Designs</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            From traditional Celtic knotwork to contemporary platinum designs, our comprehensive bands collection celebrates both heritage and innovation. Each piece is crafted with precision to symbolize your eternal commitment and personal style.
        </p>
        <a href="#formtable" style="background: linear-gradient(145deg, #FFD700, #FFA500); color: black; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
            Explore Custom Band Designs
        </a>
    </div>
    
    <script>
    // Filter functionality
    function filterItems(category) {
        const items = document.querySelectorAll('.jewelry-item');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Filter items
        items.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.5s ease';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // View details functionality
    function viewDetails(itemId) {
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with Celtic symbolism explanation, specifications, and customization options.');
    }
    
    // Add fade-in animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize gallery animation on load
    $(document).ready(function() {
        $('.jewelry-item').each(function(index) {
            $(this).delay(index * 100).animate({
                opacity: 1
            }, 500);
        });
    });
    </script>
</body>
</html>
