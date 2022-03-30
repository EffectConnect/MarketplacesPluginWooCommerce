<?php

namespace EffectConnect\Marketplaces\Exception;

use Exception;

abstract class AbstractException extends Exception
{
    /**
     * The message format for the error (used for sprintf formatting).
     */
    protected const MESSAGE_FORMAT = '';

    /**
     * The error code for the error.
     */
    protected const ERROR_CODE = 0;

    public function __construct(...$params) {

        $message = sprintf(static::MESSAGE_FORMAT, ...$params);
        parent::__construct($message, static::ERROR_CODE, null);
    }
}