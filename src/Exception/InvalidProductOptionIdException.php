<?php

namespace EffectConnect\Marketplaces\Exception;

class InvalidProductOptionIdException extends AbstractException
{
    const MESSAGE_FORMAT = 'Invalid (empty) product ID.';
}