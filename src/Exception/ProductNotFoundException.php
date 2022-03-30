<?php


namespace EffectConnect\Marketplaces\Exception;


class ProductNotFoundException extends AbstractException
{
    const MESSAGE_FORMAT = 'Product was not found (%s).';
}