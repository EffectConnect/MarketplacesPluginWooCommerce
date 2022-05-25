<?php

namespace EffectConnect\Marketplaces\DB;

use EffectConnect\Marketplaces\Model\ConnectionResource;
use wpdb;

class ConnectionRepository
{
    /**
     * Name of the table this class communicates with.
     * @var string
     */
    private $tableName;

    private static $instance;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * Get singleton instance of ProductOptionsRepository.
     * @return ConnectionRepository
     */
    static function getInstance(): ConnectionRepository
    {
        if (!self::$instance) {
            self::$instance = new ConnectionRepository();
        }

        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . 'ec_connections';
    }

    /**
     * @param $connectionId
     */
    public function deleteConnection($connectionId)
    {
        $this->wpdb->delete($this->tableName, ['connection_id' => $connectionId], '%d');
    }

    /**
     * @param int $id
     * @return ConnectionResource
     */
    public function get(int $id): ConnectionResource
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM `$this->tableName` WHERE `connection_id` = %s",
                $id), 'ARRAY_A'
        );

        return new ConnectionResource((array)$result);
    }

    /**
     * @param ConnectionResource $connectionResource
     * @return void
     */
    public function saveConnection(ConnectionResource $connectionResource)
    {
        if ($connectionResource->getConnectionId() > 0) {
            $where = ['connection_id' => $connectionResource->getConnectionId()];
            $this->wpdb->update($this->tableName, $connectionResource->toArray(), $where);
        } else {
            $this->wpdb->insert($this->tableName, $connectionResource->toArray());
        }
    }

    /**
     * @return object|null
     */
    public function getLatestConnection()
    {
        return $this->wpdb->get_row("SELECT * FROM $this->tableName ORDER BY connection_id DESC LIMIT 1");
    }

    /**
     * @return array|null
     */
    public function getAllConnections(): ?array
    {
        return $this->wpdb->get_results("SELECT * FROM $this->tableName ORDER BY connection_id");
    }

    /**
     * @return array|null
     */
    public function getConnectionEanLeadingZero(): ?array
    {
        return $this->wpdb->get_row("SELECT catalog_export_ean_leading_zero FROM $this->tableName DESC LIMIT 1");
    }

    /**
     * @return array|null
     */
    public function getConnectionEanInvalidExport(): ?array
    {
        return $this->wpdb->get_row("SELECT catalog_export_skip_invalid_ean FROM $this->tableName DESC LIMIT 1");
    }
}