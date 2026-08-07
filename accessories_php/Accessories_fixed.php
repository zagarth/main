<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Jewelry accessories, earrings, necklaces, pendants, Cadman Manufacturing, fine jewelry" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Accessories Collection - Cadman Manufacturing</title>
<script src="../js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('accessories'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    
    <!-- Collection Header -->
    <div class="accessories-header">
        <div class="collection-header">
            <h1>Accessories Collection</h1>
            <p>Complete your look with our stunning collection of fine jewelry accessories. From elegant earrings to eye-catching pendants, each piece is crafted with attention to detail and designed to complement your personal style.</p>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <h3 style="margin-bottom: 15px; color: #0066CC;">Filter by Type</h3>
        <button class="filter-btn active" onclick="filterItems('all')">All Accessories</button>
        <button class="filter-btn" onclick="filterItems('crosses')">Crosses & Lockets</button>
        <button class="filter-btn" onclick="filterItems('idents')">Idents</button>
        <button class="filter-btn" onclick="filterItems('earrings')">Pendant Earrings</button>
    </div>
    
    <!-- Gallery Container -->
    <div class="gallery-container">
        <div class="gallery-grid" id="jewelry-gallery">
            <!-- Crosses and Lockets Collection Items -->
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/10TGA.png" alt="Traditional Cross - 10TGA" loading="lazy">
                <div class="item-info">
                    <h3>Traditional Cross - 10TGA</h3>
                    <p>Classic gold cross pendant</p>
                    <div class="item-price">Starting at $285</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('traditional-cross-10TGA')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/20.png" alt="Simple Cross - 20" loading="lazy">
                <div class="item-info">
                    <h3>Simple Cross - 20</h3>
                    <p>Elegant minimalist cross design</p>
                    <div class="item-price">Starting at $195</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('simple-cross-20')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/21.png" alt="Classic Cross - 21" loading="lazy">
                <div class="item-info">
                    <h3>Classic Cross - 21</h3>
                    <p>Traditional cross with refined details</p>
                    <div class="item-price">Starting at $225</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('classic-cross-21')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/21TG.png" alt="Gold Cross - 21TG" loading="lazy">
                <div class="item-info">
                    <h3>Gold Cross - 21TG</h3>
                    <p>Beautiful gold cross pendant</p>
                    <div class="item-price">Starting at $365</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-cross-21TG')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/21TGA.png" alt="Gold Accent Cross - 21TGA" loading="lazy">
                <div class="item-info">
                    <h3>Gold Accent Cross - 21TGA</h3>
                    <p>Cross with gold accent details</p>
                    <div class="item-price">Starting at $385</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-accent-cross-21TGA')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/29TGA.png" alt="Diamond Cross - 29TGA" loading="lazy">
                <div class="item-info">
                    <h3>Diamond Cross - 29TGA</h3>
                    <p>Stunning cross with diamond accents</p>
                    <div class="item-price">Starting at $585</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('diamond-cross-29TGA')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/30.png" alt="Contemporary Cross - 30" loading="lazy">
                <div class="item-info">
                    <h3>Contemporary Cross - 30</h3>
                    <p>Modern cross design</p>
                    <div class="item-price">Starting at $245</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('contemporary-cross-30')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/30TGA.png" alt="Premium Cross - 30TGA" loading="lazy">
                <div class="item-info">
                    <h3>Premium Cross - 30TGA</h3>
                    <p>Premium gold cross with detailed work</p>
                    <div class="item-price">Starting at $485</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('premium-cross-30TGA')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/32.png" alt="Distinctive Cross - 32" loading="lazy">
                <div class="item-info">
                    <h3>Distinctive Cross - 32</h3>
                    <p>Unique cross design with character</p>
                    <div class="item-price">Starting at $275</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('distinctive-cross-32')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/41NB.png" alt="Noble Cross - 41NB" loading="lazy">
                <div class="item-info">
                    <h3>Noble Cross - 41NB</h3>
                    <p>Noble design with traditional styling</p>
                    <div class="item-price">Starting at $295</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('noble-cross-41NB')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/70TGD.png" alt="Gold Diamond Cross - 70TGD" loading="lazy">
                <div class="item-info">
                    <h3>Gold Diamond Cross - 70TGD</h3>
                    <p>Premium gold cross with diamonds</p>
                    <div class="item-price">Starting at $685</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-diamond-cross-70TGD')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/70TGE.png" alt="Gold Emerald Cross - 70TGE" loading="lazy">
                <div class="item-info">
                    <h3>Gold Emerald Cross - 70TGE</h3>
                    <p>Beautiful cross with emerald centerpiece</p>
                    <div class="item-price">Starting at $725</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-emerald-cross-70TGE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">🔖</div>
                <img src="accessories_php/images/Crosses_and_Lockets/L26.png" alt="Heart Locket - L26" loading="lazy">
                <div class="item-info">
                    <h3>Heart Locket - L26</h3>
                    <p>Classic heart-shaped locket</p>
                    <div class="item-price">Starting at $325</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('heart-locket-L26')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">🔖</div>
                <img src="accessories_php/images/Crosses_and_Lockets/L26TG.png" alt="Gold Heart Locket - L26TG" loading="lazy">
                <div class="item-info">
                    <h3>Gold Heart Locket - L26TG</h3>
                    <p>Elegant gold heart locket</p>
                    <div class="item-price">Starting at $425</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-heart-locket-L26TG')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">🔖</div>
                <img src="accessories_php/images/Crosses_and_Lockets/L7TG.png" alt="Oval Locket - L7TG" loading="lazy">
                <div class="item-info">
                    <h3>Oval Locket - L7TG</h3>
                    <p>Elegant oval-shaped locket</p>
                    <div class="item-price">Starting at $385</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('oval-locket-L7TG')">View Details</a>
                </div>
            </div>
            
            <!-- Idents Collection Items -->
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/1BE.png" alt="Business Ident - 1BE" loading="lazy">
                <div class="item-info">
                    <h3>Business Ident - 1BE</h3>
                    <p>Professional business identification</p>
                    <div class="item-price">Starting at $185</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('business-ident-1BE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/4BE.png" alt="Executive Ident - 4BE" loading="lazy">
                <div class="item-info">
                    <h3>Executive Ident - 4BE</h3>
                    <p>Premium executive identification piece</p>
                    <div class="item-price">Starting at $225</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('executive-ident-4BE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/5B.png" alt="Standard Ident - 5B" loading="lazy">
                <div class="item-info">
                    <h3>Standard Ident - 5B</h3>
                    <p>Classic identification badge</p>
                    <div class="item-price">Starting at $165</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('standard-ident-5B')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/IBH.png" alt="Identity Badge - IBH" loading="lazy">
                <div class="item-info">
                    <h3>Identity Badge - IBH</h3>
                    <p>Professional identity badge</p>
                    <div class="item-price">Starting at $195</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('identity-badge-IBH')">View Details</a>
                </div>
            </div>
            
            <!-- Pendant Earrings Collection Items -->
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💎</div>
                <img src="accessories_php/images/Pendant_earrings/CH14.png" alt="Charm Earrings - CH14" loading="lazy">
                <div class="item-info">
                    <h3>Charm Earrings - CH14</h3>
                    <p>Delicate charm-style pendant earrings</p>
                    <div class="item-price">Starting at $295</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('charm-earrings-CH14')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">✨</div>
                <img src="accessories_php/images/Pendant_earrings/PH7.png" alt="Pendant Hoops - PH7" loading="lazy">
                <div class="item-info">
                    <h3>Pendant Hoops - PH7</h3>
                    <p>Classic hoops with pendant details</p>
                    <div class="item-price">Starting at $365</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('pendant-hoops-PH7')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💫</div>
                <img src="accessories_php/images/Pendant_earrings/UN1E.png" alt="Unity Earrings - UN1E" loading="lazy">
                <div class="item-info">
                    <h3>Unity Earrings - UN1E</h3>
                    <p>Elegant pendant earrings</p>
                    <div class="item-price">Starting at $385</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('unity-earrings-UN1E')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💎</div>
                <img src="accessories_php/images/Pendant_earrings/UN1P_chain.png" alt="Unity Pendant Chain - UN1P" loading="lazy">
                <div class="item-info">
                    <h3>Unity Pendant Chain - UN1P</h3>
                    <p>Matching pendant chain for Unity collection</p>
                    <div class="item-price">Starting at $255</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('unity-pendant-chain-UN1P')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">🌟</div>
                <img src="accessories_php/images/Pendant_earrings/UN2E.png" alt="Designer Earrings - UN2E" loading="lazy">
                <div class="item-info">
                    <h3>Designer Earrings - UN2E</h3>
                    <p>Contemporary pendant earring design</p>
                    <div class="item-price">Starting at $425</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('designer-earrings-UN2E')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💫</div>
                <img src="accessories_php/images/Pendant_earrings/UN2P.png" alt="Unity Pendant - UN2P" loading="lazy">
                <div class="item-info">
                    <h3>Unity Pendant - UN2P</h3>
                    <p>Matching pendant for earring set</p>
                    <div class="item-price">Starting at $285</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('unity-pendant-UN2P')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">✨</div>
                <img src="accessories_php/images/Pendant_earrings/UN3P.png" alt="Trio Pendant - UN3P" loading="lazy">
                <div class="item-info">
                    <h3>Trio Pendant - UN3P</h3>
                    <p>Three-stone pendant design</p>
                    <div class="item-price">Starting at $325</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('trio-pendant-UN3P')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">🌟</div>
                <img src="accessories_php/images/Pendant_earrings/UN4LP.png" alt="Long Pendant - UN4LP" loading="lazy">
                <div class="item-info">
                    <h3>Long Pendant - UN4LP</h3>
                    <p>Elegant long pendant design</p>
                    <div class="item-price">Starting at $355</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('long-pendant-UN4LP')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💎</div>
                <img src="accessories_php/images/Pendant_earrings/UN4P.png" alt="Unity Classic Pendant - UN4P" loading="lazy">
                <div class="item-info">
                    <h3>Unity Classic Pendant - UN4P</h3>
                    <p>Classic pendant with unity design</p>
                    <div class="item-price">Starting at $295</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('unity-classic-pendant-UN4P')">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Care Instructions Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; max-width: 1000px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #0066CC; margin-bottom: 20px; text-align: center;">Jewelry Care & Maintenance</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left;">
            <div>
                <h3 style="color: #333; margin-bottom: 10px;">Daily Care</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Remove jewelry before sleeping or exercising</li>
                    <li>Apply lotions and perfumes before putting on jewelry</li>
                    <li>Store pieces separately to prevent scratching</li>
                </ul>
            </div>
            <div>
                <h3 style="color: #333; margin-bottom: 10px;">Cleaning</h3>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Use a soft cloth for regular cleaning</li>
                    <li>Gentle soap solution for deeper cleaning</li>
                    <li>Professional cleaning for valuable pieces</li>
                </ul>
            </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="#formtable" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
                Get Care Instructions
            </a>
        </div>
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
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with customization options, materials, and sizing information.');
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
