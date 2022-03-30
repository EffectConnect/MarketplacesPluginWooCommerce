<?php

namespace EffectConnect\Marketplaces\Exception;

/**
 * Class InvalidCronIntervalStringException
 * @package EffectConnect\Marketplaces\Exception
 * @method string __construct(string $intervalString)
 */
class InvalidCronIntervalStringException extends AbstractException
{
    /**
     * @inheritDoc
     */
    protected const MESSAGE_FORMAT = 'Invalid CRON interval string (%s).';
}