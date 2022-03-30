<?php

namespace EffectConnect\Marketplaces\Logic\ShipmentExport;

use WC_Order;

class ShipmentExport extends ShipmentBuilder
{

    public function getTrackingDetails($orderIds): array {
        $orderObjects = [];
        foreach($orderIds as $id) {
            $orderObjects[] = $this->setShipmentData($id);
        }

        return $orderObjects;
    }

}