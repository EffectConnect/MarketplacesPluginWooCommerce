<?php

namespace EffectConnect\Marketplaces\Logic\OrderImport;

use DateTime;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\DB\OrderRepository;
use EffectConnect\Marketplaces\DB\ProductRepository;
use EffectConnect\Marketplaces\DB\ShippingExportQueueRepository;
use EffectConnect\Marketplaces\Enums\ExternalFulfilmentEnum;
use EffectConnect\Marketplaces\Exception\OrderImportFailedException;
use EffectConnect\Marketplaces\Helper\MyParcelHelper;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\Marketplaces\Model\ShipmentExportQueueResource;
use EffectConnect\PHPSdk\Core\Model\Filter\HasStatusFilter;
use EffectConnect\PHPSdk\Core\Model\Response\Order as EffectConnectOrder;
use WC_Data_Exception;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Product;

class OrderBuilder
{
    const EXTERNALLY_FULFILLED_TAG = 'external_fulfilment';

    /**
     * @var WC_Order
     */
    protected $order;

    /**
     * @var ProductRepository
     */
    protected $productRepo;

    /**
     * @var OrderRepository
     */
    protected $orderRepo;

    /**
     * @var ShippingExportQueueRepository
     */
    protected $shippingExportQueueRepo;

    /**
     * @var ConnectionResource
     */
    protected $connection;

    /**
     * @var int
     */
    protected $lastImportedOrderId = 0;

    /**
     * @var string
     */
    protected $lastImportedOrderReference = '';

    public function __construct(ConnectionResource $connection)
    {
        $this->connection              = $connection;
        $this->productRepo             = ProductRepository::getInstance();
        $this->orderRepo               = OrderRepository::getInstance();
        $this->shippingExportQueueRepo = ShippingExportQueueRepository::getInstance();
    }

    /**
     * Creates the order object and sets the properties based on the imported EffectConnectOrder object.
     * Returns a boolean value to indicate success.
     *
     * @param EffectConnectOrder $ecOrder
     * @return bool
     * @throws OrderImportFailedException
     */
    public function importOrder(EffectConnectOrder $ecOrder): bool
    {
        // Check if we need to import the order.
        if ($this->skipOrderImport($ecOrder)) {
            return false;
        }

        // Uses the WC api to create an order object which we can populate using the ecOrder object.
        $this->order = wc_create_order();
        if (!($this->order instanceof WC_Order)) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Error while creating error.');
        }

        // Save that we are currently importing $ecOrder to prevent duplicate imports when this script is called twice at the same time.
        $this->setOrderIdentifiers($ecOrder);

        // Set all order attributes.
        try {
            $this->setOrderAddresses($ecOrder);
            $this->setOrderPostMeta($ecOrder);
            $this->setProducts($ecOrder);
            $this->setTotals($ecOrder);
        } catch (OrderImportFailedException $e) {
            // Rollback order creation in case of errors.
            $this->setOrderIdentifierError();
            if (!$this->order->delete()) {
                LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->error('Order rollback failed.', [
                    'process'    => LoggerConstants::ORDER_IMPORT,
                    'connection' => $this->connection->getConnectionId(),
                ]);
            }
            throw new OrderImportFailedException($this->connection->getConnectionId(), $e->getMessage());
        }

        // No rollback for processes that send emails.
        $this->setOrderPayment();
        $this->setStatus($ecOrder);

        $this->lastImportedOrderId        = $this->order->get_id();
        $this->lastImportedOrderReference = $this->order->get_order_key();
        $this->setOrderIdentifierSuccess();

        // Backwards compatibility with old plugin.
        apply_filters('effectconnect_created_order', $this->order);

