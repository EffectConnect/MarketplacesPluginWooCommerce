<?php

namespace EffectConnect\Marketplaces\Helper\Languages;

use EffectConnect\Marketplaces\Interfaces\LanguagePluginInterface;
use WC_Product;
use WP_Error;
use WP_Term;

/**
 * WPML plugin helper functions.
 */
class WpmlHelper implements LanguagePluginInterface
{
    /**
     * @return string
     */
    public static function getPluginName(): string
    {
        return 'WPML';
    }

    /**
     * @return bool
     */
    public static function wpmlPluginActivated(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php');
    }

    /**
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    public static function getTermTranslations(int $itemId, string $elementType = ''): array
    {
        $translationsByLocale = [];
        $translations = self::getTranslations($itemId, $elementType);
        foreach ($translations as $translation) {
            $translationsByLocale[$translation->language_code] = $translation->name;
        }
        return $translationsByLocale;
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public static function getProductCategoryTranslationsIds(int $categoryId): array
    {
        $ids = [];
        $translations = self::getTranslations($categoryId, 'tax_product_cat');
        foreach ($translations as $translation) {
            $ids[$translation->language_code] = $translation->element_id;
        }
        return $ids;
    }

    /**
     * @param int $productId
     * @return array
     */
    public static function getProductTranslationIds(int $productId): array
    {
        $ids = [];
        $translations = self::getTranslations($productId, 'post_product');
        foreach ($translations as $translation) {
            $ids[$translation->language_code] = $translation->element_id;
        }
        return $ids;
    }

    /**
     * @param string $locale
     * @return void
     */
    public static function setLanguage(string $locale): void
    {
        do_action('wpml_switch_language', $locale);
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
        $label = wc_attribute_label($wcAttributeKey); // Don't pass product along, since global attributes need to be translated globally (Polylang won't play along if we pass product)
        $translation = apply_filters('wpml_translate_single_string', $label, 'WordPress', 'taxonomy singular name: ' . $label, $locale);
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

    /**
     * https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-1215366
     * https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-1215380
     *
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    protected static function getTranslations(int $itemId, string $elementType): array
    {
        $translationId = apply_filters('wpml_element_trid', null, $itemId, $elementType);
        return (array)apply_filters('wpml_get_element_translations', null, $translationId, $elementType);
    }

    /**
     * The WPML plugin will change the ID of the given term to the term ID of the translation of the default language,
     * when calling the WC function get_term, to avoid this we have to use the WPML global variable $icl_adjust_id_url_filter_off.
     * Source: https://ashiqur.com/wpml-url-override-on-archive-page/
     *
     * @param $term
     * @param string $taxonomy
     * @return array|WP_Error|WP_Term|null
     */
    public static function getTerm($term, string $taxonomy = '')
    {
        global $icl_adjust_id_url_filter_off;

        $orig_flag_value = $icl_adjust_id_url_filter_off;
        $icl_adjust_id_url_filter_off = true;
        $term = get_term($term, $taxonomy);
        $icl_adjust_id_url_filter_off = $orig_flag_value;

        return $term;
    }
}