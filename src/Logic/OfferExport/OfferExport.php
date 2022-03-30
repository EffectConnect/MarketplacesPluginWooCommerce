<?php

namespace EffectConnect\Marketplaces\Logic\OfferExport;

use DOMException;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Exception\FileCreationFailedException;
use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Exception\NoProductsToExportException;
use EffectConnect\Marketplaces\Helper\XmlFileHelper;
use EffectConnect\Marketplaces\Helper\XmlHelper;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use WC_Product;

class OfferExport extends OfferBuilder
{
    const CONTENT_TYPE = 'offer_update';
    /**
     * Helper class for generating xml files.
     * @var bool|XmlHelper
     */
    private $xmlHelper;

    public function __construct(ConnectionResource $connection)
    {
        parent::__construct(LoggerConstants::OFFER_EXPORT, $connection);
    }

    /**
     * @param WC_Product[] $products
     * @param int $connectionId
     * @return string
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     */
    protected function generateXmlFromArray(array $products, int $connectionId): string
    {
        $model = $this->getUpdateModel($products, $connectionId);

        if (count($model) === 0) {
            throw new NoProductsToExportException();
        }

        $fileHelper = new XmlFileHelper();
        $location = $fileHelper::generateFile(static::CONTENT_TYPE, $connectionId);

        try {
            $this->xmlHelper = XmlHelper::startTransaction($location, FilePathConstants::XML_ROOT_ELEMENT_PRODUCTS);
            $this->appendToXml($model);
        } catch (DOMException $e) {
            throw new FileCreationFailedException($location, $e->getMessage());
        }

        $this->xmlHelper->endTransaction();

        $path = realpath($location);
        if ($path === false) {
            throw new FileCreationFailedException($location, 'Empty file path was returned.');
        }

        return $path;
    }

    /**
     * Append products from a page to the xml using the XmlHelper class.
     * @param $updateModel
     * @throws DOMException
     */
    protected function appendToXml($updateModel)
    {
        $totalProductCount = 0;
        do {
            foreach ($updateModel as $product) {
                if (!$product) {
                    continue;
                } else if ($this->xmlHelper->append($product, 'product')) {
                    $totalProductCount++;
                }
            }
        } while (count($updateModel) > $totalProductCount);
    }

    /**
     * Generates and returns location of xml file.
     * @param WC_Product[] $productsToUpdate
     * @param int $connectionId
     * @return string
     * @throws FileCreationFailedException
     * @throws NoProductsToExportException
     */
    public function getOfferUpdateXml(array $productsToUpdate, int $connectionId): string
    {
        return $this->generateXmlFromArray($productsToUpdate, $connectionId);
    }

}