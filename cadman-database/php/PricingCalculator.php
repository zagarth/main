<?php
/**
 * Cadman Manufacturing Pricing Calculator
 * Centralized pricing logic matching OE27/AR12 mainframe formulas
 * 
 * Used by:
 * - AR12 Calculator (cadman-database/index.php)
 * - Orders Page (cadman-database/orders.php)
 * - Admin Pricing Management (admin/manage_pricing.php)
 */

class PricingCalculator {
    
    private $goldPrice;      // Price per troy ounce
    private $laborRate;      // $ per hour
    private $sterlingGF;     // Sterling gold factor (130)
    private $baseMargin;     // Market base margin %
    
    /**
     * Constructor - loads system settings
     */
    public function __construct($pdo = null) {
        if ($pdo) {
            $this->loadSystemSettings($pdo);
        } else {
            // Default values if no DB connection
            $this->goldPrice = 7400;
            $this->laborRate = 28;
            $this->sterlingGF = 130;
            $this->baseMargin = 20;
        }
    }
    
    /**
     * Load system settings from database
     */
    private function loadSystemSettings($pdo) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $this->goldPrice = floatval($settings['gold_price'] ?? 7400);
        $this->laborRate = floatval($settings['labor_rate'] ?? 28);
        $this->sterlingGF = floatval($settings['sterling_gf'] ?? 130);
        $this->baseMargin = floatval($settings['base_margin'] ?? 20);
    }
    
    /**
     * Calculate gold cost
     * 
     * Formula: (goldGrams × goldPricePerOz) / 31.1035 troy oz/gram
     * Karat multipliers: 10K=1.0, 14K=1.4, 18K=1.8
     * 
     * @param float $goldGrams Weight in grams
     * @param string $karat Karat type (10K, 14K, 18K)
     * @return float Gold cost in dollars
     */
    public function calculateGoldCost($goldGrams, $karat = '10K') {
        if ($goldGrams == 0) return 0.0;

        $karat = $this->normalizeKarat($karat);
        
        // Apply karat purity factor to get pure gold weight
        // 10K = 10/24 = 41.67%, 14K = 14/24 = 58.33%, 18K = 18/24 = 75%
        $pureGoldGrams = $goldGrams;
        switch ($karat) {
            case '10K':
                $pureGoldGrams *= (10/24);
                break;
            case '14K':
                $pureGoldGrams *= (14/24);
                break;
            case '18K':
                $pureGoldGrams *= (18/24);
                break;
            case '24K':
                break;
            default:
                $pureGoldGrams *= (10/24);
                break;
        }
        
        // Convert troy ounce price to per-gram price (1 troy oz = 31.1035 grams)
        $pricePerGram = $this->goldPrice / 31.1035;
        
        // Calculate cost based on pure gold weight
        $goldCost = $pricePerGram * $pureGoldGrams;
        
        return $goldCost;
    }

    /**
     * Normalize metal labels to the base karat used for pricing.
     *
     * Examples:
     * - 10KTT, 10KW, 10KY -> 10K
     * - 14W, 14Y -> 14K
     * - 18W, 18Y -> 18K
     * - 24K stays 24K
     * - Unknown labels default to 10K so they do not price as pure gold
     */
    private function normalizeKarat($karat) {
        $karat = strtoupper(trim((string) $karat));

        if ($karat === '') {
            return '10K';
        }

        if ($karat === '24K') {
            return '24K';
        }

        if (preg_match('/^(10|14|18)K?(?:TT|W|Y)?$/', $karat, $matches)) {
            return $matches[1] . 'K';
        }

        if (preg_match('/^(10|14|18)$/', $karat, $matches)) {
            return $matches[1] . 'K';
        }

        return '10K';
    }
    
    /**
     * Calculate sterling silver cost
     * 
     * Formula from AR12 line 423:
     * sterlingCost = grams × sterlingGF × 0.03215076
     * 
     * @param float $sterlingGrams Weight in grams
     * @return float Sterling cost in dollars
     */
    public function calculateSterlingCost($sterlingGrams) {
        if ($sterlingGrams == 0) return 0.0;
        
        return $sterlingGrams * $this->sterlingGF * 0.03215076;
    }
    
    /**
     * Calculate labor cost
     * 
     * Formula: laborHours × laborRate
     * 
     * @param float $laborHours Hours of labor
     * @return float Labor cost in dollars
     */
    public function calculateLaborCost($laborHours) {
        return $laborHours * $this->laborRate;
    }
    
    /**
     * Calculate total cost of item
     * 
     * @param array $params Associative array with:
     *   - goldGrams (optional)
     *   - karat (optional, default '10K')
     *   - sterlingGrams (optional)
     *   - laborHours (optional)
     *   - materialCost (optional)
     *   - stoneCost (optional)
     *   - starCost (optional)
     *   - stoneSettingCost (optional)
     * @return float Total cost in dollars
     */
    public function calculateTotalCost($params) {
        $goldCost = $this->calculateGoldCost(
            $params['goldGrams'] ?? 0,
            $params['karat'] ?? '10K'
        );
        
        $sterlingCost = $this->calculateSterlingCost($params['sterlingGrams'] ?? 0);
        $laborCost = $this->calculateLaborCost($params['laborHours'] ?? 0);
        
        $totalCost = 
            $goldCost +
            $sterlingCost +
            $laborCost +
            ($params['materialCost'] ?? 0) +
            ($params['stoneCost'] ?? 0) +
            ($params['starCost'] ?? 0) +
            ($params['stoneSettingCost'] ?? 0);
        
        return $totalCost;
    }
    
    /**
     * Calculate selling price with markup and base margin
     * 
     * Formula: totalCost × (1 + markup%) × (1 + baseMargin%)
     * 
     * @param float $totalCost Total cost
     * @param float $markupPercent Item markup percentage
     * @param float $baseMarginPercent Base margin percentage (optional, uses system default)
     * @return float Selling price before rounding
     */
    public function calculateSellingPrice($totalCost, $markupPercent, $baseMarginPercent = null) {
        if ($baseMarginPercent === null) {
            $baseMarginPercent = $this->baseMargin;
        }
        
        return $totalCost * (1 + $markupPercent / 100) * (1 + $baseMarginPercent / 100);
    }
    
    /**
     * Quarter-round a price to nearest $0.25
     * Matches OE27 mainframe logic (lines 738-742)
     * 
     * Thresholds:
     * - >75¢ rounds to next dollar
     * - >50¢ rounds to $0.75
     * - >25¢ rounds to $0.50
     * - else rounds to $0.25
     * 
     * @param float $price Price to round
     * @return float Rounded price
     */
    public function roundToQuarter($price) {
        $dollars = floor($price);
        $fractionalPart = round(($price - $dollars) * 100000);
        $preciseCents = $fractionalPart / 1000;
        
        if ($preciseCents > 75) {
            return $dollars + 1.00;
        } elseif ($preciseCents > 50) {
            return $dollars + 0.75;
        } elseif ($preciseCents > 25) {
            return $dollars + 0.50;
        } else {
            return $dollars + 0.25;
        }
    }
    
    /**
     * Complete price calculation with rounding
     * 
     * @param array $params See calculateTotalCost() for parameters
     * @param float $markupPercent Item markup percentage
     * @param float $baseMarginPercent Base margin percentage (optional)
     * @return array ['cost' => float, 'price' => float, 'goldCost' => float, 'sterlingCost' => float, 'laborCost' => float]
     */
    public function calculatePrice($params, $markupPercent, $baseMarginPercent = null) {
        $goldCost = $this->calculateGoldCost(
            $params['goldGrams'] ?? 0,
            $params['karat'] ?? '10K'
        );
        
        $sterlingCost = $this->calculateSterlingCost($params['sterlingGrams'] ?? 0);
        $laborCost = $this->calculateLaborCost($params['laborHours'] ?? 0);
        
        $totalCost = $this->calculateTotalCost($params);
        $sellingPrice = $this->calculateSellingPrice($totalCost, $markupPercent, $baseMarginPercent);
        $roundedPrice = $this->roundToQuarter($sellingPrice);
        
        return [
            'goldCost' => $goldCost,
            'sterlingCost' => $sterlingCost,
            'laborCost' => $laborCost,
            'totalCost' => $totalCost,
            'sellingPrice' => $sellingPrice,
            'roundedPrice' => $roundedPrice
        ];
    }
    
    /**
     * Get system settings
     */
    public function getSettings() {
        return [
            'goldPrice' => $this->goldPrice,
            'laborRate' => $this->laborRate,
            'sterlingGF' => $this->sterlingGF,
            'baseMargin' => $this->baseMargin
        ];
    }
    
    /**
     * Set custom system settings (for testing or overrides)
     */
    public function setSettings($settings) {
        if (isset($settings['goldPrice'])) $this->goldPrice = $settings['goldPrice'];
        if (isset($settings['laborRate'])) $this->laborRate = $settings['laborRate'];
        if (isset($settings['sterlingGF'])) $this->sterlingGF = $settings['sterlingGF'];
        if (isset($settings['baseMargin'])) $this->baseMargin = $settings['baseMargin'];
    }
}
