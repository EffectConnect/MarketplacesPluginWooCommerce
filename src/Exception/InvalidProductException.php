<?php


namespace EffectConnect\Marketplaces\Exception;


class InvalidProductException extends AbstractException
{
    const MESSAGE_FORMAT = 'Product is invalid and will be skipped (reason: %s).';

}