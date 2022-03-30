<?php

namespace EffectConnect\Marketplaces\Helper;

use WC_Product;

/**
 * Helper functions for external plugin "Product code for WooCommerce" (https://wordpress.org/plugins/product-code-for-woocommerce/).
 */
class ProductCodePluginHelper
{
    const WC_PLUGINS_PRODUCT_CODE_PREFIX      = 'ecpluginsproductcode_';
    const WC_PLUGINS_PRODUCT_CODE_ATTRIBUTE_1 = '_product_code';
    const WC_PLUGINS_PRODUCT_CODE_ATTRIBUTE_2 = '_product_code_second';

    /**
     * Checks if the "Product code for WooCommerce" plugin is activated.
     * @return bool
     */
    public static function productCodePluginActivated(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('product-code-for-woocommerce/product-code-for-woocommerce.php');
    }

    /**
     * @param WC_Product $product
     * @param string $attributeName
     * @return string
     */
    public static function getValue(WC_Product $product, string $attributeName): string
    {
        $value = $product->get_meta($attributeName);
        return is_string($value) ? $value : '';
    }
}