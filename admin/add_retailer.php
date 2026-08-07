<?php
require_once 'auth.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_retailer') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Debug: Log all POST data
        error_log("=== ADD RETAILER DEBUG START ===");
        error_log("POST data: " . print_r($_POST, true));
        
        // Validate required fields
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $lat = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $lng = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        
        error_log("Parsed fields - Name: $name, Address: $address, City: $city, Province: $province");
        error_log("Coordinates - Lat: $lat, Lng: $lng");
        
        if (empty($name) || empty($address) || empty($city) || empty($province)) {
            error_log("Validation failed - missing required fields");
            throw new Exception('Name, address, city, and province are required fields.');
        }
        
        // Load existing retailers
        $retailersFile = '../retailers.json';
        error_log("Retailers file path: $retailersFile");
        error_log("File exists: " . (file_exists($retailersFile) ? 'yes' : 'no'));
        
        if (!file_exists($retailersFile)) {
            error_log("Retailers file does not exist, will create new one");
            $retailersData = [];
        } else {
            $fileContents = file_get_contents($retailersFile);
            error_log("File size: " . strlen($fileContents) . " bytes");
            $retailersData = json_decode($fileContents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                throw new Exception('Failed to read existing retailer data - invalid JSON.');
            }
            error_log("Loaded " . count($retailersData) . " existing retailers");
        }
        
        // Generate new ID
        $maxId = 0;
        foreach ($retailersData as $retailer) {
            if (isset($retailer['ID'])) {
                // Extract numeric part from ID like "ret_001"
                $numericPart = (int) str_replace('ret_', '', $retailer['ID']);
                if ($numericPart > $maxId) {
                    $maxId = $numericPart;
                }
            }
        }
        $newId = 'ret_' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
        
        error_log("Generated new ID: $newId");
        
        // Create new retailer record
        $newRetailer = [
            'ID' => $newId,
            'name' => $name,
            'address' => "$address, $city, $province" . ($postalCode ? ", $postalCode" : '') . ", Canada",
            'street' => $address,
            'city' => $city,
            'state' => $province,
            'province' => $province,
            'postal_code' => $postalCode,
            'country' => 'Canada',
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'lat' => $lat,
            'lng' => $lng,
            'longitude' => $lng, // Some parts might expect this field name
            'latitude' => $lat,  // Some parts might expect this field name
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        error_log("New retailer record: " . print_r($newRetailer, true));
        
        // Add to retailers array
        $retailersData[] = $newRetailer;
        error_log("Added to array, new count: " . count($retailersData));
        
        // Check file permissions before attempting to write
        $parentDir = dirname($retailersFile);
        error_log("Parent directory: $parentDir");
        error_log("Parent directory writable: " . (is_writable($parentDir) ? 'yes' : 'no'));
        error_log("File writable: " . (is_writable($retailersFile) ? 'yes' : 'no'));
        
        // Save back to file
        $jsonData = json_encode($retailersData, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            error_log("JSON encode failed: " . json_last_error_msg());
            throw new Exception('Failed to encode retailer data to JSON.');
        }
        
        error_log("JSON data length: " . strlen($jsonData) . " bytes");
        
        $writeResult = file_put_contents($retailersFile, $jsonData);
        error_log("file_put_contents result: " . ($writeResult !== false ? $writeResult . " bytes written" : "failed"));
        
        if ($writeResult !== false) {
            logAdminAction('RETAILER_ADD', "Added new retailer: $name in $city, $province");
            $response['success'] = true;
            $response['message'] = "Retailer '$name' added successfully!";
            $response['retailer_id'] = $newId;
        } else {
            error_log("Failed to write file - file_put_contents returned false");
            throw new Exception('Failed to save retailer data to file.');
        }
        
        error_log("=== ADD RETAILER DEBUG END - SUCCESS ===");
        
    } catch (Exception $e) {
        error_log("=== ADD RETAILER DEBUG END - ERROR ===");
        error_log("Exception: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        $response['message'] = $e->getMessage();
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Set session message for page reload
    $_SESSION['add_retailer_message'] = $response['message'];
    $_SESSION['add_retailer_success'] = $response['success'];
    
    // Redirect to prevent form resubmission
    header('Location: add_retailer.php');
    exit;
}

// Get session messages
$message = $_SESSION['add_retailer_message'] ?? '';
$success = $_SESSION['add_retailer_success'] ?? false;
unset($_SESSION['add_retailer_message'], $_SESSION['add_retailer_success']);

// Get provinces for dropdown
$provinces = [
    'AB' => 'Alberta',
    'BC' => 'British Columbia',
    'MB' => 'Manitoba',
    'NB' => 'New Brunswick',
    'NL' => 'Newfoundland and Labrador',
    'NS' => 'Nova Scotia',
    'ON' => 'Ontario',
    'PE' => 'Prince Edward Island',
    'QC' => 'Quebec',
    'SK' => 'Saskatchewan',
    'NT' => 'Northwest Territories',
    'NU' => 'Nunavut',
    'YT' => 'Yukon'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Retailer - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        /* Additional page-specific styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #87ceeb;
        }
        
        .form-section h3 {
            color: #1e3c72;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid #1e3c72;
            padding-bottom: 10px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group.full-width {
            flex: 1 0 100%;
        }
        
        .geocode-section {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #2a5298;
        }
        
        .geocode-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .coord-display {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border: 1px solid #87ceeb;
        }
        
        .coord-display.has-coords {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .submit-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .submit-section .action-button {
            font-size: 18px;
            padding: 15px 40px;
            margin: 0 10px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .map-preview {
            height: 300px;
            background: #f0f0f0;
            border-radius: 8px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            border: 2px dashed #87ceeb;
        }
        
        .map-preview.has-location {
            border-color: #28a745;
            background: #f8fff9;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="admin-card">
            <div class="back-nav">
                <a href="retailer_geocoding.php">← Back to Geocoding Tool</a>
                <a href="index.php">← Back to Admin Portal</a>
            </div>
            <h1 style="color: #1e3c72; margin: 0; text-align: center;">➕ Add New Retailer</h1>
            <p style="text-align: center; color: #666; margin: 10px 0 0 0;">Add a new jewelry retailer to the database</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($success && isset($response['retailer_id'])): ?>
                    <br><a href="retailer_geocoding.php" class="action-button" style="margin-top: 10px; font-size: 12px; padding: 8px 15px;">Go to Geocoding Tool</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="addRetailerForm">
            <input type="hidden" name="action" value="add_retailer">
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3>📋 Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Business Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required placeholder="Enter business name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="contact@retailer.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" placeholder="https://www.retailer.com">
                    </div>
                </div>
            </div>

            <!-- Address Information Section -->
            <div class="form-section">
                <h3>📍 Address Information</h3>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="address">Street Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" required placeholder="123 Main Street">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required placeholder="Toronto">
                    </div>
                    <div class="form-group">
                        <label for="province">Province <span class="required">*</span></label>
                        <select id="province" name="province" required>
                            <option value="">Select Province</option>
                            <?php foreach ($provinces as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" placeholder="M5V 3A8" pattern="[A-Za-z][0-9][A-Za-z] [0-9][A-Za-z][0-9]">
                    </div>
                </div>
            </div>

            <!-- Location Coordinates Section -->
            <div class="form-section geocode-section">
                <h3>🗺️ Location Coordinates (Optional)</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    You can either enter coordinates manually or use the auto-geocoding feature to find them automatically.
                </p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="number" id="latitude" name="latitude" step="any" placeholder="43.651070">
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" id="longitude" name="longitude" step="any" placeholder="-79.347015">
                    </div>
                </div>
                
                <div class="geocode-buttons">
                    <button type="button" class="action-button secondary" onclick="geocodeAddress(event)">
                        🔍 Auto-Find Coordinates
                    </button>
                    <button type="button" class="action-button secondary" onclick="clearCoordinates()">
                        🔄 Clear Coordinates
                    </button>
                    <button type="button" class="action-button secondary" onclick="showOnMap()" id="mapButton" disabled>
                        🗺️ View on Map
                    </button>
                </div>
                
                <div class="coord-display" id="coordDisplay">
                    <strong>Coordinates:</strong> <span id="coordText">Not set</span>
                </div>
                
                <div class="map-preview" id="mapPreview">
                    📍 Map preview will appear here when coordinates are set
                </div>
            </div>

            <!-- Submit Section -->
            <div class="submit-section">
                <button type="submit" class="action-button">
                    ✅ Add Retailer
                </button>
                <button type="button" class="action-button secondary" onclick="resetForm()">
                    🔄 Reset Form
                </button>
            </div>
        </form>
    </div>

    <script>
        // Auto-geocoding function
        async function geocodeAddress(event) {
            const address = document.getElementById('address').value;
            const city = document.getElementById('city').value;
            const province = document.getElementById('province').value;
            
            console.log('=== GEOCODING DEBUG START ===');
            console.log('Address:', address);
            console.log('City:', city);
            console.log('Province:', province);
            
            if (!address || !city || !province) {
                alert('Please enter address, city, and province before geocoding.');
                return;
            }
            
            const fullAddress = `${address}, ${city}, ${province}, Canada`;
            console.log('Full address to geocode:', fullAddress);
            
            // Get the button that was clicked
            const button = event ? event.target : document.querySelector('[onclick="geocodeAddress(event)"]');
            const originalText = button ? button.textContent : '🔍 Auto-Find Coordinates';
            
            try {
                // Show loading state
                if (button) {
                    button.textContent = '🔄 Finding...';
                    button.disabled = true;
                }
                
                console.log('Making API request to:', `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1&countrycodes=ca`);
                
                // Use OpenStreetMap Nominatim API (free) with better error handling
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1&countrycodes=ca`);
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Raw API response:', data);
                console.log('Data length:', data ? data.length : 'data is null/undefined');
                
                if (data && data.length > 0) {
                    console.log('First result:', data[0]);
                    console.log('Lat from API:', data[0].lat);
                    console.log('Lon from API:', data[0].lon);
                    
                    if (data[0].lat && data[0].lon) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        
                        console.log('Parsed lat:', lat);
                        console.log('Parsed lng:', lng);
                        
                        // Validate coordinates are reasonable for Canada
                        const isValidLat = lat >= 41.6 && lat <= 83.3;
                        const isValidLng = lng >= -141.0 && lng <= -52.6;
                        console.log('Lat valid (41.6-83.3):', isValidLat);
                        console.log('Lng valid (-141.0 to -52.6):', isValidLng);
                        
                        if (isValidLat && isValidLng) {
                            console.log('Coordinates valid, setting values...');
                            document.getElementById('latitude').value = lat.toFixed(6);
                            document.getElementById('longitude').value = lng.toFixed(6);
                            
                            updateCoordinateDisplay();
                            
                            alert(`✅ Coordinates found!\nLatitude: ${lat.toFixed(6)}\nLongitude: ${lng.toFixed(6)}\nLocation: ${data[0].display_name || 'Address found'}`);
                        } else {
                            console.log('Coordinates outside Canada bounds, trying fallback...');
                            throw new Error('Coordinates outside of Canada bounds');
                        }
                    } else {
                        console.log('Missing lat/lon in first result, trying fallback...');
                        throw new Error('No valid coordinates in first result');
                    }
                } else {
                    console.log('No results in first attempt, trying simplified address...');
                    // Try a simplified address format
                    const simplifiedAddress = `${city}, ${province}, Canada`;
                    console.log('Trying simplified address:', simplifiedAddress);
                    
                    const response2 = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(simplifiedAddress)}&limit=1&countrycodes=ca`);
                    console.log('Simplified response status:', response2.status);
                    
                    const data2 = await response2.json();
                    console.log('Simplified API response:', data2);
                    
                    if (data2 && data2.length > 0 && data2[0].lat && data2[0].lon) {
                        const lat = parseFloat(data2[0].lat);
                        const lng = parseFloat(data2[0].lon);
                        
                        console.log('Simplified coords - lat:', lat, 'lng:', lng);
                        console.log('Setting latitude field to:', lat.toFixed(6));
                        console.log('Setting longitude field to:', lng.toFixed(6));
                        
                        const latField = document.getElementById('latitude');
                        const lngField = document.getElementById('longitude');
                        
                        latField.value = lat.toFixed(6);
                        lngField.value = lng.toFixed(6);
                        
                        console.log('Latitude field value after setting:', latField.value);
                        console.log('Longitude field value after setting:', lngField.value);
                        
                        updateCoordinateDisplay();
                        
                        console.log('About to show success alert...');
                        
                        // Add visual notification to the page as well as alert
                        const notification = document.createElement('div');
                        notification.style.cssText = `
                            position: fixed; top: 20px; right: 20px; z-index: 10000;
                            background: linear-gradient(135deg, #28a745, #20c997);
                            color: white; padding: 15px 20px; border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                            max-width: 400px; font-size: 14px;
                        `;
                        notification.innerHTML = `
                            <strong>✅ Coordinates Found!</strong><br>
                            City coordinates for ${city}, ${province}<br>
                            Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}
                        `;
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 5000);
                        
                        alert(`⚠️ Exact street address not found, but found city coordinates for ${city}, ${province}\n\nLatitude: ${lat.toFixed(6)}\nLongitude: ${lng.toFixed(6)}\n\nNote: These are city-center coordinates. You may want to manually adjust them for the exact street location if needed.`);
                        console.log('Success alert should have been shown');
                    } else {
                        console.log('Simplified address also failed');
                        throw new Error('No coordinates found for this location');
                    }
                }
                
            } catch (error) {
                console.error('Geocoding error:', error);
                console.log('=== GEOCODING DEBUG ERROR ===');
                alert(`❌ Error during geocoding: ${error.message}\n\nPossible solutions:\n1. Check your internet connection\n2. Verify the address is correct\n3. Try entering coordinates manually\n4. The geocoding service might be temporarily unavailable\n\nCheck browser console for detailed logs.`);
            } finally {
                console.log('=== GEOCODING DEBUG END ===');
                // Restore button state
                if (button) {
                    button.textContent = originalText;
                    button.disabled = false;
                }
            }
        }
        
        // Clear coordinates
        function clearCoordinates() {
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            updateCoordinateDisplay();
        }
        
        // Update coordinate display
        function updateCoordinateDisplay() {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const coordDisplay = document.getElementById('coordDisplay');
            const coordText = document.getElementById('coordText');
            const mapButton = document.getElementById('mapButton');
            const mapPreview = document.getElementById('mapPreview');
            
            if (lat && lng) {
                coordText.textContent = `${lat}, ${lng}`;
                coordDisplay.classList.add('has-coords');
                mapButton.disabled = false;
                mapPreview.classList.add('has-location');
                mapPreview.innerHTML = `
                    <div style="text-align: center;">
                        <h4 style="color: #1e3c72; margin-bottom: 10px;">📍 Location Set</h4>
                        <p style="color: #666; margin-bottom: 10px;">Coordinates: ${lat}, ${lng}</p>
                        <button type="button" onclick="showOnMap()" class="action-button" style="font-size: 12px; padding: 8px 15px;">
                            🗺️ View on Google Maps
                        </button>
                    </div>
                `;
            } else {
                coordText.textContent = 'Not set';
                coordDisplay.classList.remove('has-coords');
                mapButton.disabled = true;
                mapPreview.classList.remove('has-location');
                mapPreview.innerHTML = '📍 Map preview will appear here when coordinates are set';
            }
        }
        
        // Show on map
        function showOnMap() {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            
            if (lat && lng) {
                const url = `https://www.google.com/maps?q=${lat},${lng}`;
                window.open(url, '_blank');
            }
        }
        
        // Reset form
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('addRetailerForm').reset();
                updateCoordinateDisplay();
            }
        }
        
        // Auto-format postal code
        document.getElementById('postal_code').addEventListener('input', function() {
            let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3, 6);
            }
            this.value = value;
        });
        
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
            }
            this.value = value;
        });
        
        // Update coordinate display when inputs change
        document.getElementById('latitude').addEventListener('input', updateCoordinateDisplay);
        document.getElementById('longitude').addEventListener('input', updateCoordinateDisplay);
        
        // Initialize coordinate display on page load
        document.addEventListener('DOMContentLoaded', updateCoordinateDisplay);
        
        // Form validation
        document.getElementById('addRetailerForm').addEventListener('submit', function(e) {
            const requiredFields = ['name', 'address', 'city', 'province'];
            let hasErrors = false;
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    hasErrors = true;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *).');
                return false;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.textContent = '⏳ Adding Retailer...';
            submitButton.disabled = true;
        });
    </script>
</body>
</html>
