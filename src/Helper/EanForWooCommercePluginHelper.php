<?php

namespace EffectConnect\Marketplaces\Helper;

use Throwable;
use WC_Product;

/**
 * Helper functions for external plugin "EAN for WooCommerce" (https://wordpress.org/plugins/ean-for-woocommerce/).
 *
 * Note: as of plugin v5.5.6, the plugin's global accessor function was renamed
 * from `alg_wc_ean()` to `wpfactory_wc_ean()` (prefixes changed from `alg` to `wpfactory`).
 * The `->core->get_ean()` method itself kept the same signature, so we just need
 * to call whichever accessor function currently exists.
 */
class EanForWooCommercePluginHelper
{
    const WC_PLUGINS_EAN_PREFIX    = 'ecpluginsean_';
    const WC_PLUGINS_EAN_ATTRIBUTE = 'ean';

    /**
     * Checks if the "EAN for WooCommerce" plugin is activated.
     * @return bool
     */
    public static function eanPluginActivated(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('ean-for-woocommerce/ean-for-woocommerce.php');
    }

    /**
     * @param WC_Product $product
     * @return string
     */
    public static function getValue(WC_Product $product): string
    {
        try {
            $eanPlugin = self::getEanPluginInstance();
            $ean       = $eanPlugin !== null
                ? strval($eanPlugin->core->get_ean($product->get_id(), true))
                : '';
        } catch (Throwable $e) {
            $ean = '';
        }
        return $ean;
    }

    /**
     * Returns the main plugin instance, supporting both the new (>= 5.5.6)
     * and old (< 5.5.6) accessor function names.
     *
     * @return object|null
     */
    private static function getEanPluginInstance()
    {
        if (function_exists('wpfactory_wc_ean')) {
            // New version (>= 5.5.6): prefixes renamed from `alg` to `wpfactory`.
            return wpfactory_wc_ean();
        }

        if (function_exists('alg_wc_ean')) {
            // Old version (< 5.5.6).
            return alg_wc_ean();
        }

        return null;
    }
}