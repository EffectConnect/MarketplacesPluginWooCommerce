<?php

namespace EffectConnect\Marketplaces\Api;

use DateTime;
use EffectConnect\Marketplaces\DB\ShippingExportQueueRepository;
use EffectConnect\Marketplaces\Enums\ExternalFulfilmentEnum;
use EffectConnect\Marketplaces\Exception\ApiCallFailedException;
use EffectConnect\Marketplaces\Exception\InitSdkException;
use EffectConnect\Marketplaces\Exception\OrderImportFailedException;
use EffectConnect\Marketplaces\Exception\OrdersImportFailedException;
use EffectConnect\Marketplaces\Exception\SdkCoreNotInitializedException;
use EffectConnect\Marketplaces\Exception\ShipmentsExportFailedException;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use EffectConnect\Marketplaces\Logic\OrderImport\OrderBuilder;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\PHPSdk\Core\Exception\InvalidPropertyValueException;
use EffectConnect\PHPSdk\Core\Exception\MissingFilterValueException;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\PHPSdk\Core\Model\Filter\HasStatusFilter;
use EffectConnect\PHPSdk\Core\Model\Filter\HasTagFilter;
use EffectConnect\PHPSdk\Core\Model\Filter\TagFilterValue;
use EffectConnect\PHPSdk\Core\Model\Request\OrderLineUpdate;
use EffectConnect\PHPSdk\Core\Model\Request\OrderUpdate;
use EffectConnect\PHPSdk\Core\Model\Request\OrderUpdateRequest;
use EffectConnect\PHPSdk\Core\Model\Request\OrderList;
use EffectConnect\PHPSdk\Core\Model\Response\Order as EffectConnectOrder;
use Exception;

class OrderHandler extends ApiCallHandler
{
    /**
     * Order import failed tag.
     */
    protected const ORDER_IMPORT_FAILED_TAG = 'order_import_failed';

    /**
     * Order import succeeded tag.
     */
    protected const ORDER_IMPORT_SUCCEEDED_TAG = 'order_import_succeeded';

    /**
     * Order import skipped tag.
     */
    protected const ORDER_IMPORT_SKIPPED_TAG = 'order_import_skipped';

    /**
     * Exclude tag filters.
     */
    protected const EXCLUDE_TAG_FILTERS = [
        self::ORDER_IMPORT_FAILED_TAG,
        self::ORDER_IMPORT_SUCCEEDED_TAG,
        self::ORDER_IMPORT_SKIPPED_TAG
    ];

    /**
     * @param ConnectionResource $connection
     * @throws OrdersImportFailedException
     */
    public function ordersImport(ConnectionResource $connection)
    {
        $this->startLogging(LoggerConstants::ORDER_IMPORT, $connection);

        try {
            $orderListCallType = $this->getCoreForConnection($connection)->OrderListCall();
        } catch (InitSdkException $e) {
            LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order import failed when initializing SDK.', [
                'process' => LoggerConstants::ORDER_IMPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::ORDER_IMPORT, $connection);
            throw new OrdersImportFailedException($connection->getConnectionId(), 'Initialize SDK By Connection - ' . $e->getMessage());
        }

