<?php


namespace EffectConnect\Marketplaces\Exception;


class InvalidEanException extends AbstractException
{
    const MESSAGE_FORMAT = 'EAN validation failed (%s).';
}