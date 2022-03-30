<?php

namespace EffectConnect\Marketplaces\Command;

use EffectConnect\Marketplaces\Api\OrderHandler;
use EffectConnect\Marketplaces\Model\ConnectionCollection;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use Exception;

class OrderImportCommand
{
    /**
     * @var OrderHandler
     */
    private $orderHandler;

    public function __construct() {
        $connections = ConnectionCollection::getActive();

        if (count($connections) > 0) {
            try {
                $this->orderHandler = new OrderHandler();
            } catch (Exception $e) {
            }
            foreach($connections as $connection) {
                $this->execute($connection);
            }

        }
    }

    /**
     * @param ConnectionResource $connection
     * @return void
     */
    protected function execute(ConnectionResource $connection)
    {
        try {
            $this->orderHandler->ordersImport($connection);
        } catch (Exception $e) {}
    }
}