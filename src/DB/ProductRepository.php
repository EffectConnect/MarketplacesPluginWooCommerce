<?php

namespace EffectConnect\Marketplaces\DB;

use EffectConnect\Marketplaces\Exception\InvalidProductOptionIdException;
use EffectConnect\Marketplaces\Helper\EanForWooCommercePluginHelper;
use EffectConnect\Marketplaces\Helper\PerfectBrandsPluginHelper;
use EffectConnect\Marketplaces\Helper\ProductCodePluginHelper;
use EffectConnect\Marketplaces\Helper\WcHelper;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\PHPSdk\Core\Model\Response\Order as EffectConnectOrder;
use WC_Product;
use wpdb;

class ProductRepository
{

    /**
     * Name of the product options table
     * @var string
     */
    private $productOptionsTable;
    /**
     * Name of the product update queue table
     * @var string
     */
    private $offerUpdateQueueTable;
    /**
     * Instance of the wordpress database class.
     * @var wpdb
     */
    private $wpdb;
    /**
     * All hashes generated for products in the product options table.
     * @var array
     */
    private $hashes = [];

    private static $instance;

    /**
     * Get singleton instance of ProductOptionsRepository.
     * @return ProductRepository
     */
    static function getInstance(): ProductRepository
    {
        if (!self::$instance) {
            self::$instance = new ProductRepository();
        }

        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->productOptionsTable = $this->wpdb->prefix . 'ec_product_options';
        $this->offerUpdateQueueTable = $this->wpdb->prefix . 'ec_offer_update_queue';
    }

    /**
     * Checks if there are product hashes in the custom database that don't exist in the current store. Deletes these products when found.
     */
    public function cleanDeletedProductsFromDB()
    {
        $tableHashes = $this->wpdb->get_results(
            "SELECT hash FROM $this->productOptionsTable"
        );

        $productsToDelete = [];

        foreach ($tableHashes as $value) {
            if (is_object($value)) {
                $value = $value->hash;
            }

            if (!in_array($value, $this->hashes)) {
                $productsToDelete[] = $value;
            }
        }

        if (count($productsToDelete) > 0) {

            $valueString = implode(', ', array_fill(0, count($productsToDelete), '%s'));

            $this->wpdb->query(
                $this->wpdb->prepare("DELETE FROM $this->productOptionsTable WHERE hash IN($valueString)",
                    $productsToDelete
                ));

        }
    }

