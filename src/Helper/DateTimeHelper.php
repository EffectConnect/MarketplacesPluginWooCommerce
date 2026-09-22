<?php

namespace EffectConnect\Marketplaces\Helper;

use DateTime;
use DateTimeZone;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use Exception;

class DateTimeHelper
{
    /**
     * @return DateTime
     */
    public static function now(): DateTime
    {
        try {
            return new DateTime('now', (new DateTimeZone(LoggerConstants::TIME_ZONE)));
        } catch (Exception $e) {
            return new DateTime();
        }
    }
}