<?php

namespace EffectConnect\Marketplaces\Helper;

/**
 * WooCommerce helper fucntions.
 */
class WcHelper
{
    const WC_DEFAULT_ATTRIBUTE_PREFIX = 'ecdefault_';
    const WC_DEFAULT_TAXONOMY_PREFIX  = 'ectaxonomy_';

    /**
     * @var array
     */
    protected static $carrierOptions = [];

    /**
     * @var array
     */
    protected static $paymentOptions = [];

    /**
     * @var array
     */
    protected static $productAttributes = [];

    /**
     * @var array
     */
    protected static $taxonomies = [];

    /**
     * Taxonomies to exclude from global taxonomy list.
     * (currently PWB Brands is excluded since these brands are exported as an attribute separately - for now keep it like this for backwards compatibility)
     *
     * @var string[]
     */
    protected static $excludedTaxonomies = ['pwb-brand'];

    /**
     * Get array of all available default product attributes (such as 'price') and prefix them to make 'sure' that they won't overlap with local custom attributes.
     *
     * @return array
     */
    public static function getDefaultProductAttributes(): array
    {
        return [
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'id' => TranslationHelper::translate('ID'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'name' => TranslationHelper::translate('Name'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'slug' => TranslationHelper::translate('Slug'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'status' => TranslationHelper::translate('Status'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'featured' => TranslationHelper::translate('Featured'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'catalog_visibility' => TranslationHelper::translate('Catalog visibility'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'description' => TranslationHelper::translate('Description'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'short_description' => TranslationHelper::translate('Short description'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'sku' => TranslationHelper::translate('SKU'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'global_unique_id' => TranslationHelper::translate('GTIN, UPC, EAN, or ISBN'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'regular_price' => TranslationHelper::translate('Regular price'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'sale_price' => TranslationHelper::translate('Sale price'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'date_on_sale_from' => TranslationHelper::translate('Date sale price starts'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'date_on_sale_to' => TranslationHelper::translate('Date sale price ends'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'total_sales' => TranslationHelper::translate('Total sales'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'tax_status' => TranslationHelper::translate('Tax status'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'tax_class' => TranslationHelper::translate('Tax class'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'manage_stock' => TranslationHelper::translate('Manage stock?'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'stock_quantity' => TranslationHelper::translate('In stock?'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'stock_status' => TranslationHelper::translate('Stock status'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'backorders' => TranslationHelper::translate('Backorders allowed?'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'low_stock_amount' => TranslationHelper::translate('Low stock amount'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'sold_individually' => TranslationHelper::translate('Sold individually?'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'weight' => TranslationHelper::translate('Weight'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'length' => TranslationHelper::translate('Length'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'width' => TranslationHelper::translate('Width'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'height' => TranslationHelper::translate('Height'),
            self::WC_DEFAULT_ATTRIBUTE_PREFIX . 'purchase_note' => TranslationHelper::translate('Purchase note'),
        ];
    }

    /**
     * Get array of all available product taxonomies.
     * Since global attributes are also taxonomies, and they are fetched separately, they are excluded here
     * (by only fetching public taxonomies).
     *
     * @param bool $withoutPrefix
     * @return array
     */
    public static function getTaxonomies(bool $withoutPrefix = false): array
    {
        if (count(self::$taxonomies) == 0) {
            $taxonomies = [];
            foreach (get_object_taxonomies('product', 'objects') as $taxonomy) {
                if ($taxonomy->public && !in_array($taxonomy->name, self::$excludedTaxonomies)) {
                    $taxonomies[($withoutPrefix ? '' : self::WC_DEFAULT_TAXONOMY_PREFIX) . $taxonomy->name] = $taxonomy->label;
                }
            }
            self::$taxonomies = $taxonomies;
        }
        return self::$taxonomies;
    }

    /**
     * Get array of all available global and local custom product attributes.
     * Global attributes always have prefixed 'pa_'.
     * Local attributes can have any key the customer sets.
     *
     * @return array
     */
    public static function getCustomProductAttributes(): array
    {
        global $wpdb;

        if (!function_exists('wc_attribute_label') || !function_exists('maybe_unserialize')) {
            return [];
        }

        if (count(self::$productAttributes) == 0) {
            $metas = $wpdb->get_results("SELECT `meta_value` FROM " . $wpdb->postmeta . " WHERE `meta_key` = '_product_attributes'"); // get ids of all postmeta with product attributes.
            $attributeMetas = [];

            foreach ($metas as $meta) {
                $attributeMetas[] = maybe_unserialize($meta->meta_value); // gets postmeta by id as an object (easier to get the meta_value using this dedicated function).
            }

            $uniqueAttributes = [];
            foreach ($attributeMetas as $meta) { // loop through all meta objects
                foreach ($meta as $key => $value) { // loop through all attributes in object
                    $value = wc_attribute_label($value['name']); // gets the attribute label from the attribute name, example: pa_color -> Color.
                    if (!in_array($value, $uniqueAttributes)) {
                        $uniqueAttributes[$key] = $value;
                    }
                }
            }
            self::$productAttributes = $uniqueAttributes;
        }

        return self::$productAttributes;
    }

    /**
     * Get array of all available attributes from external plugins and prefix them to make 'sure' that they won't overlap with local custom attributes.
     * Currently, supporting:
     * - "Product code for WooCommerce" (https://wordpress.org/plugins/product-code-for-woocommerce/)
     * - "Perfect brands for WooCommerce" (https://wordpress.org/plugins/perfect-woocommerce-brands/)
     *
     * @return array
     */
    public static function getPluginAttributes(): array
    {
        $attributes = [];
        if (ProductCodePluginHelper::productCodePluginActivated()) {
            $attributes[ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_PREFIX . ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_ATTRIBUTE_1] = TranslationHelper::translate('Product Code');
            $attributes[ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_PREFIX . ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_ATTRIBUTE_2] = TranslationHelper::translate('Product Code 2');
        }
        if (PerfectBrandsPluginHelper::perfectBrandsPluginActivated()) {
            $attributes[PerfectBrandsPluginHelper::WC_PLUGINS_PERFECT_BRANDS_PREFIX . PerfectBrandsPluginHelper::WC_PLUGINS_PERFECT_BRANDS_ATTRIBUTE] = TranslationHelper::translate('Perfect Brands');
        }
        if (EanForWooCommercePluginHelper::eanPluginActivated()) {
            $attributes[EanForWooCommercePluginHelper::WC_PLUGINS_EAN_PREFIX . EanForWooCommercePluginHelper::WC_PLUGINS_EAN_ATTRIBUTE] = TranslationHelper::translate('EAN for WooCommerce');
        }
        return $attributes;
    }

    /**
     * Gets available payment methods.
     *
     * @return array
     */
    public static function getPaymentOptions(): array
    {
        if (!class_exists('WooCommerce')) {
            return [];
        }

        if (count(self::$paymentOptions) == 0) {
            self::$paymentOptions = [];
            foreach( WC()->payment_gateways->get_available_payment_gateways() as $method) {
                self::$paymentOptions[$method->id] = $method->method_title;
            }
        }

        return self::$paymentOptions;
    }

    /**
     * Gets available WC carriers for shipment.
     *
     * @return array
     */
    public static function getCarrierOptions(): array
    {
        if (!class_exists('WooCommerce')) {
            return [];
        }

        if (count(self::$carrierOptions) == 0) {
            self::$carrierOptions = [];
            foreach (WC()->shipping->get_shipping_methods() as $method) {
                self::$carrierOptions[$method->id] = $method->method_title;
            }
        }

        return self::$carrierOptions;
    }

    /**
     * Get list of available order statuses.
     *
     * @return array
     */
    public static function getOrderStatusOptions(): array
    {
        if (!function_exists('wc_get_order_statuses')) {
            return [];
        }

        return wc_get_order_statuses();
    }

    /**
     * @param string $taxonomy
     * @return bool
     */
    public static function isGlobalAttribute(string $taxonomy): bool
    {
        return (strpos($taxonomy, 'pa_') === 0 );
    }
}