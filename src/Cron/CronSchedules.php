<?php

namespace EffectConnect\Marketplaces\Cron;

use EffectConnect\Marketplaces\Command\CatalogExportCommand;
use EffectConnect\Marketplaces\Command\CleanLogsCommand;
use EffectConnect\Marketplaces\Command\FullOfferExportCommand;
use EffectConnect\Marketplaces\Command\OrderImportCommand;
use EffectConnect\Marketplaces\Command\QueuedOfferExportCommand;
use EffectConnect\Marketplaces\Command\QueuedShipmentExportCommand;
use EffectConnect\Marketplaces\Exception\InvalidCronIntervalStringException;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use Exception;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CronSchedules
{
    /**
     * Queued offer export cron interval can not be modified by user.
     */
    const QUEUED_OFFER_EXPORT_INTERVAL = '1m';

    /**
     * Queued shipment export cron interval can not be modified by user.
     */
    const QUEUED_SHIPMENT_EXPORT_INTERVAL = '1m';

    /**
     * Log cleanup interval can not be modified by user.
     */
    const LOG_CLEANUP_INTERVAL = '12h';

    /**
     * Define scheduled hooks.
     */
    const LOG_CLEANUP_HOOK            = 'ec_log_cleanup';
    const CATALOG_EXPORT_HOOK         = 'ec_catalog_export';
    const FULL_OFFER_EXPORT_HOOK      = 'ec_full_offer_export';
    const QUEUED_OFFER_EXPORT_HOOK    = 'ec_queued_offer_export';
    const ORDER_IMPORT_HOOK           = 'ec_order_import';
    const QUEUED_SHIPMENT_EXPORT_HOOK = 'ec_queued_shipment_export';

    /**
     * For getting user settings.
     * @var ConfigContainer
     */
    private $config;

    public function __construct()
    {
        $this->config = ConfigContainer::getInstance();

        add_filter('cron_schedules', [$this, 'addLogCleanUpScheduleInterval']);
        add_action(self::LOG_CLEANUP_HOOK, [$this, 'runCleanLogsCommand']);
        $this->scheduleLogCleanup();

        add_filter('cron_schedules', [$this, 'addCatalogueExportScheduleInterval']);
        add_action(self::CATALOG_EXPORT_HOOK, [$this, 'runCatalogExportCommand']);
        $this->scheduleCatalogExport();

        add_filter('cron_schedules', [$this, 'addFullOfferExportScheduleInterval']);
        add_action(self::FULL_OFFER_EXPORT_HOOK, [$this, 'runFullOfferExportCommand']);
        $this->scheduleFullOfferExport();

        add_filter('cron_schedules', [$this, 'addQueuedOfferExportScheduleInterval']);
        add_action(self::QUEUED_OFFER_EXPORT_HOOK, [$this, 'runQueuedOfferExportCommand']);
        $this->scheduleQueuedOfferExport();

        add_filter('cron_schedules', [$this, 'addQueuedShipmentExportScheduleInterval']);
        add_action(self::QUEUED_SHIPMENT_EXPORT_HOOK, [$this, 'runQueuedShipmentExportCommand']);
        $this->scheduleQueuedShipmentExport();

        add_filter('cron_schedules', [$this, 'addOrderImportScheduleInterval']);
        add_action(self::ORDER_IMPORT_HOOK, [$this, 'runOrderImportCommand']);
        $this->scheduleOrderImport();
    }

    /**
     * Runs the clean logs command by executing the CleanLogsCommand.php file.
     * @throws Exception
     */
    public function runCleanLogsCommand()
    {
        new CleanLogsCommand();
    }

    /**
     * Runs the catalog export command by executing the CatalogExportCommand.php file.
     * @throws Exception
     */
    public function runCatalogExportCommand()
    {
        new CatalogExportCommand();
    }

    /**
     * Runs the queued offer export command by executing the QueuedOfferExportCommand.php file.
     * @throws Exception
     */
    public function runQueuedOfferExportCommand()
    {
        new QueuedOfferExportCommand();
    }

    /**
     * Runs the queued shipment export command by executing the QueuedShipmentExportCommand.php file.
     * @throws Exception
     */
    public function runQueuedShipmentExportCommand()
    {
        new QueuedShipmentExportCommand();
    }

    /**
     * Runs the full offer export command by executing the FullOfferExportCommand.php file.
     * @throws Exception
     */
    public function runFullOfferExportCommand()
    {
        new FullOfferExportCommand();
    }

    /**
     * Runs the order import command by executing the OrderImportCommand.php file.
     */
    public function runOrderImportCommand()
    {
        new OrderImportCommand();
    }

    /**
     * Sets the log cleanup schedule.
     */
    protected function scheduleLogCleanup()
    {
        $scheduleString = $this->getScheduleString(self::LOG_CLEANUP_INTERVAL);
        self::schedule(self::LOG_CLEANUP_HOOK, $scheduleString);
    }

    /**
     * Sets the catalog export schedule.
     */
    protected function scheduleCatalogExport()
    {
        $scheduleString = $this->getScheduleString($this->config->getCatalogExportScheduleValue());
        self::schedule(self::CATALOG_EXPORT_HOOK, $scheduleString);
    }

    /**
     * Sets the offer export schedule.
     */
    protected function scheduleFullOfferExport()
    {
        $scheduleString = $this->getScheduleString($this->config->getFullOfferExportScheduleValue());
        self::schedule(self::FULL_OFFER_EXPORT_HOOK, $scheduleString);
    }

    /**
     * Sets the offer export schedule.
     */
    protected function scheduleQueuedOfferExport()
    {
        $scheduleString = $this->getScheduleString(self::QUEUED_OFFER_EXPORT_INTERVAL);
        self::schedule(self::QUEUED_OFFER_EXPORT_HOOK, $scheduleString);
    }

    /**
     * Sets the shipment export schedule.
     */
    protected function scheduleQueuedShipmentExport()
    {
        $scheduleString = $this->getScheduleString(self::QUEUED_SHIPMENT_EXPORT_INTERVAL);
        self::schedule(self::QUEUED_SHIPMENT_EXPORT_HOOK, $scheduleString);
    }

    /**
     * Sets the order import schedule.
     */
    protected function scheduleOrderImport()
    {
        $scheduleString = $this->getScheduleString($this->config->getOrderImportScheduleValue());
        self::schedule(self::ORDER_IMPORT_HOOK, $scheduleString);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addCatalogueExportScheduleInterval($schedules): array
    {
        $intervalString = $this->config->getCatalogExportScheduleValue();
        return $this->generateNewSchedule($intervalString, $schedules);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addFullOfferExportScheduleInterval($schedules): array
    {
        $intervalString = $this->config->getFullOfferExportScheduleValue();
        return $this->generateNewSchedule($intervalString, $schedules);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addLogCleanUpScheduleInterval($schedules): array
    {
        return $this->generateNewSchedule(self::LOG_CLEANUP_INTERVAL, $schedules);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addQueuedOfferExportScheduleInterval($schedules): array
    {
        return $this->generateNewSchedule(self::QUEUED_OFFER_EXPORT_INTERVAL, $schedules);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addQueuedShipmentExportScheduleInterval($schedules): array
    {
        return $this->generateNewSchedule(self::QUEUED_SHIPMENT_EXPORT_INTERVAL, $schedules);
    }

    /**
     * Adds a custom schedule to wp-cron.
     * @param $schedules
     * @return array
     */
    public function addOrderImportScheduleInterval($schedules): array
    {
        $intervalString = $this->config->getOrderImportScheduleValue();
        return $this->generateNewSchedule($intervalString, $schedules);
    }

    /**
     * Converts a string representation of time into an integer value for the number of days.
     * @param string $intervalString
     * @param array $schedules
     * @return array
     */
    protected function generateNewSchedule(string $intervalString, array $schedules): array
    {
        if (!empty($intervalString)) {
            try {
                $seconds = self::calculateSeconds($intervalString);
                $schedules[$this->getScheduleString($intervalString)] = [
                    'interval' => $seconds,
                    'display' => esc_html__("EffectConnect Cron Interval " . $intervalString),
                ];
            } catch (InvalidCronIntervalStringException $e) {}
        }

        return $schedules;
    }

    /**
     * @param string $intervalString
     * @return string
     */
    protected function getScheduleString(string $intervalString): string
    {
        return 'ec_cron_interval_' . $intervalString;
    }

    /**
     * @param string $intervalString
     * @return int
     * @throws InvalidCronIntervalStringException
     */
    public static function calculateSeconds(string $intervalString): int
    {
        // Allowed $intervalString: <number><unit> (unit can be m (minute), h (hour), d (day) or w (week))
        if (!preg_match('~(?<number>[0-9]{1,2})(?<unit>[mhdw])~', $intervalString, $matches)) {
            throw new InvalidCronIntervalStringException($intervalString);
        }

        $seconds = [
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
            'w' => 604800,
        ];

        return $matches['number'] * $seconds[$matches['unit']];
    }

    /**
     * @param string $hook
     * @param string $scheduleString
     * @return void
     */
    public static function schedule(string $hook, string $scheduleString)
    {
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time(), $scheduleString, $hook);
        }
    }

    /**
     * @param string $hook
     * @return void
     */
    public static function unschedule(string $hook)
    {
        $timestamp = wp_next_scheduled($hook);
        wp_unschedule_event($timestamp, $hook);
    }

    /**
     * Remove all schedule tasks when plugin is deactivated:
     * https://developer.wordpress.org/plugins/cron/scheduling-wp-cron-events/#unscheduling-tasks
     *
     * @return void
     */
    public static function unscheduleAll()
    {
        self::unschedule(self::LOG_CLEANUP_HOOK);
        self::unschedule(self::CATALOG_EXPORT_HOOK);
        self::unschedule(self::FULL_OFFER_EXPORT_HOOK);
        self::unschedule(self::QUEUED_OFFER_EXPORT_HOOK);
        self::unschedule(self::ORDER_IMPORT_HOOK);
    }
}