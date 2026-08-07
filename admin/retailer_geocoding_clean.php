<?php
require_once 'auth.php';
requireAdmin();

// Handle coordinate and address updates
if ($_POST['action'] === 'update_retailer') {
    $retailerId = $_POST['retailer_id'];
    $lat = floatval($_POST['latitude']);
    $lng = floatval($_POST['longitude']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $phone = trim($_POST['phone']);
    
    // Load retailers data
    $retailersData = json_decode(file_get_contents('../retailers.json'), true);
    
    // Find and update the retailer
    $updated = false;
    foreach ($retailersData as &$retailer) {
        if ($retailer['id'] == $retailerId) {
            $retailer['lat'] = $lat;
            $retailer['lng'] = $lng;
            $retailer['name'] = $name;
            $retailer['address'] = $address;
            $retailer['city'] = $city;
            $retailer['province'] = $province;
            $retailer['phone'] = $phone;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents('../retailers.json', json_encode($retailersData, JSON_PRETTY_PRINT));
        logAdminAction('RETAILER_UPDATE', "Updated retailer: $name");
        $message = "Retailer updated successfully!";
    } else {
        $error = "Retailer not found.";
    }
}

// Load retailers data for display
$retailersData = json_decode(file_get_contents('../retailers.json'), true);
$incompleteRetailers = [];
$completedCount = 0;

foreach ($retailersData as $retailer) {
    if (empty($retailer['lat']) || empty($retailer['lng'])) {
        $incompleteRetailers[] = $retailer;
    } else {
        $completedCount++;
    }
}

$totalRetailers = count($retailersData);
$pendingCount = count($incompleteRetailers);
$progressPercent = $totalRetailers > 0 ? round(($completedCount / $totalRetailers) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Geocoding - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
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
    </style>
</head>
<body>
    <div class="geocoding-container">
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 style="color: #1e3c72; margin: 0;">🗺️ Retailer Geocoding Tool</h1>
                <a href="index.php" class="action-button secondary">← Back to Admin</a>
            </div>
            
            <div class="progress-info">
                <h3 style="color: #1e3c72; margin-bottom: 15px;">Geocoding Progress</h3>
                <div class="progress-stats">
                    <div class="progress-stat">
                        <div class="progress-number"><?php echo $totalRetailers; ?></div>
                        <div class="progress-label">Total Retailers</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number"><?php echo $completedCount; ?></div>
                        <div class="progress-label">Completed</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number"><?php echo $pendingCount; ?></div>
                        <div class="progress-label">Pending</div>
                    </div>
                    <div class="progress-stat">
                        <div class="progress-number"><?php echo $progressPercent; ?>%</div>
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

        <div class="main-grid">
            <div class="admin-card">
                <h3 style="color: #1e3c72;">Retailers Needing Geocoding (<?php echo count($incompleteRetailers); ?>)</h3>
                <input type="text" id="searchBox" class="search-bar" placeholder="Search retailers...">
                <div class="retailer-panel" id="retailerList">
                    <?php foreach ($incompleteRetailers as $retailer): ?>
                        <div class="retailer-item" onclick="selectRetailer(<?php echo htmlspecialchars(json_encode($retailer)); ?>)">
                            <div class="retailer-name"><?php echo htmlspecialchars($retailer['name']); ?></div>
                            <div class="retailer-address">
                                <?php echo htmlspecialchars($retailer['address']); ?><br>
                                <?php echo htmlspecialchars($retailer['city'] . ', ' . $retailer['province']); ?>
                            </div>
                            <span class="retailer-status status-missing">Coordinates Missing</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-card">
                <h3 style="color: #1e3c72;">Edit Retailer Information</h3>
                <div id="mapContainer" style="height: 300px; background: #f0f0f0; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; color: #666;">
                    Select a retailer to view map
                </div>
                
                <form id="editForm" method="POST" class="edit-form" style="display: none;">
                    <input type="hidden" name="action" value="update_retailer">
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
        let map = null;
        let marker = null;

        function selectRetailer(retailer) {
            currentRetailer = retailer;
            
            // Update form
            document.getElementById('retailerId').value = retailer.id;
            document.getElementById('retailerName').value = retailer.name;
            document.getElementById('retailerAddress').value = retailer.address;
            document.getElementById('retailerCity').value = retailer.city;
            document.getElementById('retailerProvince').value = retailer.province;
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
            const retailerItems = document.querySelectorAll('.retailer-item');
            
            retailerItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
