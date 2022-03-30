<?php

namespace EffectConnect\Marketplaces\Logic;

use EffectConnect\Marketplaces\Constants\ConfigConstants;
use EffectConnect\Marketplaces\Controller\BaseController;
use EffectConnect\Marketplaces\Cron\CronSchedules;
use EffectConnect\Marketplaces\Exception\InvalidCronIntervalStringException;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Model\ConfigurationValue;

class ConfigContainer extends BaseController implements ConfigConstants
{
    /**
     * Name of the table that the fields in this class will be saved to.
     * @var string
     */
    protected $configOptionName = self::CONFIGURATION_TABLE_NAME;

    /**
     * How frequent the catalog should be exported in minutes.
     * @var ConfigurationValue
     */
    protected $catalogExportSchedule;

    /**
     * How frequent the full offer export call should be executed in minutes.
     * @var ConfigurationValue
     */
    protected $fullOfferExportSchedule;

    /**
     * $whether an offerExport should be executed on every change in a product.
     * @var ConfigurationValue
     */
    protected $exportOnProductChange;

    /**
     * The size of the offer export queue.
     * @var ConfigurationValue
     */
    protected $offerExportQueueSize;

    /**
     * How frequent the order import call should be executed in minutes.
     * @var ConfigurationValue
     */
    protected $orderImportSchedule;

    /**
     * Queue size for shipment export calls.
     * @var ConfigurationValue
     */
    protected $shipmentExportQueueSize;

    /**
     * When logs should be cleaned up.
     * @var ConfigurationValue
     */
    protected $logExpiration;

    /**
     * @var ConfigContainer
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct();
        $this->getSavedConfiguration();
    }

    /**
     * @return ConfigContainer
     */
    public static function getInstance(): ConfigContainer
    {
        if (!self::$instance) {
            self::$instance = new ConfigContainer(); // instance is assigned to property in the constructor.
        }
        return self::$instance;
    }

    /**
     * Gets saved configuration from the wp database.
     */
    protected function getSavedConfiguration()
    {
        $options = [];
        if (function_exists('get_option') && function_exists('add_option')) {
            if (!get_option($this->configOptionName)) {
                add_option($this->configOptionName);
            }
            $options = json_decode(get_option($this->configOptionName), true);
        }

        $this->setCatalogExportSchedule     ($options['catalog_export_schedule'] ?? null);
        $this->setExportOnProductChange     ($options['export_on_product_change'] ?? null);
        $this->setOfferExportQueueSize      ($options['offer_export_queue_size'] ?? null);
        $this->setFullOfferExportSchedule   ($options['offer_export_schedule'] ?? null);
        $this->setLogExpiration             ($options['log_expiration'] ?? null);
        $this->setOrderImportSchedule       ($options['order_import_schedule'] ?? null);
        $this->setShipmentExportQueueSize   ($options['shipment_export_queue_size'] ?? null);
    }

    public function getAllSettingValues(): array
    {
        return [
            'catalog_export_schedule'    => $this->getCatalogExportScheduleValue(),
            'log_expiration'             => $this->getLogExpirationValue(),
            'export_on_product_change'   => $this->getExportOnProductChangeValue(),
            'offer_export_queue_size'    => $this->getOfferExportQueueSizeValue(),
            'offer_export_schedule'      => $this->getFullOfferExportScheduleValue(),
            'order_import_schedule'      => $this->getOrderImportScheduleValue(),
            'shipment_export_queue_size' => $this->getShipmentExportQueueSizeValue(),
        ];
    }

    /**
     * Gets all catalog-category settings in an array.
     * @return array
     */
    public function getCatalogSettings(): array
    {
        return [
            'catalog_export_schedule' => $this->catalogExportSchedule,
        ];
    }

    /**
     * Gets all logging category settings in an array.
     * @return array
     */
    public function getLoggingSettings(): array
    {
        return ['log_expiration' => $this->logExpiration];
    }

    /**
     * Gets all offer-export category settings in an array.
     * @return array
     */
    public function getOfferExportSettings(): array
    {
        return [
            'offer_export_schedule'    => $this->fullOfferExportSchedule,
            'export_on_product_change' => $this->exportOnProductChange,
            'offer_export_queue_size'  => $this->offerExportQueueSize,
        ];
    }

    /**
     * Gets all order-import category settings in an array.
     * @return array
     */
    public function getOrderImportSettings(): array
    {
        return [
            'order_import_schedule' => $this->orderImportSchedule,
        ];
    }

    /**
     * Gets all shipment category settings in an array.
     * @return array
     */
    public function getShipmentExportSettings(): array
    {
        return [
            'shipment_export_queue_size' => $this->shipmentExportQueueSize,
        ];
    }

