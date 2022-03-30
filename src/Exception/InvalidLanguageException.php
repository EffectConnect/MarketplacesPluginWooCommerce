<?php

namespace EffectConnect\Marketplaces\Exception;

/**
 * Class FileCreationFailedException
 * @package EffectConnect\Marketplaces\Exception
 * @method string __construct(string $language, string $languages)
 */
class InvalidLanguageException extends AbstractException
{
    /**
     * @inheritDoc
     */
    protected const MESSAGE_FORMAT = 'Connection export language (%s) is not within valid languages list (%s).';
}