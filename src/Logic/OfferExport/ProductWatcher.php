<?php
namespace EffectConnect\Marketplaces\Logic\OfferExport;

use EffectConnect\Marketplaces\DB\ProductRepository;
use EffectConnect\Marketplaces\Helper\Languages\LanguagePluginHelper;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use EffectConnect\Marketplaces\Model\ConnectionCollection;
use WC_Product;

class ProductWatcher
{
    static $updateInfo = [];

    /**
     * @var ProductRepository
     */
    protected $productRepo;

    public function __construct()
    {
        $this->productRepo = ProductRepository::getInstance();

        add_action('woocommerce_product_set_stock', [$this, 'beforeStockChanged']);
        add_action('woocommerce_variation_set_stock', [$this, 'beforeStockChanged']);
        add_action('pre_post_update', [$this, 'beforePostUpdate']);
        add_action('save_post', [$this, 'afterPostUpdate']);
    }

    /**
     * Before a post is updated, checks if the post is a WC_Product and saves the update attributes in a singleton array.
     * @param $postId
     */
    public function beforePostUpdate( $postId )
    {
        $product = wc_get_product($postId);
        if ($product) {
            $updateArray = $this->getUpdateAttributes($product);
            self::$updateInfo[$postId] = $updateArray;
        }
    }

    /**
     * After a post is updated, checks if the post is a WC_Product. When the post is of type WC_Product, checks if the update attributes are changed.
     * If changed: puts the product into the offer update queue
     * @param $postId
     */
    public function afterPostUpdate( $postId )
    {
        $product = wc_get_product($postId);
        if ($product) {
            if (isset(self::$updateInfo[$postId])) {
                $old = self::$updateInfo[$postId];
                $new = $this->getUpdateAttributes($product);

                if ($old !== $new) {
                    $this->queue($product);
                }
                unset(self::$updateInfo[$postId]);
            }
        }
    }

    /**
     * When a stock change is detected, puts the product into the offer update queue.
     * @param WC_Product $product
     */
    public function beforeStockChanged(WC_Product $product)
    {
        $this->queue($product);
    }

    /**
     * Attributes to detect changes for (for each connection, since selected attribute for each entity depends on connections settings):
     * getProductPrice()
     * getProductPriceOriginal()
     * getProductStock()
     * getProductCost()
     * getProductDeliveryTime()
     *
     * @param WC_Product $product
     * @return array
     */
    protected function getUpdateAttributes(WC_Product $product): array
    {
        $productUpdateAttributes = [];

        foreach (ConnectionCollection::getActive() as $connection) {
            $productUpdateAttributes[] = $this->productRepo->getProductPrice($product, $connection);
            $productUpdateAttributes[] = $this->productRepo->getProductPriceOriginal($product, $connection);
            $productUpdateAttributes[] = $this->productRepo->getProductStock($product, $connection);
            $productUpdateAttributes[] = $this->productRepo->getProductCost($product, $connection);
            $productUpdateAttributes[] = $this->productRepo->getProductDeliveryTime($product, $connection);
        }

        return $productUpdateAttributes;
    }

    /**
     * @param WC_Product $product
     * @return void
     */
    protected function queue(WC_Product $product)
    {
        // Only queue product in case user enabled $this->config->insertIntoProductUpdateQueue()
        $config = ConfigContainer::getInstance();
        if ($config->getExportOnProductChangeValue()) {
            // Always queue given product.
            $productIds[$product->get_id()] = true;

            // In case of WPML let's also queue all translations of the current product.
            $translationIds = LanguagePluginHelper::getProductTranslationIds($product->get_id());
            foreach ($translationIds as $id) {
                $productIds[$id] = true;
            }

            // Queue the products.
            foreach (array_keys($productIds) as $productId) {
                $this->productRepo->insertIntoProductUpdateQueue($productId);
                LoggerContainer::getLogger()->info('Added product to export queue.', [
                    'product' => $productId,
                ]);
            }
        }
    }
}