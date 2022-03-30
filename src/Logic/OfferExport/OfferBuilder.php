<?php

namespace EffectConnect\Marketplaces\Logic\OfferExport;

use EffectConnect\Marketplaces\DB\ProductRepository;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Logic\CatalogExport\CatalogBuilder;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use Exception;
use WC_Product;

class OfferBuilder extends CatalogBuilder
{
    /**
     * @var ProductRepository
     */
    private $productRepo;

    public function __construct(string $loggerType, ConnectionResource $connection)
    {
        parent::__construct($loggerType, $connection);
        $this->productRepo = ProductRepository::getInstance();
    }

    /**
     * Gets only the necessary parts that need to be updated for a single product in an offer update.
     * @param WC_Product[] $products
     * @param $productId
     * @return array
     */
    private function buildOfferUpdate(array $products, $productId): array
    {
        $productOptionsExport = [];
        foreach ($products as $productOption) {
            // All the following functions will get their data from  $this->productTranslations
            $this->productTranslations = $this->getProductTranslations($productOption);

            $productOptionExport = [
                'identifier' => $productOption->get_id(),
                'stock'      => $this->getProductStock(),
            ];

            // Product cost
            $cost = $this->getProductCost();
            if (!empty($cost)) {
                $productOptionExport['cost'] = number_format($cost, 2, '.', '');
            }

            // Product delivery time
            $deliveryTime = $this->getProductDeliveryTime();
            if (!empty($cost)) {
                $productOptionExport['deliveryTime'] = $deliveryTime;
            }

            // Product prices
            $price                        = $this->getProductPrice();
            $priceOriginal                = $this->getProductPriceOriginal();
            $productOptionExport['price'] = number_format($price, 2, '.', '');
            if ($priceOriginal !== null) {
                $productOptionExport['priceOriginal'] = number_format($priceOriginal, 2, '.', '');
            }

            $productOptionsExport[] = $productOptionExport;
        }

        $productData = [];
        if (count($productOptionsExport) > 0) {
            $productData = [
                'identifier' => $productId,
                'options'    => [
                    'option' => $productOptionsExport
                ]
            ];
        }

        return $productData;
    }

    /**
     * Gets an array structure of one or more products that need to be updated.
     * @param WC_Product[] $products
     * @param $connectionId
     * @return array
     */
    protected function getUpdateModel(array $products, $connectionId): array
    {

        $productModel = [];

        foreach ($products as $product) {
            try {

                $productId = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
                $options = $this->productRepo->getAllProductOptionIds($productId);

                $optionsArray = [];
                foreach ($options as $option) {
                    if ($option->variation_id > 0) { // If this condition is true, we need to get the variable product, so we don't update each product option with the parent's values.
                        $product = wc_get_product($option->variation_id);
                    }

                    $clone = clone $product;
                    $clone->set_id($option->option_id);
                    $optionsArray[] = $clone;
                }

                $productData = $this->buildOfferUpdate($optionsArray, $productId);
                if (count($productData) > 0) $productModel[] = $productData;
            } catch (Exception $e) {
                LoggerContainer::getLogger(LoggerConstants::OFFER_EXPORT)->error('Skipping invalid product', [
                    'process' => LoggerConstants::OFFER_EXPORT,
                    'message' => $e->getMessage(),
                    'product_id' => $product->get_id(),
                    'connection' => $connectionId
                ]);
                continue;
            }
        }
        return $productModel;
    }
}