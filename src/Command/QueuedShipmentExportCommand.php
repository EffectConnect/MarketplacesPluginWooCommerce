<?php

namespace EffectConnect\Marketplaces\Command;

use EffectConnect\Marketplaces\Api\OrderHandler;
use EffectConnect\Marketplaces\Model\ConnectionCollection;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use Exception;

class QueuedShipmentExportCommand
{
    /**
     * @var OrderHandler
     */
    private $orderHandler;

    public function __construct()
    {
        $connections = ConnectionCollection::getActive();

        if (count($connections) > 0) {
            $this->orderHandler = new OrderHandler();

            foreach($connections as $connection) {
                $this->execute($connection);
            }
        }
    }

    /**
     * Starts queued offer export.
     * @param ConnectionResource $connection
     */
    protected function execute(ConnectionResource $connection)
    {
        try {
            $this->orderHandler->exportShipments($connection);
        } catch (Exception $e) {}
    }
}