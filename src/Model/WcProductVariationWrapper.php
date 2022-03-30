<?php

namespace EffectConnect\Marketplaces\Model;

use WC_Product;
use WC_Product_Attribute;

class WcProductVariationWrapper
{
    /**
     * @var WC_Product
     */
    protected $wcProduct;

    /**
     * @var WC_Product_Attribute[]
     */
    protected $wcAttributes;

    /**
     * @param WC_Product $wcProduct
     * @param WC_Product_Attribute[] $wcAttributes
     */
    public function __construct(WC_Product $wcProduct, array $wcAttributes)
    {
        $this->wcProduct = $wcProduct;
        $this->wcAttributes = $wcAttributes;
    }

    /**
     * @return WC_Product
     */
    public function getWcProduct(): WC_Product
    {
        return $this->wcProduct;
    }

    /**
     * @return WC_Product_Attribute[]
     */
    public function getWcAttributes(): array
    {
        return $this->wcAttributes;
    }
}