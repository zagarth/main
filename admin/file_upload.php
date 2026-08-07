<?php
require_once 'auth.php';
require_once __DIR__ . '/../includes/FileUploadSecurity.php';
require_once __DIR__ . '/../includes/InputValidator.php';
require_once __DIR__ . '/../includes/JewelryDatabase.php';
requireAdmin();

// Initialize database connection
$jewelryDB = new JewelryDatabase();

// Log admin access
logAdminAction('FILE_UPLOAD_ACCESS');

// Get available collections
function getAvailableCollections() {
    $homeDir = dirname(__DIR__);
    $collections = [];
    
    // Look for collection directories ending with _php
    $directories = glob($homeDir . '/*_php', GLOB_ONLYDIR);
    
    foreach ($directories as $dir) {
        $collectionName = basename($dir);
        $displayName = ucfirst(str_replace(['_php', '_'], [' ', ' '], $collectionName));
        $collections[$collectionName] = [
            'name' => $displayName,
            'path' => $dir,
            'images_path' => $dir . '/images',
            'exists' => file_exists($dir . '/images')
        ];
    }
    
    return $collections;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $response = ['success' => false, 'message' => '', 'uploaded' => [], 'errors' => []];
    
    try {
        $collection = InputValidator::validateCollection($_POST['collection'] ?? '');
        $subcategory = InputValidator::validateCategory($_POST['subcategory'] ?? '');
        
        if ($collection === false) {
            throw new Exception('Invalid collection specified');
        }
        
        $collections = getAvailableCollections();
        if (!isset($collections[$collection])) {
            throw new Exception('Collection not found');
        }
        
        $targetDir = $collections[$collection]['images_path'];
        if ($subcategory !== false && !empty($subcategory)) {
            $targetDir .= '/' . $subcategory;
        }
        
        // Validate target directory security
        if (!FileUploadSecurity::validateUploadDirectory(dirname($targetDir))) {
            throw new Exception('Invalid upload directory');
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception('Failed to create target directory');
            }
        }
        
        $uploadedFiles = [];
        $files = $_FILES['files'];
        
        // Handle multiple files
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            
            if ($fileError !== UPLOAD_ERR_OK) {
                $response['errors'][] = "Upload error for file $fileName: " . $fileError;
                continue;
            }
            
            // Enhanced file validation with security checks
            $validation = FileUploadSecurity::validateUploadedFile(
                $fileTmp, 
                $fileName, 
                $fileSize, 
                5 * 1024 * 1024 // 5MB max (reduced from 10MB)
            );
            
            if (!$validation['valid']) {
                $response['errors'][] = "File $fileName failed validation: " . implode(', ', $validation['errors']);
                FileUploadSecurity::logSecurityEvent('file_validation_failed', [
                    'filename' => $fileName,
                    'errors' => $validation['errors']
                ]);
                continue;
            }
            
            // Generate secure filename
            $secureFileName = FileUploadSecurity::generateSecureFilename($fileName, $targetDir);
            $targetPath = $targetDir . '/' . $secureFileName;
            
            if (move_uploaded_file($fileTmp, $targetPath)) {
                // Get relative path for database storage
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'] . '/homesite/', '', $targetPath);
                
                // Get collection and category IDs
                $ids = $jewelryDB->getCollectionCategoryIds($collection, $subcategory);
                
                if ($ids) {
                    // Generate item code from filename
                    $itemCode = pathinfo($secureFileName, PATHINFO_FILENAME);
                    
                    // Check if item already exists
                    if (!$jewelryDB->itemCodeExists($collection, $itemCode)) {
                        // Get image dimensions
                        $imageInfo = @getimagesize($targetPath);
                        
                        // Generate item name
                        $itemName = ucwords(str_replace(['_', '-'], ' ', $itemCode));
                        
                        // Prepare item data
                        $itemData = [
                            'collection_id' => $ids['collection_id'],
                            'category_id' => $ids['category_id'],
                            'item_code' => $itemCode,
                            'item_name' => $itemName,
                            'description' => "New $itemName from our $collection collection.",
                            'base_price' => 500.00, // Default price, can be updated later
                            'file_path' => $relativePath,
                            'thumbnail_path' => null, // Will be generated later
                            'image_alt' => "$itemName - $collection",
                            'file_size' => $fileSize,
                            'image_width' => $imageInfo ? $imageInfo[0] : null,
                            'image_height' => $imageInfo ? $imageInfo[1] : null,
                            'mime_type' => $validation['mime_type'],
                            'sort_order' => 0
                        ];
                        
                        // Add to database
                        $jewelryDB->addItem($itemData);
                    }
                    
                    // Log upload in jewelry system
                    $jewelryDB->logUpload([
                        'original_filename' => $fileName,
                        'secure_filename' => $secureFileName,
                        'file_path' => $relativePath,
                        'collection_key' => $collection,
                        'category_key' => $subcategory,
                        'file_size' => $fileSize,
                        'mime_type' => $validation['mime_type'],
                        'upload_status' => 'uploaded',
                        'uploaded_by' => 'admin'
                    ]);
                }
                
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'secure_name' => $secureFileName,
                    'path' => $targetPath,
                    'size' => $fileSize,
                    'mime_type' => $validation['mime_type']
                ];
                
                // Log successful upload
                logAdminAction('FILE_UPLOAD', "Uploaded: $fileName as $secureFileName to $collection/" . ($subcategory ?: 'root'));
                FileUploadSecurity::logSecurityEvent('file_uploaded', [
                    'original_name' => $fileName,
                    'secure_name' => $secureFileName,
                    'collection' => $collection,
                    'subcategory' => $subcategory
                ]);
            } else {
                $response['errors'][] = "Failed to move uploaded file: $fileName";
            }
        }
        
        $response['success'] = count($uploadedFiles) > 0;
        $response['uploaded'] = $uploadedFiles;
        $response['message'] = count($uploadedFiles) . ' file(s) uploaded successfully';
        
        if (!empty($response['errors'])) {
            $response['message'] .= ' with ' . count($response['errors']) . ' errors';
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle subcategory listing
if (isset($_GET['action']) && $_GET['action'] === 'subcategories') {
    $collection = $_GET['collection'] ?? '';
    
    try {
        $categories = $jewelryDB->getCategories($collection);
        $subcategories = [];
        
        foreach ($categories as $category) {
            $subcategories[] = [
                'key' => $category['category_key'],
                'name' => $category['category_name'],
                'item_count' => $category['item_count']
            ];
        }
        
        if (is_dir($imagesPath)) {
            $dirs = glob($imagesPath . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $subcategories[] = basename($dir);
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($subcategories);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$collections = getAvailableCollections();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload - Admin Portal</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        /* Additional page-specific styles can go here if needed */
    </style>
            </style>
</head>
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-header">
            <div class="back-nav">
                <a href="index.php">← Back to Admin Portal</a>
            </div>
            <h1 style="color: #8B008B; margin: 0;">File Upload System</h1>
            <p style="color: #666; margin: 10px 0 0 0;">Upload images to jewelry collections</p>
        </div>
        
        <div id="alertContainer"></div>
        
        <div class="upload-form">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="collection">Select Collection:</label>
                    <select id="collection" name="collection" required>
                        <option value="">Choose a collection...</option>
                        <?php foreach ($collections as $key => $collection): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>">
                                <?php echo htmlspecialchars($collection['name']); ?>
                                <?php if (!$collection['exists']): ?>
                                    (No images folder)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subcategory">Subcategory (Optional):</label>
                    <select id="subcategorySelect" name="subcategory" style="display: none;">
                        <option value="">Select existing subcategory...</option>
                    </select>
                    <input type="text" id="subcategoryInput" name="subcategory" placeholder="Enter subcategory name or leave empty for root directory">
                </div>
                
                <div id="collectionInfo" class="collection-info" style="display: none;">
                    <h4>Collection Information</h4>
                    <div id="collectionStats" class="collection-stats"></div>
                </div>
                
                <div class="form-group">
                    <label>Select Files to Upload:</label>
                    <div class="file-drop-zone" id="dropZone">
                        <div>
                            <h3>📁 Drop files here or click to browse</h3>
                            <p>Supported formats: JPG, PNG, GIF, WebP (max 10MB each)</p>
                        </div>
                        <input type="file" id="fileInput" name="files[]" multiple accept="image/*" class="file-input">
                    </div>
                </div>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div id="fileList" class="file-list"></div>
                
                <button type="submit" class="upload-button" id="uploadButton" disabled>
                    📤 Upload Files
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let selectedFiles = [];
        
        // Elements
        const collectionSelect = document.getElementById('collection');
        const subcategorySelect = document.getElementById('subcategorySelect');
        const subcategoryInput = document.getElementById('subcategoryInput');
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadButton = document.getElementById('uploadButton');
        const uploadForm = document.getElementById('uploadForm');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const alertContainer = document.getElementById('alertContainer');
        const collectionInfo = document.getElementById('collectionInfo');
        const collectionStats = document.getElementById('collectionStats');
        
        // Collection change handler
        collectionSelect.addEventListener('change', async function() {
            const collection = this.value;
            
            if (collection) {
                // Load subcategories
                try {
                    const response = await fetch(`file_upload.php?action=subcategories&collection=${collection}`);
                    const subcategories = await response.json();
                    
                    subcategorySelect.innerHTML = '<option value="">Select existing subcategory...</option>';
                    subcategories.forEach(sub => {
                        subcategorySelect.innerHTML += `<option value="${sub}">${sub}</option>`;
                    });
                    
                    if (subcategories.length > 0) {
                        subcategorySelect.style.display = 'block';
                        subcategoryInput.placeholder = 'Or enter new subcategory name';
                    } else {
                        subcategorySelect.style.display = 'none';
                        subcategoryInput.placeholder = 'Enter subcategory name or leave empty for root directory';
                    }
                    
                    // Load collection info
                    loadCollectionInfo(collection);
                    
                } catch (error) {
                    console.error('Error loading subcategories:', error);
                }
                
                updateUploadButton();
            } else {
                subcategorySelect.style.display = 'none';
                collectionInfo.style.display = 'none';
                updateUploadButton();
            }
        });
        
        // Subcategory select handler
        subcategorySelect.addEventListener('change', function() {
            if (this.value) {
                subcategoryInput.value = this.value;
            }
        });
        
        // File drop zone handlers
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        // Handle selected files
        function handleFiles(files) {
            Array.from(files).forEach(file => {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showAlert('Invalid file type: ' + file.name, 'error');
                    return;
                }
                
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('File too large: ' + file.name, 'error');
                    return;
                }
                
                selectedFiles.push(file);
            });
            
            updateFileList();
            updateUploadButton();
        }
        
        // Update file list display
        function updateFileList() {
            fileList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-info">
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${formatFileSize(file.size)}</div>
                    </div>
                    <button type="button" class="file-remove" onclick="removeFile(${index})">Remove</button>
                `;
                fileList.appendChild(fileItem);
            });
        }
        
        // Remove file from selection
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
            updateUploadButton();
        }
        
        // Update upload button state
        function updateUploadButton() {
            const hasCollection = collectionSelect.value !== '';
            const hasFiles = selectedFiles.length > 0;
            
            uploadButton.disabled = !(hasCollection && hasFiles);
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Show alert message
        function showAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            
            // Handle multi-line messages
            if (message.includes('\n')) {
                const lines = message.split('\n');
                lines.forEach((line, index) => {
                    const p = document.createElement('p');
                    p.textContent = line;
                    p.style.margin = index === 0 ? '0 0 8px 0' : '4px 0';
                    alert.appendChild(p);
                });
            } else {
                alert.textContent = message;
            }
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            // Auto-hide after 8 seconds for error messages, 5 for others
            const hideAfter = type === 'error' ? 8000 : 5000;
            setTimeout(() => {
                alert.remove();
            }, hideAfter);
        }
        
        // Load collection information
        async function loadCollectionInfo(collection) {
            // This would typically fetch collection stats from an API
            // For now, show basic collection info
            collectionInfo.style.display = 'block';
            collectionStats.innerHTML = `
                <div class="stat-item">
                    <div class="stat-number">-</div>
                    <div class="stat-label">Total Images</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">-</div>
                    <div class="stat-label">Subcategories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">-</div>
                    <div class="stat-label">Last Updated</div>
                </div>
            `;
        }
        
        // Form submission handler
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (selectedFiles.length === 0) {
                showAlert('Please select files to upload', 'error');
                return;
            }
            
            if (!collectionSelect.value) {
                showAlert('Please select a collection', 'error');
                return;
            }
            
            // Show progress
            progressBar.style.display = 'block';
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('collection', collectionSelect.value);
            formData.append('subcategory', subcategoryInput.value);
            
            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });
            
            try {
                const xhr = new XMLHttpRequest();
                
                // Progress handler
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressFill.style.width = percent + '%';
                    }
                });
                
                // Response handler
                xhr.addEventListener('load', () => {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            showAlert(response.message, 'success');
                            selectedFiles = [];
                            updateFileList();
                            fileInput.value = '';
                            
                            // Show summary if provided
                            if (response.summary) {
                                console.log('Upload Summary:', response.summary);
                            }
                        } else {
                            // Handle enhanced error reporting
                            let errorMessage = response.message || 'Upload failed';
                            
                            // If there are specific errors, show them
                            if (response.errors && Array.isArray(response.errors)) {
                                errorMessage += '\n\nDetailed Errors:';
                                response.errors.forEach(error => {
                                    errorMessage += '\n• ' + error;
                                });
                            }
                            
                            showAlert(errorMessage, 'error');
                            
                            // Log security-related errors to console for debugging
                            if (response.security_log) {
                                console.warn('Security Issues:', response.security_log);
                            }
                        }
                    } catch (error) {
                        showAlert('Error processing server response', 'error');
                        console.error('Response parsing error:', error);
                    }
                    
                    // Reset UI
                    progressBar.style.display = 'none';
                    progressFill.style.width = '0%';
                    uploadButton.disabled = false;
                    uploadButton.textContent = '📤 Upload Files';
                    updateUploadButton();
                });
                
                // Error handler
                xhr.addEventListener('error', () => {
                    showAlert('Upload failed', 'error');
                    progressBar.style.display = 'none';
                    progressFill.style.width = '0%';
                    uploadButton.disabled = false;
                    uploadButton.textContent = '📤 Upload Files';
                    updateUploadButton();
                });
                
                xhr.open('POST', 'file_upload.php');
                xhr.send(formData);
                
            } catch (error) {
                showAlert('Upload failed: ' + error.message, 'error');
                progressBar.style.display = 'none';
                uploadButton.disabled = false;
                uploadButton.textContent = '📤 Upload Files';
                updateUploadButton();
            }
        });
    </script>
</body>
</html>
