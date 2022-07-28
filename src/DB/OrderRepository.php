<?php

namespace EffectConnect\Marketplaces\DB;

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
     *
     * @param string $ecOrderNumber
     * @return bool
     */
    public function checkIfOrderExists(string $ecOrderNumber): bool
    {
        $shippingExportQueueRepository = ShippingExportQueueRepository::getInstance();
        $shippingExportQueueItem = $shippingExportQueueRepository->getByEffectConnectIdentificationNumber($ecOrderNumber);
        return $shippingExportQueueItem->getShippingExportQueueId() > 0;
    }
}