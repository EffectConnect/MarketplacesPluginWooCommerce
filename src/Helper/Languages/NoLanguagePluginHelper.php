<?php

namespace EffectConnect\Marketplaces\Helper\Languages;

use EffectConnect\Marketplaces\Interfaces\LanguagePluginInterface;
use WC_Product;
use WP_Error;
use WP_Term;

/**
 * Fallback for functions in the LanguagePluginInterface in case there is no language plugin installed.
 */
class NoLanguagePluginHelper implements LanguagePluginInterface
{
    /**
     * @return string
     */
    public static function getPluginName(): string
    {
        return 'No multilanguage plugin';
    }

    /**
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    public static function getTermTranslations(int $itemId, string $elementType = ''): array
    {
        return [];
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public static function getProductCategoryTranslationsIds(int $categoryId): array
    {
        return [];
    }

    /**
     * @param int $productId
     * @return array
     */
    public static function getProductTranslationIds(int $productId): array
    {
        return [];
    }

    /**
     * @param string $locale
     * @return void
     */
    public static function setLanguage(string $locale): void
    {
    }

    /**
     * Return active WPML languages as [code] => [translated_name] value pairs.
     *
     * @return array
     */
    public static function getActiveLanguages(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return '';
    }

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getGlobalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string
    {
        $label = wc_attribute_label($wcAttributeKey);
        return strval($label);
    }

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getLocalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string
    {
        $label = wc_attribute_label($wcAttributeKey, $product);
        return strval($label);
    }

    /**
     * @param $term
     * @param string $taxonomy
     * @return array|WP_Error|WP_Term|null
     */
    public static function getTerm($term, string $taxonomy = '')
    {
        return get_term($term, $taxonomy);
    }
}