<?php

namespace EffectConnect\Marketplaces\Model;

use EffectConnect\Marketplaces\Constants\ConfigConstants;
use EffectConnect\Marketplaces\Enums\ExternalFulfilmentEnum;
use EffectConnect\Marketplaces\Helper\LanguageHelper;
use EffectConnect\Marketplaces\Helper\MyParcelHelper;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use EffectConnect\Marketplaces\Helper\WcHelper;

class ConnectionResource implements ConfigConstants
{
    /**
     * @var int
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var int
     */
    protected $isActive;

    /**
     * @var int
     */
    protected $catalogExportWpmlLanguages;

    /**
     * @var int
     */
    protected $catalogExportOnlyActive;

    /**
     * @var int
     */
    protected $catalogExportTaxonomies;

    /**
     * @var int
     */
    protected $catalogExportSpecialPrice;

    /**
     * @var int
     */
    protected $catalogExportEanLeadingZero;

    /**
     * @var int
     */
    protected $catalogExportSkipInvalidEan;

    /**
     * @var string
     */
    protected $catalogExportEanAttribute;

    /**
     * @var string
     */
    protected $catalogExportCostAttribute;

    /**
     * @var string
     */
    protected $catalogExportDeliveryAttribute;

    /**
     * @var string
     */
    protected $catalogExportBrandAttribute;

    /**
     * @var string
     */
    protected $catalogExportTitleAttribute;

    /**
     * @var string
     */
    protected $catalogExportDescriptionAttribute;

    /**
     * @var string
     */
    protected $catalogExportLanguage;

    /**
     * @var int
     */
    protected $offerExportVirtualStockAmount;

    /**
     * @var string
     */
    protected $orderImportOrderStatus;

    /**
     * @var string
     */
    protected $orderImportIdCarrier;

    /**
     * @var string
     */
    protected $orderImportIdPaymentModule;

    /**
     * @var string
     */
    protected $orderImportExternalFulfilment;

    /**
     * @var int
     */
    protected $orderImportSendEmails;

    /**
     * @var string
     */
    protected $shipmentExportWhen;

    /**
     * @var array
     */
    protected $errors = [];

