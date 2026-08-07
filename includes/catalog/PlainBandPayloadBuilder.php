<?php

require_once __DIR__ . '/ProductPricingProfile.php';

/**
 * Shared payload builder for plain-band configurator + pricing state.
 *
 * This is the single source of truth used by modal and detail page.
 */
class PlainBandPayloadBuilder
{
    /**
     * @param array<string,mixed> $product Catalog product row
     * @return array<string,mixed>
     */
    public static function build(PDO $pdo, array $product): array
    {
        $configPath = dirname(__DIR__, 2) . '/bands_php/plain_configurator.json';

        $result = [
            'configurator_options' => null,
            'has_configurator' => false,
            'resolved_series' => null,
            'default_karat' => '950_silver',
            'base_price' => null,
            'price_by_metal' => [],
            'quote_only' => false,
        ];

        if (!is_file($configPath)) {
            return $result;
        }

        $raw = json_decode((string)file_get_contents($configPath), true);
        if (!is_array($raw) || !isset($raw['data']['options']) || !is_array($raw['data']['options'])) {
            return $result;
        }

        $options = $raw['data']['options'];

        if (!empty($options['karat_level']['default']) && is_string($options['karat_level']['default'])) {
            $result['default_karat'] = $options['karat_level']['default'];
        }

        $series = self::resolveSeries($options, (string)($product['product_id'] ?? ''));
        if ($series !== null) {
            $widthOptions = [];
            foreach (($series['products'] ?? []) as $row) {
                if (!is_array($row) || empty($row['base_id']) || empty($row['width'])) {
                    continue;
                }

                $label = (string)$row['width'];
                $m = isset($row['product_id_m']) ? (string)$row['product_id_m'] : '';
                $l = isset($row['product_id_l']) ? (string)$row['product_id_l'] : '';
                if ($m !== '' || $l !== '') {
                    $label .= ' (' . $m . ($m !== '' && $l !== '' ? '/' : '') . $l . ')';
                }

                $widthOptions[] = [
                    'id' => (string)$row['base_id'],
                    'name' => $label,
                    'price_modifier' => (float)($row['price_modifier'] ?? 0),
                    'product_id_m' => $m,
                    'product_id_l' => $l,
                    'product_base' => (string)$row['base_id'],
                ];
            }

            $defaultWidth = !empty($product['width_mm']) ? ((string)$product['width_mm'] . 'mm') : '4mm';
            $options['width'] = [
                'label' => 'Band Width - ' . (string)($series['name'] ?? 'Series'),
                'required' => true,
                'type' => 'single_select',
                'help_text' => 'Select width for this series - ' . (string)($series['description'] ?? ''),
                'default' => $defaultWidth,
                'options' => $widthOptions,
            ];

            if (isset($options['style_and_width']) && is_array($options['style_and_width'])) {
                $options['style_and_width']['label'] = 'Advanced: Full Style Grid';
                $options['style_and_width']['required'] = false;
            }

            $result['resolved_series'] = [
                'id' => (string)($series['id'] ?? ''),
                'name' => (string)($series['name'] ?? ''),
                'profile' => (string)($series['profile'] ?? ''),
            ];
        }

        $pricing = ProductPricingProfile::build(
            $pdo,
            (string)($product['product_id'] ?? ''),
            (string)$result['default_karat'],
            false
        );

        $result['base_price'] = $pricing['base_price'] ?? null;
        $result['price_by_metal'] = is_array($pricing['price_by_metal'] ?? null) ? $pricing['price_by_metal'] : [];
        $result['quote_only'] = (defined('SHOW_PRICING') && SHOW_PRICING && $result['base_price'] === null);

        $result['configurator_options'] = $options;
        $result['has_configurator'] = true;

        return $result;
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>|null
     */
    private static function resolveSeries(array $options, string $productId): ?array
    {
        if ($productId === '') {
            return null;
        }

        $seriesList = $options['style_and_width']['grid_layout']['series'] ?? null;
        if (!is_array($seriesList)) {
            return null;
        }

        foreach ($seriesList as $series) {
            if (!is_array($series) || !isset($series['products']) || !is_array($series['products'])) {
                continue;
            }
            foreach ($series['products'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $m = isset($row['product_id_m']) ? (string)$row['product_id_m'] : '';
                $l = isset($row['product_id_l']) ? (string)$row['product_id_l'] : '';
                if ($m === $productId || $l === $productId) {
                    return $series;
                }
            }
        }

        return null;
    }
}
