<?php

namespace EffectConnect\Marketplaces\Logic\CatalogExport;

use EffectConnect\Marketplaces\DB\ProductRepository;
use EffectConnect\Marketplaces\Exception\InvalidLanguageException;
use EffectConnect\Marketplaces\Exception\InvalidProductOptionIdException;
use EffectConnect\Marketplaces\Exception\InvalidSkuException;
use EffectConnect\Marketplaces\Exception\ProductVariationsCreationFailedException;
use EffectConnect\Marketplaces\Helper\LanguageHelper;
use EffectConnect\Marketplaces\Helper\Languages\LanguagePluginHelper;
use EffectConnect\Marketplaces\Helper\PerfectBrandsPluginHelper;
use EffectConnect\Marketplaces\Helper\WcHelper;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\Marketplaces\Model\WcProductVariationWrapper;
use Exception;
use Laminas\Validator\Barcode;
use Throwable;
use WC_Meta_Data;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Term;

class CatalogBuilder
{
    /**
     * Contains an array of processed EANs in order to keep track of duplicates.
     * @var array
     */
    protected $_processedEANs = [];

    /**
     * Contains an array of processed product identifiers (from plugin database ec_product_options) in order to keep track of duplicates.
     * @var array
     */
    protected $_processedIdentifiers = [];

    /**
     * Type of logger that should be used for this instance.
     * @var
     */
    protected $loggerType;

    /**
     * ProductOptionsTable repository instance.
     * @var ProductRepository
     */
    protected $productOptionsRepo;

    /**
     * For getting user settings.
     * @var ConfigContainer
     */
    protected $config;

    /**
     * ConnectionResource $connection
     */
    protected $connection;

    /**
     * Languages to export (in case of WPML - otherwise only contains the default language).
     * @var array
     */
    protected $languages = [];

    /**
     * @var WC_Product[] $productTranslations
     */
    protected $productTranslations = [];

    /**
     * @var WC_Product[]
     */
    protected $parentProductTranslations = [];

    /**
     * @var array
     */
    protected $categoryTranslationsByIdAndLocale = [];

    /**
     * @var array
     */
    protected $attributeTranslationsByIdAndLocale = [];

    /**
     * @param string $loggerType
     * @param ConnectionResource $connection
     * @throws InvalidLanguageException
     */
    public function __construct(string $loggerType, ConnectionResource $connection)
    {
        $this->connection         = $connection;
        $this->config             = ConfigContainer::getInstance();
        $this->productOptionsRepo = ProductRepository::getInstance();
        $this->loggerType         = $loggerType;
        $this->setLanguages();
    }

    /**
     * Loops through each product to create a tree structure of arrays.
     * @param WC_Product[] $rawProducts
     * @return array
     */
    protected function buildCatalog(array $rawProducts): array
    {
        $catalog = [];

        foreach ($rawProducts as $product)
        {
            try {
                $productOptions = $this->getAllOptions($product);
            } catch (ProductVariationsCreationFailedException $e) {
                LoggerContainer::getLogger($this->loggerType)->error('Error when adding product.', [
                    'process'    => LoggerConstants::CATALOG_EXPORT,
                    'id'         => $product->get_id(),
                    'sku'        => $product->get_sku(),
                    'connection' => $this->connection->getConnectionId(),
                    'message'    => $e->getMessage(),
                ]);
                continue;
            }
            if (count($productOptions) === 0) {
                LoggerContainer::getLogger($this->loggerType)->warning('Skipping product because it is empty.', [
                    'process'    => LoggerConstants::CATALOG_EXPORT,
                    'id'         => $product->get_id(),
                    'sku'        => $product->get_sku(),
                    'connection' => $this->connection->getConnectionId(),
                ]);
                continue;
            }

            $productData = [
                'identifier' => $product->get_id(),
                'options'    => [
                    'option' => $productOptions
                ]
            ];

            // Brand
            $brand = $this->getProductBrand($product);
            if (!empty($brand)) {
                $productData['brand'] = $brand;
            }

            // Categories
            $categories = $this->getCategories($product);
            if (count($categories) > 0) {
                $productData['categories'] = $categories;
            }

            $catalog[] = $productData;
        }

        return $catalog;
    }

    /**
     * Builds a category tree for a product.
     *
     * @param WC_Product $product
     * @return array
     */
    protected function getCategories(WC_Product $product): array
    {
        // Returns an array with one element, the lowest level category for this product.
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if (!is_array($categories)) {
            return [];
        }

        // Build array of category tree items for each product category.
        // For example:
        // [
        //     [Clothing] <- 1 tree item
        //     [Clothing, Hoodies] <- 2 tree items
        //     [Clothing, Accessories] <- 2 tree items
        //     [Music] <- 1 tree item
        // ]
        $categoriesTreeItems = [];
        foreach ($categories as $cat) {

            // $categoryTreeItems is an array with tree elements, starting with the top category and every next element is a deeper level.
            // For example:
            // [Clothing, Hoodies]
            $categoryTreeItems = [];

            // Building this array works the other way around: we start with the leaf and prepending the parents.
            $currentCategory = $cat;
            array_unshift($categoryTreeItems, $this->getCategoryStructure($currentCategory));

            // Loop from leaf all the way up to the top level category
            while ($currentCategory->parent > 0)
            {
                // Get parent category
                $parentCategoryId = $currentCategory->parent;
                $currentCategory  = LanguagePluginHelper::getTerm($parentCategoryId, 'product_cat'); // Assign the parent to the $currentCategory variable so the while loop can continue.

                // Put parent category as first element in $categoryTreeItems array
                array_unshift($categoryTreeItems, $this->getCategoryStructure($currentCategory));
            }

            if (count($categoryTreeItems) > 0) {
                $categoriesTreeItems[] = $categoryTreeItems;
            }
        }

        // Merge all category's tree items into entire category tree
        return $this->getCategoryTree($categoriesTreeItems);
    }

