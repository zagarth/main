<?php
// Disable error reporting for production security
ini_set('display_errors', 0);
error_reporting(0);

require_once 'auth.php';
requireAdmin();

// Handle session extension
if ($_POST['extend_session'] ?? false) {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    $_SESSION['session_start'] = time();
    logAdminAction('SESSION_EXTENDED');
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['extend_session'])) {
    $action = $_POST['action'] ?? '';
    
    // Debug: Log the action and POST data
    error_log("Retailer Management - Action: " . $action);
    error_log("Retailer Management - POST data: " . print_r($_POST, true));
    
    if ($action === 'delete_retailer') {
        $retailerId = $_POST['retailer_id'] ?? '';
        if ($retailerId) {
            // Load XML
            $xmlFile = '../retailers.xml';
            if (file_exists($xmlFile)) {
                $xml = simplexml_load_file($xmlFile);
                
                // Find and remove retailer
                for ($i = 0; $i < count($xml->retailer); $i++) {
                    if ((string)$xml->retailer[$i]['id'] === $retailerId) {
                        unset($xml->retailer[$i]);
                        
                        // Save XML
                        $dom = new DOMDocument('1.0', 'UTF-8');
                        $dom->formatOutput = true;
                        
                        $xmlString = $xml->asXML();
                        if ($xmlString === false) {
                            $error = "Failed to convert XML to string.";
                        } else {
                            if ($dom->loadXML($xmlString)) {
                                if (!is_writable($xmlFile)) {
                                    $error = "XML file is not writable. Please check file permissions.";
                                } else {
                                    $saveResult = $dom->save($xmlFile);
                                    if ($saveResult !== false) {
                                        $message = "Retailer deleted successfully!";
                                        logAdminAction('RETAILER_DELETED', ['retailer_id' => $retailerId]);
                                    } else {
                                        $error = "Failed to save changes to XML file. Error: " . (error_get_last()['message'] ?? 'Unknown error');
                                    }
                                }
                            } else {
                                $error = "Failed to load XML string into DOMDocument.";
                            }
                        }
                        break;
                    }
                }
            } else {
                $error = "Retailers XML file not found.";
            }
        }
    }
    
    if ($action === 'update_retailer') {
        $retailerId = $_POST['retailer_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? 'Canada');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        
        if ($retailerId && $name && $street && $city && $province) {
            $xmlFile = '../retailers.xml';
            if (file_exists($xmlFile)) {
                $xml = simplexml_load_file($xmlFile);
                
                // Find and update retailer
                $found = false;
                foreach ($xml->retailer as $retailer) {
                    if ((string)$retailer['id'] === $retailerId) {
                        $retailer->name = $name;
                        $retailer->street = $street;
                        $retailer->city = $city;
                        $retailer->province = $province;
                        $retailer->postal_code = $postal_code;
                        $retailer->country = $country;
                        $retailer->phone = $phone;
                        $retailer->email = $email;
                        $retailer->website = $website;
                        $retailer->lat = $lat;
                        $retailer->lng = $lng;
                        
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    // Save XML
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->formatOutput = true;
                    
                    // Check if XML can be loaded
                    $xmlString = $xml->asXML();
                    if ($xmlString === false) {
                        $error = "Failed to convert XML to string.";
                    } else {
                        if ($dom->loadXML($xmlString)) {
                            // Check if file is writable
                            if (!is_writable($xmlFile)) {
                                $error = "XML file is not writable. Please check file permissions.";
                            } else {
                                $saveResult = $dom->save($xmlFile);
                                if ($saveResult !== false) {
                                    $message = "Retailer updated successfully!";
                                    logAdminAction('RETAILER_UPDATED', ['retailer_id' => $retailerId, 'name' => $name]);
                                } else {
                                    $error = "Failed to save changes to XML file. Error: " . (error_get_last()['message'] ?? 'Unknown error');
                                }
                            }
                        } else {
                            $error = "Failed to load XML string into DOMDocument.";
                        }
                    }
                } else {
                    $error = "Retailer not found.";
                }
            } else {
                $error = "Retailers XML file not found.";
            }
        } else {
            $error = "Please fill in all required fields (Name, Street, City, Province).";
        }
    }
    
    if ($action === 'add_retailer') {
        $name = trim($_POST['name'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? 'Canada');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        
        if ($name && $street && $city && $province) {
            $xmlFile = '../retailers.xml';
            if (file_exists($xmlFile)) {
                $xml = simplexml_load_file($xmlFile);
                
                // Generate new ID
                $maxId = 0;
                foreach ($xml->retailer as $retailer) {
                    $id = (int)$retailer['id'];
                    if ($id > $maxId) $maxId = $id;
                }
                $newId = $maxId + 1;
                
                // Add new retailer
                $newRetailer = $xml->addChild('retailer');
                $newRetailer->addAttribute('id', $newId);
                $newRetailer->addChild('name', htmlspecialchars($name));
                $newRetailer->addChild('street', htmlspecialchars($street));
                $newRetailer->addChild('city', htmlspecialchars($city));
                $newRetailer->addChild('province', htmlspecialchars($province));
                $newRetailer->addChild('postal_code', htmlspecialchars($postal_code));
                $newRetailer->addChild('country', htmlspecialchars($country));
                $newRetailer->addChild('phone', htmlspecialchars($phone));
                $newRetailer->addChild('email', htmlspecialchars($email));
                $newRetailer->addChild('website', htmlspecialchars($website));
                $newRetailer->addChild('lat', $lat ?: '50');
                $newRetailer->addChild('lng', $lng ?: '-100');
                
                // Save XML
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->formatOutput = true;
                
                $xmlString = $xml->asXML();
                if ($xmlString === false) {
                    $error = "Failed to convert XML to string.";
                } else {
                    if ($dom->loadXML($xmlString)) {
                        if (!is_writable($xmlFile)) {
                            $error = "XML file is not writable. Please check file permissions.";
                        } else {
                            $saveResult = $dom->save($xmlFile);
                            if ($saveResult !== false) {
                                $message = "New retailer added successfully! ID: $newId";
                                logAdminAction('RETAILER_ADDED', ['retailer_id' => $newId, 'name' => $name]);
                            } else {
                                $error = "Failed to save new retailer to XML file. Error: " . (error_get_last()['message'] ?? 'Unknown error');
                            }
                        }
                    } else {
                        $error = "Failed to load XML string into DOMDocument.";
                    }
                }
            } else {
                $error = "Retailers XML file not found.";
            }
        } else {
            $error = "Please fill in all required fields (Name, Street, City, Province).";
        }
    }
}

// Load retailers from XML
$retailers = [];
$xmlFile = '../retailers.xml';
if (file_exists($xmlFile)) {
    $xml = simplexml_load_file($xmlFile);
    foreach ($xml->retailer as $retailer) {
        $retailers[] = [
            'id' => (string)$retailer['id'],
            'name' => (string)$retailer->name,
            'street' => (string)$retailer->street,
            'city' => (string)$retailer->city,
            'province' => (string)$retailer->province,
            'postal_code' => (string)$retailer->postal_code,
            'country' => (string)$retailer->country,
            'phone' => (string)$retailer->phone,
            'email' => (string)$retailer->email,
            'website' => (string)$retailer->website,
            'lat' => (string)$retailer->lat,
            'lng' => (string)$retailer->lng,
        ];
    }
}

// Sort retailers by name
usort($retailers, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Management - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <script src="session-manager.js"></script>
    <?php echo renderSessionScript(); ?>
    <style>
        .management-container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .stats-header {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            text-align: center;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .retailers-panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .retailer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .retailer-table th,
        .retailer-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .retailer-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1e3c72;
        }
        
        .retailer-table tr:hover {
            background: #f8f9fa;
        }
        
        .retailer-row {
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .retailer-row.selected {
            background: #e3f2fd !important;
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
        
        .edit-panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1e3c72;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #1e3c72;
            outline: none;
        }
        
        .required {
            color: #dc3545;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #1e3c72;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2a4d8d;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        
        .coordinates-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .coordinates-valid {
            color: #28a745;
        }
        
        .coordinates-invalid {
            color: #dc3545;
        }
        
        .retailer-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .add-retailer-form {
            background: #f0f8ff;
            border: 2px dashed #87ceeb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-toggle {
            background: none;
            border: none;
            color: #1e3c72;
            text-decoration: underline;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="management-container">
        <div class="admin-card">
            <div class="back-nav">
                <a href="index.php">← Back to Admin Portal</a>
                <a href="retailer_geocoding.php" class="action-button" style="font-size: 12px; padding: 6px 12px; margin-left: 10px;">🗺️ Geocoding Tool</a>
            </div>
            <h1 style="color: #1e3c72; margin: 0; text-align: center;">🏪 Retailer Management System</h1>
            
            <div class="stats-header">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($retailers); ?></div>
                        <div class="stat-label">Total Retailers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(array_filter($retailers, function($r) { return $r['lat'] && $r['lng'] && !($r['lat'] == '50' && $r['lng'] == '-100'); })); ?></div>
                        <div class="stat-label">Geocoded</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(array_unique(array_column($retailers, 'province'))); ?></div>
                        <div class="stat-label">Provinces/States</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(array_filter($retailers, function($r) { return !empty($r['phone']); })); ?></div>
                        <div class="stat-label">With Phone</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="retailers-panel">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                    <h3 style="color: #1e3c72; margin: 0;">All Retailers (<?php echo count($retailers); ?>)</h3>
                    <button onclick="toggleAddForm()" class="form-toggle">+ Add New Retailer</button>
                </div>
                
                <!-- Add New Retailer Form -->
                <div id="addRetailerForm" class="add-retailer-form" style="display: none;">
                    <h4 style="color: #1e3c72; margin-top: 0;">Add New Retailer</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_retailer">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="add_name">Name <span class="required">*</span></label>
                                <input type="text" id="add_name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="add_phone">Phone</label>
                                <input type="text" id="add_phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="add_street">Street Address <span class="required">*</span></label>
                                <input type="text" id="add_street" name="street" required>
                            </div>
                            <div class="form-group">
                                <label for="add_email">Email</label>
                                <input type="email" id="add_email" name="email">
                            </div>
                            <div class="form-group">
                                <label for="add_city">City <span class="required">*</span></label>
                                <input type="text" id="add_city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="add_website">Website</label>
                                <input type="url" id="add_website" name="website">
                            </div>
                            <div class="form-group">
                                <label for="add_province">Province/State <span class="required">*</span></label>
                                <input type="text" id="add_province" name="province" required>
                            </div>
                            <div class="form-group">
                                <label for="add_postal_code">Postal Code</label>
                                <input type="text" id="add_postal_code" name="postal_code">
                            </div>
                            <div class="form-group">
                                <label for="add_lat">Latitude</label>
                                <input type="number" id="add_lat" name="lat" step="any">
                            </div>
                            <div class="form-group">
                                <label for="add_lng">Longitude</label>
                                <input type="number" id="add_lng" name="lng" step="any">
                            </div>
                        </div>
                        <div class="button-group" style="margin-top: 15px;">
                            <button type="submit" class="btn btn-success">➕ Add Retailer</button>
                            <button type="button" onclick="toggleAddForm()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <input type="text" id="searchBox" class="search-bar" placeholder="Search retailers by name, city, or province...">
                
                <div style="max-height: 600px; overflow-y: auto;">
                    <table class="retailer-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>City</th>
                                <th>Province</th>
                                <th>Phone</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="retailerTableBody">
                            <?php foreach ($retailers as $retailer): ?>
                                <tr class="retailer-row" onclick="selectRetailer(<?php echo htmlspecialchars(json_encode($retailer)); ?>)">
                                    <td><strong><?php echo htmlspecialchars($retailer['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($retailer['city']); ?></td>
                                    <td><?php echo htmlspecialchars($retailer['province']); ?></td>
                                    <td><?php echo htmlspecialchars($retailer['phone'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($retailer['lat'] && $retailer['lng'] && !($retailer['lat'] == '50' && $retailer['lng'] == '-100')): ?>
                                            <span style="color: #28a745; font-weight: bold;">✓ Complete</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545; font-weight: bold;">⚠ Missing Coords</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="edit-panel">
                <div id="selectPrompt">
                    <h3 style="color: #1e3c72;">Select a Retailer</h3>
                    <p style="color: #666;">Choose a retailer from the table to view and edit their information.</p>
                </div>
                
                <div id="editForm" class="edit-form">
                    <div class="retailer-info">
                        <h3 style="color: #1e3c72; margin-top: 0;">Retailer Information</h3>
                        <div id="retailerDetails"></div>
                    </div>
                    
                    <form method="POST" id="updateForm">
                        <input type="hidden" name="action" value="update_retailer">
                        <input type="hidden" id="retailer_id" name="retailer_id">
                        
                        <div class="form-group">
                            <label for="name">Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="street">Street Address <span class="required">*</span></label>
                            <input type="text" id="street" name="street" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="province">Province/State <span class="required">*</span></label>
                            <input type="text" id="province" name="province" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code">
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country">
                                <option value="Canada">Canada</option>
                                <option value="USA">United States</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website">
                        </div>
                        
                        <div class="form-group">
                            <label for="lat">Latitude</label>
                            <input type="number" id="lat" name="lat" step="any" onchange="validateCoordinates()">
                        </div>
                        
                        <div class="form-group">
                            <label for="lng">Longitude</label>
                            <input type="number" id="lng" name="lng" step="any" onchange="validateCoordinates()">
                        </div>
                        
                        <div id="coordinatesInfo" class="coordinates-info" style="display: none;"></div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">💾 Update Retailer</button>
                            <button type="button" onclick="geocodeCurrentRetailer()" class="btn btn-secondary">🔍 Auto-Geocode</button>
                            <button type="button" onclick="clearSelection()" class="btn btn-secondary">🔄 Clear</button>
                            <button type="button" onclick="deleteCurrentRetailer()" class="btn btn-danger">🗑️ Delete Retailer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRetailer = null;
        let allRetailers = <?php echo json_encode($retailers); ?>;

        function selectRetailer(retailer) {
            currentRetailer = retailer;
            
            // Hide select prompt and show edit form
            document.getElementById('selectPrompt').style.display = 'none';
            document.getElementById('editForm').classList.add('active');
            
            // Populate form
            document.getElementById('retailer_id').value = retailer.id;
            document.getElementById('name').value = retailer.name;
            document.getElementById('street').value = retailer.street;
            document.getElementById('city').value = retailer.city;
            document.getElementById('province').value = retailer.province;
            document.getElementById('postal_code').value = retailer.postal_code;
            document.getElementById('country').value = retailer.country || 'Canada';
            document.getElementById('phone').value = retailer.phone;
            document.getElementById('email').value = retailer.email;
            document.getElementById('website').value = retailer.website;
            document.getElementById('lat').value = retailer.lat;
            document.getElementById('lng').value = retailer.lng;
            
            // Update retailer info display
            updateRetailerDetails(retailer);
            
            // Highlight selected row
            document.querySelectorAll('.retailer-row').forEach(row => {
                row.classList.remove('selected');
            });
            event.target.closest('.retailer-row').classList.add('selected');
            
            // Validate coordinates
            validateCoordinates();
        }

        function updateRetailerDetails(retailer) {
            const address = [retailer.street, retailer.city, retailer.province, retailer.postal_code].filter(Boolean).join(', ');
            const hasCoords = retailer.lat && retailer.lng && !(retailer.lat == '50' && retailer.lng == '-100');
            
            document.getElementById('retailerDetails').innerHTML = `
                <p><strong>ID:</strong> ${retailer.id}</p>
                <p><strong>Address:</strong> ${address}</p>
                <p><strong>Status:</strong> ${hasCoords ? '<span style="color: #28a745;">✓ Geocoded</span>' : '<span style="color: #dc3545;">⚠ Missing Coordinates</span>'}</p>
                ${hasCoords ? `<p><strong>Coordinates:</strong> ${retailer.lat}, ${retailer.lng}</p>` : ''}
                ${hasCoords ? `<p><a href="https://www.google.com/maps?q=${retailer.lat},${retailer.lng}" target="_blank" style="color: #1e3c72;">🗺️ View on Google Maps</a></p>` : ''}
            `;
        }

        function validateCoordinates() {
            const lat = parseFloat(document.getElementById('lat').value);
            const lng = parseFloat(document.getElementById('lng').value);
            const info = document.getElementById('coordinatesInfo');
            
            if (isNaN(lat) || isNaN(lng)) {
                info.style.display = 'none';
                return;
            }
            
            info.style.display = 'block';
            
            if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                if (lat == 50 && lng == -100) {
                    info.className = 'coordinates-info coordinates-invalid';
                    info.innerHTML = '⚠️ These are placeholder coordinates (center of Canada)';
                } else {
                    info.className = 'coordinates-info coordinates-valid';
                    info.innerHTML = `✅ Valid coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
            } else {
                info.className = 'coordinates-info coordinates-invalid';
                info.innerHTML = '❌ Invalid coordinates. Latitude must be -90 to 90, Longitude must be -180 to 180';
            }
        }

        function clearSelection() {
            currentRetailer = null;
            document.getElementById('selectPrompt').style.display = 'block';
            document.getElementById('editForm').classList.remove('active');
            document.querySelectorAll('.retailer-row').forEach(row => {
                row.classList.remove('selected');
            });
        }

        function deleteCurrentRetailer() {
            if (!currentRetailer) return;
            
            const retailerName = currentRetailer.name;
            const retailerCity = currentRetailer.city;
            
            if (confirm(`Are you sure you want to delete "${retailerName}" in ${retailerCity}?\n\nThis action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_retailer">
                    <input type="hidden" name="retailer_id" value="${currentRetailer.id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function geocodeCurrentRetailer() {
            if (!currentRetailer) return;
            
            const address = document.getElementById('street').value;
            const city = document.getElementById('city').value;
            const province = document.getElementById('province').value;
            const postalCode = document.getElementById('postal_code').value;
            
            if (!address || !city || !province) {
                alert('Please fill in address, city, and province fields first.');
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '🔍 Geocoding...';
            
            try {
                const response = await fetch('../admin/api/geocode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ address, city, province, postal_code: postalCode })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        document.getElementById('lat').value = result.coordinates.lat.toFixed(6);
                        document.getElementById('lng').value = result.coordinates.lng.toFixed(6);
                        validateCoordinates();
                        alert(`✅ Found coordinates: ${result.coordinates.lat.toFixed(6)}, ${result.coordinates.lng.toFixed(6)}`);
                    } else {
                        alert('❌ Could not find coordinates for this address.');
                    }
                } else {
                    throw new Error('Geocoding service error');
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                alert('❌ Error during geocoding. Please enter coordinates manually.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function toggleAddForm() {
            const form = document.getElementById('addRetailerForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.retailer-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Auto-validate coordinates on page load
        window.addEventListener('load', function() {
            validateCoordinates();
        });
    </script>
</body>
</html>