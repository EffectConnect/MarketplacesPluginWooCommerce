<?php

namespace EffectConnect\Marketplaces\DB;

class ECTables
{
    protected $wpdb;
    static $instance;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    static function getInstance(): ECTables
    {
        if (!self::$instance) {
            self::$instance = new ECTables();
        }
        return self::$instance;
    }

    /**
     * Creates EffectConnect connection table
     */
    public function ecCreateConnectionsTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //* Create the connections table
        $table_name = $this->wpdb->prefix . 'ec_connections';
        $sql = "CREATE TABLE $table_name 
    (
         connection_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         connection_name VARCHAR(256) NOT NULL,
         public_key VARCHAR(256) NOT NULL,
         private_key VARCHAR(256) NOT NULL,
         is_active TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_wpml_languages TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_only_active TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_taxonomies TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_skip_regenerate_ids TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_special_price TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_ean_leading_zero TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_skip_invalid_ean TINYINT(1) UNSIGNED NOT NULL,
         catalog_export_ean_attribute VARCHAR(256) NOT NULL,
         catalog_export_cost_attribute VARCHAR(256) NOT NULL,
         catalog_export_delivery_attribute VARCHAR(256) NOT NULL,
         catalog_export_title_attribute VARCHAR(256) NOT NULL,
         catalog_export_description_attribute VARCHAR(256) NOT NULL,
         catalog_export_brand_attribute VARCHAR(256) NOT NULL,
         catalog_export_language VARCHAR(2) NOT NULL,
         offer_export_virtual_stock_amount INT(11) UNSIGNED NOT NULL,
         offer_export_virtual_stock_conditional_backorders TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
         order_import_order_status VARCHAR(64) NOT NULL,
         order_import_id_carrier VARCHAR(64) NOT NULL,
         order_import_id_payment_module VARCHAR(64) NOT NULL,
         order_import_external_fulfilment VARCHAR(64) NOT NULL,   
         order_import_send_emails TINYINT(1) NOT NULL,
         order_import_skip_taxes TINYINT(1) NOT NULL,
         shipment_export_when VARCHAR(64) NOT NULL,   
         shipment_export_tracking_codes VARCHAR(256) NOT NULL,   
         PRIMARY KEY (connection_id)
    ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Creates Product-Options table
     */
    public function ecCreateProductOptionsTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //* Create the product options table
        $table_name = $this->wpdb->prefix . 'ec_product_options';
        $sql = "CREATE TABLE $table_name 
    (
         option_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         variation_id INT(11) UNSIGNED NULL,
         product_id INT(11) UNSIGNED NOT NULL,
         product_name TEXT NOT NULL,
         attribute_data TEXT NULL,
         hash VARCHAR(32),
         PRIMARY KEY (option_id),
         UNIQUE KEY hash_unique (hash)
    ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Creates OfferExport-queue table
     */
    public function ecCreateOfferUpdateQueueTable()
    {
        $charset_collate = $this->wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //* Create the queue table
        $table_name = $this->wpdb->prefix . 'ec_offer_update_queue';
        $sql = "CREATE TABLE $table_name 
    (
         offer_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         product_id INT(11) UNSIGNED NOT NULL,
         PRIMARY KEY (offer_id),
         UNIQUE KEY product_id_unique (product_id)
    ) $charset_collate;";

        dbDelta($sql);
    }

    public function ecCreateShipmentExportQueueTable() {
        $charset_collate = $this->wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //* Create the queue table
        $table_name = $this->wpdb->prefix . 'ec_shipment_export_queue';
        $sql = "CREATE TABLE $table_name 
    (
        shipping_export_queue_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT(11) UNSIGNED NOT NULL,
        connection_id INT(10) UNSIGNED NOT NULL,
        ec_marketplaces_identification_number VARCHAR(128) NOT NULL,
        ec_marketplaces_order_line_ids text NOT NULL,
        is_shipped TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
        carrier_name VARCHAR(64) DEFAULT NULL,
        tracking_number VARCHAR(64) DEFAULT NULL,
        order_imported_at datetime DEFAULT NULL,
        shipped_exported_at datetime DEFAULT NULL,
        tracking_exported_at datetime DEFAULT NULL,
        import_success TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
        import_error TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
        PRIMARY KEY (shipping_export_queue_id),
        UNIQUE KEY order_id_unique (order_id)
    ) $charset_collate;";

        dbDelta($sql);
    }

    public function ecDeleteConnectionsTable()
    {
        $table_name = $this->wpdb->prefix . 'ec_connections';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $this->wpdb->query($sql);
    }

    public function ecDeleteProductOptionsTable()
    {
        $table_name = $this->wpdb->prefix . 'ec_product_options';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $this->wpdb->query($sql);
    }

    public function ecDeleteOfferQueueTable()
    {
        $table_name = $this->wpdb->prefix . 'ec_offer_update_queue';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $this->wpdb->query($sql);
    }

    public function ecDeleteShipmentQueueTable()
    {
        $table_name = $this->wpdb->prefix . 'ec_shipment_export_queue';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $this->wpdb->query($sql);
    }
}
