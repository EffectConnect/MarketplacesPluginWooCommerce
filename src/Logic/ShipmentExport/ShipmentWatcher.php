<?php

namespace EffectConnect\Marketplaces\Logic\ShipmentExport;

use EffectConnect\Marketplaces\DB\ConnectionRepository;
use EffectConnect\Marketplaces\DB\ShippingExportQueueRepository;
use EffectConnect\Marketplaces\Helper\MyParcelHelper;
use EffectConnect\Marketplaces\Helper\TrackingCodeFromOrderCommentHelper;

class ShipmentWatcher
{
    /**
     * @var ShippingExportQueueRepository
     */
    protected $shippingExportQueueRepository;

    /**
     * @var ConnectionRepository
     */
    protected $connectionRepository;

    public function __construct()
    {
        $this->shippingExportQueueRepository = ShippingExportQueueRepository::getInstance();
        $this->connectionRepository = ConnectionRepository::getInstance();
        add_action('woocommerce_order_edit_status', [$this, 'afterOrderStatusUpdate'], 10, 2);

        if (MyParcelHelper::myParcelPluginActivated()) {
            add_action('added_post_meta', [$this, 'afterChangedPostMeta'], 10, 4);
            add_action('updated_post_meta', [$this, 'afterChangedPostMeta'], 10, 4);
        }
    }

    /**
     * @param $orderId
     * @param $newStatus
     * @return void
     */
    public function afterOrderStatusUpdate($orderId, $newStatus)
    {
        // Add 'wc-' to order status (WC both uses order states with and without the prefix).
        $newStatus = 'wc-' === substr($newStatus, 0, 3) ? $newStatus : 'wc-' . $newStatus;

        // Check if EC imported the order.
        $shipmentExportQueueResource = $this->shippingExportQueueRepository->getByOrderId(intval($orderId));
        if ($shipmentExportQueueResource->getShippingExportQueueId() > 0) {
            $connectionId = $shipmentExportQueueResource->getConnectionId();
            $connection   = $this->connectionRepository->get($connectionId);
            $whenToUpdate = $connection->getShipmentExportWhen();

            // Check if new order state corresponds with the connection setting.
            if ($whenToUpdate === $newStatus) {
                $shipmentExportQueueResource->setIsShipped(1);

                // Read tracking code from order comment
                $trackingCode = TrackingCodeFromOrderCommentHelper::getOrderTrackingCode($orderId, $connection->getShipmentExportTrackingCodes());
                if ($trackingCode) {
                    $shipmentExportQueueResource->setTrackingNumber($trackingCode);
                }

                $this->shippingExportQueueRepository->update($shipmentExportQueueResource);
            }
        }
    }

    /**
     * @param $metaId
     * @param $orderId
     * @param $metaKey
     * @param $metaValue
     * @return void
     */
    public function afterChangedPostMeta($metaId, $orderId, $metaKey, $metaValue)
    {
        if (MyParcelHelper::isMyParcelMetaKey($metaKey)) {
            // Check if EC imported the order.
            $shipmentExportQueueResource = $this->shippingExportQueueRepository->getByOrderId(intval($orderId));
            if ($shipmentExportQueueResource->getShippingExportQueueId() > 0) {
                $connectionId = $shipmentExportQueueResource->getConnectionId();
                $connection   = $this->connectionRepository->get($connectionId);
                $whenToUpdate = $connection->getShipmentExportWhen();

                // Check if new order state corresponds with the connection setting.
                if ($whenToUpdate === MyParcelHelper::SHIPMENT_EXPORT_OPTION_TNT) {
                    // Extract TNT number from meta data
                    $myParcelData = json_decode($metaValue, true);
                    if (is_array($myParcelData)) {
                        // TODO: multiple TNT codes not supported yet, just take first one for now.
                        $shipmentData = current($myParcelData);
                        if (is_array($shipmentData) && isset($shipmentData['track_trace']) && !empty($shipmentData['track_trace'])) {
                            $shipmentExportQueueResource->setIsShipped(1);
                            $shipmentExportQueueResource->setTrackingNumber(strval($shipmentData['track_trace']));
                            $this->shippingExportQueueRepository->update($shipmentExportQueueResource);
                        }
                    }
                }
            }
        }
    }
}