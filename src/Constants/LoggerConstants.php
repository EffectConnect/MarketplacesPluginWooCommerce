<?php


namespace EffectConnect\Marketplaces\Constants;


interface LoggerConstants
{
    public const CATALOG_EXPORT = 'catalog_export';

    public const OFFER_EXPORT = 'offer_export';

    public const ORDER_IMPORT = 'order_import';

    public const SHIPMENT_EXPORT = 'shipment_export';

    public const OTHER = 'other';

    public const API_CALL = 'api_call';

    public const LOG_CHANNEL = 'EffectConnectMarketplaces';

    public const TIME_ZONE = 'Europe/Amsterdam';

    public const DATE_FORMAT = 'Y_m_d';

    public const STATUS_TYPES =
        [
            'EffectConnectMarketplaces.ERROR' => '#FFBABA80',
            'EffectConnectMarketplaces.WARNING' => '#FFFFBA80',
            'EffectConnectMarketplaces.INFO' => '#A5EE90CC'
        ];

    public const PAGE_SIZE_OPTIONS = ['10', '25', '50', '100', '250'];
}