    public function __construct(array $data = [])
    {
        $this->connectionId                        = intval($data['connection_id'] ?? 0);
        $this->connectionName                      = strval($data['connection_name'] ?? '');
        $this->publicKey                           = strval($data['public_key'] ?? '');
        $this->privateKey                          = strval($data['private_key'] ?? '');
        $this->isActive                            = intval($data['is_active'] ?? 1);
        $this->catalogExportWpmlLanguages          = intval($data['catalog_export_wpml_languages'] ?? 1);
        $this->catalogExportOnlyActive             = intval($data['catalog_export_only_active'] ?? 1);
        $this->catalogExportTaxonomies             = intval($data['catalog_export_taxonomies'] ?? 0);
        $this->catalogExportSpecialPrice           = intval($data['catalog_export_special_price'] ?? 1);
        $this->catalogExportEanLeadingZero         = intval($data['catalog_export_ean_leading_zero'] ?? 0);
        $this->catalogExportSkipInvalidEan         = intval($data['catalog_export_skip_invalid_ean'] ?? 0);
        $this->catalogExportTitleAttribute         = strval($data['catalog_export_title_attribute'] ?? WcHelper::WC_DEFAULT_ATTRIBUTE_PREFIX . 'name');
        $this->catalogExportDescriptionAttribute   = strval($data['catalog_export_description_attribute'] ?? WcHelper::WC_DEFAULT_ATTRIBUTE_PREFIX . 'description');
        $this->catalogExportEanAttribute           = strval($data['catalog_export_ean_attribute'] ?? '');
        $this->catalogExportCostAttribute          = strval($data['catalog_export_cost_attribute'] ?? '');
        $this->catalogExportDeliveryAttribute      = strval($data['catalog_export_delivery_attribute'] ?? '');
        $this->catalogExportBrandAttribute         = strval($data['catalog_export_brand_attribute'] ?? '');
        $this->catalogExportLanguage               = strval($data['catalog_export_language'] ?? 'nl');
        $this->offerExportVirtualStockAmount       = intval($data['offer_export_virtual_stock_amount'] ?? 99);
        $this->orderImportOrderStatus              = strval($data['order_import_order_status'] ?? 'wc-processing');
        $this->orderImportIdCarrier                = strval($data['order_import_id_carrier'] ?? 'effectconnect_shipping');
        $this->orderImportIdPaymentModule          = strval($data['order_import_id_payment_module'] ?? 'effectconnect_payment');
        $this->orderImportExternalFulfilment       = strval($data['order_import_external_fulfilment'] ?? 'any');
        $this->orderImportSendEmails               = intval($data['order_import_send_emails'] ?? 0);
        $this->shipmentExportWhen                  = strval($data['shipment_export_when'] ?? 'wc-completed');
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
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return int
     */
    public function getIsActive(): int
    {
        return $this->isActive;
    }

    /**
     * @return int
     */
    public function getCatalogExportWpmlLanguages(): int
    {
        return $this->catalogExportWpmlLanguages;
    }

    /**
     * @return int
     */
    public function getCatalogExportOnlyActive(): int
    {
        return $this->catalogExportOnlyActive;
    }

    /**
     * @return int
     */
    public function getCatalogExportTaxonomies(): int
    {
        return $this->catalogExportTaxonomies;
    }

    /**
     * @return int
     */
    public function getCatalogExportSpecialPrice(): int
    {
        return $this->catalogExportSpecialPrice;
    }

    /**
     * @return int
     */
    public function getCatalogExportEanLeadingZero(): int
    {
        return $this->catalogExportEanLeadingZero;
    }

    /**
     * @return int
     */
    public function getCatalogExportSkipInvalidEan(): int
    {
        return $this->catalogExportSkipInvalidEan;
    }

    /**
     * @return string
     */
    public function getCatalogExportEanAttribute(): string
    {
        return $this->catalogExportEanAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportCostAttribute(): string
    {
        return $this->catalogExportCostAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportDeliveryAttribute(): string
    {
        return $this->catalogExportDeliveryAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportBrandAttribute(): string
    {
        return $this->catalogExportBrandAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportTitleAttribute(): string
    {
        return $this->catalogExportTitleAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportDescriptionAttribute(): string
    {
        return $this->catalogExportDescriptionAttribute;
    }

    /**
     * @return string
     */
    public function getCatalogExportLanguage(): string
    {
        return $this->catalogExportLanguage;
    }

    /**
     * @return int
     */
    public function getOfferExportVirtualStockAmount(): int
    {
        return $this->offerExportVirtualStockAmount;
    }

    /**
     * @param bool $removePrefix
     * @return string
     */
    public function getOrderImportOrderStatus(bool $removePrefix = false): string
    {
        $status = $this->orderImportOrderStatus;
        if ($removePrefix) {
            // Remove 'wc-' from order status (WC both uses order states with and without the prefix).
            $status = preg_replace('~^wc-~', '', $status);
        }
        return $status;
    }

    /**
     * @return string
     */
    public function getOrderImportIdCarrier(): string
    {
        return $this->orderImportIdCarrier;
    }

    /**
     * @return string
     */
    public function getOrderImportIdPaymentModule(): string
    {
        return $this->orderImportIdPaymentModule;
    }

    /**
     * @return string
     */
    public function getOrderImportExternalFulfilment(): string
    {
        return $this->orderImportExternalFulfilment;
    }

    /**
     * @return int
     */
    public function getOrderImportSendEmails(): int
    {
        return $this->orderImportSendEmails;
    }

    /**
     * @return string
     */
    public function getShipmentExportWhen(): string
    {
        return $this->shipmentExportWhen;
    }

    /**
     * Gets available WC carriers for shipment.
     *
     * @return array
     */
    public static function getCarrierOptions(): array
    {
        return self::sort(WcHelper::getCarrierOptions());
    }

    /**
     * Gets available payment methods.
     *
     * @return array
     */
    public static function getPaymentOptions(): array
    {
        return self::sort(WcHelper::getPaymentOptions());

    }

    /**
     * Get list of available order statuses.
     *
     * @return array
     */
    public static function getOrderStatusOptions(): array
    {
        return self::sort(WcHelper::getOrderStatusOptions());
    }

    /**
     * @return array
     */
    public static function getShipmentExportWhenOptions(): array
    {
        $orderStates = WcHelper::getOrderStatusOptions();
        $options = array_map(function($state) {
            return TranslationHelper::translate('WooCommerce order state changed to') . ' ' . $state;
        }, $orderStates);

        if (MyParcelHelper::myParcelPluginActivated()) {
            $options[MyParcelHelper::SHIPMENT_EXPORT_OPTION_TNT] = TranslationHelper::translate('WooCommerce order received a Track and Trace code from MyParcel');
        }

        $options[''] = TranslationHelper::translate('Don\'t update orders');

        return self::sort($options);
    }

    /**
     * @return array
     */
    public static function getAttributeOptions(): array
    {
        // Get product taxonomies
        $taxonomies = self::sort(WcHelper::getTaxonomies());

        // Get custom product attributes
        $customAttributes = self::sort(WcHelper::getCustomProductAttributes());

        // Get default product attributes (such as 'price')
        $defaultAttributes = self::sort(WcHelper::getDefaultProductAttributes());

        // Attributes from external plugins.
        $pluginAttributes = self::sort(WcHelper::getPluginAttributes());

        // $defaultAttributes have priority over $customAttributes in case of duplicate keys
        $customAttributes = array_diff_key($customAttributes, $defaultAttributes);

        $attributes = [
            TranslationHelper::translate('General') => [
                '' => TranslationHelper::translate('Exclude from export')
            ],
            TranslationHelper::translate('Custom attributes') => $customAttributes,
            TranslationHelper::translate('Default attributes') => $defaultAttributes,
            TranslationHelper::translate('Taxonomies') => $taxonomies,
        ];
        if (count($pluginAttributes) > 0) {
            $attributes[TranslationHelper::translate('External plugins')] = $pluginAttributes;
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public static function getLanguageOptions(): array
    {
        return self::sort(LanguageHelper::getAvailableLanguages());
    }

    /**
     * @return array
     */
    public static function getExternalFulfilmentOptions(): array
    {
        return [
            ExternalFulfilmentEnum::EXTERNAL_AND_INTERNAL_ORDERS => TranslationHelper::translate('Import both internally and externally fulfilled orders'),
            ExternalFulfilmentEnum::INTERNAL_ORDERS              => TranslationHelper::translate('Import only internally fulfilled orders'),
            ExternalFulfilmentEnum::EXTERNAL_ORDERS              => TranslationHelper::translate('Import only externally fulfilled orders'),
        ];
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $valid = true;

        if (empty($this->getConnectionName())) {
            $valid = false;
            $this->errors[] = TranslationHelper::translate('Please fill in a connection name');
        }

        if (empty($this->getPublicKey())) {
            $valid = false;
            $this->errors[] = TranslationHelper::translate('Please fill in a public key');
        }

        if (empty($this->getPrivateKey())) {
            $valid = false;
            $this->errors[] = TranslationHelper::translate('Please fill in a private key');
        }

        if ($this->getOfferExportVirtualStockAmount() < 0 || $this->getOfferExportVirtualStockAmount() > 9999) {
            $valid = false;
            $this->errors[] = TranslationHelper::translate('The fictional stock amount should be between 0 and 9999');
        }

        return $valid;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'connection_id' => $this->connectionId,
            'connection_name' => $this->connectionName,
            'public_key' => $this->publicKey,
            'private_key' => $this->privateKey,
            'is_active' => $this->isActive,
            'catalog_export_wpml_languages' => $this->catalogExportWpmlLanguages,
            'catalog_export_only_active' => $this->catalogExportOnlyActive,
            'catalog_export_taxonomies' => $this->catalogExportTaxonomies,
            'catalog_export_special_price' => $this->catalogExportSpecialPrice,
            'catalog_export_ean_leading_zero' => $this->catalogExportEanLeadingZero,
            'catalog_export_skip_invalid_ean' => $this->catalogExportSkipInvalidEan,
            'catalog_export_ean_attribute' => $this->catalogExportEanAttribute,
            'catalog_export_cost_attribute' => $this->catalogExportCostAttribute,
            'catalog_export_delivery_attribute' => $this->catalogExportDeliveryAttribute,
            'catalog_export_title_attribute' => $this->catalogExportTitleAttribute,
            'catalog_export_description_attribute' => $this->catalogExportDescriptionAttribute,
            'catalog_export_brand_attribute' => $this->catalogExportBrandAttribute,
            'catalog_export_language' => $this->catalogExportLanguage,
            'offer_export_virtual_stock_amount' => $this->offerExportVirtualStockAmount,
            'order_import_order_status' => $this->orderImportOrderStatus,
            'order_import_id_carrier' => $this->orderImportIdCarrier,
            'order_import_id_payment_module' => $this->orderImportIdPaymentModule,
            'order_import_external_fulfilment' => $this->orderImportExternalFulfilment,
            'order_import_send_emails' => $this->orderImportSendEmails,
            'shipment_export_when' => $this->shipmentExportWhen,
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    protected static function sort(array $options): array
    {
        natcasesort($options);
        return $options;
    }
}