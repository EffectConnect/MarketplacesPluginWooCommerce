<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Cron\CronSchedules;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use EffectConnect\Marketplaces\Interfaces\ControllerInterface;
use EffectConnect\Marketplaces\Logic\ConfigContainer;

class ConfigController extends ConfigContainer implements ControllerInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Renders the settings page.
     */
    public function init()
    {
        $options = [];
        $options['CatalogExport']  = $this->getCatalogSettings();
        $options['OfferExport']    = $this->getOfferExportSettings();
        $options['OrderImport']    = $this->getOrderImportSettings();
        $options['ShipmentExport'] = $this->getShipmentExportSettings();
        $options['Logging']        = $this->getLoggingSettings();

        $this->render('options/ec_settings_list.html.twig', [
            'options' => $options,
            'button'  => get_submit_button(TranslationHelper::translate('Save settings'), 'primary', 'save_config'),
        ]);

        if ($_POST) {
            $this->setConfiguration();
        }
    }

    /**
     * Saves the posted configuration to the wp database and refreshes the page.
     */
    protected function setConfiguration() {
        $this->clearScheduledJobsIfNecessary();

        $this->catalogExportSchedule->setValue(strval($_REQUEST['catalog_export_schedule']));
        $this->exportOnProductChange->setValue(strval($_REQUEST['export_on_product_change']));
        $this->offerExportQueueSize->setValue(strval($_REQUEST['offer_export_queue_size']));
        $this->fullOfferExportSchedule->setValue(strval($_REQUEST['offer_export_schedule']));
        $this->orderImportSchedule->setValue(strval($_REQUEST['order_import_schedule']));
        $this->shipmentExportQueueSize->setValue(strval($_REQUEST['shipment_export_queue_size']));
        $this->logExpiration->setValue(strval($_REQUEST['log_expiration']));

        update_option($this->configOptionName, json_encode($this->getAllSettingValues()));
        $this->messagesContainer->addNotice(TranslationHelper::translate('Settings saved successfully.'));
        wp_redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * In case the schedule frequency has changed the corresponding process' cron jobs should be cleared.
     */
    protected function clearScheduledJobsIfNecessary()
    {
        if ($this->catalogExportSchedule->getValue() !== $_REQUEST['catalog_export_schedule']) {
            CronSchedules::unschedule(CronSchedules::CATALOG_EXPORT_HOOK);
        }
        if ($this->fullOfferExportSchedule->getValue() !== $_REQUEST['offer_export_schedule']) {
            CronSchedules::unschedule(CronSchedules::FULL_OFFER_EXPORT_HOOK);
        }
        if ($this->orderImportSchedule->getValue() !== $_REQUEST['order_import_schedule']) {
            CronSchedules::unschedule(CronSchedules::ORDER_IMPORT_HOOK);
        }
    }
}