<?php

namespace EffectConnect\Marketplaces\Logic\CatalogExport;

use DOMException;
use EffectConnect\Marketplaces\Exception\FileCreationFailedException;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Exception\NoProductsToExportException;
use EffectConnect\Marketplaces\Helper\XmlFileHelper;
use EffectConnect\Marketplaces\Helper\XmlHelper;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use WC_Product;

class CatalogExport extends CatalogBuilder
{
    const CONTENT_TYPE = 'catalog_export';
    const PAGE_SIZE = 50;

    /**
     * Helper class for generating xml files.
     * @var bool|XmlHelper
     */
    private $xmlHelper;

    /**
     * Total amount of pages while using paging to load products.
     * @var
     */
    private $totalPages;

    /**
     * Total amount of products, excluding variations.
     * @var int
     */
    private $totalProducts = 0;

    public function __construct(ConnectionResource $connection) // Inject the productOptions table interface.
    {
        parent::__construct(LoggerConstants::CATALOG_EXPORT, $connection);
    }

    /**
     * @param int $page
     * @return WC_Product[]
     */
    protected function getRawCatalog(int $page): array
    {
        // $ordering = WC()->query->get_catalog_ordering_args();
        $productsPerPage = apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());

        $args = [
            'limit' => $productsPerPage,
            'type' => ['simple', 'variable'],
            'paginate' => true,
            'page' => $page,
        ];

        if ($this->connection->getCatalogExportOnlyActive()) {
            $args['status'] = 'publish';
        }

        $rawCatalog = wc_get_products($args);
        $this->totalPages = $rawCatalog->max_num_pages;

        return $rawCatalog->products;
    }

    /**
     * Utilizes the FileHelper class to create an empty xml file, then uses the XmlHelper class to populate it with the catalogModel array structure.
     *
     * @return string
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     */
    protected function generateXmlFromArray(): string
    {
        $location = XmlFileHelper::generateFile(static::CONTENT_TYPE, $this->connection->getConnectionId());
        try {
            $this->xmlHelper = XmlHelper::startTransaction($location, FilePathConstants::XML_ROOT_ELEMENT_PRODUCTS);
        } catch (DOMException $e) {
            throw new FileCreationFailedException($location, $e->getMessage());
        }

        $page = 1;
        $productsAddedCount = 0;
        do {
            $rawProducts = $this->getRawCatalog($page);
            $products    = $this->buildCatalog($rawProducts);
            $this->appendToXml($products);
            $page++;
            $productsAddedCount+= count($products);
        } while($page <= $this->totalPages);

        $this->xmlHelper->endTransaction();
        $this->productOptionsRepo->cleanDeletedProductsFromDB();

        if ($productsAddedCount === 0) {
            throw new NoProductsToExportException();
        }

        $filePath = realpath($location);
        if ($filePath === false) {
            throw new FileCreationFailedException($location, 'Empty file path was returned.');
        }

        return $filePath;
    }

    /**
     * Append products from a page to the xml using the XmlHelper class.
     * @param $catalogModel
     */
    private function appendToXml($catalogModel)
    {
        $totalProductCount = 0;
        do {
            foreach ($catalogModel as $product) {
                try {
                    if (!$product) continue;
                    else if ($this->xmlHelper->append($product, 'product')) {
                        $totalProductCount++;
                        $this->totalProducts++;
                    }
                } catch (DOMException $e) {
                    LoggerContainer::getLogger(LoggerConstants::CATALOG_EXPORT)->error('Skipping product because it could not be converted to XML.', [
                        'process' => LoggerConstants::CATALOG_EXPORT,
                        'message' => $e->getMessage(),
                        'product' => $product
                    ]);
                }
            }
        } while (count($catalogModel) > $totalProductCount);
    }

    /**
     * Generates and returns location of xml file.
     * @return string
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     */
    public function getCatalogXml(): string
    {
        return $this->generateXmlFromArray();
    }
}