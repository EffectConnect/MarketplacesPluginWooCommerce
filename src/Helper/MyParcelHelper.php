<?php

namespace EffectConnect\Marketplaces\Helper;

class MyParcelHelper
{
    CONST SHIPMENT_EXPORT_OPTION_TNT = 'tnt';

    /**
     * Checks if the MyParcel plugin is activated (check MyParcel NL, MyParcel BE, PostNL plugins).
     * @return bool
     */
    public static function myParcelPluginActivated(): bool
    {
        return function_exists('is_plugin_active') && (
                is_plugin_active('woocommerce-myparcel/woocommerce-myparcel.php')
                || is_plugin_active('woo-postnl/woocommerce-postnl.php')
            )
            ;
    }

    /**
     * Check if given post meta key represents a MyParcel shipment.
     * @param string $metaKey
     * @return bool
     */
    public static function isMyParcelMetaKey(string $metaKey): bool
    {
        return
            (class_exists('WCMYPA_Admin') && $metaKey === \WCMYPA_Admin::META_SHIPMENTS) // MyParcel NL
            || (class_exists('WCMYPABE_Admin') && $metaKey === \WCMYPABE_Admin::META_SHIPMENTS) // MyParcel BE
            || (class_exists('WCPOST_Admin') && $metaKey === \WCPOST_Admin::META_SHIPMENTS) // PostNL
            ;
    }
}