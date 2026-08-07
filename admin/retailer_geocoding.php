<?php
require_once 'auth.php';
requireAdmin();

// Handle session extension
if ($_POST['extend_session'] ?? false) {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    $_SESSION['session_start'] = time(); // Reset session start time
    logAdminAction('SESSION_EXTENDED');
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Geocoding - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <script src="session-manager.js"></script>
    <?php echo renderSessionScript(); ?>
    <style>
        /* Additional page-specific styles can go here if needed */
        .geocoding-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .progress-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .progress-stats {
            display: flex;
            gap: 20px;
            text-align: center;
        }
        
        .progress-stat {
            flex: 1;
        }
        
        .progress-number {
            font-size: 24px;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .progress-label {
            font-size: 12px;
            color: #666;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .retailer-panel {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .retailer-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .retailer-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .retailer-item.selected {
            border-color: #1e3c72;
            background: #e3f2fd;
        }
        
        .retailer-name {
            font-weight: bold;
            color: #1e3c72;
            margin-bottom: 5px;
        }
        
        .retailer-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .retailer-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        
        .status-missing {
            background: #ffeaa7;
            color: #b8860b;
        }
        
        .edit-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 2px solid #ddd;
        }
        
        .form-section h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .search-bar {
            width: 100%;
            padding: 12px;
            border: 2px solid #87ceeb;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .search-bar:focus {
            border-color: #1e3c72;
            outline: none;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="geocoding-container">
        <div class="admin-card">
            <div class="back-nav">
                <a href="index.php">← Back to Admin Portal</a>
                <a href="add_retailer.php" class="action-button" style="font-size: 12px; padding: 6px 12px; margin-left: 10px;">➕ Add New Retailer</a>
            </div>
            <h1 style="color: #1e3c72; margin: 0; text-align: center;">🗺️ Retailer Geocoding Tool</h1>
            
            <div class="progress-info">
                <h3 style="color: #1e3c72; margin-bottom: 15px;">Geocoding Progress</h3>
                <div class="progress-stats" id="progressStats">
                    <div class="progress-stat">
                        <div class="progress-number" id="totalCount">-</div>
                        <div class="progress-label">Total Retailers</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number" id="completedCount">-</div>
                        <div class="progress-label">Completed</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number" id="pendingCount">-</div>
                        <div class="progress-label">Pending</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number" id="progressPercent">-%</div>
                        <div class="progress-label">Progress</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div id="alertContainer"></div>

        <div class="main-grid">
            <div class="admin-card">
                <h3 style="color: #1e3c72;">Retailers Needing Geocoding (<span id="retailerCount">Loading...</span>)</h3>
                <input type="text" id="searchBox" class="search-bar" placeholder="Search retailers...">
                <div class="retailer-panel" id="retailerList">
                    <div style="text-align: center; padding: 20px; color: #666;">Loading retailers...</div>
                </div>
            </div>

            <div class="admin-card">
                <h3 style="color: #1e3c72;">Edit Retailer Information</h3>
                <div id="mapContainer" style="height: 300px; background: #f0f0f0; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; color: #666;">
                    Select a retailer to view map
                </div>
                
                <form id="editForm" class="edit-form" style="display: none;">
                    <input type="hidden" id="retailerId" name="retailer_id">
                    
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="retailerName">Name:</label>
                                <input type="text" id="retailerName" name="name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="retailerAddress">Address:</label>
                                <input type="text" id="retailerAddress" name="address" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="retailerCity">City:</label>
                                <input type="text" id="retailerCity" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="retailerProvince">Province:</label>
                                <input type="text" id="retailerProvince" name="province" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="retailerPostalCode">Postal Code:</label>
                                <input type="text" id="retailerPostalCode" name="postal_code" placeholder="A1A 1A1">
                            </div>
                            <div class="form-group">
                                <label for="retailerPhone">Phone:</label>
                                <input type="text" id="retailerPhone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Coordinates</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="latitude">Latitude:</label>
                                <input type="number" id="latitude" name="latitude" step="any" required>
                            </div>
                            <div class="form-group">
                                <label for="longitude">Longitude:</label>
                                <input type="number" id="longitude" name="longitude" step="any" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="action-button">💾 Save Changes</button>
                        <button type="button" class="action-button secondary" onclick="geocodeAddress()">🔍 Auto-Geocode</button>
                        <button type="button" class="action-button secondary" onclick="clearForm()">🔄 Clear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentRetailer = null;
        let allRetailers = [];

        // Load retailers when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadRetailers();
        });

        async function loadRetailers() {
            try {
                const response = await fetch('api/retailer_list.php');
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Failed to load retailers');
                }
                
                allRetailers = result.data.incomplete_retailers;
                
                // Update progress stats
                document.getElementById('totalCount').textContent = result.data.total_retailers;
                document.getElementById('completedCount').textContent = result.data.completed_count;
                document.getElementById('pendingCount').textContent = result.data.pending_count;
                document.getElementById('progressPercent').textContent = result.data.progress_percent + '%';
                document.getElementById('retailerCount').textContent = result.data.pending_count;
                
                // Render retailers list
                renderRetailersList(allRetailers);
                
            } catch (error) {
                console.error('Error loading retailers:', error);
                showAlert('Error loading retailers: ' + error.message, 'error');
            }
        }

        function renderRetailersList(retailers) {
            const container = document.getElementById('retailerList');
            
            if (retailers.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">No retailers need geocoding!</div>';
                return;
            }
            
            container.innerHTML = retailers.map(retailer => `
                <div class="retailer-item" onclick="selectRetailer(${JSON.stringify(retailer).replace(/"/g, '&quot;')})">
                    <div class="retailer-name">${escapeHtml(retailer.name)}</div>
                    <div class="retailer-address">
                        ${escapeHtml(retailer.address)}<br>
                        ${escapeHtml(retailer.city + ', ' + retailer.province)}
                    </div>
                    <span class="retailer-status status-missing">Coordinates Missing</span>
                </div>
            `).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        function selectRetailer(retailer) {
            currentRetailer = retailer;
            
            // Update form
            document.getElementById('retailerId').value = retailer.ID;
            document.getElementById('retailerName').value = retailer.name;
            document.getElementById('retailerAddress').value = retailer.address;
            document.getElementById('retailerCity').value = retailer.city;
            document.getElementById('retailerProvince').value = retailer.province;
            document.getElementById('retailerPostalCode').value = retailer.postal_code || '';
            document.getElementById('retailerPhone').value = retailer.phone || '';
            document.getElementById('latitude').value = retailer.lat || '';
            document.getElementById('longitude').value = retailer.lng || '';
            
            // Show form
            document.getElementById('editForm').style.display = 'block';
            
            // Highlight selected retailer
            document.querySelectorAll('.retailer-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.target.closest('.retailer-item').classList.add('selected');
            
            // Update map
            updateMap();
        }

        // Handle form submission with AJAX
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!currentRetailer) {
                showAlert('No retailer selected', 'error');
                return;
            }
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Disable form and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '⏳ Updating...';
            
            const formData = {
                retailer_id: document.getElementById('retailerId').value,
                latitude: document.getElementById('latitude').value,
                longitude: document.getElementById('longitude').value,
                name: document.getElementById('retailerName').value,
                address: document.getElementById('retailerAddress').value,
                city: document.getElementById('retailerCity').value,
                province: document.getElementById('retailerProvince').value,
                postal_code: document.getElementById('retailerPostalCode').value,
                phone: document.getElementById('retailerPhone').value
            };
            
            // Validate coordinates
            const lat = parseFloat(formData.latitude);
            const lng = parseFloat(formData.longitude);
            
            if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                showAlert('Please enter valid latitude (-90 to 90) and longitude (-180 to 180)', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                return;
            }
            
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
                
                const response = await fetch('api/retailer_update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Failed to update retailer');
                }
                
                showAlert(`Successfully updated ${result.retailer_name}!`, 'success');
                
                // Reload the retailers list to reflect changes
                await loadRetailers();
                
                // Clear the form
                clearForm();
                
            } catch (error) {
                if (error.name === 'AbortError') {
                    showAlert('Update timed out. Please try again.', 'error');
                } else {
                    console.error('Error updating retailer:', error);
                    showAlert('Error updating retailer: ' + error.message, 'error');
                }
            } finally {
                // Re-enable form
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });

        function updateMap() {
            const mapContainer = document.getElementById('mapContainer');
            if (currentRetailer && currentRetailer.lat && currentRetailer.lng) {
                mapContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <h4 style="color: #1e3c72; margin-bottom: 10px;">${currentRetailer.name}</h4>
                        <p style="color: #666; margin-bottom: 15px;">
                            ${currentRetailer.address}<br>
                            ${currentRetailer.city}, ${currentRetailer.province}
                        </p>
                        <p style="color: #999; font-size: 14px;">
                            Coordinates: ${currentRetailer.lat}, ${currentRetailer.lng}
                        </p>
                        <a href="https://www.google.com/maps?q=${currentRetailer.lat},${currentRetailer.lng}" 
                           target="_blank" class="action-button" style="font-size: 12px; padding: 8px 15px;">
                            🗺️ View on Google Maps
                        </a>
                    </div>
                `;
            } else if (currentRetailer) {
                mapContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <h4 style="color: #1e3c72; margin-bottom: 10px;">${currentRetailer.name}</h4>
                        <p style="color: #666; margin-bottom: 15px;">
                            ${currentRetailer.address}<br>
                            ${currentRetailer.city}, ${currentRetailer.province}
                        </p>
                        <p style="color: #b8860b; font-weight: bold;">⚠️ Coordinates not set</p>
                        <button onclick="geocodeAddress()" class="action-button" style="font-size: 12px; padding: 8px 15px;">
                            🔍 Find Coordinates
                        </button>
                    </div>
                `;
            }
        }

        async function geocodeAddress() {
            if (!currentRetailer) return;
            
            const address = `${currentRetailer.address}, ${currentRetailer.city}, ${currentRetailer.province}, Canada`;
            
            try {
                // Simple geocoding using a free service (you may want to use Google Maps API)
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    alert(`Found coordinates: ${lat}, ${lng}`);
                } else {
                    alert('Could not find coordinates for this address. Please enter them manually.');
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                alert('Error during geocoding. Please enter coordinates manually.');
            }
        }

        function clearForm() {
            document.getElementById('editForm').reset();
            document.getElementById('editForm').style.display = 'none';
            document.querySelectorAll('.retailer-item').forEach(item => {
                item.classList.remove('selected');
            });
            currentRetailer = null;
            document.getElementById('mapContainer').innerHTML = 'Select a retailer to view map';
        }

                // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredRetailers = allRetailers.filter(retailer => 
                retailer.name.toLowerCase().includes(searchTerm) ||
                retailer.address.toLowerCase().includes(searchTerm) ||
                retailer.city.toLowerCase().includes(searchTerm) ||
                retailer.province.toLowerCase().includes(searchTerm)
            );
            renderRetailersList(filteredRetailers);
        });

        function updateMap() {
            const mapContainer = document.getElementById('mapContainer');
            if (currentRetailer && currentRetailer.lat && currentRetailer.lng && 
                !(currentRetailer.lat == 50 && currentRetailer.lng == -100)) {
                mapContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <h4 style="color: #1e3c72; margin-bottom: 10px;">${escapeHtml(currentRetailer.name)}</h4>
                        <p style="color: #666; margin-bottom: 15px;">
                            ${escapeHtml(currentRetailer.address)}<br>
                            ${escapeHtml(currentRetailer.city + ', ' + currentRetailer.province)}
                        </p>
                        <p style="color: #999; font-size: 14px;">
                            Coordinates: ${currentRetailer.lat}, ${currentRetailer.lng}
                        </p>
                        <a href="https://www.google.com/maps?q=${currentRetailer.lat},${currentRetailer.lng}" 
                           target="_blank" class="action-button" style="font-size: 12px; padding: 8px 15px;">
                            🗺️ View on Google Maps
                        </a>
                    </div>
                `;
            } else if (currentRetailer) {
                mapContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <h4 style="color: #1e3c72; margin-bottom: 10px;">${escapeHtml(currentRetailer.name)}</h4>
                        <p style="color: #666; margin-bottom: 15px;">
                            ${escapeHtml(currentRetailer.address)}<br>
                            ${escapeHtml(currentRetailer.city + ', ' + currentRetailer.province)}
                        </p>
                        <p style="color: #b8860b; font-weight: bold;">⚠️ Coordinates not set</p>
                        <button onclick="geocodeAddress()" class="action-button" style="font-size: 12px; padding: 8px 15px;">
                            🔍 Find Coordinates
                        </button>
                    </div>
                `;
            }
        }

        async function geocodeAddress() {
            if (!currentRetailer) return;
            
            // Get current form values in case they've been edited
            const address = document.getElementById('retailerAddress').value;
            const city = document.getElementById('retailerCity').value;
            const province = document.getElementById('retailerProvince').value;
            const postalCode = document.getElementById('retailerPostalCode').value;
            
            if (!address || !city || !province) {
                showAlert('❌ Please fill in address, city, and province fields', 'error');
                return;
            }
            
            showAlert('🔍 Searching for coordinates...', 'success');
            
            try {
                const response = await fetch('api/geocode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        address: address,
                        city: city,
                        province: province,
                        postal_code: postalCode
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const coords = result.coordinates;
                    
                    document.getElementById('latitude').value = coords.lat.toFixed(6);
                    document.getElementById('longitude').value = coords.lng.toFixed(6);
                    
                    showAlert(`✅ Found coordinates: ${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)}`, 'success');
                    
                    // Update the retailer object for map display
                    currentRetailer.lat = coords.lat;
                    currentRetailer.lng = coords.lng;
                    updateMap();
                    
                    // Show additional info
                    if (result.full_address) {
                        showGeocodingSuccess(result.full_address, result.strategy, result.source);
                    }
                } else {
                    showAlert('❌ Could not find coordinates for this address', 'error');
                    showGeocodingFailure(result.strategies_tried, result.suggestions);
                }
                
            } catch (error) {
                console.error('Geocoding error:', error);
                showAlert('❌ Error during geocoding: ' + error.message, 'error');
                
                // Fallback to client-side geocoding
                showAlert('🔄 Trying alternative geocoding method...', 'success');
                await fallbackClientGeocode(address, city, province, postalCode);
            }
        }
        
        async function fallbackClientGeocode(address, city, province, postalCode) {
            // Fallback to client-side Nominatim if server-side fails
            const addressString = postalCode ? 
                `${address}, ${city}, ${province} ${postalCode}, Canada` : 
                `${address}, ${city}, ${province}, Canada`;
                
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressString)}&countrycodes=ca&limit=1`, {
                    headers: {
                        'User-Agent': 'Cadman-Manufacturing-Admin'
                    }
                });
                
                const data = await response.json();
                
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lng.toFixed(6);
                    
                    showAlert(`✅ Found coordinates (fallback): ${lat.toFixed(6)}, ${lng.toFixed(6)}`, 'success');
                    
                    currentRetailer.lat = lat;
                    currentRetailer.lng = lng;
                    updateMap();
                } else {
                    showAlert('❌ Could not find coordinates. Please enter them manually.', 'error');
                    showGeocodingTips();
                }
            } catch (error) {
                showAlert('❌ All geocoding methods failed. Please enter coordinates manually.', 'error');
                showGeocodingTips();
            }
        }
        
        function showGeocodingSuccess(fullAddress, strategy, source) {
            const info = document.createElement('div');
            info.className = 'geocoding-success';
            info.innerHTML = `
                <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 12px; margin-top: 10px;">
                    <h5 style="margin: 0 0 8px 0; color: #155724;">✅ Geocoding Successful</h5>
                    <p style="margin: 0 0 5px 0; font-size: 13px; color: #155724;"><strong>Found:</strong> ${escapeHtml(fullAddress)}</p>
                    <p style="margin: 0 0 5px 0; font-size: 12px; color: #155724;"><strong>Strategy:</strong> ${escapeHtml(strategy)}</p>
                    <p style="margin: 0; font-size: 12px; color: #155724;"><strong>Source:</strong> ${source}</p>
                </div>
            `;
            
            insertTemporaryMessage(info, 15000);
        }
        
        function showGeocodingFailure(strategiesTried, suggestions) {
            const info = document.createElement('div');
            info.className = 'geocoding-failure';
            info.innerHTML = `
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; padding: 12px; margin-top: 10px;">
                    <h5 style="margin: 0 0 8px 0; color: #721c24;">❌ Geocoding Failed</h5>
                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #721c24;">Tried ${strategiesTried.length} different address formats</p>
                    <div style="font-size: 12px; color: #721c24;">
                        <strong>Suggestions:</strong>
                        <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                            ${suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
            
            insertTemporaryMessage(info, 20000);
        }
        
        function insertTemporaryMessage(element, duration) {
            const formSection = document.querySelector('.form-section');
            const existingMessage = document.querySelector('.geocoding-success, .geocoding-failure, .geocoding-tips');
            if (existingMessage) {
                existingMessage.remove();
            }
            formSection.appendChild(element);
            
            setTimeout(() => {
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            }, duration);
        }

        function clearForm() {
            document.getElementById('editForm').reset();
            document.getElementById('editForm').style.display = 'none';
            document.querySelectorAll('.retailer-item').forEach(item => {
                item.classList.remove('selected');
            });
            currentRetailer = null;
            document.getElementById('mapContainer').innerHTML = 'Select a retailer to view map';
        }
    </script>
</body>
</html>
    </script>
</body>
</html>