    /**
     * @param WP_Term $category
     * @return array
     */
    protected function getCategoryStructure(WP_Term $category): array
    {
        // Get category translations.
        $categoryTranslations = $this->getCategoryTranslations($category);

        $titles = [
            'title' => []
        ];

        foreach ($categoryTranslations as $locale => $categoryTranslation) {
            if (!empty($categoryTranslation->name)) {
                $titles['title'][] = [
                    '_attributes' => ['language' => $locale],
                    '_cdata'      => $categoryTranslation->name,
                ];
            }
        }

        return [
            'id'     => $category->term_taxonomy_id,
            'titles' => $titles
        ];
    }

    /**
     * @param array $categoriesTreeItems
     * @return array
     */
    protected function getCategoryTree(array $categoriesTreeItems): array
    {
        $categoryTree = [];

        foreach ($categoriesTreeItems as $categoryTreeItems)
        {
            $treeHead        = &$categoryTree;
            $categoryCounter = 0;

            foreach ($categoryTreeItems as $category)
            {
                $categoryId = $category['id'];

                if (isset($treeHead['category'][$categoryId])) {
                    $treeHead['category'][$categoryId] = $treeHead['category'][$categoryId] + $category;
                } else {
                    $treeHead['category'][$categoryId] = $category;
                }

                $categoryCounter++;
                if ($categoryCounter < count($categoryTreeItems)) {
                    if (!isset($treeHead['category'][$categoryId]['children'])) {
                        $treeHead['category'][$categoryId]['children'] = [];
                    }
                    $treeHead = &$treeHead['category'][$categoryId]['children'];
                }
            }
        }

        return $categoryTree;
    }

