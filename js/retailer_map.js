        console.log('🚀 Retailer Map JavaScript loading...');
        
        // Global variables for retailer map functionality (using var for true global scope)
        var retailerMap;
        var currentSearchResults = [];
        var mapMarkers = [];
        var allRetailersCluster; // Make cluster group globally accessible
        var clusteringDisabled = false; // Track if clustering was manually disabled
        
        // Retailer locations data - Loaded dynamically from XML (using var for HTML onclick access)
        var retailerLocations = [];
        var retailerDataLoaded = false;
        
        console.log('🔧 Global variables declared with var for proper scope access');
        
        // Load retailer data from JSON/XML files
        async function loadRetailerData() {
            try {
                console.log('Loading retailers from API...');
                
                // Try to load from retailers API first with cache-busting
                const timestamp = Date.now();
                let response = await fetch(`retailers_api.php?_t=${timestamp}&v=2025.11.28`);
                if (!response.ok) {
                    throw new Error('Failed to load retailers_api.php: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Successfully loaded ' + data.length + ' retailers from database API');
                
                retailerLocations = data;
                retailerDataLoaded = true;
                
                // Update the retailer count display
                const countElement = document.getElementById('retailer-count');
                if (countElement) {
                    countElement.textContent = data.length;
                    console.log('Updated retailer count display to:', data.length);
                }
                
                // Initialize map with loaded data
                if (typeof L !== 'undefined') {
                    initializeLeafletMapWithData();
                }
                
                return data;
                
            } catch (error) {
                console.log('API failed, trying XML conversion:', error);
                
                // Fallback: try XML to JSON conversion with cache-busting
                try {
                    const xmlTimestamp = Date.now();
                    const response = await fetch(`xml_to_json.php?_t=${xmlTimestamp}`);
                    const data = await response.json();
                    console.log('XML fallback successful: ' + data.length + ' retailers');
                    
                    retailerLocations = data;
                    retailerDataLoaded = true;
                    
                    // Update the retailer count display
                    const countElement = document.getElementById('retailer-count');
                    if (countElement) {
                        countElement.textContent = data.length;
                    }
                    
                    if (typeof L !== 'undefined') {
                        initializeLeafletMapWithData();
                    }
                    
                    return data;
                    
                } catch (xmlError) {
                    console.error('Both API and XML failed:', xmlError);
                    
                    // Final fallback to sample data
                    retailerLocations = [
                        {
                            name: "Sample Retailer",
                            address: "123 Main Street, Toronto, ON M5H 2M5",
                            phone: "(416) 555-0123",
                            lat: 43.6532,
                            lng: -79.3832,
                            city: "Toronto",
                            province: "ON"
                        }
                    ];
                    retailerDataLoaded = true;
                    
                    const countElement = document.getElementById('retailer-count');
                    if (countElement) {
                        countElement.textContent = retailerLocations.length;
                    }
                    
                    if (typeof L !== 'undefined') {
                        initializeLeafletMapWithData();
                    }
                    
                    return retailerLocations;
                }
            }
        }

        // ================================
        // MAP CONTROL FUNCTIONS  
        // ================================
        
        // View All Canada - Toggles between clustered and individual marker views
        function viewAllCanada() {
            console.log('🇨🇦 View All Canada button clicked');
            
            try {
                // Check if map exists
                if (!retailerMap) {
                    console.error('❌ Map not initialized yet');
                    alert('Map is still loading, please wait a moment and try again.');
                    return;
                }
                
                console.log('✅ Map found, showing all of Canada');
                
                // Set view to show all of Canada  
                retailerMap.setView([56.0, -96.0], 4);
                console.log('✅ Map centered on Canada');
                
                if (!retailerDataLoaded || !mapMarkers || mapMarkers.length === 0) {
                    console.log('⚠️ No retailer data yet, showing Canada center');
                    return;
                }
                
                // Toggle between clustered and individual views
                if (clusteringDisabled) {
                    // Currently showing individual markers - switch to clustered view
                    console.log('🔄 Switching to clustered view');
                    restoreClustering();
                    
                    // Fit bounds with clustering  
                    if (allRetailersCluster && retailerMap.hasLayer(allRetailersCluster)) {
                        const bounds = allRetailersCluster.getBounds();
                        retailerMap.fitBounds(bounds, { 
                            padding: [30, 30], 
                            maxZoom: 5 
                        });
                        console.log('✅ Showing clustered view of Canada');
                    }
                    
                } else {
                    // Currently showing clustered view - switch to individual markers
                    console.log('✅ Found', mapMarkers.length, 'retailers, switching to individual markers');
                    
                    // Remove clustered layer
                    if (allRetailersCluster && retailerMap.hasLayer(allRetailersCluster)) {
                        retailerMap.removeLayer(allRetailersCluster);
                        console.log('✅ Removed clustering layer');
                    }
                    
                    // Add individual markers directly to map
                    let markerBounds = L.latLngBounds();
                    mapMarkers.forEach(function(marker) {
                        retailerMap.addLayer(marker);
                        markerBounds.extend(marker.getLatLng());
                    });
                    
                    console.log('✅ Added', mapMarkers.length, 'individual markers to map');
                    
                    // Fit bounds to show all markers
                    if (markerBounds.isValid()) {
                        retailerMap.fitBounds(markerBounds, { 
                            padding: [30, 30], 
                            maxZoom: 5 
                        });
                        console.log('✅ Fit bounds to show all individual markers');
                    }
                    
                    // Mark clustering as disabled
                    clusteringDisabled = true;
                }
                
                console.log('🎉 View All Canada completed -', clusteringDisabled ? 'individual markers' : 'clustered view');
                
            } catch (error) {
                console.error('❌ Error in viewAllCanada:', error);
                alert('Error showing map. Please refresh and try again.');
            }
        }

        // Function to restore clustering
        function restoreClustering() {
            if (!clusteringDisabled || !retailerMap || !allRetailersCluster) return;
            
            console.log('🔄 Restoring clustering at zoom level', retailerMap.getZoom());
            
            try {
                // Remove individual markers
                mapMarkers.forEach(function(marker) {
                    if (retailerMap.hasLayer(marker)) {
                        retailerMap.removeLayer(marker);
                    }
                });
                
                // Re-add the cluster layer
                retailerMap.addLayer(allRetailersCluster);
                clusteringDisabled = false;
                
                console.log('✅ Clustering restored successfully');
            } catch (error) {
                console.error('❌ Error restoring clustering:', error);
            }
        }
        
        // Legacy function name support
        function viewAllRetailers() {
            viewAllCanada();
        }
        
        // ================================
        // RETAILER MAP FUNCTIONS - LEAFLET IMPLEMENTATION
        // ================================
        
        // Show loading overlay on map
        function showMapLoading(message) {
            const mapContainer = document.getElementById('canada-map');
            if (!mapContainer) return;
            
            // Remove existing overlay if present
            const existing = document.getElementById('map-loading-overlay');
            if (existing) existing.remove();
            
            const overlay = document.createElement('div');
            overlay.id = 'map-loading-overlay';
            overlay.innerHTML = `
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.7);
                    backdrop-filter: blur(5px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                    border-radius: 15px;
                ">
                    <div style="
                        background: white;
                        padding: 30px 40px;
                        border-radius: 15px;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                        text-align: center;
                        max-width: 300px;
                    ">
                        <div class="loading-spinner" style="
                            width: 50px;
                            height: 50px;
                            border: 4px solid #f3f3f3;
                            border-top: 4px solid #28a745;
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                            margin: 0 auto 20px;
                        "></div>
                        <div style="color: #333; font-size: 16px; font-weight: 600; margin-bottom: 10px;">
                            ${message || 'Finding your location...'}
                        </div>
                        <div style="color: #666; font-size: 13px;">
                            Please wait
                        </div>
                    </div>
                </div>
            `;
            mapContainer.appendChild(overlay);
        }
        
        // Hide loading overlay
        function hideMapLoading() {
            const overlay = document.getElementById('map-loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.3s ease';
                setTimeout(() => overlay.remove(), 300);
            }
        }
        
        // Show toast notification on map
        function showMapNotification(title, message, type = 'info') {
            const mapContainer = document.getElementById('canada-map');
            if (!mapContainer) return;
            
            const colors = {
                success: { bg: '#28a745', border: '#1e7e34' },
                error: { bg: '#dc3545', border: '#bd2130' },
                info: { bg: '#0066CC', border: '#004499' }
            };
            
            const color = colors[type] || colors.info;
            
            const notification = document.createElement('div');
            notification.className = 'map-notification';
            notification.innerHTML = `
                <div style="
                    position: absolute;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: ${color.bg};
                    color: white;
                    padding: 15px 25px;
                    border-radius: 10px;
                    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
                    z-index: 1001;
                    max-width: 90%;
                    text-align: center;
                    animation: slideDown 0.3s ease;
                    border: 2px solid ${color.border};
                ">
                    <div style="font-weight: bold; font-size: 15px; margin-bottom: 5px;">${title}</div>
                    <div style="font-size: 13px; opacity: 0.95;">${message}</div>
                </div>
            `;
            
            mapContainer.appendChild(notification);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Find nearest retailers using geolocation
        function findnearme() {
            console.log('📍 Finding nearest retailer...');
            
            try {
                // Check if we have retailer data first
                if (!retailerDataLoaded || !retailerLocations || retailerLocations.length === 0) {
                    alert('Retailer data is still loading. Please wait a moment and try again.');
                    return;
                }
                
                // Check if page is served over HTTPS (required for geolocation)
                if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                    alert('🔒 Location services require a secure connection.\n\nPlease use our search to find retailers in your area.');
                    return;
                }
                
                // Check geolocation support
                if (!navigator.geolocation) {
                    alert('🚫 Your browser doesn\'t support location services.\n\nPlease use our search to find retailers in your area.');
                    return;
                }
                
                // Show loading overlay
                showMapLoading('📍 Finding your location...');
                
                // Request geolocation
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        console.log('✅ Got location:', userLat, userLng);
                        
                        // Calculate distances to all retailers
                        const retailersWithDistance = retailerLocations.map(retailer => {
                            const distance = calculateDistance(userLat, userLng, retailer.lat, retailer.lng);
                            return {
                                ...retailer,
                                distance: distance
                            };
                        });
                        
                        // Sort by distance and get nearest
                        retailersWithDistance.sort((a, b) => a.distance - b.distance);
                        const nearest = retailersWithDistance[0];
                        
                        console.log('🎯 Nearest retailer:', nearest.name, 'Distance:', nearest.distance.toFixed(2), 'km');
                        
                        // Hide loading overlay
                        hideMapLoading();
                        
                        // Center map on user's location
                        if (retailerMap) {
                            retailerMap.setView([userLat, userLng], 10);
                            
                            // Add a marker for user's location
                            const userMarker = L.marker([userLat, userLng], {
                                icon: L.divIcon({
                                    className: 'user-location-marker',
                                    html: '<div style="background: #4285F4; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"></div>',
                                    iconSize: [20, 20],
                                    iconAnchor: [10, 10]
                                })
                            }).addTo(retailerMap);
                            
                            userMarker.bindPopup('<div style="text-align: center; padding: 10px;"><strong>📍 Your Location</strong></div>');
                            
                            // Find and open the nearest retailer's popup
                            mapMarkers.forEach(marker => {
                                const markerPos = marker.getLatLng();
                                if (Math.abs(markerPos.lat - nearest.lat) < 0.0001 && 
                                    Math.abs(markerPos.lng - nearest.lng) < 0.0001) {
                                    // Open popup with enhanced content showing distance
                                    marker.openPopup();
                                }
                            });
                            
                            // Show success toast notification
                            showMapNotification(
                                '✅ Nearest Retailer Found!',
                                `${nearest.name}<br>${nearest.distance.toFixed(2)} km away`,
                                'success'
                            );
                        }
                    },
                    (error) => {
                        console.error('❌ Geolocation error:', error);
                        
                        // Hide loading overlay
                        hideMapLoading();
                        
                        let errorMsg = 'Location access failed. ';
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg += 'You denied location access.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg += 'Location information unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMsg += 'Location request timed out.';
                                break;
                            default:
                                errorMsg += error.message;
                        }
                        
                        alert(errorMsg + '\n\nPlease use the search to find retailers in your area.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 300000
                    }
                );
                
            } catch (error) {
                console.error('Error finding nearest retailer:', error);
                alert('An error occurred while finding the nearest retailer. Please try again or use the search function.');
            }
        }
        
        // Calculate distance between coordinates (Haversine formula)
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Earth's radius in kilometers
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                     Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // ================================
        // LEAFLET MAP IMPLEMENTATION (NO API KEY REQUIRED)
        // ================================
        
        // Initialize Leaflet Map (No API Key Required) - Now loads data dynamically
        function initializeLeafletMap() {
            // Load retailer data first, map will be initialized from loadRetailerData
            loadRetailerData();
        }
        
        // Initialize Leaflet Map with loaded retailer data
        function initializeLeafletMapWithData() {
            if (!retailerDataLoaded) {
                console.log('Waiting for retailer data to load...');
                return;
            }
            
            try {
                // Initialize map centered on Canada with Cadman-inspired configuration
                retailerMap = L.map('canada-map', {
                    center: [56.0, -96.0], // Adjust center to better show all of Canada
                    zoom: 4, // Zoom out to show all of Canada in frame
                    zoomControl: true,
                    scrollWheelZoom: true,  // Enable scroll zoom for better user experience
                    attributionControl: false, // Remove attribution for cleaner appearance (like Cadman)
                    doubleClickZoom: true,
                    touchZoom: true,
                    maxZoom: 18              // Prevent over-zooming (like Cadman)
                });

                // Add CartoDB tile layer (same as Cadman Manufacturing uses)
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    subdomains: 'abcd'
                }).addTo(retailerMap);

                // Add zoom event listener to restore clustering when zooming in
                retailerMap.on('zoomend', function() {
                    const currentZoom = retailerMap.getZoom();
                    // If clustering was disabled by "View All Canada" and user zooms in beyond level 6, restore clustering
                    if (clusteringDisabled && currentZoom > 6) {
                        restoreClustering();
                    }
                });

                // Create custom shield icon matching Cadman's logo crest design using SVG
                // Create custom icon using logo.png as the map pin with enhanced styling
                const customIcon = L.icon({
                    iconUrl: 'PNG/logo.png',
                    iconSize: [35, 50], // Slightly larger for better visibility
                    iconAnchor: [17, 50], // Bottom center of the icon
                    popupAnchor: [0, -50], // Position popup above the icon
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    shadowSize: [50, 64],
                    shadowAnchor: [4, 62],
                    className: 'cadman-marker' // Add custom class for styling
                });

                // Add markers for each retailer from loaded data
                console.log('Adding', retailerLocations.length, 'retailer markers to map');
                
                // Create a single marker cluster group with province-aware clustering
                allRetailersCluster = L.markerClusterGroup({
                    disableClusteringAtZoom: 10, // Show individual markers at zoom 10 and higher (was 13)
                    maxClusterRadius: function(zoom) {
                        // Improved clustering radius for better "View All Canada" experience
                        if (zoom <= 3) return 60;       // Very tight clustering at country level
                        if (zoom <= 4) return 80;       // Small clusters to show more individual markers
                        if (zoom <= 6) return 100;      // Regional clustering
                        if (zoom <= 8) return 80;       // City-area clustering
                        if (zoom <= 10) return 60;      // Town clustering
                        return 40;                       // Tight clustering before individual markers
                    },
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    spiderfyDistanceMultiplier: 1.2,
                    iconCreateFunction: function(cluster) {
                        const count = cluster.getChildCount();
                        
                        return L.divIcon({
                            html: `<div class="cadman-cluster-icon" style="
                                position: relative;
                                width: 35px; 
                                height: 50px;
                                background-image: url('PNG/logo.png'); 
                                background-size: contain; 
                                background-repeat: no-repeat; 
                                background-position: center;
                                filter: drop-shadow(0 3px 6px rgba(0,0,0,0.3));
                            ">
                                <span style="
                                    position: absolute;
                                    top: -8px;
                                    right: -8px;
                                    background: #dc2626;
                                    color: white;
                                    border-radius: 50%;
                                    width: 22px;
                                    height: 22px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 11px;
                                    font-weight: bold;
                                    border: 2px solid white;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                ">${count}</span>
                            </div>`,
                            className: 'cadman-cluster-wrapper',
                            iconSize: [35, 50],
                            iconAnchor: [17, 50]
                        });
                    }
                });
                
                // Add all retailers to the single cluster group
                retailerLocations.forEach(retailer => {
                    // Skip retailers with default coordinates (50, -100)
                    if (retailer.lat == 50 && retailer.lng == -100) {
                        console.log('Skipping retailer with default coordinates:', retailer.name);
                        return;
                    }
                    
                    const marker = L.marker([retailer.lat, retailer.lng], { icon: customIcon })
                        .bindPopup(`
                            <div style="padding: 15px; min-width: 280px; font-family: 'Segoe UI', sans-serif;">
                                <h4 style="margin: 0 0 12px 0; color: #0066CC; font-size: 18px; font-weight: 600;">${retailer.name}</h4>
                                <div style="margin-bottom: 8px;">
                                    <span style="color: #666; font-size: 14px;">📍 ${retailer.address}</span>
                                </div>
                                ${retailer.phone ? `<div style="margin-bottom: 8px;">
                                    <span style="color: #666; font-size: 14px;">📞 ${retailer.phone}</span>
                                </div>` : ''}
                                <div style="margin-bottom: 12px;">
                                    <span style="color: #999; font-size: 13px; font-style: italic;">${retailer.city}, ${retailer.province}</span>
                                </div>
                                <div style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 12px; display: flex; gap: 8px;">
                                    <a href="https://www.google.com/maps/dir//${encodeURIComponent(retailer.address)}" 
                                       target="_blank" 
                                       style="background: linear-gradient(145deg, #0066CC, #004499); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,102,204,0.3);">
                                        🗺️ Get Directions
                                    </a>
                                    ${retailer.phone ? `<a href="tel:${retailer.phone}" 
                                       style="background: linear-gradient(145deg, #28a745, #1e7e34); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(40,167,69,0.3);">
                                        📞 Call
                                    </a>` : ''}
                                </div>
                            </div>
                        `);
                
                    allRetailersCluster.addLayer(marker);
                    mapMarkers.push(marker);
                });
                
                // Add the cluster group to the map
                retailerMap.addLayer(allRetailersCluster);
                
                console.log('Added', mapMarkers.length, 'valid retailers to map with dynamic zoom-based clustering');

                // Set custom view instead of fitBounds to maintain our zoom settings
                const canadaBounds = L.latLngBounds([
                    [41.0, -141.001], // Southwest corner - extended to ensure all Maritime provinces are accessible
                    [83.114, -52.648]   // Northeast corner
                ]);
                
                // Use setView to maintain our custom zoom and center, but ensure all of Canada is accessible
                retailerMap.setMaxBounds(canadaBounds);
                retailerMap.setView([56.0, -96.0], 4); // Show all of Canada in frame
                
                // Initialize scroll zoom toggle button state
                const toggleButton = document.getElementById('scroll-zoom-toggle');
                if (toggleButton && retailerMap.scrollWheelZoom.enabled()) {
                    toggleButton.style.background = 'rgba(200, 255, 200, 0.9)';
                    toggleButton.style.borderColor = '#66cc66';
                }
                
            } catch (error) {
                showLeafletFallback();
            }
        }

        // Fallback message if Leaflet map fails to load
        function showLeafletFallback() {
            const mapContainer = document.getElementById('canada-map');
            if (mapContainer) {
                mapContainer.innerHTML = `
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 100%;
                        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                        border-radius: 15px;
                        flex-direction: column;
                        text-align: center;
                        padding: 40px;
                    ">
                        <div style="font-size: 3em; margin-bottom: 20px;">🗺️</div>
                        <p style="color: #666; margin-bottom: 20px;">Map temporarily unavailable</p>
                        <a href="https://www.openstreetmap.org/#map=4/60/-95" target="_blank" 
                           style="background: #0066CC; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
                            View on OpenStreetMap
                        </a>
                    </div>
                `;
            }
        }

        // Updated initialization to use Leaflet instead of Google Maps
        function initMapWhenReady() {
            // Wait a bit for Leaflet to be fully loaded
            if (typeof L !== 'undefined') {
                initializeLeafletMap();
            } else {
                setTimeout(() => {
                    if (typeof L !== 'undefined') {
                        initializeLeafletMap();
                    } else {
                        showLeafletFallback();
                    }
                }, 1000);
            }
        }
        
        // ---- Lazy-load Leaflet when the map section enters the viewport ----
        (function() {
            var mapSection = document.getElementById('map-placeholder') ||
                             document.getElementById('canada-map');
            if (!mapSection) return;

            var leafletLoaded = false;

            function loadLeafletAndInit() {
                if (leafletLoaded) return;
                leafletLoaded = true;

                // Reveal the real map div, hide placeholder
                var placeholder = document.getElementById('map-placeholder');
                var mapDiv = document.getElementById('canada-map');
                if (placeholder) placeholder.style.display = 'none';
                if (mapDiv) mapDiv.style.display = '';

                // Load Leaflet CSS
                var lCSS = document.createElement('link');
                lCSS.rel = 'stylesheet';
                lCSS.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css';
                document.head.appendChild(lCSS);

                // Load MarkerCluster CSS
                var mcCSS1 = document.createElement('link');
                mcCSS1.rel = 'stylesheet';
                mcCSS1.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css';
                document.head.appendChild(mcCSS1);

                var mcCSS2 = document.createElement('link');
                mcCSS2.rel = 'stylesheet';
                mcCSS2.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css';
                document.head.appendChild(mcCSS2);

                // Load Leaflet JS, then MarkerCluster JS, then init map
                var lJS = document.createElement('script');
                lJS.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
                lJS.onload = function() {
                    var mcJS = document.createElement('script');
                    mcJS.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js';
                    mcJS.onload = function() {
                        initializeLeafletMap();
                    };
                    document.head.appendChild(mcJS);
                };
                document.head.appendChild(lJS);
            }
            // Expose globally so inline onclick handlers can call it
            window.loadLeafletAndInit = loadLeafletAndInit;

            var isMobileDevice = window.innerWidth <= 768 ||
                /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

            if (isMobileDevice) {
                // On mobile: show a tap-to-load button to save ~200 KB of JS + tile traffic
                var placeholder = document.getElementById('map-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:14px;">' +
                        '<div style="font-size:2.8em;">🗺️</div>' +
                        '<div style="color:#444;font-size:0.95em;text-align:center;padding:0 20px;">Interactive retailer map</div>' +
                        '<button onclick="(function(){this.disabled=true;this.textContent=\'Loading…\';loadLeafletAndInit();}).call(this)" ' +
                        'style="background:linear-gradient(145deg,#0066CC,#004499);color:#fff;border:none;padding:10px 22px;border-radius:8px;font-size:0.95em;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,102,204,0.35);">' +
                        '📍 Load Map</button>' +
                        '<div style="color:#999;font-size:0.75em;">Or <a href="/retailers.php" style="color:#0066CC;">view full retailer list</a></div>' +
                        '</div>';
                }
            } else if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    if (entries[0].isIntersecting) {
                        observer.disconnect();
                        loadLeafletAndInit();
                    }
                }, { rootMargin: '200px' });
                observer.observe(mapSection);
            } else {
                // Fallback for old browsers: load after page load
                window.addEventListener('load', loadLeafletAndInit);
            }
        })();
        
        // ================================
        // SEARCH FUNCTIONALITY
        // ================================
        // Search modal functions are now loaded from js/search_modal.js
        
        // Toggle scroll zoom functionality
        function toggleScrollZoom() {
            const toggleButton = document.getElementById('scroll-zoom-toggle');
            
            if (retailerMap.scrollWheelZoom.enabled()) {
                // Disable scroll zoom
                retailerMap.scrollWheelZoom.disable();
                toggleButton.innerHTML = '🖱️ Scroll Zoom: OFF';
                toggleButton.style.background = 'rgba(255, 200, 200, 0.9)';
                toggleButton.style.borderColor = '#ff6666';
                console.log('Scroll zoom disabled');
            } else {
                // Enable scroll zoom
                retailerMap.scrollWheelZoom.enable();
                toggleButton.innerHTML = '🖱️ Scroll Zoom: ON';
                toggleButton.style.background = 'rgba(200, 255, 200, 0.9)';
                toggleButton.style.borderColor = '#66cc66';
                console.log('Scroll zoom enabled');
            }
        }
        
        // Verify functions are loaded
        console.log('✅ Retailer Map JavaScript loaded successfully');
        console.log('✅ Available functions:', { 
            viewAllCanada: typeof viewAllCanada, 
            viewAllRetailers: typeof viewAllRetailers,
            findnearme: typeof findnearme,
            restoreClustering: typeof restoreClustering,
            calculateDistance: typeof calculateDistance
        });
        
        // Test function execution
        console.log('🔧 Testing findnearme function availability...');
        if (typeof findnearme === 'function') {
            console.log('✅ findnearme is properly defined as a function');
            
            // Test manual execution
            window.testFindNearest = function() {
                console.log('🧪 MANUAL TEST: Calling findnearme directly');
                findnearme();
            };
            console.log('🧪 Added window.testFindNearest() for manual testing');
            
        } else {
            console.error('❌ findnearme is NOT defined as a function, type is:', typeof findnearme);
        }
        
        // Make functions globally accessible BEFORE any potential calls
        window.findnearme = findnearme;
        window.viewAllCanada = viewAllCanada;
        
        // Also ensure they're accessible in global scope
        if (typeof window !== 'undefined') {
            window.retailerDataLoaded = function() { return retailerDataLoaded; };
            window.getRetailerLocations = function() { return retailerLocations; };
        }
        
        console.log('🌍 Functions and data accessors made globally accessible');
        console.log('✅ Scope verification:', {
            findnearme: typeof findnearme,
            viewAllCanada: typeof viewAllCanada,
            retailerDataLoaded: typeof retailerDataLoaded,
            retailerLocations: typeof retailerLocations
        });
