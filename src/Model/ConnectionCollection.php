<?php

namespace EffectConnect\Marketplaces\Model;

class ConnectionCollection
{
    static $connections = [];

    /**
     * Returns the first active connection.
     * @return ConnectionResource[]
     */
    public static function getActive(): array
    {
        if (!self::$connections) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ec_connections';
            $connections = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1");

            self::$connections = [];
            foreach ($connections as $connectionData) {
                self::$connections[] = new ConnectionResource((array)$connectionData);
            }
        }

        return self::$connections;
    }
}