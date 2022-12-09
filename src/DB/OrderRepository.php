<?php

namespace EffectConnect\Marketplaces\DB;

use EffectConnect\Marketplaces\Model\ShipmentExportQueueResource;

class OrderRepository
{
    private static $instance;

    /**
     * Get singleton instance of ProductOptionsRepository.
     * @return OrderRepository
     */
    static function getInstance(): OrderRepository
    {
        if (!self::$instance) {
            self::$instance = new OrderRepository();
        }

        return self::$instance;
    }

    /**
     * Each imported EC order gets an entry in the 'ec_shipment_export_queue' database.
     * This check if used to see if an order should be imported or not.
     * An order should not be imported if:
     * - it is already importing (no success or error flag)
     * - it is already imported (success flag)
     *
     * @param string $ecOrderNumber
     * @return bool
     */
    public function checkIfOrderIsImportedOrImporting(string $ecOrderNumber): bool
    {
        $shippingExportQueueRepository = ShippingExportQueueRepository::getInstance();
        $shippingExportQueueItems = $shippingExportQueueRepository->getListByEffectConnectIdentificationNumber($ecOrderNumber);
        $importedOrImporting = array_filter($shippingExportQueueItems, function ($item) {
            return $item->getImportSuccess() || !$item->getImportError();
        });
        return count($importedOrImporting) > 0;
    }
}