    /**
     * This method first looks at the type of product (Simple/Variable). If the product is of the simple variety, we only have to call setOptions once.
     * For WC_Product_Variable, we first need to get all variation id's, then iterate through this array of id's and get each product using wc_get_product.
     * @param WC_Product $product
     * @return array
     * @throws ProductVariationsCreationFailedException
     */
    protected function getAllOptions(WC_Product $product): array
    {
        $options = [];

        if ($product instanceof WC_Product_Variable) { // $product is an abstract class which can be of type WC_Product_Simple, or WC_Product_Variable
            $variations = $this->createProductVariations($product);
            foreach ($variations as $wcProductVariationWrapper) {
                $option = $this->createAvailableOptionsArray($wcProductVariationWrapper);
                if (count($option) > 0) {
                    $options[] = $option;
                }
            }
        } else {
            $option = $this->createAvailableOptionsArray(new WcProductVariationWrapper($product, $product->get_attributes()));
            if (count($option) > 0) {
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * This method tries to create an option array with all product details and attributes. Will ignore an attribute if it returns false.
     * @param WcProductVariationWrapper $wcProductVariationWrapper
     * @return array
     */
    protected function createAvailableOptionsArray(WcProductVariationWrapper $wcProductVariationWrapper): array
    {
        $productOption = $wcProductVariationWrapper->getWcProduct();

        // All the following functions will get their data from  $this->productTranslations
        $this->collectProductTranslations($productOption);

        try {
            $identifier = $this->getProductOptionId();
            $sku        = $this->getProductSku();
        } catch (Exception $e) {
            LoggerContainer::getLogger($this->loggerType)->warning('Skipping product because it has no identifier.', [
                'process'    => LoggerConstants::CATALOG_EXPORT,
                'id'         => $productOption->get_id(),
                'sku'        => $productOption->get_sku(),
                'connection' => $this->connection->getConnectionId(),
            ]);
            return [];
        }

        // Never export duplicate identifiers
        if (in_array($identifier, $this->_processedIdentifiers))
        {
            LoggerContainer::getLogger($this->loggerType)->warning('Skipping product because of duplicate identifier.', [
                'process' => LoggerConstants::CATALOG_EXPORT,
                'identifier' => $identifier,
                'id'         => $productOption->get_id(),
                'sku'        => $productOption->get_sku(),
                'connection' => $this->connection->getConnectionId(),
            ]);
            return [];
        }
        $this->_processedIdentifiers[] = $identifier;

        // Default/required product options values
        $productOptionExport = [
            'identifier' => $identifier,
            'sku'        => $sku,
            'stock'      => $this->getProductStock(),
        ];

        // EAN
        $modifiedEan = $this->getProductEan();
        if (!$this->validateEAN($modifiedEan))
        {
            // Skip invalid EAN or export product without EAN?
            if ($this->connection->getCatalogExportSkipInvalidEan())
            {
                LoggerContainer::getLogger($this->loggerType)->warning('Skipping product because of an invalid EAN.', [
                    'process' => LoggerConstants::CATALOG_EXPORT,
                    'identifier' => $identifier,
                    'sku'        => $sku,
                    'ean'        => $modifiedEan,
                    'connection' => $this->connection->getConnectionId(),
                ]);
                return [];
            }
            $modifiedEan = '';
        }

        // We don't include EAN field in export when EAN is empty
        if (!empty($modifiedEan))
        {
            // Never export duplicate EANs
            if (in_array($modifiedEan, $this->_processedEANs))
            {
                LoggerContainer::getLogger($this->loggerType)->warning('Skipping product because of duplicate EAN.', [
                    'process' => LoggerConstants::CATALOG_EXPORT,
                    'identifier' => $identifier,
                    'sku'        => $sku,
                    'ean'        => $modifiedEan,
                    'connection' => $this->connection->getConnectionId(),
                ]);
                return [];
            }

            $productOptionExport['ean'] = $modifiedEan;
            $this->_processedEANs[]     = $modifiedEan;
        }

        // Product titles (by language)
        $titles = $this->getProductTitles();
        if (count($titles) > 0) {
            $productOptionExport['titles']['title'] = $titles;
        }

        // Product descriptions (by language)
        $descriptions = $this->getProductDescriptions();
        if (count($descriptions) > 0) {
            $productOptionExport['descriptions']['description'] = $descriptions;
        }

        // Product urls (by language)
        $urls = $this->getProductUrls();
        if (count($urls) > 0) {
            $productOptionExport['urls']['url'] = $urls;
        }

        // Product cost
        $cost = $this->getProductCost();
        if (!empty($cost)) {
            $productOptionExport['cost'] = number_format($cost, 2, '.', '');
        }

        // Product delivery time
        $deliveryTime = $this->getProductDeliveryTime();
        if (!empty($deliveryTime)) {
            $productOptionExport['deliveryTime'] = $deliveryTime;
        }

        // Product images
        $images = $this->getProductImages();
        if (count($images) > 0) {
            $productOptionExport['images']['image'] = $images;
        }

        // Product prices
        $price                        = $this->getProductPrice();
        $priceOriginal                = $this->getProductPriceOriginal();
        $productOptionExport['price'] = number_format($price, 2, '.', '');
        if ($priceOriginal !== null) {
            $productOptionExport['priceOriginal'] = number_format($priceOriginal, 2, '.', '');
        }

        // Attributes
        $attributes = $this->deduplicateAttributes(array_merge(
            $this->connection->getCatalogExportTaxonomies() ? $this->getProductTaxonomies() : [],
            $this->getProductAttributes($wcProductVariationWrapper->getWcAttributes()),
            $this->getProductAttributesFixed(),
            $this->getProductPerfectBrandsAttributes(),
            $this->getProductFields(),
            $this->getProductMetaFields()
        ));
        if (count($attributes) > 0) {
            $productOptionExport['attributes']['attribute'] = $attributes;
        }

        return $productOptionExport;
    }

    /**
     * Returns the id for a product.
     *
     * @return int
     * @throws InvalidProductOptionIdException
     */
    protected function getProductOptionId(): int
    {
        $product = $this->getDefaultProductTranslation();
        return $this->productOptionsRepo->getProductOptionId($product, $this->getAttributeArray($product));
    }

    /**
     * Returns an array with the product's title in one or more languages.
     *
     * @return array
     */
    protected function getProductTitles(): array
    {
        $titles = [];

        foreach ($this->languages as $language) {
            $productTranslation = $this->getProductTranslation($language);
            $productTitle = $this->productOptionsRepo->getProductTitle($productTranslation, $this->connection);
            if (!empty($productTitle)) {
                $titles[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      => $productTitle
                ];
            }
        }

        return $titles;
    }

    /**
     * Returns an array with the product's description in one or more languages.
     *
     * @return array
     */
    protected function getProductDescriptions(): array
    {
        $descriptions = [];

        foreach ($this->languages as $language) {
            $productTranslation = $this->getProductTranslation($language);
            $productDescription = $this->productOptionsRepo->getProductDescription($productTranslation, $this->connection);

            // In case of variable product and empty description, fallback on parent product description
            if (empty($productDescription)) {
                $parentProductTranslation = $this->getParentProductTranslation($language);
                if ($parentProductTranslation instanceof WC_Product) {
                    $productDescription = $this->productOptionsRepo->getProductDescription($parentProductTranslation, $this->connection);
                }
            }

            if (!empty($productDescription)) {
                $descriptions[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      => $productDescription
                ];
            }
        }

        return $descriptions;
    }

    /**
     * Returns the current active price.
     * If the product is on sale, it will return the sale price, else it will return the original price.
     *
     * @return float
     */
    protected function getProductPrice(): float
    {
        $product = $this->getDefaultProductTranslation();
        return floatval($this->productOptionsRepo->getProductPrice($product, $this->connection));
    }

    /**
     * Returns a product's original price without discounts.
     *
     * @return float|null
     */
    protected function getProductPriceOriginal(): ?float
    {
        $product = $this->getDefaultProductTranslation();
        $price = $this->productOptionsRepo->getProductPriceOriginal($product, $this->connection);
        return is_null($price) ? null : floatval($price);
    }

    /**
     * Gets images attached to a product in the highest resolution available.
     * The first element in the result array is the main product image, other entries are gallery images.
     *
     * @return array
     */
    protected function getProductImages(): array
    {
        $images = [];
        $product = $this->getDefaultProductTranslation();

        // Gallery images
        $galleryIds = $product->get_gallery_image_ids();

        // Main product image ID (always add in front of other gallery images)
        $mainProductImageId = $product->get_image_id();
        if (!empty($mainProductImageId)) {
            array_unshift($galleryIds, $mainProductImageId);
        }

        // Add images (and use image url as key, since image urls must be unique)
        $imageUrls = [];
        foreach ($galleryIds as $id) {
            $image       = wp_get_attachment_image_src($id, 'full');
            $imageUrls[] = current($image);
        }

        // EC API limits amount of images (max 10 unique image urls)
        $uniqueImageUrls = array_slice(array_unique($imageUrls), 0, 10);

        // Add the images
        foreach ($uniqueImageUrls as $index => $imageUrl) {
            $images[] = [
                'url'   => $imageUrl,
                'order' => $index + 1,
            ];
        }

        return $images;
    }

    /**
     * Gets a product's Sku.
     *
     * @return string
     * @throws InvalidSkuException
     */
    protected function getProductSku(): string
    {
        $product = $this->getDefaultProductTranslation();
        $sku = trim(strval($product->get_sku()));
        if (empty($sku)) {
            throw new InvalidSkuException();
        }
        return $sku;
    }

    /**
     * Gets the current stock for a product.
     *
     * @return int
     */
    protected function getProductStock(): int
    {
        $product = $this->getDefaultProductTranslation();
        return $this->productOptionsRepo->getProductStock($product, $this->connection);
    }

    /**
     * Returns a product's associated brand.
     * The brand in EC has no translations (and is an attribute on the parent product).
     *
     * @param WC_Product $product
     * @return string
     */
    protected function getProductBrand(WC_Product $product): string
    {
        return $this->productOptionsRepo->getProductBrand($product, $this->connection);
    }

    /**
     * Recursive function to generate all combinations of elements from multiple arrays.
     *
     * @param $arrays
     * @return array
     */
    protected function combinations($arrays): array
    {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    /**
     * Gets all attributes with 'any' as the given value and the original fixed values.
     *
     * @param WC_Product_Variable $variableProduct
     * @param WC_Product_Variation $variation
     * @return array
     */
    protected function getAllValueAttributes(WC_Product_Variable $variableProduct, WC_Product_Variation $variation): array
    {
        $valuesArray = [];

        foreach ($variableProduct->get_variation_attributes() as $key => $attribute) {
            foreach ($variation->get_attributes() as $attrKey => $value) {
                // In case of local attributes the $key is in fact the attribute name and not the key.
                $sanitizedKey = $this->sanitize($key);
                if ($attrKey === $sanitizedKey) {
                    $valuesArray[$sanitizedKey] = strlen($value) === 0 ? $attribute : [$value];
                }
            }
        }
        return $valuesArray;
    }

    /**
     * Creates product variations based on non pre-defined attribute options.
     * Returns an array of type WC_Product.
     *
     * @param WC_Product_Variable $variableProduct
     * @return WcProductVariationWrapper[]
     * @throws ProductVariationsCreationFailedException
     */
    protected function createProductVariations(WC_Product_Variable $variableProduct): array
    {
        // Returning 'objects' is not available in WC4, we have to convert the returned array to an object ourselves.
        try {
            $variationsArray = $variableProduct->get_available_variations();
        } catch (Throwable $e) {
            throw new ProductVariationsCreationFailedException('Could not fetch product variations (message from WooCommerce: ' . $e->getMessage() . ')');
        }
        $variations = [];
        foreach ($variationsArray as $variation) {
            $variations[] = wc_get_product($variation['variation_id']);
        }

        // Structure of $termIdBySlug:
        // Array(
        //   [pa_color] => Array(
        //     [blue] => 18
        //     [green] => 23
        //     [red] => 24
        //   )
        //   [pa_size] => Array(
        //     [large] => 25
        //     [medium] => 26
        //     [small] => 27
        //   )
        //   [pa_has_logo] => Array(
        //     [0] => 47
        //     [1] => 46
        //   )
        // )
        $termIdBySlug = [];
        foreach ($variableProduct->get_attributes() as $wcAttributeKey => $wcAttribute) {
            /** @var WC_Product_Attribute $wcAttribute */
            foreach ($wcAttribute->get_options() as $option) {
                if (WcHelper::isGlobalAttribute($wcAttributeKey)) {
                    $term = LanguagePluginHelper::getTerm($option);
                    $termIdBySlug[$wcAttributeKey][$term->slug] = $option;
                } else {
                    $termIdBySlug[$wcAttributeKey][$option] = $option;
                }
            }
        }

        /** @var WC_Product_Variation[] $newProductVariations */
        $newProductVariations = [];
        foreach ($variations as $variation) {
            // Get all attribute keys that have no 'any' setting in WC. This setting creates a problem that a variation
            // could occur multiple times. The attributes that have a fixed value always have priority.
            // For example in the following case there could be a variation with color blue and EAN 9296478285174,
            // and since each exported EAN should be unique, we have to choose which variation to export:
            // Color: blue, EAN: any
            // Color: red, EAN: 9296478285174 <- has priority to export because of fixed EAN value
            // For now we will give priority to variations without empty.
            $variationAttributes       = $variation->get_attributes();
            $variationAttributesFilled = array_filter($variationAttributes); // Filter out empty values (which represent 'any' attribute values)
            $variationHasPriority      = (count($variationAttributes) === count($variationAttributesFilled));

            // Structure of the variation attributes ($variation->get_attributes()):
            // Array(
            //   [pa_has_logo] => 1
            //   [pa_size] =>
            //   [pa_color] =>
            // )
            //
            // The empty values mean 'any' is WC, so we have to find all possible values for size and color ourselves using the main product.
            // Structure of $allValueAttributes:
            // Array(
            //    [pa_has_logo] => Array(
            //            [0] => 1
            //        )
            //    [pa_size] => Array(
            //            [0] => large
            //            [1] => medium
            //            [2] => small
            //        )
            //    [pa_color] => Array(
            //            [0] => blue
            //            [1] => green
            //            [2] => red
            //        )
            // )
            $allValueAttributes = $this->getAllValueAttributes($variableProduct, $variation);

            // In case the main product (WC_Product_Variable) set 3 sizes and 3 colors, we have to generate
            // 3x3=9 product variations (since the 'has_logo' has a fixed value for the current variation).
            // Structure of $combinations:
            //Array
            //(
            //    [0] => Array(
            //            [pa_color] => blue
            //            [pa_size] => large
            //            [pa_has_logo] => 1
            //        )
            //    ...
            //    [8] => Array(
            //            [pa_color] => red
            //            [pa_size] => small
            //            [pa_has_logo] => 1
            //        )
            //)
            $combinations = $this->combinations($allValueAttributes); // recursively get all possible variations of a product based on the attributes with 'any' as their value.

            // All variations we are going to generate use the existing variation as a boilerplate.
            $existingProductVariation = wc_get_product($variation->get_id());
            if (!($existingProductVariation instanceof WC_Product_Variation)) {
                throw new ProductVariationsCreationFailedException('Unexpected product variation class');
            }

            foreach ($combinations as $combination) {
                /** @var WC_Product_Attribute[] $newProductAttributes */
                $newProductAttributes = [];
                $newProductAttributesKeyValue = [];
                foreach ($combination as $attributeKey => $attributeValue) {
                    $termId = $termIdBySlug[$attributeKey][$attributeValue] ?? 0;
                    $newProductAttribute = new WC_Product_Attribute();
                    $newProductAttribute->set_name($attributeKey);
                    $newProductAttribute->set_options([$termId]);
                    $newProductAttribute->set_variation(true);
                    $newProductAttributes[$attributeKey] = $newProductAttribute;
                    $newProductAttributesKeyValue[$attributeKey] = $attributeValue;
                }

                // Since the product attributes in WooCommerce in WC_Product_Simple differ from those in WC_Product_Variation,
                // we make use of a WcProductVariationWrapper to put the attributes in (instead of using $existingProductVariation->set_attributes()).
                // In WooCommerce the attributes of a WC_Product_Variation consists of key-value pairs instead of
                // an array with WC_Product_Attribute elements (which we prefer).
                // For WC functions we should also define the key-value pairs version of the attributes.
                $newProductVariation = clone $existingProductVariation;
                $newProductVariation->set_attributes($newProductAttributesKeyValue);
                $export = new WcProductVariationWrapper($newProductVariation, $newProductAttributes);

                // Order of variations.
                if ($variationHasPriority) {
                    array_unshift($newProductVariations, $export); // Add product to export at start of products array
                } else {
                    array_push($newProductVariations, $export); // Add product to export at end of products array
                }
            }
        }

        return $newProductVariations;
    }

    /**
     * Gets useful attribute data from WC_Product_Attribute objects.
     * @param WC_Product_Attribute $attribute
     * @return array
     */
    protected function getAttributesFromObject(WC_Product_Attribute $attribute): array
    {
        $options = $attribute->get_options();
        $values = [];

        foreach ($options as $option) {
            if (is_integer($option)) { // When the option value is an integer, it means it is the term id, so we need to call the get_term function for the value.
                $option = LanguagePluginHelper::getTerm($option);
                $values[] = $option->slug;
            } else if (strlen($option) !== 0) {
                $values[] = $option;
            }
        }

        return $values;
    }

    /**
     * Gets attributes in object or array form and puts them into a simple array.
     * @param WC_Product $product
     * @return array
     */
    protected function getAttributeArray(WC_Product $product): array
    {
        $attributes = $product->get_attributes();

        $attributeArray = [];

        foreach ($attributes as $key => $value) {
            $newValues = is_object($value) ? $this->getAttributesFromObject($value) : $value;

            if (is_array($newValues)) {
                if (count($newValues) > 1) {
                    foreach ($newValues as $newValue) {
                        $attributeArray[$key][] = $newValue;
                    }
                } else {
                    $attributeArray[$key] = $newValues[0];
                }
            } else {
                $attributeArray[$key] = $newValues;
            }
        }

        // Make sure the order of attributes is always the same
        foreach ($attributeArray as &$attributeValue) {
            if (is_array($attributeValue)) {
                sort($attributeValue); // Don't maintain array keys, because sequential array
            }
        }
        asort($attributeArray); // Do maintain keys

        return $attributeArray;
    }

    /**
     * Gets all taxonomies attached to a product.
     *
     * @return array
     */
    public function getProductTaxonomies(): array
    {
        $taxonomiesExport = [];
        $product = $this->getDefaultProductTranslation();
        $productId = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
        $allTaxonomies = WcHelper::getTaxonomies(true);
        $wcProductTaxonomies = $this->productOptionsRepo->getProductTaxonomies($productId, $allTaxonomies);

        foreach ($wcProductTaxonomies as $wcProductTaxonomy) {
            // TODO: skip duplicates

            //
            // Get taxonomy names for each language (fixed)
            // TODO: take WPML into account
            //

            $taxonomyLocaleExport = [];
            foreach ($this->languages as $language) {
                $taxonomyLocaleExport[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      =>  $allTaxonomies[$wcProductTaxonomy] ?? $wcProductTaxonomy,
                ];
            }

            //
            // Get taxonomy values for each language (fixed)
            // TODO: take WPML into account
            //

            $taxonomyValuesExport = [];

            $values = wp_get_object_terms($productId, $wcProductTaxonomy, ['fields' => 'names']);
            foreach ($values as $value) {
                $taxonomyLocaleValuesExport = [];
                foreach ($this->languages as $language) {
                    if (!empty($value)) {
                        $taxonomyLocaleValuesExport[] = [
                            '_attributes' => ['language' => $language],
                            '_cdata'      => $value,
                        ];
                    }
                }

                if (count($taxonomyLocaleValuesExport) == 0) {
                    continue;
                }

                $taxonomyValuesExport[] = [
                    'code'  => $wcProductTaxonomy . '-' . $this->sanitize($value),
                    'names' => [
                        'name' => $taxonomyLocaleValuesExport,
                    ],
                ];
            }

            if (count($taxonomyValuesExport) == 0) {
                continue;
            }

            $taxonomiesExport[] = [
                'code'   => $wcProductTaxonomy,
                'names' => [
                    'name' => $taxonomyLocaleExport,
                ],
                'values' => [
                    'value' => $taxonomyValuesExport,
                ],
            ];
        }

        return $taxonomiesExport;
    }

    /**
     * Gets meta fields attached to a product.
     * Note that in this function we only take ACF fields into account (https://www.advancedcustomfields.com).
     * Meta fields that are generated 'on-the-fly' (for example by other plugins code base) are fetched with 'getProductMetaFields'.
     *
     * @return array
     */
    public function getProductFields(): array
    {
        if (!function_exists('get_fields') || !function_exists('get_field_object')) {
            return [];
        }

        $fieldsExport = [];
        $product = $this->getDefaultProductTranslation();
        $productId = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
        $fields = get_fields($productId);
        if (!$fields) {
            return [];
        }

        foreach (array_keys($fields) as $fieldName) {

            $fieldInfo = get_field_object($fieldName, $productId);
            if (!$fieldInfo) {
                continue;
            }

            //
            // Get field names for each language (fixed)
            // TODO: take WPML into account
            //

            $fieldLocaleExport = [];
            foreach ($this->languages as $language) {
                $fieldLocaleExport[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      =>  isset($fieldInfo['label']) && !empty($fieldInfo['label']) ? $fieldInfo['label'] : $fieldName
                ];
            }

            //
            // Get field values for each language (fixed)
            // TODO: take WPML into account
            //

            $fieldValuesExport = [];

            $values = $fieldInfo['value'] ?? [];
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $fieldLocaleValuesExport = [];
                foreach ($this->languages as $language) {
                    if (!empty($value) && is_scalar($value)) {
                        $fieldLocaleValuesExport[] = [
                            '_attributes' => ['language' => $language],
                            '_cdata'      => strval($value),
                        ];
                    }
                }

                if (count($fieldLocaleValuesExport) == 0) {
                    continue;
                }

                $fieldValuesExport[] = [
                    'code'  => $fieldName . '-' . $this->sanitize($value),
                    'names' => [
                        'name' => $fieldLocaleValuesExport,
                    ],
                ];
            }

            if (count($fieldValuesExport) == 0) {
                continue;
            }

            $fieldsExport[] = [
                'code'   => $fieldName,
                'names' => [
                    'name' => $fieldLocaleExport,
                ],
                'values' => [
                    'value' => $fieldValuesExport,
                ],
            ];
        }

        return $fieldsExport;
    }

    /**
     * Gets extra meta fields attached to a product.
     * In the function 'getProductFields' we already fetched ACF fields (https://www.advancedcustomfields.com).
     * In this function we also fetch fields that are generated 'on-the-fly' (for example by other plugins code base).
     *
     * @return array
     */
    public function getProductMetaFields(): array
    {
        $fieldsExport = [];
        $product = $this->getDefaultProductTranslation();

        // Only add meta that don't start with an underscore (those are internal fields).
        $metas = [];
        $allMetas = $product->get_meta_data();
        foreach ($allMetas as $meta) {
            /** @var WC_Meta_Data $meta */
            if (!empty($meta->key) && is_string($meta->key) && substr($meta->key, 0, 1) !== '_') {
                $metas[] = $meta;
            }
        }

        foreach ($metas as $meta) {

            //
            // Get field names for each language (fixed)
            // TODO: take WPML into account
            //

            $fieldLocaleExport = [];
            foreach ($this->languages as $language) {
                $fieldLocaleExport[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      =>  $meta->key,
                ];
            }

            //
            // Get field values for each language (fixed)
            // TODO: take WPML into account
            //

            $fieldValuesExport = [];

            $values = $meta->value ?? [];
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $fieldLocaleValuesExport = [];
                foreach ($this->languages as $language) {
                    if (!empty($value) && is_scalar($value)) {
                        $fieldLocaleValuesExport[] = [
                            '_attributes' => ['language' => $language],
                            '_cdata'      => strval($value),
                        ];
                    }
                }

                if (count($fieldLocaleValuesExport) == 0) {
                    continue;
                }

                $fieldValuesExport[] = [
                    'code'  => $meta->key . '-' . $this->sanitize($value),
                    'names' => [
                        'name' => $fieldLocaleValuesExport,
                    ],
                ];
            }

            if (count($fieldValuesExport) == 0) {
                continue;
            }

            $fieldsExport[] = [
                'code'   => $meta->key,
                'names' => [
                    'name' => $fieldLocaleExport,
                ],
                'values' => [
                    'value' => $fieldValuesExport,
                ],
            ];
        }

        return $fieldsExport;
    }

    /**
     * Gets all attributes attached to a product.
     *
     * @param WC_Product_Attribute[] $wcAttributes
     * @return array
     */
    protected function getProductAttributes(array $wcAttributes): array
    {
        $attributesExport = [];
        $product          = $this->getDefaultProductTranslation();

        foreach ($wcAttributes as $wcAttributeKey => $wcAttribute) {

            //
            // Get attributes
            //

            $attributeLocaleExport = [];
            foreach ($this->languages as $language) {
                // Our equivalent of WPML's translated_attribute_label
                if (WcHelper::isGlobalAttribute($wcAttributeKey)) {
                    $name = $this->getGlobalAttributeLabelTranslation($wcAttributeKey, $language);
                } else {
                    $name = $this->getLocalAttributeLabelTranslation($wcAttributeKey, $language);
                }
                if (!empty($name)) {
                    $attributeLocaleExport[] = [
                        '_attributes' => ['language' => $language],
                        '_cdata'      => $name,
                    ];
                }
            }

            //
            // Get attribute values
            //

            $attributeValuesExport = [];

            $options = $wcAttribute->get_options();
            foreach ($options as $optionKey => $option) {
                $attributeLocaleValuesExport = [];
                foreach ($this->languages as $language) {
                    if (WcHelper::isGlobalAttribute($wcAttributeKey)) {
                        // Global attribute - in case of global attribute, the option value represents the term ID
                        $value = $this->getGlobalAttributeValueTranslation(intval($option), $language);
                    } else {
                        // Local attribute
                        $value = $this->getLocalAttributeValueTranslation($wcAttributeKey, $optionKey, $language);
                    }
                    if (!empty($value)) {
                        $attributeLocaleValuesExport[] = [
                            '_attributes' => ['language' => $language],
                            '_cdata'      => $value,
                        ];
                    }
                }

                if (count($attributeLocaleValuesExport) == 0) {
                    continue;
                }

                $attributeValuesExport[] = [
                    'code'   => $wcAttributeKey . '-' . $this->sanitize($option),
                    'names' => [
                        'name' => $attributeLocaleValuesExport,
                    ],
                ];
            }

            if (count($attributeValuesExport) == 0) {
                continue;
            }

            $attributesExport[] = [
                'code'   => $wcAttributeKey,
                'names' => [
                    'name' => $attributeLocaleExport,
                ],
                'values' => [
                    'value' => $attributeValuesExport,
                ],
            ];
        }

        return $attributesExport;
    }

    /**
     * @return array
     */
    protected function getProductPerfectBrandsAttributes(): array
    {
        if (!PerfectBrandsPluginHelper::perfectBrandsPluginActivated()) {
            return [];
        }

        $attributeKey = PerfectBrandsPluginHelper::WC_PLUGINS_PERFECT_BRANDS_PREFIX . PerfectBrandsPluginHelper::WC_PLUGINS_PERFECT_BRANDS_ATTRIBUTE;

        //
        // Get attribute names for each language (fixed)
        //

        $attributeLocaleExport = [];

        foreach ($this->languages as $language) {
            $attributeLocaleExport[] = [
                '_attributes' => ['language' => $language],
                '_cdata'      =>  'Perfect Brands Attribute',
            ];
        }

        //
        // Get attribute values
        //

        $attributeValuesExport = [];

        $product = $this->getDefaultProductTranslation();
        $values = PerfectBrandsPluginHelper::getValues($product);
        foreach ($values as $value) {
            $attributeLocaleValuesExport = [];
            foreach ($this->languages as $language) {
                if (!empty($value)) {
                    $attributeLocaleValuesExport[] = [
                        '_attributes' => ['language' => $language],
                        '_cdata'      => $value,
                    ];
                }
            }

            if (count($attributeLocaleValuesExport) == 0) {
                continue;
            }

            $attributeValuesExport[] = [
                'code'   => $attributeKey . '-' . $this->sanitize($value),
                'names' => [
                    'name' => $attributeLocaleValuesExport,
                ],
            ];
        }

        if (count($attributeValuesExport) == 0) {
            return [];
        }

        return [
            [
                'code'   => $attributeKey,
                'names' => [
                    'name' => $attributeLocaleExport,
                ],
                'values' => [
                    'value' => $attributeValuesExport,
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getProductAttributesFixed(): array
    {
        $attributesExport = [];

        $fixedAttributes = [
            'width'           => 'get_width',
            'height'          => 'get_height',
            'length'          => 'get_length',
            'weight'          => 'get_weight',
            'parent_title'    => 'get_title',
            'variation_title' => 'get_name',
            'backorders'      => 'get_backorders',
        ];
        foreach ($fixedAttributes as $fixedAttributeKey => $fixedAttributeFunction)
        {
            $attributeValueCode = '';

            $attributeValueNames = [];

            foreach ($this->languages as $language) {
                $productTranslation = $this->getProductTranslation($language);
                if (method_exists($productTranslation, $fixedAttributeFunction)) {
                    $attributeValue = call_user_func([$productTranslation, $fixedAttributeFunction]);
                    if (!empty($attributeValue)) {
                        if (empty($attributeValueCode)) {
                            $attributeValueCode = $this->sanitize($attributeValue);
                        }
                        $attributeValueNames[] = [
                            '_attributes' => ['language' => $language],
                            '_cdata'      => $attributeValue,
                        ];
                    }
                }
            }

            if (empty($attributeValueCode) || count($attributeValueNames) === 0) {
                continue;
            }

            $attributeNames = [];

            foreach ($this->languages as $language) {
                $attributeNames[] = [
                    '_attributes' => ['language' => $language],
                    '_cdata'      =>  $fixedAttributeKey . ' (Fixed WooCommerce Attribute)',
                ];
            }

            $attributeValuesExport = [];
            $attributeValuesExport[] = [
                'code'   => $attributeValueCode,
                'names' => [
                    'name' => $attributeValueNames,
                ],
            ];

            $attributeCode = WcHelper::WC_DEFAULT_ATTRIBUTE_PREFIX . $fixedAttributeKey;
            $attributesExport[] = [
                'code'   => $attributeCode,
                'names' => [
                    'name' => $attributeNames,
                ],
                'values' => [
                    'value' => $attributeValuesExport,
                ],
            ];
        }

        return $attributesExport;
    }

    /**
     * Gets a product's permalink.
     *
     * @return array
     */
    protected function getProductUrls(): array
    {
        $urls = [];

        foreach ($this->languages as $language) {
            $productTranslation = $this->getProductTranslation($language);
            $url = get_permalink($productTranslation->get_id());
            if (!empty($url)) {
                $urls[] = [
                    '_cdata'      => $url,
                    '_attributes' => ['language' => $language]
                ];
            }
        }

        return $urls;
    }

    /**
     * @return string
     */
    protected function getDefaultLanguage(): string
    {
        if ($this->connection->getCatalogExportWpmlLanguages()) {
            return LanguagePluginHelper::getDefaultLanguage();
        } else {
            return $this->connection->getCatalogExportLanguage();
        }
    }

    /**
     * @return string
     */
    protected function getProductEan(): string
    {
        $product = $this->getDefaultProductTranslation();
        return $this->productOptionsRepo->getProductEan($product, $this->connection);
    }

    /**
     * @return string
     */
    protected function getProductCost(): string
    {
        $product = $this->getDefaultProductTranslation();
        return $this->productOptionsRepo->getProductCost($product, $this->connection);
    }

    /**
     * @return string
     */
    protected function getProductDeliveryTime(): string
    {
        $product = $this->getDefaultProductTranslation();
        return $this->productOptionsRepo->getProductDeliveryTime($product, $this->connection);
    }

    /**
     * @param string $ean
     * @return bool
     */
    protected function validateEAN(string $ean): bool
    {
        $validator = new Barcode('EAN13');
        return $validator->isValid($ean);
    }

    /**
     * @param WC_Product $product
     */
    protected function collectProductTranslations(Wc_Product $product)
    {
        // By default add current product as default locale.
        $defaultLocale = $this->getDefaultLanguage();
        $productTranslations = [
            $defaultLocale => $product
        ];

        // Get available languages for current product.
        $translationIds = LanguagePluginHelper::getProductTranslationIds($product->get_id());

        // Get translated products for all available locales.
        foreach ($translationIds as $locale => $id) {
            if ($locale !== $defaultLocale) {
                $productTranslation = wc_get_product($id);
                if ($productTranslation instanceof WC_Product) {
                    $productTranslations[$locale] = $productTranslation;
                }
            }
        }

        // Also collect parent product info (to be used for fallbacks - for example if variation description is empty, we can use parent description)
        $parentProductTranslations = [];
        foreach ($productTranslations as $locale => $productTranslation) {
            if ($productTranslation instanceof WC_Product_Variation) {
                $parentProductTranslation = wc_get_product($productTranslation->get_parent_id());
                if ($parentProductTranslation instanceof WC_Product) {
                    $parentProductTranslations[$locale] = $parentProductTranslation;
                }
            }
        }

        $this->productTranslations       = $productTranslations;
        $this->parentProductTranslations = $parentProductTranslations;
    }

    /**
     * @param WP_Term $category
     * @return WP_Term[]
     */
    protected function getCategoryTranslations(WP_Term $category): array
    {
        $id = $category->term_taxonomy_id;
        if (!isset($this->categoryTranslationsByIdAndLocale[$id])) {
            // Always include default values for all available languages for fallback.
            $categoryTranslations = [];
            foreach ($this->languages as $language) {
                $categoryTranslations[$language] = $category;
            }

            // Get available languages for current product.
            $translationIds = LanguagePluginHelper::getProductCategoryTranslationsIds($id);

            // Get translated products for all available locales.
            foreach ($translationIds as $locale => $id) {
                $categoryTranslation = LanguagePluginHelper::getTerm($id);
                if ($categoryTranslation instanceof WP_Term) {
                    $categoryTranslations[$locale] = $categoryTranslation;
                }
            }

            $this->categoryTranslationsByIdAndLocale[$id] = $categoryTranslations;
        }

        return $this->categoryTranslationsByIdAndLocale[$id];
    }

    /**
     * @param int $selectedTermId
     * @param string $selectedLocale
     * @return string
     */
    protected function getGlobalAttributeValueTranslation(int $selectedTermId, string $selectedLocale): string
    {
        if (!isset($this->attributeTranslationsByIdAndLocale[$selectedTermId])) {
            $term = LanguagePluginHelper::getTerm($selectedTermId);
            $labelsTranslated = [];
            if ($term instanceof WP_Term) {
                // Always include default values for all available languages for fallback.
                foreach ($this->languages as $language) {
                    $labelsTranslated[$language] = $term->name;
                }

                // In case of translations the default value for default locale will be overwritten.
                $termTranslations = LanguagePluginHelper::getTermTranslations($term->term_taxonomy_id, 'tax_' . $term->taxonomy);
                foreach ($termTranslations as $locale => $termTranslation) {
                    $labelsTranslated[$locale] = $termTranslation;
                }
            }
            $this->attributeTranslationsByIdAndLocale[$selectedTermId] = $labelsTranslated;
        }

        return strval($this->attributeTranslationsByIdAndLocale[$selectedTermId][$selectedLocale] ?? '');
    }

    /**
     * @param string $wcAttributeKey
     * @param int $optionKey
     * @param string $selectedLocale
     * @return string
     */
    protected function getLocalAttributeValueTranslation(string $wcAttributeKey, int $optionKey, string $selectedLocale): string
    {
        $productTranslation = $this->getProductTranslation($selectedLocale);
        $wcAttributeTranslation = $productTranslation->get_attributes()[$wcAttributeKey] ?? null;

        // Attribute is unknown for current product translation, fallback on default product.
        if (is_null($wcAttributeTranslation)) {
            $productTranslation = $this->getDefaultProductTranslation();
            $wcAttributeTranslation = $productTranslation->get_attributes()[$wcAttributeKey] ?? null;
        }

        // For current language there are fewer values than in the original product:
        // For example the original (en) product has values 'ONE | TWO | THREE' and the translated product (nl) has 'EEN | TWEE'.
        // In this case also fallback on default product.
        if ($wcAttributeTranslation instanceof WC_Product_Attribute && !isset($wcAttributeTranslation->get_options()[$optionKey])) {
            $productTranslation = $this->getDefaultProductTranslation();
            $wcAttributeTranslation = $productTranslation->get_attributes()[$wcAttributeKey] ?? null;
        }

        // Yes, get_attributes can both return an array of strings
        // (in case of local attributes in product variations) or an array of WC_Product_Attribute elements.
        if ($wcAttributeTranslation instanceof WC_Product_Attribute) {
            $optionValue = $wcAttributeTranslation->get_options()[$optionKey] ?? null;
            return strval($optionValue ?? '');
        } elseif (is_string($wcAttributeTranslation)) {
            return $wcAttributeTranslation;
        }

        return '';
    }

    /**
     * @return WC_Product
     */
    protected function getDefaultProductTranslation(): WC_Product
    {
        return reset($this->productTranslations);
    }

    /**
     * @param string $language
     * @return WC_Product
     */
    protected function getProductTranslation(string $language): WC_Product
    {
        // Automatically fallback on default product in case of missing translations.
        if (isset($this->productTranslations[$language])) {
            return $this->productTranslations[$language];
        }
        return $this->getDefaultProductTranslation();
    }

    /**
     * @param string $language
     * @return WC_Product|null
     */
    protected function getParentProductTranslation(string $language)
    {
        if (isset($this->parentProductTranslations[$language])) {
            return $this->parentProductTranslations[$language];
        }
        if (count($this->parentProductTranslations) > 0) {
            return reset($this->parentProductTranslations);
        }
        return null;
    }

    /**
     * @param string $attributeKey
     * @param string $language
     * @return string|null
     */
    protected function getGlobalAttributeLabelTranslation(string $attributeKey, string $language): ?string
    {
        // Don't fetch translated product, because it might not exist.
        // The translations of global attribute names do not depend on product language, so they might exist even if the product translation is missing.
        $product = $this->getDefaultProductTranslation();
        return LanguagePluginHelper::getGlobalAttributeLabelTranslation($attributeKey, $product, $language);
    }

    /**
     * @param string $attributeKey
     * @param string $language
     * @return string
     */
    protected function getLocalAttributeLabelTranslation(string $attributeKey, string $language): string
    {
        $productTranslation = $this->getProductTranslation($language);
        return LanguagePluginHelper::getLocalAttributeLabelTranslation($attributeKey, $productTranslation, $language);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function sanitize(string $string): string
    {
        return function_exists('wc_sanitize_taxonomy_name') ? wc_sanitize_taxonomy_name($string) : $string;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function deduplicateAttributes(array $attributes): array
    {
        $attributeCodes   = [];
        $uniqueAttributes = [];
        foreach ($attributes as $attribute) {
            // Skip duplicate attribute key
            if (!in_array($attribute['code'], $attributeCodes)) {
                $attributeValueCodes   = [];
                $uniqueAttributeValues = [];
                $attributeValues       = &$attribute['values']['value'];
                foreach ($attributeValues as $attributeValue) {
                    // Skip duplicate attribute value key
                    if (!in_array($attributeValue['code'], $attributeValueCodes)) {
                        $uniqueAttributeValues[] = $attributeValue;
                        $attributeValueCodes[]   = $attributeValue['code'];
                    }
                }
                $attributeValues    = $uniqueAttributeValues;
                $uniqueAttributes[] = $attribute;
                $attributeCodes[]   = $attribute['code'];
            }
        }
        return $uniqueAttributes;
    }

    /**
     * @return void
     * @throws InvalidLanguageException
     */
    public function setLanguages()
    {
        // Also, when customer does not want to export WPML languages, WPML still dictates what languages are available for export.
        $availableLanguages = array_keys(LanguageHelper::getAvailableLanguages());

        // The default language in the connection should appear within available languages list.
        if (!in_array($this->getDefaultLanguage(), $availableLanguages)) {
            throw new InvalidLanguageException($this->getDefaultLanguage(), implode(', ', $availableLanguages));
        }

        // Select languages to export
        if ($this->connection->getCatalogExportWpmlLanguages()) {
            $this->languages = $availableLanguages;
        } else {
            $this->languages = [$this->getDefaultLanguage()];
        }

        // Note: in case of WPML this won't fetch products that have a main language set differs from the language we set below.
        LanguagePluginHelper::setLanguage($this->getDefaultLanguage());
    }
}
