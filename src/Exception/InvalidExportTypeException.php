<?php


namespace EffectConnect\Marketplaces\Exception;


class InvalidExportTypeException extends AbstractException
{
    const MESSAGE_FORMAT = 'Offer export type was invalid (%s).';
}