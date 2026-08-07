<?php

/**
 * Shared pricing profile builder for product configurators.
 *
 * Produces a per-metal price map and resolves the displayed base price
 * from the configurator default metal selection.
 */
class ProductPricingProfile
{
    /**
     * Build likely base-code candidates from a product id.
     * Example: 3T00RM -> [3T00RM, 3T00R]
     *
     * @return array<int,string>
     */
    private static function productIdCandidates(string $productId): array
    {
        $id = trim($productId);
        if ($id === '') {
            return [];
        }

        $candidates = [$id];

        // Many plain-band IDs are gendered variants ending in M/L.
        if (preg_match('/[ML]$/i', $id) === 1 && strlen($id) > 1) {
            $candidates[] = substr($id, 0, -1);

            $lastChar = strtoupper(substr($id, -1));
            $opposite = $lastChar === 'M' ? 'L' : 'M';
            $candidates[] = substr($id, 0, -1) . $opposite;
        }

        return array_values(array_unique(array_filter($candidates, static fn($v) => $v !== '')));
    }

    private static function resolveBaseCode(PDO $pdo, array $candidates): ?string
    {
        if (empty($candidates)) {
            return null;
        }

        $in = implode(',', array_fill(0, count($candidates), '?'));
        $stmt = $pdo->prepare("SELECT base_code FROM products WHERE base_code IN ($in)");
        $stmt->execute($candidates);
        $found = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($found)) {
            return null;
        }

