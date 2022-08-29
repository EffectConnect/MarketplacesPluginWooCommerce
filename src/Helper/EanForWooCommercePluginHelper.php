<?php

namespace EffectConnect\Marketplaces\Helper;

use Throwable;
use WC_Product;

/**
 * Helper functions for external plugin "EAN for WooCommerce" (https://wordpress.org/plugins/ean-for-woocommerce/).
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
            $ean = strval(alg_wc_ean()->core->get_ean($product->get_id(), true));
        } catch (Throwable $e) {
            $ean = '';
        }
        return $ean;
    }
}