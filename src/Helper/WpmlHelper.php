<?php

namespace EffectConnect\Marketplaces\Helper;

use WC_Product;

/**
 * WPML plugin helper functions.
 */
class WpmlHelper
{
    /**
     * https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-1215366
     * https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-1215380
     *
     * @param int $itemId
     * @param string $elementType
     * @return mixed
     */
    public static function getTranslations(int $itemId, string $elementType)
    {
        $translationId = apply_filters('wpml_element_trid', null, $itemId, $elementType);
        return apply_filters('wpml_get_element_translations', null, $translationId, $elementType);
    }

    /**
     * @param int $categoryId
     * @return mixed
     */
    public static function getProductCategoryTranslations(int $categoryId)
    {
        return self::getTranslations($categoryId, 'tax_product_cat');
    }

    /**
     * @param $productId
     * @return mixed
     */
    public static function getProductTranslations($productId)
    {
        return self::getTranslations($productId, 'post_product');
    }

    /**
     * @param string $locale
     * @return void
     */
    public static function setLanguage(string $locale)
    {
        do_action('wpml_switch_language', $locale);
    }

    /**
     * @return array
     */
    public static function getActiveLanguageCodes(): array
    {
        return array_keys(self::getActiveLanguages());
    }

    /**
     * Return active WPML languages as [code] => [translated_name] value pairs.
     *
     * @return array
     */
    public static function getActiveLanguages(): array
    {
        $languagesOutput = [];
        $wpmlLanguages = apply_filters('wpml_active_languages', null);
        if (is_array($wpmlLanguages) && count($wpmlLanguages) > 0) {
            foreach ($wpmlLanguages as $wpmlLanguageArray) {
                $languagesOutput[$wpmlLanguageArray['code']] = $wpmlLanguageArray['translated_name'];
            }
        }
        return $languagesOutput;
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return strval(apply_filters('wpml_default_language', null));
    }

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getGlobalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string
    {
        $label = wc_attribute_label($wcAttributeKey, $product);
        $translation = apply_filters( 'wpml_translate_single_string', $label, 'WordPress', 'taxonomy singular name: ' . $label, $locale);
        return strval(is_string($translation) && !empty($translation) ? $translation : $label);
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
        $translations = get_post_meta($product->get_id(), 'attr_label_translations', true);
        return strval($translations[$locale][$wcAttributeKey] ?? $label);
    }
}