<?php
/**
 * Retailer Management - Database Edition
 * Manages retailer data in the clients database table
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
requireAdmin();

// Load database connections
require_once __DIR__ . '/../includes/db_config_encrypted.php';
require_once __DIR__ . '/../includes/db_config_readonly.php';

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
    
    try {
        $pdo = getAdminConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // DELETE (SOFT DELETE)
        if ($action === 'delete_retailer') {
            $retailerId = filter_var($_POST['retailer_id'] ?? '', FILTER_VALIDATE_INT);
            
            if (!$retailerId) {
                throw new Exception("Invalid retailer ID");
            }
            
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("SELECT business_name FROM clients WHERE client_id = ? AND client_type = 'Retailer'");
                $stmt->execute([$retailerId]);
                $retailer = $stmt->fetch();
                
                if (!$retailer) {
                    throw new Exception("Retailer not found");
                }
                
                $stmt = $pdo->prepare("UPDATE clients SET status = 'Inactive', updated_at = NOW() WHERE client_id = ?");
                $stmt->execute([$retailerId]);
                
                $pdo->commit();
                
                $message = "Retailer '{$retailer['business_name']}' marked as inactive!";
                logAdminAction('RETAILER_SOFT_DELETED', ['retailer_id' => $retailerId, 'name' => $retailer['business_name']]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
        // UPDATE RETAILER
        if ($action === 'update_retailer') {
            $retailerId = filter_var($_POST['retailer_id'] ?? '', FILTER_VALIDATE_INT);
            
            if (!$retailerId) {
                throw new Exception("Invalid retailer ID");
            }
            
            $name = trim($_POST['name'] ?? '');
            $street = trim($_POST['street'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $country = trim($_POST['country'] ?? 'Canada');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $website = trim($_POST['website'] ?? '');
            $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
            $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;
            $status = trim($_POST['status'] ?? 'Active');
            
            if (!$name || !$street || !$city || !$province) {
                throw new Exception("Name, street, city, and province are required");
            }
            
            // Validate status
            if (!in_array($status, ['Active', 'Inactive'])) {
                $status = 'Active';
            }
            
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE clients SET
                        business_name = ?,
                        address = ?,
                        city = ?,
                        province = ?,
                        postal_code = ?,
                        country = ?,
                        phone = ?,
                        email = ?,
                        website = ?,
                        latitude = ?,
                        longitude = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE client_id = ? AND client_type = 'Retailer'
                ");
                
                $stmt->execute([
                    $name, $street, $city, $province, $postal_code, $country,
                    $phone, $email, $website, $lat, $lng, $status, $retailerId
                ]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Retailer not found or no changes made");
                }
                
                $pdo->commit();
                
                $message = "Retailer '$name' updated successfully!";
                logAdminAction('RETAILER_UPDATED', ['retailer_id' => $retailerId, 'name' => $name]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
        // ADD NEW RETAILER
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
            $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
            $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;
            
            if (!$name || !$street || !$city || !$province) {
                throw new Exception("Name, street, city, and province are required");
            }
            
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO clients (
                        client_type, business_name, address, city, province,
                        postal_code, country, phone, email, website,
                        latitude, longitude, status, created_at, updated_at
                    ) VALUES (
                        'Retailer', ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, 'Active', NOW(), NOW()
                    )
                ");
                
                $stmt->execute([
                    $name, $street, $city, $province, $postal_code, $country,
                    $phone, $email, $website, $lat, $lng
                ]);
                
                $newId = $pdo->lastInsertId();
                
                $pdo->commit();
                
                $message = "Retailer '$name' added successfully! ID: $newId";
                logAdminAction('RETAILER_ADDED', ['retailer_id' => $newId, 'name' => $name]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Retailer Management Error: " . $e->getMessage());
    }
}

// Load retailers from database
$retailers = [];
try {
    $pdo = getReadOnlyDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            client_id as id,
            business_name as name,
            address as street,
            city,
            province,
            postal_code,
            country,
            phone,
            email,
            website,
            latitude as lat,
            longitude as lng,
            status,
            updated_at
        FROM clients 
        WHERE client_type = 'Retailer'
          AND status = 'Active'
        ORDER BY business_name ASC
    ");
    $stmt->execute();
    $retailers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Failed to load retailers: " . $e->getMessage();
    error_log("Failed to load retailers: " . $e->getMessage());
}

$active_count = count(array_filter($retailers, fn($r) => $r['status'] === 'Active'));
$inactive_count = count(array_filter($retailers, fn($r) => $r['status'] !== 'Active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Management (Database) - Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <script src="session-manager.js"></script>
    <?php echo renderSessionScript(); ?>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f7fa; margin: 0; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0 0 10px 0; font-size: 28px; }
        .badge-db { background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #6b7280; font-size: 14px; }
        .main-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
        .panel { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .search-bar { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 15px; font-size: 16px; }
        .search-bar:focus { border-color: #667eea; outline: none; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        .retailer-row { cursor: pointer; transition: all 0.2s; }
        .retailer-row.selected { background: #ede9fe !important; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .edit-panel { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
        .edit-form { display: none; }
        .edit-form.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #374151; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .form-group input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .required { color: #dc2626; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; transition: all 0.2s; font-size: 14px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3); }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; border-left: 4px solid; }
        .alert-success { background: #d1fae5; color: #065f46; border-color: #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-color: #dc2626; }
        .add-form-toggle { background: #f0f8ff; border: 2px dashed #87ceeb; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: center; }
        .coords-info { background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 10px; font-size: 13px; }
        @media (max-width: 1200px) { .main-grid { grid-template-columns: 1fr; } .edit-panel { position: relative; top: 0; max-height: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🏪 Retailer Management <span class="badge-db">DATABASE</span></h1>
                    <div style="opacity: 0.9; font-size: 14px;">Manage retailers in the CadmanClients database</div>
                </div>
                <a href="index.php" class="btn btn-secondary">← Back to Admin</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_count; ?></div>
                <div class="stat-label">Active Retailers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inactive_count; ?></div>
                <div class="stat-label">Inactive Retailers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($retailers, function($r) { 
                    return $r['lat'] && $r['lng'] && $r['lat'] != 50 && $r['lng'] != -100; 
                })); ?></div>
                <div class="stat-label">Geocoded</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($retailers); ?></div>
                <div class="stat-label">Total in Database</div>
            </div>
        </div>

        <div class="main-grid">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">All Retailers</h2>
                    <button onclick="toggleAddForm()" class="btn btn-success">+ Add New</button>
                </div>

                <div id="addForm" style="display: none; background: #f0f8ff; border: 2px solid #87ceeb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">Add New Retailer</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_retailer">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Name <span class="required">*</span></label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone">
                            </div>
                            <div class="form-group">
                                <label>Street <span class="required">*</span></label>
                                <input type="text" name="street" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email">
                            </div>
                            <div class="form-group">
                                <label>City <span class="required">*</span></label>
                                <input type="text" name="city" required>
                            </div>
                            <div class="form-group">
                                <label>Website</label>
                                <input type="url" name="website">
                            </div>
                            <div class="form-group">
                                <label>Province <span class="required">*</span></label>
                                <input type="text" name="province" required>
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code">
                            </div>
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" step="any" name="lat" id="add_lat">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" step="any" name="lng" id="add_lng">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="geocodeAddForm()" style="margin-bottom: 15px; width: 100%;">
                            🗺️ Auto-Geocode from Address
                        </button>
                        <div class="button-group">
                            <button type="submit" class="btn btn-success">Add Retailer</button>
                            <button type="button" onclick="toggleAddForm()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <input type="text" class="search-bar" id="searchBar" placeholder="🔍 Search retailers...">
                
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>Province</th>
                            <th>Phone</th>
                            <th>Coords</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="retailerTable">
                        <?php foreach ($retailers as $retailer): ?>
                        <?php 
                            $hasCoords = $retailer['lat'] && $retailer['lng'] && $retailer['lat'] != 50 && $retailer['lng'] != -100;
                        ?>
                        <tr class="retailer-row" data-id="<?php echo $retailer['id']; ?>">
                            <td><?php echo htmlspecialchars($retailer['name']); ?></td>
                            <td><?php echo htmlspecialchars($retailer['city']); ?></td>
                            <td><?php echo htmlspecialchars($retailer['province']); ?></td>
                            <td><?php echo htmlspecialchars($retailer['phone'] ?: '-'); ?></td>
                            <td>
                                <?php if ($hasCoords): ?>
                                    <span style="color: #059669; font-weight: bold;">✓</span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: bold;">✗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($retailer['status']); ?>">
                                    <?php echo $retailer['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel edit-panel">
                <h3>Edit Retailer</h3>
                <p style="color: #6b7280; font-size: 14px;">Select a retailer from the list to edit</p>
                
                <div id="editForm" class="edit-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_retailer">
                        <input type="hidden" name="retailer_id" id="retailerId">
                        
                        <div class="form-group">
                            <label>Name <span class="required">*</span></label>
                            <input type="text" name="name" id="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Street <span class="required">*</span></label>
                            <input type="text" name="street" id="street" required>
                        </div>
                        
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" id="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Province <span class="required">*</span></label>
                            <input type="text" name="province" id="province" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" id="postal_code">
                        </div>
                        
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" id="country" value="Canada">
                        </div>
                        
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status" id="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" id="phone">
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email">
                        </div>
                        
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" id="website">
                        </div>
                        
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="number" step="any" name="lat" id="lat" oninput="validateCoordinates()">
                        </div>
                        
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="number" step="any" name="lng" id="lng" oninput="validateCoordinates()">
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn btn-secondary" onclick="geocodeAddress()" id="geocodeBtn" style="width: 100%;">
                                🗺️ Auto-Geocode from Address
                            </button>
                        </div>
                        
                        <div class="coords-info" id="coordsInfo"></div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="clearForm()">Cancel</button>
                            <button type="button" class="btn btn-danger" onclick="deleteRetailer()">🗑️ Deactivate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const retailers = <?php echo json_encode($retailers); ?>;
        let selectedId = null;

        document.getElementById('searchBar').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.retailer-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        document.querySelectorAll('.retailer-row').forEach(row => {
            row.addEventListener('click', function() {
                const id = this.dataset.id;
                loadRetailer(id);
                document.querySelectorAll('.retailer-row').forEach(r => r.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        function loadRetailer(id) {
            selectedId = id;
            const retailer = retailers.find(r => r.id == id);
            if (!retailer) return;
            
            document.getElementById('retailerId').value = retailer.id;
            document.getElementById('name').value = retailer.name;
            document.getElementById('street').value = retailer.street || '';
            document.getElementById('city').value = retailer.city || '';
            document.getElementById('province').value = retailer.province || '';
            document.getElementById('postal_code').value = retailer.postal_code || '';
            document.getElementById('country').value = retailer.country || 'Canada';
            document.getElementById('status').value = retailer.status || 'Active';
            document.getElementById('phone').value = retailer.phone || '';
            document.getElementById('email').value = retailer.email || '';
            document.getElementById('website').value = retailer.website || '';
            document.getElementById('lat').value = retailer.lat || '';
            document.getElementById('lng').value = retailer.lng || '';
            
            validateCoordinates();
            document.getElementById('editForm').classList.add('active');
        }

        // Validate and display coordinate information
        function validateCoordinates() {
            const lat = parseFloat(document.getElementById('lat').value);
            const lng = parseFloat(document.getElementById('lng').value);
            const info = document.getElementById('coordsInfo');
            
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                info.innerHTML = `⚠️ <strong>No coordinates set</strong><br><small>Retailer will not appear on map until geocoded</small>`;
                info.style.color = '#92400e';
                info.style.background = '#fef3c7';
                return;
            }
            
            const isValid = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
            const isPlaceholder = (lat === 50 && lng === -100);
            
            if (!isValid) {
                info.innerHTML = `❌ <strong>Invalid coordinates</strong><br><small>Latitude must be -90 to 90, Longitude must be -180 to 180</small>`;
                info.style.color = '#991b1b';
                info.style.background = '#fee2e2';
            } else if (isPlaceholder) {
                info.innerHTML = `⚠️ <strong>Placeholder coordinates</strong><br><small>Center of Canada (50, -100) - please geocode accurate location</small>`;
                info.style.color = '#92400e';
                info.style.background = '#fef3c7';
            } else {
                const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
                info.innerHTML = `✅ <strong>Valid coordinates</strong><br>
                    <small>📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</small><br>
                    <small><a href="${mapsUrl}" target="_blank" style="color: #2563eb;">View on Google Maps →</a></small>`;
                info.style.color = '#065f46';
                info.style.background = '#d1fae5';
            }
        }

        // Geocode address using Google Maps API
        async function geocodeAddress() {
            const address = document.getElementById('street').value;
            const city = document.getElementById('city').value;
            const province = document.getElementById('province').value;
            const postal_code = document.getElementById('postal_code').value;
            
            if (!address || !city || !province) {
                alert('⚠️ Please fill in Street, City, and Province before geocoding');
                return;
            }
            
            const btn = document.getElementById('geocodeBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '🔍 Geocoding...';
            
            try {
                const response = await fetch('api/geocode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ address, city, province, postal_code })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Geocode API returned non-JSON:', text.substring(0, 500));
                    alert('❌ Geocoding API error: Expected JSON but received HTML.\n\nCheck browser console for details.');
                    return;
                }
                
                if (data.auth_failed) {
                    alert('⚠️ Session expired. Please refresh the page and log in again.');
                    window.location.reload();
                    return;
                }
                
                if (data.success && data.coordinates) {
                    document.getElementById('lat').value = data.coordinates.lat;
                    document.getElementById('lng').value = data.coordinates.lng;
                    validateCoordinates();
                    alert(`✅ Geocoding successful!\n\nCoordinates: ${data.coordinates.lat}, ${data.coordinates.lng}\n\nDon't forget to click "Save Changes" to update the database.`);
                } else {
                    alert('❌ Geocoding failed: ' + (data.error || 'Could not find coordinates for this address.'));
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                alert('❌ Error contacting geocoding service: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Geocode for the Add New form
        async function geocodeAddForm() {
            const address = document.querySelector('#addForm input[name="street"]').value;
            const city = document.querySelector('#addForm input[name="city"]').value;
            const province = document.querySelector('#addForm input[name="province"]').value;
            const postal_code = document.querySelector('#addForm input[name="postal_code"]').value;
            
            if (!address || !city || !province) {
                alert('⚠️ Please fill in Street, City, and Province before geocoding');
                return;
            }
            
            try {
                const response = await fetch('api/geocode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ address, city, province, postal_code })
                });
                
                const data = await response.json();
                
                if (data.success && data.coordinates) {
                    document.getElementById('add_lat').value = data.coordinates.lat;
                    document.getElementById('add_lng').value = data.coordinates.lng;
                    alert(`✅ Geocoding successful!\n\nCoordinates: ${data.coordinates.lat}, ${data.coordinates.lng}`);
                } else {
                    alert('❌ Geocoding failed: ' + (data.error || 'Could not find coordinates.'));
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                alert('❌ Error during geocoding: ' + error.message);
            }
        }

        function deleteRetailer() {
            if (!selectedId) return;
            
            const retailer = retailers.find(r => r.id == selectedId);
            if (!confirm(`Mark "${retailer.name}" as INACTIVE?\n\nThis will remove it from the public map but keep the data.`)) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_retailer">
                <input type="hidden" name="retailer_id" value="${selectedId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function clearForm() {
            document.getElementById('editForm').classList.remove('active');
            document.querySelectorAll('.retailer-row').forEach(r => r.classList.remove('selected'));
            selectedId = null;
        }

        function toggleAddForm() {
            const form = document.getElementById('addForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
