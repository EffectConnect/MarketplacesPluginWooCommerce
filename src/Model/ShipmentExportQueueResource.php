<?php

namespace EffectConnect\Marketplaces\Model;

use DateTime;
use Exception;

class ShipmentExportQueueResource
{
    /**
     * @var int
     */
    protected $shippingExportQueueId;

    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var int
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $ecMarketplacesIdentificationNumber;

    /**
     * @var array
     */
    protected $ecMarketplacesOrderLineIds;

    /**
     * @var bool
     */
    protected $isShipped;

    /**
     * @var string|null
     */
    protected $carrierName;

    /**
     * @var string|null
     */
    protected $trackingNumber;

    /**
     * @var DateTime|null
     */
    protected $orderImportedAt;

    /**
     * @var DateTime|null
     */
    protected $shippedExportedAt;

    /**
     * @var DateTime|null
     */
    protected $trackingExportedAt;

    public function __construct(array $data = [])
    {
        $this->shippingExportQueueId              = intval($data['shipping_export_queue_id'] ?? 0);
        $this->orderId                            = intval($data['order_id'] ?? 0);
        $this->connectionId                       = intval($data['connection_id'] ?? 0);
        $this->ecMarketplacesIdentificationNumber = strval($data['ec_marketplaces_identification_number'] ?? '');
        $this->ecMarketplacesOrderLineIds         = (array)json_decode($data['ec_marketplaces_order_line_ids'] ?? '');
        $this->isShipped                          = intval($data['is_shipped'] ?? 0);
        $this->carrierName                        = isset($data['carrier_name']) ? strval($data['carrier_name']) : null;
        $this->trackingNumber                     = isset($data['tracking_number']) ? strval($data['tracking_number']) : null;
        try {
            $this->orderImportedAt = isset($data['order_imported_at']) ? new DateTime($data['order_imported_at']) : null;
        } catch (Exception $e) {
            $this->orderImportedAt = null;
        }
        try {
            $this->shippedExportedAt = isset($data['shipped_exported_at']) ? new DateTime($data['shipped_exported_at']) : null;
        } catch (Exception $e) {
            $this->shippedExportedAt = null;
        }
        try {
            $this->trackingExportedAt = isset($data['tracking_exported_at']) ? new DateTime($data['tracking_exported_at']) : null;
        } catch (Exception $e) {
            $this->trackingExportedAt = null;
        }
    }

    /**
     * @return int
     */
    public function getShippingExportQueueId(): int
    {
        return $this->shippingExportQueueId;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getConnectionId(): int
    {
        return $this->connectionId;
    }

    /**
     * @return string
     */
    public function getEcMarketplacesIdentificationNumber(): string
    {
        return $this->ecMarketplacesIdentificationNumber;
    }

    /**
     * @return array
     */
    public function getEcMarketplacesOrderLineIds(): array
    {
        return $this->ecMarketplacesOrderLineIds;
    }

    /**
     * @return string|null
     */
    public function getCarrierName(): ?string
    {
        return $this->carrierName;
    }

    /**
     * @return string|null
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function setOrderId(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param int $connectionId
     * @return void
     */
    public function setConnectionId(int $connectionId)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * @param string $ecMarketplacesIdentificationNumber
     * @return void
     */
    public function setEcMarketplacesIdentificationNumber(string $ecMarketplacesIdentificationNumber)
    {
        $this->ecMarketplacesIdentificationNumber = $ecMarketplacesIdentificationNumber;
    }

    /**
     * @param array $ecMarketplacesOrderLineIds
     * @return void
     */
    public function setEcMarketplacesOrderLineIds(array $ecMarketplacesOrderLineIds)
    {
        $this->ecMarketplacesOrderLineIds = $ecMarketplacesOrderLineIds;
    }

    /**
     * @param int $isShipped
     * @return void
     */
    public function setIsShipped(int $isShipped)
    {
        $this->isShipped = $isShipped;
    }

    /**
     * @param string $trackingNumber
     * @return void
     */
    public function setTrackingNumber(string $trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * @param DateTime $orderImportedAt
     * @return void
     */
    public function setOrderImportedAt(DateTime $orderImportedAt)
    {
        $this->orderImportedAt = $orderImportedAt;
    }

    /**
     * @param DateTime $shippedExportedAt
     * @return void
     */
    public function setShippedExportedAt(DateTime $shippedExportedAt)
    {
        $this->shippedExportedAt = $shippedExportedAt;
    }

    /**
     * @param DateTime $trackingExportedAt
     * @return void
     */
    public function setTrackingExportedAt(DateTime $trackingExportedAt)
    {
        $this->trackingExportedAt = $trackingExportedAt;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $fields = [
            'shipping_export_queue_id' => $this->shippingExportQueueId,
            'order_id' => $this->orderId,
            'connection_id' => $this->connectionId,
            'ec_marketplaces_identification_number' => $this->ecMarketplacesIdentificationNumber,
            'ec_marketplaces_order_line_ids' => json_encode($this->ecMarketplacesOrderLineIds),
            'is_shipped' => $this->isShipped,
        ];

        if (!is_null($this->carrierName)) {
            $fields['carrier_name'] = $this->carrierName;
        }

        if (!is_null($this->trackingNumber)) {
            $fields['tracking_number'] = $this->trackingNumber;
        }

        if (!is_null($this->orderImportedAt)) {
            $fields['order_imported_at'] = $this->orderImportedAt->format('Y-m-d H:i:s');
        }

        if (!is_null($this->shippedExportedAt)) {
            $fields['shipped_exported_at'] = $this->shippedExportedAt->format('Y-m-d H:i:s');
        }

        if (!is_null($this->trackingExportedAt)) {
            $fields['tracking_exported_at'] = $this->trackingExportedAt->format('Y-m-d H:i:s');
        }

        return $fields;
    }
}