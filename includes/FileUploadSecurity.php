<?php
/**
 * Enhanced File Upload Security Class
 * Provides comprehensive file upload validation and security
 * Cadman Manufacturing - Security Framework
 */

class FileUploadSecurity {
    
    // Allowed image types with their magic bytes
    private static $allowedImageTypes = [
        'image/jpeg' => [
            'extensions' => ['jpg', 'jpeg'],
            'magic_bytes' => [
                [0xFF, 0xD8, 0xFF], // JPEG
            ]
        ],
        'image/png' => [
            'extensions' => ['png'],
            'magic_bytes' => [
                [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A], // PNG
            ]
        ],
        'image/gif' => [
            'extensions' => ['gif'],
            'magic_bytes' => [
                [0x47, 0x49, 0x46, 0x38, 0x37, 0x61], // GIF87a
                [0x47, 0x49, 0x46, 0x38, 0x39, 0x61], // GIF89a
            ]
        ],
        'image/webp' => [
            'extensions' => ['webp'],
            'magic_bytes' => [
                [0x52, 0x49, 0x46, 0x46], // RIFF header (WebP)
            ]
        ]
    ];
    
    /**
     * Comprehensive file validation
     */
    public static function validateUploadedFile($fileTmp, $fileName, $fileSize, $maxSize = 5242880) { // 5MB default
        $errors = [];
        
        // Check if file was actually uploaded
        if (!is_uploaded_file($fileTmp)) {
            $errors[] = "File was not uploaded properly";
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate file size
        if ($fileSize > $maxSize) {
            $errors[] = "File size exceeds maximum allowed (" . self::formatBytes($maxSize) . ")";
        }
        
        if ($fileSize === 0) {
            $errors[] = "File is empty";
        }
        
        // Validate filename
        $sanitizedName = self::sanitizeFilename($fileName);
        if ($sanitizedName === false) {
            $errors[] = "Invalid filename";
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $validExtensions = [];
        foreach (self::$allowedImageTypes as $mimeType => $data) {
            $validExtensions = array_merge($validExtensions, $data['extensions']);
        }
        
        if (!in_array($extension, $validExtensions)) {
            $errors[] = "File extension not allowed. Allowed: " . implode(', ', $validExtensions);
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, self::$allowedImageTypes)) {
            $errors[] = "File type not allowed. Detected: " . $mimeType;
        }
        
        // Validate magic bytes (file signature)
        if (!self::validateMagicBytes($fileTmp, $mimeType)) {
            $errors[] = "File content does not match its type";
        }
        
        // Additional security checks
        if (!self::performSecurityChecks($fileTmp, $fileName)) {
            $errors[] = "File failed security validation";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_name' => $sanitizedName,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Validate file magic bytes (file signature)
     */
    private static function validateMagicBytes($filePath, $mimeType) {
        if (!isset(self::$allowedImageTypes[$mimeType])) {
            return false;
        }
        
        $file = fopen($filePath, 'rb');
        if (!$file) {
            return false;
        }
        
        $header = fread($file, 16); // Read first 16 bytes
        fclose($file);
        
        if ($header === false) {
            return false;
        }
        
        $magicBytesList = self::$allowedImageTypes[$mimeType]['magic_bytes'];
        
        foreach ($magicBytesList as $magicBytes) {
            $match = true;
            for ($i = 0; $i < count($magicBytes); $i++) {
                if (!isset($header[$i]) || ord($header[$i]) !== $magicBytes[$i]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Perform additional security checks
     */
    private static function performSecurityChecks($filePath, $fileName) {
        // Check for embedded PHP code
        $content = file_get_contents($filePath, false, null, 0, 1024); // Read first 1KB
        if ($content === false) {
            return false;
        }
        
        // Look for PHP tags
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false || strpos($content, '<%') !== false) {
            return false;
        }
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            'eval(',
            'exec(',
            'system(',
            'shell_exec(',
            'passthru(',
            'base64_decode(',
            'file_get_contents(',
            'fopen(',
            'include',
            'require'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return false;
            }
        }
        
        // Validate that it's actually a valid image by attempting to create an image resource
        return self::validateImageIntegrity($filePath);
    }
    
    /**
     * Validate image integrity by attempting to load it
     */
    private static function validateImageIntegrity($filePath) {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }
        
        // Try to create image resource to ensure it's not corrupted
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                $image = @imagecreatefromwebp($filePath);
                break;
            default:
                return false;
        }
        
        if ($image === false) {
            return false;
        }
        
        imagedestroy($image);
        return true;
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($fileName) {
        // Remove any path components
        $fileName = basename($fileName);
        
        // Remove dangerous characters
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        
        // Ensure it has a valid structure
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]{2,5}$/', $fileName)) {
            return false;
        }
        
        // Limit length
        if (strlen($fileName) > 100) {
            $pathInfo = pathinfo($fileName);
            $name = substr($pathInfo['filename'], 0, 95);
            $fileName = $name . '.' . $pathInfo['extension'];
        }
        
        return $fileName;
    }
    
    /**
     * Generate secure filename to prevent conflicts and attacks
     */
    public static function generateSecureFilename($originalName, $targetDir) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $secureFileName = $baseName . '_' . $timestamp . '_' . $random . '.' . $extension;
        
        // Ensure uniqueness
        $counter = 1;
        while (file_exists($targetDir . '/' . $secureFileName)) {
            $secureFileName = $baseName . '_' . $timestamp . '_' . $random . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $secureFileName;
    }
    
    /**
     * Validate upload directory
     */
    public static function validateUploadDirectory($directory) {
        // Check if directory exists
        if (!is_dir($directory)) {
            return false;
        }
        
        // Check if writable
        if (!is_writable($directory)) {
            return false;
        }
        
        // Check if it's not a system directory
        $systemDirs = ['/bin', '/usr/bin', '/etc', '/var/www/html', '/'];
        $realDir = realpath($directory);
        
        foreach ($systemDirs as $sysDir) {
            if (strpos($realDir, $sysDir) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Format bytes for display
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        error_log('File Upload Security: ' . json_encode($logEntry));
    }
}