    /**
     * @param $value
     */
    protected function setShipmentExportQueueSize($value)
    {
        $this->shipmentExportQueueSize = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Queue size'),
            TranslationHelper::translate('Select how many calls can be queued up.'),
            $value ?? '10',
            self::getQueueSizeOptions()
        );
    }

    /**
     * @param $value
     */
    protected function setOrderImportSchedule($value)
    {
        $this->orderImportSchedule = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Schedule'),
            TranslationHelper::translate('Select the frequency the order import process will run at.'),
            $value ?? '',
            [
                '' => TranslationHelper::translate('Disabled'),
                '5m' => TranslationHelper::translate('5 minutes'),
                '15m' => TranslationHelper::translate('15 minutes'),
                '30m' => TranslationHelper::translate('30 minutes'),
                '1h' => TranslationHelper::translate('1 hour'),
                '1d' => TranslationHelper::translate('1 day'),
            ]
        );
    }

    /**
     * @param $value
     */
    protected function setFullOfferExportSchedule($value)
    {
        $this->fullOfferExportSchedule = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Schedule'),
            TranslationHelper::translate('Select the frequency the offer export process will run at.'),
            $value ?? '',
            [
                '' => TranslationHelper::translate('Disabled'),
                '30m' => TranslationHelper::translate('30 minutes'),
                '1h' => TranslationHelper::translate('1 hour'),
                '2h' => TranslationHelper::translate('2 hours'),
                '3h' => TranslationHelper::translate('3 hours'),
                '6h' => TranslationHelper::translate('6 hours'),
                '8h' => TranslationHelper::translate('8 hours'),
                '12h' => TranslationHelper::translate('12 hours'),
            ]
        );
    }

    /**
     * @param $value
     */
    protected function setExportOnProductChange($value)
    {
        $this->exportOnProductChange = new ConfigurationValue(
            ConfigurationValue::TYPE_CHECKBOX,
            TranslationHelper::translate('Export on product change'),
            TranslationHelper::translate('Select if an offer export should be executed after a product has seen changes.'),
            $value ?? ''
        );
    }

    /**
     * @param $value
     */
    protected function setOfferExportQueueSize($value)
    {
        $this->offerExportQueueSize = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Queue size'),
            TranslationHelper::translate('Select how many calls can be queued up.'),
            $value ?? '10',
            self::getQueueSizeOptions()
        );
    }

    /**
     * @param $value
     */
    protected function setLogExpiration($value)
    {
        $this->logExpiration = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Log expiration time'),
            TranslationHelper::translate('Select how long the log should be preserved before the expired entries will automatically be removed from the log.'),
            $value ?? '3d',
            [
                '1d' => TranslationHelper::translate('Expires in 1 day'),
                '3d' => TranslationHelper::translate('Expires in 3 days'),
                '1w' => TranslationHelper::translate('Expires in 1 week'),
                '2w' => TranslationHelper::translate('Expires in 2 weeks'),
                '4w' => TranslationHelper::translate('Expires in 4 weeks'),
            ]
        );
    }

    /**
     * @param $value
     */
    protected function setCatalogExportSchedule($value)
    {
        $this->catalogExportSchedule = new ConfigurationValue(
            ConfigurationValue::TYPE_SELECT,
            TranslationHelper::translate('Schedule'),
            TranslationHelper::translate('Select the frequency the catalog export process will run at.'),
            $value ?? '',
            [
                '' => TranslationHelper::translate('Disabled'),
                '1h' => TranslationHelper::translate('1 hour'),
                '2h' => TranslationHelper::translate('2 hours'),
                '4h' => TranslationHelper::translate('4 hours'),
                '6h' => TranslationHelper::translate('6 hours'),
                '12h' => TranslationHelper::translate('12 hours'),
                '1d' => TranslationHelper::translate('1 day'),
                '2d' => TranslationHelper::translate('2 days'),
                '5d' => TranslationHelper::translate('5 days'),
                '1w' => TranslationHelper::translate('1 week'),
            ]
        );
    }

    /**
     * @return string
     */
    public function getCatalogExportScheduleValue(): string
    {
        return $this->catalogExportSchedule->getValue();
    }

    /**
     * @return string
     */
    public function getShipmentExportQueueSizeValue(): string
    {
        return $this->shipmentExportQueueSize->getValue();
    }

    /**
     * @return string
     */
    public function getOrderImportScheduleValue(): string {
        return $this->orderImportSchedule->getValue();
    }

    /**
     * @return string
     */
    public function getFullOfferExportScheduleValue(): string
    {
        return $this->fullOfferExportSchedule->getValue();
    }
    /**
     * @return string
     */
    public function getExportOnProductChangeValue(): string
    {
        return $this->exportOnProductChange->getValue();
    }

    /**
     * @return string
     */
    public function getOfferExportQueueSizeValue(): string
    {
        return $this->offerExportQueueSize->getValue();
    }

    /**
     * @return string
     */
    public function getLogExpirationValue(): string
    {
        return $this->logExpiration->getValue();
    }

    /**
     * @return int
     */
    public function getLogExpirationValueInDays(): int
    {
        try {
            $logExpirationSeconds = CronSchedules::calculateSeconds($this->getLogExpirationValue());
        } catch (InvalidCronIntervalStringException $e) {
            return 0;
        }

        return ceil($logExpirationSeconds / 86400);
    }

    /**
     * @return string[]
     */
    protected static function getQueueSizeOptions(): array
    {
        return [
            '5' => '5',
            '10' => '10',
            '15' => '15',
            '25' => '25',
            '50' => '50',
            '100' => '100',
        ];
    }
}