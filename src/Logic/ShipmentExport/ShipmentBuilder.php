<?php


namespace EffectConnect\Marketplaces\Logic\ShipmentExport;


use EffectConnect\Marketplaces\DB\OrderRepository;
use EffectConnect\Marketplaces\Helper\MyParcelHelper;
use WC_Order;

class ShipmentBuilder
{
    /**
     * @var WC_Order
     */
    private $order;

    /**
     * Order Repository.
     * @var OrderRepository
     */
    private $orderRepo;

    public function __construct() {
        $this->orderRepo = OrderRepository::getInstance();
    }

    public function setShipmentData($orderId): array {
        $shipmentDetails = [];

        $this->order = wc_get_order($orderId);

        if (MyParcelHelper::myParcelPluginActivated()) {
            $trackingCode = $this->orderRepo->getOrderTrackingCode($orderId);
        }


        $shipmentDetails['order'] = $this->order ?? null;
        $shipmentDetails['tracking'] = $trackingCode ?? null;

        $orderObject = [];
        foreach($shipmentDetails as $key => $val) {
            if (isset($val)) {
                $orderObject[$key] = $val;
            }
        }

        return $orderObject;
    }
}