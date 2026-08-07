<?php
require_once 'auth.php';
requireAdmin();

// Handinate and address updates
if ($_POST['action'] === 'update_retailer') {
    $retailerId = $_POST['retailer_id'];
    $lat = floatval($_POST['latitude']);
    $lng = floatval($_POST['longitude']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $phone = trim($_POST['phone']);
    
    // Load current retailers
    $retailers = json_decode(file_get_contents('../retailers.json'), true);
    
    // Find and update the retailer
    foreach ($retailers as &$retailer) {
        if ($retailer['ID'] === $retailerId) {
            $retailer['lat'] = $lat;
            $retailer['lng'] = $lng;
            $retailer['name'] = $name;
            $retailer['address'] = $address;
            $retailer['city'] = $city;
            $retailer['province'] = $province;
            $retailer['phone'] = $phone;
            break;
        }
    }
    
    // Save updated data
    file_put_contents('../retailers.json', json_encode($retailers, JSON_PRETTY_PRINT));
    
    logAdminAction('RETAILER_UPDATE', "Updated retailer ID: $retailerId - coordinates: $lat, $lng - address: $name, $address, $city, $province");
    
    echo json_encode(['success' => true, 'message' => 'Retailer information updated successfully']);
    exit;
}

// Load retailers with default coordinates
$retailers = json_decode(file_get_contents('../retailers.json'), true);
$remainingRetailers = [];
foreach ($retailers as $retailer) {
    if ($retailer['lat'] == 50 && $retailer['lng'] == -100) {
        $remainingRetailers[] = $retailer;
    }
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
    <style>
        /* Additional page-specific styles can go here if needed */
    </style>
</head>


        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B008B, #FF69B4);
            transition: width 0.3s ease;
        }
        
        .retailer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .retailer-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .retailer-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .retailer-name {
            font-size: 18px;
            font-weight: bold;
            color: #8B008B;
            margin-bottom: 5px;
        }
        
        .retailer-info {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .editable-fields {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .field-group {
            margin-bottom: 10px;
        }
        
        .field-label {
            display: block;
            font-weight: bold;
            color: #495057;
            margin-bottom: 3px;
            font-size: 12px;
        }
        
        .field-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .coordinate-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .search-section {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .search-button {
            background: #8B008B;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .search-button:hover {
            background: #6B006B;
        }
        
        .update-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .update-button:hover {
            background: #218838;
        }
        
        .update-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .skip-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .status-message {
            margin-top: 10px;
            padding: 8px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="geocoding-container">
        <div class="header-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 style="color: #8B008B; margin: 0;">🗺️ Retailer Geocoding Tool</h1>
                <a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Admin</a>
            </div>
            
            <p style="color: #666; margin-bottom: 20px;">
                Manually geocode the remaining <?php echo count($remainingRetailers); ?> retailers that couldn't be automatically located.
                Use the search tools below to find coordinates for each retailer.
            </p>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo round((87 / 144) * 100, 1); ?>%"></div>
            </div>
            <div style="text-align: center; color: #666; font-size: 14px;">
                Progress: 87 of 144 retailers geocoded (<?php echo round((87 / 144) * 100, 1); ?>%)
            </div>
        </div>
        
        <div class="retailer-grid">
            <?php foreach ($remainingRetailers as $index => $retailer): ?>
            <div class="retailer-card" id="retailer-<?php echo $retailer['ID']; ?>">
                <div class="retailer-header">
                    <div class="retailer-name"><?php echo htmlspecialchars($retailer['name']); ?></div>
                    <div class="retailer-info">
                        <strong>Current Address:</strong> <?php echo htmlspecialchars($retailer['address']); ?><br>
                        <strong>City:</strong> <?php echo htmlspecialchars($retailer['city']); ?><br>
                        <strong>Province:</strong> <?php echo htmlspecialchars($retailer['province']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($retailer['phone']); ?>
                    </div>
                </div>
                
                <div class="editable-fields">
                    <h4 style="margin: 0 0 15px 0; color: #8B008B;">Edit All Information:</h4>
                    
                    <div class="field-group">
                        <label class="field-label">Business Name:</label>
                        <input type="text" class="field-input" id="name-<?php echo $retailer['ID']; ?>" 
                               value="<?php echo htmlspecialchars($retailer['name']); ?>" />
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Street Address:</label>
                        <input type="text" class="field-input" id="address-<?php echo $retailer['ID']; ?>" 
                               value="<?php echo htmlspecialchars($retailer['address']); ?>" />
                    </div>
                    
                    <div class="coordinate-row">
                        <div class="field-group">
                            <label class="field-label">City:</label>
                            <input type="text" class="field-input" id="city-<?php echo $retailer['ID']; ?>" 
                                   value="<?php echo htmlspecialchars($retailer['city']); ?>" />
                        </div>
                        <div class="field-group">
                            <label class="field-label">Province:</label>
                            <input type="text" class="field-input" id="province-<?php echo $retailer['ID']; ?>" 
                                   value="<?php echo htmlspecialchars($retailer['province']); ?>" />
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Phone Number:</label>
                        <input type="text" class="field-input" id="phone-<?php echo $retailer['ID']; ?>" 
                               value="<?php echo htmlspecialchars($retailer['phone']); ?>" />
                    </div>
                    
                    <div class="coordinate-row">
                        <div class="field-group">
                            <label class="field-label">Latitude:</label>
                            <input type="number" step="any" class="field-input" id="lat-<?php echo $retailer['ID']; ?>" 
                                   placeholder="e.g. 43.6532" />
                        </div>
                        <div class="field-group">
                            <label class="field-label">Longitude:</label>
                            <input type="number" step="any" class="field-input" id="lng-<?php echo $retailer['ID']; ?>" 
                                   placeholder="e.g. -79.3832" />
                        </div>
                    </div>
                </div>
                
                <div class="search-section">
                    <input type="text" class="search-input" id="search-<?php echo $retailer['ID']; ?>" 
                           placeholder="Search: <?php echo htmlspecialchars($retailer['name']); ?> <?php echo htmlspecialchars($retailer['city']); ?>" 
                           value="<?php echo htmlspecialchars($retailer['name'] . ' ' . $retailer['city'] . ' ' . $retailer['province']); ?>">
                    
                    <button class="search-button" onclick="searchGoogle('<?php echo $retailer['ID']; ?>')">🔍 Google Search</button>
                    <button class="search-button" onclick="searchMaps('<?php echo $retailer['ID']; ?>')">🗺️ Google Maps</button>
                    <button class="search-button" onclick="searchOSM('<?php echo $retailer['ID']; ?>')">🌍 OpenStreetMap</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button class="update-button" style="flex: 1;" onclick="updateRetailer('<?php echo $retailer['ID']; ?>')">💾 Save All Changes</button>
                    <button class="skip-button" onclick="skipRetailer('<?php echo $retailer['ID']; ?>')">Skip This Retailer</button>
                </div>
                
                <div class="status-message" id="status-<?php echo $retailer['ID']; ?>" style="display: none;"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function searchGoogle(retailerId) {
            const searchTerm = document.getElementById('search-' + retailerId).value;
            const url = 'https://www.google.com/search?q=' + encodeURIComponent(searchTerm + ' jewelry store');
            window.open(url, '_blank');
        }
        
        function searchMaps(retailerId) {
            const searchTerm = document.getElementById('search-' + retailerId).value;
            const url = 'https://www.google.com/maps/search/' + encodeURIComponent(searchTerm);
            window.open(url, '_blank');
        }
        
        function searchOSM(retailerId) {
            const searchTerm = document.getElementById('search-' + retailerId).value;
            const url = 'https://www.openstreetmap.org/search?query=' + encodeURIComponent(searchTerm);
            window.open(url, '_blank');
        }
        
        function updateRetailer(retailerId) {
            const name = document.getElementById('name-' + retailerId).value.trim();
            const address = document.getElementById('address-' + retailerId).value.trim();
            const city = document.getElementById('city-' + retailerId).value.trim();
            const province = document.getElementById('province-' + retailerId).value.trim();
            const phone = document.getElementById('phone-' + retailerId).value.trim();
            const lat = document.getElementById('lat-' + retailerId).value;
            const lng = document.getElementById('lng-' + retailerId).value;
            
            // Validate required fields
            if (!name || !address || !city || !province) {
                showStatus(retailerId, 'Please fill in all required fields (name, address, city, province)', 'error');
                return;
            }
            
            if (!lat || !lng) {
                showStatus(retailerId, 'Please enter both latitude and longitude coordinates', 'error');
                return;
            }
            
            // Validate coordinate ranges
            if (lat < -90 || lat > 90) {
                showStatus(retailerId, 'Latitude must be between -90 and 90', 'error');
                return;
            }
            
            if (lng < -180 || lng > 180) {
                showStatus(retailerId, 'Longitude must be between -180 and 180', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_retailer');
            formData.append('retailer_id', retailerId);
            formData.append('name', name);
            formData.append('address', address);
            formData.append('city', city);
            formData.append('province', province);
            formData.append('phone', phone);
            formData.append('latitude', lat);
            formData.append('longitude', lng);
            
            // Show loading state
            const button = document.querySelector(`#retailer-${retailerId} .update-button`);
            const originalText = button.textContent;
            button.textContent = '💾 Saving...';
            button.disabled = true;
            
            fetch('retailer_geocoding.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showStatus(retailerId, 'All retailer information updated successfully!', 'success');
                    document.getElementById('retailer-' + retailerId).style.opacity = '0.5';
                    document.getElementById('retailer-' + retailerId).style.border = '2px solid #28a745';
                    
                    // Update the header info with new values
                    const headerName = document.querySelector(`#retailer-${retailerId} .retailer-name`);
                    const headerInfo = document.querySelector(`#retailer-${retailerId} .retailer-info`);
                    
                    headerName.textContent = name;
                    headerInfo.innerHTML = `
                        <strong>Current Address:</strong> ${address}<br>
                        <strong>City:</strong> ${city}<br>
                        <strong>Province:</strong> ${province}<br>
                        <strong>Phone:</strong> ${phone}
                    `;
                } else {
                    showStatus(retailerId, 'Error updating retailer information', 'error');
                }
            })
            .catch(error => {
                showStatus(retailerId, 'Network error: ' + error, 'error');
            })
            .finally(() => {
                // Restore button state
                button.textContent = originalText;
                button.disabled = false;
            });
        }
        
        function skipRetailer(retailerId) {
            document.getElementById('retailer-' + retailerId).style.display = 'none';
        }
        
        function showStatus(retailerId, message, type) {
            const statusDiv = document.getElementById('status-' + retailerId);
            statusDiv.textContent = message;
            statusDiv.className = 'status-message ' + type;
            statusDiv.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }
        }
    </script>
</body>
</html>