<?php

namespace EffectConnect\Marketplaces\DB;

use EffectConnect\Marketplaces\Model\ShipmentExportQueueResource;
use wpdb;

class ShippingExportQueueRepository
{
    /**
     * Name of the table this class communicates with.
     * @var string
     */
    protected $tableName;

    protected static $instance;

    /**
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Get singleton instance of ShippingExportQueueRepository.
     * @return ShippingExportQueueRepository
     */
    public static function getInstance(): ShippingExportQueueRepository
    {
        if (!self::$instance) {
            self::$instance = new ShippingExportQueueRepository();
        }

        return self::$instance;
    }

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . 'ec_shipment_export_queue';
    }

    /**
     * @param int $orderId
     * @return ShipmentExportQueueResource
     */
    public function getByOrderId(int $orderId): ShipmentExportQueueResource
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM `$this->tableName` WHERE `order_id` = %s",
                $orderId), 'ARRAY_A'
        );

        return new ShipmentExportQueueResource((array)$result);
    }

    /**
     * @param string $ecNumber
     * @return ShipmentExportQueueResource
     */
    public function getByEffectConnectIdentificationNumber(string $ecNumber): ShipmentExportQueueResource
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM `$this->tableName` WHERE `ec_marketplaces_identification_number` = %s",
                $ecNumber), 'ARRAY_A'
        );

        return new ShipmentExportQueueResource((array)$result);
    }

    /**
     * @param ShipmentExportQueueResource $shipmentExportQueueResource
     * @return void
     */
    public function upsert(ShipmentExportQueueResource $shipmentExportQueueResource)
    {
        $existingResource = $this->getByOrderId($shipmentExportQueueResource->getOrderId());
        if ($existingResource->getShippingExportQueueId() > 0) {
            $this->update($shipmentExportQueueResource);
        } else {
            $this->insert($shipmentExportQueueResource);
        }
    }

    /**
     * @param ShipmentExportQueueResource $shipmentExportQueueResource
     * @return void
     */
    public function insert(ShipmentExportQueueResource $shipmentExportQueueResource)
    {
        $this->wpdb->insert($this->tableName, $shipmentExportQueueResource->toArray());
    }

    /**
     * @param ShipmentExportQueueResource $shipmentExportQueueResource
     * @return void
     */
    public function update(ShipmentExportQueueResource $shipmentExportQueueResource)
    {
        $where = ['shipping_export_queue_id' => $shipmentExportQueueResource->getShippingExportQueueId()];
        $this->wpdb->update($this->tableName, $shipmentExportQueueResource->toArray(), $where);
    }

    /**
     * @param int $queueSize
     * @return array
     */
    public function getListToExport(int $queueSize = 10): array
    {
        $query = "SELECT * FROM `$this->tableName` WHERE `is_shipped` = 1 AND `shipped_exported_at` IS NULL LIMIT $queueSize";
        $results = $this->wpdb->get_results($query, 'ARRAY_A');

        $shipmentExportQueueResources = [];
        foreach ($results as $result) {
            $shipmentExportQueueResources[] = new ShipmentExportQueueResource((array)$result);
        }

        return $shipmentExportQueueResources;
    }
}