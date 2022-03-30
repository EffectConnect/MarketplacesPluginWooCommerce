<?php

namespace EffectConnect\Marketplaces\Exception;

class InvalidSkuException extends AbstractException
{
    const MESSAGE_FORMAT = 'Invalid (empty) SKU.';
}