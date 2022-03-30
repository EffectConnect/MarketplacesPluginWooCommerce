<?php

namespace EffectConnect\Marketplaces\Helper;

use WC_Product;

/**
 * Helper functions for external plugin "Perfect brands for WooCommerce" (https://wordpress.org/plugins/perfect-woocommerce-brands/)
 */
class PerfectBrandsPluginHelper
{
    const WC_PLUGINS_PERFECT_BRANDS_PREFIX    = 'ecpluginsperfectbrands_';
    const WC_PLUGINS_PERFECT_BRANDS_ATTRIBUTE = 'brand';

    /**
     * Checks if the "Product code for WooCommerce" plugin is activated.
     * @return bool
     */
    public static function perfectBrandsPluginActivated(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('perfect-woocommerce-brands/perfect-woocommerce-brands.php');
    }

    /**
     * @param WC_Product $product
     * @return array
     */
    public static function getValues(WC_Product $product): array
    {
        // Get products brands (multiple) - brands are always related to the parent product ID.
        $brands = wp_get_object_terms($product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id(), 'pwb-brand', ['fields' => 'names']);
        if (is_array($brands)) {
            return $brands;
        }
        return [];
    }
}