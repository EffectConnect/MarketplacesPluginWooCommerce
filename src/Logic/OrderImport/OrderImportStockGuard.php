<?php

namespace EffectConnect\Marketplaces\Logic\OrderImport;

use EffectConnect\Marketplaces\DB\ConnectionRepository;
use EffectConnect\Marketplaces\DB\ShippingExportQueueRepository;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use WC_Order;

class OrderImportStockGuard
{
    public function __construct()
    {
        add_filter('woocommerce_can_reduce_order_stock', [$this, 'shouldReduceStock'], 10, 2);
        add_filter('woocommerce_payment_complete_reduce_order_stock', [$this, 'shouldReduceStock'], 10, 2);
    }

    /**
     * Determine whether WooCommerce can reduce stock for an order.
     *
     * @param bool $shouldReduceStock
     * @param mixed $orderOrOrderId
     * @return bool
     */
    public function shouldReduceStock(bool $shouldReduceStock, $orderOrOrderId): bool
    {
        if (!$shouldReduceStock) {
            return false;
        }

        $order = $this->resolveOrder($orderOrOrderId);
        if (!($order instanceof WC_Order)) {
            return true;
        }

        $connection = $this->resolveConnection($order);
        if (!($connection instanceof ConnectionResource) || $connection->getConnectionId() <= 0) {
            return true;
        }

        return $this->shouldReduceStockForImportedOrder($connection, $order);
    }

    /**
     * @param ConnectionResource $connection
     * @param WC_Order $order
     * @return bool
     */
    protected function shouldReduceStockForImportedOrder(ConnectionResource $connection, WC_Order $order): bool
    {
        // Only apply the 'reduce stock' setting for externally fulfilled orders
        if ($order->get_meta('effectconnect_external_fulfillment', true) === 'Yes') {
            return !$connection->getOrderImportSkipReduceStock();
        }

        return true;
    }

    /**
     * @param mixed $orderOrOrderId
     * @return WC_Order|null
     */
    protected function resolveOrder($orderOrOrderId)
    {
        if ($orderOrOrderId instanceof WC_Order) {
            return $orderOrOrderId;
        }

        if (!function_exists('wc_get_order')) {
            return null;
        }

        $order = wc_get_order($orderOrOrderId);

        return $order instanceof WC_Order ? $order : null;
    }

    /**
     * @param WC_Order $order
     * @return ConnectionResource|null
     */
    protected function resolveConnection(WC_Order $order): ?ConnectionResource
    {
        $shipmentExportQueueResource = ShippingExportQueueRepository::getInstance()->getByOrderId($order->get_id());
        $connectionId = $shipmentExportQueueResource->getConnectionId();
        if ($connectionId <= 0) {
            return null;
        }

        $connection = ConnectionRepository::getInstance()->get($connectionId);

        return $connection->getConnectionId() > 0 ? $connection : null;
    }
}
