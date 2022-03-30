<?php
/**
 * Plugin Name: EffectConnect Marketplaces
 * Description: This plugin will allow you to connect your WooCommerce 4.0+ webshop with EffectConnect Marketplaces.
 * Version: 3.0.11
 * Author: EffectConnect
 * Author URI: https://www.effectconnect.com/
 */

use EffectConnect\Marketplaces\Controller\ECMenu;
use EffectConnect\Marketplaces\Cron\CronSchedules;
use EffectConnect\Marketplaces\DB\ECTables;
use EffectConnect\Marketplaces\Logic\OfferExport\ProductWatcher;
use EffectConnect\Marketplaces\Logic\ShipmentExport\ShipmentWatcher;
use EffectConnect\Marketplaces\Model\ECPayment;
use EffectConnect\Marketplaces\Model\ECShipping;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PluginActivationClass
{
    public function __construct()
    {
        require_once __DIR__ . '/vendor/autoload.php';

        register_activation_hook(__FILE__, [$this, 'ec_plugin_activate']);
        register_deactivation_hook(__FILE__, [$this, 'ec_plugin_deactivate']);

        add_action('woocommerce_shipping_init', [$this, 'registerECShippingMethod']);
        add_action('woocommerce_after_register_post_type', [$this, 'registerECPaymentMethods']);
        add_action('woocommerce_after_register_post_type', [$this, 'addWatchers']);

        $this->addPluginMenus();
        $this->addCronSchedules();
        $this->loadTextDomain();
    }

    private function addPluginMenus()
    {
        new ECMenu();
    }

    public function addWatchers()
    {
        new ProductWatcher();
        new ShipmentWatcher();
    }

    private function addCronSchedules()
    {
        new CronSchedules();
    }

    /**
     * Load translations files and set the translations domain key to 'effectconnect_marketplaces'.
     * https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
     *
     * @return void
     */
    protected function loadTextDomain()
    {
        load_plugin_textdomain( 'effectconnect_marketplaces', false, dirname(plugin_basename( __FILE__ )) . '/languages' );
    }

    /**
     * Activate the plugin.
     */
    public function ec_plugin_activate()
    {
        $tables = ECTables::getInstance();
        $tables->ecCreateConnectionsTable();
        $tables->ecCreateProductOptionsTable();
        $tables->ecCreateOfferUpdateQueueTable();
        $tables->ecCreateShipmentExportQueueTable();
    }

    public function registerECShippingMethod() {
        add_action( 'woocommerce_shipping_methods', [$this, 'addShippingMethod']);
    }

    public function registerECPaymentMethods() {
        add_action( 'woocommerce_payment_gateways', [$this, 'addPaymentMethod']);
    }

    public function addShippingMethod($methods) {
        if ((function_exists('is_admin') && is_admin()) || (function_exists('wp_doing_cron') && wp_doing_cron())) {
            $methods[] = new ECShipping();
        }
        return $methods;
    }

    public function addPaymentMethod($methods) {
        if ((function_exists('is_admin') && is_admin()) || (function_exists('wp_doing_cron') && wp_doing_cron())) {
            $methods[] = new ECPayment();
        }
        return $methods;
    }

    /**
     * Deactivation hook.
     */
    public function ec_plugin_deactivate()
    {
        CronSchedules::unscheduleAll();
    }
}
$plugin = new PluginActivationClass();

register_uninstall_hook(__FILE__, 'ec_plugin_uninstall');

/**
 * Uninstall hook.
 */
function ec_plugin_uninstall()
{
    $tables = ECTables::getInstance();
    $tables->ecDeleteProductOptionsTable();
    $tables->ecDeleteConnectionsTable();
    $tables->ecDeleteOfferQueueTable();
    $tables->ecDeleteShipmentQueueTable();
}