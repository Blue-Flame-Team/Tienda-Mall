<?php
/**
 * Currency helper functions for Tienda Mall
 * These functions help standardize currency formatting across the site
 */

// Make sure database connection is available
if (!function_exists('Database::getInstance')) {
    require_once 'db.php';
}

/**
 * Get the configured currency code (EGP, USD, etc)
 * 
 * @return string Currency code
 */
function getCurrencyCode() {
    static $currencyCode = null;
    
    if ($currencyCode === null) {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'currency' LIMIT 1");
            $result = $stmt->fetch();
            $currencyCode = $result ? $result['setting_value'] : 'EGP';
        } catch (Exception $e) {
            // Default to EGP if there's an error
            $currencyCode = 'EGP';
        }
    }
    
    return $currencyCode;
}

/**
 * Get the configured currency symbol (L.E, $, etc)
 * 
 * @return string Currency symbol
 */
function getCurrencySymbol() {
    static $symbol = null;
    
    if ($symbol === null) {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'currency_symbol' LIMIT 1");
            $result = $stmt->fetch();
            $symbol = $result ? $result['setting_value'] : 'L.E';
        } catch (Exception $e) {
            // Default to L.E if there's an error
            $symbol = 'L.E';
        }
    }
    
    return $symbol;
}

/**
 * Format money amount with the site's configured currency symbol
 * 
 * @param float $amount Amount to format
 * @param bool $includeSymbol Whether to include the currency symbol
 * @return string Formatted amount
 */
function formatMoney($amount, $includeSymbol = true) {
    $formatted = number_format($amount, 2);
    
    if (!$includeSymbol) {
        return $formatted;
    }
    
    $symbol = getCurrencySymbol();
    $code = getCurrencyCode();
    
    // Different currencies have different symbol placements
    if ($code == 'EGP' || $symbol == 'L.E' || $symbol == 'ج.م') {
        // Egyptian Pound: symbol after the number
        return $formatted . ' ' . $symbol;
    } else if ($code == 'EUR' || $symbol == '€') {
        // Euro: symbol after the number
        return $formatted . ' ' . $symbol;
    } else {
        // USD and others: symbol before the number
        return $symbol . $formatted;
    }
}
?>
