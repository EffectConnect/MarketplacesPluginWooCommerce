<?php

namespace EffectConnect\Marketplaces\Api;

use CURLFile;
use EffectConnect\Marketplaces\Exception\ApiCallFailedException;
use EffectConnect\Marketplaces\Exception\CatalogExportFailedException;
use EffectConnect\Marketplaces\Exception\FileCreationFailedException;
use EffectConnect\Marketplaces\Exception\InitSdkException;
use EffectConnect\Marketplaces\Exception\InvalidExportTypeException;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Exception\InvalidLanguageException;
use EffectConnect\Marketplaces\Exception\NoProductsToExportException;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Logic\CatalogExport\CatalogExport;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use EffectConnect\Marketplaces\Logic\OfferExport\OfferExport;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\PHPSdk\Core\CallType\ProductsCall;
use Exception;
use WC_Product;

class ProductHandler extends ApiCallHandler
{
    const FULL_EXPORT = 'full_export';
    const QUEUED_EXPORT = 'queued_export';

    /**
     * Total pages when getting products from WooCommerce with Pagination.
     * @var
     */
    private $totalPages;

    /**
     * Creates a catalog export call.
     *
     * @param ConnectionResource $connection
     * @throws CatalogExportFailedException
     * @throws NoProductsToExportException
     */
    public function catalogExport(ConnectionResource $connection)
    {
        $this->startLogging(LoggerConstants::CATALOG_EXPORT, $connection);

        $productsCall = $this->initProductsCall(LoggerConstants::CATALOG_EXPORT, $connection);

        try {
            $productCreateFileLocation = $this->getCatalog($connection);
        } catch (FileCreationFailedException|InvalidLanguageException $e) {
            LoggerContainer::getLogger(LoggerConstants::CATALOG_EXPORT)->error('Catalog export failed when building catalog.', [
                'process' => LoggerConstants::CATALOG_EXPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::CATALOG_EXPORT, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Build Catalog XML - ' . $e->getMessage());
        } catch (NoProductsToExportException $e) {
            LoggerContainer::getLogger(LoggerConstants::CATALOG_EXPORT)->info('No products to export.', [
                'process' => LoggerConstants::CATALOG_EXPORT,
            ]);
            $this->stopLogging(LoggerConstants::CATALOG_EXPORT, $connection);
            throw new NoProductsToExportException();
        }

        $curlFile = $this->initCurlFile($productCreateFileLocation, LoggerConstants::CATALOG_EXPORT, $connection);

        try {
            $apiCall = $productsCall->create($curlFile);
            $this->callAndResolveResponse($apiCall);
        } catch (ApiCallFailedException $e) {
            LoggerContainer::getLogger(LoggerConstants::CATALOG_EXPORT)->error('Catalog export failed when doing create call to EffectConnect.', [
                'process' => LoggerConstants::CATALOG_EXPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::CATALOG_EXPORT, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Product Create Call - ' . $e->getMessage());
        }

        $this->stopLogging(LoggerConstants::CATALOG_EXPORT, $connection);
    }

    /**
     * Executes an offer export. Specify the type of export with the type parameter. type options: 'full_export' (default), 'queued_export'.
     *
     * @param string $type
     * @param ConnectionResource $connection
     * @throws CatalogExportFailedException
     * @throws NoProductsToExportException
     */
    public function offerExport(string $type, ConnectionResource $connection)
    {
        $this->startLogging(LoggerConstants::OFFER_EXPORT, $connection);

        $productsCall = $this->initProductsCall(LoggerConstants::OFFER_EXPORT, $connection);

        try {
            $productUpdateFileLocation = $this->getOfferUpdate($type, $connection);
        } catch (FileCreationFailedException|InvalidExportTypeException|InvalidLanguageException $e) {
            LoggerContainer::getLogger(LoggerConstants::OFFER_EXPORT)->error('Offer export failed when building offer catalog.', [
                'process' => LoggerConstants::OFFER_EXPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::OFFER_EXPORT, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Build Offer XML - ' . $e->getMessage());
        } catch (NoProductsToExportException $e) {
            LoggerContainer::getLogger(LoggerConstants::OFFER_EXPORT)->info('No offers to export.', [
                'process' => LoggerConstants::OFFER_EXPORT,
            ]);
            $this->stopLogging(LoggerConstants::OFFER_EXPORT, $connection);
            throw new NoProductsToExportException();
        }

        $curlFile = $this->initCurlFile($productUpdateFileLocation, LoggerConstants::OFFER_EXPORT, $connection);

        try {
            $apiCall = $productsCall->update($curlFile);
            $this->callAndResolveResponse($apiCall);
        } catch (ApiCallFailedException $e) {
            LoggerContainer::getLogger(LoggerConstants::OFFER_EXPORT)->error('Offer export failed when doing update call to EffectConnect.', [
                'process' => LoggerConstants::OFFER_EXPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::OFFER_EXPORT, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Product Update Call - ' . $e->getMessage());
        }

        $this->stopLogging(LoggerConstants::OFFER_EXPORT, $connection);
    }

    /**
     * @param ConnectionResource $connection
     * @return string
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     * @throws InvalidLanguageException
     */
    protected function getCatalog(ConnectionResource $connection): string
    {
        $catalogueExport = new CatalogExport($connection);
        return $catalogueExport->getCatalogXml();
    }

    /**
     * @param string $type
     * @param ConnectionResource $connection
     * @return string
     * @throws InvalidExportTypeException
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     * @throws InvalidLanguageException
     */
    protected function getOfferUpdate(string $type, ConnectionResource $connection): string
    {
        $offerExport = new OfferExport($connection);
        $products = [];

        if ($type === static::QUEUED_EXPORT) {
            $products = $this->getProductsInQueue();
        } else if ($type === static::FULL_EXPORT) {
            $i = 1;
            do { // On full export, use pagination to get product ids.
                $products = array_merge($this->getAllProductIds($i), $products);
                $i++;

            } while($this->totalPages === null || $i <= $this->totalPages);
        } else {
            throw new InvalidExportTypeException('Offer export type is neither queued, nor full.');
        }

        return $offerExport->getOfferUpdateXml($this->getProductObjects($products), $connection->getConnectionId());
    }

    /**
     * Gets the WC_Product objects from their product ids.
     * @param $productIds
     * @return WC_Product[] array
     */
    protected function getProductObjects($productIds): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ec_offer_update_queue';

        $products = [];
        foreach ($productIds as $id) {
            $product = wc_get_product($id);
            $wpdb->delete($table_name, ['product_id' => $id]);
            $products[] = $product;

        }
        return $products;
    }

    /**
     * Returns an array of products ids from the offer update queue.
     * @return array
     */
    private function getProductsInQueue(): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ec_offer_update_queue';

        $config = ConfigContainer::getInstance();
        $limit = $config->getOfferExportQueueSizeValue();

        $resultObjects = $wpdb->get_results(
            "SELECT product_id FROM $table_name ORDER BY offer_id DESC LIMIT $limit"
        );

        $ids = [];

        foreach ($resultObjects as $val) {
            $ids[] = $val->product_id;
        }

        return $ids;
    }


    /**
     * Gets all product ids using pagination.
     * @param $page
     * @return array
     */
    protected function getAllProductIds($page): array
    {
        $productsPerPage = apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());

        $args = array(
            'limit' => $productsPerPage,
            'page' => $page,
            'paginate' => true,
            'status' => 'publish',
            'type' => ['simple', 'variable'],
            'return' => 'ids'
        );

        $ids = wc_get_products($args);
        $this->totalPages = $ids->max_num_pages;
        $idArray = $ids->products;

        return is_array($idArray) ? $idArray : [];
    }

    /**
     * @param string $loggerType
     * @param ConnectionResource $connection
     * @return ProductsCall
     * @throws CatalogExportFailedException
     */
    protected function initProductsCall(string $loggerType, ConnectionResource $connection): ProductsCall
    {
        try {
            $productsCall = $this->getCoreForConnection($connection)->ProductsCall();
        } catch (InitSdkException $e) {
            LoggerContainer::getLogger($loggerType)->error('Export failed when initializing SDK.', [
                'process' => $loggerType,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging($loggerType, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Initialize SDK By Connection - ' . $e->getMessage());
        }
        return $productsCall;
    }

    /**
     * @param string $fileLocation
     * @param string $loggerType
     * @param ConnectionResource $connection
     * @return CURLFile
     * @throws CatalogExportFailedException
     */
    protected function initCurlFile(string $fileLocation, string $loggerType, ConnectionResource $connection): CURLFile
    {
        try {
            $curlFile = new CURLFile($fileLocation);
        } catch (Exception $e) {
            LoggerContainer::getLogger($loggerType)->error('Export failed when initialize CURL file.', [
                'process' => $loggerType,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging($loggerType, $connection);
            throw new CatalogExportFailedException($connection->getConnectionId(), 'Initialize CURL File - ' . $e->getMessage());
        }
        return $curlFile;
    }
}