        $foundMap = array_fill_keys(array_map('strval', $found), true);
        foreach ($candidates as $candidate) {
            if (isset($foundMap[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Fill missing metal types from sibling candidates (e.g. 200M <- 200L)
     * while preserving any metals already present on the primary base code.
     *
     * @param array<string,float> $priceByMetal
     * @param array<int,string> $candidates
     */
    private static function applySiblingMetalFallback(PDO $pdo, PricingCalculator $pricingCalc, string $primaryBaseCode, array $candidates, array &$priceByMetal): void
    {
        $missingMetals = ['STER', 'GF', '10K', '14K', '18K'];
        $missingMetals = array_values(array_filter($missingMetals, static fn($m) => !isset($priceByMetal[$m])));
        if (empty($missingMetals)) {
            return;
        }

        foreach ($candidates as $candidate) {
            if ($candidate === $primaryBaseCode) {
                continue;
            }

            $stmt = $pdo->prepare(
                "SELECT
                    p.labor_hours,
                    p.stone_cost,
                    p.star_cost,
                    p.stone_setting_cost,
                    p.markup_percent,
                    pv.gold_grams,
                    pv.sterling_grams,
                    pv.material_cost,
                    pv.metal_type
                FROM products p
                LEFT JOIN product_variants pv ON pv.product_id = p.product_id
                WHERE p.base_code = ?
                  AND pv.metal_type IS NOT NULL"
            );
            $stmt->execute([$candidate]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $metalType = (string)($row['metal_type'] ?? '');
                if ($metalType === '' || isset($priceByMetal[$metalType])) {
                    continue;
                }

                $karat = preg_replace('/TT$/', '', $metalType);
                if (!in_array($karat, ['10K', '14K', '18K'], true)) {
                    $karat = '10K';
                }

                $costParams = [
                    'goldGrams'        => (float)($row['gold_grams'] ?? 0),
                    'karat'            => $karat,
                    'sterlingGrams'    => (float)($row['sterling_grams'] ?? 0),
                    'laborHours'       => (float)($row['labor_hours'] ?? 0),
                    'materialCost'     => (float)($row['material_cost'] ?? 0),
                    'stoneCost'        => (float)($row['stone_cost'] ?? 0),
                    'starCost'         => (float)($row['star_cost'] ?? 0),
                    'stoneSettingCost' => (float)($row['stone_setting_cost'] ?? 0),
                ];
                $markup = (float)($row['markup_percent'] ?? 50);
                $priceResult = $pricingCalc->calculatePrice($costParams, $markup);
                if (($priceResult['roundedPrice'] ?? 0) > 0) {
                    $priceByMetal[$metalType] = (float)$priceResult['roundedPrice'];
                }
            }

            $missingMetals = array_values(array_filter($missingMetals, static fn($m) => !isset($priceByMetal[$m])));
            if (empty($missingMetals)) {
                break;
            }
        }
    }

    /**
     * Build per-metal pricing and display base price for a product.
     *
     * @return array{base_price:?float, price_by_metal:array<string,float>}
     */
    public static function build(PDO $pdo, string $productId, string $defaultKaratId = '10k', bool $isTwoTone = false): array
    {
        if (!defined('SHOW_PRICING') || !SHOW_PRICING) {
            return [
                'base_price' => null,
                'price_by_metal' => [],
            ];
        }

        $calcPath = __DIR__ . '/../../cadman-database/php/PricingCalculator.php';
        if (!is_file($calcPath)) {
            return [
                'base_price' => null,
                'price_by_metal' => [],
            ];
        }
        require_once $calcPath;

        $priceByMetal = [];
        $basePrice = null;

        try {
            $pricingCalc = new PricingCalculator($pdo);
            $candidates = self::productIdCandidates($productId);
            $resolvedBaseCode = self::resolveBaseCode($pdo, $candidates);
            if ($resolvedBaseCode === null) {
                return [
                    'base_price' => null,
                    'price_by_metal' => [],
                ];
            }

            $priceStmt = $pdo->prepare(
                "SELECT
                    p.labor_hours,
                    p.stone_cost,
                    p.star_cost,
                    p.stone_setting_cost,
                    p.markup_percent,
                    pv.gold_grams,
                    pv.sterling_grams,
                    pv.material_cost,
                    pv.metal_type
                FROM products p
                LEFT JOIN product_variants pv ON pv.product_id = p.product_id
                WHERE p.base_code = ?
                  AND pv.metal_type IS NOT NULL"
            );
            $priceStmt->execute([$resolvedBaseCode]);
            $allVariants = $priceStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($allVariants as $row) {
                $metalType = (string)($row['metal_type'] ?? '');
                if ($metalType === '') {
                    continue;
                }
                $karat = preg_replace('/TT$/', '', $metalType);
                if (!in_array($karat, ['10K', '14K', '18K'], true)) {
                    $karat = '10K';
                }

                $costParams = [
                    'goldGrams'        => (float)($row['gold_grams'] ?? 0),
                    'karat'            => $karat,
                    'sterlingGrams'    => (float)($row['sterling_grams'] ?? 0),
                    'laborHours'       => (float)($row['labor_hours'] ?? 0),
                    'materialCost'     => (float)($row['material_cost'] ?? 0),
                    'stoneCost'        => (float)($row['stone_cost'] ?? 0),
                    'starCost'         => (float)($row['star_cost'] ?? 0),
                    'stoneSettingCost' => (float)($row['stone_setting_cost'] ?? 0),
                ];
                $markup = (float)($row['markup_percent'] ?? 50);
                $priceResult = $pricingCalc->calculatePrice($costParams, $markup);
                if (($priceResult['roundedPrice'] ?? 0) > 0) {
                    $priceByMetal[$metalType] = (float)$priceResult['roundedPrice'];
                }
            }

            // Some records (e.g. 200M) may only have a subset of metals.
            // Fill only missing metals from sibling candidates (e.g. 200L).
            self::applySiblingMetalFallback($pdo, $pricingCalc, $resolvedBaseCode, $candidates, $priceByMetal);

            $basePrice = self::resolveBasePriceFromDefault($priceByMetal, $defaultKaratId, $isTwoTone);

            if ($basePrice === null && !empty($priceByMetal)) {
                // Deterministic fallback order.
                $preferred = ['STER', 'GF', '10K', '10KTT', '14K', '14KTT', '18K', '18KTT'];
                foreach ($preferred as $metalType) {
                    if (isset($priceByMetal[$metalType])) {
                        $basePrice = $priceByMetal[$metalType];
                        break;
                    }
                }
                if ($basePrice === null) {
                    $basePrice = reset($priceByMetal);
                }
            }
        } catch (Throwable $e) {
            error_log('ProductPricingProfile::build error for ' . $productId . ': ' . $e->getMessage());
        }

        return [
            'base_price' => $basePrice,
            'price_by_metal' => $priceByMetal,
        ];
    }

    /**
     * Resolve base display price from configurator karat option id.
     */
    public static function resolveBasePriceFromDefault(array $priceByMetal, string $karatId, bool $isTwoTone = false): ?float
    {
        if (empty($priceByMetal)) {
            return null;
        }

        $map = [
            '950_silver' => 'STER',
            '10k' => $isTwoTone ? '10KTT' : '10K',
            '14k' => $isTwoTone ? '14KTT' : '14K',
            '18k' => $isTwoTone ? '18KTT' : '18K',
        ];

        $metalType = $map[$karatId] ?? null;
        if ($metalType && isset($priceByMetal[$metalType])) {
            return (float)$priceByMetal[$metalType];
        }

        if ($metalType && str_ends_with($metalType, 'TT')) {
            $single = substr($metalType, 0, -2);
            if (isset($priceByMetal[$single])) {
                return (float)$priceByMetal[$single];
            }
        }

        return null;
    }
}
