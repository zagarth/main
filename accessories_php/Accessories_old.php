<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Jewelry accessories, earrings, necklaces, pendants, Cadman Manufacturing, fine jewelry" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Accessories Collection - Cadman Manufacturing</title>
<script src="../js/jquery-3.6.0.min.js"></script>
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
            <!-- Sample Accessories -->
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">✝️</div>
                <div class="new-badge">New</div>
                <img src="accessories_php/images/Crosses_and_Lockets/70TGD.png" alt="Gold Cross Pendant" loading="lazy">
                <div class="item-info">
                    <h3>Gold Cross Pendant - 70TGD</h3>
                    <p>Beautiful 14K gold cross with detailed craftsmanship</p>
                    <div class="item-price">Starting at $485</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('gold-cross-pendant-70TGD')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="crosses">
                <div class="accessory-icon">�</div>
                <img src="accessories_php/images/Crosses_and_Lockets/70TGE.png" alt="Cross with Gemstone" loading="lazy">
                <div class="item-info">
                    <h3>Cross with Gemstone - 70TGE</h3>
                    <p>Elegant cross pendant with precious gemstone</p>
                    <div class="item-price">Starting at $625</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('cross-gemstone-70TGE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">📿</div>
                <img src="accessories_php/images/Crosses_and_Lockets/L26TG.png" alt="Locket Pendant" loading="lazy">
                <div class="item-info">
                    <h3>Heart Locket - L26TG</h3>
                    <p>Classic gold heart locket for cherished memories</p>
                    <div class="item-price">Starting at $375</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('heart-locket-L26TG')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/41NB.png" alt="Simple Cross" loading="lazy">
                <div class="item-info">
                    <h3>Simple Cross - 41NB</h3>
                    <p>Clean, elegant cross design in precious metal</p>
                    <div class="item-price">Starting at $285</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('simple-cross-41NB')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">�</div>
                <img src="accessories_php/images/Crosses_and_Lockets/29TGA.png" alt="Diamond Cross" loading="lazy">
                <div class="item-info">
                    <h3>Diamond Cross - 29TGA</h3>
                    <p>Stunning cross featuring brilliant diamonds</p>
                    <div class="item-price">Starting at $750</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('diamond-cross-29TGA')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">�</div>
                <div class="new-badge">Popular</div>
                <img src="accessories_php/images/Crosses_and_Lockets/L7TG.png" alt="Oval Locket" loading="lazy">
                <div class="item-info">
                    <h3>Oval Locket - L7TG</h3>
                    <p>Timeless oval locket with intricate detailing</p>
                    <div class="item-price">Starting at $425</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('oval-locket-L7TG')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">✝️</div>
                <img src="accessories_php/images/Crosses_and_Lockets/8ND.png" alt="Traditional Cross" loading="lazy">
                <div class="item-info">
                    <h3>Traditional Cross - 8ND</h3>
                    <p>Classic cross design with traditional styling</p>
                    <div class="item-price">Starting at $315</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('traditional-cross-8ND')">View Details</a>
                </div>
            </div>
            
            <!-- Idents Collection Items -->
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🔖</div>
                <img src="accessories_php/images/Idents/IBH.png" alt="Identity Badge" loading="lazy">
                <div class="item-info">
                    <h3>Identity Badge - IBH</h3>
                    <p>Professional identification badge</p>
                    <div class="item-price">Starting at $185</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('identity-badge-IBH')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/1BE.png" alt="Business Ident" loading="lazy">
                <div class="item-info">
                    <h3>Business Ident - 1BE</h3>
                    <p>Executive business identification piece</p>
                    <div class="item-price">Starting at $225</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('business-ident-1BE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🔖</div>
                <img src="accessories_php/images/Idents/4BE.png" alt="Professional Ident" loading="lazy">
                <div class="item-info">
                    <h3>Professional Ident - 4BE</h3>
                    <p>Elegant professional identification</p>
                    <div class="item-price">Starting at $195</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('professional-ident-4BE')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="idents">
                <div class="accessory-icon">🏷️</div>
                <img src="accessories_php/images/Idents/5B.png" alt="Standard Ident" loading="lazy">
                <div class="item-info">
                    <h3>Standard Ident - 5B</h3>
                    <p>Classic identification badge design</p>
                    <div class="item-price">Starting at $165</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('standard-ident-5B')">View Details</a>
                </div>
            </div>
            
            <!-- Pendant Earrings Collection Items -->
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💎</div>
                <img src="accessories_php/images/Pendant_earrings/UN1E.png" alt="Unity Earrings" loading="lazy">
                <div class="item-info">
                    <h3>Unity Earrings - UN1E</h3>
                    <p>Elegant drop earrings with matching pendants</p>
                    <div class="item-price">Starting at $385</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('unity-earrings-UN1E')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">✨</div>
                <img src="accessories_php/images/Pendant_earrings/UN2E.png" alt="Designer Earrings" loading="lazy">
                <div class="item-info">
                    <h3>Designer Earrings - UN2E</h3>
                    <p>Contemporary pendant earrings</p>
                    <div class="item-price">Starting at $425</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('designer-earrings-UN2E')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">💫</div>
                <img src="accessories_php/images/Pendant_earrings/CH14.png" alt="Charm Earrings" loading="lazy">
                <div class="item-info">
                    <h3>Charm Earrings - CH14</h3>
                    <p>Delicate charm-style pendant earrings</p>
                    <div class="item-price">Starting at $295</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('charm-earrings-CH14')">View Details</a>
                </div>
            </div>
            
            <div class="jewelry-item" data-category="earrings">
                <div class="accessory-icon">🌟</div>
                <img src="accessories_php/images/Pendant_earrings/PH7.png" alt="Pendant Hoops" loading="lazy">
                <div class="item-info">
                    <h3>Pendant Hoops - PH7</h3>
                    <p>Classic hoops with pendant accents</p>
                    <div class="item-price">Starting at $365</div>
                    <a href="#" class="view-details-btn" onclick="viewDetails('pendant-hoops-PH7')">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Care Instructions Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; max-width: 1000px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #0066CC; margin-bottom: 20px; text-align: center;">Jewelry Care & Maintenance</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin: 25px 0;">
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(0,102,204,0.1);">
                <h4 style="color: #0066CC; margin-bottom: 10px; font-size: 1.2em;">🧼 Cleaning</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">Regular gentle cleaning with jewelry-specific solutions maintains brilliance</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(0,102,204,0.1);">
                <h4 style="color: #0066CC; margin-bottom: 10px; font-size: 1.2em;">📦 Storage</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">Store pieces separately in soft pouches to prevent scratching</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(0,102,204,0.1);">
                <h4 style="color: #0066CC; margin-bottom: 10px; font-size: 1.2em;">🔧 Maintenance</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">Professional inspection and cleaning recommended annually</p>
            </div>
            <div style="text-align: center; padding: 20px; border-radius: 8px; background: rgba(0,102,204,0.1);">
                <h4 style="color: #0066CC; margin-bottom: 10px; font-size: 1.2em;">⚠️ Protection</h4>
                <p style="color: #666; font-size: 14px; line-height: 1.5;">Remove jewelry during sports, cleaning, or heavy activities</p>
            </div>
        </div>
    </div>
    
    <!-- Custom Accessories Section -->
    <div style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto;">
        <h2 style="color: #0066CC; margin-bottom: 15px;">Custom Accessory Design</h2>
        <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
            Create the perfect accessory to complement your unique style. Our skilled craftsmen can design and create custom earrings, necklaces, bracelets, and pendants tailored to your vision and preferences.
        </p>
        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
            <a href="#formtable" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">Design Consultation</a>
            <a href="#formtable" style="background: linear-gradient(145deg, #4169E1, #0000CD); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">View Portfolio</a>
            <a href="#formtable" style="background: linear-gradient(145deg, #1E90FF, #0066CC); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; transition: all 0.3s ease;">Get Quote</a>
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
        alert('View details for: ' + itemId + '\n\nThis would open a detailed view with specifications, care instructions, and customization options.');
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