        return true;
    }

    /**
     * Creates the necessary PostMeta in the WordPress DB for the imported order.
     * In case the ACF plugin is enabled, we have to make sure the fields are explicitly created in order to make the
     * 'update_post_meta' requests work properly (please refer to the registerAcfFields method).
     *
     * @throws OrderImportFailedException
     */
    protected function setOrderPostMeta(EffectConnectOrder $ecOrder)
    {
        $channelType = $ecOrder->getChannelInfo()->getType();
        $channelSubtype = $ecOrder->getChannelInfo()->getSubtype();
        if (!empty($channelSubtype)) {
            $channelType .= ' (' . $channelSubtype . ')';
        }
        if (
            update_post_meta($this->order->get_id(), 'order_source', 'effectconnect') === false
            || update_post_meta($this->order->get_id(), 'effectconnect_order_number', $ecOrder->getIdentifiers()->getEffectConnectNumber()) === false
            || update_post_meta($this->order->get_id(), 'effectconnect_order_number_channel', $ecOrder->getIdentifiers()->getChannelNumber()) === false
            || update_post_meta($this->order->get_id(), 'effectconnect_channel_name', $ecOrder->getChannelInfo()->getTitle()) === false
            || update_post_meta($this->order->get_id(), 'effectconnect_channel_type', $channelType) === false
            || update_post_meta($this->order->get_id(), 'effectconnect_external_fulfillment', $this->checkIfExternallyFulfilled($ecOrder) ? 'Yes' : 'No') === false
        ) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Post meta update failed.');
        }
    }

    /**
     * Returns true if order contains externally fulfilled tag.
     * @param EffectConnectOrder $order
     * @return bool
     */
    protected function checkIfExternallyFulfilled(EffectConnectOrder $order): bool
    {
        foreach ($order->getTags() as $tagObject) {
            if ($tagObject->getTag() === self::EXTERNALLY_FULFILLED_TAG) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the order addresses from the ecOrder and assigns them to the WC_Order object.
     * Note: shipping email and salutation fields do not exist in WC.
     *
     * @param EffectConnectOrder $ecOrder
     * @throws OrderImportFailedException
     */
    protected function setOrderAddresses(EffectConnectOrder $ecOrder)
    {
        try {
            $billingAddress = implode(
                ' ',
                array_filter(
                    [
                        $ecOrder->getBillingAddress()->getStreet(),
                        $ecOrder->getBillingAddress()->getHouseNumber(),
                        $ecOrder->getBillingAddress()->getHouseNumberExtension()
                    ]
                )
            );
            $this->order->set_billing_first_name($ecOrder->getBillingAddress()->getFirstName());
            $this->order->set_billing_last_name($ecOrder->getBillingAddress()->getLastName());
            $this->order->set_billing_address_1($billingAddress);
            $this->order->set_billing_address_2($ecOrder->getBillingAddress()->getAddressNote());
            $this->order->set_billing_postcode($ecOrder->getBillingAddress()->getZipCode());
            $this->order->set_billing_city($ecOrder->getBillingAddress()->getCity());
            $this->order->set_billing_state($ecOrder->getBillingAddress()->getState());
            $this->order->set_billing_country($ecOrder->getBillingAddress()->getCountry());
            $this->order->set_billing_company($ecOrder->getBillingAddress()->getCompany());
            $this->order->set_billing_email($ecOrder->getBillingAddress()->getEmail());
            $this->order->set_billing_phone($ecOrder->getBillingAddress()->getPhone());
        } catch (WC_Data_Exception $e) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Error while setting billing address [' . $e->getMessage(). '].');
        }

        try {
            $shippingAddress = implode(
                ' ',
                array_filter(
                    [
                        $ecOrder->getShippingAddress()->getStreet(),
                        $ecOrder->getShippingAddress()->getHouseNumber(),
                        $ecOrder->getShippingAddress()->getHouseNumberExtension()
                    ]
                )
            );
            $this->order->set_shipping_first_name($ecOrder->getShippingAddress()->getFirstName());
            $this->order->set_shipping_last_name($ecOrder->getShippingAddress()->getLastName());
            $this->order->set_shipping_address_1($shippingAddress);
            $this->order->set_shipping_address_2($ecOrder->getShippingAddress()->getAddressNote());
            $this->order->set_shipping_postcode($ecOrder->getShippingAddress()->getZipCode());
            $this->order->set_shipping_city($ecOrder->getShippingAddress()->getCity());
            $this->order->set_shipping_state($ecOrder->getShippingAddress()->getState());
            $this->order->set_shipping_country($ecOrder->getShippingAddress()->getCountry());
            $this->order->set_shipping_company($ecOrder->getShippingAddress()->getCompany());
            $this->order->set_shipping_phone($ecOrder->getShippingAddress()->getPhone());
        } catch (WC_Data_Exception $e) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Error while setting shipping address [' . $e->getMessage(). '].');
        }
    }

    /**
     * Gets the ordered products from the ecOrder and assigns them to the WC_Order object.
     *
     * @param EffectConnectOrder $ecOrder
     * @throws OrderImportFailedException
     */
    protected function setProducts(EffectConnectOrder $ecOrder)
    {
        $lineProducts = $ecOrder->getLines();
        if (count($lineProducts) === 0) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'No products in order.');
        }

        $ordered = [];
        foreach ($lineProducts as $product)
        {
            $productIdentifier = intval($product->getProduct()->getIdentifier());
            $productOption = $this->productRepo->getProductOptionById($productIdentifier);

            if ($productOption === false) {
                throw new OrderImportFailedException($this->connection->getConnectionId(), 'Product [' . $productIdentifier . '] not found.');
            }

            $attributes = (array)json_decode($productOption->attribute_data, true);

            // Get clone of parent product or parent variation, we then need to manually add the product option data from the product option table.
            $id              = $productOption->variation_id > 0 ? $productOption->variation_id : $productOption->product_id;
            $wcProductOption = wc_get_product($id);
            if (!($wcProductOption instanceof WC_Product)) {
                throw new OrderImportFailedException($this->connection->getConnectionId(), 'Product [' . $id . '] could not be loaded.');
            }

            //$orderedProductOption = clone wc_get_product($id); // TODO: do we need clone?
            $wcAttributes = $wcProductOption->get_attributes();
            foreach ($attributes as $key => $val) {
                if (is_object($wcAttributes[$key])) {
                    $wcAttributes[$key]->set_options($val);
                } else {
                    $wcAttributes[$key] = $val;
                }
            }

            if (count($wcAttributes) > 0) {
                $wcProductOption->set_attributes($wcAttributes);
            }

            $wcProductOption->set_price($product->getLineAmount());
            //$orderedProductOption->set_name($productOption->product_name); // TODO: why
            //$orderedProductOption->set_sku($this->generateSku($orderedProductOption)); // TODO: why

            $ordered[$product->getProduct()->getIdentifier()][] = $wcProductOption;
        }

        foreach ($ordered as $productToAdd) {
            $this->order->add_product(current($productToAdd), count($productToAdd));
        }
    }

    /**
     * Gets payment method for order.
     *
     * @return void
     * @throws OrderImportFailedException
     */
    protected function setOrderPayment()
    {
        $gateways = ConnectionResource::getPaymentOptions();
        $paymentMethodId = $this->connection->getOrderImportIdPaymentModule();
        if (empty($paymentMethodId) || !isset($gateways[$paymentMethodId])) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Payment method [' . $paymentMethodId . '] not found.');
        }

        try {
            $this->order->set_payment_method($gateways[$paymentMethodId]);
        } catch (WC_Data_Exception $e) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Error while setting payment method [' . $paymentMethodId . '].');
        }

        // Make sure this status change will send an email only when set in the settings.
        $this->setOrderEmail($this->order->needs_processing() ? 'processing' : 'completed');
        $this->order->payment_complete();
    }

    /**
     * Adds all product-prices, product-fees and order-fees together to get the total order price.
     *
     * @param EffectConnectOrder $ecOrder
     * @throws OrderImportFailedException
     */
    protected function setTotals(EffectConnectOrder $ecOrder)
    {
        $shippingTotal = 0;
        $total = 0;

        foreach ($ecOrder->getLines() as $lineProduct) { // get product fees.
            $price = floatval($lineProduct->getLineAmount());
            foreach ($lineProduct->getFees() as $fee) {
                if ($fee->getType() === 'commission') {
                    continue;
                } else if ($fee->getType() === 'shipping') {
                    $shippingTotal += floatval($fee->getAmount());
                } else {
                    $total += floatval($fee->getAmount());
                }
            }
            $total += $price;
        }

        foreach ($ecOrder->getFees() as $fee) { // get order fees.
            if ($fee->getType() === 'commission') {
                continue;
            } else if ($fee->getType() === 'shipping') {
                $shippingTotal += floatval($fee->getAmount());
            } else {
                $total += floatval($fee->getAmount());
            }
        }

        $total += $shippingTotal;

        $shipmentMethodId = $this->connection->getOrderImportIdCarrier();
        $shipmentMethods = ConnectionResource::getCarrierOptions();
        $shipmentMethodName = $shipmentMethods[$shipmentMethodId] ?? '';
        if (empty($shipmentMethodName)) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Shipment method [' . $shipmentMethodId . '] not found.');
        }

        try {
            $order_shipping_item = new WC_Order_Item_Shipping();
            $shippingInfo = __('EffectConnect Marketplaces channel', 'effectconnect_marketplaces') . ' ' .
                '`' . $ecOrder->getChannelInfo()->getTitle() . '` (' . $ecOrder->getIdentifiers()->getChannelNumber() . ')';
            $order_shipping_item->set_name($shippingInfo);
            $order_shipping_item->set_total($shippingTotal);
            $this->order->add_item(
                $order_shipping_item
            );
        } catch (WC_Data_Exception $e) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Setting shipment method [' . $shipmentMethodName . '] failed with message [' . $e->getMessage() . '].');
        }

        try {
            $this->order->set_shipping_total($shippingTotal);
            $this->order->set_total($total);
        } catch (WC_Data_Exception $e) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Settings totals [' . $shippingTotal . ', ' . $total . '] failed with message [' . $e->getMessage() . '].');
        }

        // Make sure WC sets the correct VAT
        $this->order->calculate_totals();
    }

    /**
     * @param EffectConnectOrder $ecOrder
     * @return bool
     */
    protected function skipOrderImport(EffectConnectOrder $ecOrder): bool
    {
        // Check if order was already imported (or is currently importing - no success or failed flag) - identify by EC order number.
        $effectConnectNumber = $ecOrder->getIdentifiers()->getEffectConnectNumber();
        if ($this->orderRepo->checkIfOrderIsImportedOrImporting($effectConnectNumber)) {
            LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->info('Order ' . $effectConnectNumber . ' skipped because it was already imported.', [
                'process'    => LoggerConstants::ORDER_IMPORT,
                'connection' => $this->connection->getConnectionId(),
            ]);
            return true;
        }

        // Status to fetch orders for depends on connection setting 'order_import_external_fulfilment'.
        // Internal fulfilled orders always have status 'paid'.
        // External fulfilled orders always have status 'completed' AND tag 'external_fulfilment'.
        // To fetch internal as well external orders we should apply the filter 'status paid' or 'status completed and tag external_fulfilment'.
        // When fetching orders we only look at status, so we have to filter the combination of status and tag now.
        $effectConnectOrderIsExternalFulfilled = $this->checkIfExternallyFulfilled($ecOrder);
        $skipOrderImport                       = false;
        switch ($this->connection->getOrderImportExternalFulfilment())
        {
            case ExternalFulfilmentEnum::EXTERNAL_AND_INTERNAL_ORDERS:
                if ($ecOrder->getStatus() == HasStatusFilter::STATUS_COMPLETED && !$effectConnectOrderIsExternalFulfilled) {
                    $skipOrderImport = true;
                }
                break;
            case ExternalFulfilmentEnum::EXTERNAL_ORDERS:
                if ($ecOrder->getStatus() != HasStatusFilter::STATUS_COMPLETED || !$effectConnectOrderIsExternalFulfilled) {
                    $skipOrderImport = true;
                }
                break;
            case ExternalFulfilmentEnum::INTERNAL_ORDERS:
            default:
                if ($ecOrder->getStatus() != HasStatusFilter::STATUS_PAID || $effectConnectOrderIsExternalFulfilled) {
                    $skipOrderImport = true;
                }
                break;
        }

        if ($skipOrderImport) {
            LoggerContainer::getLogger(LoggerConstants::ORDER_IMPORT)->info('Order ' . $effectConnectNumber . ' skipped because fulfilment status (is external fulfilled: ' . intval($effectConnectOrderIsExternalFulfilled) . ') does not match connection setting (' . $this->connection->getOrderImportExternalFulfilment() . ').', [
                'process'    => LoggerConstants::ORDER_IMPORT,
                'connection' => $this->connection->getConnectionId(),
            ]);
            return true;
        }

        // No reason found for skipping the order import.
        return false;
    }

    /**
     * TODO: use separate state (completed?) for externally fulfilled orders?
     *
     * @param EffectConnectOrder $ecOrder
     * @throws OrderImportFailedException
     */
    protected function setStatus(EffectConnectOrder $ecOrder)
    {
        $state    = $this->connection->getOrderImportOrderStatus();
        $wcStates = ConnectionResource::getOrderStatusOptions();
        if (empty($state) || !isset($wcStates[$state])) {
            throw new OrderImportFailedException($this->connection->getConnectionId(), 'Order status [' . $state . '] not found.');
        }

        // Make sure this status change will send an email only when set in the settings.
        $this->setOrderEmail($this->connection->getOrderImportOrderStatus(true));
        $this->order->update_status($state, $ecOrder->getStatus());
    }

    /**
     * Added the imported order info to plugin database table (at the beginning of the import).
     *
     * @param EffectConnectOrder $ecOrder
     * @return void
     */
    protected function setOrderIdentifiers(EffectConnectOrder $ecOrder)
    {
        $orderLineIds = [];
        foreach ($ecOrder->getLines() as $orderLine) {
            $orderLineIds[] = $orderLine->getIdentifiers()->getEffectConnectId();
        }

        $shipmentExportQueueResource = new ShipmentExportQueueResource();
        $shipmentExportQueueResource->setOrderId($this->order->get_id());
        $shipmentExportQueueResource->setConnectionId($this->connection->getConnectionId());
        $shipmentExportQueueResource->setEcMarketplacesIdentificationNumber($ecOrder->getIdentifiers()->getEffectConnectNumber());
        $shipmentExportQueueResource->setEcMarketplacesOrderLineIds($orderLineIds);
        $shipmentExportQueueResource->setOrderImportedAt(new DateTime);
        $this->shippingExportQueueRepo->upsert($shipmentExportQueueResource);
    }

    /**
     * Set that the order import has succeeded.
     *
     * @return void
     */
    protected function setOrderIdentifierSuccess()
    {
        $shipmentExportQueueResource = $this->shippingExportQueueRepo->getByOrderId($this->order->get_id());
        $shipmentExportQueueResource->setImportSuccess(1);
        $this->shippingExportQueueRepo->upsert($shipmentExportQueueResource);
    }

    /**
     * Set that the order import has failed.
     *
     * @return void
     */
    protected function setOrderIdentifierError()
    {
        $shipmentExportQueueResource = $this->shippingExportQueueRepo->getByOrderId($this->order->get_id());
        $shipmentExportQueueResource->setImportError(1);
        $this->shippingExportQueueRepo->upsert($shipmentExportQueueResource);
    }

    /**
     * @return int
     */
    public function getLastImportedOrderId(): int
    {
        return $this->lastImportedOrderId;
    }

    /**
     * @return string
     */
    public function getLastImportedOrderReference(): string
    {
        return $this->lastImportedOrderReference;
    }

    /**
     * Set whether to send an order email.
     * Note that WC will only send the email if it is enabled in the WC settings also.
     *
     * @return void
     */
    protected function setOrderEmail(string $orderStatus = '')
    {
        $connection = $this->connection;

        // Enable or disable admin mail
        add_filter('woocommerce_email_enabled_new_order', function ($wooCommerceEmailEnabled) use ($connection) {
            return $wooCommerceEmailEnabled && $connection->getOrderImportSendEmails();
        });

        // Enable or disable customer mail
        add_filter('woocommerce_email_enabled_customer_' . $orderStatus . '_order', function ($wooCommerceEmailEnabled) use ($connection) {
            return $wooCommerceEmailEnabled && $connection->getOrderImportSendEmails();
        });
    }
}