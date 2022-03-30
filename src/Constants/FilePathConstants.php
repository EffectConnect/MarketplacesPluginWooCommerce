<?php

namespace EffectConnect\Marketplaces\Constants;

interface FilePathConstants
{
    /**
     * The directory where all temporary export and log files are situated.
     */
    public const TEMP_DIRECTORY = __DIR__.'/../../temp/';

    public const XML_ROOT_ELEMENT_PRODUCTS = 'products';

    public const XML_ROOT_ELEMENT_ORDERS = 'update';

    public const DIR_NAME = self::TEMP_DIRECTORY . '%s/';

    /**
     * The filename for the generated XML (first parameter is the content type, second parameter is the shop ID and third parameter the current timestamp).
     */
    public const FILE_NAME = 'ec_%s_%s_%s.xml';

    /**
     * The directory where the logs need to be generated.
     */
    public const LOG_DIRECTORY = self::TEMP_DIRECTORY . 'logs/';

    public const CATALOG_DIRECTORY = self::TEMP_DIRECTORY . 'catalog_export/';

    public const OFFER_DIRECTORY = self::TEMP_DIRECTORY . 'offer_update/';

    /**
     * The filename for the generated log file (first parameter is the process, second parameter is the date).
     */
    public const LOG_FILENAME_FORMAT = '%s-%s.log';

    /**
     * The download location for zipped data map (parameter is the current timestamp).
     */
    public const ZIP_FILENAME_FORMAT     = 'data_%s.zip';
}