    /**
     * Returns the id for a product.
     * @param WC_Product $productOption
     * @param array $attributeArray
     * @param bool $skipRegenerateIdsForSimpleProducts
     * @return int
     * @throws InvalidProductOptionIdException
     */
    public function getProductOptionId(WC_Product $productOption, array $attributeArray, bool $skipRegenerateIdsForSimpleProducts = false): int
    {
        $productId = $productOption->get_parent_id() ? $productOption->get_parent_id() : $productOption->get_id();
        $variationId = $productOption->get_parent_id() ? $productOption->get_id() : null;

        $name = $productOption->get_name();
        $attributes = $attributeArray;
        if ($productOption->get_parent_id() === 0 && $skipRegenerateIdsForSimpleProducts) {
            $attributes = []; // Only use product ID for determining unique product hash
        }

        $hashID = $this->addProductOptionsData($variationId, $productId, $name, $attributes);

        $optionId = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT option_id FROM $this->productOptionsTable WHERE hash = %s",
                $hashID
            )
        );

        if (strlen($optionId) === 0) {
            throw new InvalidProductOptionIdException();
        }

        return intval($optionId);
    }

    /**
     * Inserts a product option into a custom table.
     * @param $variationId
     * @param $productId
     * @param $name
     * @param $attributes
     * @return string
     */
    public function addProductOptionsData($variationId, $productId, $name, $attributes): string
    {
        $hash = hash("md5", json_encode([$attributes, $productId]));
        if (in_array($hash, $this->hashes)) {
            return $hash;
        }

        $this->hashes[] = $hash;

        if (count($attributes) === 0) {
            $attributes = null;
        } else {
            $attributes = json_encode($attributes);
        }

        $this->wpdb->query(
            $this->wpdb->prepare(
                "INSERT IGNORE INTO $this->productOptionsTable(variation_id, product_id, product_name, attribute_data, hash) VALUES (%d, %d, %s, %s, %s) ON DUPLICATE KEY UPDATE variation_id = %d, product_name = %s",
                $variationId, $productId, $name, $attributes, $hash, $variationId, $name
            )
        );
        return $hash;
    }

    /**
     * @param int $productId
     * @return void
     */
    public function insertIntoProductUpdateQueue(int $productId)
    {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "INSERT IGNORE INTO $this->offerUpdateQueueTable(product_id) VALUES (%d)",
                $productId
            )
        );
    }

    /**
     * Gets the ids for all variations of a product that were generated for EffectConnect during the latest catalog export.
     * @param $productId
     * @return array
     */
    public function getAllProductOptionIds($productId): array
    {
        $ids = $this->wpdb->get_results(
            "SELECT option_id, variation_id FROM $this->productOptionsTable WHERE product_id = $productId"
        );

        $ecIds = [];

        foreach ($ids as $value) {
            $ecIds[] = $value;
        }

        return $ecIds;
    }

    /**
     * Gets attribute json from the product options table and returns the decoded array.
     * @param int $optionId
     * @return object|bool
     */
    public function getProductOptionById(int $optionId)
    {
        $productOptionArray = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT attribute_data, product_id, variation_id, product_name FROM $this->productOptionsTable WHERE option_id = %d",
                $optionId
            ));

        if (is_null($productOptionArray) || count($productOptionArray) !== 1) {
            return false;
        }

        return current($productOptionArray);
    }

    /**
     * Checks if ordered products exist in the product option table.
     * Returns false if one or more products are not found in the product option table.
     * @param EffectConnectOrder $ecOrder
     * @return bool
     */
    public function checkIfProductsExist(EffectConnectOrder $ecOrder): bool
    {
        $idArray = [];

        foreach ($ecOrder->getLines() as $product) {
            $id = $product->getProduct()->getIdentifier();
            if (!in_array($id, $idArray)) $idArray[] = $id;
        }

        $valueString = implode(', ', array_fill(0, count($idArray), '%s'));

        $orderedProducts = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM $this->productOptionsTable WHERE option_id IN($valueString)",
                $idArray
            ));

        return count($orderedProducts) === count($idArray);
    }

    /**
     * Returns a product's associated brand.
     *
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductBrand(WC_Product $product, ConnectionResource $connection): string
    {
        $brandAttribute = $connection->getCatalogExportBrandAttribute();
        return $this->getProductAttribute($product, $brandAttribute);
    }

    /**
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductTitle(WC_Product $product, ConnectionResource $connection): string
    {
        $attribute = $connection->getCatalogExportTitleAttribute();
        return $this->getProductAttribute($product, $attribute);
    }

    /**
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductDescription(WC_Product $product, ConnectionResource $connection): string
    {
        $attribute = $connection->getCatalogExportDescriptionAttribute();
        return nl2br($this->getProductAttribute($product, $attribute));
    }

    /**
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductEan(Wc_Product $product, ConnectionResource $connection): string
    {
        $eanAttribute = $connection->getCatalogExportEanAttribute();
        $ean          = $this->getProductAttribute($product, $eanAttribute);

        // Add leading zero in case EAN consists of 12 characters?
        if ($connection->getCatalogExportEanLeadingZero() && strlen($ean) === 12) {
            $ean = str_pad($ean, 13, '0', STR_PAD_LEFT);
        }

        return $ean;
    }

    /**
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductCost(Wc_Product $product, ConnectionResource $connection): string
    {
        $costAttribute = $connection->getCatalogExportCostAttribute();
        return $this->getProductAttribute($product, $costAttribute);
    }

    /**
     * Returns the current active price.
     * If the product is on sale, it will return the sale price, else it will return the original price.
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductPrice(WC_Product $product, ConnectionResource $connection): string
    {
        // If 'getCatalogExportSpecialPrice' is enabled then the special price AND the original price will be exported to EffectConnect Marketplaces.
        // If it is disabled only the regular price is exported (sale price is not taken into account).
        if ($connection->getCatalogExportSpecialPrice()) {
            return $product->get_price();
        }

        return $product->get_regular_price();
    }

    /**
     * Returns a product's original price without discounts.
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string|null
     */
    public function getProductPriceOriginal(WC_Product $product, ConnectionResource $connection): ?string
    {
        // If 'getCatalogExportSpecialPrice' is enabled then the special price AND the original price will be exported to EffectConnect Marketplaces.
        // If it is disabled only the regular price is exported (sale price is not taken into account).
        if ($connection->getCatalogExportSpecialPrice() && $product->get_regular_price() > $product->get_price()) {
            return $product->get_regular_price();
        }

        return null;
    }

    /**
     * Gets the current stock for a product.
     * In the future we might return false (disable stock export) in case stock tracking is disabled (and implement a setting for this).
     * For now, a stock value is always sent.
     *
     * No stock management
     *   Product is 'in stock' -> send fictional stock
     *   Product is 'out of stock' -> send stock 0
     * Stock management
     *   Backorders enabled
     *     Setting 'offer_export_virtual_stock_conditional_backorders' disabled (default) > send fictional stock
     *     Setting 'offer_export_virtual_stock_conditional_backorders' enabled
     *       Product has quantity > 0 -> send stock amount
     *       Product has quantity <= 0 -> send fictional stock
     *   Backorders disabled -> send stock amount
     *
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return int
     */
    public function getProductStock(WC_Product $product, ConnectionResource $connection): int
    {
        // Stock management
        if ($product->managing_stock()) {
            if ($product->backorders_allowed()) {
                if ($connection->getOfferExportVirtualStockConditionalBackorders()) {
                    if ($product->get_stock_quantity() > 0) {
                        return $this->correctStockValue(intval($product->get_stock_quantity()));
                    } else {
                        return $this->correctStockValue($connection->getOfferExportVirtualStockAmount());
                    }
                } else {
                    return $this->correctStockValue($connection->getOfferExportVirtualStockAmount());
                }
            } else {
                return $this->correctStockValue(intval($product->get_stock_quantity()));
            }
        }
        // No stock management
        else {
            if ($product->is_in_stock()) {
                return $this->correctStockValue($connection->getOfferExportVirtualStockAmount());
            } else {
                return 0;
            }
        }
    }

    /**
     * @param WC_Product $product
     * @param ConnectionResource $connection
     * @return string
     */
    public function getProductDeliveryTime(Wc_Product $product, ConnectionResource $connection): string
    {
        $deliveryAttribute = $connection->getCatalogExportDeliveryAttribute();
        return $this->getProductAttribute($product, $deliveryAttribute);
    }

    /**
     * @param int $productId
     * @param array $taxonomies
     * @return array
     */
    public function getProductTaxonomies(int $productId, array $taxonomies): array
    {
        if (count($taxonomies) === 0) {
            return [];
        }

        $valueString = implode(', ', array_fill(0, count($taxonomies), '%s'));
        $sql = "
            SELECT DISTINCT {$this->wpdb->prefix}term_taxonomy.taxonomy
            FROM {$this->wpdb->prefix}term_relationships 
            INNER JOIN {$this->wpdb->prefix}term_taxonomy
            ON {$this->wpdb->prefix}term_relationships.term_taxonomy_id = {$this->wpdb->prefix}term_taxonomy.term_taxonomy_id
            WHERE {$this->wpdb->prefix}term_relationships.object_id = $productId
            AND {$this->wpdb->prefix}term_taxonomy.taxonomy IN($valueString)
        ";
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql,
                array_keys($taxonomies)
            ));
        if (!is_array($results)) {
            return [];
        }

        $productTaxonomies = [];
        foreach ($results as $result) {
            $productTaxonomies[] = $result->taxonomy;
        }
        return $productTaxonomies;
    }

    /**
     * @param WC_Product $product
     * @param string $attributeName
     * @return string
     */
    protected function getProductAttribute(WC_Product $product, string $attributeName): string
    {
        if (strpos($attributeName, WcHelper::WC_DEFAULT_TAXONOMY_PREFIX) === 0) {
            // Fetch product data from WC taxonomy if $attributeName starts with <default-product-taxonomy-prefix>
            $wcTaxonomyName = str_replace(WcHelper::WC_DEFAULT_TAXONOMY_PREFIX, '', $attributeName);
            // Get taxonomies (multiple) - these are always related to the parent product ID.
            $terms = wp_get_object_terms($product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id(), $wcTaxonomyName, ['fields' => 'names']);
            if (is_array($terms) && count($terms) > 0) {
                return strval(reset($terms));
            }
        } elseif (strpos($attributeName, WcHelper::WC_DEFAULT_ATTRIBUTE_PREFIX) === 0) {
            // Fetch product data from core WC attribute if $attributeName starts with <default-product-attribute-prefix>
            $wcAttributeName = str_replace(WcHelper::WC_DEFAULT_ATTRIBUTE_PREFIX, '', $attributeName);
            $method          = 'get_' . $wcAttributeName;
            if (method_exists($product, $method)) {
                return strval(call_user_func([$product, $method]));
            }
        } elseif (strpos($attributeName, ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_PREFIX) === 0) {
            // Get attribute from external plugin "Product code for WooCommerce"
            $pluginAttributeName = str_replace(ProductCodePluginHelper::WC_PLUGINS_PRODUCT_CODE_PREFIX, '', $attributeName);
            return ProductCodePluginHelper::getValue($product, $pluginAttributeName);
        } elseif (strpos($attributeName, PerfectBrandsPluginHelper::WC_PLUGINS_PERFECT_BRANDS_PREFIX) === 0) {
            // Get attribute from external plugin "Perfect brands for WooCommerce"
            $brands = PerfectBrandsPluginHelper::getValues($product);
            if (count($brands) > 0) {
                return strval(reset($brands));
            }
        } elseif (strpos($attributeName, EanForWooCommercePluginHelper::WC_PLUGINS_EAN_PREFIX) === 0) {
            // Get attribute from external plugin "EAN for WooCommerce"
            return EanForWooCommercePluginHelper::getValue($product);
        } else {
            // Fetch product data from custom attribute
            return strval($product->get_attribute($attributeName));
        }

        return '';
    }

    /**
     * @param int $stockValue
     * @return int
     */
    protected function correctStockValue(int $stockValue): int
    {
        $stockValue = min($stockValue, 9999);
        return max(0, $stockValue);
    }
}
