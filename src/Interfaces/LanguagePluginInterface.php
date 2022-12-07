<?php

namespace EffectConnect\Marketplaces\Interfaces;

use WC_Product;
use WP_Error;
use WP_Term;

interface LanguagePluginInterface
{
    /**
     * @return string
     */
    public static function getPluginName(): string;

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string;

    /**
     * @return array
     */
    public static function getActiveLanguages(): array;

    /**
     * @param int $productId
     * @return array
     */
    public static function getProductTranslationIds(int $productId): array;

    /**
     * @param int $categoryId
     * @return array
     */
    public static function getProductCategoryTranslationsIds(int $categoryId): array;

    /**
     * @param int $itemId
     * @param string $elementType
     * @return array
     */
    public static function getTermTranslations(int $itemId, string $elementType = ''): array;

    /**
     * @param string $locale
     * @return void
     */
    public static function setLanguage(string $locale): void;

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getGlobalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string;

    /**
     * @param string $wcAttributeKey
     * @param WC_Product $product
     * @param string $locale
     * @return string
     */
    public static function getLocalAttributeLabelTranslation(string $wcAttributeKey, WC_Product $product, string $locale): string;

    /**
     * @param $term
     * @param string $taxonomy
     * @return array|WP_Error|WP_Term|null
     */
    public static function getTerm($term, string $taxonomy = '');
}