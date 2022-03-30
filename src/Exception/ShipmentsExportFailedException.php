<?php

namespace EffectConnect\Marketplaces\Exception;

/**
 * Class ShipmentsExportFailedException
 * @package EffectConnect\Marketplaces\Exception
 * @method string __construct(string $connectionId, string $message)
 */
class ShipmentsExportFailedException extends AbstractException
{
    /**
     * @inheritDoc
     */
    protected const MESSAGE_FORMAT = 'The shipments export for connection %s failed with message: %s';
}