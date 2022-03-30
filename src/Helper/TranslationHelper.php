<?php

namespace EffectConnect\Marketplaces\Helper;

class TranslationHelper
{
    /**
     * Use default WordPress translate function '__()' with text domain of plugin.
     *
     * @param string $key
     * @return string
     */
    public static function translate(string $key): string
    {
        return __($key, self::getTextDomain());
    }

    /**
     * @return string
     */
    public static function getTextDomain(): string
    {
        return 'effectconnect_marketplaces';
    }
}