        try {
            $orderList = new OrderList();
            $this->addStatusFilters($orderList, $connection);
            $this->addExcludeTagFilters($orderList);
            $apiCall = $orderListCallType->read($orderList);
            $result = $this->callAndResolveResponse($apiCall);
        } catch (Exception $e) {
            LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order import failed when doing read call to EffectConnect.', [
                'process' => LoggerConstants::ORDER_IMPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::ORDER_IMPORT, $connection);
            throw new OrdersImportFailedException($connection->getConnectionId(), 'Order List Read Call - ' . $e->getMessage());
        }

        $ecOrders = $result->getOrders();
        LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->info(count($ecOrders) . ' order(s) to import.', [
            'process'       => LoggerConstants::ORDER_IMPORT,
            'connection_id' => $connection->getConnectionId(),
        ]);

        $orderBuilder = new OrderBuilder($connection);
        /** @var EffectConnectOrder $ecOrder */
        foreach ($ecOrders as $ecOrder)
        {
            try {
                $result = $orderBuilder->importOrder($ecOrder);
                if ($result)
                {
                    LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->info('Order imported successfully.', [
                        'process'               => LoggerConstants::ORDER_IMPORT,
                        'connection_id'         => $connection->getConnectionId(),
                        'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                        'shop_number'           => $orderBuilder->getLastImportedOrderId(),
                    ]);

                    // Update EC order with shop order number and ID.
                    try {
                        $this->orderUpdateCall(
                            $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                            $orderBuilder->getLastImportedOrderId(),
                            $orderBuilder->getLastImportedOrderReference()
                        );
                    } catch (Exception $e) {
                        LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order update call (update identifiers) to EffectConnect failed.', [
                            'process'               => LoggerConstants::ORDER_IMPORT,
                            'connection_id'         => $connection->getConnectionId(),
                            'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                            'shop_number'           => $orderBuilder->getLastImportedOrderId(),
                        ]);
                        continue;
                    }

                    // Send feedback to EffectConnect that we have successfully imported the order.
                    try
                    {
                        $this->orderUpdateAddTagCall(
                            $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                            self::ORDER_IMPORT_SUCCEEDED_TAG
                        );
                    }
                    catch (Exception $e)
                    {
                        LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order update call (add success tag) to EffectConnect failed.', [
                            'process'               => LoggerConstants::ORDER_IMPORT,
                            'connection_id'         => $connection->getConnectionId(),
                            'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                            'shop_number'           => $orderBuilder->getLastImportedOrderId(),
                        ]);
                    }
                }
                else
                {
                    // Order was skipped for a reason (which was already logged by the OrderTransformer).
                    // We'll send a 'skipped' tag for these orders, to make sure we won't import these orders again.
                    try
                    {
                        $this->orderUpdateAddTagCall(
                            $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                            self::ORDER_IMPORT_SKIPPED_TAG
                        );
                    }
                    catch (Exception $e)
                    {
                        LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order update call (add skipped tag) to EffectConnect failed.', [
                            'process'               => LoggerConstants::ORDER_IMPORT,
                            'connection_id'         => $connection->getConnectionId(),
                            'message'               => $e->getMessage(),
                            'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                        ]);
                    }
                }
            } catch (OrderImportFailedException $e) {
                LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Importing order failed.', [
                    'process'               => LoggerConstants::ORDER_IMPORT,
                    'connection_id'         => $connection->getConnectionId(),
                    'message'               => $e->getMessage(),
                    'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                ]);

                // Send feedback to EffectConnect that we have failed to import the order.
                try
                {
                    $this->orderUpdateAddTagCall(
                        $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                        self::ORDER_IMPORT_FAILED_TAG
                    );
                }
                catch (Exception $e)
                {
                    LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order update call (add fail tag) to EffectConnect failed.', [
                        'process'               => LoggerConstants::ORDER_IMPORT,
                        'connection_id'         => $connection->getConnectionId(),
                        'message'               => $e->getMessage(),
                        'effect_connect_number' => $ecOrder->getIdentifiers()->getEffectConnectNumber(),
                    ]);
                }
            }
        }

        $this->stopLogging(LoggerConstants::ORDER_IMPORT, $connection);
    }

    /**
     * @param ConnectionResource $connection
     * @return void
     * @throws ShipmentsExportFailedException
     */
    public function exportShipments(ConnectionResource $connection)
    {
        $this->startLogging(LoggerConstants::SHIPMENT_EXPORT, $connection);

        try {
            $this->getCoreForConnection($connection);
        } catch (InitSdkException $e) {
            LoggerContainer::getLogger(LoggerConstants::SHIPMENT_EXPORT)->error('Shipment export failed when initializing SDK.', [
                'process' => LoggerConstants::SHIPMENT_EXPORT,
                'message' => $e->getMessage(),
            ]);
            $this->stopLogging(LoggerConstants::SHIPMENT_EXPORT, $connection);
            throw new ShipmentsExportFailedException($connection->getConnectionId(), 'Initialize SDK By Connection - ' . $e->getMessage());
        }

        // Get x shipments to export
        $config = ConfigContainer::getInstance();
        $queueSize = $config->getShipmentExportQueueSizeValue();
        $shippingExportQueueRepository = ShippingExportQueueRepository::getInstance();
        $shipmentExportQueueResources = $shippingExportQueueRepository->getListToExport($queueSize);
        LoggerContainer::getLogger(LoggerConstants::SHIPMENT_EXPORT)->info(count($shipmentExportQueueResources) . ' shipment(s) to export.', [
            'process'       => LoggerConstants::SHIPMENT_EXPORT,
            'connection_id' => $connection->getConnectionId(),
        ]);

        foreach ($shipmentExportQueueResources as $shipmentExportQueueResource)
        {
            // Save that we are exporting this tracking code to prevent other cronjobs to process the same item.
            // Bad luck if the export fails, we will not try to do this again. All tracking items we export (either
            // by order state 'shipped' or when a tracking number was added) will get the 'shipped' status in
            // EffectConnect. Adding a tracking number (and carrier) to the order update is optional.
            // Each type of export is done once (set EC order to 'shipped' and add tracking number - we won't do
            // any updates).
            $shipmentExportQueueResource->setShippedExportedAt(new DateTime);
            if ($shipmentExportQueueResource->getCarrierName() !== null || $shipmentExportQueueResource->getTrackingNumber() !== null) {
                $shipmentExportQueueResource->setTrackingExportedAt(new DateTime);
            }
            $shippingExportQueueRepository->update($shipmentExportQueueResource);

            // Update EC order update with shop order number and ID.
            try {
                $this->trackingExportCall(
                    $shipmentExportQueueResource->getEcMarketplacesIdentificationNumber(),
                    $shipmentExportQueueResource->getEcMarketplacesOrderLineIds(),
                    $shipmentExportQueueResource->getCarrierName(),
                    $shipmentExportQueueResource->getTrackingNumber()
                );
            } catch (Exception $e) {
                LoggerContainer::getLogger(LoggerConstants::SHIPMENT_EXPORT)->error('Shipment export failed.', [
                    'process'         => LoggerConstants::SHIPMENT_EXPORT,
                    'connection_id'   => $connection->getConnectionId(),
                    'tracking_export' => $shipmentExportQueueResource->toArray(),
                    'message'         => $e->getMessage(),
                ]);
            }
        }

        $this->stopLogging(LoggerConstants::SHIPMENT_EXPORT, $connection);
    }

    /**
     * Status to fetch orders for depends on connection setting 'order_import_external_fulfilment'.
     * Internal fulfilled orders always have status 'paid'.
     * External fulfilled orders always have status 'completed' AND tag 'external_fulfilment'.
     * To fetch internal as well external orders we should apply the filter 'status paid' or 'status completed and tag external_fulfilment'.
     * The latter is not possible in current API version, so let's only take status into account and filter by tag afterwards in OrderImportTransformer.
     *
     * @param OrderList $orderList
     * @param ConnectionResource $connection
     * @throws InvalidPropertyValueException
     * @throws MissingFilterValueException
     */
    protected function addStatusFilters(OrderList &$orderList, ConnectionResource $connection)
    {
        $hasStatusFilter = new HasStatusFilter();

        switch ($connection->getOrderImportExternalFulfilment())
        {
            case ExternalFulfilmentEnum::EXTERNAL_AND_INTERNAL_ORDERS:
                $statusFilters = [
                    HasStatusFilter::STATUS_PAID,
                    HasStatusFilter::STATUS_COMPLETED,
                ];
                break;
            case ExternalFulfilmentEnum::EXTERNAL_ORDERS:
                $statusFilters = [HasStatusFilter::STATUS_COMPLETED];
                break;
            case ExternalFulfilmentEnum::INTERNAL_ORDERS:
            default:
                $statusFilters = [HasStatusFilter::STATUS_PAID];
                break;
        }

        $hasStatusFilter->setFilterValue($statusFilters);
        $orderList->addFilter($hasStatusFilter);
    }

    /**
     * Add exclude tag filters.
     *
     * @param OrderList $orderList
     * @throws MissingFilterValueException
     * @throws InvalidPropertyValueException
     */
    protected function addExcludeTagFilters(OrderList &$orderList)
    {
        $tagFilterValues = [];
        foreach (static::EXCLUDE_TAG_FILTERS as $excludeTag) {
            $tagFilterValue = new TagFilterValue();
            $tagFilterValue->setTagName($excludeTag);
            $tagFilterValue->setExclude(true);
            $tagFilterValues[] = $tagFilterValue;
        }
        $hasTagFilter = new HasTagFilter();
        $hasTagFilter->setFilterValue($tagFilterValues);
        $orderList->addFilter($hasTagFilter);
    }

    /**
     * @param string $effectConnectNumber
     * @param int $shopOrderId
     * @param string $shopOrderNumber
     * @return void
     * @throws ApiCallFailedException
     * @throws SdkCoreNotInitializedException
     * @throws InvalidPropertyValueException
     */
    protected function orderUpdateCall(string $effectConnectNumber, int $shopOrderId, string $shopOrderNumber)
    {
        $orderCall = $this->getSdkCore()->OrderCall();

        $orderData = new OrderUpdate();
        $orderData
            ->setOrderIdentifierType(OrderUpdate::TYPE_EFFECTCONNECT_NUMBER)
            ->setOrderIdentifier($effectConnectNumber)
            ->setConnectionIdentifier($shopOrderId)
            ->setConnectionNumber($shopOrderNumber);

        $orderUpdate = new OrderUpdateRequest();
        $orderUpdate->addOrderUpdate($orderData);

        $apiCall = $orderCall->update($orderUpdate);
        $this->callAndResolveResponse($apiCall);
    }

    /**
     * @param string $effectConnectNumber
     * @param string $tag
     * @return void
     * @throws ApiCallFailedException
     * @throws InvalidPropertyValueException
     * @throws SdkCoreNotInitializedException
     */
    protected function orderUpdateAddTagCall(string $effectConnectNumber, string $tag)
    {
        $orderCall = $this->getSdkCore()->OrderCall();

        $orderData = new OrderUpdate();
        $orderData
            ->setOrderIdentifierType(OrderUpdate::TYPE_EFFECTCONNECT_NUMBER)
            ->setOrderIdentifier($effectConnectNumber)
            ->addTag($tag);

        $orderUpdate = new OrderUpdateRequest();
        $orderUpdate->addOrderUpdate($orderData);

        $apiCall = $orderCall->update($orderUpdate);
        $this->callAndResolveResponse($apiCall);
    }

    /**
     * @param string $effectConnectNumber
     * @param array $effectConnectLineIds
     * @param string|null $carrier
     * @param string|null $trackingNumber
     * @return void
     * @throws ApiCallFailedException
     * @throws InvalidPropertyValueException
     * @throws SdkCoreNotInitializedException
     */
    protected function trackingExportCall(string $effectConnectNumber, array $effectConnectLineIds, string $carrier = null, string $trackingNumber = null)
    {
        $orderCall = $this->getSdkCore()->OrderCall();

        $orderData = new OrderUpdate();
        $orderData
            ->setOrderIdentifierType(OrderUpdate::TYPE_EFFECTCONNECT_NUMBER)
            ->setOrderIdentifier($effectConnectNumber);

        $orderUpdate = new OrderUpdateRequest();
        $orderUpdate->addOrderUpdate($orderData);

        foreach ($effectConnectLineIds as $effectConnectLineId)
        {
            $orderLineUpdate = (new OrderLineUpdate())
                ->setOrderlineIdentifierType(OrderLineUpdate::TYPE_EFFECTCONNECT_ID)
                ->setOrderlineIdentifier($effectConnectLineId);

            if ($carrier !== null) {
                $orderLineUpdate->setCarrier($carrier);
            }

            if ($trackingNumber !== null) {
                $orderLineUpdate->setTrackingNumber($trackingNumber);
            }

            $orderUpdate->addLineUpdate($orderLineUpdate);
        }

        $apiCall = $orderCall->update($orderUpdate);
        $this->callAndResolveResponse($apiCall);
    }
}