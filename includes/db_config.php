<?php
/**
 * Database Configuration
 * Centralized database connection settings
 * Uses encrypted credentials from the shared config loader.
 */

require_once __DIR__ . '/db_config_encrypted.php';

if (!defined('DB_USER')) {
    define('DB_USER', DB_ADMIN_USER ?? '');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', DB_ADMIN_PASS ?? '');
}

if (!function_exists('getTaxRateForProvince')) {
    function getTaxRateForProvince($province) {
        $taxRates = [
            'AB' => 5.0,
            'BC' => 12.0,
            'MB' => 12.0,
            'NB' => 15.0,
            'NL' => 15.0,
            'NF' => 15.0,
            'NS' => 15.0,
            'ON' => 13.0,
            'PE' => 15.0,
            'QC' => 14.975,
            'SK' => 11.0,
            'NT' => 5.0,
            'NU' => 5.0,
            'YT' => 5.0,
        ];

        if ($province === null) {
            return 0.0;
        }

        $cleanProvince = trim((string)$province);
        if ($cleanProvince === '') {
            return 0.0;
        }

        $normalized = strtoupper(str_replace(['.', ' ', '-'], '', $cleanProvince));
        $aliases = [
            'BRITISHCOLUMBIA' => 'BC',
            'BC' => 'BC',
            'ALBERTA' => 'AB',
            'MANITOBA' => 'MB',
            'NEWBRUNSWICK' => 'NB',
            'NEWFOUNDLAND' => 'NL',
            'NOVA' => 'NS',
            'SCOTIA' => 'NS',
            'ONTARIO' => 'ON',
            'PRINCEEDWARDISLAND' => 'PE',
            'QUEBEC' => 'QC',
            'SASKATCHEWAN' => 'SK',
            'NORTHWESTTERRITORIES' => 'NT',
            'NUNAVUT' => 'NU',
            'YUKON' => 'YT',
        ];

        if (isset($aliases[$normalized])) {
            return $taxRates[$aliases[$normalized]] ?? 0.0;
        }

        return $taxRates[$normalized] ?? 0.0;
    }
}

if (!function_exists('calculateOrderBreakdown')) {
    function calculateOrderBreakdown($totalAmount, $taxRate, $discountAmount = 0.0) {
        $totalAmount = (float)$totalAmount;
        $discountAmount = (float)$discountAmount;
        $taxRate = (float)$taxRate;

        $subtotal = $taxRate > 0 ? round($totalAmount / (1 + ($taxRate / 100)), 2) : $totalAmount;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $displayTotal = round($subtotal + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $displayTotal,
        ];
    }
}

if (!function_exists('calculateOrderBreakdownFromProvince')) {
    function calculateOrderBreakdownFromProvince($totalAmount, $province, $discountAmount = 0.0) {
        return calculateOrderBreakdown($totalAmount, getTaxRateForProvince($province), $discountAmount);
    }
}
?>
