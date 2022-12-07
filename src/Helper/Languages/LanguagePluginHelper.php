<?php

namespace EffectConnect\Marketplaces\Helper\Languages;

use EffectConnect\Marketplaces\Interfaces\LanguagePluginInterface;
use WC_Product;

/**
 * Language plugin helper functions - supports WPML and Polylang plugins.
 */
class LanguagePluginHelper implements LanguagePluginInterface
{
    /**
     * @var LanguagePluginInterface
     */
    protected static $pluginClassInstance;

    /**
     * @return string
     */
    public static function getPluginName(): string
    {
        return static::getPluginClassInstance()->getPluginName();
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return static::getPluginClassInstance()->getDefaultLanguage();
    }

    /**
     * @return array
     */
    public static function getActiveLanguages(): array
    {
        return static::getPluginClassInstance()->getActiveLanguages();
    }

    /**
     * @return array
     */
    public static function getActiveLanguageCodes(): array
    {
        return array_keys(self::getActiveLanguages());
    }

    /**
     * @param int $productId
     * @return array
     */
    public static function getProductTranslationIds(int $productId): array
    {
        return static::getPluginClassInstance()->getProductTranslationIds($productId);
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public static function getProductCategoryTranslationsIds(int $categoryId): array
    {
        return static::getPluginClassInstance()->getProductCategoryTranslationsIds($categoryId);
    }

    /**
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    public static function getTermTranslations(int $itemId, string $elementType = ''): array
    {
        return static::getPluginClassInstance()->getTermTranslations($itemId, $elementType);
    }

    /**
     * @param mixed $term
     * @param string $taxonomy
     * @return mixed
     */
    public static function getTerm($term, string $taxonomy = '')
    {
        return static::getPluginClassInstance()->getTerm($term, $taxonomy);
    }

    /**
     * @param string $locale
     * @return void
     */
    public static function setLanguage(string $locale): void
    {
        static::getPluginClassInstance()->setLanguage($locale);
    }

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getGlobalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string
    {
        return static::getPluginClassInstance()->getGlobalAttributeLabelTranslation($wcAttributeKey, $product, $locale);
    }

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getLocalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string
    {
        return static::getPluginClassInstance()->getLocalAttributeLabelTranslation($wcAttributeKey, $product, $locale);
    }

    /**
     * @return LanguagePluginInterface
     */
    protected static function getPluginClassInstance(): LanguagePluginInterface
    {
        if (!isset(static::$pluginClassInstance)) {
            if (PolylangHelper::polylangPluginActivated()) {
                static::$pluginClassInstance = new PolylangHelper();
            } elseif (WpmlHelper::wpmlPluginActivated()) {
                static::$pluginClassInstance = new WpmlHelper();
            } else {
                static::$pluginClassInstance = new NoLanguagePluginHelper();
            }
        }
        return static::$pluginClassInstance;
    }
}