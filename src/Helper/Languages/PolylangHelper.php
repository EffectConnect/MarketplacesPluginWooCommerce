<?php

namespace EffectConnect\Marketplaces\Helper\Languages;

use WP_Error;
use WP_Term;

/**
 * Polylang plugin helper functions.
 * Polylang is based on an (older) version of WPML, which means that some functions are the same for Polylang and WPML.
 */
class PolylangHelper extends WpmlHelper
{
    /**
     * @return string
     */
    public static function getPluginName(): string
    {
        return 'Polylang';
    }

    /**
     * @return bool
     */
    public static function polylangPluginActivated(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('polylang-wc/polylang-wc.php');
    }

    /**
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    public static function getTermTranslations(int $itemId, string $elementType = ''): array
    {
        $termIds = static::getTermIds($itemId);
        $terms = [];
        foreach ($termIds as $locale => $termId) {
            $term = get_term($termId);
            if ($term instanceof WP_Term) {
                $terms[$locale] = $term->name;
            }
        }
        return $terms;
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public static function getProductCategoryTranslationsIds(int $categoryId): array
    {
        return static::getTermIds($categoryId);
    }

    /**
     * @param int $productId
     * @return array
     */
    public static function getProductTranslationIds(int $productId): array
    {
        return static::getPostIds($productId);
    }

    /**
     * Return active polylang languages as [slug] => [name] value pairs.
     *
     * @return array
     */
    public static function getActiveLanguages(): array
    {
        $languagesOutput = [];
        $polylangLanguages = function_exists('pll_the_languages') ? pll_the_languages(['raw' => true]) : [];
        if (count($polylangLanguages) > 0) {
            foreach ($polylangLanguages as $polylangLanguageArray) {
                $languagesOutput[$polylangLanguageArray['slug']] = $polylangLanguageArray['name'];
            }
        }
        return $languagesOutput;
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return function_exists('pll_default_language') ? strval(pll_default_language()) : '';
    }

    /**
     * @param int $termId
     * @return array
     */
    protected static function getTermIds(int $termId): array
    {
        $ids = [];
        if (function_exists('pll_get_term')) {
            $languages = array_keys(static::getActiveLanguages());
            foreach ($languages as $language) {
                $termId = intval(pll_get_term($termId, $language));
                if ($termId > 0) {
                    $ids[$language] = $termId;
                }
            }
        }
        return $ids;
    }

    /**
     * @param int $postId
     * @return array
     */
    protected static function getPostIds(int $postId): array
    {
        $ids = [];
        if (function_exists('pll_get_post')) {
            $languages = array_keys(static::getActiveLanguages());
            foreach ($languages as $language) {
                $termId = intval(pll_get_post($postId, $language));
                if ($termId > 0) {
                    $ids[$language] = $termId;
                }
            }
        }
        return $ids;
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