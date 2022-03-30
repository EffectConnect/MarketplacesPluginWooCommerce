<?php

namespace EffectConnect\Marketplaces\Enums;

/**
 * Class ExternalFulfilment
 * @package EffectConnect\Marketplaces\Enums
 */
class ExternalFulfilmentEnum
{
    /**
     * Only import my own orders.
     */
    const INTERNAL_ORDERS = 'internal_only';

    /**
     * Only import orders that are fulfilled externally by the channel.
     */
    const EXTERNAL_ORDERS = 'external_only';

    /**
     * Import both my own order and externally fulfilled orders.
     */
    const EXTERNAL_AND_INTERNAL_ORDERS = 'any';
}