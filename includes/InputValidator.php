<?php
/**
 * Input Validation and Sanitization Class
 * Provides secure validation methods for user inputs
 * Cadman Manufacturing - Security Framework
 */

class InputValidator {
    
    /**
     * Validate and sanitize collection name
     * Only allows specific collection names
     */
    public static function validateCollection($input) {
        $allowedCollections = [
            'bands', 'family', 'corp', 'corporate', 'signet', 'frontline_workers',
            'accessories', 'ladys_stoneset', 'ladies_jewelry', 'engagement'
        ];
        
        $sanitized = strtolower(trim($input));
        return in_array($sanitized, $allowedCollections) ? $sanitized : false;
    }
    
    /**
     * Validate product ID
     * Allows alphanumeric characters, underscores, dashes
     */
    public static function validateProductId($input) {
        $sanitized = trim($input);
        return preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $sanitized) ? $sanitized : false;
    }
    
    /**
     * Validate category name
     * Allows alphanumeric characters and underscores
     */
    public static function validateCategory($input) {
        $allowedCategories = [
            'mother', 'father', 'daughter', 'mens', 'womens', 'unisex',
            'wedding', 'engagement', 'anniversary', 'corporate', 'medical',
            'gems', 'pearls', 'gold', 'silver', 'platinum'
        ];
        
        $sanitized = strtolower(trim($input));
        return in_array($sanitized, $allowedCategories) ? $sanitized : false;
    }
    
    /**
     * Validate search term
     * Removes dangerous characters, limits length
     */
    public static function validateSearchTerm($input) {
        $sanitized = trim($input);
        // Remove potentially dangerous characters
        $sanitized = preg_replace('/[<>"\'\&]/', '', $sanitized);
        // Limit length
        $sanitized = substr($sanitized, 0, 100);
        
        return strlen($sanitized) >= 1 ? $sanitized : false;
    }
    
    /**
     * Validate filename for image operations
     * Only allows safe filename characters
     */
    public static function validateFilename($input) {
        $sanitized = trim($input);
        // Only allow alphanumeric, dots, hyphens, underscores
        return preg_match('/^[a-zA-Z0-9._-]{1,100}\.(png|jpg|jpeg|gif|webp)$/i', $sanitized) ? $sanitized : false;
    }
    
    /**
     * Validate base name for product grouping
     * Allows alphanumeric characters, underscores, dashes
     */
    public static function validateBaseName($input) {
        $sanitized = trim($input);
        return preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $sanitized) ? $sanitized : false;
    }
    
    /**
     * Validate page number for pagination
     */
    public static function validatePageNumber($input) {
        $page = intval($input);
        return ($page > 0 && $page <= 1000) ? $page : 1;
    }
    
    /**
     * Validate boolean values
     */
    public static function validateBoolean($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
    
    /**
     * General HTML output sanitization
     */
    public static function sanitizeOutput($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($input) {
        $sanitized = filter_var(trim($input), FILTER_VALIDATE_EMAIL);
        return $sanitized !== false ? $sanitized : false;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}