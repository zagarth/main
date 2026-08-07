<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="/styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="About Cadman Manufacturing, jewelry company history, family business, custom jewelry makers, Canadian jewelry" />
<meta name="description" content="Learn about Cadman Manufacturing - a family-owned Canadian jewelry company with decades of experience in custom jewelry design and manufacturing." />
<link rel="icon" sizes="" href="/favicon.ico">
<title>About Us - Cadman Manufacturing</title>
<script src="/js/jquery-3.6.0.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
// Ensure STLLoader is available
if (typeof THREE !== 'undefined' && !THREE.STLLoader) {
    // Define STLLoader if not available
    THREE.STLLoader = function() {
        this.manager = THREE.DefaultLoadingManager;
    };
    
    THREE.STLLoader.prototype = {
        constructor: THREE.STLLoader,
        
        load: function(url, onLoad, onProgress, onError) {
            var scope = this;
            var loader = new THREE.FileLoader(scope.manager);
            loader.setResponseType('arraybuffer');
            loader.load(url, function(text) {
                try {
                    onLoad(scope.parse(text));
                } catch (e) {
                    if (onError) {
                        onError(e);
                    } else {
                        console.error(e);
                    }
                    scope.manager.itemError(url);
                }
            }, onProgress, onError);
        },
        
        parse: function(data) {
            var geometry = new THREE.BufferGeometry();
            var dataview = new DataView(data);
            var isLittleEndian = true;
            var triangles = dataview.getUint32(80, isLittleEndian);
            var offset = 84;
            var vertices = [];
            var normals = [];
            
            for (var i = 0; i < triangles; i++) {
                var normal = new THREE.Vector3(
                    dataview.getFloat32(offset, isLittleEndian),
                    dataview.getFloat32(offset + 4, isLittleEndian),
                    dataview.getFloat32(offset + 8, isLittleEndian)
                );
                offset += 12;
                
                for (var j = 0; j < 3; j++) {
                    vertices.push(
                        dataview.getFloat32(offset, isLittleEndian),
                        dataview.getFloat32(offset + 4, isLittleEndian),
                        dataview.getFloat32(offset + 8, isLittleEndian)
                    );
                    normals.push(normal.x, normal.y, normal.z);
                    offset += 12;
                }
                offset += 2;
            }
            
            geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
            geometry.setAttribute('normal', new THREE.Float32BufferAttribute(normals, 3));
            geometry.computeBoundingBox();
            geometry.computeBoundingSphere();
            
            return geometry;
        }
    };
}
</script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('about'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    <?php include 'includes/search_modal.php'; ?>
    
    <!-- About Us Header -->
    <div class="about-header">
        <div class="collection-header">
            <h1>About Cadman Manufacturing</h1>
            <p>Discover our story, heritage, and commitment to crafting exceptional jewelry for over three decades.</p>
        </div>
    </div>
    
    <!-- Main Content Container -->
    <div class="about-container">
        
        <!-- Company Story Section -->
        <section class="about-section">
            <div class="section-content">
                <h2>Our Story</h2>
                <div class="story-grid">
                    <div class="story-text">
                        <h3>Humble Beginnings - 1930s</h3>
                        <p>Our story began in the 1930s, when Walter Cadman travelled from town to town with a trunk full of jewellery. In true entrepreneur fashion, our founder was passionate about delivering nothing short of excellence. Those first impressions created memorable experiences that still ring true today.</p>
                        
                        <p>From Walter's humble beginnings as a travelling jeweller to becoming a trusted name in custom jewelry, we have remained committed to our core values of quality, integrity, and personalized service. Every piece that leaves our workshop carries with it decades of expertise and an unwavering dedication to excellence that Walter established nearly a century ago.</p>
                        
                        <p>Today, Cadman Manufacturing continues Walter's legacy, combining traditional jewelry-making techniques with modern innovation to create pieces that tell your unique story. We are committed to the same passion for exceptional craftsmanship that has defined our family business for generations.</p>
                    </div>
                    <div class="story-image">
                        <img src="/PNG/cadmanplant.png" alt="Cadman Manufacturing Facility" class="company-image">
                    </div>
                </div>
            </div>
        </section>
        
        <!-- 3D Jewelry Showcase Section -->
        <section class="about-section showcase-section">
            <div class="section-content">
                <h2>Experience Our Craftsmanship</h2>
                <p style="text-align: center; color: #666; font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">
                    Custom by request 3D printed and CNC machined jewellery. For your loved ones or yourself. We can also create Gifts and Awards.
                </p>
                
                <!-- 3D Jewelry Viewer -->
                <div id="jewelry-viewer" style="text-align: center; padding: 20px; background: rgba(0,0,0,0.05); margin: 20px; border-radius: 10px;">
                    <h3 style="color: #333; margin-bottom: 20px;">Interactive 3D Jewelry Viewer</h3>
                    <div id="jewelry-container" style="position: relative; width: 100%; max-width: 600px; height: 400px; margin: 0 auto; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 10px; overflow: hidden; touch-action: none;">
                        <canvas id="jewelry-canvas" style="width: 100%; height: 100%; display: block; touch-action: none; -webkit-touch-callout: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;"></canvas>
                        
                        <!-- Single loading indicator that will be replaced by JavaScript -->
                        <div id="loading-display" style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            color: #FFD700;
                            font-size: 18px;
                            text-align: center;
                            font-family: Arial, sans-serif;
                        ">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border: 4px solid rgba(255, 215, 0, 0.3);
                                border-top: 4px solid #FFD700;
                                border-radius: 50%;
                                animation: spin 1s linear infinite;
                                margin: 0 auto 15px;
                            "></div>
                            <div style="font-weight: bold; margin-bottom: 10px;">Initializing 3D Viewer</div>
                            <div style="font-size: 14px; opacity: 0.8;">Please wait...</div>
                        </div>
                        
                        <!-- Controls Info: moved below canvas for visibility -->
                        <div id="controls-info" style="margin: 18px auto 0 auto; color: rgba(40,40,40,0.85); font-size: 14px; background: rgba(255,255,255,0.85); border-radius: 8px; padding: 10px 18px; max-width: 400px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: none;">
                            <span class="desktop-controls">Desktop: Click & drag to rotate • Scroll to zoom</span>
                            <span class="mobile-controls">Mobile: Touch & drag to rotate • Pinch to zoom</span>
                        </div>
                        
                        <style>
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        </style>
                    </div>
                    
                    <!-- Jewelry Selection -->
                    <div style="margin-top: 20px; text-align: center;">
                        <button onclick="loadJewelry('cadman3d')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Cadman Logo</button>
                        <button onclick="loadJewelry('gem')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Gem Design</button>
                        <button onclick="loadJewelry('angel')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Angel Pendant</button>
                        <button onclick="loadJewelry('canadagames')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Canada Games</button>
                        <button onclick="loadJewelry('snowflakes')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Snowflakes</button>
                        <button onclick="loadJewelry('manistate')" style="margin: 5px; padding: 10px 20px; background: #0066CC; color: white; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">Manitoulin Design</button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Our Mission Section -->
        <section class="about-section mission-section">
            <div class="section-content">
                <h2>Our Mission</h2>
                <div class="mission-grid">
                    <div class="mission-card">
                        <div class="mission-icon">🏆</div>
                        <h3>Quality Craftsmanship</h3>
                        <p>We are committed to using only the finest materials and time-honored techniques to create jewelry that stands the test of time.</p>
                    </div>
                    <div class="mission-card">
                        <div class="mission-icon">💎</div>
                        <h3>Custom Design</h3>
                        <p>Every piece is thoughtfully designed to reflect your personal style and commemorate life's most precious moments.</p>
                    </div>
                    <div class="mission-card">
                        <div class="mission-icon">🤝</div>
                        <h3>Personal Service</h3>
                        <p>We believe in building lasting relationships with our clients through personalized attention and exceptional service.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Our Expertise Section -->
        <section class="about-section expertise-section">
            <div class="section-content">
                <h2>Our Expertise</h2>
                <div class="expertise-grid">
                    <div class="expertise-item">
                        <h3>Engagement Rings</h3>
                        <p>Creating the perfect symbol of your love with custom engagement rings that capture your unique story.</p>
                    </div>
                    <div class="expertise-item">
                        <h3>Wedding Bands</h3>
                        <p>Crafting beautiful wedding bands that represent your eternal commitment and personal style.</p>
                    </div>
                    <div class="expertise-item">
                        <h3>Family Jewelry</h3>
                        <p>Designing meaningful family pieces that celebrate heritage, milestones, and cherished memories.</p>
                    </div>
                    <div class="expertise-item">
                        <h3>Corporate Awards</h3>
                        <p>Creating distinguished corporate recognition pieces and awards that honor achievement and excellence.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Why Choose Us Section -->
        <section class="about-section why-choose-section">
            <div class="section-content">
                <h2>Why Choose Cadman Manufacturing?</h2>
                <div class="why-choose-grid">
                    <div class="feature">
                        <h4>Decades of Experience</h4>
                        <p>Our skilled artisans bring years of expertise to every project, ensuring exceptional quality and attention to detail.</p>
                    </div>
                    <div class="feature">
                        <h4>Canadian Made</h4>
                        <p>Proudly Canadian-owned and operated, supporting local craftsmanship and sustainable business practices.</p>
                    </div>
                    <div class="feature">
                        <h4>Canadian Jewellery Group</h4>
                        <p>Since it's inception we have been a member of the Canadian Jewellery Group</p>     
                        <a href="https://canadianjewellerygroup.ca/"><img src="/PNG/CJG.png" alt="Canadian Jewellery Group" style ="width: 150px; background-color:white; margin-top: 10px; opacity: 0.8;"></a>
                    </div>
                    <div class="feature">
                        <h4>Quality Guarantee</h4>
                        <p>We stand behind our work with comprehensive warranties and ongoing support for all our pieces.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contact CTA Section -->
        <section class="about-section contact-cta-section">
            <div class="section-content">
                <div class="cta-content">
                    <h2>Ready to Create Something Special?</h2>
                    <p>Let us help you bring your vision to life with our custom jewelry design services. Contact us today to discuss your project and discover the Cadman Manufacturing difference.</p>
                    <div class="cta-buttons">
                        <a href="#" onclick="event.preventDefault(); openContactModalWithTracking('About Page', 'CTA - Ready to Create');" class="btn btn-primary cta-btn">Get Started</a>
                        <a href="/" class="btn btn-secondary cta-btn">View Our Collections</a>
                    </div>
                </div>
            </div>
        </section>
        
    </div>

    <!-- 3D Jewelry Viewer JavaScript -->
    <script>
    // 3D Jewelry Viewer Variables
    let scene, camera, renderer, jewelryMesh;
    let isLoadingJewelry = false;
    let animationId;

    // Material and Texture Creation Functions
    function createDiamondTexture() {
        const size = 512;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        
        // Create diamond facet pattern
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, size, size);
        
        // Draw diamond facets
        const facetSize = 32;
        const rows = size / facetSize;
        const cols = size / facetSize;
        
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const x = col * facetSize;
                const y = row * facetSize;
                const centerX = x + facetSize / 2;
                const centerY = y + facetSize / 2;
                
                // Create facet gradient
                const gradient = context.createRadialGradient(
                    centerX, centerY, 0,
                    centerX, centerY, facetSize / 2
                );
                
                // Alternate facet brightness for diamond effect
                const brightness = (row + col) % 2 === 0 ? 0.9 : 0.7;
                gradient.addColorStop(0, `rgba(255, 255, 255, ${brightness})`);
                gradient.addColorStop(0.5, `rgba(240, 240, 255, ${brightness * 0.8})`);
                gradient.addColorStop(1, `rgba(220, 220, 245, ${brightness * 0.6})`);
                
                context.fillStyle = gradient;
                context.fillRect(x, y, facetSize, facetSize);
                
                // Add facet lines
                context.strokeStyle = `rgba(200, 200, 220, ${brightness * 0.5})`;
                context.lineWidth = 1;
                context.strokeRect(x, y, facetSize, facetSize);
                
                // Add diagonal facet lines
                context.beginPath();
                context.moveTo(x, y);
                context.lineTo(x + facetSize, y + facetSize);
                context.moveTo(x + facetSize, y);
                context.lineTo(x, y + facetSize);
                context.stroke();
            }
        }
        
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;
        texture.repeat.set(2, 2);
        return texture;
    }

    function createMetallicTexture() {
        const size = 1024; // Increased size even more for less repetition
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        
        // Fill with base metallic color
        context.fillStyle = 'rgb(220, 220, 220)';
        context.fillRect(0, 0, size, size);
        
        // Single subtle diagonal streak
        const gradient1 = context.createLinearGradient(0, 0, size * 1.5, size * 0.3);
        gradient1.addColorStop(0, 'rgba(240, 240, 240, 0.0)');
        gradient1.addColorStop(0.3, 'rgba(250, 250, 250, 0.4)');
        gradient1.addColorStop(0.5, 'rgba(255, 255, 255, 0.6)');
        gradient1.addColorStop(0.7, 'rgba(250, 250, 250, 0.4)');
        gradient1.addColorStop(1, 'rgba(240, 240, 240, 0.0)');
        
        context.fillStyle = gradient1;
        context.fillRect(0, 0, size, size);
        
        // Optional second very subtle streak
        const gradient2 = context.createLinearGradient(size * 0.2, size * 0.8, size * 0.8, size * 1.2);
        gradient2.addColorStop(0, 'rgba(210, 210, 210, 0.0)');
        gradient2.addColorStop(0.4, 'rgba(200, 200, 200, 0.2)');
        gradient2.addColorStop(0.6, 'rgba(190, 190, 190, 0.3)');
        gradient2.addColorStop(1, 'rgba(210, 210, 210, 0.0)');
        
        context.fillStyle = gradient2;
        context.fillRect(0, 0, size, size);
        
        // Very minimal noise for subtle texture
        const imageData = context.getImageData(0, 0, size, size);
        const data = imageData.data;
        
        for (let i = 0; i < data.length; i += 4) {
            const noise = (Math.random() - 0.5) * 8; // Very subtle noise
            data[i] = Math.max(0, Math.min(255, data[i] + noise));     // R
            data[i + 1] = Math.max(0, Math.min(255, data[i + 1] + noise)); // G
            data[i + 2] = Math.max(0, Math.min(255, data[i + 2] + noise)); // B
        }
        
        context.putImageData(imageData, 0, 0);
        
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;
        texture.repeat.set(0.8, 0.8); // Very minimal repetition for maximum spread
        return texture;
    }

    function createRoughnessTexture() {
        const size = 1024; // Increased size for less repetition
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        
        // Fill with uniform roughness
        context.fillStyle = 'rgb(110, 110, 110)';
        context.fillRect(0, 0, size, size);
        
        // Single subtle roughness variation streak
        const gradient1 = context.createLinearGradient(size * 0.1, 0, size * 0.9, size);
        gradient1.addColorStop(0, 'rgba(130, 130, 130, 0.0)');
        gradient1.addColorStop(0.4, 'rgba(120, 120, 120, 0.3)');
        gradient1.addColorStop(0.6, 'rgba(100, 100, 100, 0.5)');
        gradient1.addColorStop(1, 'rgba(130, 130, 130, 0.0)');
        
        context.fillStyle = gradient1;
        context.fillRect(0, 0, size, size);
        
        // Minimal organic variation
        const imageData = context.getImageData(0, 0, size, size);
        const data = imageData.data;
        
        for (let i = 0; i < data.length; i += 4) {
            const x = (i / 4) % size;
            const y = Math.floor((i / 4) / size);
            
            // Very subtle wave pattern
            const wave = Math.sin(x * 0.005) * Math.sin(y * 0.003) * 10;
            const randomNoise = (Math.random() - 0.5) * 15;
            const totalNoise = wave + randomNoise;
            
            const roughness = Math.floor(110 + totalNoise);
            
            data[i] = Math.max(0, Math.min(255, roughness));     // R
            data[i + 1] = Math.max(0, Math.min(255, roughness)); // G
            data[i + 2] = Math.max(0, Math.min(255, roughness)); // B
            data[i + 3] = 255;       // A
        }
        
        context.putImageData(imageData, 0, 0);
        
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;
        texture.repeat.set(0.7, 0.7); // Very minimal repetition for maximum spread
        return texture;
    }

    function createGoldMaterial(isMobile = false) {
        const metallicMap = createMetallicTexture();
        const roughnessMap = createRoughnessTexture();
        
        return new THREE.MeshPhysicalMaterial({
            color: 0xFFD700, // Gold color
            metalness: 0.75, // Reduced further for flatter look
            roughness: isMobile ? 0.55 : 0.45, // Increased significantly for flatter appearance
            clearcoat: isMobile ? 0.2 : 0.3, // Much reduced for minimal shine
            clearcoatRoughness: 0.3, // Increased for diffuse clearcoat
            reflectivity: isMobile ? 0.4 : 0.5, // Much reduced for flatter look
            envMapIntensity: isMobile ? 0.4 : 0.6, // Much reduced environment reflections
            ior: 1.5,
            metalnessMap: metallicMap,
            roughnessMap: roughnessMap
        });
    }

    function createSilverMaterial(isMobile = false) {
        const metallicMap = createMetallicTexture();
        const roughnessMap = createRoughnessTexture();
        
        return new THREE.MeshPhysicalMaterial({
            color: 0xC0C0C0, // Silver color
            metalness: 0.78, // Reduced further for flatter look
            roughness: isMobile ? 0.52 : 0.42, // Increased significantly for flatter appearance
            clearcoat: isMobile ? 0.25 : 0.35, // Much reduced for minimal shine
            clearcoatRoughness: 0.25, // Increased for diffuse clearcoat
            reflectivity: isMobile ? 0.45 : 0.55, // Much reduced for flatter look
            envMapIntensity: isMobile ? 0.5 : 0.7, // Much reduced environment reflections
            ior: 1.5,
            metalnessMap: metallicMap,
            roughnessMap: roughnessMap
        });
    }

    function createDiamondMaterial(isMobile = false) {
        const diamondTexture = createDiamondTexture();
        
        return new THREE.MeshPhysicalMaterial({
            color: 0xffffff, // Clear white
            metalness: 0,
            roughness: isMobile ? 0.03 : 0.01,
            transparent: true,
            opacity: 0.9,
            transmission: isMobile ? 0.8 : 0.95,
            ior: 2.42, // Diamond's refractive index
            clearcoat: isMobile ? 0.9 : 1.0,
            clearcoatRoughness: 0.01,
            thickness: 1.0,
            envMapIntensity: isMobile ? 1.5 : 2.5,
            reflectivity: 0.9,
            map: diamondTexture,
            normalMap: diamondTexture,
            normalScale: new THREE.Vector2(0.5, 0.5)
        });
    }

    function createWhiteEnvironmentTexture() {
        const size = 512;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        
        // Create gradient from white to light gray
        const gradient = context.createRadialGradient(size/2, size/2, 0, size/2, size/2, size/2);
        gradient.addColorStop(0, '#ffffff');
        gradient.addColorStop(0.5, '#f8f8f8');
        gradient.addColorStop(1, '#f0f0f0');
        
        context.fillStyle = gradient;
        context.fillRect(0, 0, size, size);
        
        const texture = new THREE.CanvasTexture(canvas);
        texture.mapping = THREE.EquirectangularReflectionMapping;
        return texture;
    }

    // 3D Jewelry Database
    const jewelryDatabase = {
        gem: {
            name: 'Gem Design',
            type: 'engagement',
            color: 0xffffff, // White
            stlFile: 'stl/gem.stl'
        },
        cadman3d: {
            name: 'Cadman Logo',
            type: 'logo',
            color: 0xFFD700, // Gold
            stlFile: '/stl/cadman3d.stl'
        },
        angel: {
            name: 'Angel Pendant',
            type: 'pendant',
            color: 0xC0C0C0, // Silver
            stlFile: 'stl/angelP.stl'
        },
        canadagames: {
            name: 'Canada Games',
            type: 'medallion',
            color: 0xFFD700, // Gold
            stlFile: 'stl/CanadaGames.stl'
        },
        snowflakes: {
            name: 'Snowflakes',
            type: 'pendant',
            color: 0xC0C0C0, // Silver
            stlFile: 'stl/planeflakes (1).stl'
        },
        manistate: {
            name: 'Manitoba Design',
            type: 'ring',
            color: 0xFFD700, // Gold
            stlFile: 'stl/manistate (1) (5) (3).stl'
        }
    };

    // Initialize 3D Scene
    function init3DViewer() {
        console.log('Starting 3D viewer initialization...');
        
        if (typeof THREE === 'undefined') {
            console.error('Three.js not loaded');
            showMobileError('3D library not loaded');
            return;
        }
        
        console.log('Three.js loaded, version:', THREE.REVISION);

        const canvas = document.getElementById('jewelry-canvas');
        
        if (!canvas) {
            console.error('Canvas not found');
            showMobileError('3D canvas element not found');
            return;
        }
        
        console.log('Canvas found:', canvas);

        try {
            // Test WebGL support
            const testCanvas = document.createElement('canvas');
            const gl = testCanvas.getContext('webgl') || testCanvas.getContext('experimental-webgl');
            
            if (!gl) {
                console.error('WebGL not supported');
                showMobileError('WebGL not supported on this device');
                return;
            }
            
            console.log('WebGL supported');

            // Scene setup with HDRI-like environment
            scene = new THREE.Scene();
            console.log('Scene created');
            
            // Create white environment with HDRI-like lighting
            const envGeometry = new THREE.SphereGeometry(50, 32, 16);
            const envMaterial = new THREE.MeshBasicMaterial({
                color: 0xffffff,
                side: THREE.BackSide
            });
            const envSphere = new THREE.Mesh(envGeometry, envMaterial);
            scene.add(envSphere);
            console.log('Environment sphere added');
            
            // Create environment texture for reflections
            const envTexture = createWhiteEnvironmentTexture();
            scene.environment = envTexture;
            scene.background = new THREE.Color(0xf8f8f8); // Light gray background
            console.log('Environment texture set');

            // Camera setup
            camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
            camera.position.set(0, 0, 5);
            console.log('Camera created');

            // Renderer setup
            renderer = new THREE.WebGLRenderer({ 
                canvas: canvas, 
                antialias: true,
                alpha: true
            });
            
            renderer.setSize(canvas.clientWidth, canvas.clientHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            console.log('Renderer created and configured');

            // Advanced lighting setup for realistic materials
            const isMobile = window.innerWidth <= 768;
            console.log('Mobile detected:', isMobile);
            
            // Soft ambient lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, isMobile ? 0.306 : 0.2295);
            scene.add(ambientLight);

            // Main directional light (key light) with shadows
            const keyLight = new THREE.DirectionalLight(0xffffff, isMobile ? 0.612 : 0.765);
            keyLight.position.set(10, 10, 5);
            keyLight.castShadow = true;
            keyLight.shadow.mapSize.width = isMobile ? 1024 : 2048;
            keyLight.shadow.mapSize.height = isMobile ? 1024 : 2048;
            keyLight.shadow.camera.near = 0.5;
            keyLight.shadow.camera.far = 50;
            keyLight.shadow.camera.left = -10;
            keyLight.shadow.camera.right = 10;
            keyLight.shadow.camera.top = 10;
            keyLight.shadow.camera.bottom = -10;
            keyLight.shadow.bias = -0.0001;
            keyLight.shadow.radius = isMobile ? 4 : 8;
            scene.add(keyLight);

            // Fill light from opposite side
            const fillLight = new THREE.DirectionalLight(0xffffff, isMobile ? 0.2295 : 0.306);
            fillLight.position.set(-5, 5, -5);
            scene.add(fillLight);

            // Rim light for edge definition
            const rimLight = new THREE.DirectionalLight(0xffffff, isMobile ? 0.153 : 0.2295);
            rimLight.position.set(0, -5, 10);
            scene.add(rimLight);
            console.log('Lighting setup complete');

            // Add white floor with shadow receiving
            const floorGeometry = new THREE.PlaneGeometry(20, 20);
            const floorMaterial = new THREE.MeshPhysicalMaterial({
                color: 0xffffff,
                metalness: 0,
                roughness: 0.8,
                transparent: true,
                opacity: 0.3
            });
            const floor = new THREE.Mesh(floorGeometry, floorMaterial);
            floor.rotation.x = -Math.PI / 2;
            floor.position.y = -2;
            floor.receiveShadow = true;
            scene.add(floor);
            console.log('Floor added');

            console.log('3D Scene initialized successfully');
            
            // Hide loading indicator and show controls
            document.getElementById('loading-display').style.display = 'none';
            document.getElementById('controls-info').style.display = 'block';
            
            // Load default jewelry
            loadJewelry('cadman3d');
            
        } catch (error) {
            console.error('Error initializing 3D viewer:', error);
            console.error('Error stack:', error.stack);
            showMobileError('Failed to initialize 3D viewer: ' + error.message);
        }
    }

    // Mouse and touch interaction variables
    let isDragging = false;
    let previousMousePosition = { x: 0, y: 0 };
    let rotationSpeed = 0.01;

    // Mouse events
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('jewelry-canvas');
        if (!canvas) return;

        canvas.addEventListener('mousedown', (event) => {
            isDragging = true;
            previousMousePosition = {
                x: event.clientX,
                y: event.clientY
            };
        });

        canvas.addEventListener('mouseup', () => {
            isDragging = false;
        });

        canvas.addEventListener('mousemove', (event) => {
            if (!isDragging || !jewelryMesh) return;

            const deltaMove = {
                x: event.clientX - previousMousePosition.x,
                y: event.clientY - previousMousePosition.y
            };

            jewelryMesh.rotation.y += deltaMove.x * rotationSpeed;
            jewelryMesh.rotation.x += deltaMove.y * rotationSpeed;

            previousMousePosition = {
                x: event.clientX,
                y: event.clientY
            };
        });

        canvas.addEventListener('wheel', (event) => {
            event.preventDefault();
            if (!camera) return;
            
            const zoomSpeed = 0.1;
            camera.position.z += event.deltaY * 0.01 * zoomSpeed;
            camera.position.z = Math.max(2, Math.min(10, camera.position.z));
        });

        // Touch events for mobile
        let touchStartDistance = 0;
        let touchStartRotation = { x: 0, y: 0 };

        canvas.addEventListener('touchstart', (event) => {
            event.preventDefault();
            
            if (event.touches.length === 1) {
                isDragging = true;
                const rect = canvas.getBoundingClientRect();
                previousMousePosition = {
                    x: event.touches[0].clientX - rect.left,
                    y: event.touches[0].clientY - rect.top
                };
            } else if (event.touches.length === 2) {
                isDragging = false;
                const dx = event.touches[0].clientX - event.touches[1].clientX;
                const dy = event.touches[0].clientY - event.touches[1].clientY;
                touchStartDistance = Math.sqrt(dx * dx + dy * dy);
                
                if (jewelryMesh) {
                    touchStartRotation = {
                        x: jewelryMesh.rotation.x,
                        y: jewelryMesh.rotation.y
                    };
                }
            }
        });

        canvas.addEventListener('touchend', (event) => {
            event.preventDefault();
            isDragging = false;
        });

        canvas.addEventListener('touchcancel', (event) => {
            event.preventDefault();
            isDragging = false;
        });

        canvas.addEventListener('touchmove', (event) => {
            event.preventDefault();
            
            if (event.touches.length === 1 && isDragging && jewelryMesh) {
                const rect = canvas.getBoundingClientRect();
                const currentPosition = {
                    x: event.touches[0].clientX - rect.left,
                    y: event.touches[0].clientY - rect.top
                };
                
                const deltaMove = {
                    x: currentPosition.x - previousMousePosition.x,
                    y: currentPosition.y - previousMousePosition.y
                };

                jewelryMesh.rotation.y += deltaMove.x * rotationSpeed;
                jewelryMesh.rotation.x += deltaMove.y * rotationSpeed;

                previousMousePosition = currentPosition;
            } else if (event.touches.length === 2 && camera) {
                const dx = event.touches[0].clientX - event.touches[1].clientX;
                const dy = event.touches[0].clientY - event.touches[1].clientY;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (touchStartDistance > 0) {
                    const scale = distance / touchStartDistance;
                    camera.position.z /= scale;
                    camera.position.z = Math.max(2, Math.min(10, camera.position.z));
                    touchStartDistance = distance;
                }
            }
        });

        // Initialize 3D viewer when page loads
        setTimeout(init3DViewer, 500);
    });

    // Load jewelry function
    function loadJewelry(jewelryKey) {
        if (isLoadingJewelry) {
            console.log('Already loading jewelry, please wait...');
            return;
        }

        const jewelry = jewelryDatabase[jewelryKey];
        if (!jewelry) {
            console.error('Jewelry not found:', jewelryKey);
            return;
        }

        if (!scene) {
            console.error('3D scene not initialized');
            return;
        }

        isLoadingJewelry = true;
        console.log('Loading jewelry:', jewelry.name);

        // Remove existing jewelry
        if (jewelryMesh) {
            scene.remove(jewelryMesh);
            jewelryMesh = null;
        }

        // Show loading state
        updateLoadingDisplay('Loading ' + jewelry.name + '...');

        // Check if STLLoader is available
        if (!THREE.STLLoader) {
            console.error('STLLoader not available');
            showError('STL loader not available');
            isLoadingJewelry = false;
            return;
        }

        // Load STL file
        const loader = new THREE.STLLoader();
        console.log('Loading STL file:', jewelry.stlFile);
        loader.load(jewelry.stlFile, 
            function(geometry) {
                try {
                    const isMobile = window.innerWidth <= 768;
                    
                    // Create advanced material based on jewelry type and color
                    let material;
                    if (jewelry.color === 0xFFD700) { // Gold
                        material = createGoldMaterial(isMobile);
                    } else if (jewelry.color === 0xC0C0C0) { // Silver
                        material = createSilverMaterial(isMobile);
                    } else if (jewelry.color === 0xffffff) { // White/Diamond
                        material = createDiamondMaterial(isMobile);
                    } else {
                        // Fallback for other colors
                        material = new THREE.MeshPhongMaterial({ 
                            color: jewelry.color,
                            shininess: 100,
                            transparent: false
                        });
                    }

                    // Create mesh
                    jewelryMesh = new THREE.Mesh(geometry, material);
                    
                    // Center and scale the geometry
                    geometry.computeBoundingBox();
                    const center = geometry.boundingBox.getCenter(new THREE.Vector3());
                    geometry.translate(-center.x, -center.y, -center.z);

                    // Scale to fit
                    const size = geometry.boundingBox.getSize(new THREE.Vector3());
                    const maxDim = Math.max(size.x, size.y, size.z);
                    const scale = 2 / maxDim;
                    geometry.scale(scale, scale, scale);

                    // Add to scene
                    scene.add(jewelryMesh);
                    
                    console.log('Jewelry loaded successfully:', jewelry.name);
                    hideLoadingDisplay();
                    
                    // Start animation
                    animate();
                    
                } catch (error) {
                    console.error('Error creating jewelry mesh:', error);
                    showError('Error displaying jewelry model');
                } finally {
                    isLoadingJewelry = false;
                }
            },
            function(progress) {
                const percent = (progress.loaded / progress.total * 100) || 0;
                updateLoadingDisplay('Loading ' + jewelry.name + '... ' + Math.round(percent) + '%');
            },
            function(error) {
                console.error('Error loading STL file:', error);
                showError('Failed to load jewelry model: ' + jewelry.name);
                isLoadingJewelry = false;
            }
        );
    }

    // Animation loop
    function animate() {
        animationId = requestAnimationFrame(animate);
        
        // Auto-rotate jewelry
        if (jewelryMesh && !isLoadingJewelry) {
            jewelryMesh.rotation.y += 0.005;
        }
        
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    }

    // Utility functions
    function updateLoadingDisplay(message) {
        const loadingDiv = document.getElementById('loading-display');
        if (loadingDiv) {
            loadingDiv.style.display = 'block';
            const messageDiv = loadingDiv.querySelector('div:nth-child(2)');
            if (messageDiv) messageDiv.textContent = message;
        }
    }

    function hideLoadingDisplay() {
        const loadingDiv = document.getElementById('loading-display');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }

    function showError(message) {
        updateLoadingDisplay('Error: ' + message);
        setTimeout(hideLoadingDisplay, 3000);
    }

    function showMobileError(message) {
        const loadingDiv = document.getElementById('loading-display');
        if (loadingDiv) {
            loadingDiv.innerHTML = '<div style="color: #ff6b6b; font-weight: bold;">Error: ' + message + '</div>';
        }
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (camera && renderer) {
            const canvas = document.getElementById('jewelry-canvas');
            if (canvas) {
                camera.aspect = canvas.clientWidth / canvas.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(canvas.clientWidth, canvas.clientHeight);
            }
        }
    });
    </script>

    <script src="/js/search_modal.js?v=20260604_1" defer></script>

    <?php 
    include 'footer.php'; 
    renderFooter('about');
    ?>
</body>
</html>